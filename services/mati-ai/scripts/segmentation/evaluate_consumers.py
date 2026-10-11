"""Isolated paired consumers: frozen SigLIP, original Lab/CIEDE2000 and exact retrieval.

No DB, no HTTP inference service, no model updates, no production color guard changes.
Run only after the explicitly prepared candidate benchmark. All artifacts stay private.
"""
from __future__ import annotations
import argparse
import gc
import hashlib
import json
from pathlib import Path

import cv2
import numpy as np
from PIL import Image

from metrics import classification_metrics, color_metrics, retrieval_metrics
from mati_ai.contracts.vision import CatalogValue
from mati_ai.core.config import Settings
from mati_ai.core.model_manager import ModelManager
from mati_ai.vision.classification import category_prompts, classify
from mati_ai.vision import color as colors
from mati_ai.vision.color_distance import delta_e_2000
from mati_ai.vision.segmentation import MIN_FOREGROUND_CONFIDENCE, foreground_mask
from mati_ai.vision.validation import validate_image


def write_json(path: Path, value):
    path.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
    temporary = path.with_suffix(path.suffix + ".tmp")
    temporary.write_text(json.dumps(value, ensure_ascii=False, indent=2) + "\n")
    temporary.chmod(0o600)
    temporary.replace(path)


def safe_artifact(base: Path, reference: str) -> Path:
    if not isinstance(reference, str) or not reference or Path(reference).is_absolute():
        raise ValueError("INVALID_ARTIFACT_REFERENCE")
    path = (base / reference).resolve()
    if not path.is_relative_to(base.resolve()) or not path.is_file():
        raise ValueError("ARTIFACT_OUTSIDE_PRIVATE_BENCHMARK")
    return path



def automatic_target(detection: dict, policy_version: str) -> dict | None:
    """Only validated AUTO artifacts enter automatic consumer metrics."""
    candidates = {c["id"]: c for c in detection["candidates"]}
    if len(candidates) != len(detection["candidates"]):
        raise ValueError("DUPLICATE_CANDIDATE_IDS")
    target = detection.get("target_garment", {})
    selected_id = detection.get("selected_candidate_id")
    if selected_id != target.get("selected_candidate_id"):
        raise ValueError("SELECTED_CANDIDATE_ID_MISMATCH")
    if (target.get("status") != "selected"
            or target.get("selection_mode") != "automatic"
            or target.get("selection_validated") is not True
            or target.get("selection_policy_version") != policy_version):
        return None
    selected = candidates.get(selected_id)
    if selected is None:
        raise ValueError("SELECTED_CANDIDATE_NOT_PRESENT")
    for key in ("mask_reference", "crop_reference", "masked_crop_reference"):
        ref = selected.get(key)
        if not isinstance(ref, str) or not ref or Path(ref).is_absolute() or ".." in Path(ref).parts:
            raise ValueError("INVALID_ARTIFACT_REFERENCE")
    return selected

def color_with_semantic_mask(image: Image.Image, mask: np.ndarray, catalog: list[CatalogValue]) -> dict:
    """Reuse exact production color functions with an explicit experimental pixel mask."""
    if mask.shape != (image.height, image.width) or mask.dtype != bool or not mask.any():
        raise ValueError("INVALID_EXPERIMENTAL_MASK")
    preview = image.copy()
    preview.thumbnail((256, 256), Image.Resampling.LANCZOS)
    rgb = np.asarray(preview)
    lab = cv2.cvtColor(rgb.astype(np.float32) / 255, cv2.COLOR_RGB2LAB)
    reduced = np.asarray(Image.fromarray(mask.astype("uint8") * 255).resize(preview.size, Image.Resampling.NEAREST)) > 0
    # Sampling-scale erosion excludes antialias boundaries, same 3x3 core principle as baseline.
    core = cv2.erode(reduced.astype(np.uint8), np.ones((3, 3), np.uint8)).astype(bool)
    if not core.any():
        return {"palette": [], "primary_color": None, "warnings": ["MASK_INTERIOR_TOO_SMALL"],
                "commercial_guard": "unchanged", "target_garment_identity_verified": False}
    _, _, original_segmentation, original_background_warnings = foreground_mask(lab)
    illumination, illumination_warnings = colors._illumination(rgb, core, original_segmentation)
    # This fixed lower bound is a conservative compatibility input, NOT mask accuracy,
    # probability, or a use of the detector/SAM score to unlock commercial IDs.
    fixed_foreground = MIN_FOREGROUND_CONFIDENCE
    palette, warnings = [], list(illumination_warnings)
    groups = colors._clusters(lab[core])
    references = colors._references(catalog)
    for group in groups:
        entry, issues = colors._palette_element(group, references, fixed_foreground, illumination)
        entry["prospective_catalog_id"] = entry["catalog_color_id"]
        entry["prospective_catalog_name"] = entry["catalog_color_name"]
        entry["evidence"]["observed_catalog_match_accepted"] = entry["evidence"]["catalog_match_accepted"]
        entry["evidence"]["observed_catalog_match_reason"] = entry["evidence"]["catalog_match_reason"]
        entry["evidence"]["catalog_match_accepted"] = False
        entry["evidence"]["catalog_match_reason"] = "target_garment_identity_unverified"
        for key in ("catalog_color_id", "catalog_color_name", "catalog_id", "catalog_name"):
            entry[key] = None
        palette.append(entry)
        warnings.extend(issues)
    coverage = min(1.0, sum(group["percentage"] for group in groups))
    reliable = bool(illumination["reliable"] and coverage >= .85)
    properties = colors._properties(lab[core], rgb[core], palette, reliable) if palette else {}
    primary = None
    if palette:
        top = palette[0]
        runner_up = palette[1]["percentage"] if len(palette) > 1 else 0.0
        if top["percentage"] >= .55 and top["percentage"] - runner_up >= .15:
            primary = top
    if primary is None:
        warnings.append("NO_SINGLE_DOMINANT_COLOR")
    return {
        "status": "completed" if palette and reliable else "partial" if palette else "insufficient_evidence",
        "palette": palette, "primary_color": primary, "coverage": coverage,
        "dominant_family": properties.get("dominant_family"),
        "properties": properties, "warnings": list(dict.fromkeys(warnings)),
        "illumination": illumination, "original_background_warnings": original_background_warnings,
        "color_math": "unchanged production Lab/CIEDE2000/clustering/thresholds",
        "experimental_mask_foreground_compatibility_score": fixed_foreground,
        "score_note": "fixed minimum compatibility input; not accuracy or calibrated probability",
        "target_garment_identity_verified": False, "commercial_guard": "unchanged",
        "mask_sampling_interior_pixels": int(core.sum()),
    }


def add_color_proxy(value: dict, row: dict, catalog: list[CatalogValue]) -> dict:
    primary = value.get("primary_color")
    candidate = None
    if primary:
        candidate = primary.get("prospective_catalog_id")
        if candidate is None and primary["evidence"].get("observed_catalog_match_accepted"):
            name = primary["evidence"].get("nearest_catalog_color_name")
            found = [c.id for c in catalog if c.name == name]
            candidate = found[0] if len(found) == 1 else None
    references = {item.id: lab for item, lab, source in colors._references(catalog) if source == "catalog_hex"}
    distances = [float(delta_e_2000(np.array(primary["lab"]), references[r["id_color"]]))
                 for r in row["color_labels"] if primary and r["id_color"] in references]
    dominant = value.get("dominant_family")
    label_family = None
    if len(row["color_labels"]) == 1:
        label = row["color_labels"][0]
        entry = next((c for c in catalog if c.id == label["id_color"]), None)
        if entry and entry.id in references:
            label_family = colors._family(references[entry.id], entry)
    value["proxy"] = {
        "prospective_primary_catalog_id": candidate,
        "primary_to_commercial_swatch_delta_e00": min(distances) if distances else None,
        "observed_family": dominant, "catalog_label_family": label_family,
        "physical_color_accuracy": False, "applicable_commercial_id": None,
    }
    return value


def consumer_metrics(rows, predictions, vectors, observations):
    return {"classification": classification_metrics(rows, predictions),
            "color": color_metrics(rows, observations),
            "retrieval": retrieval_metrics(rows, vectors)}


def run():
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", type=Path, default=Path("/workspace"))
    parser.add_argument("--output", type=Path, default=Path("/workspace/storage/app/ai-segmentation"))
    parser.add_argument("--original-only", action="store_true")
    args = parser.parse_args()
    root, output = args.root.resolve(), args.output.resolve()
    protocol_bytes = (root / "services/mati-ai/scripts/segmentation/protocol.json").read_bytes()
    protocol = json.loads(protocol_bytes)
    frozen = json.loads((output / "protocol-freeze.json").read_text())
    assert hashlib.sha256(protocol_bytes).hexdigest() == frozen["protocol_sha256"]
    manifest_path = root / protocol["manifest_path"]
    assert hashlib.sha256(manifest_path.read_bytes()).hexdigest() == protocol["manifest_sha256"]
    manifest = json.loads(manifest_path.read_text())
    by_id = {r["image_id"]: r for r in manifest["images"]}
    rows = [by_id[i] for i in protocol["image_ids"]]
    assert len(rows) == 130 and len({r["sha256"] for r in rows}) == 130
    categories = [CatalogValue.model_validate(c) for c in manifest["taxonomies"]["categories"]]
    catalog = [CatalogValue.model_validate(c) for c in manifest["taxonomies"]["colors"]]
    settings = Settings(_env_file=None, local_files_only=True, device="cuda",
                        model_revision=protocol["siglip"]["revision"],
                        vision_model=protocol["siglip"]["model_id"])
    manager = ModelManager(settings)
    prompts = category_prompts(categories)
    baseline_rows = json.loads((root / "storage/app/ai-p1/classification_predictions.json").read_text())
    baseline = {r["image_id"]: r for r in baseline_rows}
    baseline_colors = json.loads((root / "storage/app/ai-audit/p1-color-baseline-comparison.json").read_text())
    baseline_color = {r["image_id"]: r["colorimetry"] for r in baseline_colors["rows"]}

    def load_image(row):
        # The frozen paths belong to the host. Resolve only known manifest relatives
        # inside the read-only mounted project; never accept a browser path.
        path = root / "storage/app/public" / row["relative_path"]
        raw = path.read_bytes()
        assert hashlib.sha256(raw).hexdigest() == row["sha256"]
        return validate_image(raw, settings).image

    full_predictions, full_vectors, full_colors, full_details = {}, {}, {}, []
    for i, row in enumerate(rows):
        image = load_image(row)
        result = manager.infer(image, prompts)
        prediction = classify(categories, result["similarities"])
        prior = baseline[row["image_id"]]["response"]["data"]["classification"]
        max_diff = max(abs(a["similarity_score"] - b["similarity_score"])
                       for a, b in zip(prediction["top_k"], prior["top_k"]))
        if [v["category_id"] for v in prediction["top_k"]] != [v["category_id"] for v in prior["top_k"]] or max_diff > 1e-5:
            raise ValueError(f"FROZEN_BASELINE_DRIFT:image_{row['image_id']}")
        full_predictions[row["image_id"]] = prediction
        full_vectors[row["image_id"]] = result["embedding"]
        full_colors[row["image_id"]] = add_color_proxy(baseline_color[row["image_id"]], row, catalog)
        full_details.append({"image_id": row["image_id"], "sha256": row["sha256"],
                             "classification": prediction, "max_baseline_topk_score_difference": max_diff,
                             "model": result["model"], "device": result["device"],
                             "inference_ms": result["metrics"]["inference"],
                             "preprocess_version": protocol["consumer_preprocess_versions"]["full"]})
        image.close()
        if (i + 1) % 20 == 0:
            print(f"full consumers {i + 1}/130", flush=True)
    full_report = consumer_metrics(rows, full_predictions, full_vectors, full_colors)
    full_report["identity"] = {**manager.identity, "preprocess_version": protocol["consumer_preprocess_versions"]["full"],
                               "protocol_sha256": frozen["protocol_sha256"], "original_sha_verified_n": 130}
    full_report["rows"] = full_details
    write_json(output / "consumer-full.json", full_report)
    write_json(output / "color-full.json", full_colors)
    np.savez_compressed(output / "embeddings-full.npz", image_ids=np.array([r["image_id"] for r in rows]),
                        values=np.array([full_vectors[r["image_id"]] for r in rows], dtype=np.float32))
    print("full baseline reproduced", full_report["classification"]["top1"],
          full_report["classification"]["macro_f1"], flush=True)
    if args.original_only:
        return

    all_candidates = {}
    for spec in protocol["candidates"]:
        candidate = spec["id"]
        source = output / candidate / "results.json"
        if not source.exists():
            raise ValueError(f"CANDIDATE_BENCHMARK_MISSING:{candidate}")
        data = json.loads(source.read_text())
        if data["metadata"].get("protocol_sha256") != frozen["protocol_sha256"]:
            raise ValueError("CANDIDATE_PROTOCOL_MISMATCH")
        detections = {r["image_id"]: r for r in data["rows"]}
        assert set(detections) == set(protocol["image_ids"])
        predictions = {"crop": {}, "masked": {}}
        vectors = {"crop": {}, "masked": {}}
        semantic_colors = {}
        diagnostic13 = {"mode": "ASSISTED_DIAGNOSTIC_ONLY", "status": "separate_visual_selection_artifact", "automatic_result_unchanged": True}
        details = []
        for i, row in enumerate(rows):
            detection = detections[row["image_id"]]
            assert detection["sha256"] == row["sha256"]
            selected = automatic_target(detection, protocol["selection_policy_version"])
            process = []
            if selected is not None:
                process.append(("AUTO", selected))
            if not process:
                details.append({"image_id": row["image_id"], "target_status": detection["target_garment"]["status"],
                                "consumer_prediction": "abstention_no_automatic_target"})
                continue
            image = load_image(row)
            for mode, target in process:
                mask_path = safe_artifact(output, target["mask_reference"])
                mask = np.asarray(Image.open(mask_path).convert("L")) > 0
                if mask.shape != (image.height, image.width):
                    raise ValueError("MASK_PHOTO_GEOMETRY_MISMATCH")
                profile_results = {}
                for profile in ("crop", "masked"):
                    reference = target["crop_reference" if profile == "crop" else "masked_crop_reference"]
                    region = Image.open(safe_artifact(output, reference)).convert("RGB")
                    result = manager.infer(region, prompts)
                    prediction = classify(categories, result["similarities"])
                    profile_results[profile] = {"classification": prediction,
                                               "preprocess_version": protocol["consumer_preprocess_versions"][profile],
                                               "inference_ms": result["metrics"]["inference"],
                                               "region_size": list(region.size)}
                    if mode == "AUTO":
                        predictions[profile][row["image_id"]] = prediction
                        vectors[profile][row["image_id"]] = result["embedding"]
                    region.close()
                observation = add_color_proxy(color_with_semantic_mask(image, mask, catalog), row, catalog)
                if mode == "AUTO":
                    semantic_colors[row["image_id"]] = observation
                    details.append({"image_id": row["image_id"], "target_status": "selected",
                                    "target_id": target["id"], "profiles": profile_results})
                else:
                    diagnostic13.update({"status": "evaluated", "selected_candidate_id": target["id"],
                                         "external_class": target["external_class"], "bbox": target["bbox"],
                                         "mask_reference": target["mask_reference"],
                                         "profile_results": profile_results, "color": observation,
                                         "catalog_label_unchanged": row["category_id"],
                                         "selection_note": "user-explicit diagnostic target; not independent human oracle and not AUTO benchmark"})
            image.close()
        selected_rows = [r for r in rows if r["image_id"] in predictions["crop"]]
        report = {"candidate_id": candidate, "n_frozen": 130,
                  "automatic_selected_n": len(selected_rows),
                  "protocol_sha256": frozen["protocol_sha256"],
                  "siglip_identity": manager.identity,
                  "all130": {profile: consumer_metrics(rows, predictions[profile], vectors[profile], semantic_colors)
                             for profile in ("crop", "masked")},
                  "automatic_selected_only": {
                      "n": len(selected_rows),
                      "full_matched_cohort": consumer_metrics(selected_rows, full_predictions, full_vectors, full_colors),
                      **{profile: consumer_metrics(selected_rows, predictions[profile], vectors[profile], semantic_colors)
                         for profile in ("crop", "masked")}},
                  "image13_assisted_diagnostic": diagnostic13, "rows": details}
        write_json(output / candidate / "consumers.json", report)
        write_json(output / candidate / "color-semantic.json", semantic_colors)
        for profile in ("crop", "masked"):
            ids = sorted(vectors[profile])
            np.savez_compressed(output / candidate / f"embeddings-{profile}.npz",
                                image_ids=np.array(ids),
                                values=np.array([vectors[profile][i] for i in ids], dtype=np.float32).reshape(len(ids), 768))
        all_candidates[candidate] = {
            "automatic_selected_n": len(selected_rows),
            "all130": {profile: {k: v for k, v in report["all130"][profile].items()}
                       for profile in ("crop", "masked")},
        }
        print(candidate, "consumers finished AUTO", len(selected_rows), flush=True)
    write_json(output / "consumer-comparison.json", all_candidates)
    manager._model = manager._processor = None
    gc.collect()
    import torch
    torch.cuda.empty_cache()


if __name__ == "__main__":
    run()

