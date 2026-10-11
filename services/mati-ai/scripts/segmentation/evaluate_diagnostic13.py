"""One case only: independently declared Codex visual target, outside AUTO N130."""
from __future__ import annotations
import hashlib
import json
from pathlib import Path
import numpy as np
from PIL import Image
from evaluate_consumers import safe_artifact, color_with_semantic_mask, add_color_proxy, write_json
from mati_ai.contracts.vision import CatalogValue
from mati_ai.core.config import Settings
from mati_ai.core.model_manager import ModelManager
from mati_ai.vision.classification import category_prompts, classify
from mati_ai.vision.validation import validate_image
from policy import build_mask_evidence, SelectionContext, evaluate_candidates

ROOT = Path("/workspace")
BASE = ROOT / "storage/app/ai-segmentation"


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main():
    selected = json.loads((BASE/"image13-assisted-selection.json").read_text())
    assert selected["selection_mode"] == "ASSISTED_CODEX_VISUAL"
    assert selected["annotator"] == "Codex_visual_inspection_not_human"
    assert selected["image_id"] == 13 and selected["include_in_aggregate_metrics"] is False
    assert selected["commercial_authorization"] is False
    protocol = json.loads((ROOT/"services/mati-ai/scripts/segmentation/protocol.json").read_text())
    frozen = json.loads((BASE/"protocol-freeze.json").read_text())
    assert digest(ROOT/"services/mati-ai/scripts/segmentation/protocol.json") == frozen["protocol_sha256"]
    result_path = BASE / selected["pipeline_id"] / "results.json"
    assert digest(result_path) == selected["source_results_sha256"]
    source = json.loads(result_path.read_text())
    detection = next(r for r in source["rows"] if r["image_id"] == 13)
    candidates = [c for c in detection["candidates"] if c["id"] == selected["candidate_id"]]
    assert len(candidates) == 1
    candidate = candidates[0]
    assert selected["selection_evidence"] == detection["selection"]["evidence"]
    assert selected["source_image_sha256"] == detection["sha256"]
    assert detection["target_garment"]["status"] == "ambiguous"
    assert detection["selected_candidate_id"] is None
    context = SelectionContext(**selected["selection_evidence"]["context"])
    checked = evaluate_candidates([candidate], (detection["image_width"], detection["image_height"]), context=context)
    assert len(checked["candidates"]) == 1
    assert checked["candidates"][0]["id"] == selected["candidate_id"]

    manifest_path = ROOT / protocol["manifest_path"]
    assert digest(manifest_path) == protocol["manifest_sha256"]
    manifest = json.loads(manifest_path.read_text())
    row = next(r for r in manifest["images"] if r["image_id"] == 13)
    path = ROOT / "storage/app/public" / row["relative_path"]
    assert digest(path) == selected["source_image_sha256"] == row["sha256"]
    mask_path = safe_artifact(BASE, candidate["mask_reference"])
    assert digest(mask_path) == selected["mask_png_sha256"] == candidate["mask_png_sha256"]
    mask = np.asarray(Image.open(mask_path).convert("L")) > 0
    assert build_mask_evidence(mask)["sha256"] == selected["mask_evidence_sha256"]
    settings = Settings(_env_file=None, local_files_only=True, device="cuda",
                        model_revision=protocol["siglip"]["revision"], vision_model=protocol["siglip"]["model_id"])
    original = validate_image(path.read_bytes(), settings).image
    assert mask.shape == (original.height, original.width)
    categories = [CatalogValue.model_validate(c) for c in manifest["taxonomies"]["categories"]]
    catalog = [CatalogValue.model_validate(c) for c in manifest["taxonomies"]["colors"]]
    prompts = category_prompts(categories)
    manager = ModelManager(settings)
    predictions = {}
    for profile, image in [("full", original)]:
        result = manager.infer(image, prompts)
        predictions[profile] = {"classification": classify(categories, result["similarities"]),
                               "model": result["model"], "preprocess_version": protocol["consumer_preprocess_versions"][profile],
                               "device": result["device"]}
    for profile, reference_key, hash_key in [("crop","crop_reference","crop_sha256"),("masked","masked_crop_reference","masked_crop_sha256")]:
        artifact = safe_artifact(BASE, candidate[reference_key])
        assert digest(artifact) == candidate[hash_key] == selected[hash_key]
        image = Image.open(artifact).convert("RGB")
        result = manager.infer(image, prompts)
        predictions[profile] = {"classification": classify(categories, result["similarities"]),
                               "model": result["model"], "preprocess_version": protocol["consumer_preprocess_versions"][profile],
                               "device": result["device"]}
        image.close()
    full_colors = json.loads((BASE/"color-full.json").read_text())["13"]
    mask_color = add_color_proxy(color_with_semantic_mask(original,mask,catalog),row,catalog)
    original.close()
    report = {
        "schema_version":"mati-case13-assisted-consumers-1", "image_id":13, "n":1,
        "mode":"ASSISTED_CODEX_VISUAL_DIAGNOSTIC", "not_auto":True, "not_human_oracle":True,
        "not_in_any_aggregate":True, "selection":selected,
        "source_selection_sha256":digest(BASE/"image13-assisted-selection.json"),
        "protocol_sha256":frozen["protocol_sha256"],
        "auto_unchanged":{"status":"ambiguous","selected_candidate_id":None},
        "target_verification":{"valid_mask_geometry":True,"codex_visual_confirmation":True,
                               "human_verified":False,"commercial_authorization":False},
        "category_ground_truth_unchanged":row["category_id"], "category_name_unchanged":row["category_name"],
        "classification":predictions,"color_before_full":full_colors,"color_after_semantic_mask":mask_color,
        "retrieval":"not evaluated on single diagnostic","generalization_claim":False,
    }
    write_json(BASE/"image13-assisted-consumers.json",report)
    print(json.dumps({"status":"diagnostic_completed","image_id":13,
                      "full_top1":predictions["full"]["classification"]["top_k"][0],
                      "masked_top1":predictions["masked"]["classification"]["top_k"][0],
                      "before_primary":full_colors["primary_color"],"after_primary":mask_color["primary_color"],
                      "before_proxy":full_colors["proxy"],"after_proxy":mask_color["proxy"],
                      "commercial_ids_authorized":False},indent=2))


if __name__ == "__main__":
    main()

