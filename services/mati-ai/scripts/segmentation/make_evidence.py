"""Private scientific evidence: original/boxes, individual masks and candidate crops."""
from __future__ import annotations
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import textwrap
from PIL import Image, ImageDraw

COLORS = ["#e6194b", "#3cb44b", "#4363d8", "#f58231", "#911eb4", "#42d4f4", "#f032e6", "#bfef45", "#fabed4", "#469990", "#dcbeff", "#9a6324"]
W, H = 260, 350


def paste_panel(canvas, image, x, y):
    preview = image.copy().convert("RGB")
    preview.thumbnail((W - 12, H - 42))
    canvas.paste(preview, (x + (W - preview.width) // 2, y + 30 + (H - 42 - preview.height) // 2))


def image_ref(base, reference):
    if not reference:
        return None
    path = (base / reference).resolve()
    if not path.is_relative_to(base.resolve()) or not path.is_file():
        raise ValueError("INVALID_PRIVATE_EVIDENCE_PATH")
    return Image.open(path)


def draw_box(image, candidate, color):
    copy = image.copy()
    draw = ImageDraw.Draw(copy)
    box = candidate.get("box_xyxy")
    if box:
        thickness = max(3, round(max(image.size) / 250))
        draw.rectangle(tuple(box), outline=color, width=thickness)
    return copy


def text_panel(canvas, x, y, text):
    draw = ImageDraw.Draw(canvas)
    for i, line in enumerate(textwrap.wrap(text, width=38)):
        if i >= 18:
            break
        draw.text((x + 8, y + 32 + i * 15), line, fill="#111")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", type=Path, default=Path.cwd())
    parser.add_argument("--copy-to", type=Path)
    args = parser.parse_args()
    root = args.root.resolve()
    base = root / "storage/app/ai-segmentation"
    sample = json.loads((base / "visual-sample-freeze.json").read_text())
    manifest = json.loads((root / "DOCUMENTACION/IA/AUDITORIA_FINAL/evaluation_manifest.json").read_text())
    sources = {r["image_id"]: r for r in manifest["images"]}
    protocol = json.loads((root / "services/mati-ai/scripts/segmentation/protocol.json").read_text())
    evidence = []
    for spec in protocol["candidates"]:
        result_path = base / spec["id"] / "results.json"
        if not result_path.exists():
            continue
        results = json.loads(result_path.read_text())
        detections = {r["image_id"]: r for r in results["rows"]}
        dest = base / "visual-evidence" / spec["id"]
        dest.mkdir(mode=0o700, parents=True, exist_ok=True)
        for image_id in sample["image_ids"]:
            row = detections[image_id]
            original_path = root / "storage/app/public" / sources[image_id]["relative_path"]
            raw = original_path.read_bytes()
            assert hashlib.sha256(raw).hexdigest() == sources[image_id]["sha256"]
            from mati_ai.core.config import Settings
            from mati_ai.vision.validation import validate_image
            original = validate_image(raw, Settings(_env_file=None)).image
            candidates = row["candidates"]
            shown = candidates[:protocol["max_candidates_per_image"]]
            canvas = Image.new("RGB", (4 * W, (1 + len(shown)) * H), "#eeeeee")
            draw = ImageDraw.Draw(canvas)
            overview = original.copy()
            for i, candidate in enumerate(shown):
                overview = draw_box(overview, candidate, COLORS[i % len(COLORS)])
            paste_panel(canvas, original, 0, 0)
            paste_panel(canvas, overview, W, 0)
            draw.text((8, 8), f"Original: image_id={image_id}", fill="#111")
            draw.text((W+8, 8), f"All shown boxes: {len(shown)}/{len(candidates)}", fill="#111")
            draw.text((2*W+8, 8), "Target selection (AUTO)", fill="#111")
            draw.text((3*W+8, 8), "Evidence limitations", fill="#111")
            text_panel(canvas, 2*W, 0,
                       f"{spec['id']} status={row['target_garment']['status']}; selected={row['selected_candidate_id']}; reason={row.get('selection',{}).get('reason')}; classes: " +
                       "; ".join(str(c.get("raw_label", c.get("external_class"))) for c in candidates))
            text_panel(canvas, 3*W, 0,
                       "Candidate masks/crops below are not automatically chosen targets. Scores are not accuracy or calibrated probability. No human mask ground truth. Originals unmodified. Commercial IDs remain blocked. Unknown/mixed classes and invalid geometry may have boxes without masks.")
            for i, candidate in enumerate(shown):
                y = (i + 1) * H
                color = COLORS[i % len(COLORS)]
                draw.text((8, y+8), f"C{i}: {candidate.get('raw_label',candidate['external_class'])[:28]}", fill=color)
                draw.text((W+8, y+8), "Individual binary mask", fill="#111")
                draw.text((2*W+8, y+8), "Candidate tight-mask crop", fill="#111")
                draw.text((3*W+8, y+8), "Candidate masked crop (gray127)", fill="#111")
                paste_panel(canvas, draw_box(original, candidate, color), 0, y)
                mask = image_ref(base, candidate.get("mask_reference"))
                crop = image_ref(base, candidate.get("crop_reference"))
                masked = image_ref(base, candidate.get("masked_crop_reference"))
                for column, image in enumerate((mask, crop, masked), 1):
                    if image is None:
                        text_panel(canvas, column*W, y, "No accepted mask/crop. Refer to rejected_candidates and raw detector phrase in results.json.")
                    else:
                        paste_panel(canvas, image, column*W, y)
                        image.close()
                draw.text((8, y+H-16), f"{candidate['id']} score={candidate['score']:.3f}", fill="#111")
            path = dest / f"image-{image_id:03}.jpg"
            canvas.save(path, quality=90)
            os.chmod(path, 0o600)
            record = {"candidate": spec["id"], "image_id": image_id,
                      "relative_path": path.relative_to(base).as_posix(),
                      "sha256": hashlib.sha256(path.read_bytes()).hexdigest(),
                      "width": canvas.width, "height": canvas.height, "candidate_count": len(candidates)}
            if args.copy_to:
                copy_dest = args.copy_to / spec["id"]
                copy_dest.mkdir(parents=True, exist_ok=True)
                shutil.copy2(path, copy_dest/path.name)
            evidence.append(record)
            original.close()
        print(spec["id"], "visual evidence", len(sample["image_ids"]), flush=True)
    path = base / "visual-evidence-manifest.json"
    path.write_text(json.dumps({"sample": sample["image_ids"], "source_sample_sha256": hashlib.sha256((base/"visual-sample-freeze.json").read_bytes()).hexdigest(),
                                "not_human_mask_ground_truth": True, "evidence": evidence}, indent=2)+"\n")
    os.chmod(path, 0o600)


if __name__ == "__main__":
    main()

