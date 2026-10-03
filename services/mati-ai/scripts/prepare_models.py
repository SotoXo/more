"""Download selected model assets without running an image through inference."""
import argparse
import json
import time
from importlib.metadata import version
from pathlib import Path

from mati_ai.core.config import Settings


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Prepare visual weights and/or OCR assets before starting the private API."
    )
    parser.add_argument("--vision", action="store_true", help="Download configured visual weights.")
    parser.add_argument("--ocr", action="store_true", help="Prepare EasyOCR Spanish/English CPU assets.")
    parser.add_argument("--output", type=Path, help="Optional JSON preparation report.")
    args = parser.parse_args()
    if not args.vision and not args.ocr:
        parser.error("Select --vision, --ocr, or both.")

    settings = Settings()
    report: dict = {"preparation_only": True, "inference_performed": False}
    if args.vision:
        from huggingface_hub import snapshot_download

        started = time.perf_counter()
        cache = snapshot_download(
            settings.model_id,
            revision=settings.model_revision,
            allow_patterns=["*.json", "*.model", "*.safetensors", "*.txt"],
        )
        report["vision"] = {
            "model_id": settings.model_id,
            "model_revision": Path(cache).name,
            "cache_path": cache,
            "preparation_seconds": round(time.perf_counter() - started, 3),
            "transformers_version": version("transformers"),
        }
        print(json.dumps({"vision": report["vision"]}, ensure_ascii=False), flush=True)

    if args.ocr:
        import easyocr

        started = time.perf_counter()
        # Reader initialization downloads/checks its assets but does not call readtext.
        reader = easyocr.Reader(
            ["es", "en"],
            gpu=False,
            download_enabled=True,
            model_storage_directory=settings.ocr_model_directory,
            verbose=False,
        )
        report["ocr"] = {
            "provider": "easyocr_cpu",
            "version": version("easyocr"),
            "languages": ["es", "en"],
            "model_directory": reader.model_storage_directory,
            "preparation_seconds": round(time.perf_counter() - started, 3),
        }
        del reader
        print(json.dumps({"ocr": report["ocr"]}, ensure_ascii=False), flush=True)

    encoded = json.dumps(report, indent=2, ensure_ascii=False)
    if args.output:
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(encoded + "\n", encoding="utf-8")
    print(encoded, flush=True)


if __name__ == "__main__":
    main()
