"""Conservative, deterministic object/background evidence for garment photos.

This is a silhouette heuristic, not a garment/person detector. The caller must
supply one isolated garment. Unreliable separation returns an empty mask.
"""

import cv2
import numpy as np

from mati_ai.vision.color_distance import delta_e_2000

MIN_FOREGROUND_CONFIDENCE = 0.70


def foreground_mask(lab: np.ndarray) -> tuple[np.ndarray, float, dict, list[str]]:
    height, width = lab.shape[:2]
    band = max(1, round(min(height, width) * 0.04))
    border_mask = np.zeros((height, width), dtype=bool)
    border_mask[:band] = border_mask[-band:] = True
    border_mask[:, :band] = border_mask[:, -band:] = True
    border = lab[border_mask]
    # Find the principal border mode before its median; a textured border must
    # not manufacture a background color by averaging incompatible colors.
    bins = np.floor(border / np.array([6, 8, 8])).astype(np.int16)
    _, inverse, counts = np.unique(bins, axis=0, return_inverse=True, return_counts=True)
    modal = border[inverse == int(np.argmax(counts))].mean(axis=0)
    near_mode = delta_e_2000(border, modal) <= 6
    background = np.median(border[near_mode], axis=0) if near_mode.any() else modal
    border_distance = delta_e_2000(border, background)
    distance = delta_e_2000(lab, background)
    side_distances = [
        distance[:band].reshape(-1), distance[-band:].reshape(-1),
        distance[:, :band].reshape(-1), distance[:, -band:].reshape(-1),
    ]
    side_support = [float(np.mean(values <= 7)) for values in side_distances]
    support = float(np.mean(border_distance <= 7))
    p90 = float(np.percentile(border_distance, 90))
    evidence = {
        "method": "border_connected_background_lab_ciede2000",
        "semantic_garment_detection": False,
        "foreground_confidence_kind": "heuristic_uncalibrated",
        "minimum_foreground_confidence": MIN_FOREGROUND_CONFIDENCE,
        "background_lab": [round(float(value), 3) for value in background],
        "background_border_support": round(support, 4),
        "background_border_delta_e_p90": round(p90, 3),
        "background_side_support": [round(value, 4) for value in side_support],
        "analysis_width": width,
        "analysis_height": height,
    }
    warnings = []
    empty = np.zeros((height, width), dtype=bool)
    uniform = (
        support >= 0.82 and p90 <= 7
        and min(side_support) >= 0.65
        and sum(value >= 0.82 for value in side_support) >= 3
    )
    if not uniform:
        evidence["rejection_reason"] = "background_not_uniform_on_supported_borders"
        return empty, round(min(0.49, support * 0.49), 3), evidence, ["BACKGROUND_INTERFERENCE"]

    # Only color-similar regions connected to the image border are background.
    # Similar patches enclosed by the silhouette remain in the garment palette.
    threshold = max(6.0, min(11.0, p90 + 3.0))
    similar = (distance <= threshold).astype(np.uint8)
    _, labels = cv2.connectedComponents(similar, connectivity=8)
    exterior_labels = np.unique(labels[border_mask & similar.astype(bool)])
    connected_background = np.isin(labels, exterior_labels) & similar.astype(bool)
    candidate = (~connected_background).astype(np.uint8)
    # A small closing joins sampling-scale gaps without filling a silhouette's
    # large holes. It never bridges distinct sizable foreground objects.
    candidate = cv2.morphologyEx(candidate, cv2.MORPH_CLOSE, np.ones((3, 3), np.uint8))
    component_count, components, stats, centroids = cv2.connectedComponentsWithStats(candidate, connectivity=8)
    total = height * width
    fraction = float(candidate.mean())
    center = np.zeros((height, width), dtype=bool)
    center[int(height * 0.28):int(height * 0.72), int(width * 0.28):int(width * 0.72)] = True
    eligible = [
        index for index in range(1, component_count)
        if stats[index, cv2.CC_STAT_AREA] >= total * 0.04
        and np.any((components == index) & center)
    ]
    evidence.update({
        "background_gate_delta_e": round(threshold, 3),
        "candidate_foreground_fraction": round(fraction, 4),
        "significant_components": int(sum(stats[index, cv2.CC_STAT_AREA] >= total * 0.035 for index in range(1, component_count))),
    })
    if not eligible or fraction < 0.08:
        evidence["rejection_reason"] = "no_supported_central_foreground"
        return empty, 0.30, evidence, ["BACKGROUND_INTERFERENCE", "FOREGROUND_TOO_SMALL"]
    selected = max(eligible, key=lambda index: int(stats[index, cv2.CC_STAT_AREA]))
    mask = components == selected
    area = int(mask.sum())
    component_share = area / max(int(candidate.sum()), 1)
    other_large = [
        index for index in range(1, component_count)
        if index != selected and stats[index, cv2.CC_STAT_AREA] >= total * 0.035
    ]
    central_occupancy = float(mask[center].mean())
    border_leakage = float(mask[border_mask].mean())
    center_x, center_y = centroids[selected] / np.array([width, height])
    offset = float(np.hypot(center_x - 0.5, center_y - 0.5))
    foreground_distance = float(np.median(distance[mask]))
    boundary = cv2.dilate(mask.astype(np.uint8), np.ones((3, 3), np.uint8)) != cv2.erode(mask.astype(np.uint8), np.ones((3, 3), np.uint8))
    lightness = lab[..., 0].astype(np.float32)
    dx = cv2.Sobel(lightness, cv2.CV_32F, 1, 0)
    dy = cv2.Sobel(lightness, cv2.CV_32F, 0, 1)
    edge_support = float(np.mean(np.hypot(dx, dy)[boundary] >= 8)) if boundary.any() else 0.0
    evidence.update({
        "foreground_fraction": round(area / total, 4),
        "principal_component_share": round(component_share, 4),
        "central_occupancy": round(central_occupancy, 4),
        "border_leakage": round(border_leakage, 4),
        "centroid_offset": round(offset, 4),
        "foreground_background_delta_e_median": round(foreground_distance, 3),
        "boundary_lightness_edge_support": round(edge_support, 4),
    })
    if other_large or component_share < 0.88:
        warnings.append("MULTIPLE_FOREGROUND_REGIONS")
    if border_leakage > 0.05 or area / total > 0.88:
        warnings.append("GARMENT_MAY_BE_CROPPED")
    if central_occupancy < 0.45 or offset > 0.22 or foreground_distance < 10:
        warnings.append("BACKGROUND_INTERFERENCE")
    if warnings:
        evidence["rejection_reason"] = "foreground_geometry_or_separation_uncertain"
        return empty, 0.45, evidence, list(dict.fromkeys(["BACKGROUND_INTERFERENCE", *warnings]))

    confidence = min(0.94, (
        0.30 * support
        + 0.20 * max(0, 1 - p90 / 10)
        + 0.20 * min(1, foreground_distance / 22)
        + 0.15 * component_share
        + 0.10 * min(1, central_occupancy / 0.8)
        + 0.05 * min(1, edge_support / 0.3)
    ))
    # Avoid anti-aliased boundary pixels; keep enough area for slim silhouettes.
    core = cv2.erode(mask.astype(np.uint8), np.ones((3, 3), np.uint8)).astype(bool)
    if core.sum() < area * 0.85:
        evidence["rejection_reason"] = "insufficient_interior_pixels"
        return empty, 0.50, evidence, ["BACKGROUND_INTERFERENCE", "FOREGROUND_TOO_SMALL"]
    evidence["sampled_foreground_fraction"] = round(float(core.mean()), 4)
    evidence["boundary_pixels_excluded"] = area - int(core.sum())
    evidence["rejection_reason"] = None
    if confidence < MIN_FOREGROUND_CONFIDENCE:
        evidence["rejection_reason"] = "confidence_below_threshold"
        return empty, round(confidence, 3), evidence, ["BACKGROUND_INTERFERENCE"]
    return core, round(confidence, 3), evidence, []

