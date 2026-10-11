"""Validate private V2 dataset integrity without exposing holdout queries by default.

The frozen corpus is read-only. This module contains no benchmark queries or
labels, performs no retrieval, and does not load models.
"""
from __future__ import annotations

import argparse
import hashlib
import json
import unicodedata
from collections import Counter, defaultdict
from pathlib import Path

CLASSES = {"RAG_ANSWERABLE", "TOOL_REQUIRED", "INSUFFICIENT_EVIDENCE"}
SPLITS = {"TRAIN", "CALIBRATION", "HOLDOUT"}
ROOT = Path(__file__).resolve().parents[4]


def sha_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1 << 16), b""):
            digest.update(chunk)
    return digest.hexdigest()


def read_json(path: Path) -> dict:
    with path.open(encoding="utf-8") as handle:
        data = json.load(handle)
    if not isinstance(data, dict):
        raise TypeError(f"Expected object: {path.name}")
    return data


def validate_rows(rows: list[dict], corpus: dict, allowed_splits: set[str]) -> dict:
    if not isinstance(rows, list) or not rows:
        raise ValueError("Queries must be a nonempty list")
    docs = {d["id"]: {c["id"] for c in d["chunks"]} for d in corpus["documents"]}
    ownership: dict[str, dict[str, set[str]]] = {
        key: defaultdict(set)
        for key in ("group_id", "source_concept", "template_family")
    }
    positive_docs: dict[str, set[str]] = defaultdict(set)
    ids: set[str] = set()
    texts: set[str] = set()
    for row in rows:
        required = ("id", "query", "split", "ground_truth_class", "semantic_family",
                    "source_concept", "template_family", "group_id", "language",
                    "difficulty", "expected_document_ids", "expected_chunk_ids",
                    "expected_match_policy", "label_rationale")
        if any(key not in row for key in required):
            raise ValueError("Missing dataset schema field")
        if row["split"] not in allowed_splits or row["ground_truth_class"] not in CLASSES:
            raise ValueError("Unexpected split or label")
        if row["language"] not in {"es", "en"}:
            raise ValueError("Unexpected language")
        if not isinstance(row["query"], str) or not 1 <= len(row["query"]) <= 1000:
            raise ValueError("Invalid query length")
        if unicodedata.normalize("NFC", row["query"]).strip() != row["query"]:
            raise ValueError("Query must already be stripped NFC")
        if row["id"] in ids or row["query"].casefold() in texts:
            raise ValueError("Duplicate ID or query")
        ids.add(row["id"])
        texts.add(row["query"].casefold())
        for key in ownership:
            ownership[key][row[key]].add(row["split"])
        if row["ground_truth_class"] == "RAG_ANSWERABLE":
            if not row["expected_document_ids"] or not row["expected_chunk_ids"]:
                raise ValueError("Positive lacks declared supporting evidence")
            if row["expected_match_policy"] != "any_supporting_chunk":
                raise ValueError("Unsupported positive relevance convention")
            expected_chunks = set()
            for doc_id in row["expected_document_ids"]:
                if doc_id not in docs:
                    raise ValueError("Expected document not in frozen corpus")
                expected_chunks.update(docs[doc_id])
                positive_docs[doc_id].add(row["split"])
            if set(row["expected_chunk_ids"]) != expected_chunks:
                raise ValueError("Expected chunk IDs disagree with documents")
        elif row["expected_document_ids"] or row["expected_chunk_ids"]:
            raise ValueError("Negative unexpectedly declares positive support")
        if row["ground_truth_class"] == "TOOL_REQUIRED":
            if not isinstance(row.get("required_tool"), str):
                raise ValueError("Tool query lacks conceptual tool metadata")
        elif "required_tool" in row:
            raise ValueError("Non-tool query has tool metadata")
        if not isinstance(row["label_rationale"], str) or len(row["label_rationale"]) < 15:
            raise ValueError("Missing meaningful ground-truth rationale")
    for key, values in ownership.items():
        if any(len(splits) > 1 for splits in values.values()):
            raise ValueError(f"Cross-split leakage in {key}")
    if any(len(splits) > 1 for splits in positive_docs.values()):
        raise ValueError("Positive document concept appears in multiple splits")
    return {
        "n": len(rows),
        "by_class": dict(Counter(r["ground_truth_class"] for r in rows)),
        "by_language": dict(Counter(r["language"] for r in rows)),
        "by_split": dict(Counter(r["split"] for r in rows)),
        "group_count": len(ownership["group_id"]),
        "expected_document_count": len(positive_docs),
        "cross_split_group_source_template_document_leakage": 0,
    }


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--private-dir", type=Path, default=ROOT / "storage/app/ai-rag-gate-v2")
    parser.add_argument("--final-audit", action="store_true",
                        help="Read frozen holdout labels only after the final experiment is frozen/executed.")
    args = parser.parse_args()
    private = args.private_dir.resolve()
    corpus_path = ROOT / "services/mati-ai/resources/rag/corpus-v1.json"
    corpus = read_json(corpus_path)
    quality = read_json(private / "dataset-quality.json")
    for name, key in (("dataset-frozen.json", "dataset_sha256"),
                      ("split-manifest.json", "split_sha256"),
                      ("train-cal-queries.json", "train_cal_sha256"),
                      ("holdout-sealed.json", "holdout_sealed_sha256")):
        if sha_file(private / name) != quality[key]:
            raise ValueError(f"Frozen SHA mismatch: {name}")
    traincal = read_json(private / "train-cal-queries.json")
    if traincal["dataset_sha256"] != quality["dataset_sha256"]:
        raise ValueError("Train/cal provenance mismatch")
    if traincal["split_sha256"] != quality["split_sha256"]:
        raise ValueError("Train/cal split provenance mismatch")
    result = validate_rows(traincal["queries"], corpus, {"TRAIN", "CALIBRATION"})
    if args.final_audit:
        frozen = read_json(private / "dataset-frozen.json")
        if frozen["corpus_sha256"] != sha_file(corpus_path):
            raise ValueError("Frozen corpus SHA mismatch")
        result = validate_rows(frozen["queries"], corpus, SPLITS)
        if result["n"] < 180:
            raise ValueError("V2 requires at least 180 queries")
        holdout = read_json(private / "holdout-sealed.json")
        if holdout["queries"] != [r for r in frozen["queries"] if r["split"] == "HOLDOUT"]:
            raise ValueError("Sealed holdout does not match frozen dataset")
        if traincal["queries"] != [r for r in frozen["queries"] if r["split"] != "HOLDOUT"]:
            raise ValueError("Train/cal data does not match frozen dataset")
        assignments = read_json(private / "split-manifest.json")["assignment"]
        if assignments != {r["id"]: r["split"] for r in frozen["queries"]}:
            raise ValueError("Split manifest does not match dataset")
    result["holdout_labels_loaded"] = bool(args.final_audit)
    result["frozen_hashes_match"] = True
    result["no_queries_or_labels_printed"] = True
    print(json.dumps(result, ensure_ascii=False, sort_keys=True, indent=2))


if __name__ == "__main__":
    main()
