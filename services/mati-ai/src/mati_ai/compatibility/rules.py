"""Immutable v1 policy. Any change to geometry, weights or scores needs a version."""

from dataclasses import asdict, dataclass

from mati_ai.contracts.compatibility import COMPATIBILITY_RULES_VERSION


@dataclass(frozen=True)
class CompatibilityRules:
    version: str = COMPATIBILITY_RULES_VERSION
    # Artistic hue approximation, explicitly distinct from the CIELAB hue.
    hsv_to_ryb: tuple[tuple[float, float], ...] = (
        (0, 0), (30, 60), (60, 120), (120, 180),
        (180, 210), (240, 240), (300, 300), (360, 360),
    )
    monochromatic_degrees: float = 18
    analogous_degrees: float = 55
    complementary_tolerance: float = 25
    triple_tolerance: float = 22
    triple_min_share: float = 0.06
    triple_min_garment_support: float = 0.18
    triple_min_combined_support: float = 0.45
    pair_min_support: float = 0.20
    neutral_chroma: float = 12
    earth_neutral_chroma: float = 30
    earth_hue_min: float = 35
    earth_hue_max: float = 100
    neutral_garment_share: float = 0.70
    accent_garment_share: float = 0.12
    light_dark_delta: float = 30
    light_dark_support: float = 0.35
    foreground_min: float = 0.70
    coverage_min: float = 0.50
    coverage_complete: float = 0.85
    swatch_confidence_min: float = 0.45
    low_confidence_mass: float = 0.25
    high_chroma: float = 50
    high_saturation: float = 0.60
    intense_mass: float = 0.60
    monochromatic_score: float = 0.90
    analogous_score: float = 0.84
    complementary_score: float = 0.90
    split_complementary_score: float = 0.87
    triadic_score: float = 0.87
    neutrals_score: float = 0.92
    neutral_accent_score: float = 0.90
    unmatched_hue_score: float = 0.55
    lightness_base: float = 0.70
    lightness_range_bonus: float = 0.20
    lightness_range_scale: float = 40
    monochromatic_lightness_score: float = 0.85
    temperature_same_score: float = 0.82
    temperature_neutral_score: float = 0.88
    temperature_opposed_score: float = 0.70
    temperature_structured_score: float = 0.86
    contrast_base: float = 0.85
    contrast_unmatched_adjustment: float = 0.12
    both_saturated_adjustment: float = 0.08
    both_complex_adjustment: float = 0.10
    both_intense_patterns_adjustment: float = 0.08
    adjustment_cap: float = 0.20
    component_weights: tuple[tuple[str, float], ...] = (
        ("temperature", 0.10), ("lightness", 0.20), ("hue", 0.55), ("contrast", 0.15),
    )

    def metadata(self) -> dict:
        return asdict(self)


RULES_V1 = CompatibilityRules()
RULESETS = {COMPATIBILITY_RULES_VERSION: RULES_V1}
