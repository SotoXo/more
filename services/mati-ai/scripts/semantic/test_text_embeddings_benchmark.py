"""Metric and immutable-input checks; no ML libraries or checkpoints required."""
import hashlib
import json
from pathlib import Path

import pytest

from benchmark_embeddings import (
    aggregate,
    frozen_inputs,
    metric_row,
    normalized_text,
    percentile,
    read_checked,
)
from prepare_text_models import MODEL_SPECS, validate_spec


def test_multirelevant_recall_is_fraction_not_hit():
    metrics = metric_row(["d", "a", "c", "b"], {"a": 2, "b": 2, "c": 3})
    assert metrics["recall_at_1"] == 0
    assert metrics["recall_at_3"] == pytest.approx(2 / 3)
    assert metrics["mrr"] == 0.5
    assert 0 < metrics["ndcg_at_3"] < 1


def test_graded_ndcg_uses_ideal_order():
    best = metric_row(["c", "a", "b"], {"a": 2, "b": 2, "c": 3})
    worse = metric_row(["a", "b", "c"], {"a": 2, "b": 2, "c": 3})
    assert best["ndcg_at_3"] == 1
    assert worse["ndcg_at_3"] < 1
    assert best["recall_at_3"] == worse["recall_at_3"] == 1


def test_negative_queries_cannot_enter_positive_metric():
    with pytest.raises(ValueError, match="Positive queries"):
        metric_row(["a"], {})


def test_mrr_uses_full_rank_beyond_top3():
    metrics = metric_row(["a", "b", "c", "d"], {"d": 1})
    assert metrics["recall_at_3"] == 0
    assert metrics["mrr"] == 0.25
    assert metrics["ndcg_at_3"] == 0


def test_macro_metric_keeps_failed_query_as_zero():
    success = {"metrics": dict.fromkeys(["recall_at_1", "recall_at_3", "mrr", "ndcg_at_3"], 1.0)}
    failed = {"metrics": dict.fromkeys(["recall_at_1", "recall_at_3", "mrr", "ndcg_at_3"], 0.0)}
    assert aggregate([success, failed])["mrr"] == 0.5
    assert aggregate([success, failed])["n"] == 2


def test_p95_linear_interpolation_and_empty():
    assert percentile([1, 2, 3, 4], 0.95) == pytest.approx(3.85)
    assert percentile([], 0.95) is None


def test_nfc_accents_preserved_and_empty_rejected():
    assert normalized_text("  algodo\u0301n  ") == "algodón"
    assert normalized_text("CAMISA") == "CAMISA"
    with pytest.raises(ValueError, match="EMPTY_TEXT"):
        normalized_text("\t\n")


def test_pinned_profile_rejects_prefix_and_revision_changes():
    validate_spec(MODEL_SPECS[0])
    for key, value in [("revision", "main"), ("query_prefix", ""), ("max_length", 1024)]:
        with pytest.raises(ValueError, match="preregistered"):
            validate_spec(MODEL_SPECS[0] | {key: value})


def write_json(path: Path, value: dict) -> str:
    path.write_text(json.dumps(value), encoding="utf-8")
    return hashlib.sha256(path.read_bytes()).hexdigest()


def frozen_fixture(tmp_path: Path):
    content = "Camisa de algodón"
    corpus = {
        "documents": [{
            "id": "doc", "title": "CAMISAS",
            "chunks": [{"id": "doc#definition", "section": "definition",
                        "content": content,
                        "content_hash": hashlib.sha256(content.encode()).hexdigest()}],
        }],
    }
    evaluation = {
        "queries": [{"id": "q", "text": "camisa", "language": "es",
                     "relevant_chunk_ids": ["doc#definition"],
                     "relevance": {"doc#definition": 2}}],
        "negatives": [{"id": "n", "text": "galaxia", "relevant_chunk_ids": [],
                       "relevance": {}}],
    }
    corpus_sha = write_json(tmp_path / "corpus.json", corpus)
    evaluation_sha = write_json(tmp_path / "evaluation.json", evaluation)
    protocol = {
        "corpus_path": "corpus.json", "corpus_sha256": corpus_sha,
        "evaluation_path": "evaluation.json", "evaluation_sha256": evaluation_sha,
    }
    return protocol, corpus, evaluation


def test_exact_input_is_title_section_content_and_content_hash_differs(tmp_path):
    protocol, _, _ = frozen_fixture(tmp_path)
    _, _, chunks = frozen_inputs(protocol, tmp_path)
    assert chunks[0]["text"] == "CAMISAS\ndefinition\nCamisa de algodón"
    assert chunks[0]["text_sha256"] != chunks[0]["content_hash"]


def test_tampered_corpus_fails_before_inference(tmp_path):
    protocol, _, _ = frozen_fixture(tmp_path)
    with (tmp_path / "corpus.json").open("a") as source:
        source.write(" ")
    with pytest.raises(ValueError, match="SHA256"):
        frozen_inputs(protocol, tmp_path)


def test_relevance_disagreement_fails_before_inference(tmp_path):
    protocol, _, evaluation = frozen_fixture(tmp_path)
    evaluation["queries"][0]["relevant_chunk_ids"] = []
    protocol["evaluation_sha256"] = write_json(tmp_path / "evaluation.json", evaluation)
    with pytest.raises(ValueError, match="disagree"):
        frozen_inputs(protocol, tmp_path)


def test_read_checked_requires_explicit_matching_digest(tmp_path):
    write_json(tmp_path / "file.json", {"version": 1})
    with pytest.raises(ValueError, match="SHA256"):
        read_checked(tmp_path / "file.json", "")
