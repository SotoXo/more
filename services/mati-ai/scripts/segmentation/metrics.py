"""Frozen-denominator metrics for the isolated target-garment experiment."""
from __future__ import annotations

import math
from typing import Any
import numpy as np
from sklearn.metrics import confusion_matrix, precision_recall_fscore_support


def classification_metrics(rows: list[dict], predictions: dict[int, dict | None]) -> dict[str, Any]:
    """Ranking metrics retain missing targets as no prediction; thresholds stay separate."""
    labels = sorted({r["category_id"] for r in rows})
    actual = [r["category_id"] for r in rows]
    inferred, top3, formal = [], [], []
    for row in rows:
        result = predictions.get(row["image_id"])
        ranked = result.get("top_k", []) if result else []
        inferred.append(ranked[0]["category_id"] if ranked else -1)
        top3.append(row["category_id"] in [r["category_id"] for r in ranked[:3]])
        formal.append(bool(ranked and not result.get("abstained", True)))
    n = len(rows)
    if not n:
        return {"n": 0, "top1": None, "top3": None, "macro_precision": None,
                "macro_recall": None, "macro_f1": None, "weighted_f1": None,
                "formal_coverage": None, "abstention_rate": None}
    precision, recall, f1, support = precision_recall_fscore_support(
        actual, inferred, labels=labels, zero_division=0
    )
    # The extra prediction column is essential: missing targets never vanish
    # from the confusion matrix or the class supports.
    extra_predictions = sorted(set(inferred) - set(labels) - {-1})
    prediction_labels = labels + extra_predictions + [-1]
    matrix = confusion_matrix(actual, inferred, labels=prediction_labels)
    return {
        "n": n, "ranked_n": sum(v != -1 for v in inferred),
        "top1": sum(a == b for a, b in zip(actual, inferred)) / n,
        "top3": sum(top3) / n,
        "macro_precision": float(np.mean(precision)),
        "macro_recall": float(np.mean(recall)),
        "macro_f1": float(np.mean(f1)),
        "weighted_f1": float(np.average(f1, weights=support)),
        "formal_coverage": sum(formal) / n,
        "formal_covered_n": sum(formal), "abstention_rate": 1 - sum(formal) / n,
        "missing_target_n": sum(v == -1 for v in inferred),
        "class_labels": labels, "confusion_prediction_labels": labels + extra_predictions + ["no_prediction"],
        "confusion_matrix": matrix[:len(labels)].tolist(),
        "per_class": [{"id": int(label), "precision": float(p), "recall": float(r),
                       "f1": float(f), "support": int(s)}
                      for label, p, r, f, s in zip(labels, precision, recall, f1, support)],
    }


def retrieval_metrics(rows: list[dict], vectors: dict[int, list[float]]) -> dict[str, Any]:
    """Same-profile exact cosine. Missing queries score zero, gallery relevance stays frozen."""
    present = [r for r in rows if r["image_id"] in vectors]
    if present:
        values = np.asarray([vectors[r["image_id"]] for r in present], dtype=np.float64)
        norms = np.linalg.norm(values, axis=1, keepdims=True)
        if values.shape[1] != 768 or not np.isfinite(values).all() or (norms <= 0).any():
            raise ValueError("INVALID_EXPERIMENTAL_EMBEDDINGS")
        values = values / norms
        similarities = values @ values.T
    else:
        similarities = np.empty((0, 0))
    index = {r["image_id"]: i for i, r in enumerate(present)}
    result = []
    for row in rows:
        complete_gallery = [r for r in rows if
                            r["prenda_id"] != row["prenda_id"] and r["sha256"] != row["sha256"]]
        relevant_n = sum(r["category_id"] == row["category_id"] for r in complete_gallery)
        query = {"image_id": row["image_id"], "relevant_n": relevant_n,
                 "gallery_complete_n": len(complete_gallery), "query_available": row["image_id"] in index}
        rank = []
        if row["image_id"] in index:
            rank = sorted(
                [(r, float(similarities[index[row["image_id"]], index[r["image_id"]]]))
                 for r in present if r["prenda_id"] != row["prenda_id"] and r["sha256"] != row["sha256"]],
                key=lambda item: (-item[1], item[0]["image_id"]),
            )
        relevant = [item[0]["category_id"] == row["category_id"] for item in rank]
        first = next((i + 1 for i, value in enumerate(relevant) if value), None)
        query["mrr"] = 1 / first if first else 0.0
        ideal = sum(1 / math.log2(i + 2) for i in range(min(10, relevant_n)))
        query["ndcg10"] = sum(bool(v) / math.log2(i + 2) for i, v in enumerate(relevant[:10])) / ideal if ideal else 0.0
        query["gallery_available_n"] = len(rank)
        query["top10"] = [{"image_id": r["image_id"], "cosine": score,
                           "relevant": r["category_id"] == row["category_id"]} for r, score in rank[:10]]
        for k in (1, 5, 10):
            query[f"recall{k}"] = sum(relevant[:k]) / relevant_n if relevant_n else 0.0
            query[f"hit{k}"] = float(any(relevant[:k]))
        result.append(query)
    fields = ["mrr", "ndcg10"] + [f"{metric}{k}" for metric in ("recall", "hit") for k in (1, 5, 10)]
    return {"n_queries": len(rows), "n_available_queries": len(present), "n_vectors": len(present),
            "query_coverage": len(present) / len(rows) if rows else None,
            "relevance": "same_category_proxy_different_product_and_sha",
            "missing_query_metrics": "zero; denominator preserved",
            "gallery_policy": "same_preprocess_only; frozen complete-gallery relevance denominator",
            "metrics": {key: float(np.mean([r[key] for r in result])) if result else None for key in fields},
            "queries": result}


def percentiles(values: list[float]) -> dict:
    return {"n": len(values), "p50": float(np.percentile(values, 50)) if values else None,
            "p95": float(np.percentile(values, 95)) if values else None,
            "mean": float(np.mean(values)) if values else None}


def color_metrics(rows: list[dict], observations: dict[int, dict | None]) -> dict:
    """Commercial swatches are proxies; prospective candidates never authorize catalog IDs."""
    n = len(rows)
    palette_n = primary_n = candidate_n = correct_n = family_n = family_correct_n = 0
    distances = []
    detail = []
    for row in rows:
        value = observations.get(row["image_id"])
        palette = value.get("palette", []) if value else []
        primary = value.get("primary_color") if value else None
        palette_n += bool(palette)
        primary_n += primary is not None
        proxy = value.get("proxy", {}) if value else {}
        candidate = proxy.get("prospective_primary_catalog_id")
        candidate_n += candidate is not None
        label_ids = {r["id_color"] for r in row["color_labels"]}
        correct = candidate in label_ids if candidate is not None else False
        correct_n += correct
        distance = proxy.get("primary_to_commercial_swatch_delta_e00")
        if distance is not None:
            distances.append(distance)
        if proxy.get("observed_family") is not None and proxy.get("catalog_label_family") is not None:
            family_n += 1
            family_correct_n += proxy["observed_family"] == proxy["catalog_label_family"]
        detail.append({"image_id": row["image_id"], "palette_available": bool(palette),
                       "primary_available": primary is not None, "prospective_candidate": candidate,
                       "proxy_label_ids": sorted(label_ids), "proxy_correct": correct,
                       "primary_delta_e00": distance})
    return {
        "n": n, "palette_n": palette_n, "palette_coverage": palette_n / n if n else None,
        "primary_n": primary_n, "primary_coverage": primary_n / n if n else None,
        "prospective_id_candidate_n": candidate_n,
        "prospective_id_coverage": candidate_n / n if n else None,
        "exact_proxy_correct_n": correct_n, "exact_proxy_correct_all_n": correct_n / n if n else None,
        "conditional_proxy_id_accuracy": correct_n / candidate_n if candidate_n else None,
        "family_n": family_n, "family_correct_n": family_correct_n,
        "family_accuracy_conditional": family_correct_n / family_n if family_n else None,
        "family_correct_all_n": family_correct_n / n if n else None,
        "delta_e00": {"n": len(distances), "mean": float(np.mean(distances)) if distances else None,
                      "median": float(np.median(distances)) if distances else None,
                      "p90": float(np.percentile(distances, 90)) if distances else None},
        "applicable_commercial_id_coverage": 0.0,
        "commercial_guard": "target_garment_identity_unverified; never removed by benchmark",
        "details": detail,
    }

