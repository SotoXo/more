"""Benchmark-only conservative garment target policy; never a product identity model.

Inputs are trusted detector/mask outputs and an immutable server-owned context.
Browser mask/path/owner fields must never be supplied as these trusted inputs.
No inference, files, network, database, image transforms, or commercial writes.
"""
from __future__ import annotations

from dataclasses import asdict, dataclass
import hashlib
import math
import re
import time
from typing import Any

POLICY_VERSION = "target-selection-benchmark-1"
CONTRACT_VERSION = "target-garment-selection-1"
MIN_AREA_RATIO = 0.005
MAX_AREA_RATIO = 0.90
MAX_CANDIDATES = 12
DUPLICATE_BBOX_IOU = 0.85
MAX_AUTO_CLIPPING = 0.03
_ID = re.compile(r"^[A-Za-z0-9_-]{1,120}$")
_SHA = re.compile(r"^[a-f0-9]{64}$")
ROLES = {
    "shirt": "upper", "blouse": "upper", "t-shirt": "upper",
    "polo shirt": "upper", "sweater": "upper", "cardigan": "upper",
    "blazer": "upper", "jacket": "upper", "coat": "upper",
    "trousers": "lower", "jeans": "lower", "skirt": "lower",
    "shorts": "lower", "dress": "one_piece", "jumpsuit": "one_piece",
}
# Strict spelling aliases; never merge shirt/blouse/blazer merely by upper role.
LABEL_ALIASES = {"t shirt": "t-shirt", "tshirt": "t-shirt", "pants": "trousers"}
SIDES = ("top", "right", "bottom", "left")


def _finite(value: Any) -> bool:
    return isinstance(value, (int, float)) and not isinstance(value, bool) and math.isfinite(value)


@dataclass(frozen=True)
class SelectionContext:
    request_id: str
    image_sha256: str
    owner_id: str
    policy_version: str = POLICY_VERSION
    image_generation: int = 0
    expires_at: float | None = None

    def __post_init__(self) -> None:
        if not isinstance(self.request_id, str) or not _ID.fullmatch(self.request_id):
            raise ValueError("INVALID_REQUEST_ID")
        if not isinstance(self.owner_id, str) or not _ID.fullmatch(self.owner_id):
            raise ValueError("INVALID_OWNER_ID")
        if not isinstance(self.image_sha256, str) or not _SHA.fullmatch(self.image_sha256):
            raise ValueError("INVALID_IMAGE_SHA256")
        if self.policy_version != POLICY_VERSION:
            raise ValueError("UNSUPPORTED_POLICY_VERSION")
        if type(self.image_generation) is not int or self.image_generation < 0:
            raise ValueError("INVALID_IMAGE_GENERATION")
        if self.expires_at is not None and not _finite(self.expires_at):
            raise ValueError("INVALID_EXPIRY")


@dataclass(frozen=True)
class HumanSelection:
    request_id: str
    image_sha256: str
    candidate_id: str
    policy_version: str = POLICY_VERSION
    image_generation: int = 0


def build_mask_evidence(mask: Any) -> dict:
    """Compute validated binary mask metadata without serializing its matrix.

    Accepts a 2D NumPy bool/0-1 array without importing NumPy, or a Python grid.
    Digest is SHA256 of uint8 C-order mask bytes prefixed by H,W as big-endian
    uint64, not the digest of an encoded PNG file.
    """
    try:
        if hasattr(mask, "ndim") and hasattr(mask, "tobytes"):
            if mask.ndim != 2 or not ((mask == 0) | (mask == 1)).all():
                raise ValueError("INVALID_BINARY_MASK")
            h, w = map(int, mask.shape)
            if h < 1 or w < 1:
                raise ValueError("INVALID_MASK_SHAPE")
            binary = mask.astype("uint8", copy=False)
            raw = binary.tobytes(order="C")
            area = int(binary.sum())
            touched = [side for side, value in zip(SIDES, (
                binary[0, :].any(), binary[:, -1].any(),
                binary[-1, :].any(), binary[:, 0].any(),
            )) if bool(value)]
        else:
            if not isinstance(mask, (list, tuple)) or not mask:
                raise ValueError("INVALID_MASK_SHAPE")
            h = len(mask)
            w = len(mask[0]) if isinstance(mask[0], (list, tuple)) else 0
            if w < 1 or any(not isinstance(row, (list, tuple)) or len(row) != w for row in mask):
                raise ValueError("INVALID_MASK_SHAPE")
            if any(not _finite(v) and not isinstance(v, bool) or v not in (0, 1) for row in mask for v in row):
                raise ValueError("INVALID_BINARY_MASK")
            raw = bytes(int(v) for row in mask for v in row)
            area = sum(raw)
            touched = [side for side, value in zip(SIDES, (
                any(mask[0]), any(row[-1] for row in mask),
                any(mask[-1]), any(row[0] for row in mask),
            )) if value]
    except (AttributeError, TypeError, OverflowError) as exc:
        raise ValueError("INVALID_BINARY_MASK") from exc
    digest = hashlib.sha256(h.to_bytes(8, "big") + w.to_bytes(8, "big") + raw).hexdigest()
    return {"shape": [h, w], "area_pixels": area, "nonempty": area > 0,
            "sha256": digest, "border_sides_touched": touched}


def bbox_iou(left: list[float], right: list[float]) -> float:
    """Detection deduplication only; not IoU against ground-truth masks."""
    iw = max(0.0, min(left[2], right[2]) - max(left[0], right[0]))
    ih = max(0.0, min(left[3], right[3]) - max(left[1], right[1]))
    intersection = iw * ih
    total = (left[2] - left[0]) * (left[3] - left[1]) + (right[2] - right[0]) * (right[3] - right[1]) - intersection
    return intersection / total if total > 0 else 0.0


def _candidate(item: Any, index: int, size: tuple[int, int]) -> tuple[dict | None, str | None]:
    if not isinstance(item, dict):
        return None, "MALFORMED_CANDIDATE"
    candidate_id = item.get("id")
    if not isinstance(candidate_id, str) or not _ID.fullmatch(candidate_id):
        return None, "INVALID_CANDIDATE_ID"
    label = item.get("label")
    if not isinstance(label, str):
        return None, "INVALID_GARMENT_CLASS"
    label = LABEL_ALIASES.get(label.strip().lower(), label.strip().lower())
    if label not in ROLES or item.get("role", ROLES.get(label)) != ROLES[label]:
        return None, "UNSUPPORTED_GARMENT_CLASS"
    score = item.get("score")
    if not _finite(score) or not 0 <= score <= 1:
        return None, "INVALID_DETECTOR_SCORE"
    box = item.get("box_xyxy")
    if not isinstance(box, (list, tuple)) or len(box) != 4 or not all(_finite(v) for v in box):
        return None, "INVALID_BBOX"
    x1, y1, x2, y2 = map(float, box)
    w, h = size
    if not 0 <= x1 < x2 <= w or not 0 <= y1 < y2 <= h:
        return None, "CANDIDATE_OUTSIDE_IMAGE"
    box_ratio = (x2 - x1) * (y2 - y1) / (w * h)
    if not MIN_AREA_RATIO <= box_ratio <= MAX_AREA_RATIO:
        return None, "BBOX_AREA_OUT_OF_RANGE"
    clipping = item.get("bbox_clipping_fraction", 0.0)
    if not _finite(clipping) or not 0 <= clipping <= 1:
        return None, "INVALID_CLIPPING"
    evidence = item.get("mask_evidence")
    if not isinstance(evidence, dict):
        return None, "MASK_EVIDENCE_REQUIRED"
    shape = evidence.get("shape")
    if not isinstance(shape, (tuple, list)) or len(shape) != 2 or any(type(v) is not int for v in shape) or list(shape) != [h, w]:
        return None, "MASK_SHAPE_MISMATCH"
    area = evidence.get("area_pixels")
    if type(area) is not int or not 0 < area <= w * h or evidence.get("nonempty") is not True:
        return None, "INVALID_MASK_AREA"
    ratio = area / (w * h)
    if not MIN_AREA_RATIO <= ratio <= MAX_AREA_RATIO:
        return None, "MASK_AREA_OUT_OF_RANGE"
    digest = evidence.get("sha256")
    if not isinstance(digest, str) or not _SHA.fullmatch(digest):
        return None, "INVALID_MASK_DIGEST"
    sides = evidence.get("border_sides_touched")
    if not isinstance(sides, (tuple, list)) or any(not isinstance(side, str) or side not in SIDES for side in sides) or len(set(sides)) != len(sides):
        return None, "INVALID_BORDER_EVIDENCE"
    auto = len(sides) < 2 and clipping <= MAX_AUTO_CLIPPING
    return {
        "id": candidate_id, "external_class": label, "mapped_roles": [ROLES[label]],
        "score": float(score), "score_kind": "detector_score_uncalibrated",
        "box_xyxy": [x1, y1, x2, y2], "bbox": {"x": x1, "y": y1, "width": x2-x1, "height": y2-y1},
        "bbox_area_ratio": box_ratio, "area_ratio": ratio,
        "mask_evidence": {"shape": [h, w], "area_pixels": area, "nonempty": True,
                          "sha256": digest, "border_sides_touched": list(sides)},
        "bbox_clipping_fraction": float(clipping), "automatic_eligible": auto,
        "source_index": index, "alias_candidate_ids": [],
    }, None


def _result(context: SelectionContext, status: str, reason: str, candidates: list[dict] | None = None,
            rejected: list[dict] | None = None, selected: dict | None = None,
            mode: str = "none", warnings: list[str] | None = None) -> dict:
    candidates, rejected = candidates or [], rejected or []
    return {
        "contract_version": CONTRACT_VERSION,
        "target_garment": {
            "status": status, "selection_mode": mode,
            "candidate_count": len(candidates), "eligible_candidate_count": len(candidates),
            "selected_candidate_id": selected["id"] if selected else None,
            "confidence": None, "selection_policy_version": POLICY_VERSION,
            "warnings": warnings or [], "selection_validated": selected is not None,
            "commercial_authorization": False,
        },
        "candidates": candidates, "rejected_candidates": rejected,
        "selected_index": selected["source_index"] if selected else None,
        "reason": reason,
        "evidence": {"context": asdict(context), "semantic_target_accuracy_measured": False,
                     "score_is_probability": False, "benchmark_only": True},
    }


def evaluate_candidates(detections: list[dict], image_size: tuple[int, int], *,
                        context: SelectionContext, model_available: bool = True,
                        error_code: str | None = None, truncated: bool = False) -> dict:
    """AUTO selection without GT category, hints, largest-box, or max-score shortcuts."""
    if not isinstance(context, SelectionContext):
        raise ValueError("IMMUTABLE_CONTEXT_REQUIRED")
    if not model_available or error_code:
        code = error_code if isinstance(error_code, str) and re.fullmatch(r"[A-Z_]{3,80}", error_code) else "MODEL_UNAVAILABLE"
        return _result(context, "insufficient_evidence", code, warnings=[code])
    if context.expires_at is not None and context.expires_at <= time.time():
        return _result(context, "insufficient_evidence", "STALE_SELECTION", warnings=["STALE_SELECTION"])
    if not isinstance(image_size, (tuple, list)) or len(image_size) != 2 or any(type(v) is not int or v < 1 for v in image_size):
        return _result(context, "insufficient_evidence", "INVALID_IMAGE_SIZE", warnings=["INVALID_IMAGE_SIZE"])
    if not isinstance(detections, list):
        return _result(context, "insufficient_evidence", "MALFORMED_SEGMENTATION_RESPONSE", warnings=["MALFORMED_SEGMENTATION_RESPONSE"])
    if truncated or len(detections) > MAX_CANDIDATES:
        return _result(context, "ambiguous", "MAX_CANDIDATES_EXCEEDED", warnings=["MAX_CANDIDATES_EXCEEDED"])
    ids = [d.get("id") for d in detections if isinstance(d, dict)]
    if len(ids) != len(detections) or any(not isinstance(i, str) or not _ID.fullmatch(i) for i in ids):
        return _result(context, "insufficient_evidence", "MALFORMED_SEGMENTATION_RESPONSE", warnings=["MALFORMED_SEGMENTATION_RESPONSE"])
    if len(set(ids)) != len(ids):
        return _result(context, "insufficient_evidence", "DUPLICATE_CANDIDATE_IDS", warnings=["DUPLICATE_CANDIDATE_IDS"])
    valid, rejected = [], []
    for index, item in enumerate(detections):
        candidate, reason = _candidate(item, index, tuple(image_size))
        if candidate is None:
            rejected.append({"id": item["id"], "reason": reason, "source_index": index})
            continue
        alias = next((prior for prior in valid
                      if prior["external_class"] == candidate["external_class"]
                      and prior["mapped_roles"] == candidate["mapped_roles"]
                      and bbox_iou(prior["box_xyxy"], candidate["box_xyxy"]) >= DUPLICATE_BBOX_IOU), None)
        if alias:
            alias["alias_candidate_ids"].append(candidate["id"])
            alias["automatic_eligible"] = alias["automatic_eligible"] and candidate["automatic_eligible"]
        else:
            valid.append(candidate)
    if not valid:
        return _result(context, "insufficient_evidence", "NO_VALID_CANDIDATE", rejected=rejected,
                       warnings=list(dict.fromkeys(r["reason"] for r in rejected)))
    if len(valid) > 1:
        return _result(context, "ambiguous", "MULTIPLE_PLAUSIBLE_GARMENTS", valid, rejected)
    if rejected:
        return _result(context, "ambiguous", "PLAUSIBLE_INVALID_CANDIDATE", valid, rejected,
                       warnings=list(dict.fromkeys(r["reason"] for r in rejected)))
    if not valid[0]["automatic_eligible"]:
        return _result(context, "ambiguous", "BORDER_OR_CLIPPING_REQUIRES_HUMAN", valid,
                       warnings=["BORDER_OR_CLIPPING_REQUIRES_HUMAN"])
    return _result(context, "selected", "SINGLE_VALID_GARMENT", valid, selected=valid[0], mode="automatic")


def select_human_candidate(detections: list[dict], image_size: tuple[int, int], *,
                           context: SelectionContext, selection: HumanSelection,
                           actor_owner_id: str, current_context: SelectionContext,
                           now: float | None = None) -> dict:
    """Select against trusted original detections/context, never browser geometry.

    actor_owner_id comes from authenticated session. current_context is computed
    server-side from current image bytes/generation, never a browser SHA claim.
    Expired/stale/other-owner/other-run requests fail closed without selecting.
    """
    stamp = time.time() if now is None else now
    if not isinstance(context, SelectionContext) or not isinstance(current_context, SelectionContext) or not isinstance(selection, HumanSelection):
        raise ValueError("IMMUTABLE_CONTEXT_AND_SELECTION_REQUIRED")
    mismatch = (
        current_context != context
        or type(selection.image_generation) is not int
        or not isinstance(selection.request_id, str)
        or not isinstance(selection.image_sha256, str)
        or not isinstance(selection.policy_version, str)
        or actor_owner_id != context.owner_id
        or selection.request_id != context.request_id
        or selection.image_sha256 != context.image_sha256
        or selection.policy_version != context.policy_version
        or selection.image_generation != context.image_generation
        or not _finite(stamp)
        or context.expires_at is not None and context.expires_at <= stamp
    )
    if mismatch:
        return _result(context, "insufficient_evidence", "SELECTION_IDENTITY_MISMATCH",
                       warnings=["SELECTION_IDENTITY_MISMATCH"])
    result = evaluate_candidates(detections, image_size, context=context)
    candidate = next((c for c in result["candidates"] if c["id"] == selection.candidate_id), None)
    if not candidate:
        return _result(context, "insufficient_evidence", "INVALID_SELECTED_CANDIDATE",
                       result["candidates"], result["rejected_candidates"],
                       warnings=["INVALID_SELECTED_CANDIDATE"])
    return _result(context, "selected", "EXPLICIT_HUMAN_SELECTION", result["candidates"],
                   result["rejected_candidates"], selected=candidate, mode="human",
                   warnings=result["target_garment"]["warnings"])
