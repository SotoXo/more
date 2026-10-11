#!/usr/bin/env python3
"""Explicit benchmark weight preparation; never imported by the production service."""
from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

MODEL_IDS = {
    "IDEA-Research/grounding-dino-tiny",
    "IDEA-Research/grounding-dino-base",
    "facebook/sam2.1-hiera-tiny",
    "facebook/sam-vit-base",
}
CODE_LICENSES = {
    "IDEA-Research": "https://github.com/IDEA-Research/GroundingDINO/blob/main/LICENSE",
    "sam2": "https://github.com/facebookresearch/sam2/blob/main/LICENSE",
    "sam": "https://github.com/facebookresearch/segment-anything/blob/main/LICENSE",
}


def sha_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def write_json(path: Path, value: dict) -> None:
    if path.exists():
        raise ValueError(f"Evidence already exists: {path.name}")
    with path.open("x", encoding="utf-8") as stream:
        json.dump(value, stream, indent=2, ensure_ascii=False)
        stream.write("\n")
    path.chmod(0o600)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--protocol", type=Path, required=True)
    parser.add_argument("--expected-protocol-sha256", required=True)
    parser.add_argument("--workspace-root", type=Path, default=Path.cwd())
    parser.add_argument("--output-dir", type=Path, default=Path("storage/app/ai-segmentation"))
    parser.add_argument("--cache-dir", type=Path)
    parser.add_argument("--prepare-explicit", action="store_true")
    args = parser.parse_args()
    if not args.prepare_explicit:
        parser.error("Downloads require the explicit --prepare-explicit flag.")
    os.umask(0o077)
    protocol_path = args.protocol.resolve(strict=True)
    protocol_sha = sha_file(protocol_path)
    if protocol_sha != args.expected_protocol_sha256:
        raise ValueError("Protocol hash differs from the frozen, approved protocol.")
    protocol = json.loads(protocol_path.read_text())
    if protocol.get("schema_version") != "mati-target-benchmark-1":
        raise ValueError("Unsupported protocol schema.")
    models = sorted({
        model for candidate in protocol["candidates"]
        for model in (candidate["detector"], candidate["segmenter"])
    })
    if set(models) != MODEL_IDS:
        raise ValueError("Protocol model set differs from the four researched checkpoints.")
    workspace = args.workspace_root.resolve(strict=True)
    output = args.output_dir
    if not output.is_absolute():
        output = workspace / output
    output = output.resolve()
    output.relative_to(workspace / "storage/app/ai-segmentation")
    output.mkdir(parents=True, exist_ok=True, mode=0o700)
    output.chmod(0o700)
    lock_path = output / "model-lock.json"
    if lock_path.exists():
        lock = json.loads(lock_path.read_text())
        if lock.get("protocol_sha256") != protocol_sha or set(lock.get("models", {})) != MODEL_IDS:
            raise ValueError("Existing immutable model lock does not match the protocol.")
        for record in lock["models"].values():
            local = Path(record["local_snapshot"])
            if sha_file(local / "model.safetensors") != record["weights_sha256"]:
                raise ValueError("Existing model lock references absent or changed weights.")
        print(json.dumps({"status": "already_prepared", "model_lock": str(lock_path),
                          "model_lock_sha256": sha_file(lock_path)}))
        return 0

    from huggingface_hub import HfApi, snapshot_download
    from huggingface_hub.constants import HF_HUB_CACHE

    cache = (args.cache_dir or Path(HF_HUB_CACHE)).resolve()
    cache.mkdir(parents=True, exist_ok=True)
    api = HfApi(token=False)
    resolved = {}
    for model_id in models:
        info = api.model_info(model_id, revision="main", files_metadata=True, token=False)
        revision = info.sha
        if not isinstance(revision, str) or not re.fullmatch(r"[0-9a-f]{40}", revision):
            raise ValueError(f"Publisher did not return an immutable revision for {model_id}.")
        card = info.card_data.to_dict() if info.card_data is not None else {}
        if card.get("license") != "apache-2.0" or bool(info.gated):
            raise ValueError(f"Checkpoint commercial-license/access gate failed: {model_id}.")
        weights = next((file for file in info.siblings
                        if file.rfilename == "model.safetensors"), None)
        if weights is None or weights.lfs is None:
            raise ValueError(f"Missing safetensors LFS integrity metadata: {model_id}.")
        lfs_sha = weights.lfs.sha256
        if not re.fullmatch(r"[0-9a-f]{64}", lfs_sha or ""):
            raise ValueError("Malformed publisher weight hash.")
        resolved[model_id] = {
            "revision": revision, "license": card["license"],
            "weights_sha256": lfs_sha, "weights_bytes": weights.lfs.size,
        }
    # Resolve all current-main identities once, then download only those immutable SHAs.
    print(json.dumps({"status": "revisions_frozen", "protocol_sha256": protocol_sha,
                      "models": resolved}), flush=True)
    records = {}
    for model_id, metadata in resolved.items():
        local = Path(snapshot_download(
            repo_id=model_id, revision=metadata["revision"], cache_dir=str(cache),
            token=False, max_workers=4,
            allow_patterns=["*.json", "*.txt", "*.model", "*.vocab", "*.merges",
                            "*.safetensors", "README*", "LICENSE*", "NOTICE*"],
            ignore_patterns=["*.bin", "*.pt", "*.pth", "*.onnx", "*.py",
                             "*.msgpack", "*.h5", "*.tflite"],
        ))
        weights_path = local / "model.safetensors"
        if sha_file(weights_path) != metadata["weights_sha256"]:
            raise ValueError(f"Downloaded checkpoint SHA256 mismatch: {model_id}.")
        if weights_path.stat().st_size != metadata["weights_bytes"]:
            raise ValueError(f"Downloaded checkpoint length mismatch: {model_id}.")
        files = []
        for path in sorted(local.rglob("*")):
            if path.is_file():
                relative = path.relative_to(local).as_posix()
                if path.suffix in {".bin", ".pt", ".pth", ".py"}:
                    continue
                files.append({"path": relative, "bytes": path.stat().st_size,
                              "sha256": sha_file(path)})
        if not (local / "config.json").is_file() or not (local / "preprocessor_config.json").is_file():
            raise ValueError(f"Snapshot lacks image model configuration: {model_id}.")
        family = ("sam2" if "/sam2." in model_id else
                  "sam" if "/sam-vit" in model_id else "IDEA-Research")
        records[model_id] = {
            "model_id": model_id, **metadata, "local_snapshot": str(local),
            "gated": False, "use_safetensors": True, "trust_remote_code": False,
            "publisher_model_card_url": f"https://huggingface.co/{model_id}/blob/{metadata['revision']}/README.md",
            "upstream_code_license_url": CODE_LICENSES[family],
            "license_scope": "publisher code/checkpoint; datasets have separate restrictions",
            "files": files,
        }
        print(json.dumps({"status": "model_prepared", "model_id": model_id,
                          "revision": metadata["revision"], "weights_sha256": metadata["weights_sha256"]}),
              flush=True)
    lock = {
        "schema_version": "mati-segmentation-model-lock-1",
        "prepared_utc": datetime.now(timezone.utc).isoformat(),
        "protocol_sha256": protocol_sha, "cache_dir": str(cache), "models": records,
        "preparation_is_explicit": True, "inference_downloads_allowed": False,
        "dataset_license_is_separate": True,
    }
    write_json(lock_path, lock)
    print(json.dumps({"status": "prepared", "model_lock": str(lock_path),
                      "model_lock_sha256": sha_file(lock_path)}), flush=True)
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:  # noqa: BLE001 - emit explicit failure evidence, never success
        # No auth tokens are supplied, but avoid echoing transport exceptions or response bodies.
        print(json.dumps({"status": "preparation_failed", "error_type": type(exc).__name__}), file=sys.stderr)
        raise SystemExit(1)
