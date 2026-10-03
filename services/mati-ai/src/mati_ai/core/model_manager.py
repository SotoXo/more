import threading
import time
from typing import Any

from PIL import Image

from mati_ai.contracts.common import PIPELINE_VERSION
from mati_ai.core.config import Settings
from mati_ai.core.errors import PipelineError


class ModelManager:
    """One cached visual model per worker; lock bounds both loading and inference."""

    def __init__(self, settings: Settings):
        self.settings = settings
        self._model: Any = None
        self._processor: Any = None
        self._lock = threading.Lock()
        self._failed_until = 0.0
        self.device = "cpu"
        self.revision = settings.model_revision
        self.load_ms = 0.0

    @property
    def identity(self) -> dict:
        return {
            "model_id": self.settings.model_id,
            "model_revision": self.revision,
            "pipeline_version": PIPELINE_VERSION,
        }

    def _load(self) -> None:
        if self._model is not None:
            return
        if time.monotonic() < self._failed_until:
            raise PipelineError(
                "MODEL_UNAVAILABLE", "El modelo visual no está disponible.", 503, True
            )
        start = time.perf_counter()
        try:
            import torch
            from transformers import AutoModel, AutoProcessor

            self.device = (
                "cuda"
                if self.settings.device != "cpu" and torch.cuda.is_available()
                else "cpu"
            )
            options = {
                "revision": self.settings.model_revision,
                "local_files_only": self.settings.local_files_only,
                "trust_remote_code": False,
            }
            processor = AutoProcessor.from_pretrained(self.settings.model_id, **options)
            model = AutoModel.from_pretrained(self.settings.model_id, **options)
            try:
                model = model.to(self.device).eval()
            except (RuntimeError, torch.cuda.OutOfMemoryError):
                self.device = "cpu"
                model = model.to("cpu").eval()
                if torch.cuda.is_available():
                    torch.cuda.empty_cache()
            self._processor, self._model = processor, model
            self.revision = (
                getattr(model.config, "_commit_hash", None)
                or self.settings.model_revision
            )
            self.load_ms = round((time.perf_counter() - start) * 1000, 3)
        except Exception:  # noqa: BLE001 - isolate third-party/runtime failures at the service boundary
            self._model = self._processor = None
            self._failed_until = time.monotonic() + self.settings.model_retry_seconds
            raise PipelineError(
                "MODEL_UNAVAILABLE",
                "El modelo visual no está disponible; puede continuar manualmente.",
                503,
                True,
            ) from None

    def infer(self, image: Image.Image, prompts: list[str]) -> dict:
        if not self._lock.acquire(timeout=self.settings.inference_lock_timeout):
            raise PipelineError(
                "GPU_BUSY", "El modelo está ocupado. Intente nuevamente.", 503, True
            )
        try:
            self._load()
            import torch

            start = time.perf_counter()
            inputs = self._processor(
                text=prompts,
                images=image,
                padding="max_length",
                truncation=True,
                return_tensors="pt",
            ).to(self.device)
            if self.device == "cuda":
                torch.cuda.reset_peak_memory_stats()
            with torch.inference_mode():
                try:
                    output = self._model(**inputs)
                except (RuntimeError, torch.cuda.OutOfMemoryError):
                    if self.device != "cuda":
                        raise
                    # Release the single visual model's CUDA allocation before CPU fallback.
                    self._model = self._model.to("cpu")
                    self.device = "cpu"
                    inputs = inputs.to("cpu")
                    torch.cuda.empty_cache()
                    output = self._model(**inputs)
            vectors = torch.nn.functional.normalize(
                output.image_embeds.float(), p=2, dim=-1
            )
            texts = torch.nn.functional.normalize(
                output.text_embeds.float(), p=2, dim=-1
            )
            similarities = (vectors @ texts.T)[0].detach().cpu().tolist()
            if self.device == "cuda":
                torch.cuda.synchronize()
            return {
                "similarities": similarities,
                "embedding": vectors[0].detach().cpu().tolist(),
                "model": self.identity,
                "metrics": {
                    "model_load": self.load_ms,
                    "inference": round((time.perf_counter() - start) * 1000, 3),
                },
                "device": self.device,
                "vram_peak_mb": round(torch.cuda.max_memory_allocated() / 1024**2, 2)
                if self.device == "cuda"
                else 0.0,
            }
        except PipelineError:
            raise
        except Exception:  # noqa: BLE001 - isolate third-party/runtime failures at the service boundary
            raise PipelineError(
                "MODEL_UNAVAILABLE",
                "No fue posible completar la inferencia visual.",
                503,
                True,
            ) from None
        finally:
            self._lock.release()
