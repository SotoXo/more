"""Pairwise ranking contract over existing garment colorimetry, without images."""

from typing import Any, Literal

from pydantic import BaseModel, ConfigDict, Field, field_validator

from mati_ai.contracts.colorimetry import GarmentColorimetry

COMPATIBILITY_RULES_VERSION = "garment-compatibility-1.0.0"
HarmonyType = Literal[
    "monochromatic", "analogous", "complementary", "split_complementary",
    "triadic", "neutrals", "neutral_accent", "light_dark_contrast",
]
PatternKind = Literal["solid", "printed", "striped", "checkered", "unknown"]


class GarmentCompatibilityRequest(BaseModel):
    model_config = ConfigDict(extra="forbid", allow_inf_nan=False)

    request_id: str | None = Field(default=None, max_length=100, pattern=r"^[A-Za-z0-9._:-]{1,100}$")
    garment_a: GarmentColorimetry
    garment_b: GarmentColorimetry
    rules_version: Literal["garment-compatibility-1.0.0"] = COMPATIBILITY_RULES_VERSION
    pattern_a: PatternKind = "unknown"
    pattern_b: PatternKind = "unknown"

    @field_validator("garment_a", "garment_b")
    @classmethod
    def bounded_colorimetry(cls, value: GarmentColorimetry) -> GarmentColorimetry:
        # The upstream extractor retains at most eight swatches. Bound geometry
        # work without changing that shared, already deployed contract.
        for collection in (value.palette, value.secondary_colors, value.accent_colors):
            if len(collection) > 8:
                raise ValueError("La colorimetría admite hasta ocho muestras por lista.")
        samples = value.palette + value.secondary_colors + value.accent_colors
        if value.primary_color is not None:
            samples = samples + [value.primary_color]
        if any(not 0 <= sample.lab[0] <= 100 for sample in samples):
            raise ValueError("La luminosidad Lab física debe estar entre 0 y 100.")
        if any(abs(axis) > 160 for sample in samples for axis in sample.lab[1:]):
            raise ValueError("Los ejes Lab deben estar en el rango físico admitido.")
        if sum(sample.percentage for sample in value.palette) > 1.00001:
            raise ValueError("Las proporciones de la paleta no pueden sumar más de uno.")
        keys = [(sample.rgb, sample.lab) for sample in value.palette]
        if len(set(keys)) != len(keys):
            raise ValueError("La paleta no debe duplicar una misma muestra.")
        return value


class CompatibilityComponents(BaseModel):
    model_config = ConfigDict(extra="forbid", allow_inf_nan=False)

    temperature: float | None = Field(default=None, ge=0, le=100)
    lightness: float | None = Field(default=None, ge=0, le=100)
    hue: float | None = Field(default=None, ge=0, le=100)
    contrast: float | None = Field(default=None, ge=0, le=100)


class GarmentCompatibilityResult(BaseModel):
    model_config = ConfigDict(extra="forbid", allow_inf_nan=False)

    version: str = COMPATIBILITY_RULES_VERSION
    rules_version: str = COMPATIBILITY_RULES_VERSION
    status: Literal["completed", "partial", "insufficient_evidence"]
    score_kind: Literal["heuristic_ranking_signal"] = "heuristic_ranking_signal"
    compatibility_score: float | None = Field(default=None, ge=0, le=100)
    harmony_types: list[HarmonyType] = Field(default_factory=list)
    components: CompatibilityComponents = Field(default_factory=CompatibilityComponents)
    explanations: list[str] = Field(default_factory=list)
    warnings: list[str] = Field(default_factory=list)
    evidence: dict[str, Any] = Field(default_factory=dict)
