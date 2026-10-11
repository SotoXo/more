"""One-off real CPU isolation probe; fixtures only, no DB or production token."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import time
from uuid import uuid4

import torch

from mati_ai.core.config import Settings
from mati_ai.rag.service import TextEmbeddingService


def cgroup_memory():
    path = Path("/sys/fs/cgroup/memory.current")
    return int(path.read_text()) if path.is_file() else None


def parent_state():
    return {
        "torch_threads": torch.get_num_threads(),
        "torch_interop_threads": torch.get_num_interop_threads(),
        "cuda_initialized": torch.cuda.is_initialized(),
        "uid": os.getuid(), "cgroup_memory_bytes": cgroup_memory(),
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--profile", required=True)
    parser.add_argument("--cache-dir", default="/cache/text-embeddings")
    args = parser.parse_args()
    torch.set_num_threads(8)
    if torch.get_num_interop_threads() != 16:
        torch.set_num_interop_threads(16)
    before = parent_state()
    settings = Settings(
        _env_file=None, token="local-fixture-token", ocr_enabled=False,
        text_embedding_enabled=True, text_embedding_profile_path=args.profile,
        text_embedding_cache_dir=args.cache_dir, text_embedding_timeout_seconds=30,
    )
    service = TextEmbeddingService(settings)
    started = time.perf_counter()
    first = service.embed(["Camisa azul de algodón, talla M"], "query")
    cold_lazy_ms = (time.perf_counter() - started) * 1000
    first_runtime = dict(first["runtime"])
    started = time.perf_counter()
    warm = service.embed(["¿Cuál es la diferencia entre camisa y blusa?"], "query")
    warm_wall_ms = (time.perf_counter() - started) * 1000
    after = parent_state()
    vector_norms = [entry["norm"] for entry in first["data"]["embeddings"] + warm["data"]["embeddings"]]
    from fastapi.testclient import TestClient
    from mati_ai.main import create_app

    missing_settings = Settings(
        _env_file=None, token="local-fixture-token", ocr_enabled=False,
        text_embedding_enabled=True,
        text_embedding_profile_path="/app/absent-text-profile.json",
        text_embedding_cache_dir=args.cache_dir,
    )
    app = create_app(missing_settings)
    with TestClient(app) as client:
        missing = client.post(
            "/api/v1/rag/embed",
            headers={"Authorization": "Bearer local-fixture-token"},
            json={
                "schema_version": "mati-text-embed-request-1",
                "request_id": str(uuid4()), "texts": ["camisa"], "input_type": "query",
            },
        )
        missing_probe = {
            "http_status": missing.status_code, "error_code": missing.json()["error"]["code"],
            "executor_created": app.state.text_embedding_service._executor is not None,
            "visual_model_loaded": app.state.model_manager._model is not None,
        }
    artifact = {
        "schema_version": "mati-text-cpu-isolation-probe-1",
        "real_model_inference": True,
        "environment": {
            "uid": os.getuid(), "network": "none", "gpu_devices_passed": False,
            "cache_mount": "read_only", "project_mount": "read_only",
            "parent_baseline": "explicitly controlled8/16; this is not the live Uvicorn parent",
        },
        "profile_sha256": hashlib.sha256(Path(args.profile).read_bytes()).hexdigest(),
        "probe_source_sha256": hashlib.sha256(Path(__file__).read_bytes()).hexdigest(),
        "before": before, "after": after,
        "cold_lazy_startup_ms": cold_lazy_ms, "warm_end_to_end_wall_ms": warm_wall_ms,
        "cold_worker_timing_ms": first["timing_ms"],
        "cold_child_runtime": first_runtime, "warm_child_runtime": warm["runtime"],
        "model": first["data"]["model"], "observed_norms": vector_norms,
        "dimensions": [entry["dimensions"] for entry in first["data"]["embeddings"]],
        "missing_profile_http": missing_probe,
        "checks": {
            "parent_threads_unchanged": (before["torch_threads"], before["torch_interop_threads"]) == (after["torch_threads"], after["torch_interop_threads"]) == (8, 16),
            "child_cpu_threads": first_runtime["threads"] == 4 and first_runtime["interop_threads"] == 1,
            "no_cuda_contexts": not before["cuda_initialized"] and not after["cuda_initialized"] and not first_runtime["cuda_initialized"],
            "same_cached_child": first_runtime["child_pid"] == warm["runtime"]["child_pid"],
            "warm_no_model_reload": warm["runtime"]["loaded_now"] is False,
            "norm_l2": all(abs(norm - 1) <= 1e-5 for norm in vector_norms),
            "missing_safe_503": missing_probe["http_status"] == 503 and missing_probe["error_code"] == "TEXT_MODEL_UNAVAILABLE",
            "missing_no_executor_or_visual_load": not missing_probe["executor_created"] and not missing_probe["visual_model_loaded"],
        },
        "vectors_or_texts_exposed_in_artifact": False,
    }
    service.close()
    print(json.dumps(artifact, ensure_ascii=False, sort_keys=True))
    return 0 if all(artifact["checks"].values()) else 1


if __name__ == "__main__":
    raise SystemExit(main())
