import hashlib
import io
import warnings
from dataclasses import dataclass

import numpy as np
from PIL import Image, ImageCms, ImageOps, UnidentifiedImageError

from mati_ai.core.config import Settings
from mati_ai.core.errors import PipelineError


@dataclass
class ValidatedImage:
    image: Image.Image
    metadata: dict


def validate_image(raw: bytes, settings: Settings) -> ValidatedImage:
    if not raw:
        raise PipelineError("INVALID_IMAGE", "La imagen está vacía.")
    if len(raw) > settings.max_image_bytes:
        raise PipelineError(
            "PAYLOAD_TOO_LARGE", "La imagen supera el tamaño permitido.", 413
        )
    try:
        with warnings.catch_warnings():
            warnings.simplefilter("error", Image.DecompressionBombWarning)
            source = Image.open(io.BytesIO(raw))
            mime = {"JPEG": "image/jpeg", "PNG": "image/png", "WEBP": "image/webp"}.get(
                source.format or ""
            )
            if mime is None:
                raise PipelineError(
                    "UNSUPPORTED_MEDIA",
                    "Solo se permiten imágenes JPEG, PNG o WebP.",
                    415,
                )
            width, height = source.size
            if (
                width * height > settings.max_pixels
                or max(width, height) > settings.max_dimension
            ):
                raise PipelineError(
                    "IMAGE_TOO_LARGE",
                    "Las dimensiones de la imagen exceden el límite.",
                    413,
                )
            if min(width, height) < 16 or getattr(source, "n_frames", 1) != 1:
                raise PipelineError(
                    "INVALID_IMAGE",
                    "Se necesita una imagen estática de al menos 16 píxeles.",
                )
            source.verify()
            source = Image.open(io.BytesIO(raw))
            source.load()
            image = ImageOps.exif_transpose(source)
            profile = image.info.get("icc_profile")
            if profile:
                try:
                    converted = ImageCms.profileToProfile(
                        image,
                        ImageCms.ImageCmsProfile(io.BytesIO(profile)),
                        ImageCms.createProfile("sRGB"),
                        outputMode="RGB",
                    )
                    if converted is None:
                        raise ValueError("Color conversion failed")
                    image = converted
                except (ValueError, OSError, ImageCms.PyCMSError):
                    raise PipelineError(
                        "INVALID_COLOR_PROFILE", "El perfil de color no es válido."
                    ) from None
            else:
                if image.mode in ("RGBA", "LA") or "transparency" in image.info:
                    rgba = image.convert("RGBA")
                    background = Image.new("RGBA", rgba.size, "white")
                    image = Image.alpha_composite(background, rgba)
                image = image.convert("RGB")
            image = image.copy()
            image.info.clear()
    except PipelineError:
        raise
    except (
        UnidentifiedImageError,
        OSError,
        ValueError,
        Image.DecompressionBombError,
        Image.DecompressionBombWarning,
    ):
        raise PipelineError(
            "DECODE_FAILED", "No se pudo decodificar una imagen válida."
        ) from None
    thumb = np.asarray(image.convert("L").resize((9, 8)))
    bits = (thumb[:, 1:] > thumb[:, :-1]).flatten()
    dhash = sum(int(bit) << index for index, bit in enumerate(bits))
    return ValidatedImage(
        image,
        {
            "mime": mime,
            "width": image.width,
            "height": image.height,
            "bytes": len(raw),
            "sha256": hashlib.sha256(raw).hexdigest(),
            "pixel_sha256": hashlib.sha256(
                f"{image.width}x{image.height}:RGB:".encode() + image.tobytes()
            ).hexdigest(),
            "perceptual_hash": f"{dhash:016x}",
            "color_space": "sRGB",
        },
    )
