"""Dedicated CPU child entry point. No vision imports or request-time downloads."""
import hashlib
import os
import resource
import time
import unicodedata

from mati_ai.rag.profile import TextModelProfile

_cached = None


def initialize_cpu_worker() -> None:
    # These settings apply exclusively in a spawned child, never in FastAPI.
    os.environ["CUDA_VISIBLE_DEVICES"] = ""
    os.environ["HF_HUB_OFFLINE"] = "1"
    os.environ["TRANSFORMERS_OFFLINE"] = "1"
    os.environ["TOKENIZERS_PARALLELISM"] = "false"
    os.environ["HF_HUB_DISABLE_TELEMETRY"] = "1"
    os.environ["OMP_NUM_THREADS"] = "4"
    os.environ["MKL_NUM_THREADS"] = "4"
    import torch

    torch.set_num_threads(4)
    torch.set_num_interop_threads(1)
    torch.manual_seed(0)
    torch.use_deterministic_algorithms(True)


def rss_mib() -> float:
    with open("/proc/self/statm", encoding="ascii") as source:
        pages = int(source.read().split()[1])
    return pages * os.sysconf("SC_PAGE_SIZE") / 1024**2


def embed_in_worker(source_json: str, cache_root: str, texts: list[str], input_type: str) -> dict:
    global _cached
    started = time.perf_counter()
    loaded_now = False
    cold_load_ms = 0.0
    integrity_ms = 0.0
    profile = TextModelProfile(source_json, cache_root)
    settings = profile.data
    fingerprint = hashlib.sha256(source_json.encode("utf-8")).hexdigest()
    try:
        import torch
        from transformers import AutoModel, AutoTokenizer

        if _cached is None or _cached["fingerprint"] != fingerprint:
            before_hash = time.perf_counter()
            profile.verify_files()
            integrity_ms = (time.perf_counter() - before_hash) * 1000
            baseline_rss = rss_mib()
            cold_started = time.perf_counter()
            tokenizer = AutoTokenizer.from_pretrained(
                settings["snapshot_path"], local_files_only=True, trust_remote_code=False
            )
            model = AutoModel.from_pretrained(
                settings["snapshot_path"], local_files_only=True,
                trust_remote_code=False, use_safetensors=True, dtype=torch.float32,
            ).to("cpu").eval()
            cold_load_ms = (time.perf_counter() - cold_started) * 1000
            _cached = {
                "fingerprint": fingerprint, "tokenizer": tokenizer, "model": model,
                "baseline_rss_mib": baseline_rss,
            }
            loaded_now = True
        tokenizer, model = _cached["tokenizer"], _cached["model"]
        prefix = settings["query_prefix"] if input_type == "query" else settings["document_prefix"]
        prepared = [prefix + unicodedata.normalize("NFC", text).strip() for text in texts]
        complete = tokenizer(prepared, truncation=False, add_special_tokens=True)
        token_counts = [len(ids) for ids in complete["input_ids"]]
        truncated = [count > settings["max_length"] for count in token_counts]
        if input_type == "query" and any(truncated):
            return {
                "status": "error", "code": "TEXT_TOO_LONG", "http_status": 422,
                "retryable": False, "model_loaded": True,
            }
        batch = tokenizer(
            prepared, padding=True, truncation=True, max_length=settings["max_length"],
            return_tensors="pt",
        )
        with torch.inference_mode():
            states = model(**batch).last_hidden_state.float()
            mask = batch["attention_mask"].unsqueeze(-1).bool()
            pooled = states.masked_fill(~mask, 0).sum(dim=1)
            pooled /= batch["attention_mask"].sum(dim=1).unsqueeze(-1)
            vectors = torch.nn.functional.normalize(pooled, p=2, dim=1)
        if vectors.shape != (len(texts), settings["dimensions"]):
            raise ValueError("Invalid embedding dimensions")
        if not torch.isfinite(vectors).all() or vectors.device.type != "cpu":
            raise ValueError("Invalid embedding/device")
        norms = vectors.double().norm(p=2, dim=1)
        if (norms - 1).abs().max().item() > 1e-5 or torch.cuda.is_initialized():
            raise ValueError("Invalid normalization/CUDA initialization")
        entries = [{
            "index": index, "vector": vector.tolist(),
            "dimensions": settings["dimensions"], "norm": float(norms[index]),
            "text_sha256": hashlib.sha256(texts[index].encode("utf-8")).hexdigest(),
            "truncated": truncated[index],
            "token_count_before_truncation": token_counts[index],
        } for index, vector in enumerate(vectors)]
        return {
            "status": "completed", "model_loaded": True,
            "data": {
                "embeddings": entries, "model": profile.identity, "device": "cpu",
                "input_type": input_type, "truncated": any(truncated),
            },
            "timing_ms": {
                "worker_total": (time.perf_counter() - started) * 1000,
                "cold_load": cold_load_ms, "integrity_verification": integrity_ms,
            },
            "runtime": {
                "child_pid": os.getpid(), "child_rss_mib": rss_mib(),
                "child_peak_rss_mib": resource.getrusage(resource.RUSAGE_SELF).ru_maxrss / 1024,
                "preload_rss_mib": _cached["baseline_rss_mib"],
                "loaded_now": loaded_now, "threads": torch.get_num_threads(),
                "interop_threads": torch.get_num_interop_threads(),
                "cuda_initialized": False,
            },
        }
    except Exception:
        _cached = None
        # No file paths, input text, credentials or third-party exception details.
        return {
            "status": "error", "code": "TEXT_MODEL_UNAVAILABLE", "http_status": 503,
            "retryable": True, "model_loaded": False,
        }
