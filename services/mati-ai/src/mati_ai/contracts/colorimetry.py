"""Additive garment-only colorimetry contract; commercial truth stays in Laravel."""

from typing import Annotated, Any, Literal

from pydantic import BaseModel, ConfigDict, Field

COLORIMETRY_VERSION = "garment-colorimetry-1.0.0"

ColorLevel = Literal["low", "medium", "high", "unknown"]
ColorTemperature = Literal[
    "warm", "cool", "neutral", "warm_neutral", "cool_neutral", "unknown"
]
ColorFamily = Literal[
    "black", "white", "gray", "beige_cream", "brown", "red", "orange",
    "yellow", "green", "blue", "violet", "pink", "metallic", "multicolor",
]
ColorVariety = Literal["monochrome", "bicolor", "multicolor", "unknown"]
RGBChannel = Annotated[int, Field(ge=0, le=255)]


class ColorimetrySwatch(BaseModel):
    # Legacy palette aliases and future additive evidence survive serialization.
    model_config = ConfigDict(extra="allow", allow_inf_nan=False)

    rgb: tuple[RGBChannel, RGBChannel, RGBChannel]
    hex: str = Field(pattern=r"^#[0-9a-fA-F]{6}$")
    lab: tuple[float, float, float]
    percentage: float = Field(ge=0, le=1)
    catalog_color_id: int | str | None = None
    catalog_color_name: str | None = None
    delta_e: float | None = Field(default=None, ge=0)
    confidence_score: float = Field(ge=0, le=1)
    family: ColorFamily
    descriptive_name: str
    evidence: dict[str, Any] = Field(default_factory=dict)


class GarmentColorimetry(BaseModel):
    model_config = ConfigDict(extra="allow", allow_inf_nan=False)

    version: str = COLORIMETRY_VERSION
    status: Literal["completed", "partial", "insufficient_evidence"]
    # Geometric observations do not establish the identity of the target garment.
    commercial_color_status: Literal["insufficient_evidence"] = "insufficient_evidence"
    foreground_confidence: float = Field(ge=0, le=1)
    palette: list[ColorimetrySwatch] = Field(default_factory=list)
    primary_color: ColorimetrySwatch | None = None
    secondary_colors: list[ColorimetrySwatch] = Field(default_factory=list)
    accent_colors: list[ColorimetrySwatch] = Field(default_factory=list)
    temperature: ColorTemperature = "unknown"
    lightness: ColorLevel = "unknown"
    chroma: ColorLevel = "unknown"
    saturation: ColorLevel = "unknown"
    neutrality: ColorLevel = "unknown"
    internal_contrast: ColorLevel = "unknown"
    color_variety: ColorVariety = "unknown"
    dominant_family: ColorFamily | None = None
    warnings: list[str] = Field(default_factory=list)
    metrics: dict[str, Any] = Field(default_factory=dict)
    evidence: dict[str, Any] = Field(default_factory=dict)
    coverage: float = Field(ge=0, le=1)
