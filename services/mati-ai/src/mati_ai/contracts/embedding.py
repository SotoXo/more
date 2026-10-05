"""Versioned projected image embeddings; metadata preserves legacy consumers."""

import math
import re
from typing import Literal

from pydantic import BaseModel, ConfigDict, Field, model_validator

EMBEDDING_VERSION = "projected-image-l2-1"
PREPROCESS_VERSION = "srgb-exif-transformers-image-1"
RESOLVED_REVISION_PATTERN = r"^[0-9a-fA-F]{40}(?:[0-9a-fA-F]{24})?$"


def resolved_revision(value: str | None) -> str | None:
    return value.lower() if value and re.fullmatch(RESOLVED_REVISION_PATTERN, value) else None


class Embedding(BaseModel):
    """Old values/model fields remain readable; new vectors carry full identity."""

    model_config = ConfigDict(allow_inf_nan=False)
    values: list[float] = Field(min_length=1, max_length=2048)
    model_id: str = Field(min_length=1, max_length=200)
    model_revision: str = Field(min_length=1, max_length=120)
    dimension: int | None = Field(default=None, ge=1, le=2048)
    pipeline_version: str | None = Field(default=None, max_length=120)
    embedding_version: str | None = Field(default=None, max_length=120)
    preprocess_version: str | None = Field(default=None, max_length=120)
    normalization: Literal["l2"] | None = None
    normalized: bool | None = None
    norm: float | None = Field(default=None, gt=0)
    dimension_source: Literal["generated_vector"] | None = None

    @model_validator(mode="after")
    def validate_vector_metadata(self):
        if self.dimension is not None and self.dimension != len(self.values):
            raise ValueError("Embedding dimension must match the actual vector length.")
        actual_norm = math.hypot(*self.values)
        if not math.isfinite(actual_norm) or actual_norm <= 0:
            raise ValueError("Embedding must have a finite, nonzero norm.")
        if self.norm is not None and not math.isclose(self.norm, actual_norm, rel_tol=1e-4, abs_tol=1e-5):
            raise ValueError("Embedding norm must match its values.")
        if (self.normalized is True or self.normalization == "l2") and not math.isclose(actual_norm, 1.0, rel_tol=1e-4, abs_tol=1e-5):
            raise ValueError("L2 normalized embeddings must have unit norm.")
        if self.embedding_version is not None:
            if self.normalized is not True:
                raise ValueError("Versioned embeddings must declare verified L2 normalization.")
            if resolved_revision(self.model_revision) is None:
                raise ValueError("Versioned embeddings require an immutable checkpoint revision.")
            if any(value is None for value in (
                self.dimension, self.pipeline_version, self.preprocess_version,
                self.normalization, self.normalized, self.norm,
            )):
                raise ValueError("Versioned embeddings require complete generation metadata.")
        return self


def compatible_embeddings(left: Embedding, right: Embedding) -> bool:
    """Never mix a legacy vector with a fully versioned embedding space."""
    return (
        resolved_revision(left.model_revision) is not None
        and resolved_revision(right.model_revision) is not None
        and bool(left.pipeline_version)
        and bool(right.pipeline_version)
        and left.model_id == right.model_id
        and left.model_revision == right.model_revision
        and len(left.values) == len(right.values)
        and left.pipeline_version == right.pipeline_version
        and left.embedding_version == right.embedding_version
        and left.preprocess_version == right.preprocess_version
        and left.normalization == right.normalization
    )


