"""Read-only color audit over existing manifest photos, only in-memory perturbations."""
import io
import json
import time
from collections import Counter
from pathlib import Path

import cv2
import numpy as np
from PIL import Image, ImageEnhance, ImageFilter

from mati_ai.contracts.colorimetry import GarmentColorimetry
from mati_ai.contracts.vision import CatalogValue
from mati_ai.core.config import Settings
from mati_ai.compatibility.engine import evaluate_compatibility
from mati_ai.vision.color import extract_colors
from mati_ai.vision.color_distance import delta_e_2000
from mati_ai.vision.validation import validate_image

ROOT = Path(__file__).resolve().parents[4]
OUTPUT = ROOT / "storage/app/ai-audit"
manifest = json.loads((OUTPUT / "evaluation_manifest.json").read_text())
predictions = json.loads((OUTPUT / "classification_predictions.json").read_text())
sources = {row["image_id"]: row for row in manifest["images"]}
catalog = [CatalogValue.model_validate(row) for row in manifest["taxonomies"]["colors"]]
catalog_by_id = {str(row.id): row for row in catalog}
config = Settings(local_files_only=True, device="cpu", _env_file=None)
output = {"ground_truth": "commercial_variant_proxy_not_independently_confirmed_single_color",
          "eligible_images": len(manifest["images"]), "unique_images": manifest["summary"]["unique_sha_images"],
          "excluded_conflicting_sha_image_ids": [2, 5], "baseline": [], "perturbations": []}


def lab_reference(hexvalue):
    channels = [int(hexvalue.lstrip("#")[i:i+2], 16) for i in (0, 2, 4)]
    return cv2.cvtColor(np.array(channels, np.float32).reshape(1, 1, 3) / 255, cv2.COLOR_RGB2LAB)[0, 0]


def description(data, row):
    palette = data["palette"]
    top = palette[0] if palette else None
    primary = data["primary_color"]
    expected_ids = [str(value["id_color"]) for value in row["color_labels"]]
    expected_hex = [value["hex_col"] for value in row["color_labels"] if value.get("hex_col")]
    expected_lab = [lab_reference(value) for value in expected_hex]
    distance = min(float(delta_e_2000(np.array(top["lab"]), ref)) for ref in expected_lab) if top and expected_lab else None
    return {"image_id": row["image_id"], "product_id": row["product_id"], "category": row["category_name"],
        "expected_color_ids": expected_ids, "expected_color_names": [v["nom_col"] for v in row["color_labels"]],
        "status": data["status"], "foreground_confidence": data["foreground_confidence"],
        "palette_size": len(palette), "coverage": data["coverage"],
        "primary_id": primary["catalog_color_id"] if primary else None,
        "top_id": top["catalog_color_id"] if top else None,
        "top_family": top["family"] if top else None,
        "primary_proxy_match": bool(primary and str(primary["catalog_color_id"]) in expected_ids),
        "top_proxy_match": bool(top and str(top["catalog_color_id"]) in expected_ids),
        "any_palette_proxy_match": any(str(s["catalog_color_id"]) in expected_ids for s in palette),
        "top_to_expected_swatch_delta_e_proxy": distance,
        "warnings": data["warnings"], "colorimetry": data}


def perturbations(image):
    probe = image.copy()
    probe.thumbnail((1024, 1024), Image.Resampling.LANCZOS)
    arr = np.asarray(probe).astype(float)
    stream = io.BytesIO()
    probe.save(stream, "JPEG", quality=60)
    return {
        "reference_1024": probe,
        "brightness_065": ImageEnhance.Brightness(probe).enhance(.65),
        "brightness_125": ImageEnhance.Brightness(probe).enhance(1.25),
        "blur_2": probe.filter(ImageFilter.GaussianBlur(2)),
        "jpeg_60": Image.open(io.BytesIO(stream.getvalue())).convert("RGB"),
        "rotation_10": probe.rotate(10, expand=True, fillcolor="white"),
        "warm_cast": Image.fromarray(np.clip(arr * [1.08, 1, .92], 0, 255).astype(np.uint8)),
        "cool_cast": Image.fromarray(np.clip(arr * [.92, 1, 1.08], 0, 255).astype(np.uint8)),
        "small_128": probe.copy().resize((max(16, round(probe.width * 128 / max(probe.size))), max(16, round(probe.height * 128 / max(probe.size))))),
    }


for i, prediction in enumerate(predictions):
    source = sources[prediction["image_id"]]
    data = prediction["response"]["data"]["colorimetry"]
    baseline = description(data, source)
    output["baseline"].append(baseline)
    original = validate_image(Path(source["local_path"]).read_bytes(), config).image
    for name, modified in perturbations(original).items():
        started = time.perf_counter()
        result = extract_colors(modified, catalog)["colorimetry"]
        current = description(result, source)
        first = data["palette"][0] if data["palette"] else None
        second = result["palette"][0] if result["palette"] else None
        output["perturbations"].append({
            "image_id": source["image_id"], "transform": name, "status": current["status"],
            "baseline_status": baseline["status"], "foreground_confidence": current["foreground_confidence"],
            "palette_size": current["palette_size"], "primary_id": current["primary_id"],
            "top_id": current["top_id"], "top_proxy_match": current["top_proxy_match"],
            "primary_proxy_match": current["primary_proxy_match"],
            "any_palette_proxy_match": current["any_palette_proxy_match"],
            "primary_id_equal_to_baseline": bool(data["primary_color"] and result["primary_color"] and data["primary_color"]["catalog_color_id"] == result["primary_color"]["catalog_color_id"]),
            "top_to_baseline_delta_e": float(delta_e_2000(np.array(first["lab"]), np.array(second["lab"]))) if first and second else None,
            "warnings": current["warnings"], "duration_ms": round((time.perf_counter() - started) * 1000, 3),
        })
    original.close()
    if (i + 1) % 20 == 0:
        print(json.dumps({"processed": i + 1, "total": len(predictions)}), flush=True)

rows = output["baseline"]
proxy = [r["top_to_expected_swatch_delta_e_proxy"] for r in rows if r["top_to_expected_swatch_delta_e_proxy"] is not None]
summary = {
    "n_unique_evaluated": len(rows), "status_counts": dict(Counter(r["status"] for r in rows)),
    "nonempty_palette": sum(r["palette_size"] > 0 for r in rows),
    "primary_present": sum(r["colorimetry"]["primary_color"] is not None for r in rows),
    "primary_catalog_id_present": sum(r["primary_id"] is not None for r in rows),
    "top_catalog_id_present": sum(r["top_id"] is not None for r in rows),
    "any_palette_catalog_id_present": sum(any(s["catalog_color_id"] is not None for s in r["colorimetry"]["palette"]) for r in rows),
    "primary_proxy_matches": sum(r["primary_proxy_match"] for r in rows),
    "top_proxy_matches": sum(r["top_proxy_match"] for r in rows),
    "any_palette_proxy_matches": sum(r["any_palette_proxy_match"] for r in rows),
    "top_expected_delta_e_proxy_n": len(proxy),
    "top_expected_delta_e_proxy_median": float(np.median(proxy)) if proxy else None,
    "top_expected_delta_e_proxy_mean": float(np.mean(proxy)) if proxy else None,
    "top_expected_delta_e_proxy_p90": float(np.percentile(proxy, 90)) if proxy else None,
    "warning_counts": dict(Counter(w for r in rows for w in r["warnings"])),
}
summary["per_category"] = {}
for category in sorted(set(r["category"] for r in rows)):
    group = [r for r in rows if r["category"] == category]
    summary["per_category"][category] = {"n": len(group), "status_counts": dict(Counter(r["status"] for r in group)),
        "primary_proxy_matches": sum(r["primary_proxy_match"] for r in group),
        "any_palette_proxy_matches": sum(r["any_palette_proxy_match"] for r in group)}
summary["per_transform"] = {}
for transform in sorted(set(r["transform"] for r in output["perturbations"])):
    group = [r for r in output["perturbations"] if r["transform"] == transform]
    diffs = [r["top_to_baseline_delta_e"] for r in group if r["top_to_baseline_delta_e"] is not None]
    summary["per_transform"][transform] = {"n": len(group), "status_counts": dict(Counter(r["status"] for r in group)),
        "both_palettes_n": len(diffs), "delta_e_vs_baseline_median": float(np.median(diffs)) if diffs else None,
        "delta_e_vs_baseline_p90": float(np.percentile(diffs, 90)) if diffs else None,
        "primary_proxy_matches": sum(r["primary_proxy_match"] for r in group),
        "any_palette_proxy_matches": sum(r["any_palette_proxy_match"] for r in group)}
profiles = [(r["image_id"], GarmentColorimetry.model_validate(r["colorimetry"])) for r in rows[:30]]
compatibility = []
for i, (left_id, left) in enumerate(profiles):
    for right_id, right in profiles[i+1:]:
        result = evaluate_compatibility(left, right)
        reverse = evaluate_compatibility(right, left)
        compatibility.append({"image_ids": [left_id, right_id], "status": result.status,
            "score": result.compatibility_score, "harmonies": result.harmony_types,
            "symmetric": result.compatibility_score == reverse.compatibility_score,
            "warnings": result.warnings})
output["real_palette_compatibility"] = {"n": len(compatibility),
    "status_counts": dict(Counter(r["status"] for r in compatibility)),
    "symmetry_passed": sum(r["symmetric"] for r in compatibility), "rows": compatibility,
    "ground_truth": "no_human_pair_compatibility_judgments_no_accuracy_claim"}
output["summary"] = summary
(OUTPUT / "python-color-real.json").write_text(json.dumps(output, indent=2, ensure_ascii=False) + "\n")
print(json.dumps(summary, indent=2, ensure_ascii=False), flush=True)
