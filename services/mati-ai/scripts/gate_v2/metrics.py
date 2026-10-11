"""Point metrics and Wilson intervals; labels are consumed only for reporting."""
from __future__ import annotations
import math
import time
from collections import Counter
from typing import Any
from model import CLASSES, FEATURE_ORDER, evaluate_binary, routed_decision, validate_model

NEGATIVE_SUBGROUPS = ["tool_required", "unsupported_in_domain", "ood", "ambiguous"]

def negative_subgroup(row: dict[str, Any]) -> str | None:
    label = row["ground_truth_class"]
    if label == "TOOL_REQUIRED":
        return "tool_required"
    if label == "RAG_ANSWERABLE":
        return None
    family = row.get("semantic_family")
    mapping = {"unsupported_in_domain": "unsupported_in_domain",
               "out_of_domain": "ood", "ambiguous_query": "ambiguous"}
    if family not in mapping:
        raise ValueError(f"UNKNOWN_NEGATIVE_SUBGROUP:{family}")
    return mapping[family]

def wilson(successes: int, denominator: int) -> dict[str, Any]:
    if not 0 <= successes <= denominator:
        raise ValueError("WILSON_COUNTS")
    if denominator == 0:
        return {"successes": successes, "denominator": denominator,
                "estimate": None, "low": None, "high": None,
                "method": "wilson", "confidence_level": 0.95}
    z = 1.959963984540054
    proportion = successes / denominator
    divisor = 1 + z * z / denominator
    center = (proportion + z * z / (2 * denominator)) / divisor
    half = z * math.sqrt(proportion * (1 - proportion) / denominator
                         + z * z / (4 * denominator**2)) / divisor
    return {"successes": successes, "denominator": denominator,
            "estimate": proportion, "low": max(0.0, center-half),
            "high": min(1.0, center+half), "method": "wilson",
            "confidence_level": 0.95}

def _ratio(numerator: int, denominator: int) -> float | None:
    return numerator / denominator if denominator else None

def _document_id(entry: dict[str, Any]) -> str:
    value = entry.get("document_key", entry.get("document_id"))
    if value is None and isinstance(entry.get("metadata"), dict):
        value = entry["metadata"].get("document_id", entry["metadata"].get("document_key"))
    if not isinstance(value, str) or not value:
        raise ValueError("VECTOR_DOCUMENT_ID_MISSING")
    return value

def predictions(model: dict[str, Any], rows: list[dict[str, Any]]) -> list[dict[str, Any]]:
    result = []
    for row in rows:
        validate_model(model, row.get("runtime_context"))
        label = row.get("ground_truth_class")
        if label not in CLASSES:
            raise ValueError("UNKNOWN_GROUND_TRUTH_CLASS")
        if row.get("schema_version") not in (None, "mati-rag-gate-features-2"):
            raise ValueError("ROW_FEATURE_VERSION")
        start = time.perf_counter_ns()
        binary = evaluate_binary(model, row["features"],
                                 row.get("feature_order", FEATURE_ORDER))
        top3 = row.get("vector_top3")
        if not isinstance(top3, list) or len(top3) > 3:
            raise ValueError("VECTOR_TOP3_SHAPE")
        documents = {_document_id(entry) for entry in top3}
        expected = row.get("expected_document_ids")
        if not isinstance(expected, list) or any(not isinstance(x, str) for x in expected):
            raise ValueError("EXPECTED_DOCUMENT_IDS")
        expected_set = set(expected)
        if label == "RAG_ANSWERABLE" and not expected_set:
            raise ValueError("POSITIVE_WITHOUT_SUPPORTING_DOCUMENTS")
        route = routed_decision(binary, row["router_decision"],
                                chunks_available=bool(top3))
        gate_ms = (time.perf_counter_ns()-start) / 1_000_000
        result.append({
            "id": row["id"], "ground_truth_class": label,
            "predicted_class": route, "raw_binary_accepted": binary["accepted"],
            "operational_accepted": route == "RAG_ANSWERABLE",
            "answerability_score": binary["answerability_score"],
            "operational_score": (binary["answerability_score"]
                                   if (row["router_decision"].get("route", row["router_decision"].get("decision"))
                                       if isinstance(row["router_decision"], dict)
                                       else row["router_decision"]) == "candidate_rag"
                                   else -1.0),
            "score_kind": binary["score_kind"],
            "positive": label == "RAG_ANSWERABLE",
            "negative_subgroup": negative_subgroup(row),
            "language": row.get("language"), "difficulty": row.get("difficulty"),
            "group_id": row.get("group_id"),
            "supporting_document_hit": bool(expected_set & documents),
            "expected_document_set_recall_at_3": (
                len(expected_set & documents)/len(expected_set)
                if expected_set else None),
            "expected_document_count": len(expected_set),
            "retrieved_expected_document_count": len(expected_set & documents),
            "gate_ms": gate_ms,
        })
    return result

def binary_metrics(rows: list[dict[str, Any]], *, raw: bool = False) -> dict[str, Any]:
    key = "raw_binary_accepted" if raw else "operational_accepted"
    tp = sum(r["positive"] and r[key] for r in rows)
    fn = sum(r["positive"] and not r[key] for r in rows)
    fp = sum(not r["positive"] and r[key] for r in rows)
    tn = sum(not r["positive"] and not r[key] for r in rows)
    n = len(rows)
    precision = _ratio(tp, tp+fp)
    recall = _ratio(tp, tp+fn)
    specificity = _ratio(tn, tn+fp)
    f1 = _ratio(2*tp, 2*tp+fp+fn)
    denominator = math.sqrt((tp+fp)*(tp+fn)*(tn+fp)*(tn+fn))
    mcc = (tp*tn-fp*fn)/denominator if denominator else None
    auroc = average_precision = None
    if tp+fn and tn+fp:
        from sklearn.metrics import average_precision_score, roc_auc_score
        y = [int(r["positive"]) for r in rows]
        scores = [r["answerability_score" if raw else "operational_score"] for r in rows]
        auroc = float(roc_auc_score(y, scores))
        average_precision = float(average_precision_score(y, scores))
    accepted_positive = [r for r in rows if r["positive"] and r[key]]
    hits = sum(r["supporting_document_hit"] for r in accepted_positive)
    fractional_sum = sum(r["expected_document_set_recall_at_3"] for r in accepted_positive)
    expected_count = sum(r["expected_document_count"] for r in accepted_positive)
    retrieved_expected_count = sum(r["retrieved_expected_document_count"] for r in accepted_positive)
    intervals = {
        "accuracy": wilson(tp+tn, n), "precision": wilson(tp, tp+fp),
        "answerable_recall": wilson(tp, tp+fn),
        "negative_rejection": wilson(tn, tn+fp), "false_accept_rate": wilson(fp, tn+fp),
        "false_reject_rate": wilson(fn, tp+fn),
        "accepted_positive_query_hit_recall_at_3": wilson(hits, len(accepted_positive)),
    }
    return {
        "n": n, "positive_n": tp+fn, "negative_n": tn+fp,
        "tp": tp, "fn": fn, "fp": fp, "tn": tn,
        "accuracy": _ratio(tp+tn, n),
        "balanced_accuracy": ((recall+specificity)/2
                              if recall is not None and specificity is not None else None),
        "precision": precision, "answerable_recall": recall, "f1": f1,
        "negative_rejection": specificity, "false_accept_rate": _ratio(fp, tn+fp),
        "false_reject_rate": _ratio(fn, tp+fn), "mcc": mcc,
        "auroc": auroc, "pr_auc_average_precision": average_precision,
        "pr_auc_definition": "sklearn average_precision_score step-integral; not trapezoidal PR area.",
        "score_note": "Multi_signal uses coarse binary rule scores, not calibrated probabilities.",
        "accepted_positive_n": len(accepted_positive),
        "accepted_positive_query_hit_recall_at_3": _ratio(hits, len(accepted_positive)),
        "accepted_positive_hit_count": hits,
        "macro_expected_document_set_recall_at_3": (
            fractional_sum/len(accepted_positive) if accepted_positive else None),
        "fractional_recall_sum": fractional_sum,
        "accepted_expected_document_count": expected_count,
        "accepted_retrieved_expected_document_count": retrieved_expected_count,
        "pooled_expected_document_coverage": _ratio(retrieved_expected_count, expected_count),
        "wilson_95": intervals,
    }

def report(model: dict[str, Any], rows: list[dict[str, Any]]) -> dict[str, Any]:
    items = predictions(model, rows)
    matrix = {actual: {predicted: 0 for predicted in CLASSES} for actual in CLASSES}
    for item in items:
        matrix[item["ground_truth_class"]][item["predicted_class"]] += 1
    negatives = {
        group: binary_metrics([r for r in items if r["negative_subgroup"] == group])
        for group in NEGATIVE_SUBGROUPS}
    languages = {
        str(language): binary_metrics([r for r in items if r["language"] == language])
        for language in sorted({r["language"] for r in items if r["language"] is not None})}
    difficulties = {
        str(difficulty): binary_metrics([r for r in items if r["difficulty"] == difficulty])
        for difficulty in sorted({r["difficulty"] for r in items
                                  if r["difficulty"] is not None})}
    timings = sorted(r["gate_ms"] for r in items)
    def percentile(p: float) -> float | None:
        if not timings:
            return None
        index = (len(timings)-1)*p
        lower, upper = math.floor(index), math.ceil(index)
        return timings[lower] + (timings[upper]-timings[lower])*(index-lower)
    return {
        "candidate_id": model["candidate_id"], "policy_kind": model["policy_kind"],
        "operational": binary_metrics(items), "raw_binary": binary_metrics(items, raw=True),
        "negative_subgroups": negatives, "languages": languages,
        "difficulty_subgroups": difficulties, "three_class_confusion_matrix": matrix,
        "three_class_accuracy": _ratio(sum(matrix[c][c] for c in CLASSES), len(items)),
        "class_counts": dict(Counter(r["ground_truth_class"] for r in items)),
        "group_count": len({r["group_id"] for r in items}),
        "gate_latency_ms": {"p50": percentile(.50), "p95": percentile(.95),
                            "n": len(items),
                            "scope": "Python portable gate+validation+support-id reporting; not HTTP E2E."},
        "predictions": items,
    }

def check_gates(result: dict[str, Any], protocol: dict[str, Any]) -> dict[str, Any]:
    limits = protocol["gates"]
    m = result["operational"]
    checks: dict[str, bool] = {}
    for key, limit in [
        ("answerable_recall", "answerable_recall_min"),
        ("negative_rejection", "negative_rejection_min"),
        ("f1", "f1_min"),
        ("accepted_positive_query_hit_recall_at_3", "accepted_positive_recall_at_3_min"),
    ]:
        checks[key] = m[key] is not None and m[key] >= limits[limit]
    checks["false_accept_rate"] = (
        m["false_accept_rate"] is not None
        and m["false_accept_rate"] <= limits["false_accept_rate_max"])
    for group in limits["required_negative_subgroups"]:
        rate = result["negative_subgroups"][group]["negative_rejection"]
        checks[f"negative_subgroup:{group}"] = (
            rate is not None and rate >= limits["negative_subgroup_rejection_min"])
    return {"passed": all(checks.values()), "checks": checks,
            "point_estimates_only": True, "confidence_intervals_informational": True}
