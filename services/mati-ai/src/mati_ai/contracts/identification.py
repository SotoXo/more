from pydantic import AliasChoices, BaseModel, ConfigDict, Field

from mati_ai.contracts.embedding import Embedding


class MatchItem(BaseModel):
    model_config = ConfigDict(extra="ignore", allow_inf_nan=False)
    candidate_id: int | str | None = None
    sku: str | None = Field(default=None, max_length=160)
    gtin: str | None = Field(default=None, max_length=30)
    model_code: str | None = Field(default=None, max_length=160)
    brand: str | None = Field(default=None, max_length=160)
    text: str | None = Field(default=None, max_length=4000)
    category: int | str | None = Field(
        default=None, validation_alias=AliasChoices("category_id", "category")
    )
    color: int | str | None = Field(
        default=None, validation_alias=AliasChoices("color_id", "color")
    )
    embedding: Embedding | None = None
    image_sha256: str | None = Field(default=None, max_length=64)


class MatchRequest(BaseModel):
    request_id: str | None = Field(default=None, max_length=100)
    query: MatchItem
    candidates: list[MatchItem] = Field(default_factory=list, max_length=250)
    limit: int = Field(default=10, ge=1, le=50)


