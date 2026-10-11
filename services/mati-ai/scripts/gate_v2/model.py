"""Portable gate evaluator; no embeddings, labels, training or external calls."""
from __future__ import annotations
import hashlib
import json
import math
import os
import re
from pathlib import Path
from typing import Any

MODEL_VERSION = "mati-rag-gate-model-2"
FEATURE_VERSION = "mati-rag-gate-features-2"
NORMALIZATION_VERSION = "mati-rag-query-normalization-2"
ROUTING_VERSION = "mati-rag-routing-2"
CONTEXT_KEYS = ["corpus_sha256", "profile_sha256", "text_space_key",
                "feature_schema_sha256", "routing_rules_sha256", "runtime_bundle_sha256"]
TEXT_MODEL = {"model_id": "intfloat/multilingual-e5-small",
              "revision": "614241f622f53c4eeff9890bdc4f31cfecc418b3",
              "dimensions": 384, "normalization": "l2", "vector_space": "text"}
FEATURE_ORDER = [
    "vector_top1", "margin_1_2", "lexical_top1",
    "vector_lexical_agreement", "exact_taxonomy_name_match",
    "normalized_keyword_overlap",
]
CLASSES = ["RAG_ANSWERABLE", "TOOL_REQUIRED", "INSUFFICIENT_EVIDENCE"]
KINDS = ["threshold", "multi_signal", "logistic_regression"]
SHA_PATTERN = re.compile(r"^[0-9a-f]{64}$")

def finite(value: Any, name: str) -> float:
    if isinstance(value, bool) or not isinstance(value, (int, float)):
        raise ValueError(f"NON_NUMERIC:{name}")
    result = float(value)
    if not math.isfinite(result):
        raise ValueError(f"NON_FINITE:{name}")
    return result

def _pairs(pairs: list[tuple[str, Any]]) -> dict[str, Any]:
    result: dict[str, Any] = {}
    for key, value in pairs:
        if key in result:
            raise ValueError(f"DUPLICATE_JSON_KEY:{key}")
        result[key] = value
    return result

def read_json(path: Path) -> dict[str, Any]:
    def invalid_constant(value: str) -> Any:
        raise ValueError(f"INVALID_JSON_CONSTANT:{value}")
    result = json.loads(path.read_text(encoding="utf-8"), object_pairs_hook=_pairs,
                        parse_constant=invalid_constant)
    if not isinstance(result, dict):
        raise ValueError("JSON_ROOT_NOT_OBJECT")
    return result

def sha256_file(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()

def sha256_json(value: Any) -> str:
    encoded = json.dumps(value, ensure_ascii=False, sort_keys=True,
                         separators=(",", ":"), allow_nan=False).encode("utf-8")
    return hashlib.sha256(encoded).hexdigest()

def write_private(path: Path, value: Any, *, exclusive: bool = False) -> None:
    path.parent.mkdir(parents=True, exist_ok=True, mode=0o700)
    path.parent.chmod(0o700)
    flags = os.O_WRONLY | os.O_CREAT | (os.O_EXCL if exclusive else os.O_TRUNC)
    data = (json.dumps(value, ensure_ascii=False, indent=2, allow_nan=False) + "\n").encode()
    fd = os.open(path, flags, 0o600)
    try:
        with os.fdopen(fd, "wb") as stream:
            stream.write(data)
            stream.flush()
            os.fsync(stream.fileno())
        path.chmod(0o600)
    except BaseException:
        # Exclusive execution markers deliberately survive incomplete writes.
        raise

def checked_sha(value: Any, name: str) -> str:
    if not isinstance(value, str) or SHA_PATTERN.fullmatch(value) is None:
        raise ValueError(f"INVALID_SHA256:{name}")
    return value

def checked_file(spec: dict[str, Any], name: str) -> Path:
    path = Path(spec["path"]).resolve(strict=True)
    if not path.is_file():
        raise ValueError(f"NOT_FILE:{name}")
    if sha256_file(path) != checked_sha(spec["sha256"], name):
        raise ValueError(f"HASH_MISMATCH:{name}")
    return path

def features(values: Any, order: Any = FEATURE_ORDER) -> list[float]:
    if order != FEATURE_ORDER or not isinstance(values, dict):
        raise ValueError("FEATURE_ORDER_OR_SHAPE")
    if list(values) != FEATURE_ORDER:
        raise ValueError("FEATURE_KEYS_OR_ORDER")
    result = [finite(values[key], key) for key in FEATURE_ORDER]
    for index, lower, upper in [(0, -1.000001, 1.000001), (1, 0.0, 2.000002),
                                (3, 0.0, 1.0), (5, 0.0, 1.0)]:
        if not lower <= result[index] <= upper:
            raise ValueError(f"FEATURE_RANGE:{FEATURE_ORDER[index]}")
    if result[2] < 0 or result[4] not in (0.0, 1.0):
        raise ValueError("FEATURE_RANGE:lexical_or_exact")
    return result

def _array(value: Any, name: str, *, positive: bool = False) -> list[float]:
    if not isinstance(value, list) or len(value) != len(FEATURE_ORDER):
        raise ValueError(f"ARRAY_DIMENSIONS:{name}")
    result = [finite(item, name) for item in value]
    if positive and any(item <= 0 for item in result):
        raise ValueError(f"NON_POSITIVE:{name}")
    return result

def validate_context(context: Any) -> dict[str, str]:
    if not isinstance(context, dict) or set(context) != set(CONTEXT_KEYS):
        raise ValueError("MODEL_CONTEXT_KEYS")
    for key in CONTEXT_KEYS:
        checked_sha(context[key], key)
    return context

def validate_model(model: dict[str, Any], expected_context: Any = None) -> dict[str, Any]:
    if model.get("schema_version") != MODEL_VERSION:
        raise ValueError("MODEL_SCHEMA_VERSION")
    if model.get("feature_schema_version") != FEATURE_VERSION:
        raise ValueError("MODEL_FEATURE_VERSION")
    if model.get("normalization_version") != NORMALIZATION_VERSION:
        raise ValueError("MODEL_NORMALIZATION_VERSION")
    if model.get("feature_order") != FEATURE_ORDER:
        raise ValueError("MODEL_FEATURE_ORDER")
    if model.get("routing_version") != ROUTING_VERSION:
        raise ValueError("MODEL_ROUTING_VERSION")
    if model.get("text_model") != TEXT_MODEL:
        raise ValueError("MODEL_TEXT_IDENTITY")
    validate_context(model.get("context"))
    if expected_context is not None:
        validate_context(expected_context)
        if model["context"] != expected_context:
            raise ValueError("MODEL_CONTEXT_MISMATCH")
    if not isinstance(model.get("policy_version"), str) or re.fullmatch(r"[a-zA-Z0-9._-]{1,100}", model["policy_version"]) is None:
        raise ValueError("MODEL_POLICY_VERSION")
    if model.get("policy_kind") not in KINDS:
        raise ValueError("POLICY_KIND")
    if not isinstance(model.get("candidate_id"), str) or not model["candidate_id"]:
        raise ValueError("CANDIDATE_ID")
    p = model.get("parameters")
    if not isinstance(p, dict):
        raise ValueError("PARAMETERS")
    kind = model["policy_kind"]
    if kind == "threshold":
        if set(p) != {"threshold"} or not -1 <= finite(p["threshold"], "threshold") <= 1:
            raise ValueError("THRESHOLD_PARAMETERS")
    elif kind == "multi_signal":
        expected = {"vector_min", "lexical_min", "lexical_operator",
                    "overlap_min", "agreement_min"}
        if set(p) != expected or p["lexical_operator"] != "gt":
            raise ValueError("MULTI_PARAMETERS")
        if not -1 <= finite(p["vector_min"], "vector_min") <= 1:
            raise ValueError("MULTI_VECTOR_RANGE")
        if finite(p["lexical_min"], "lexical_min") != 0:
            raise ValueError("MULTI_LEXICAL_RANGE")
        if abs(finite(p["agreement_min"], "agreement_min") - 1/3) > 1e-12:
            raise ValueError("MULTI_AGREEMENT_FIXED")
        for key in ("overlap_min", "agreement_min"):
            if not 0 <= finite(p[key], key) <= 1:
                raise ValueError(f"MULTI_RANGE:{key}")
    else:
        expected = {"coefficients", "intercept", "scaler_mean",
                    "scaler_scale", "threshold"}
        if set(p) != expected:
            raise ValueError("LR_PARAMETERS")
        _array(p["coefficients"], "coefficients")
        _array(p["scaler_mean"], "scaler_mean")
        _array(p["scaler_scale"], "scaler_scale", positive=True)
        finite(p["intercept"], "intercept")
        if not 0 <= finite(p["threshold"], "threshold") <= 1:
            raise ValueError("LR_THRESHOLD_RANGE")
    return model

def evaluate_binary(model: dict[str, Any], values: dict[str, Any],
                    order: Any = FEATURE_ORDER) -> dict[str, Any]:
    validate_model(model)
    vector = features(values, order)
    p = model["parameters"]
    kind = model["policy_kind"]
    if kind == "threshold":
        score = vector[0]
        accept = score >= p["threshold"]
        score_kind = "cosine_similarity_uncalibrated"
    elif kind == "multi_signal":
        accept = vector[0] >= p["vector_min"] and (
            vector[4] == 1 or (
                vector[2] > p["lexical_min"]
                and vector[5] >= p["overlap_min"]
                and vector[3] >= p["agreement_min"]))
        score = float(accept)
        score_kind = "deterministic_rule_indicator"
    else:
        terms = [
            ((value - mean) / scale) * coefficient
            for value, mean, scale, coefficient in zip(
                vector, p["scaler_mean"], p["scaler_scale"], p["coefficients"],
                strict=True)]
        logit = math.fsum([p["intercept"], *terms])
        if not math.isfinite(logit):
            raise ValueError("NON_FINITE_LR_LOGIT")
        if logit >= 0:
            score = 1.0 / (1.0 + math.exp(-logit))
        else:
            exp_logit = math.exp(logit)
            score = exp_logit / (1.0 + exp_logit)
        accept = score >= p["threshold"]
        score_kind = "answerability_score_uncalibrated"
    return {"accepted": bool(accept), "answerability_score": score,
            "score_kind": score_kind}

def routed_decision(binary: dict[str, Any], router: Any,
                    *, chunks_available: bool) -> str:
    if isinstance(router, dict):
        router = router.get("route", router.get("decision"))
    router = {"tool": "TOOL_REQUIRED", "required_information": "INSUFFICIENT_EVIDENCE"}.get(router, router)
    if router not in ("candidate_rag", "TOOL_REQUIRED", "INSUFFICIENT_EVIDENCE"):
        raise ValueError("ROUTER_DECISION")
    if router in ("TOOL_REQUIRED", "INSUFFICIENT_EVIDENCE"):
        return str(router)
    if not chunks_available:
        return "INSUFFICIENT_EVIDENCE"
    return "RAG_ANSWERABLE" if binary["accepted"] else "INSUFFICIENT_EVIDENCE"

def base_model(kind: str, candidate_id: str, parameters: dict[str, Any],
               context: dict[str, Any], metadata: dict[str, Any]) -> dict[str, Any]:
    return validate_model({
        "schema_version": MODEL_VERSION,
        "feature_schema_version": FEATURE_VERSION,
        "normalization_version": NORMALIZATION_VERSION,
        "routing_version": ROUTING_VERSION,
        "policy_version": f"rag-gate-v2.{candidate_id}",
        "text_model": TEXT_MODEL.copy(),
        "feature_order": FEATURE_ORDER.copy(),
        "candidate_id": candidate_id, "policy_kind": kind,
        "parameters": parameters, "context": context, "metadata": metadata,
        "approved": False,
    })
