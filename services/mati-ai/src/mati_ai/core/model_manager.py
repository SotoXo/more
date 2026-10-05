import math
import threading
import time
from pathlib import Path
from typing import Any, Callable

from PIL import Image

from mati_ai.contracts.common import PIPELINE_VERSION
from mati_ai.contracts.embedding import (
    EMBEDDING_VERSION,
    PREPROCESS_VERSION,
    resolved_revision,
)
from mati_ai.core.config import Settings
from mati_ai.core.errors import PipelineError


class ModelManager:
    """One cached visual model per worker; lock bounds loading and inference."""

    def __init__(self, settings: Settings):
        self.settings = settings
        self._model: Any = None
        self._processor: Any = None
        self._lock = threading.Lock()
        self._failed_until = 0.0
        self._generated_dimension: int | None = None
        self._cached_config_metadata: dict | None = None
        self.device = "cpu"
        self.revision: str | None = None
        self.load_ms = 0.0

    @property
    def identity(self) -> dict:
        return {
            "model_id": self.settings.model_id,
            "model_revision": self.revision or "unresolved",
            "pipeline_version": PIPELINE_VERSION,
        }

    def _revision(self, config: Any) -> str | None:
        revision = resolved_revision(getattr(config, "_commit_hash", None))
        if revision is not None:
            return revision
        try:
            from huggingface_hub import try_to_load_from_cache

            location = try_to_load_from_cache(
                self.settings.model_id,
                "config.json",
                revision=self.settings.model_revision,
            )
            if isinstance(location, str):
                candidate = resolved_revision(Path(location).parent.name)
                if candidate:
                    return candidate
        except Exception:  # noqa: BLE001 - cache metadata is optional, no download
            pass
        # A pinned revision passed to from_pretrained names the exact artifact.
        return resolved_revision(self.settings.model_revision)

    @staticmethod
    def _config_dimension(config: Any) -> int | None:
        model_type = getattr(config, "model_type", None)
        if model_type == "clip":
            dimension = getattr(config, "projection_dim", None)
        elif model_type in ("siglip", "siglip2"):
            vision = getattr(config, "vision_config", None)
            dimension = getattr(vision, "hidden_size", None)
        else:
            return None
        return int(dimension) if isinstance(dimension, int) and dimension > 0 else None

    def capabilities_metadata(self) -> dict:
        """Read configured cache metadata; never load weights or download assets."""
        if self._model is not None:
            dimension = self._generated_dimension
            source = "generated_vector" if dimension is not None else "loaded_model_config"
            if dimension is None:
                projection = getattr(self._model, "visual_projection", None)
                dimension = getattr(projection, "out_features", None)
                if dimension is None:
                    vision = getattr(self._model, "vision_model", None)
                    probe = getattr(getattr(vision, "head", None), "probe", None)
                    dimension = int(probe.shape[-1]) if probe is not None else self._config_dimension(self._model.config)
            metadata = {
                "model_revision": self.revision,
                "embedding_dimension": int(dimension) if dimension is not None else None,
                "embedding_dimension_source": source if dimension is not None else None,
            }
        else:
            if self._cached_config_metadata is None:
                metadata = {
                    "model_revision": None,
                    "embedding_dimension": None,
                    "embedding_dimension_source": None,
                }
                try:
                    from transformers import AutoConfig

                    config = AutoConfig.from_pretrained(
                        self.settings.model_id,
                        revision=self.settings.model_revision,
                        local_files_only=True,
                        trust_remote_code=False,
                    )
                    dimension = self._config_dimension(config)
                    metadata = {
                        "model_revision": self._revision(config),
                        "embedding_dimension": dimension,
                        "embedding_dimension_source": "cached_model_config" if dimension is not None else None,
                    }
                except Exception:  # noqa: BLE001 - absent cache means unknown, not a guess
                    pass
                self._cached_config_metadata = metadata
            metadata = dict(self._cached_config_metadata)
        return {
            **metadata,
            "configured_model_revision": self.settings.model_revision,
            "pipeline_version": PIPELINE_VERSION,
            "embedding_version": EMBEDDING_VERSION,
            "preprocess_version": PREPROCESS_VERSION,
            "normalization": "l2",
            "dimension_runtime_verified": self._generated_dimension is not None,
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
            revision = self._revision(model.config)
            if revision is None:
                raise PipelineError(
                    "MODEL_REVISION_UNRESOLVED",
                    "No se pudo identificar una revisión inmutable del modelo visual.",
                    503,
                )
            try:
                model = model.to(self.device).eval()
            except (RuntimeError, torch.cuda.OutOfMemoryError):
                self.device = "cpu"
                model = model.to("cpu").eval()
                if torch.cuda.is_available():
                    torch.cuda.empty_cache()
            # Publish the cache only once both components and identity are ready.
            self.revision = revision
            self._processor, self._model = processor, model
            self.load_ms = round((time.perf_counter() - start) * 1000, 3)
        except PipelineError:
            self._model = self._processor = None
            self._failed_until = time.monotonic() + self.settings.model_retry_seconds
            raise
        except Exception:  # noqa: BLE001 - isolate third-party/runtime failures
            self._model = self._processor = None
            self._failed_until = time.monotonic() + self.settings.model_retry_seconds
            raise PipelineError(
                "MODEL_UNAVAILABLE",
                "El modelo visual no está disponible; puede continuar manualmente.",
                503,
                True,
            ) from None

    def _run(self, inputs: Any, forward: Callable) -> Any:
        """Run the selected visual operation with the existing CPU fallback."""
        import torch

        with torch.inference_mode():
            try:
                return forward(inputs)
            except (RuntimeError, torch.cuda.OutOfMemoryError):
                if self.device != "cuda":
                    raise
                self._model = self._model.to("cpu")
                self.device = "cpu"
                inputs = inputs.to("cpu")
                torch.cuda.empty_cache()
                return forward(inputs)

    @staticmethod
    def _normalized_vectors(features: Any):
        import torch

        if (
            not isinstance(features, torch.Tensor)
            or features.ndim != 2
            or features.shape[0] < 1
            or not 1 <= features.shape[-1] <= 2048
        ):
            raise PipelineError("INVALID_VISUAL_EMBEDDING", "El modelo devolvió un vector visual inválido.", 503)
        vectors = features.float()
        norms = torch.linalg.vector_norm(vectors, ord=2, dim=-1)
        if not bool(torch.isfinite(vectors).all()) or not bool(torch.isfinite(norms).all()) or bool((norms <= 0).any()):
            raise PipelineError("INVALID_VISUAL_EMBEDDING", "El vector visual no contiene evidencia numérica válida.", 503)
        return torch.nn.functional.normalize(vectors, p=2, dim=-1)

    def _embedding_metadata(self, vector: Any) -> dict:
        values = vector.detach().cpu().tolist()
        dimension = len(values)
        norm = math.sqrt(math.fsum(value * value for value in values))
        if resolved_revision(self.revision) is None or not math.isclose(norm, 1.0, rel_tol=1e-4, abs_tol=1e-5):
            raise PipelineError("INVALID_VISUAL_EMBEDDING", "El embedding visual no tiene identidad o normalización verificable.", 503)
        self._generated_dimension = dimension
        return {
            "values": values,
            "dimension": dimension,
            **self.identity,
            "embedding_version": EMBEDDING_VERSION,
            "preprocess_version": PREPROCESS_VERSION,
            "normalization": "l2",
            "normalized": True,
            "norm": norm,
            "dimension_source": "generated_vector",
        }

    def _metrics(self, started: float) -> dict:
        import torch

        if self.device == "cuda":
            torch.cuda.synchronize()
        return {
            "metrics": {
                "model_load": self.load_ms,
                "inference": round((time.perf_counter() - started) * 1000, 3),
            },
            "device": self.device,
            "vram_peak_mb": round(torch.cuda.max_memory_allocated() / 1024**2, 2) if self.device == "cuda" else 0.0,
        }

    def infer(self, image: Image.Image, prompts: list[str]) -> dict:
        if not self._lock.acquire(timeout=self.settings.inference_lock_timeout):
            raise PipelineError("GPU_BUSY", "El modelo está ocupado. Intente nuevamente.", 503, True)
        try:
            self._load()
            import torch

            started = time.perf_counter()
            inputs = self._processor(
                text=prompts, images=image, padding="max_length",
                truncation=True, return_tensors="pt",
            ).to(self.device)
            if self.device == "cuda":
                torch.cuda.reset_peak_memory_stats()
            output = self._run(inputs, lambda batch: self._model(**batch))
            vectors = self._normalized_vectors(output.image_embeds)
            texts = self._normalized_vectors(output.text_embeds)
            similarities = (vectors @ texts.T)[0].detach().cpu().tolist()
            embedding = self._embedding_metadata(vectors[0])
            return {
                "similarities": similarities,
                "embedding": embedding["values"],
                "embedding_metadata": embedding,
                "model": self.identity,
                **self._metrics(started),
            }
        except PipelineError:
            raise
        except Exception:  # noqa: BLE001 - third-party/runtime boundary
            raise PipelineError(
                "MODEL_UNAVAILABLE", "No fue posible completar la inferencia visual.", 503, True
            ) from None
        finally:
            self._lock.release()

    def embed_image(self, image: Image.Image) -> dict:
        """Same projected image space as infer(), with no text classification."""
        if not self._lock.acquire(timeout=self.settings.inference_lock_timeout):
            raise PipelineError("GPU_BUSY", "El modelo está ocupado. Intente nuevamente.", 503, True)
        try:
            self._load()
            import torch

            started = time.perf_counter()
            inputs = self._processor(images=image, return_tensors="pt").to(self.device)
            if self.device == "cuda":
                torch.cuda.reset_peak_memory_stats()
            output = self._run(inputs, lambda batch: self._model.get_image_features(**batch))
            # Transformers 4 returned a tensor; Transformers 5 returns pooled
            # model outputs (CLIP's pooler already includes visual_projection).
            if isinstance(output, torch.Tensor):
                features = output
            else:
                features = getattr(output, "pooler_output", None)
                if features is None and isinstance(output, tuple) and len(output) >= 2:
                    features = output[1]
            vectors = self._normalized_vectors(features)
            embedding = self._embedding_metadata(vectors[0])
            return {
                "embedding": embedding["values"],
                "embedding_metadata": embedding,
                "model": self.identity,
                **self._metrics(started),
            }
        except PipelineError:
            raise
        except Exception:  # noqa: BLE001 - third-party/runtime boundary
            raise PipelineError(
                "MODEL_UNAVAILABLE", "No fue posible obtener el embedding visual.", 503, True
            ) from None
        finally:
            self._lock.release()
