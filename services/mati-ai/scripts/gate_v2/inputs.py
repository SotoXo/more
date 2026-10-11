"""Read-only input boundaries and hash seals for the offline V2 benchmark."""
from __future__ import annotations
import hashlib
from pathlib import Path
from typing import Any
from model import FEATURE_ORDER, FEATURE_VERSION, checked_sha, features, read_json, sha256_file, validate_context

REPO = Path(__file__).resolve().parents[4]
ARTIFACT_ROOT = REPO / "storage/app/ai-rag-gate-v2"
PROTOCOL_PATH = Path(__file__).with_name("protocol.json")

def load_preregistration() -> tuple[dict[str, Any], dict[str, Any]]:
    seal = read_json(ARTIFACT_ROOT / "preregistration-seal.json")
    if seal.get("before_dataset_inference") is not True:
        raise ValueError("PREREGISTRATION_NOT_FROZEN")
    protocol_sha = checked_sha(seal.get("protocol_sha256"), "protocol")
    if sha256_file(PROTOCOL_PATH) != protocol_sha:
        raise ValueError("PROTOCOL_HASH_MISMATCH")
    protocol = read_json(PROTOCOL_PATH)
    if protocol.get("schema_version") != "mati-rag-gate-protocol-2":
        raise ValueError("PROTOCOL_VERSION")
    return seal, protocol

def canonical_split(value: Any) -> str:
    mapping = {"TRAIN": "train", "CALIBRATION": "cal", "CAL": "cal",
               "HOLDOUT": "holdout"}
    if not isinstance(value, str):
        raise ValueError("SPLIT_TYPE")
    result = mapping.get(value.upper())
    if result is None:
        raise ValueError("UNKNOWN_SPLIT")
    return result

def read_author_queries(path: Path, expected_sha: str,
                        allowed_splits: set[str]) -> list[dict[str, Any]]:
    if path.resolve().parent != ARTIFACT_ROOT.resolve():
        raise ValueError("AUTHOR_INPUT_PATH_OUTSIDE_FROZEN_ROOT")
    if sha256_file(path) != checked_sha(expected_sha, "author_queries"):
        raise ValueError("AUTHOR_QUERY_HASH_MISMATCH")
    payload = read_json(path)
    rows = payload.get("queries")
    if not isinstance(rows, list) or not rows:
        raise ValueError("AUTHOR_QUERIES_EMPTY_OR_SHAPE")
    ids = set()
    groups: dict[str, str] = {}
    normalized_rows = []
    for raw_row in rows:
        if not isinstance(raw_row, dict):
            raise ValueError("AUTHOR_ROW_SHAPE")
        row = raw_row.copy()
        row["split"] = canonical_split(row.get("split"))
        if row["split"] not in allowed_splits:
            raise ValueError("FORBIDDEN_OR_MISSING_SPLIT")
        identifier = row.get("id")
        if not isinstance(identifier, str) or not identifier or identifier in ids:
            raise ValueError("AUTHOR_QUERY_ID")
        ids.add(identifier)
        group = row.get("group_id")
        if not isinstance(group, str) or not group:
            raise ValueError("AUTHOR_GROUP_ID")
        if group in groups and groups[group] != row["split"]:
            raise ValueError("GROUP_CROSSES_ALLOWED_SPLITS")
        groups[group] = row["split"]
        if not isinstance(row.get("query"), str) or not row["query"].strip():
            raise ValueError("AUTHOR_QUERY_TEXT")
        normalized_rows.append(row)
    return normalized_rows

def read_feature_export(path: Path, expected_sha: str) -> dict[str, Any]:
    if path.resolve().parent != ARTIFACT_ROOT.resolve():
        raise ValueError("FEATURE_INPUT_PATH_OUTSIDE_FROZEN_ROOT")
    if sha256_file(path) != checked_sha(expected_sha, "features"):
        raise ValueError("FEATURE_EXPORT_HASH_MISMATCH")
    payload = read_json(path)
    if payload.get("feature_schema_version") != FEATURE_VERSION:
        raise ValueError("EXPORT_FEATURE_SCHEMA_VERSION")
    if payload.get("feature_order") != FEATURE_ORDER:
        raise ValueError("EXPORT_FEATURE_ORDER")
    if not isinstance(payload.get("rows"), list) or not payload["rows"]:
        raise ValueError("EXPORT_ROWS")
    if payload.get("passed") is not True or "failure" not in payload or payload["failure"] is not None:
        raise ValueError("EXPORT_UNSUCCESSFUL")
    checked_sha(payload.get("input_sha256"), "feature_export_author_input")
    http = payload.get("http")
    if not isinstance(http, list) or len(http) != len(payload["rows"]):
        raise ValueError("EXPORT_HTTP_COUNT")
    ids = [row.get("id") for row in payload["rows"]]
    http_ids = []
    for response in http:
        if not isinstance(response, dict) or type(response.get("status")) is not int or response["status"] != 200:
            raise ValueError("EXPORT_HTTP_NOT_200")
        if response.get("endpoint") != "/api/v1/rag/embed":
            raise ValueError("EXPORT_HTTP_ENDPOINT")
        http_ids.append(response.get("query_id"))
    if len(set(ids)) != len(ids) or len(set(http_ids)) != len(http_ids) or set(ids) != set(http_ids):
        raise ValueError("EXPORT_HTTP_QUERY_IDS")
    payload["_validation"] = {"passed": True, "failure": None,
                              "http_responses": len(http), "all_http_200": True,
                              "no_500": True, "complete_one_response_per_query": True}
    return payload

def join_rows(author_rows: list[dict[str, Any]], export: dict[str, Any]) -> list[dict[str, Any]]:
    exported = {}
    for item in export["rows"]:
        identifier = item.get("id")
        if not isinstance(identifier, str) or identifier in exported:
            raise ValueError("EXPORTED_DUPLICATE_OR_MISSING_ID")
        exported[identifier] = item
    if set(exported) != {row["id"] for row in author_rows}:
        raise ValueError("EXPORTED_QUERY_IDS_NOT_EXACT")
    merged = []
    for author in author_rows:
        item = exported[author["id"]]
        query_sha = hashlib.sha256(author["query"].encode("utf-8")).hexdigest()
        if item.get("query_sha256") != query_sha:
            raise ValueError("QUERY_SHA256_MISMATCH")
        if item.get("feature_schema_version", FEATURE_VERSION) != FEATURE_VERSION:
            raise ValueError("ROW_FEATURE_SCHEMA_VERSION")
        if item.get("feature_order", FEATURE_ORDER) != FEATURE_ORDER:
            raise ValueError("ROW_FEATURE_ORDER")
        features(item.get("features"), item.get("feature_order", FEATURE_ORDER))
        for key in ("ground_truth_class", "split", "expected_document_ids",
                    "expected_chunk_ids", "semantic_family", "difficulty", "group_id"):
            actual = canonical_split(item[key]) if key == "split" and key in item else item.get(key)
            if key in item and actual != author[key]:
                raise ValueError(f"AUTHOR_METADATA_MISMATCH:{key}")
        row = author.copy()
        row.update({key: item[key] for key in
                    ("features", "router_decision", "vector_top3", "lexical_top3")})
        row["feature_order"] = FEATURE_ORDER.copy()
        row["schema_version"] = FEATURE_VERSION
        row["timing"] = item.get("timing", {})
        row["runtime_context"] = runtime_context(export)
        merged.append(row)
    return merged

def runtime_context(export: dict[str, Any]) -> dict[str, Any]:
    context = export.get("context")
    if not isinstance(context, dict):
        raise ValueError("EXPORT_CONTEXT")
    validate_context(context)
    return context.copy()

def source_hashes() -> dict[str, str]:
    return {path.name: sha256_file(path)
            for path in sorted(Path(__file__).parent.glob("*.py"))}
