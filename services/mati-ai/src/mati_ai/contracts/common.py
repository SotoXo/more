from typing import Any, Literal

from fastapi import Request
from pydantic import BaseModel, Field

PIPELINE_VERSION = "registration-1.0.0"
Status = Literal["completed", "partial", "insufficient_evidence", "degraded", "failed"]


class ModelIdentity(BaseModel):
    model_id: str = "deterministic"
    model_revision: str = "1"
    pipeline_version: str = PIPELINE_VERSION


class Envelope(BaseModel):
    request_id: str
    contract_version: str = "1"
    status: Literal[
        "completed", "partial", "insufficient_evidence", "degraded", "failed"
    ] = "completed"
    data: dict[str, Any] = Field(default_factory=dict)
    warnings: list[str] = Field(default_factory=list)
    abstentions: list[str] = Field(default_factory=list)
    model: ModelIdentity = Field(default_factory=ModelIdentity)
    timing_ms: dict[str, float] = Field(default_factory=dict)
    human_review_required: bool = True


def envelope(
    request: Request,
    data: dict,
    status: Status = "completed",
    warnings: list | None = None,
    abstentions: list | None = None,
    model: dict | None = None,
    timing_ms: dict | None = None,
) -> dict:
    return Envelope(
        request_id=request.state.request_id,
        status=status,
        data=data,
        warnings=warnings or [],
        abstentions=abstentions or [],
        model=ModelIdentity.model_validate(model or {}),
        timing_ms=timing_ms or {},
    ).model_dump()
