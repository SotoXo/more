"""Read-only approved text model profile and integrity checks."""
from dataclasses import dataclass
import hashlib
import json
from pathlib import Path
import re
from typing import Any

PIPELINE_VERSION = "text-embedding-1.0.0"
SHA40 = re.compile(r"^[0-9a-f]{40}$")
SHA64 = re.compile(r"^[0-9a-f]{64}$")


def file_sha256(path: Path) -> str:
    value = hashlib.sha256()
    with path.open("rb") as source:
        for block in iter(lambda: source.read(1024 * 1024), b""):
            value.update(block)
    return value.hexdigest()


@dataclass(frozen=True)
class TextModelProfile:
    source_json: str
    cache_root: str

    @classmethod
    def read(cls, path: Path, cache_root: Path) -> "TextModelProfile":
        raw = json.loads(path.read_text(encoding="utf-8"))
        if raw.get("schema_version") != "mati-text-model-profile-1" or raw.get("approved") is not True:
            raise ValueError("Text model profile is not approved")
        if raw.get("pipeline_version") != PIPELINE_VERSION or raw.get("vector_space") != "text":
            raise ValueError("Unexpected text pipeline/space")
        revision = raw.get("revision", raw.get("model_revision", ""))
        if not isinstance(revision, str) or not SHA40.fullmatch(revision):
            raise ValueError("Immutable revision required")
        for key in ("weights_sha256", "protocol_sha256", "benchmark_result_sha256"):
            if not isinstance(raw.get(key), str) or not SHA64.fullmatch(raw[key]):
                raise ValueError("Approved artifact SHA256 missing")
        expected = {
            "intfloat/multilingual-e5-small": (384, 512, "query: ", "passage: ",
                "text-nfc-strip-meanpool-l2-e5querypassage512-1", "mit"),
            "intfloat/multilingual-e5-base": (768, 512, "query: ", "passage: ",
                "text-nfc-strip-meanpool-l2-e5querypassage512-1", "mit"),
            "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2":
                (384, 128, "", "", "text-nfc-strip-meanpool-l2-minilm128-1", "apache-2.0"),
        }.get(raw.get("model_id"))
        if expected is None or tuple(raw.get(key) for key in (
            "dimensions", "max_length", "query_prefix", "document_prefix", "preprocess_version", "license"
        )) != expected:
            raise ValueError("Unexpected text model profile")
        if raw.get("normalization") != "l2" or raw.get("pooling") != "attention_mask_mean":
            raise ValueError("Unexpected text vector processing")
        floor = 0.35 if raw["model_id"].startswith("sentence-transformers/") else 0.74
        if raw.get("minimum_cosine") != floor:
            raise ValueError("Cosine floor differs from frozen profile")
        pinned = {
            "intfloat/multilingual-e5-small": (
                "614241f622f53c4eeff9890bdc4f31cfecc418b3",
                "1a55775f53449dac10a2bcbc312469fac40b96d53198c407081a831f81c98477",
            ),
            "intfloat/multilingual-e5-base": (
                "d128750597153bb5987e10b1c3493a34e5a4502a",
                "a18a44fad1d0b46ded15928144138cff1135d5cc8233bdd90be5f18822de09a7",
            ),
            "sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2": (
                "e8f8c211226b894fcb81acc59f3b34ba3efd5f42",
                "eaa086f0ffee582aeb45b36e34cdd1fe2d6de2bef61f8a559a1bbc9bd955917b",
            ),
        }
        if (revision, raw["weights_sha256"]) != pinned[raw["model_id"]]:
            raise ValueError("Checkpoint differs from evaluated immutable model")
        if raw.get("model_revision", revision) != revision:
            raise ValueError("Revision aliases disagree")
        raw["revision"] = revision
        snapshot = Path(raw["snapshot_path"])
        cache = cache_root.resolve()
        if not snapshot.is_absolute() or snapshot.name != revision:
            raise ValueError("Invalid pinned local snapshot")
        if not snapshot.resolve().is_relative_to(cache):
            raise ValueError("Model snapshot outside dedicated text cache")
        if not isinstance(raw.get("files"), list) or not raw["files"]:
            raise ValueError("Pinned model assets missing")
        paths: set[str] = set()
        for item in raw["files"]:
            relative = Path(item["path"])
            if relative.is_absolute() or ".." in relative.parts or relative.as_posix() in paths:
                raise ValueError("Unsafe or duplicate model asset")
            if relative.suffix in {".bin", ".py", ".pt", ".pth", ".pickle"}:
                raise ValueError("Non-native/unsafe model asset")
            if not SHA64.fullmatch(item["sha256"]) or not isinstance(item["bytes"], int):
                raise ValueError("Invalid model asset integrity metadata")
            actual = snapshot / relative
            if not actual.is_file() or not actual.resolve().is_relative_to(cache):
                raise ValueError("Local model asset unavailable/outside cache")
            paths.add(relative.as_posix())
        required = {"model.safetensors", "config.json", "tokenizer_config.json"}
        if not required.issubset(paths):
            raise ValueError("Core model assets missing")
        checkpoint = next(item for item in raw["files"] if item["path"] == "model.safetensors")
        if checkpoint["sha256"] != raw["weights_sha256"]:
            raise ValueError("Checkpoint identity mismatch")
        config = json.loads((snapshot / "config.json").read_text())
        if config.get("auto_map") or config.get("hidden_size") != raw["dimensions"]:
            raise ValueError("Native architecture/dimension mismatch")
        return cls(json.dumps(raw, ensure_ascii=False, sort_keys=True), str(cache))

    @property
    def data(self) -> dict[str, Any]:
        return json.loads(self.source_json)

    @property
    def identity(self) -> dict[str, Any]:
        data = self.data
        return {
            "model_id": data["model_id"], "model_revision": data["revision"],
            "dimensions": data["dimensions"], "normalization": "l2",
            "preprocess_version": data["preprocess_version"], "vector_space": "text",
            "pipeline_version": PIPELINE_VERSION,
        }

    def verify_files(self) -> None:
        data = self.data
        snapshot = Path(data["snapshot_path"])
        for item in data["files"]:
            path = snapshot / item["path"]
            if path.stat().st_size != item["bytes"] or file_sha256(path) != item["sha256"]:
                raise ValueError("Approved local text model assets changed")
