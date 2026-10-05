"""Explicit palette harmonies; no image inference, training or outfit generation."""

import colorsys
import math
from dataclasses import dataclass
from itertools import combinations

from mati_ai.compatibility.rules import RULESETS, CompatibilityRules
from mati_ai.contracts.colorimetry import COLORIMETRY_VERSION, ColorimetrySwatch, GarmentColorimetry
from mati_ai.contracts.compatibility import (
    COMPATIBILITY_RULES_VERSION,
    CompatibilityComponents,
    GarmentCompatibilityRequest,
    GarmentCompatibilityResult,
    PatternKind,
)

HARMONY_ORDER = (
    "monochromatic", "analogous", "complementary", "split_complementary",
    "triadic", "neutrals", "neutral_accent", "light_dark_contrast",
)
HARMONY_NAMES = {
    "monochromatic": "monocromática: tonos próximos en la misma región de matiz",
    "analogous": "análoga: regiones vecinas de la rueda cromática",
    "complementary": "complementaria: regiones opuestas de la rueda cromática",
    "split_complementary": "complementaria dividida: un tono y dos vecinos de su opuesto",
    "triadic": "triádica: tres regiones de matiz aproximadamente equidistantes",
}


@dataclass(frozen=True)
class Tone:
    swatch: ColorimetrySwatch
    weight: float
    role: str
    hue: float
    chroma: float
    saturation: float
    neutral: bool

    @property
    def lightness(self) -> float:
        return self.swatch.lab[0]


def _angle(left: float, right: float) -> float:
    return abs((left - right + 180) % 360 - 180)


def _key(swatch: ColorimetrySwatch) -> tuple:
    return swatch.rgb, swatch.lab


def _ryb(hue: float, rules: CompatibilityRules) -> float:
    for (x0, y0), (x1, y1) in zip(rules.hsv_to_ryb, rules.hsv_to_ryb[1:]):
        if x0 <= hue <= x1:
            return (y0 + (hue - x0) * (y1 - y0) / (x1 - x0)) % 360
    return 0.0


def _tones(profile: GarmentColorimetry, rules: CompatibilityRules) -> list[Tone]:
    total = sum(sample.percentage for sample in profile.palette)
    if total <= 0:
        return []
    primary = _key(profile.primary_color) if profile.primary_color else None
    secondary = {_key(sample) for sample in profile.secondary_colors}
    accents = {_key(sample) for sample in profile.accent_colors}
    result = []
    for sample in profile.palette:
        if sample.percentage <= 0:
            continue
        _, a, b = sample.lab
        chroma = math.hypot(a, b)
        lab_hue = math.degrees(math.atan2(b, a)) % 360
        hsv_hue, saturation, _ = colorsys.rgb_to_hsv(*(channel / 255 for channel in sample.rgb))
        neutral = chroma <= rules.neutral_chroma or (
            sample.family in ("beige_cream", "brown")
            and chroma <= rules.earth_neutral_chroma
            and rules.earth_hue_min <= lab_hue <= rules.earth_hue_max
        )
        key = _key(sample)
        role = "primary" if key == primary else "accent" if key in accents else (
            "secondary" if key in secondary else "unassigned"
        )
        result.append(Tone(
            sample, sample.percentage / total, role,
            _ryb(hsv_hue * 360, rules), chroma, saturation, neutral,
        ))
    return result


def _profile_warnings(profile: GarmentColorimetry, label: str, rules: CompatibilityRules) -> list[str]:
    warnings = [f"{label}:{warning}" for warning in profile.warnings]
    total = sum(sample.percentage for sample in profile.palette)
    if profile.version != COLORIMETRY_VERSION:
        warnings.append(f"{label}:UNSUPPORTED_COLORIMETRY_VERSION")
    if profile.status != "completed":
        warnings.append(f"{label}:COLORIMETRY_{profile.status.upper()}")
    if profile.coverage < rules.coverage_complete or total < rules.coverage_complete:
        warnings.append(f"{label}:PALETTE_COVERAGE_LOW")
    low_confidence_share = sum(
        sample.percentage for sample in profile.palette
        if sample.confidence_score < rules.swatch_confidence_min
    ) / total if total > 0 else 0.0
    if low_confidence_share >= rules.low_confidence_mass:
        warnings.append(f"{label}:LOW_SWATCH_EVIDENCE")
    if abs(profile.coverage - total) > 0.01:
        warnings.append(f"{label}:PALETTE_COVERAGE_MISMATCH")
    palette_keys = {_key(sample) for sample in profile.palette}
    references = profile.secondary_colors + profile.accent_colors
    if profile.primary_color:
        references = references + [profile.primary_color]
    if any(_key(sample) not in palette_keys for sample in references):
        warnings.append(f"{label}:COLOR_ROLE_NOT_IN_PALETTE")
    primary_keys = {_key(profile.primary_color)} if profile.primary_color else set()
    secondary_keys = {_key(sample) for sample in profile.secondary_colors}
    accent_keys = {_key(sample) for sample in profile.accent_colors}
    if primary_keys & (secondary_keys | accent_keys) or secondary_keys & accent_keys:
        warnings.append(f"{label}:COLOR_ROLES_OVERLAP")
    return list(dict.fromkeys(warnings))


def _unusable(profile: GarmentColorimetry, rules: CompatibilityRules) -> bool:
    total = sum(sample.percentage for sample in profile.palette)
    return (
        profile.version != COLORIMETRY_VERSION
        or profile.status == "insufficient_evidence"
        or not profile.palette
        or total <= 0
        or min(profile.coverage, total) < rules.coverage_min
        or profile.foreground_confidence < rules.foreground_min
    )


def _triple_matches(left: list[Tone], right: list[Tone], rules: CompatibilityRules) -> dict:
    candidates = sorted(
        [(tone, owner) for owner, tones in enumerate((left, right)) for tone in tones
         if not tone.neutral and tone.swatch.percentage >= rules.triple_min_share],
        key=lambda item: (item[0].hue, item[0].swatch.rgb, item[1]),
    )
    best = {}
    for triple in combinations(candidates, 3):
        if len({owner for _, owner in triple}) < 2:
            continue
        hues = sorted(tone.hue for tone, _ in triple)
        gaps = sorted([(hues[(i + 1) % 3] - hues[i]) % 360 for i in range(3)])
        if gaps[0] <= 2 * rules.monochromatic_degrees:
            continue
        for kind, target in (
            ("triadic", (120, 120, 120)), ("split_complementary", (60, 150, 150)),
        ):
            error = max(abs(gap - expected) for gap, expected in zip(gaps, target))
            if error > rules.triple_tolerance:
                continue
            indices = [
                [index for index, tone in enumerate(tones) if not tone.neutral
                 and any(_angle(tone.hue, hue) <= rules.monochromatic_degrees for hue in hues)]
                for tones in (left, right)
            ]
            shares = [sum(tones[i].weight for i in selected)
                      for tones, selected in zip((left, right), indices)]
            support = sum(shares) / 2
            if min(shares) < rules.triple_min_garment_support or support < rules.triple_min_combined_support:
                continue
            quality = (support, -error, tuple(-hue for hue in hues))
            if kind not in best or quality > best[kind]["quality"]:
                best[kind] = {
                    "quality": quality, "hues": hues, "support": support,
                    "garment_support": shares, "indices": indices, "max_gap_error": error,
                }
    return best


def _pair(left: Tone, right: Tone, rules: CompatibilityRules) -> tuple[str | None, float]:
    if left.neutral and right.neutral:
        return "neutrals", rules.neutrals_score
    if left.neutral or right.neutral:
        return "neutral_accent", rules.neutral_accent_score
    angle = _angle(left.hue, right.hue)
    if angle <= rules.monochromatic_degrees:
        return "monochromatic", rules.monochromatic_score
    if angle <= rules.analogous_degrees:
        return "analogous", rules.analogous_score
    if abs(angle - 180) <= rules.complementary_tolerance:
        return "complementary", rules.complementary_score
    return None, rules.unmatched_hue_score


def _intense(tones: list[Tone], profile: GarmentColorimetry, rules: CompatibilityRules) -> bool:
    mass = sum(tone.weight for tone in tones
               if tone.chroma >= rules.high_chroma or tone.saturation >= rules.high_saturation)
    return mass >= rules.intense_mass or profile.chroma == "high" or profile.saturation == "high"


def _temperature(left: GarmentColorimetry, right: GarmentColorimetry,
                 harmonies: list[str], rules: CompatibilityRules) -> tuple[float | None, str]:
    first, second = left.temperature, right.temperature
    if "unknown" in (first, second):
        return None, "La temperatura no tiene una interpretación común fiable; su componente queda sin puntuar."
    if first == "neutral" or second == "neutral":
        return rules.temperature_neutral_score, "Una temperatura neutra facilita el diálogo entre las paletas."
    warm = {"warm", "warm_neutral"}
    cool = {"cool", "cool_neutral"}
    if (first in warm and second in warm) or (first in cool and second in cool):
        return rules.temperature_same_score, "Las paletas comparten una orientación de temperatura."
    if any(kind in harmonies for kind in ("complementary", "split_complementary", "triadic", "neutral_accent")):
        return rules.temperature_structured_score, (
            "El contraste cálido/frío cuenta con una estructura cromática reconocida y puede aportar equilibrio."
        )
    return rules.temperature_opposed_score, (
        "Hay contraste cálido/frío; esta diferencia aporta tensión visual sin descartar la combinación."
    )


def _tone_evidence(tones: list[Tone]) -> list[dict]:
    return [{
        "hex": tone.swatch.hex, "family": tone.swatch.family, "role": tone.role,
        "original_share": tone.swatch.percentage, "retained_palette_weight": round(tone.weight, 6),
        "ryb_hue": None if tone.neutral else round(tone.hue, 3),
        "lab_lightness": tone.lightness, "lab_chroma": round(tone.chroma, 3),
        "hsv_saturation": round(tone.saturation, 4), "neutral": tone.neutral,
    } for tone in tones]


def evaluate_compatibility(
    garment_a: GarmentColorimetry,
    garment_b: GarmentColorimetry,
    *,
    rules_version: str = COMPATIBILITY_RULES_VERSION,
    pattern_a: PatternKind = "unknown",
    pattern_b: PatternKind = "unknown",
) -> GarmentCompatibilityResult:
    """Symmetric score on two observed palettes; roles never add palette mass."""
    # Enforce the same bounds for HTTP and direct Python callers.
    request = GarmentCompatibilityRequest(
        garment_a=garment_a, garment_b=garment_b, rules_version=rules_version,
        pattern_a=pattern_a, pattern_b=pattern_b,
    )
    rules = RULESETS[request.rules_version]
    profiles = (request.garment_a, request.garment_b)
    warnings = [warning for profile, label in zip(profiles, ("A", "B"))
                for warning in _profile_warnings(profile, label, rules)]
    evidence = {
        "rules": rules.metadata(),
        "input_versions": [profile.version for profile in profiles],
        "input_statuses": [profile.status for profile in profiles],
        "input_coverage": [profile.coverage for profile in profiles],
        "input_properties": [{
            "temperature": p.temperature, "lightness": p.lightness, "chroma": p.chroma,
            "saturation": p.saturation, "neutrality": p.neutrality,
            "internal_contrast": p.internal_contrast, "color_variety": p.color_variety,
        } for p in profiles],
        "scope": "two_observed_garment_palettes",
        "hue_space": "versioned_approximate_artistic_RYB_from_sRGB_HSV",
        "lightness_chroma_space": "CIELAB_D65",
        "garment_weights": [0.5, 0.5],
        "weight_basis": "conditional_on_retained_palette_not_physical_outfit_area",
        "pattern_hints": [pattern_a, pattern_b],
        "pattern_basis": "caller_supplied_existing_pattern_result_or_unknown",
    }
    if any(_unusable(profile, rules) for profile in profiles):
        warnings.append("COMPATIBILITY_INSUFFICIENT_EVIDENCE")
        return GarmentCompatibilityResult(
            status="insufficient_evidence", warnings=list(dict.fromkeys(warnings)),
            explanations=[
                "No hay evidencia cromática utilizable en ambas prendas bajo el contrato admitido.",
                "La ausencia de puntuación no significa incompatibilidad cromática.",
            ], evidence=evidence,
        )

    left, right = (_tones(profile, rules) for profile in profiles)
    triples = _triple_matches(left, right, rules)
    support = dict.fromkeys(HARMONY_ORDER, 0.0)
    hue_score = lightness_score = unmatched_mass = mean_lightness_delta = 0.0
    pair_evidence = []
    for i, a in enumerate(left):
        for j, b in enumerate(right):
            weight = a.weight * b.weight
            kind, score = _pair(a, b, rules)
            if kind is not None:
                support[kind] += weight
            supporting_triples = []
            for triple_kind, match in triples.items():
                if i in match["indices"][0] and j in match["indices"][1]:
                    triple_score = getattr(rules, triple_kind + "_score")
                    score = max(score, triple_score)
                    supporting_triples.append(triple_kind)
            if kind is None and not supporting_triples:
                unmatched_mass += weight
            delta_l = abs(a.lightness - b.lightness)
            if delta_l >= rules.light_dark_delta:
                support["light_dark_contrast"] += weight
            light_score = rules.lightness_base + rules.lightness_range_bonus * min(
                1, delta_l / rules.lightness_range_scale
            )
            if kind == "monochromatic":
                light_score = max(light_score, rules.monochromatic_lightness_score)
            hue_score += weight * score
            lightness_score += weight * light_score
            mean_lightness_delta += weight * delta_l
            pair_evidence.append({
                "a": i, "b": j, "weight": round(weight, 6),
                "hue_difference_ryb": None if a.neutral or b.neutral else round(_angle(a.hue, b.hue), 3),
                "lightness_difference": round(delta_l, 3), "pair_rule": kind,
                "triple_rules": supporting_triples, "hue_score": round(score * 100, 3),
            })

    neutral_shares = [sum(tone.weight for tone in tones if tone.neutral) for tones in (left, right)]
    harmonies = [kind for kind in ("monochromatic", "analogous", "complementary")
                 if support[kind] >= rules.pair_min_support]
    for kind, match in triples.items():
        support[kind] = match["support"]
        harmonies.append(kind)
    if min(neutral_shares) >= rules.neutral_garment_share:
        harmonies.append("neutrals")
    if any(neutral_shares[i] >= rules.neutral_garment_share
           and 1 - neutral_shares[1 - i] >= rules.accent_garment_share for i in (0, 1)):
        harmonies.append("neutral_accent")
    if support["light_dark_contrast"] >= rules.light_dark_support:
        harmonies.append("light_dark_contrast")
    harmonies = [kind for kind in HARMONY_ORDER if kind in harmonies]

    explanations = [
        f"Se reconoce una relación {HARMONY_NAMES[kind]}; su soporte es "
        f"{support[kind]:.2f} de la masa de paleta evaluada."
        for kind in harmonies if kind in HARMONY_NAMES
    ]
    if "neutrals" in harmonies:
        explanations.append(
            "Ambas paletas tienen una base neutra importante; los tonos tierra se aceptan "
            "como neutros solo cuando su croma y matiz respaldan esa función."
        )
    if "neutral_accent" in harmonies:
        explanations.append(
            "Una base neutra permite que el color de la otra prenda actúe como acento entre prendas. "
            "Ese acento puede ser el color principal dentro de su propia prenda."
        )
        for tones in (left, right):
            for family, message in (
                ("white", "El blanco de bajo croma aporta una base versátil para muchos colores."),
                ("black", "El negro de bajo croma sostiene el acento y puede destacar su luminosidad."),
            ):
                if sum(t.weight for t in tones if t.neutral and t.swatch.family == family) >= rules.neutral_garment_share:
                    explanations.append(message)
    if "light_dark_contrast" in harmonies:
        explanations.append(
            "Una proporción relevante de las paletas separa claros y oscuros al menos "
            f"{rules.light_dark_delta:g} unidades L*, aportando diferenciación visual."
        )
    if not harmonies:
        explanations.append(
            "Estas reglas no identifican una armonía con soporte suficiente; "
            "la combinación puede responder a otras preferencias estéticas."
        )
    explanations.append(
        f"La diferencia de luminosidad media entre parejas de tonos es {mean_lightness_delta:.1f} "
        "unidades L*. Se considera tanto la continuidad tonal como la separación de claros y oscuros."
    )
    if any(profile.primary_color is None for profile in profiles):
        explanations.append(
            "La falta de un color principal único no invalida las paletas: secundarios y acentos "
            "participan según su proporción original, sin duplicar su peso."
        )
    temperature_score, message = _temperature(*profiles, harmonies, rules)
    explanations.append(message)
    if temperature_score is None:
        warnings.append("TEMPERATURE_COMPONENT_UNAVAILABLE")

    intense = [_intense(tones, profile, rules) for tones, profile in zip((left, right), profiles)]
    complex_palettes = [
        profile.color_variety == "multicolor" and profile.internal_contrast == "high"
        for profile in profiles
    ]
    adjustments = {}
    if all(intense):
        adjustments["both_saturated"] = rules.both_saturated_adjustment
        warnings.append("BOTH_PALETTES_INTENSE")
        explanations.append(
            "Las dos prendas concentran croma o saturación altos; se aplica un ajuste moderado "
            "por competencia visual, aunque una combinación intensa puede ser intencional."
        )
    if all(complex_palettes):
        adjustments["both_complex"] = rules.both_complex_adjustment
        warnings.append("BOTH_PALETTES_COMPLEX")
        explanations.append(
            "Ambas paletas son multicolor y contrastadas; conviene considerar la proporción "
            "de sus tonos y las relaciones que comparten."
        )
    printed_patterns = {"printed", "striped", "checkered"}
    pattern_intensity = [value or profile.internal_contrast == "high"
                         for value, profile in zip(intense, profiles)]
    if pattern_a in printed_patterns and pattern_b in printed_patterns and all(pattern_intensity):
        adjustments["both_intense_patterns"] = rules.both_intense_patterns_adjustment
        warnings.append("BOTH_PATTERNS_INTENSE")
        explanations.append(
            "Los patrones informados y sus paletas intensas pueden competir visualmente. "
            "La escala y distribución del estampado no se evalúan aquí."
        )
    if any(profile.color_variety == "multicolor" for profile in profiles):
        warnings.append("MULTICOLOR_PROPORTIONS_MATTER")
        explanations.append(
            "La paleta multicolor se evalúa completa y ponderada por sus proporciones; "
            "ningún tono pequeño representa por sí solo a toda la prenda."
        )
    if unmatched_mass >= rules.pair_min_support:
        explanations.append(
            f"Una masa de parejas cromáticas de {unmatched_mass:.2f} queda fuera de las geometrías "
            "reconocidas; se conserva como señal de tensión, sin un veto estético."
        )
    adjustment = min(rules.adjustment_cap, sum(adjustments.values()))
    contrast_score = max(0, rules.contrast_base - rules.contrast_unmatched_adjustment * unmatched_mass - adjustment)
    components = {
        "temperature": temperature_score, "lightness": lightness_score,
        "hue": hue_score, "contrast": contrast_score,
    }
    weights = dict(rules.component_weights)
    active_weight = sum(weights[name] for name, value in components.items() if value is not None)
    score = sum(weights[name] * value for name, value in components.items() if value is not None) / active_weight
    partial = any(
        profile.status != "completed"
        or min(profile.coverage, sum(t.percentage for t in profile.palette)) < rules.coverage_complete
        for profile in profiles
    ) or any("PALETTE_COVERAGE_MISMATCH" in w or "COLOR_ROLE" in w
             or "LOW_SWATCH_EVIDENCE" in w for w in warnings)
    if partial:
        warnings.append("COMPATIBILITY_PROVISIONAL_INPUT")
        explanations.append(
            "La evidencia de entrada es parcial; las relaciones son provisionales. "
            "Esa incertidumbre no se convierte automáticamente en menor compatibilidad."
        )
    evidence.update({
        "tones_a": _tone_evidence(left), "tones_b": _tone_evidence(right),
        "neutral_shares": [round(share, 6) for share in neutral_shares],
        "role_shares": [{role: round(sum(t.weight for t in tones if t.role == role), 6)
                        for role in ("primary", "secondary", "accent", "unassigned")}
                       for tones in (left, right)],
        "mean_cross_palette_lightness_delta": round(mean_lightness_delta, 3),
        "rule_support": {kind: round(value, 6) for kind, value in support.items()},
        "triple_matches": {kind: {
            "hues_ryb": [round(hue, 3) for hue in match["hues"]],
            "combined_support": round(match["support"], 6),
            "garment_support": [round(value, 6) for value in match["garment_support"]],
            "max_gap_error": round(match["max_gap_error"], 3),
        } for kind, match in triples.items()},
        "cross_palette_pairs": pair_evidence, "unmatched_pair_mass": round(unmatched_mass, 6),
        "contrast_adjustments": adjustments, "applied_contrast_adjustment": adjustment,
        "active_component_weights": {
            name: round(weights[name] / active_weight, 6)
            for name, value in components.items() if value is not None
        },
        "formula": "weighted_component_mean * 100; missing components excluded",
    })
    return GarmentCompatibilityResult(
        status="partial" if partial else "completed",
        compatibility_score=round(min(1, max(0, score)) * 100, 2),
        harmony_types=harmonies,
        components=CompatibilityComponents(**{
            name: round(value * 100, 2) if value is not None else None
            for name, value in components.items()
        }),
        explanations=list(dict.fromkeys(explanations)),
        warnings=list(dict.fromkeys(warnings)), evidence=evidence,
    )
