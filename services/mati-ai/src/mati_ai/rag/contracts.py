"""Strict internal text-space contract; independent of visual embeddings."""
from typing import Annotated, Literal
import unicodedata
from uuid import UUID

from pydantic import BaseModel, ConfigDict, Field, StringConstraints, field_validator

Text = Annotated[
    str, StringConstraints(strict=True, min_length=1, max_length=8192)
]


class TextEmbeddingRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")
    schema_version: Literal["mati-text-embed-request-1"]
    request_id: UUID
    texts: list[Text] = Field(min_length=1, max_length=16)
    input_type: Literal["query", "document"]

    @field_validator("texts")
    @classmethod
    def valid_unicode(cls, texts: list[str]) -> list[str]:
        for text in texts:
            try:
                text.encode("utf-8")
            except UnicodeError as exc:
                raise ValueError("Invalid Unicode text") from exc
            if not unicodedata.normalize("NFC", text).strip():
                raise ValueError("Empty text")
        return texts


class TextModelIdentity(BaseModel):
    model_config = ConfigDict(extra="forbid")
    model_id: str
    model_revision: str
    dimensions: int
    normalization: Literal["l2"]
    preprocess_version: str
    vector_space: Literal["text"]
    pipeline_version: Literal["text-embedding-1.0.0"]


class TextEnvelope(BaseModel):
    request_id: str
    contract_version: Literal["1"] = "1"
    status: Literal["completed"] = "completed"
    data: dict
    warnings: list[str] = Field(default_factory=list)
    abstentions: list[str] = Field(default_factory=list)
    model: TextModelIdentity
    timing_ms: dict[str, float]
    human_review_required: bool = False
