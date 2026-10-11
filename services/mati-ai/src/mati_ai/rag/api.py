"""Authenticated internal endpoint for the independent local text space."""
import time

from fastapi import APIRouter, Depends, Request

from mati_ai.api.dependencies import require_service_token
from mati_ai.rag.contracts import TextEmbeddingRequest, TextEnvelope, TextModelIdentity

router = APIRouter(prefix="/api/v1/rag", dependencies=[Depends(require_service_token)])


@router.post("/embed")
def embed_text(request: Request, payload: TextEmbeddingRequest):
    started = time.perf_counter()
    request.state.request_id = str(payload.request_id)
    result = request.app.state.text_embedding_service.embed(payload.texts, payload.input_type)
    identity = TextModelIdentity.model_validate(result["data"]["model"])
    request.state.model_id = identity.model_id
    return TextEnvelope(
        request_id=request.state.request_id, data=result["data"], model=identity,
        warnings=["TEXT_DOCUMENT_TRUNCATED"] if result["data"]["truncated"] else [],
        timing_ms=result["timing_ms"] | {"total": (time.perf_counter() - started) * 1000},
    ).model_dump()
