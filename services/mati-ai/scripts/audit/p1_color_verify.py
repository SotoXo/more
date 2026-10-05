"""Compare the P1 color policy with the frozen N130 baseline without ML inference."""
import hashlib
import json
from collections import Counter
from pathlib import Path

from mati_ai.contracts.colorimetry import GarmentColorimetry
from mati_ai.contracts.vision import CatalogValue
from mati_ai.core.config import Settings
from mati_ai.vision.color import extract_colors
from mati_ai.vision.validation import validate_image

root = Path(__file__).resolve().parents[4]
output = root / "storage/app/ai-audit"
manifest = json.loads((output / "evaluation_manifest.json").read_text())
predictions = json.loads((output / "classification_predictions.json").read_text())
sources = {row["image_id"]: row for row in manifest["images"]}
catalog = [CatalogValue.model_validate(row) for row in manifest["taxonomies"]["colors"]]
settings = Settings(local_files_only=True, device="cpu", _env_file=None)
scalar_fields = ["status", "foreground_confidence", "coverage", "temperature", "lightness", "chroma",
                 "saturation", "neutrality", "internal_contrast", "color_variety", "dominant_family", "metrics"]
swatch_fields = ["hex", "rgb", "lab", "percentage", "family", "descriptive_name", "delta_e", "confidence_score"]


def observed_swatch(swatch):
    return {key: swatch[key] for key in swatch_fields} if swatch else None


def observations(profile):
    return {**{key: profile[key] for key in scalar_fields},
            "palette": [observed_swatch(s) for s in profile["palette"]],
            "primary_color": observed_swatch(profile["primary_color"]),
            "secondary_colors": [observed_swatch(s) for s in profile["secondary_colors"]],
            "accent_colors": [observed_swatch(s) for s in profile["accent_colors"]]}


rows = []
for prediction in predictions:
    source = sources[prediction["image_id"]]
    image_path = Path(source["local_path"])
    raw = image_path.read_bytes()
    assert hashlib.sha256(raw).hexdigest() == prediction["sha256"]
    image = validate_image(raw, settings).image
    current = GarmentColorimetry.model_validate(extract_colors(image, catalog)["colorimetry"]).model_dump(mode="json")
    image.close()
    baseline = prediction["response"]["data"]["colorimetry"]
    assert hashlib.sha256(image_path.read_bytes()).hexdigest() == prediction["sha256"]
    changed = [key for key in observations(current) if observations(current)[key] != observations(baseline)[key]]
    rows.append({"image_id": source["image_id"], "sha256": prediction["sha256"],
                 "technical_status": current["status"], "observations_unchanged": not changed,
                 "changed_observation_fields": changed, "palette_size": len(current["palette"]),
                 "commercial_color_status": current["commercial_color_status"],
                 "applicable_id_count": sum(s["catalog_color_id"] is not None for s in current["palette"]),
                 "original_hash_unchanged": True, "colorimetry": current})
report = {"workload": "CPU deterministic color only; no classification or model inference",
          "manifest_sha256": hashlib.sha256((output / "evaluation_manifest.json").read_bytes()).hexdigest(),
          "n": len(rows), "same_n130_no_new_exclusions": len(rows) == 130,
          "observations_unchanged": sum(row["observations_unchanged"] for row in rows),
          "technical_status_counts": dict(Counter(row["technical_status"] for row in rows)),
          "nonempty_palette": sum(row["palette_size"] > 0 for row in rows),
          "commercial_insufficient": sum(row["commercial_color_status"] == "insufficient_evidence" for row in rows),
          "images_with_applicable_color_id": sum(row["applicable_id_count"] > 0 for row in rows),
          "original_hashes_unchanged": sum(row["original_hash_unchanged"] for row in rows), "rows": rows}
(output / "p1-color-baseline-comparison.json").write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n")
print(json.dumps({key: value for key, value in report.items() if key != "rows"}, ensure_ascii=False, indent=2))
assert report["observations_unchanged"] == report["n"]
assert report["images_with_applicable_color_id"] == 0
