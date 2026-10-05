"""Garment-only color observations; Laravel remains the catalog authority.

The original image is never changed. Segmentation, palette and confidence are
deterministic heuristics, not calibrated probabilities or material recognition.
"""

import re
import unicodedata
from typing import Any

import cv2
import numpy as np
from PIL import Image

from mati_ai.contracts.colorimetry import COLORIMETRY_VERSION
from mati_ai.contracts.vision import CatalogValue
from mati_ai.vision.color_distance import delta_e_2000
from mati_ai.vision.segmentation import MIN_FOREGROUND_CONFIDENCE, foreground_mask

CATALOG_MAX_DELTA_E = 10.0
CATALOG_MIN_MARGIN = 2.0
CATALOG_MIN_CONFIDENCE = 0.55
MAX_PALETTE_COLORS = 8
MIN_PALETTE_SHARE = 0.015
SIGNIFICANT_COLOR_SHARE = 0.12

# Only approximate references for legacy catalog rows without an exact HEX.
# They never authorize a commercial ID. Runtime references come from Laravel.
FALLBACK_HEX = {
    "NEGRO": "111111", "BLANCO": "f7f6f2", "BEIGE": "d8c3a5",
    "CAMEL": "b88956", "AZUL": "2447d8", "AZUL MARINO": "1f2a44",
    "ROJO": "e53935", "VERDE": "009b77", "VERDE OLIVA": "66704a",
    "AMARILLO": "f5dc1e", "GRIS": "8a8f98", "ROSA": "f48fb1",
    "ROSA PALO": "d8a7b1", "MORADO": "6a1b9a", "MARRON": "6f4e37",
    "CAFE": "6f4e37", "NARANJA": "f57c00", "CELESTE": "a9c7e8",
    "CREMA": "f2e8d8", "BORGONA": "6f1d2c",
}
FAMILY_ALIASES = {
    "NEGRO": "black", "BLANCO": "white", "GRIS": "gray",
    "BEIGE": "beige_cream", "CREMA": "beige_cream", "CAMEL": "brown",
    "MARRON": "brown", "CAFE": "brown", "ROJO": "red", "NARANJA": "orange",
    "AMARILLO": "yellow", "VERDE": "green", "AZUL": "blue",
    "MORADO": "violet", "VIOLETA": "violet", "ROSA": "pink",
    "ROSADO": "pink", "DORADO": "metallic", "PLATEADO": "metallic",
    "METALIZADO": "metallic", "MULTICOLOR": "multicolor",
}
FAMILY_NAMES = {
    "black": "negro", "white": "blanco", "gray": "gris",
    "beige_cream": "beige / crema", "brown": "marrón", "red": "rojo",
    "orange": "naranja", "yellow": "amarillo", "green": "verde",
    "blue": "azul", "violet": "violeta", "pink": "rosado",
    "metallic": "metalizado", "multicolor": "multicolor",
}


def _name(value: str) -> str:
    normalized = "".join(
        character for character in unicodedata.normalize("NFKD", value.upper())
        if not unicodedata.combining(character)
    )
    return " ".join(normalized.split())


def _rgb(value: CatalogValue) -> list[int] | None:
    color = value.hex or FALLBACK_HEX.get(_name(value.name))
    if not color or not re.fullmatch(r"#?[0-9a-fA-F]{6}", color):
        return None
    return [int(color.lstrip("#")[index:index + 2], 16) for index in (0, 2, 4)]


def _family(lab: np.ndarray, catalog_color: CatalogValue | None = None) -> str:
    lightness, a, b = (float(value) for value in lab)
    chroma = float(np.hypot(a, b))
    hue = float(np.degrees(np.arctan2(b, a)) % 360)
    if catalog_color is not None:
        name = _name(catalog_color.name)
        commercial_family = FAMILY_ALIASES.get(_name(catalog_color.family or ""))
        # CREMA/MARFIL remain perceptual cream even if the commercial family is BLANCO.
        if any(word in name for word in ("CREMA", "MARFIL", "HUESO")) and b > 4:
            return "beige_cream"
        if commercial_family and commercial_family not in ("metallic", "multicolor"):
            return commercial_family
    if lightness < 23 and chroma < 16:
        return "black"
    if lightness >= 88 and chroma <= 9:
        return "white"
    if chroma <= 8:
        return "gray"
    if lightness >= 62 and chroma <= 32 and 35 <= hue <= 105:
        return "beige_cream"
    if lightness < 62 and chroma <= 52 and 30 <= hue <= 95:
        return "brown"
    if a > 10 and ((lightness >= 55 and (hue >= 330 or hue < 25)) or (hue >= 325 and b < 0)):
        return "pink"
    if hue < 35 or hue >= 345:
        return "red"
    if hue < 75:
        return "orange"
    if hue < 110:
        return "yellow"
    if hue < 195:
        return "green"
    if hue < 295:
        return "blue"
    return "violet"


def _temperature(lab: np.ndarray) -> str:
    _, a, b = (float(value) for value in lab)
    chroma = float(np.hypot(a, b))
    if chroma < 5:
        return "neutral"
    if chroma < 30:
        if b > 5 and a > -8:
            return "warm_neutral"
        if b < -5:
            return "cool_neutral"
        return "neutral"
    hue = float(np.degrees(np.arctan2(b, a)) % 360)
    return "warm" if hue <= 125 or hue >= 345 else "cool"


def _level(value: float, low: float, high: float) -> str:
    return "low" if value < low else "high" if value >= high else "medium"


def _description(center: np.ndarray, family: str, reliable: bool) -> str:
    if not reliable:
        return FAMILY_NAMES[family] + " observado; iluminación por revisar"
    lightness = float(center[0])
    chroma = float(np.hypot(center[1], center[2]))
    hue = float(np.degrees(np.arctan2(center[2], center[1])) % 360)
    if family == "blue" and lightness < 35:
        return "azul marino"
    if family == "red" and lightness < 45 and (hue < 35 or hue > 335):
        return "rojo vino"
    if family == "green" and 95 <= hue <= 145 and lightness < 65 and chroma < 45:
        return "verde oliva"
    if family == "pink" and chroma < 35 and lightness >= 55:
        return "rosa palo"
    if family == "beige_cream":
        return "crema" if lightness >= 86 and chroma < 20 else "beige cálido" if center[2] > 5 else "beige"
    name = FAMILY_NAMES[family]
    if family not in ("black", "white"):
        if lightness >= 72:
            name += " claro"
        elif lightness < 38:
            name += " oscuro"
        else:
            name += " medio"
    if family not in ("black", "white", "gray", "brown"):
        if chroma < 25:
            name += " apagado"
        elif chroma >= 50:
            name += " intenso"
    return name


def _illumination(rgb: np.ndarray, mask: np.ndarray, segmentation: dict) -> tuple[dict, list[str]]:
    pixels = rgb[mask]
    # Without a valid garment mask, exposure belongs to the whole image only.
    if not len(pixels):
        return {
            "reliable": False, "confidence_score": 0.0,
            "white_balance_reference": "unavailable", "white_balance_corrected": False,
            "shadow_clipped_fraction": None, "highlight_clipped_fraction": None,
            "channel_clipped_fraction": None, "background_lab": segmentation.get("background_lab"),
            "reference_chroma": None,
        }, []
    shadow_fraction = float(np.mean(np.all(pixels <= 4, axis=1)))
    highlight_fraction = float(np.mean(np.all(pixels >= 250, axis=1)))
    channel_fraction = float(np.mean(np.any(pixels >= 254, axis=1) | np.all(pixels <= 1, axis=1)))
    warnings = []
    if shadow_fraction >= 0.40:
        warnings.append("COLOR_UNDEREXPOSURE_SUSPECTED")
    if highlight_fraction >= 0.40:
        warnings.append("COLOR_OVEREXPOSURE_SUSPECTED")
    if channel_fraction >= 0.35:
        warnings.append("COLOR_CHANNEL_CLIPPING")
    background = np.array(segmentation["background_lab"], dtype=float)
    reference_chroma = float(np.hypot(background[1], background[2]))
    # A light, near-neutral uniform border is only a possible reference. We
    # cannot prove it was physically white/gray, so cast is reported as suspicion.
    possible_reference = background[0] >= 70 and reference_chroma <= 22 and abs(background[1]) <= 10
    if possible_reference and background[2] > 9:
        warnings.extend(["WARM_LIGHTING_SUSPECTED", "WHITE_BALANCE_SUSPECTED"])
    elif possible_reference and background[2] < -9:
        warnings.extend(["COOL_LIGHTING_SUSPECTED", "WHITE_BALANCE_SUSPECTED"])
    reliable = not warnings
    evidence = {
        "reliable": reliable,
        "confidence_score": 0.90 if reliable else 0.45,
        "white_balance_reference": "unverified_uniform_border" if possible_reference else "unavailable",
        "white_balance_corrected": False,
        "background_lab": [round(float(value), 3) for value in background],
        "reference_chroma": round(reference_chroma, 3),
        "shadow_clipped_fraction": round(shadow_fraction, 4),
        "highlight_clipped_fraction": round(highlight_fraction, 4),
        "channel_clipped_fraction": round(channel_fraction, 4),
        "exposure_interpretation": "clipping_can_also_be_intrinsic_black_white_or_intense_fabric",
    }
    return evidence, list(dict.fromkeys(warnings))


def _clusters(pixels: np.ndarray) -> list[dict]:
    # Weighted histogram modes initialize perceptual clusters deterministically.
    bins = np.floor(pixels / np.array([6, 8, 8])).astype(np.int16)
    _, inverse, counts = np.unique(bins, axis=0, return_inverse=True, return_counts=True)
    sums = np.zeros((len(counts), 3), dtype=np.float64)
    np.add.at(sums, inverse, pixels)
    nodes = sums / counts[:, None]
    seeds = []
    for index in np.argsort(-counts, kind="stable"):
        if not seeds or float(np.min(delta_e_2000(nodes[index], np.asarray(seeds)))) >= 12:
            seeds.append(nodes[index])
        if len(seeds) == MAX_PALETTE_COLORS:
            break
    centers = np.asarray(seeds)
    # Reassign all bins, not just the most frequent ones. No RGB distance or
    # random K-means seeds are used; Lab centroid updates approximate each group.
    for _ in range(5):
        assignments = np.argmin(delta_e_2000(nodes[:, None, :], centers[None, :, :]), axis=1)
        updated = []
        for index in range(len(centers)):
            selected = assignments == index
            if selected.any():
                updated.append(np.average(nodes[selected], axis=0, weights=counts[selected]))
        centers = np.asarray(updated)
    assignments = np.argmin(delta_e_2000(nodes[:, None, :], centers[None, :, :]), axis=1)
    pixel_assignments = assignments[inverse]
    groups = []
    for index, center in enumerate(centers):
        group = pixels[pixel_assignments == index]
        if not len(group):
            continue
        percentage = len(group) / len(pixels)
        if percentage < MIN_PALETTE_SHARE:
            continue
        center = group.mean(axis=0)
        rgb = cv2.cvtColor(center.reshape(1, 1, 3).astype(np.float32), cv2.COLOR_LAB2RGB)[0, 0]
        groups.append({
            "center": center,
            "rgb": np.clip(np.rint(rgb * 255), 0, 255).astype(int).tolist(),
            "percentage": percentage,
            "pixel_count": len(group),
            "spread": float(np.percentile(delta_e_2000(group, center), 90)),
        })
    return sorted(groups, key=lambda group: (-group["pixel_count"], *group["rgb"]))


def _references(catalog: list[CatalogValue]) -> list[tuple[CatalogValue, np.ndarray, str]]:
    references = []
    for value in catalog:
        color = _rgb(value)
        if color is None:
            continue
        lab = cv2.cvtColor(np.array(color, np.float32).reshape(1, 1, 3) / 255, cv2.COLOR_RGB2LAB)[0, 0]
        references.append((value, lab, "catalog_hex" if value.hex else "name_fallback"))
    return references


def _palette_element(group: dict, references: list, foreground: float, illumination: dict) -> tuple[dict[str, Any], list[str]]:
    center = group["center"]
    compactness = max(0.35, 1 - group["spread"] / 30)
    confidence = float(foreground * illumination["confidence_score"] * compactness)
    candidate, distance, margin, reference_source = None, None, None, None
    warnings = []
    accepted = False
    reason = "no_catalog_reference"
    if references:
        ranked = sorted(
            [(entry, float(delta_e_2000(center, lab)), source) for entry, lab, source in references],
            key=lambda value: value[1],
        )
        candidate, distance, reference_source = ranked[0]
        margin = ranked[1][1] - distance if len(ranked) > 1 else None
        metallic = _name(candidate.family or "") in ("DORADO", "PLATEADO", "METALIZADO") or _name(candidate.name) in ("DORADO", "PLATEADO")
        if not illumination["reliable"]:
            reason = "illumination_uncertain"
        elif reference_source != "catalog_hex":
            reason = "approximate_reference"
            warnings.append("CATALOG_REFERENCE_APPROXIMATE")
        elif metallic:
            reason = "metallic_finish_not_observable_from_swatch"
            warnings.append("METALLIC_FINISH_NOT_VERIFIABLE")
        elif distance > CATALOG_MAX_DELTA_E:
            reason = "distance_exceeds_threshold"
            warnings.append("CATALOG_COLOR_UNMATCHED")
        elif (margin is None or margin < CATALOG_MIN_MARGIN) and distance > 2:
            reason = "nearest_catalog_references_ambiguous"
            warnings.append("CATALOG_MATCH_AMBIGUOUS")
        elif confidence < CATALOG_MIN_CONFIDENCE or group["spread"] > 14:
            reason = "cluster_color_evidence_insufficient"
            warnings.append("CATALOG_COLOR_UNMATCHED")
        else:
            reason, accepted = "exact_reference_within_thresholds", True
    else:
        warnings.append("CATALOG_COLOR_UNMATCHED")
    family = _family(center, candidate if accepted else None)
    color = group["rgb"]
    catalog_id = candidate.id if accepted and candidate is not None else None
    catalog_name = candidate.name if accepted and candidate is not None else None
    value = {
        "hex": "#" + "".join(f"{component:02x}" for component in color),
        "rgb": color,
        "lab": [round(float(component), 3) for component in center],
        "percentage": round(group["percentage"], 6),
        "family": family,
        "descriptive_name": _description(center, family, illumination["reliable"]),
        "catalog_color_id": catalog_id,
        "catalog_color_name": catalog_name,
        "delta_e": round(distance, 3) if distance is not None else None,
        "confidence_score": round(confidence, 3),
        "source": "lab_quantization",
        "evidence": {
            "method": "weighted_lab_histogram_ciede2000",
            "confidence_kind": "heuristic_uncalibrated",
            "pixel_count": group["pixel_count"],
            "cluster_delta_e_p90": round(group["spread"], 3),
            "foreground_confidence": foreground,
            "illumination_reliable": illumination["reliable"],
            "catalog_match_accepted": accepted,
            "catalog_match_reason": reason,
            "catalog_reference_source": reference_source,
            "catalog_match_margin_delta_e": round(margin, 3) if margin is not None else None,
            "catalog_match_confidence_score": round(confidence * max(0, 1 - (distance or 0) / 15), 3) if accepted else 0.0,
            "nearest_catalog_color_name": candidate.name if candidate is not None else None,
            "delta_e_formula": "CIEDE2000",
            "catalog_max_delta_e": CATALOG_MAX_DELTA_E,
            "catalog_min_margin_delta_e": CATALOG_MIN_MARGIN,
            "catalog_min_confidence": CATALOG_MIN_CONFIDENCE,
        },
        # Compatibility aliases; values always match the additive contract.
        "catalog_id": catalog_id,
        "catalog_name": catalog_name,
        "distance_delta_e": round(distance, 3) if distance is not None else None,
    }
    return value, warnings


def _distinct_color(left: dict, right: dict) -> bool:
    first, second = np.array(left["lab"]), np.array(right["lab"])
    if float(delta_e_2000(first, second)) < 15:
        return False
    c1, c2 = float(np.hypot(*first[1:])), float(np.hypot(*second[1:]))
    if max(c1, c2) < 14:
        return abs(first[0] - second[0]) >= 35
    if min(c1, c2) < 10:
        return max(c1, c2) >= 25 or abs(first[0] - second[0]) >= 40
    hue1 = float(np.degrees(np.arctan2(first[2], first[1])) % 360)
    hue2 = float(np.degrees(np.arctan2(second[2], second[1])) % 360)
    hue_difference = abs((hue1 - hue2 + 180) % 360 - 180)
    # Avoid interpreting shadows of the same textile as additional colors.
    return hue_difference >= 30 or (abs(c1 - c2) >= 24 and abs(first[0] - second[0]) <= 12)


def _properties(pixels: np.ndarray, rgb: np.ndarray, palette: list[dict], reliable: bool) -> dict:
    lightness = float(np.mean(pixels[:, 0]))
    chroma_values = np.hypot(pixels[:, 1], pixels[:, 2])
    chroma = float(np.mean(chroma_values))
    saturation = float(np.mean(cv2.cvtColor(rgb.reshape(-1, 1, 3).astype(np.float32) / 255, cv2.COLOR_RGB2HSV)[:, 0, 1]))
    hues = np.mod(np.degrees(np.arctan2(pixels[:, 2], pixels[:, 1])), 360)
    neutral_pixels = (chroma_values <= 12) | ((chroma_values <= 30) & (hues >= 35) & (hues <= 100))
    neutrality = float(np.mean(neutral_pixels))
    dominant_groups = []
    for entry in palette:
        if entry["percentage"] < SIGNIFICANT_COLOR_SHARE:
            continue
        if all(_distinct_color(entry, group) for group in dominant_groups):
            dominant_groups.append(entry)
    variety = "monochrome" if len(dominant_groups) <= 1 else "bicolor" if len(dominant_groups) == 2 else "multicolor"
    contrast = 0.0
    for left in palette:
        for right in palette:
            contrast += left["percentage"] * right["percentage"] * float(delta_e_2000(np.array(left["lab"]), np.array(right["lab"])))
    l10, l90 = (float(value) for value in np.percentile(pixels[:, 0], [10, 90]))
    temperatures: dict[str, float] = {}
    families: dict[str, float] = {}
    for entry in palette:
        temperature = _temperature(np.array(entry["lab"]))
        temperatures[temperature] = temperatures.get(temperature, 0.0) + entry["percentage"]
        families[entry["family"]] = families.get(entry["family"], 0.0) + entry["percentage"]
    temperature = max(temperatures, key=temperatures.get)
    dominant_family = max(families, key=families.get)
    if variety == "multicolor":
        dominant_family = "multicolor"
    # Opposing substantial temperatures have no reliable categorical majority.
    warm = temperatures.get("warm", 0) + temperatures.get("warm_neutral", 0)
    cool = temperatures.get("cool", 0) + temperatures.get("cool_neutral", 0)
    if warm >= 0.25 and cool >= 0.25 and abs(warm - cool) < 0.20:
        temperature = "unknown"
    return {
        "temperature": temperature if reliable else "unknown",
        "lightness": _level(lightness, 35, 70) if reliable else "unknown",
        "chroma": _level(chroma, 25, 50) if reliable else "unknown",
        "saturation": _level(saturation, 0.25, 0.60) if reliable else "unknown",
        "neutrality": _level(neutrality, 0.35, 0.70) if reliable else "unknown",
        "internal_contrast": _level(max(contrast, (l90 - l10) / 2), 8, 20) if reliable else "unknown",
        "color_variety": variety if reliable else "unknown",
        "dominant_family": dominant_family if reliable else None,
        "metrics": {
            "mean_lightness_l": round(lightness, 3),
            "mean_chroma_c": round(chroma, 3),
            "mean_hsv_saturation": round(saturation, 4),
            "neutral_pixel_fraction": round(neutrality, 4),
            "lightness_p10_p90_range": round(l90 - l10, 3),
            "internal_contrast_delta_e": round(contrast, 3),
            "significant_color_group_count": len(dominant_groups),
            "palette_color_count": len(palette),
            "temperature_shares": {key: round(value, 6) for key, value in temperatures.items()},
            "family_shares": {key: round(value, 6) for key, value in families.items()},
        },
    }


def extract_colors(image: Image.Image, catalog: list[CatalogValue]) -> dict:
    """Evolve the existing palette contract with additive garment colorimetry."""
    preview = image.convert("RGB").copy()
    preview.thumbnail((256, 256), Image.Resampling.LANCZOS)
    rgb = np.asarray(preview)
    # OpenCV float input 0..1 yields real Lab, not its uint8 encoding.
    lab = cv2.cvtColor(rgb.astype(np.float32) / 255, cv2.COLOR_RGB2LAB)
    mask, foreground, segmentation, warnings = foreground_mask(lab)
    illumination, lighting_warnings = _illumination(rgb, mask, segmentation)
    warnings.extend(lighting_warnings)
    colorimetry: dict[str, Any] = {
        "version": COLORIMETRY_VERSION,
        "status": "insufficient_evidence",
        "commercial_color_status": "insufficient_evidence",
        "foreground_confidence": foreground,
        "palette": [], "primary_color": None,
        "secondary_colors": [], "accent_colors": [],
        "temperature": "unknown", "lightness": "unknown", "chroma": "unknown",
        "saturation": "unknown", "neutrality": "unknown",
        "internal_contrast": "unknown", "color_variety": "unknown",
        "dominant_family": None, "warnings": [], "coverage": 0.0, "metrics": {},
        "evidence": {
            "segmentation": segmentation, "illumination": illumination,
            "color_space": "CIELAB_D65_from_sRGB",
            "comparison_metric": "CIEDE2000",
            "original_modified": False,
            "white_balance_corrected": False,
            "properties_scope": "observed_geometric_foreground_only",
            "target_garment_identity_verified": False,
            "target_garment_identity_method": "not_implemented",
            "confidence_kind": "heuristic_uncalibrated",
            "thresholds_version": COLORIMETRY_VERSION,
        },
    }
    if foreground < MIN_FOREGROUND_CONFIDENCE or not mask.any():
        warnings.extend(["BACKGROUND_INTERFERENCE", "BACKGROUND_MAY_AFFECT_COLOR", "COLOR_PROPERTIES_UNCERTAIN"])
    else:
        palette = []
        references = _references(catalog)
        groups = _clusters(lab[mask])
        for group in groups:
            entry, catalog_warnings = _palette_element(group, references, foreground, illumination)
            palette.append(entry)
            warnings.extend(catalog_warnings)
        coverage = round(min(1.0, sum(group["percentage"] for group in groups)), 6)
        if not palette:
            warnings.append("COLOR_PROPERTIES_UNCERTAIN")
        else:
            reliable = bool(illumination["reliable"] and coverage >= 0.85)
            if coverage < 0.85:
                warnings.append("PALETTE_COVERAGE_LOW")
            if not reliable:
                warnings.append("COLOR_PROPERTIES_UNCERTAIN")
            properties = _properties(lab[mask], rgb[mask], palette, reliable)
            top = palette[0]
            next_share = palette[1]["percentage"] if len(palette) > 1 else 0.0
            primary = top if top["percentage"] >= 0.55 and top["percentage"] - next_share >= 0.15 else None
            if primary is None:
                warnings.append("NO_SINGLE_DOMINANT_COLOR")
            accent = [
                entry for entry in palette
                if entry is not primary and entry["percentage"] < SIGNIFICANT_COLOR_SHARE
                and float(delta_e_2000(np.array(entry["lab"]), np.array(top["lab"]))) >= 15
            ]
            secondary = [
                entry for entry in palette
                if entry is not primary and all(entry is not item for item in accent)
            ]
            colorimetry.update(properties)
            colorimetry.update({
                "status": "completed" if reliable else "partial",
                "palette": palette, "primary_color": primary,
                "secondary_colors": secondary, "accent_colors": accent, "coverage": coverage,
            })
            colorimetry["evidence"]["property_confidence_score"] = round(
                foreground * illumination["confidence_score"] * coverage, 3
            )
            colorimetry["evidence"]["percentages_basis"] = "all_accepted_interior_foreground_pixels"
            colorimetry["evidence"]["percentage_kind"] = "visible_image_area_approximation"
            colorimetry["evidence"]["palette_max_colors"] = MAX_PALETTE_COLORS
    # A connected object may contain a person, other garments or accessories.
    # Preserve the observations, but geometry cannot authorize commercial IDs.
    colorimetry["evidence"]["observation_status"] = colorimetry["status"]
    for swatch in colorimetry["palette"]:
        evidence = swatch["evidence"]
        evidence["observed_catalog_match_accepted"] = evidence["catalog_match_accepted"]
        evidence["observed_catalog_match_reason"] = evidence["catalog_match_reason"]
        evidence["catalog_match_accepted"] = False
        evidence["catalog_match_reason"] = "target_garment_identity_unverified"
        evidence["catalog_match_confidence_score"] = 0.0
        for field in ("catalog_color_id", "catalog_color_name", "catalog_id", "catalog_name"):
            swatch[field] = None
    warnings.append("TARGET_GARMENT_IDENTITY_UNVERIFIED")
    warnings = list(dict.fromkeys(warnings))
    colorimetry["warnings"] = warnings
    palette = colorimetry["palette"]
    return {
        "dominant_color": colorimetry["primary_color"],
        "secondary_colors": [entry for entry in palette if entry is not colorimetry["primary_color"]],
        "palette": palette,
        "foreground_confidence": foreground,
        "warnings": warnings,
        "coverage": colorimetry["coverage"],
        "colorimetry": colorimetry,
    }


