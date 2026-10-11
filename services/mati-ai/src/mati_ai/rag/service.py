"""Bounded lazy CPU child lifecycle for local text embeddings."""
from concurrent.futures import ProcessPoolExecutor, TimeoutError
import logging
import multiprocessing
from pathlib import Path
import threading
import time

from mati_ai.core.errors import PipelineError
from mati_ai.rag.profile import TextModelProfile
from mati_ai.rag.worker import embed_in_worker, initialize_cpu_worker

logger = logging.getLogger("mati_ai.rag")


class TextEmbeddingService:
    def __init__(self, settings):
        self.settings = settings
        self._pending = threading.BoundedSemaphore(1)
        self._mutex = threading.Lock()
        self._executor = None
        self._profile = None
        self._failed_until = 0.0
        self._loaded = False
        self._closed = False
        self.last_runtime = None

    def _get_profile(self) -> TextModelProfile:
        if not self.settings.text_embedding_enabled:
            raise PipelineError("TEXT_MODEL_UNAVAILABLE", "Los embeddings de texto no están disponibles.", 503, True)
        if self._profile is None:
            self._profile = TextModelProfile.read(
                Path(self.settings.text_embedding_profile_path),
                Path(self.settings.text_embedding_cache_dir),
            )
        return self._profile

    def capabilities(self) -> dict:
        metadata = {
            "enabled": self.settings.text_embedding_enabled, "device": "cpu",
            "vector_space": "text", "loaded": self._loaded,
            "request_downloads": False, "trust_remote_code": False,
            "max_batch": 16, "max_text_codepoints": 8192, "max_pending": 1,
        }
        try:
            profile = self._get_profile()
            return metadata | {
                "profile_available": True, "model": profile.identity,
                "max_tokens": profile.data["max_length"],
                "file_integrity_verified_in_child": self._loaded,
            }
        except Exception:
            return metadata | {"profile_available": False, "model": None,
                               "file_integrity_verified_in_child": False}

    def embed(self, texts: list[str], input_type: str) -> dict:
        if self._closed or time.monotonic() < self._failed_until:
            raise PipelineError("TEXT_MODEL_UNAVAILABLE", "Los embeddings de texto no están disponibles.", 503, True)
        if not self._pending.acquire(blocking=False):
            raise PipelineError("TEXT_EMBEDDING_BUSY", "El servicio de texto está ocupado.", 503, True)
        submitted = False
        try:
            with self._mutex:
                profile = self._get_profile()
                if self._executor is None:
                    self._executor = ProcessPoolExecutor(
                        max_workers=1, mp_context=multiprocessing.get_context("spawn"),
                        initializer=initialize_cpu_worker,
                    )
                future = self._executor.submit(
                    embed_in_worker, profile.source_json, profile.cache_root, texts, input_type
                )
            submitted = True

            def release_when_done(completed):
                try:
                    result = completed.result()
                    self._loaded = bool(result.get("model_loaded", False))
                    if result.get("runtime"):
                        self.last_runtime = result["runtime"]
                except Exception:
                    self._loaded = False
                finally:
                    self._pending.release()

            future.add_done_callback(release_when_done)
            try:
                result = future.result(timeout=self.settings.text_embedding_timeout_seconds)
            except TimeoutError as exc:
                # A running job cannot safely be cancelled. Keep capacity held
                # until the completion callback fires; never queue another job.
                raise PipelineError(
                    "TEXT_EMBEDDING_TIMEOUT", "El servicio de texto tardó demasiado.", 503, True
                ) from exc
            if result.get("status") != "completed":
                status = result.get("http_status", 503)
                code = result.get("code", "TEXT_MODEL_UNAVAILABLE")
                if status == 422 and code == "TEXT_TOO_LONG":
                    raise PipelineError(code, "La consulta supera el límite de tokens del modelo.", 422)
                self._failed_until = time.monotonic() + self.settings.text_embedding_retry_seconds
                raise PipelineError("TEXT_MODEL_UNAVAILABLE", "Los embeddings de texto no están disponibles.", 503, True)
            logger.info(
                "text_embedding model=%s revision=%s child_rss_mib=%.3f child_peak_rss_mib=%.3f cold_load_ms=%.3f threads=%s",
                profile.identity["model_id"], profile.identity["model_revision"],
                result["runtime"]["child_rss_mib"], result["runtime"]["child_peak_rss_mib"],
                result["timing_ms"]["cold_load"], result["runtime"]["threads"],
            )
            return result
        except PipelineError:
            raise
        except Exception as exc:
            self._loaded = False
            self._failed_until = time.monotonic() + self.settings.text_embedding_retry_seconds
            with self._mutex:
                if self._executor is not None:
                    self._executor.shutdown(wait=False, cancel_futures=True)
                    self._executor = None
            raise PipelineError(
                "TEXT_MODEL_UNAVAILABLE", "Los embeddings de texto no están disponibles.", 503, True
            ) from exc
        finally:
            if not submitted:
                self._pending.release()

    def close(self) -> None:
        self._closed = True
        with self._mutex:
            if self._executor is not None:
                self._executor.shutdown(wait=False, cancel_futures=True)
                self._executor = None
