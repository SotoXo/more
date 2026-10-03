"""Closed configuration and bounded contracts for external evidence."""

from typing import Literal

from pydantic import BaseModel, ConfigDict, Field

Rights = Literal[
    "owned", "licensed", "provider_authorized", "public_domain", "unknown", "prohibited"
]
PUBLISHABLE_RIGHTS = {"owned", "licensed", "provider_authorized", "public_domain"}


class ExternalQuery(BaseModel):
    model_config = ConfigDict(extra="forbid", str_max_length=1000, allow_inf_nan=False)
    request_id: str | None = None
    gtin: str | None = Field(default=None, max_length=32)
    brand: str | None = Field(default=None, max_length=160)
    model_code: str | None = Field(default=None, max_length=160)
    query: str | None = None
    category: str | None = Field(default=None, max_length=160)
    color: str | None = Field(default=None, max_length=160)
    source_ids: list[str] = Field(default_factory=list, max_length=10)
    limit: int = Field(default=5, ge=1, le=10)
    embedding: list[float] | None = Field(default=None, max_length=2048)
    model_id: str | None = None
    model_revision: str | None = None


class ExternalImage(BaseModel):
    source: str
    original_url: str
    canonical_product_url: str
    width: int | None = None
    height: int | None = None
    sha256: str | None = None
    fetched_at: str | None = None
    rights_status: Rights = "unknown"
    rights_evidence: str | None = None
    warning: str | None = None


class ExternalProduct(BaseModel):
    model_config = ConfigDict(extra="ignore", allow_inf_nan=False)
    source: str
    source_id: str
    source_type: str = "manual"
    external_id: str = ""
    title: str = Field(max_length=500)
    brand: str | None = None
    model_code: str | None = None
    gtin: str | None = None
    description: str | None = Field(default=None, max_length=10000)
    category: str | None = None
    color: str | None = None
    canonical_url: str
    images: list[ExternalImage] = Field(default_factory=list)
    price: str | None = None
    currency: str | None = None
    availability: str | None = None
    fetched_at: str | None = None
    rights_status: Rights = "unknown"
    rights_evidence: str | None = None
    visual_score: float | None = None
    text_score: float = 0
    identifier_score: float = 0
    final_score: float = 0
    components: dict[str, float] = Field(default_factory=dict)
    conflicts: list[str] = Field(default_factory=list)
    warnings: list[str] = Field(default_factory=list)
    embedding: list[float] | None = Field(default=None, exclude=True)
    model_id: str | None = Field(default=None, exclude=True)
    model_revision: str | None = Field(default=None, exclude=True)


class SourceConfig(BaseModel):
    model_config = ConfigDict(extra="forbid")
    id: str = Field(pattern=r"^[a-z0-9_-]{1,64}$")
    name: str = Field(max_length=160)
    adapter: Literal["manual", "html"]
    source_type: Literal[
        "supplier",
        "official_brand",
        "authorized_api",
        "public_catalog",
        "html",
        "manual",
    ] = "manual"
    enabled: bool = False
    domains: list[str] = Field(default_factory=list, max_length=20)
    terms_approved: bool = False
    terms_url: str | None = None
    reviewed_at: str | None = None
    rights_status: Rights = "unknown"
    rights_evidence: str | None = None
    min_interval_seconds: float = Field(default=1, ge=0.1, le=30)
    timeout_seconds: float = Field(default=5, gt=0, le=15)
    max_bytes: int = Field(default=2_000_000, ge=1024, le=10_000_000)
    max_pages: int = Field(default=3, ge=1, le=5)
    search_url: str | None = None
    selectors: dict[str, str] = Field(default_factory=dict)
    products: list[dict] = Field(default_factory=list, max_length=10000)


class ImageRequest(BaseModel):
    model_config = ConfigDict(extra="forbid", str_max_length=2048)
    request_id: str | None = None
    source_id: str
    product_url: str
    original_url: str
    purpose: Literal["reference", "publication"] = "publication"
