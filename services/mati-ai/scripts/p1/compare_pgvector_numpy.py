"""Validate captured production pgvector exact cosine against NumPy, without inference."""
from __future__ import annotations

import argparse
import hashlib
import json
from datetime import datetime, timezone
from pathlib import Path

import numpy as np


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--input", type=Path, required=True)
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    if args.output.exists():
        raise SystemExit("P1_REPORT_EXISTS_USE_NEW_NAME")
    raw = args.input.read_bytes()
    capture = json.loads(raw)
    samples = capture["sample"]
    persisted = np.asarray([sample["persisted_values"] for sample in samples], dtype=np.float64)
    queries = np.asarray([sample["query_prepared_values"] for sample in samples], dtype=np.float32).astype(np.float64)
    if persisted.shape != (len(samples), 768) or queries.shape != persisted.shape:
        raise SystemExit("P1_VECTOR_DIMENSIONS_INVALID")
    if not np.all(np.isfinite(persisted)) or not np.all(np.isfinite(queries)):
        raise SystemExit("P1_VECTOR_VALUES_NONFINITE")
    # pgvector persists float32 values; captures their round-trip textual representation.
    # Queries round-trip prepare output through float32, matching SQL CAST(? AS vector).
    persisted /= np.linalg.norm(persisted, axis=1, keepdims=True)
    queries /= np.linalg.norm(queries, axis=1, keepdims=True)
    checks = []
    max_error = 0.0
    for query in capture["queries"]:
        query_index = next(i for i, sample in enumerate(samples) if sample["embedding_id"] == query["query_embedding_id"])
        scores = persisted @ queries[query_index]
        candidates = []
        for i, sample in enumerate(samples):
            if sample["product_id"] == query["filters"]["exclude_prenda_id"]:
                continue
            candidates.append({
                "embedding_id": sample["embedding_id"],
                "product_id": sample["product_id"],
                "image_id": sample["image_id"],
                "score": float(scores[i]),
            })
        # Production ranks each garment's best image, then distance/product/embedding.
        candidates.sort(key=lambda row: (-row["score"], row["product_id"], row["embedding_id"]))
        best_by_product = {}
        for candidate in candidates:
            best_by_product.setdefault(candidate["product_id"], candidate)
        expected = [
            row for row in best_by_product.values()
            if row["score"] >= query["filters"]["min_similarity"]
        ][:query["filters"]["limit"]]
        observed = query["matches"]
        expected_ids = [row["product_id"] for row in expected]
        observed_ids = [row["prenda_id"] for row in observed]
        expected_embedding_ids = [row["embedding_id"] for row in expected]
        observed_embedding_ids = [row["embedding_id"] for row in observed]
        expected_image_ids = [row["image_id"] for row in expected]
        observed_image_ids = [row["image_id"] for row in observed]
        errors = [
            abs(best_by_product[row["prenda_id"]]["score"] - row["similarity_score"])
            for row in observed
        ]
        max_error = max([max_error, *errors])
        checks.append({
            "query_embedding_id": query["query_embedding_id"],
            "expected_product_ids": expected_ids,
            "observed_product_ids": observed_ids,
            "exact_order_agreement": expected_ids == observed_ids and expected_embedding_ids == observed_embedding_ids and expected_image_ids == observed_image_ids,
            "expected_embedding_ids": expected_embedding_ids,
            "observed_embedding_ids": observed_embedding_ids,
            "expected_image_ids": expected_image_ids,
            "observed_image_ids": observed_image_ids,
            "candidate_count_agreement": len(best_by_product) == query["total_candidates"],
            "max_absolute_cosine_error": max(errors, default=0.0),
        })
    passed = all(check["exact_order_agreement"] and check["candidate_count_agreement"] for check in checks) and max_error < 1e-5
    report = {
        "utc": datetime.now(timezone.utc).isoformat(),
        "implementation": "NumPy float64 cosine brute force vs production CatalogEmbeddingStore pgvector exact search",
        "numpy_version": np.__version__, "sample_n": len(samples), "query_n": len(checks),
        "source_capture_sha256": hashlib.sha256(raw).hexdigest(),
        "exact_order_agreement_rate": sum(check["exact_order_agreement"] for check in checks) / len(checks),
        "max_absolute_cosine_error": max_error, "tolerance": 1e-5,
        "inference_repeated": False, "database_writes": False, "passed": passed, "checks": checks,
    }
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(report, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({key: value for key, value in report.items() if key != "checks"}, indent=2))
    raise SystemExit(0 if passed else 1)


if __name__ == "__main__":
    main()
