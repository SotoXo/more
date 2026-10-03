import threading
import time
from abc import ABC, abstractmethod
from typing import Any

import numpy as np
from PIL import Image

from mati_ai.core.config import Settings
from mati_ai.core.errors import PipelineError


class OCRProvider(ABC):
    @abstractmethod
    def read(self, image: Image.Image) -> dict: ...


class EasyOCRProvider(OCRProvider):
    """CPU OCR deliberately avoids competing with the visual model for 8 GB VRAM."""

    def __init__(self, settings: Settings):
        self.settings = settings
        self._reader: Any = None
        self._lock = threading.Lock()
        self._failed_until = 0.0

    def read(self, image: Image.Image) -> dict:
        if not self.settings.ocr_enabled or time.monotonic() < self._failed_until:
            raise PipelineError(
                "OCR_UNAVAILABLE",
                "El OCR no está disponible; puede ingresar la etiqueta manualmente.",
                503,
                True,
            )
        if not self._lock.acquire(timeout=2):
            raise PipelineError(
                "OCR_BUSY", "El lector de etiquetas está ocupado.", 503, True
            )
        try:
            if self._reader is None:
                import easyocr

                self._reader = easyocr.Reader(
                    ["es", "en"],
                    gpu=False,
                    download_enabled=self.settings.ocr_download_enabled,
                    model_storage_directory=self.settings.ocr_model_directory,
                    verbose=False,
                )
            preview = image.copy()
            preview.thumbnail((1800, 1800))
            scale_x, scale_y = (
                image.width / preview.width,
                image.height / preview.height,
            )
            regions = [
                {
                    "bounding_box": [
                        [float(x) * scale_x, float(y) * scale_y] for x, y in box
                    ],
                    "text": text,
                    "confidence_score": float(confidence),
                }
                for box, text, confidence in self._reader.readtext(
                    np.asarray(preview), detail=1, paragraph=False, workers=0
                )
            ]
            return {
                "raw_text": "\n".join(item["text"] for item in regions),
                "regions": regions,
                "provider": "easyocr_cpu",
            }
        except PipelineError:
            raise
        except Exception:  # noqa: BLE001 - isolate third-party/runtime failures at the service boundary
            self._failed_until = time.monotonic() + self.settings.model_retry_seconds
            raise PipelineError(
                "OCR_UNAVAILABLE",
                "El OCR no está disponible; puede ingresar la etiqueta manualmente.",
                503,
                True,
            ) from None
        finally:
            self._lock.release()
