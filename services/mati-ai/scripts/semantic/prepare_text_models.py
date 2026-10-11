"""Explicit preparation of pinned text models; never called by request handlers."""
from __future__ import annotations

import argparse
from datetime import datetime, timezone
import hashlib
import json
import os
from pathlib import Path
import re
from typing import Any

MODEL_SPECS = [
    {
        "id": "e5-small",
        "model_id": "intfloat/multilingual-e5-small",
        "revision": "614241f622f53c4eeff9890bdc4f31cfecc418b3",
        "weights_sha256": "1a55775f53449dac10a2bcbc312469fac40b96d53198c407081a831f81c98477",
        "weights_bytes": 470641600,
        "dimensions": 384,
        "max_length": 512,
        "query_prefix": "query: ",
        "document_prefix": "passage: ",
        "preprocess_version": "text-nfc-strip-meanpool-l2-e5querypassage512-1",
        "pooling": "attention_mask_mean",
        "normalization": "l2",
        "license": "mit",
        "ood_cosine_min": 0.74,
    },
    {
        "id": "minilm",
        "model_id": "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2",
        "revision": "e8f8c211226b894fcb81acc59f3b34ba3efd5f42",
        "weights_sha256": "eaa086f0ffee582aeb45b36e34cdd1fe2d6de2bef61f8a559a1bbc9bd955917b",
        "weights_bytes": 470641600,
        "dimensions": 384,
        "max_length": 128,
        "query_prefix": "",
        "document_prefix": "",
        "preprocess_version": "text-nfc-strip-meanpool-l2-minilm128-1",
        "pooling": "attention_mask_mean",
        "normalization": "l2",
        "license": "apache-2.0",
        "ood_cosine_min": 0.35,
    },
    {
        "id": "e5-base",
        "model_id": "intfloat/multilingual-e5-base",
        "revision": "d128750597153bb5987e10b1c3493a34e5a4502a",
        "weights_sha256": "a18a44fad1d0b46ded15928144138cff1135d5cc8233bdd90be5f18822de09a7",
        "weights_bytes": 1112201288,
        "dimensions": 768,
        "max_length": 512,
        "query_prefix": "query: ",
        "document_prefix": "passage: ",
        "preprocess_version": "text-nfc-strip-meanpool-l2-e5querypassage512-1",
        "pooling": "attention_mask_mean",
        "normalization": "l2",
        "license": "mit",
        "ood_cosine_min": 0.74,
    },
]
SHA40 = re.compile(r"^[0-9a-f]{40}$")
SHA64 = re.compile(r"^[0-9a-f]{64}$")
ALLOW_PATTERNS = [
    "config.json", "tokenizer*.json", "special_tokens_map.json",
    "vocab.txt", "sentencepiece.bpe.model", "*.safetensors",
    "config_sentence_transformers.json", "sentence_bert_config.json",
    "modules.json", "1_Pooling/config.json", "2_Normalize/config.json",
    "README.md", "LICENSE*", "NOTICE*", "license*", "notice*",
]
PROFILE_KEYS = [
    "id", "model_id", "revision", "weights_sha256", "dimensions",
    "max_length", "query_prefix", "document_prefix", "preprocess_version",
    "pooling", "normalization", "license", "ood_cosine_min",
]


def digest(path: Path) -> str:
    value = hashlib.sha256()
    with path.open("rb") as source:
        for block in iter(lambda: source.read(1024 * 1024), b""):
            value.update(block)
    return value.hexdigest()


def json_bytes(value: Any) -> bytes:
    return (json.dumps(value, ensure_ascii=False, indent=2, sort_keys=True) + "\n").encode()


def private_write(path: Path, value: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.parent.chmod(0o700)
    payload = json_bytes(value)
    temporary = path.with_suffix(path.suffix + ".tmp")
    temporary.write_bytes(payload)
    temporary.chmod(0o600)
    temporary.replace(path)


def validate_spec(spec: dict[str, Any]) -> None:
    approved = next((item for item in MODEL_SPECS if item["id"] == spec["id"]), None)
    if approved is None or any(spec.get(key) != approved[key] for key in PROFILE_KEYS):
        raise ValueError("Model/profile differs from the preregistered immutable shortlist")
    if not SHA40.fullmatch(spec["revision"]) or not SHA64.fullmatch(spec["weights_sha256"]):
        raise ValueError("A full immutable model revision and weights SHA256 are required")


def make_new_cache_readable(cache: Path) -> None:
    """Expose only this new public-model cache to the runtime UID."""
    if cache.name != "text-embeddings" or cache.is_symlink():
        raise ValueError("Only a dedicated text-embeddings directory may change permissions")
    cache.chmod(0o755)
    for root, directories, files in os.walk(cache, followlinks=False):
        root_path = Path(root)
        if root_path.is_symlink():
            continue
        root_path.chmod(0o755)
        for name in directories:
            target = root_path / name
            if not target.is_symlink():
                target.chmod(0o755)
        for name in files:
            target = root_path / name
            if not target.is_symlink():
                target.chmod(0o644)


def inspect_snapshot(snapshot: Path, spec: dict[str, Any]) -> list[dict[str, Any]]:
    config = json.loads((snapshot / "config.json").read_text())
    if config.get("auto_map"):
        raise ValueError("Remote model code is prohibited")
    if config.get("model_type") not in {"bert", "xlm-roberta"}:
        raise ValueError("Unexpected native model architecture")
    if config.get("hidden_size") != spec["dimensions"]:
        raise ValueError("Model dimension disagrees with approved profile")
    sentence = json.loads((snapshot / "sentence_bert_config.json").read_text())
    if sentence.get("max_seq_length") != spec["max_length"]:
        raise ValueError("Native sentence profile max length disagrees")
    pooling = json.loads((snapshot / "1_Pooling/config.json").read_text())
    if not pooling.get("pooling_mode_mean_tokens"):
        raise ValueError("Expected masked mean pooling")
    if pooling.get("word_embedding_dimension") != spec["dimensions"]:
        raise ValueError("Pooling dimension disagrees")
    weights = snapshot / "model.safetensors"
    if not weights.is_file() or weights.stat().st_size != spec["weights_bytes"]:
        raise ValueError("Missing or unexpected Safetensors checkpoint size")
    records = []
    for path in sorted(snapshot.rglob("*")):
        if not path.is_file():
            continue
        relative = path.relative_to(snapshot).as_posix()
        if path.suffix.lower() in {".bin", ".pt", ".pth", ".py", ".pickle"}:
            raise ValueError("Unsafe/duplicated model artifact in prepared snapshot")
        sha = digest(path)
        if relative == "model.safetensors" and sha != spec["weights_sha256"]:
            raise ValueError("Checkpoint SHA256 mismatch")
        records.append({
            "path": relative, "sha256": sha, "bytes": path.stat().st_size,
        })
    if not any(record["path"] == "README.md" for record in records):
        raise ValueError("Pinned model card missing")
    return records


def prepare(args: argparse.Namespace) -> dict[str, Any]:
    if not args.prepare_explicit:
        raise ValueError("Preparation requires --prepare-explicit; request-time downloads forbidden")
    cache = Path(args.cache_dir).absolute()
    if cache.name != "text-embeddings" or cache.is_symlink():
        raise ValueError("A separate text-embeddings cache is required")
    cache.mkdir(parents=True, exist_ok=True)
    specs = MODEL_SPECS
    protocol_sha = None
    if args.protocol:
        protocol_path = Path(args.protocol)
        protocol_sha = digest(protocol_path)
        if not args.protocol_sha256 or protocol_sha != args.protocol_sha256:
            raise ValueError("Protocol SHA256 mismatch")
        protocol = json.loads(protocol_path.read_text())
        if protocol.get("schema_version") != "mati-text-benchmark-1":
            raise ValueError("Unexpected protocol schema")
        specs = protocol["candidates"]
    for spec in specs:
        validate_spec(spec)
    from huggingface_hub import HfApi, snapshot_download

    api = HfApi(token=False)
    prepared = []
    for spec in specs:
        print(f"Preparing {spec['id']} revision {spec['revision']}", flush=True)
        info = api.model_info(spec["model_id"], revision=spec["revision"], files_metadata=True)
        if info.sha != spec["revision"] or info.private or info.gated:
            raise ValueError("Pinned public/ungated revision cannot be verified")
        card_data = info.card_data
        card = (
            card_data.to_dict() if hasattr(card_data, "to_dict")
            else card_data if isinstance(card_data, dict)
            else {}
        )
        if card.get("license") != spec["license"]:
            raise ValueError("Pinned model license differs from approved research")
        checkpoint = next(
            (entry for entry in info.siblings if entry.rfilename == "model.safetensors"), None
        )
        lfs = getattr(checkpoint, "lfs", None)
        sha = lfs.get("sha256") if isinstance(lfs, dict) else getattr(lfs, "sha256", None)
        if sha != spec["weights_sha256"]:
            raise ValueError("Official checkpoint blob SHA256 differs from approval")
        snapshot = Path(snapshot_download(
            repo_id=spec["model_id"], revision=spec["revision"],
            cache_dir=str(cache), token=False, allow_patterns=ALLOW_PATTERNS,
            ignore_patterns=["*.bin", "*.pt", "*.pth", "*.py", "onnx/**", "openvino/**"],
        ))
        files = inspect_snapshot(snapshot, spec)
        license_assets = [
            item for item in files
            if Path(item["path"]).name.lower().startswith(("license", "notice"))
        ]
        prepared.append({
            **{key: spec[key] for key in PROFILE_KEYS},
            "snapshot_path": str(snapshot),
            "files": files,
            "license_evidence": {
                "declared_license": card["license"],
                "model_card_revision": spec["revision"],
                "model_card_sha256": next(item["sha256"] for item in files if item["path"] == "README.md"),
                "license_assets": license_assets,
                "scope": "checkpoint declaration; training datasets have separate terms",
                "source_url": f"https://huggingface.co/{spec['model_id']}/tree/{spec['revision']}",
            },
        })
        make_new_cache_readable(cache)
        print(f"Verified {spec['id']}: {len(files)} assets", flush=True)
    lock = {
        "schema_version": "mati-text-model-lock-1",
        "created_at_utc": datetime.now(timezone.utc).isoformat(),
        "protocol_sha256": protocol_sha,
        "preparation_independent_of_corpus": protocol_sha is None,
        "model_spec_sha256": hashlib.sha256(json_bytes(MODEL_SPECS)).hexdigest(),
        "cache_dir": str(cache), "models": prepared,
        "runtime": {
            "trust_remote_code": False, "use_safetensors": True,
            "request_downloads": False, "device": "cpu",
            "weights_permissions": "new text cache only: directories755/files644",
        },
    }
    private_write(Path(args.output_lock), lock)
    print(f"Lock: {args.output_lock} SHA256 {digest(Path(args.output_lock))}", flush=True)
    return lock


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--cache-dir", required=True)
    parser.add_argument("--output-lock", required=True)
    parser.add_argument("--protocol")
    parser.add_argument("--protocol-sha256")
    parser.add_argument("--prepare-explicit", action="store_true")
    args = parser.parse_args()
    try:
        prepare(args)
    except Exception as exc:
        print(f"PREPARATION_FAILED {type(exc).__name__}: {exc}", flush=True)
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
