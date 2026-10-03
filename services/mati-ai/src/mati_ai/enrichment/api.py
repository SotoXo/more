import time

from fastapi import APIRouter, Depends, Request

from mati_ai.api.dependencies import require_service_token
from mati_ai.contracts.common import envelope
from mati_ai.core.errors import PipelineError

from .contracts import ExternalQuery, ImageRequest
from .safe_fetch import FetchError
from .service import load_service

router = APIRouter(
    prefix="/api/v1/products", dependencies=[Depends(require_service_token)]
)


def get_service(request: Request):
    try:
        return getattr(request.app.state, "enrichment_service", None) or load_service(
            request.app.state.settings.external_sources_file or ""
        )
    except (OSError, ValueError, KeyError, FetchError):
        raise PipelineError(
            "SOURCE_CONFIGURATION_INVALID",
            "Las fuentes externas no están configuradas correctamente.",
            status_code=503,
        ) from None


@router.post("/external-search")
def external_search(query: ExternalQuery, request: Request):
    start = time.perf_counter()
    result = get_service(request).search(query)
    warnings = result.pop("warnings")
    return envelope(
        request,
        result,
        status="partial" if warnings else "completed",
        warnings=warnings,
        timing_ms={"total": round((time.perf_counter() - start) * 1000, 2)},
    )


@router.post("/external-image")
def external_image(query: ImageRequest, request: Request):
    start = time.perf_counter()
    try:
        result = get_service(request).image(query)
    except FetchError as exc:
        raise PipelineError(
            exc.code,
            "No fue posible obtener una imagen externa autorizada.",
            status_code=422,
        ) from None
    return envelope(
        request,
        result,
        timing_ms={"total": round((time.perf_counter() - start) * 1000, 2)},
    )
