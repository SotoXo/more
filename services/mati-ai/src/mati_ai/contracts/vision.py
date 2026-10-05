from pydantic import BaseModel, ConfigDict, Field


class CatalogValue(BaseModel):
    model_config = ConfigDict(extra="ignore", str_strip_whitespace=True)
    id: int | str
    name: str = Field(min_length=1, max_length=160)
    aliases: list[str] = Field(default_factory=list, max_length=8)
    hex: str | None = Field(default=None, pattern=r"^#?[0-9a-fA-F]{6}$")
    family: str | None = Field(default=None, max_length=160)
    family_hex: str | None = Field(default=None, pattern=r"^#?[0-9a-fA-F]{6}$")


class CategoryPrediction(BaseModel):
    category_id: int | str
    category_name: str
    similarity_score: float = Field(ge=-1, le=1)
