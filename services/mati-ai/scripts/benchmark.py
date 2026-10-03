"""Operator-run measurements using real input images and already prepared models.

This script never downloads weights. Run prepare_models.py separately first.
"""
import argparse
import gc
import json
import time
from pathlib import Path

from mati_ai.core.config import Settings
from mati_ai.core.errors import PipelineError
from mati_ai.core.model_manager import ModelManager
from mati_ai.identification.codes import decode_codes
from mati_ai.identification.ocr import EasyOCRProvider
from mati_ai.vision.color import extract_colors
from mati_ai.vision.validation import validate_image


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--image", type=Path, required=True, help="Real garment image.")
    parser.add_argument("--ocr-label", type=Path, help="Optional real label photo.")
    parser.add_argument("--barcode-image", type=Path, help="Optional real barcode photo.")
    parser.add_argument("--device", choices=["auto", "cuda", "cpu"], action="append")
    parser.add_argument("--samples", type=int, default=3)
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    if not 1 <= args.samples <= 20:
        parser.error("--samples must be between 1 and 20.")

    import torch

    settings = Settings().model_copy(
        update={"local_files_only": True, "ocr_download_enabled": False}
    )
    image = validate_image(args.image.read_bytes(), settings).image
    report: dict = {
        "input_kind": "operator_provided_image",
        "downloads_enabled": False,
        "image_size": list(image.size),
        "torch": torch.__version__,
        "cuda_available": torch.cuda.is_available(),
        "gpu": torch.cuda.get_device_name() if torch.cuda.is_available() else None,
        "visual": {},
    }
    failed = False
    prompts = [
        "This is a photo of trousers.",
        "This is a photo of a polo shirt.",
        "This is a photo of a skirt.",
    ]
    for device in dict.fromkeys(args.device or ["auto"]):
        if device == "cuda" and not torch.cuda.is_available():
            report["visual"][device] = {"status": "skipped", "reason": "CUDA_UNAVAILABLE"}
            failed = True
            continue
        manager = ModelManager(settings.model_copy(update={"device": device}))
        try:
            samples = []
            for _ in range(args.samples):
                output = manager.infer(image, prompts)
                samples.append(
                    {
                        "inference_ms": output["metrics"]["inference"],
                        "device_used": output["device"],
                        "vram_peak_mb": output["vram_peak_mb"],
                    }
                )
            report["visual"][device] = {
                "status": "completed",
                "model_load_ms": manager.load_ms,
                "samples": samples,
                "embedding_dimensions": len(output["embedding"]),
                "model": output["model"],
            }
        except PipelineError as exc:
            report["visual"][device] = {"status": "failed", "error_code": exc.code}
            failed = True
        finally:
            del manager
            gc.collect()
            if torch.cuda.is_available():
                torch.cuda.empty_cache()

    started = time.perf_counter()
    colors = extract_colors(image, [])
    report["color"] = {
        "duration_ms": round((time.perf_counter() - started) * 1000, 3),
        "palette_size": len(colors["palette"]),
    }
    if args.barcode_image:
        barcode = validate_image(args.barcode_image.read_bytes(), settings).image
        started = time.perf_counter()
        codes = decode_codes(barcode)
        report["barcode"] = {
            "duration_ms": round((time.perf_counter() - started) * 1000, 3),
            "detected": len(codes["codes"]),
            "symbologies": sorted({item["symbology"] for item in codes["codes"]}),
            "valid_checksums": sum(item["checksum_valid"] is True for item in codes["codes"]),
        }
    if args.ocr_label:
        label = validate_image(args.ocr_label.read_bytes(), settings).image
        provider = EasyOCRProvider(settings)
        started = time.perf_counter()
        try:
            output = provider.read(label)
            first_ms = round((time.perf_counter() - started) * 1000, 3)
            started = time.perf_counter()
            output = provider.read(label)
            report["ocr"] = {
                "status": "completed",
                "load_and_first_ms": first_ms,
                "warm_ms": round((time.perf_counter() - started) * 1000, 3),
                "regions": len(output["regions"]),
                "text_characters": len(output["raw_text"]),
            }
        except PipelineError as exc:
            report["ocr"] = {"status": "failed", "error_code": exc.code}
            failed = True

    report["status"] = "partial" if failed else "completed"
    args.output.parent.mkdir(parents=True, exist_ok=True)
    encoded = json.dumps(report, indent=2, ensure_ascii=False)
    args.output.write_text(encoded + "\n", encoding="utf-8")
    print(encoded, flush=True)
    if failed:
        raise SystemExit(1)


if __name__ == "__main__":
    main()
