import base64
import hashlib
import io

from mati_ai.core.config import Settings
from mati_ai.core.errors import PipelineError
from mati_ai.vision.validation import validate_image

ALLOWED_RIGHTS = {"owned", "licensed", "provider_authorized", "public_domain"}


def prepare_publication(
    raw: bytes, settings: Settings, approved: bool, rights_status: str, approval_id: str
) -> dict:
    if not approved or rights_status not in ALLOWED_RIGHTS or not approval_id.strip():
        raise PipelineError(
            "RIGHTS_NOT_APPROVED",
            "La imagen requiere aprobación y derechos de publicación.",
            403,
        )
    validated = validate_image(raw, settings)
    derivatives = []
    for profile, size in [("thumbnail", 240), ("card", 640), ("detail", 1600)]:
        image = validated.image.copy()
        image.thumbnail((size, size))
        image.info.clear()
        buffer = io.BytesIO()
        image.save(buffer, format="WEBP", quality=90, method=4, exif=b"")
        content = buffer.getvalue()
        derivatives.append(
            {
                "profile": profile,
                "profile_version": "1",
                "width": image.width,
                "height": image.height,
                "mime": "image/webp",
                "sha256": hashlib.sha256(content).hexdigest(),
                "bytes": len(content),
                "content_base64": base64.b64encode(content).decode("ascii"),
            }
        )
    return {
        "source_sha256": validated.metadata["sha256"],
        "rights_status": rights_status,
        "approval_id": approval_id,
        "derivatives": derivatives,
        "transforms": [
            "exif_orientation",
            "srgb",
            "strip_metadata",
            "resize_contain",
            "webp",
        ],
        "original_modified": False,
    }
