"""Offline sequential CPU benchmark of frozen text-embedding candidates."""
from __future__ import annotations

import argparse
from contextlib import contextmanager
from datetime import datetime, timezone
import hashlib
import json
import math
import os
from pathlib import Path
import resource
import subprocess
import sys
import threading
import time
from typing import Any
import unicodedata

from prepare_text_models import PROFILE_KEYS, validate_spec

PIPELINE_VERSION = "text-embedding-1.0.0"
EXPECTED_PROTOCOL = "mati-text-benchmark-1"


def digest(path: Path) -> str:
    result = hashlib.sha256()
    with path.open("rb") as source:
        for block in iter(lambda: source.read(1024 * 1024), b""):
            result.update(block)
    return result.hexdigest()


def read_checked(path: Path, expected: str) -> dict[str, Any]:
    if not expected or digest(path) != expected:
        raise ValueError(f"SHA256 mismatch: {path.name}")
    return json.loads(path.read_text(encoding="utf-8"))


def resolve_input(raw: str, workspace: Path) -> Path:
    path = Path(raw)
    if not path.is_absolute():
        return workspace / path
    if path.exists():
        return path
    original_root = Path("/home/soto/Code/more")
    try:
        return workspace / path.relative_to(original_root)
    except ValueError:
        return path


def private_write(path: Path, value: Any) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.parent.chmod(0o700)
    temporary = path.with_suffix(path.suffix + ".tmp")
    temporary.write_text(
        json.dumps(value, ensure_ascii=False, indent=2, sort_keys=True) + "\n",
        encoding="utf-8",
    )
    temporary.chmod(0o600)
    temporary.replace(path)


def rss_bytes() -> int:
    with Path("/proc/self/statm").open() as source:
        pages = int(source.read().split()[1])
    return pages * os.sysconf("SC_PAGE_SIZE")


@contextmanager
def rss_sampler():
    baseline = rss_bytes()
    state = {"baseline_bytes": baseline, "peak_sampled_bytes": baseline}
    stop = threading.Event()

    def sample() -> None:
        while not stop.wait(0.01):
            state["peak_sampled_bytes"] = max(state["peak_sampled_bytes"], rss_bytes())

    watcher = threading.Thread(target=sample, daemon=True)
    watcher.start()
    try:
        yield state
    finally:
        stop.set()
        watcher.join()
        state["final_bytes"] = rss_bytes()
        state["process_peak_bytes"] = resource.getrusage(resource.RUSAGE_SELF).ru_maxrss * 1024
        state["peak_bytes"] = max(state["peak_sampled_bytes"], state["process_peak_bytes"])
        state["incremental_peak_mib"] = max(0, state["peak_bytes"] - baseline) / 1024**2
        state["sampling_interval_ms"] = 10


def metric_row(ranked_ids: list[str], relevance: dict[str, int]) -> dict[str, float]:
    relevant = {key for key, grade in relevance.items() if grade > 0}
    if not relevant:
        raise ValueError("Positive queries require at least one relevant chunk")
    recall1 = len(set(ranked_ids[:1]) & relevant) / len(relevant)
    recall3 = len(set(ranked_ids[:3]) & relevant) / len(relevant)
    reciprocal = next(
        (1.0 / rank for rank, key in enumerate(ranked_ids, 1) if key in relevant), 0.0
    )
    dcg = sum(
        (2 ** relevance.get(key, 0) - 1) / math.log2(rank + 1)
        for rank, key in enumerate(ranked_ids[:3], 1)
    )
    ideal = sum(
        (2**grade - 1) / math.log2(rank + 1)
        for rank, grade in enumerate(sorted(relevance.values(), reverse=True)[:3], 1)
    )
    return {"recall_at_1": recall1, "recall_at_3": recall3,
            "mrr": reciprocal, "ndcg_at_3": dcg / ideal if ideal else 0.0}


def aggregate(rows: list[dict[str, Any]]) -> dict[str, Any]:
    if not rows:
        return {"n": 0, "recall_at_1": None, "recall_at_3": None, "mrr": None, "ndcg_at_3": None}
    return {"n": len(rows), **{
        key: sum(row["metrics"][key] for row in rows) / len(rows)
        for key in ("recall_at_1", "recall_at_3", "mrr", "ndcg_at_3")
    }}


def percentile(values: list[float], quantile: float) -> float | None:
    if not values:
        return None
    ordered = sorted(values)
    position = (len(ordered) - 1) * quantile
    lo, hi = math.floor(position), math.ceil(position)
    return ordered[lo] + (ordered[hi] - ordered[lo]) * (position - lo)


def frozen_inputs(
    protocol: dict[str, Any], workspace: Path
) -> tuple[dict[str, Any], dict[str, Any], list[dict[str, Any]]]:
    corpus = read_checked(
        resolve_input(protocol["corpus_path"], workspace), protocol["corpus_sha256"]
    )
    evaluation = read_checked(
        resolve_input(protocol["evaluation_path"], workspace), protocol["evaluation_sha256"]
    )
    chunks = []
    chunk_ids: set[str] = set()
    for document in corpus["documents"]:
        for chunk in document["chunks"]:
            if chunk["id"] in chunk_ids:
                raise ValueError("Duplicate chunk ID")
            chunk_ids.add(chunk["id"])
            if hashlib.sha256(chunk["content"].encode("utf-8")).hexdigest() != chunk["content_hash"]:
                raise ValueError("Chunk content hash mismatch")
            text = document["title"] + "\n" + chunk["section"] + "\n" + chunk["content"]
            chunks.append({
                "id": chunk["id"], "document_id": document["id"],
                "text": text, "text_sha256": hashlib.sha256(text.encode("utf-8")).hexdigest(),
                "content_hash": chunk["content_hash"],
            })
    if not chunks:
        raise ValueError("Empty corpus")
    query_ids: set[str] = set()
    for query in evaluation["queries"] + evaluation.get("negatives", []):
        if query["id"] in query_ids or not isinstance(query["text"], str):
            raise ValueError("Invalid or duplicate query")
        query_ids.add(query["id"])
        graded = query.get("relevance", {})
        if any(key not in chunk_ids or not isinstance(grade, int) or grade < 0
               for key, grade in graded.items()):
            raise ValueError("Evaluation references invalid chunks/grades")
        expected = set(query.get("relevant_chunk_ids", []))
        if expected != {key for key, grade in graded.items() if grade > 0}:
            raise ValueError("Relevant chunk IDs disagree with graded relevance")
    if not evaluation["queries"]:
        raise ValueError("No positive evaluation queries")
    return corpus, evaluation, chunks


def normalized_text(text: str) -> str:
    prepared = unicodedata.normalize("NFC", text).strip()
    if not prepared:
        raise ValueError("EMPTY_TEXT")
    return prepared


def verify_model(lock_model: dict[str, Any], spec: dict[str, Any]) -> Path:
    validate_spec(spec)
    if any(lock_model.get(key) != spec.get(key) for key in PROFILE_KEYS):
        raise ValueError("Model lock does not match the frozen candidate profile")
    snapshot = Path(lock_model["snapshot_path"])
    if not snapshot.is_dir():
        raise ValueError("Prepared local model snapshot unavailable")
    for item in lock_model["files"]:
        relative = Path(item["path"])
        if relative.is_absolute() or ".." in relative.parts:
            raise ValueError("Unsafe model file path")
        path = snapshot / relative
        if not path.is_file() or digest(path) != item["sha256"]:
            raise ValueError(f"Pinned local model artifact unavailable/corrupt: {relative}")
    config = json.loads((snapshot / "config.json").read_text())
    if config.get("auto_map"):
        raise ValueError("Remote code is not allowed")
    return snapshot


def run_candidate(args: argparse.Namespace, protocol: dict[str, Any], lock: dict[str, Any]) -> int:
    # Set before importing any ML library; never initialize CUDA.
    os.environ["CUDA_VISIBLE_DEVICES"] = ""
    os.environ["HF_HUB_OFFLINE"] = "1"
    os.environ["TRANSFORMERS_OFFLINE"] = "1"
    os.environ["TOKENIZERS_PARALLELISM"] = "false"
    import numpy as np
    import torch
    import transformers
    from transformers import AutoModel, AutoTokenizer

    if protocol.get("threads") != 4 or protocol.get("dtype") != "float32":
        raise ValueError("Frozen benchmark must use four CPU threads and FP32")
    if protocol.get("document_batch_size", 1) != 1:
        raise ValueError("Preregistered document batch size is one")
    if protocol.get("query_repetitions", 1) != 1:
        raise ValueError("Preregistered warm query repetitions is one")
    if protocol.get("determinism_repetitions", 3) != 3:
        raise ValueError("Preregistered determinism repetitions is three")
    torch.set_num_threads(4)
    torch.set_num_interop_threads(1)
    torch.manual_seed(0)
    torch.use_deterministic_algorithms(True)
    workspace = Path(args.workspace)
    corpus, evaluation, chunks = frozen_inputs(protocol, workspace)
    spec = next(item for item in protocol["candidates"] if item["id"] == args.candidate_id)
    locked = next(item for item in lock["models"] if item["id"] == args.candidate_id)
    snapshot = verify_model(locked, spec)
    output = Path(args.output_root) / spec["id"]
    output.mkdir(parents=True, exist_ok=True)
    output.chmod(0o700)
    if (output / "results.json").exists() and not args.overwrite_explicit:
        raise ValueError("Results already exist; reruns require --overwrite-explicit")
    total_started = time.perf_counter()
    errors: list[dict[str, Any]] = []
    positive_rows: list[dict[str, Any]] = []
    negative_rows: list[dict[str, Any]] = []
    document_rows: list[dict[str, Any]] = []
    latencies: list[float] = []
    first_forward_ms = None
    vectors: list[Any] = []
    query_vectors: list[Any] = []
    query_vector_ids: list[str] = []
    fixture_checks: dict[str, Any] = {}
    cold_ms = None

    with rss_sampler() as memory:
        cold_started = time.perf_counter()
        tokenizer = AutoTokenizer.from_pretrained(
            snapshot, local_files_only=True, trust_remote_code=False
        )
        model = AutoModel.from_pretrained(
            snapshot, local_files_only=True, trust_remote_code=False,
            use_safetensors=True, dtype=torch.float32,
        ).to("cpu").eval()
        cold_ms = (time.perf_counter() - cold_started) * 1000

        def encode(text: str, input_type: str, reject_long_query: bool = True):
            started = time.perf_counter()
            prepared = normalized_text(text)
            prefix = spec["query_prefix"] if input_type == "query" else spec["document_prefix"]
            value = prefix + prepared
            full = tokenizer(value, truncation=False, add_special_tokens=True)
            token_count = len(full["input_ids"])
            truncated = token_count > spec["max_length"]
            if input_type == "query" and reject_long_query and truncated:
                raise ValueError("TEXT_TOO_LONG")
            batch = tokenizer(
                [value], padding=True, truncation=True, max_length=spec["max_length"],
                return_tensors="pt",
            )
            with torch.inference_mode():
                states = model(**batch).last_hidden_state.float()
                mask = batch["attention_mask"].unsqueeze(-1).bool()
                states = states.masked_fill(~mask, 0)
                pooled = states.sum(dim=1) / batch["attention_mask"].sum(dim=1).unsqueeze(-1)
                vector = torch.nn.functional.normalize(pooled, p=2, dim=1)[0]
            array = vector.cpu().numpy().astype(np.float32)
            norm = float(np.linalg.norm(array.astype(np.float64)))
            if array.shape != (spec["dimensions"],) or not np.isfinite(array).all():
                raise ValueError("INVALID_EMBEDDING")
            if abs(norm - 1) > 1e-5 or vector.device.type != "cpu":
                raise ValueError("INVALID_EMBEDDING_NORM_OR_DEVICE")
            return array, {
                "token_count_before_truncation": token_count, "truncated": truncated,
                "norm": norm, "latency_ms": (time.perf_counter() - started) * 1000,
                "text_sha256": hashlib.sha256(text.encode("utf-8")).hexdigest(),
            }

        for chunk in chunks:
            array, info = encode(chunk["text"], "document")
            if first_forward_ms is None:
                first_forward_ms = info["latency_ms"]
            vectors.append(array)
            document_rows.append({key: value for key, value in chunk.items() if key != "text"} | info)
        matrix = np.stack(vectors)
        all_queries = [(query, False) for query in evaluation["queries"]]
        all_queries += [(query, True) for query in evaluation.get("negatives", [])]
        for query, negative in all_queries:
            try:
                array, info = encode(query["text"], "query")
                query_vectors.append(array)
                query_vector_ids.append(query["id"])
                cosine = matrix.astype(np.float64) @ array.astype(np.float64)
                ranked = sorted(range(len(chunks)), key=lambda idx: (-float(cosine[idx]), chunks[idx]["id"]))
                ranking = [{
                    "chunk_id": chunks[idx]["id"], "document_id": chunks[idx]["document_id"],
                    "rank": rank, "cosine": float(cosine[idx]),
                } for rank, idx in enumerate(ranked, 1)]
                row = {
                    "query_id": query["id"], "language": query.get("language"),
                    "type": query.get("type"), **info,
                    "ranking": ranking,
                    "score_is_probability": False, "status": "completed",
                }
                if negative:
                    row["expected_policy"] = query.get("expected_policy")
                    row["fixed_ood_cosine_min"] = spec["ood_cosine_min"]
                    row["would_retrieve_by_fixed_threshold"] = ranking[0]["cosine"] >= spec["ood_cosine_min"]
                    negative_rows.append(row)
                else:
                    row["metrics"] = metric_row(
                        [chunks[idx]["id"] for idx in ranked], query["relevance"]
                    )
                    positive_rows.append(row)
                    latencies.append(info["latency_ms"])
            except Exception as exc:
                errors.append({
                    "query_id": query["id"], "negative": negative,
                    "error_type": type(exc).__name__, "code": str(exc)[:100],
                })
                failure = {
                    "query_id": query["id"], "language": query.get("language"),
                    "type": query.get("type"), "status": "failed", "ranking": [],
                    "truncated": False, "score_is_probability": False,
                }
                if negative:
                    negative_rows.append(failure)
                else:
                    failure["metrics"] = {
                        key: 0.0 for key in ("recall_at_1", "recall_at_3", "mrr", "ndcg_at_3")
                    }
                    positive_rows.append(failure)

        if evaluation["queries"]:
            repeated = [encode(evaluation["queries"][0]["text"], "query")[0] for _ in range(3)]
            fixture_checks["determinism"] = {
                "n": 3, "max_absolute_difference": float(max(
                    np.abs(repeated[0] - vector).max() for vector in repeated[1:]
                )), "tolerance": 1e-6,
            }
            fixture_checks["determinism"]["passed"] = fixture_checks["determinism"]["max_absolute_difference"] <= 1e-6
        for text in ("", " \t\n"):
            try:
                normalized_text(text)
                fixture_checks.setdefault("empty_rejected", []).append(False)
            except ValueError:
                fixture_checks.setdefault("empty_rejected", []).append(True)
        unicode_text = "camisa azul, algodón, talla M"
        nfc = encode(unicode_text, "query")[0]
        decomposed = encode(unicodedata.normalize("NFD", unicode_text), "query")[0]
        fixture_checks["unicode_nfc"] = {
            "max_absolute_difference": float(np.abs(nfc - decomposed).max()),
            "passed": bool(np.allclose(nfc, decomposed, atol=1e-6, rtol=0)),
        }
        upper = encode(unicode_text.upper(), "query")[0]
        fixture_checks["native_case_behavior"] = {
            "cosine_original_uppercase": float(nfc.astype(np.float64) @ upper.astype(np.float64)),
            "equal_within_1e_6": bool(np.allclose(nfc, upper, atol=1e-6, rtol=0)),
            "manual_lowercasing": False,
        }
        long_text = "camisa algodón azul " * 1000
        _, long_document = encode(long_text, "document")
        try:
            encode(long_text, "query")
            rejected = False
        except ValueError as exc:
            rejected = str(exc) == "TEXT_TOO_LONG"
        fixture_checks["long_text"] = {
            "query_rejected": rejected, "document_truncated": long_document["truncated"],
            "document_token_count_before_truncation": long_document["token_count_before_truncation"],
            "limit": spec["max_length"],
        }
        if torch.cuda.is_initialized():
            raise ValueError("Unexpected CUDA initialization")

    result = {
        "schema_version": "mati-text-benchmark-result-1",
        "created_at_utc": datetime.now(timezone.utc).isoformat(),
        "candidate_id": spec["id"],
        "metadata": {
            "protocol_sha256": args.protocol_sha256,
            "model_lock_sha256": args.model_lock_sha256,
            "corpus_sha256": protocol["corpus_sha256"],
            "evaluation_sha256": protocol["evaluation_sha256"],
            "corpus_version": corpus.get("corpus_version"),
            "evaluation_version": evaluation.get("evaluation_version"),
            "source_sha256": digest(Path(__file__)),
            "python": sys.version, "torch": torch.__version__,
            "transformers": transformers.__version__, "numpy": np.__version__,
            "device": "cpu", "dtype": "float32", "threads": 4, "interop_threads": 1,
            "cuda_visible_devices": "", "cuda_initialized": False,
            "model": {**{key: spec[key] for key in PROFILE_KEYS},
                      "model_revision": spec["revision"], "vector_space": "text",
                      "pipeline_version": PIPELINE_VERSION},
            "model_files": locked["files"], "license_evidence": locked["license_evidence"],
        },
        "counts": {
            "documents": len(corpus["documents"]), "chunks": len(chunks),
            "positive_expected": len(evaluation["queries"]),
            "positive_completed": sum(row["status"] == "completed" for row in positive_rows),
            "negative_expected": len(evaluation.get("negatives", [])),
            "negative_completed": sum(row["status"] == "completed" for row in negative_rows), "errors": len(errors),
        },
        "metrics": {
            "all": aggregate(positive_rows),
            "es": aggregate([row for row in positive_rows if row["language"] == "es"]),
            "en": aggregate([row for row in positive_rows if row["language"] == "en"]),
            "definition": {
                "recall": "macro mean of relevant chunks retrieved / all relevant chunks per query",
                "mrr": "first relevant rank reciprocal over complete ranking",
                "ndcg_at_3": "graded gain 2^grade-1; cutoff3",
                "negatives": "excluded from positive metrics; reported separately",
            },
        },
        "timing_ms": {
            "cold_load": cold_ms, "first_document_forward": first_forward_ms,
            "warm_query_n": len(latencies), "warm_query_p50": percentile(latencies, 0.5),
            "warm_query_p95": percentile(latencies, 0.95), "warm_query_samples": latencies,
            "total": (time.perf_counter() - total_started) * 1000,
        },
        "memory": memory | {
            "vram_peak_allocated_mib": 0.0,
            "vram_basis": "CPU tensors and no CUDA context; external GPU baseline is separate",
        },
        "truncation": {
            "document_n": len(document_rows),
            "documents_truncated": sum(row["truncated"] for row in document_rows),
            "positive_query_n": len(positive_rows),
            "positive_queries_truncated": sum(row["truncated"] for row in positive_rows),
        },
        "documents": document_rows, "positive_queries": positive_rows,
        "negative_queries": negative_rows, "errors": errors, "fixture_checks": fixture_checks,
        "gate_evaluated_elsewhere": True,
    }
    npz_path = output / "embeddings.npz"
    np.savez_compressed(
        npz_path, document_ids=np.asarray([chunk["document_id"] for chunk in chunks]),
        chunk_ids=np.asarray([chunk["id"] for chunk in chunks]),
        document_vectors=matrix,
        query_ids=np.asarray(query_vector_ids),
        query_vectors=np.stack(query_vectors) if query_vectors else np.empty((0, spec["dimensions"]), dtype=np.float32),
    )
    npz_path.chmod(0o600)
    result["vectors_artifact"] = {
        "path": npz_path.name, "sha256": digest(npz_path), "private": True,
        "frontend_exposure": False,
    }
    private_write(output / "results.json", result)
    print(f"COMPLETED {spec['id']} positive={len(positive_rows)} errors={len(errors)} "
          f"p95_ms={result['timing_ms']['warm_query_p95']}", flush=True)
    return 1 if errors else 0


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--protocol", required=True)
    parser.add_argument("--protocol-sha256", required=True)
    parser.add_argument("--model-lock", required=True)
    parser.add_argument("--model-lock-sha256", required=True)
    parser.add_argument("--workspace", default="/workspace")
    parser.add_argument("--output-root", required=True)
    parser.add_argument("--candidate-id")
    parser.add_argument("--execute-explicit", action="store_true")
    parser.add_argument("--overwrite-explicit", action="store_true")
    args = parser.parse_args()
    if not args.execute_explicit:
        parser.error("Inference requires --execute-explicit and frozen SHA256 inputs")
    protocol = read_checked(Path(args.protocol), args.protocol_sha256)
    lock = read_checked(Path(args.model_lock), args.model_lock_sha256)
    if protocol.get("schema_version") != EXPECTED_PROTOCOL:
        raise ValueError("Unexpected frozen protocol schema")
    if lock.get("schema_version") != "mati-text-model-lock-1":
        raise ValueError("Unexpected model lock schema")
    if len(protocol["candidates"]) != 3 or len({item["id"] for item in protocol["candidates"]}) != 3:
        raise ValueError("Exactly three preregistered candidates required")
    for spec in protocol["candidates"]:
        validate_spec(spec)
    if args.candidate_id and args.candidate_id not in {item["id"] for item in protocol["candidates"]}:
        raise ValueError("Unknown candidate ID")
    if args.candidate_id:
        try:
            return run_candidate(args, protocol, lock)
        except Exception as exc:
            private_write(Path(args.output_root) / args.candidate_id / "failure.json", {
                "status": "model_unavailable_or_benchmark_failed", "candidate_id": args.candidate_id,
                "error_type": type(exc).__name__, "message": str(exc)[:300],
                "protocol_sha256": args.protocol_sha256,
                "retry_or_fallback": False,
            })
            print(f"FAILED {args.candidate_id} {type(exc).__name__}: {str(exc)[:300]}", flush=True)
            return 1
    # Each fresh subprocess owns one model, preventing allocator/RSS contamination.
    commands = [
        sys.executable, str(Path(__file__).absolute()),
        "--protocol", args.protocol, "--protocol-sha256", args.protocol_sha256,
        "--model-lock", args.model_lock, "--model-lock-sha256", args.model_lock_sha256,
        "--workspace", args.workspace, "--output-root", args.output_root,
        "--execute-explicit",
    ]
    if args.overwrite_explicit:
        commands.append("--overwrite-explicit")
    status = 0
    for spec in protocol["candidates"]:
        completed = subprocess.run(commands + ["--candidate-id", spec["id"]], check=False)
        status = max(status, completed.returncode)
    return status


if __name__ == "__main__":
    raise SystemExit(main())
