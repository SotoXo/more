"""Pure policy/security checks. No models, inference, network, database, or catalogue."""
from dataclasses import FrozenInstanceError, replace
import math
import time

import pytest

from policy import (
    CONTRACT_VERSION, HumanSelection, SelectionContext,
    bbox_iou, build_mask_evidence, evaluate_candidates, select_human_candidate,
)

SIZE = (20, 20)
CTX = SelectionContext("run-fixture-1", "a" * 64, "owner-fixture-1")


def detection(candidate_id="garment_1", label="shirt", box=(2, 2, 6, 6), score=.75):
    grid = [[0 for _ in range(SIZE[0])] for _ in range(SIZE[1])]
    x1, y1, x2, y2 = box
    for y in range(max(0, int(y1)), min(SIZE[1], int(y2))):
        for x in range(max(0, int(x1)), min(SIZE[0], int(x2))):
            grid[y][x] = 1
    return {"id": candidate_id, "label": label, "score": score,
            "box_xyxy": list(box), "mask_evidence": build_mask_evidence(grid)}


def evaluate(items, **kwargs):
    return evaluate_candidates(items, SIZE, context=CTX, **kwargs)


def target(result):
    return result["target_garment"]


def test_empty_candidates_failclosed():
    r = evaluate([])
    assert target(r)["status"] == "insufficient_evidence"
    assert target(r)["selection_mode"] == "none"
    assert r["selected_index"] is None


def test_single_valid_candidate_selected_automatic_and_no_commercial_authority():
    r = evaluate([detection()])
    assert r["contract_version"] == CONTRACT_VERSION
    assert target(r)["status"] == "selected"
    assert target(r)["selection_mode"] == "automatic"
    assert target(r)["selected_candidate_id"] == "garment_1"
    assert target(r)["confidence"] is None
    assert target(r)["commercial_authorization"] is False
    assert r["selected_index"] == 0
    assert r["candidates"][0]["score_kind"] == "detector_score_uncalibrated"


def test_multiple_candidates_not_selected_by_score_size_or_centrality():
    r = evaluate([detection(score=.26), detection("garment_2", "trousers", (8, 8, 18, 18), .99)])
    assert target(r)["status"] == "ambiguous"
    assert target(r)["selected_candidate_id"] is None
    assert target(r)["candidate_count"] == 2


def test_overlapping_shirt_and_blazer_remain_distinct():
    r = evaluate([detection(), detection("garment_2", "blazer")])
    assert target(r)["status"] == "ambiguous"
    assert len(r["candidates"]) == 2


def test_strict_spelling_alias_dedup_preserves_source_identity_without_max_score():
    r = evaluate([detection(label="t-shirt", score=.26), detection("alias_2", "tshirt", score=.99)])
    assert target(r)["status"] == "selected"
    assert r["selected_index"] == 0
    assert r["candidates"][0]["alias_candidate_ids"] == ["alias_2"]
    assert r["candidates"][0]["score"] == .26


def test_alias_border_clipping_alert_is_not_dropped():
    alias = detection("alias_2")
    alias["bbox_clipping_fraction"] = .04
    r = evaluate([detection(), alias])
    assert target(r)["status"] == "ambiguous"


@pytest.mark.parametrize("mutation,reason", [
    ({"box_xyxy": [-1, 2, 6, 6]}, "CANDIDATE_OUTSIDE_IMAGE"),
    ({"box_xyxy": [2, 2, 21, 6]}, "CANDIDATE_OUTSIDE_IMAGE"),
    ({"box_xyxy": [2, 2, math.nan, 6]}, "INVALID_BBOX"),
    ({"box_xyxy": [1, 1, 2, 2]}, "BBOX_AREA_OUT_OF_RANGE"),
    ({"box_xyxy": [0, 0, 20, 20]}, "BBOX_AREA_OUT_OF_RANGE"),
    ({"box_xyxy": [5, 5, 4, 4]}, "CANDIDATE_OUTSIDE_IMAGE"),
    ({"score": math.inf}, "INVALID_DETECTOR_SCORE"),
    ({"score": True}, "INVALID_DETECTOR_SCORE"),
    ({"score": -1}, "INVALID_DETECTOR_SCORE"),
    ({"role": "lower"}, "UNSUPPORTED_GARMENT_CLASS"),
    ({"label": "person"}, "UNSUPPORTED_GARMENT_CLASS"),
    ({"mask_evidence": None}, "MASK_EVIDENCE_REQUIRED"),
])
def test_geometry_or_detector_contract_rejection(mutation, reason):
    bad = detection()
    bad.update(mutation)
    r = evaluate([bad])
    assert target(r)["status"] == "insufficient_evidence"
    assert r["rejected_candidates"][0]["reason"] == reason


@pytest.mark.parametrize("mutation,reason", [
    ({"shape": [21, 20]}, "MASK_SHAPE_MISMATCH"),
    ({"shape": [20., 20]}, "MASK_SHAPE_MISMATCH"),
    ({"area_pixels": 0, "nonempty": False}, "INVALID_MASK_AREA"),
    ({"area_pixels": 1}, "MASK_AREA_OUT_OF_RANGE"),
    ({"area_pixels": 400}, "MASK_AREA_OUT_OF_RANGE"),
    ({"area_pixels": True}, "INVALID_MASK_AREA"),
    ({"sha256": "../../mask"}, "INVALID_MASK_DIGEST"),
    ({"border_sides_touched": ["top", "top"]}, "INVALID_BORDER_EVIDENCE"),
])
def test_mask_shape_area_and_digest_contract(mutation, reason):
    bad = detection()
    bad["mask_evidence"].update(mutation)
    r = evaluate([bad])
    assert target(r)["status"] == "insufficient_evidence"
    assert r["rejected_candidates"][0]["reason"] == reason


def test_invalid_plausible_candidate_blocks_remaining_single_automatic():
    bad = detection("garment_2", "trousers")
    bad["mask_evidence"]["area_pixels"] = 0
    r = evaluate([detection(), bad])
    assert target(r)["status"] == "ambiguous"
    assert r["reason"] == "PLAUSIBLE_INVALID_CANDIDATE"
    assert r["selected_index"] is None
    assert len(r["candidates"]) == 1


@pytest.mark.parametrize("code", ["MODEL_UNAVAILABLE", "MODEL_TIMEOUT", "MODEL_OOM"])
def test_model_failure_safe_without_prediction(code):
    r = evaluate([detection()], error_code=code)
    assert target(r)["status"] == "insufficient_evidence"
    assert target(r)["selected_candidate_id"] is None
    assert r["reason"] == code


def test_model_not_prepared_is_safe():
    assert target(evaluate([detection()], model_available=False))["status"] == "insufficient_evidence"


def test_candidate_overflow_and_truncation_never_single_auto():
    r = evaluate([detection(str(i)) for i in range(13)])
    assert target(r)["status"] == "ambiguous"
    assert r["reason"] == "MAX_CANDIDATES_EXCEEDED"
    assert target(r)["selected_candidate_id"] is None
    assert target(evaluate([detection()], truncated=True))["status"] == "ambiguous"


def test_two_sides_border_requires_human_but_valid_candidate_is_selectable():
    item = detection(box=(0, 0, 8, 8))
    r = evaluate([item])
    assert target(r)["status"] == "ambiguous"
    chosen = select_human_candidate([item], SIZE, context=CTX,
        selection=HumanSelection(CTX.request_id, CTX.image_sha256, "garment_1"),
        actor_owner_id=CTX.owner_id, current_context=CTX)
    assert target(chosen)["selection_mode"] == "human"


def test_clipping_boundary_is_fixed():
    item = detection()
    item["bbox_clipping_fraction"] = .03
    assert target(evaluate([item]))["status"] == "selected"
    item["bbox_clipping_fraction"] = .030001
    assert target(evaluate([item]))["status"] == "ambiguous"


def test_duplicate_ids_are_malformed_not_deduplicated():
    r = evaluate([detection(), detection()])
    assert r["reason"] == "DUPLICATE_CANDIDATE_IDS"
    assert target(r)["status"] == "insufficient_evidence"


@pytest.mark.parametrize("payload", [None, {}, [None], [1], [{"id": ["nested"]}]])
def test_malformed_response_safe(payload):
    r = evaluate(payload)
    assert target(r)["status"] == "insufficient_evidence"


def test_human_selects_exact_candidate_in_ambiguous_image():
    items = [detection(), detection("garment_2", "trousers", (10, 10, 16, 16))]
    r = select_human_candidate(items, SIZE, context=CTX,
        selection=HumanSelection(CTX.request_id, CTX.image_sha256, "garment_2"),
        actor_owner_id=CTX.owner_id, current_context=CTX)
    assert target(r)["status"] == "selected"
    assert target(r)["selection_mode"] == "human"
    assert r["selected_index"] == 1


@pytest.mark.parametrize("selection,actor", [
    (HumanSelection("another-run", CTX.image_sha256, "garment_1"), CTX.owner_id),
    (HumanSelection(CTX.request_id, "b"*64, "garment_1"), CTX.owner_id),
    (HumanSelection(CTX.request_id, CTX.image_sha256, "garment_1", "other-policy"), CTX.owner_id),
    (HumanSelection(CTX.request_id, CTX.image_sha256, "garment_1", image_generation=1), CTX.owner_id),
    (HumanSelection(CTX.request_id, CTX.image_sha256, "garment_1"), "another-owner"),
])
def test_human_selection_bound_to_run_image_owner_policy_generation(selection, actor):
    r = select_human_candidate([detection()], SIZE, context=CTX, selection=selection, actor_owner_id=actor, current_context=CTX)
    assert target(r)["status"] == "insufficient_evidence"
    assert r["reason"] == "SELECTION_IDENTITY_MISMATCH"


@pytest.mark.parametrize("candidate_id", ["unknown", "../../mask.png", "garment_2"])
def test_manipulated_candidate_id_not_selected(candidate_id):
    r = select_human_candidate([detection()], SIZE, context=CTX,
        selection=HumanSelection(CTX.request_id, CTX.image_sha256, candidate_id),
        actor_owner_id=CTX.owner_id, current_context=CTX)
    assert r["reason"] == "INVALID_SELECTED_CANDIDATE"


def test_expired_context_rejected_for_auto_and_human():
    expired = replace(CTX, expires_at=time.time()-1)
    assert target(evaluate_candidates([detection()], SIZE, context=expired))["status"] == "insufficient_evidence"
    r = select_human_candidate([detection()], SIZE, context=expired,
        selection=HumanSelection(CTX.request_id, CTX.image_sha256, "garment_1"),
        actor_owner_id=CTX.owner_id, current_context=CTX)
    assert target(r)["status"] == "insufficient_evidence"


def test_context_is_immutable_and_invalid_sha_rejected():
    with pytest.raises(FrozenInstanceError):
        CTX.image_sha256 = "b"*64
    with pytest.raises(ValueError, match="INVALID_IMAGE_SHA256"):
        SelectionContext("request", "not-sha", "owner")
    with pytest.raises(ValueError, match="UNSUPPORTED_POLICY"):
        replace(CTX, policy_version="other")


def test_binary_mask_metadata_and_no_dense_json():
    evidence = build_mask_evidence([[0, 1, 0], [0, 1, 0]])
    assert evidence["shape"] == [2, 3]
    assert evidence["area_pixels"] == 2
    assert evidence["border_sides_touched"] == ["top", "bottom"]
    assert len(evidence["sha256"]) == 64
    assert all(key not in evidence for key in ("pixels", "mask", "path"))


@pytest.mark.parametrize("mask", [[], [[0], [0, 1]], [[0, .5]], [[float("nan")]], [[2]], [["1"]]])
def test_invalid_binary_mask_builder(mask):
    with pytest.raises(ValueError):
        build_mask_evidence(mask)


def test_numpy_and_python_binary_masks_share_identity():
    np = pytest.importorskip("numpy")
    grid = [[0, 1, 0], [0, 1, 0]]
    assert build_mask_evidence(np.asarray(grid, dtype=np.bool_)) == build_mask_evidence(grid)


def test_bbox_iou_is_detection_duplicate_metric_only():
    assert bbox_iou([0, 0, 10, 10], [0, 0, 10, 10]) == 1
    assert bbox_iou([0, 0, 10, 10], [20, 20, 30, 30]) == 0


@pytest.mark.parametrize("new_context", [
    replace(CTX, image_sha256="b"*64),
    replace(CTX, image_generation=1),
    replace(CTX, owner_id="another-owner"),
    replace(CTX, request_id="another-run"),
])
def test_matching_old_browser_payload_cannot_authorize_current_different_image_or_owner(new_context):
    old = HumanSelection(CTX.request_id, CTX.image_sha256, "garment_1")
    r = select_human_candidate([detection()], SIZE, context=CTX, selection=old,
        actor_owner_id=CTX.owner_id, current_context=new_context)
    assert target(r)["status"] == "insufficient_evidence"
    assert r["reason"] == "SELECTION_IDENTITY_MISMATCH"


@pytest.mark.parametrize("generation", [False, 0.0])
def test_selection_generation_rejects_boolean_or_float_even_when_equal_to_zero(generation):
    r = select_human_candidate([detection()], SIZE, context=CTX,
        selection=HumanSelection(CTX.request_id, CTX.image_sha256, "garment_1", image_generation=generation),
        actor_owner_id=CTX.owner_id, current_context=CTX)
    assert target(r)["status"] == "insufficient_evidence"
