#!/usr/bin/env python3
"""Offline, isolated target-mask benchmark. No production API or database writes."""
from __future__ import annotations

import argparse
import gc
import hashlib
import json
import math
import os
import random
import re
import sys
import time
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

MIB = 1024 ** 2
MODEL_IDS = {
    "IDEA-Research/grounding-dino-tiny", "IDEA-Research/grounding-dino-base",
    "facebook/sam2.1-hiera-tiny", "facebook/sam-vit-base",
}
PAIR_IDS = {
    "dino_tiny_sam2_tiny", "dino_base_sam2_tiny", "dino_tiny_sam_vit_b",
}


class BenchmarkFailure(RuntimeError):
    def __init__(self, code: str):
        super().__init__(code)
        self.code = code


def sha_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(MIB), b""):
            digest.update(chunk)
    return digest.hexdigest()


def private_dir(path: Path) -> None:
    path.mkdir(parents=True, exist_ok=True, mode=0o700)
    path.chmod(0o700)


def write_json(path: Path, value: Any) -> None:
    with path.open("x", encoding="utf-8") as stream:
        json.dump(value, stream, indent=2, ensure_ascii=False, allow_nan=False)
        stream.write("\n")
    path.chmod(0o600)


def bounded(path: Path, root: Path) -> Path:
    path = path.resolve()
    path.relative_to(root.resolve())
    return path


def memory(torch: Any, baseline_reserved: int) -> dict:
    free, total = torch.cuda.mem_get_info()
    return {
        "allocated_mib": torch.cuda.memory_allocated() / MIB,
        "reserved_mib": torch.cuda.memory_reserved() / MIB,
        "peak_allocated_mib": torch.cuda.max_memory_allocated() / MIB,
        "peak_reserved_mib": torch.cuda.max_memory_reserved() / MIB,
        "incremental_peak_reserved_mib": max(
            0, torch.cuda.max_memory_reserved() - baseline_reserved
        ) / MIB,
        "device_free_mib": free / MIB, "device_total_mib": total / MIB,
    }


def resource_guard(torch: Any, protocol: dict, baseline_reserved: int,
                   minimum_free_mib: int | None = None) -> dict:
    torch.cuda.synchronize()
    measured = memory(torch, baseline_reserved)
    resource = protocol["gate"]["resource"]
    floor = (minimum_free_mib if minimum_free_mib is not None
             else resource["min_free_device_mib"])
    if measured["device_free_mib"] < floor:
        raise BenchmarkFailure("INSUFFICIENT_DEVICE_MEMORY")
    if measured["incremental_peak_reserved_mib"] > resource["peak_incremental_reserved_mib_max"]:
        raise BenchmarkFailure("RESOURCE_BUDGET_EXCEEDED")
    return measured


def failed_row(item: dict, candidate: dict, protocol_sha: str, code: str,
               elapsed_ms: float = 0.0, memory_info: dict | None = None,
               error_type: str | None = None) -> dict:
    from policy import SelectionContext, evaluate_candidates

    context = SelectionContext(
        request_id=f"{candidate['id']}-{item['image_id']}-{protocol_sha[:12]}",
        image_sha256=item["sha256"], owner_id="isolated-benchmark",
    )
    selection = evaluate_candidates(
        [], (1, 1), context=context, model_available=False, error_code=code,
    )
    return {
        "image_id": item["image_id"], "sha256": item["sha256"],
        "status": selection["target_garment"]["status"],
        "image_width": None, "image_height": None, "candidates": [],
        "target_garment": selection["target_garment"],
        "selected_candidate_id": None, "selection": selection,
        "timing_ms": {"total": elapsed_ms}, "memory": memory_info,
        "error": {"code": code, "type": error_type},
    }


def label_normalized(label: str) -> str:
    # Spelling aliases only. Unknown mixed phrases are retained and rejected by policy.
    from policy import LABEL_ALIASES
    label = " ".join(label.strip().lower().strip(".").split())
    return LABEL_ALIASES.get(label, label)


def image_input(item: dict, workspace: Path, settings: Any) -> Any:
    from mati_ai.vision.validation import validate_image
    relative = item.get("relative_path", item.get("image_path"))
    if not isinstance(relative, str) or Path(relative).is_absolute():
        raise BenchmarkFailure("INVALID_IMAGE_PATH")
    path = bounded(workspace / "storage/app/public" / relative,
                   workspace / "storage/app/public")
    raw = path.read_bytes()
    if hashlib.sha256(raw).hexdigest() != item["sha256"]:
        raise BenchmarkFailure("IMAGE_SHA_MISMATCH")
    validated = validate_image(raw, settings)
    if validated.metadata["sha256"] != item["sha256"]:
        raise BenchmarkFailure("IMAGE_SHA_MISMATCH")
    return validated


def gpu_inputs(batch: Any) -> dict:
    return {key: value.to("cuda") for key, value in batch.items()
            if hasattr(value, "to")}


def detect(image: Any, processor: Any, model: Any, torch: Any,
           protocol: dict, baseline_reserved: int) -> tuple[list[dict], float, dict]:
    batch = processor(images=image, text=protocol["text_prompt"], return_tensors="pt")
    inputs = gpu_inputs(batch)
    before = resource_guard(torch, protocol, baseline_reserved)
    started = time.perf_counter()
    with torch.inference_mode(), torch.autocast("cuda", dtype=torch.bfloat16):
        outputs = model(**inputs)
    torch.cuda.synchronize()
    forward_ms = (time.perf_counter() - started) * 1000
    resource_guard(torch, protocol, baseline_reserved)
    processed = processor.post_process_grounded_object_detection(
        outputs, input_ids=inputs["input_ids"], threshold=protocol["box_threshold"],
        text_threshold=protocol["text_threshold"],
        target_sizes=[(image.height, image.width)],
    )[0]
    boxes = processed["boxes"].detach().float().cpu().tolist()
    scores = processed["scores"].detach().float().cpu().tolist()
    labels = processed["text_labels"]
    if len(boxes) != len(scores) or len(boxes) != len(labels):
        raise BenchmarkFailure("MALFORMED_DETECTOR_OUTPUT")
    detected = [
        {"label": label_normalized(str(label)), "raw_label": str(label),
         "score": float(score), "box_model_xyxy": list(map(float, box))}
        for box, score, label in zip(boxes, scores, labels)
    ]
    if any(not math.isfinite(item["score"]) or not 0 <= item["score"] <= 1
           or any(not math.isfinite(v) for v in item["box_model_xyxy"])
           for item in detected):
        raise BenchmarkFailure("MALFORMED_DETECTOR_OUTPUT")
    del outputs, processed, inputs, batch
    return detected, forward_ms, before


def segment(image: Any, boxes: list[list[float]], processor: Any, model: Any,
            family: str, torch: Any, protocol: dict, baseline_reserved: int) -> tuple[Any, Any, float, dict]:
    batch = processor(images=image, input_boxes=[boxes], return_tensors="pt")
    original_sizes = batch["original_sizes"].cpu()
    reshaped_sizes = batch.get("reshaped_input_sizes")
    if reshaped_sizes is not None:
        reshaped_sizes = reshaped_sizes.cpu()
    inputs = gpu_inputs(batch)
    # Original/reshaped sizes are postprocessing metadata, not model arguments.
    inputs.pop("original_sizes", None)
    inputs.pop("reshaped_input_sizes", None)
    before = resource_guard(torch, protocol, baseline_reserved)
    started = time.perf_counter()
    with torch.inference_mode(), torch.autocast("cuda", dtype=torch.bfloat16):
        outputs = model(**inputs, multimask_output=False)
    torch.cuda.synchronize()
    forward_ms = (time.perf_counter() - started) * 1000
    resource_guard(torch, protocol, baseline_reserved)
    raw_masks = outputs.pred_masks.detach().float().cpu()
    qualities = outputs.iou_scores.detach().float().cpu().reshape(-1).tolist()
    if (not bool(torch.isfinite(raw_masks).all()) or len(qualities) != len(boxes)
            or any(not math.isfinite(value) for value in qualities)):
        raise BenchmarkFailure("MALFORMED_SEGMENTER_OUTPUT")
    if family == "sam2":
        masks = processor.post_process_masks(raw_masks, original_sizes)[0]
    else:
        if reshaped_sizes is None:
            raise BenchmarkFailure("MISSING_SAM_RESHAPED_SIZE")
        masks = processor.post_process_masks(
            raw_masks, original_sizes, reshaped_sizes
        )[0]
    # One mask per box is a frozen operational choice, not highest-score selection.
    if masks.shape[0] != len(boxes) or masks.shape[1] != 1:
        raise BenchmarkFailure("MALFORMED_SEGMENTER_OUTPUT")
    masks = masks[:, 0].cpu().numpy().astype("bool")
    del outputs, inputs, batch, raw_masks
    return masks, qualities, forward_ms, before


def save_mask(image: Any, mask: Any, destination: Path, output: Path) -> dict:
    import numpy as np
    from PIL import Image
    from policy import build_mask_evidence

    evidence = build_mask_evidence(mask)
    ys, xs = np.nonzero(mask)
    if not len(xs):
        return {"mask_evidence": evidence, "mask_reference": None,
                "crop_reference": None, "masked_crop_reference": None,
                "bbox": None, "area_ratio": 0.0,
                "border_touch": evidence["border_sides_touched"]}
    x0, y0, x1, y1 = int(xs.min()), int(ys.min()), int(xs.max()) + 1, int(ys.max()) + 1
    private_dir(destination)
    mask_path, crop_path, masked_path = (
        destination / "mask.png", destination / "crop.png", destination / "masked-crop.png"
    )
    Image.fromarray(mask.astype("uint8") * 255).save(mask_path)
    image.crop((x0, y0, x1, y1)).save(crop_path)
    array = np.array(image, dtype="uint8", copy=True)
    array[~mask] = (127, 127, 127)
    Image.fromarray(array).crop((x0, y0, x1, y1)).save(masked_path)
    for path in (mask_path, crop_path, masked_path):
        path.chmod(0o600)
    return {
        "mask_evidence": evidence,
        "mask_reference": mask_path.relative_to(output).as_posix(),
        "mask_png_sha256": sha_file(mask_path),
        "crop_reference": crop_path.relative_to(output).as_posix(),
        "crop_sha256": sha_file(crop_path),
        "masked_crop_reference": masked_path.relative_to(output).as_posix(),
        "masked_crop_sha256": sha_file(masked_path),
        "bbox": {"x": x0, "y": y0, "width": x1-x0, "height": y1-y0},
        "area_ratio": evidence["area_pixels"] / (image.width * image.height),
        "border_touch": evidence["border_sides_touched"],
    }


def process_image(item: dict, candidate: dict, protocol: dict, protocol_sha: str,
                  workspace: Path, output: Path, pair_dir: Path, settings: Any,
                  detector_processor: Any, detector: Any,
                  segmenter_processor: Any, segmenter: Any,
                  torch: Any, baseline_reserved: int, forward_state: dict) -> dict:
    import numpy as np
    from PIL import Image
    from policy import ROLES, SelectionContext, evaluate_candidates

    started = time.perf_counter()
    validated = image_input(item, workspace, settings)
    image = validated.image
    validate_ms = (time.perf_counter() - started) * 1000
    model_image = image.copy()
    model_image.thumbnail((protocol["max_dimension"], protocol["max_dimension"]),
                          Image.Resampling.LANCZOS)
    first_detector = forward_state["detector"] == 0
    detected, detector_ms, detector_memory = detect(
        model_image, detector_processor, detector, torch, protocol, baseline_reserved
    )
    forward_state["detector"] += 1
    scale_x, scale_y = image.width / model_image.width, image.height / model_image.height
    detections = []
    boxes, mask_indices = [], []
    for index, raw in enumerate(detected):
        bx = raw["box_model_xyxy"]
        full_box = [bx[0]*scale_x, bx[1]*scale_y, bx[2]*scale_x, bx[3]*scale_y]
        entry = {
            "id": f"image-{item['image_id']}-candidate-{index}",
            "external_class": raw["label"], "label": raw["label"],
            "raw_label": raw["raw_label"], "score": raw["score"],
            "score_kind": "detector_score_uncalibrated",
            "box_xyxy": full_box, "bbox": None,
            "mask_reference": None, "crop_reference": None,
            "masked_crop_reference": None, "mask_evidence": None,
            "area_ratio": None, "border_touch": [],
        }
        if raw["label"] in ROLES:
            entry["role"] = ROLES[raw["label"]]
        detections.append(entry)
        # Do not make an invalid box valid by clipping it to the image.
        valid_box = all(math.isfinite(v) for v in bx) and (
            0 <= bx[0] < bx[2] <= model_image.width
            and 0 <= bx[1] < bx[3] <= model_image.height
        )
        if raw["label"] in protocol["class_whitelist"] and valid_box:
            boxes.append(bx)
            mask_indices.append(index)
    overflow = len(detections) > protocol["max_candidates_per_image"]
    segmenter_ms = 0.0
    segmenter_memory = None
    first_segmenter = bool(boxes and not overflow and forward_state["segmenter"] == 0)
    if boxes and not overflow:
        masks, qualities, segmenter_ms, segmenter_memory = segment(
            model_image, boxes, segmenter_processor, segmenter, candidate["family"],
            torch, protocol, baseline_reserved,
        )
        forward_state["segmenter"] += 1
        for mask_number, raw_index in enumerate(mask_indices):
            small_mask = masks[mask_number]
            mask = np.asarray(Image.fromarray(small_mask.astype("uint8")).resize(
                image.size, Image.Resampling.NEAREST
            ), dtype="uint8").astype("bool")
            entry = detections[raw_index]
            entry.update(save_mask(
                image, mask, pair_dir / f"image-{item['image_id']}" / entry["id"], output
            ))
            entry["segmenter_quality_score"] = float(qualities[mask_number])
            entry["segmenter_quality_is_probability"] = False
        del masks
    context = SelectionContext(
        request_id=f"{candidate['id']}-{item['image_id']}-{protocol_sha[:12]}",
        image_sha256=item["sha256"], owner_id="isolated-benchmark",
    )
    selection = evaluate_candidates(
        detections, image.size, context=context, truncated=overflow
    )
    end_memory = resource_guard(torch, protocol, baseline_reserved)
    row = {
        "image_id": item["image_id"], "sha256": item["sha256"],
        "pixel_sha256": validated.metadata["pixel_sha256"],
        "image_width": image.width, "image_height": image.height,
        "model_input_width": model_image.width, "model_input_height": model_image.height,
        "status": selection["target_garment"]["status"], "candidates": detections,
        "target_garment": selection["target_garment"],
        "selected_candidate_id": selection["target_garment"]["selected_candidate_id"],
        "selection": selection, "error": None,
        "cold_forward": first_detector or first_segmenter,
        "first_detector_forward": first_detector, "first_segmenter_forward": first_segmenter,
        "timing_ms": {
            "validation": validate_ms, "detector_forward": detector_ms,
            "segmenter_forward": segmenter_ms,
            "total": (time.perf_counter()-started)*1000,
        },
        "memory": {"before_detector": detector_memory,
                   "before_segmenter": segmenter_memory, "after_pipeline": end_memory},
    }
    return row


def verify_lock(lock: dict, protocol_sha: str) -> None:
    if lock.get("schema_version") != "mati-segmentation-model-lock-1":
        raise BenchmarkFailure("INVALID_MODEL_LOCK")
    if lock.get("protocol_sha256") != protocol_sha or set(lock.get("models", {})) != MODEL_IDS:
        raise BenchmarkFailure("MODEL_LOCK_PROTOCOL_MISMATCH")
    for model_id, record in lock["models"].items():
        if record.get("model_id") != model_id or record.get("license") != "apache-2.0":
            raise BenchmarkFailure("MODEL_LICENSE_GATE_FAILED")
        if record.get("gated") or not re.fullmatch(r"[a-f0-9]{40}", record.get("revision", "")):
            raise BenchmarkFailure("MODEL_REVISION_GATE_FAILED")
        if record.get("trust_remote_code") is not False or record.get("use_safetensors") is not True:
            raise BenchmarkFailure("UNSAFE_MODEL_LOCK")
        local = Path(record["local_snapshot"]).resolve(strict=True)
        if sha_file(local / "model.safetensors") != record["weights_sha256"]:
            raise BenchmarkFailure("WEIGHTS_SHA_MISMATCH")
        for file in record["files"]:
            relative = Path(file["path"])
            if relative.is_absolute() or ".." in relative.parts:
                raise BenchmarkFailure("INVALID_SNAPSHOT_FILE")
            path = local / relative
            if not path.is_file() or path.stat().st_size != file["bytes"] or sha_file(path) != file["sha256"]:
                raise BenchmarkFailure("SNAPSHOT_FILE_SHA_MISMATCH")


def load_pair(candidate: dict, lock: dict) -> tuple[Any, Any, Any, Any]:
    from transformers import (
        AutoProcessor,
        GroundingDinoConfig,
        GroundingDinoForObjectDetection,
        Sam2Model,
        Sam2Processor,
        SamModel,
        SamProcessor,
    )
    dino_path = lock["models"][candidate["detector"]]["local_snapshot"]
    sam_path = lock["models"][candidate["segmenter"]]["local_snapshot"]
    safe = {"local_files_only": True, "trust_remote_code": False}
    detector_processor = AutoProcessor.from_pretrained(dino_path, **safe)
    detector_config = GroundingDinoConfig.from_pretrained(dino_path, **safe)
    detector_config.disable_custom_kernels = True
    detector = GroundingDinoForObjectDetection.from_pretrained(
        dino_path, config=detector_config, use_safetensors=True, **safe
    ).eval().to("cuda")
    if candidate["family"] == "sam2":
        segmenter_processor = Sam2Processor.from_pretrained(sam_path, **safe)
        segmenter_class = Sam2Model
    else:
        segmenter_processor = SamProcessor.from_pretrained(sam_path, **safe)
        segmenter_class = SamModel
    segmenter = segmenter_class.from_pretrained(
        sam_path, use_safetensors=True, **safe
    ).eval().to("cuda")
    return detector_processor, detector, segmenter_processor, segmenter


def run_pair(candidate: dict, items: list[dict], protocol: dict, protocol_sha: str,
             lock: dict, lock_sha: str, workspace: Path, output: Path,
             settings: Any, torch: Any) -> dict:
    from importlib.metadata import version

    import numpy as np

    pair_dir = output / candidate["id"]
    private_dir(pair_dir)
    if (pair_dir / "results.json").exists() or (pair_dir / "rows.jsonl").exists():
        raise BenchmarkFailure("CANDIDATE_OUTPUT_ALREADY_EXISTS")
    torch.cuda.empty_cache()
    torch.cuda.reset_peak_memory_stats()
    baseline_reserved = torch.cuda.memory_reserved()
    pair = None
    abort_code = None
    load_ms = None
    error_type = None
    cold_before = memory(torch, baseline_reserved)
    try:
        resource_guard(torch, protocol, baseline_reserved, minimum_free_mib=2048)
        started = time.perf_counter()
        pair = load_pair(candidate, lock)
        torch.cuda.synchronize()
        load_ms = (time.perf_counter()-started)*1000
        resource_guard(torch, protocol, baseline_reserved)
    except torch.cuda.OutOfMemoryError as exc:
        abort_code, error_type = "CUDA_OOM", type(exc).__name__
    except BenchmarkFailure as exc:
        abort_code, error_type = exc.code, type(exc).__name__
    except Exception as exc:  # noqa: BLE001 - emit explicit failure evidence, never success
        abort_code, error_type = "MODEL_UNAVAILABLE", type(exc).__name__
    rows = []
    forward_state = {"detector": 0, "segmenter": 0}
    raw_path = pair_dir / "rows.jsonl"
    with raw_path.open("x", encoding="utf-8") as raw_stream:
        raw_path.chmod(0o600)
        for item in items:
            started = time.perf_counter()
            if abort_code:
                row = failed_row(item, candidate, protocol_sha, abort_code,
                                 memory_info=memory(torch, baseline_reserved),
                                 error_type=error_type)
            else:
                try:
                    row = process_image(
                        item, candidate, protocol, protocol_sha, workspace, output,
                        pair_dir, settings, *pair, torch, baseline_reserved, forward_state,
                    )
                except torch.cuda.OutOfMemoryError as exc:
                    abort_code, error_type = "CUDA_OOM", type(exc).__name__
                    row = failed_row(item, candidate, protocol_sha, abort_code,
                                     (time.perf_counter()-started)*1000,
                                     memory(torch, baseline_reserved), error_type)
                except BenchmarkFailure as exc:
                    error_type = type(exc).__name__
                    if exc.code in {"RESOURCE_BUDGET_EXCEEDED", "INSUFFICIENT_DEVICE_MEMORY"}:
                        abort_code = exc.code
                    row = failed_row(item, candidate, protocol_sha, exc.code,
                                     (time.perf_counter()-started)*1000,
                                     memory(torch, baseline_reserved), error_type)
                except Exception as exc:  # noqa: BLE001 - emit explicit failure evidence, never success
                    row = failed_row(item, candidate, protocol_sha, "INFERENCE_ERROR",
                                     (time.perf_counter()-started)*1000,
                                     memory(torch, baseline_reserved), type(exc).__name__)
            rows.append(row)
            raw_stream.write(json.dumps(row, ensure_ascii=False, allow_nan=False)+"\n")
            raw_stream.flush()
            print(json.dumps({"candidate_id": candidate["id"], "image_id": item["image_id"],
                              "status": row["status"], "error": row["error"],
                              "completed": len(rows), "n": len(items)}), flush=True)
    peak = memory(torch, baseline_reserved)
    timings = [row["timing_ms"]["total"] for row in rows if row["error"] is None]
    warm = [row["timing_ms"]["total"] for row in rows
            if row["error"] is None and not row.get("cold_forward", False)]
    def quantiles(values: list[float]) -> dict:
        return {"n": len(values),
                "p50_ms": float(np.percentile(values, 50)) if values else None,
                "p95_ms": float(np.percentile(values, 95)) if values else None}
    summary = {
        "n": len(rows), "status_counts": dict(Counter(row["status"] for row in rows)),
        "selected_count": sum(row["selected_candidate_id"] is not None for row in rows),
        "error_counts": dict(Counter(row["error"]["code"] for row in rows if row["error"])),
        "cold_load_ms": load_ms, "pipeline_all_success": quantiles(timings),
        "warm_pipeline": quantiles(warm),
        "warm_definition": "successful rows excluding first completed forward of either model; no extra warmup inference",
        "memory_before_cold_load": cold_before, "memory_peak": peak,
        "classification_color_retrieval_accuracy": "computed separately by unchanged consumers",
        "mask_ground_truth_accuracy": None, "commercial_ids_authorized": False,
    }
    metadata = {
        "schema_version": "mati-target-candidate-results-1",
        "completed_utc": datetime.now(timezone.utc).isoformat(),
        "candidate_id": candidate["id"], "protocol_sha256": protocol_sha,
        "model_lock_sha256": lock_sha, "manifest_sha256": protocol["manifest_sha256"],
        "detector": {key: lock["models"][candidate["detector"]][key] for key in
                     ("model_id", "revision", "weights_sha256")},
        "segmenter": {key: lock["models"][candidate["segmenter"]][key] for key in
                      ("model_id", "revision", "weights_sha256")},
        "device": torch.cuda.get_device_name(), "torch": torch.__version__,
        "cuda_runtime": torch.version.cuda, "transformers": version("transformers"),
        "python": sys.version.split()[0],
        "dtype": protocol["dtype"], "model_parameters_dtype": "float32",
        "preprocess_model": "validated-srgb-exif-icc-max2048-transformers-pinned-1",
        "pipeline": "grounding-dino-sam-box-single-mask-1",
        "consumer_preprocess_versions": protocol["consumer_preprocess_versions"],
        "cold_load_definition": "local from_pretrained plus CUDA transfer, after lock integrity verification and CUDA initialization",
        "disable_custom_dino_kernels": True, "multimask_output": False,
        "mask_threshold": 0.0, "downloads_enabled": False,
        "class_whitelist": protocol["class_whitelist"],
        "selection_uses_catalog_labels": False, "target_accuracy_measured": False,
        "artifact_paths_relative_to": str(output),
        "crop_box_source": "tight binary mask bbox in validated original image",
        "mask_neutral_rgb": protocol["mask_neutral_rgb"],
        "mask_resize": "nearest neighbor from model input to validated original",
    }
    results = {"metadata": metadata, "summary": summary, "rows": rows}
    write_json(pair_dir / "results.json", results)
    del pair
    gc.collect()
    torch.cuda.empty_cache()
    print(json.dumps({"status": "candidate_completed", "candidate_id": candidate["id"],
                      "summary": summary}), flush=True)
    return summary


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--protocol", type=Path, required=True)
    parser.add_argument("--expected-protocol-sha256", required=True)
    parser.add_argument("--model-lock", type=Path, required=True)
    parser.add_argument("--expected-model-lock-sha256", required=True)
    parser.add_argument("--workspace-root", type=Path, default=Path.cwd())
    parser.add_argument("--output-dir", type=Path, default=Path("storage/app/ai-segmentation"))
    parser.add_argument("--candidate", choices=sorted(PAIR_IDS))
    parser.add_argument("--execute-explicit", action="store_true")
    args = parser.parse_args()
    if not args.execute_explicit:
        parser.error("Inference requires --execute-explicit after protocol/model-lock review.")
    os.umask(0o077)
    os.environ["HF_HUB_OFFLINE"] = "1"
    os.environ["TRANSFORMERS_OFFLINE"] = "1"
    os.environ["HF_HUB_DISABLE_TELEMETRY"] = "1"
    workspace = args.workspace_root.resolve(strict=True)
    sys.path.insert(0, str(Path(__file__).resolve().parent))
    sys.path.insert(0, str(workspace / "services/mati-ai/src"))
    protocol_path = args.protocol.resolve(strict=True)
    protocol_sha = sha_file(protocol_path)
    if protocol_sha != args.expected_protocol_sha256:
        raise BenchmarkFailure("PROTOCOL_SHA_MISMATCH")
    protocol = json.loads(protocol_path.read_text())
    if protocol["schema_version"] != "mati-target-benchmark-1" or protocol["n"] != 130:
        raise BenchmarkFailure("INVALID_PROTOCOL")
    from policy import (
        DUPLICATE_BBOX_IOU,
        MAX_AREA_RATIO,
        MAX_CANDIDATES,
        MIN_AREA_RATIO,
        POLICY_VERSION,
    )
    if (
        protocol["selection_policy_version"] != POLICY_VERSION
        or protocol["mask_min_area_ratio"] != MIN_AREA_RATIO
        or protocol["mask_max_area_ratio"] != MAX_AREA_RATIO
        or protocol["max_candidates_per_image"] != MAX_CANDIDATES
        or protocol["duplicate_bbox_iou"] != DUPLICATE_BBOX_IOU
        or protocol["mask_neutral_rgb"] != [127, 127, 127]
        or protocol["dtype"] != "bfloat16_autocast"
    ):
        raise BenchmarkFailure("PROTOCOL_POLICY_MISMATCH")
    manifest_path = bounded(workspace / protocol["manifest_path"], workspace)
    if sha_file(manifest_path) != protocol["manifest_sha256"]:
        raise BenchmarkFailure("MANIFEST_SHA_MISMATCH")
    manifest = json.loads(manifest_path.read_text())
    indexed = {item["image_id"]: item for item in manifest["images"]}
    ids = protocol["image_ids"]
    if len(ids) != 130 or len(set(ids)) != 130 or not set(ids).issubset(indexed):
        raise BenchmarkFailure("INVALID_FROZEN_SAMPLE")
    # Strip all catalog labels before inference and selection.
    items = [
        {key: indexed[image_id][key] for key in ("image_id", "sha256", "relative_path")}
        for image_id in ids
    ]
    if len({item["sha256"] for item in items}) != 130:
        raise BenchmarkFailure("NON_UNIQUE_FROZEN_SAMPLE")
    lock_path = args.model_lock.resolve(strict=True)
    lock_sha = sha_file(lock_path)
    if lock_sha != args.expected_model_lock_sha256:
        raise BenchmarkFailure("MODEL_LOCK_SHA_MISMATCH")
    lock = json.loads(lock_path.read_text())
    verify_lock(lock, protocol_sha)
    output_path = args.output_dir
    if not output_path.is_absolute():
        output_path = workspace / output_path
    output = bounded(output_path, workspace / "storage/app/ai-segmentation")
    private_dir(output)
    import torch
    from mati_ai.core.config import Settings
    if not torch.cuda.is_available() or not torch.cuda.is_bf16_supported():
        raise BenchmarkFailure("CUDA_BFLOAT16_UNAVAILABLE")
    random.seed(protocol["seed"])
    import numpy as np
    np.random.seed(protocol["seed"])
    torch.manual_seed(protocol["seed"])
    torch.cuda.manual_seed_all(protocol["seed"])
    torch.backends.cudnn.benchmark = False
    torch.backends.cudnn.deterministic = True
    settings = Settings(_env_file=None, device="cuda", local_files_only=True,
                        ocr_enabled=False, ocr_download_enabled=False)
    candidates = [candidate for candidate in protocol["candidates"]
                  if args.candidate is None or candidate["id"] == args.candidate]
    if not candidates or any(candidate["id"] not in PAIR_IDS for candidate in candidates):
        raise BenchmarkFailure("INVALID_CANDIDATE_SET")
    summaries = []
    for candidate in candidates:
        summaries.append(run_pair(
            candidate, items, protocol, protocol_sha, lock, lock_sha,
            workspace, output, settings, torch,
        ))
    print(json.dumps({"status": "benchmark_completed", "candidates": len(summaries)}))
    return int(any(summary["error_counts"] for summary in summaries))


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:  # noqa: BLE001 - emit explicit failure evidence, never success
        print(json.dumps({"status": "benchmark_failed",
                          "code": getattr(exc, "code", "BENCHMARK_SETUP_ERROR"),
                          "error_type": type(exc).__name__}), file=sys.stderr)
        raise SystemExit(1)
