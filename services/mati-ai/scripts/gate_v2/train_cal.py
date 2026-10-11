"""Fit one fixed LR and select frozen representatives using TRAIN/CAL only."""
from __future__ import annotations
import argparse
import datetime
import importlib.metadata
import sys
import warnings
from pathlib import Path
from typing import Any
from inputs import (ARTIFACT_ROOT, join_rows, load_preregistration, read_author_queries,
                    read_feature_export, runtime_context, source_hashes)
from metrics import check_gates, report
from model import ( base_model, evaluate_binary, features, sha256_file,
                   sha256_json, write_private)

def rank(candidate: dict[str, Any], *, diagnostic: bool = False) -> tuple[Any, ...]:
    m = candidate["cal"]["operational"]
    p = candidate["model"]["parameters"]
    conservative = p.get("threshold", p.get("vector_min", 0))
    tie = (-conservative, -p.get("overlap_min", 0), candidate["model"]["candidate_id"])
    if diagnostic:
        return (-float(m["f1"] or 0), -float(m["answerable_recall"] or 0),
                float(m["false_accept_rate"] if m["false_accept_rate"] is not None else 1), *tie)
    return (float(m["false_accept_rate"] if m["false_accept_rate"] is not None else 1),
            -float(m["answerable_recall"] or 0), -float(m["f1"] or 0), *tie)

def select_representative(candidates: list[dict[str, Any]]) -> tuple[dict[str, Any], str]:
    eligible = [candidate for candidate in candidates if candidate["cal_gates"]["passed"]]
    if eligible:
        return min(eligible, key=rank), "cal_eligible"
    high_recall = [candidate for candidate in candidates
                   if (candidate["cal"]["operational"]["answerable_recall"] or 0) >= .90]
    if high_recall:
        return min(high_recall, key=rank), "diagnostic_recall_at_least_0.90"
    return min(candidates, key=lambda item: rank(item, diagnostic=True)), "diagnostic_f1"

def fit_models(train: list[dict[str, Any]], cal: list[dict[str, Any]],
               protocol: dict[str, Any], context: dict[str, Any],
               metadata: dict[str, Any]) -> tuple[list[dict[str, Any]], dict[str, Any]]:
    import numpy as np
    from sklearn.exceptions import ConvergenceWarning
    from sklearn.linear_model import LogisticRegression
    from sklearn.preprocessing import StandardScaler
    models = []
    for threshold in protocol["threshold"]["thresholds"]:
        identifier = f"threshold_t{round(threshold*100):03d}"
        models.append(base_model("threshold", identifier, {"threshold": threshold}, context, metadata))
    multi = protocol["multi_signal"]
    for vector_min in multi["vector_min"]:
        for overlap_min in multi["overlap_min"]:
            identifier = f"multi_v{round(vector_min*100):03d}_o{round(overlap_min*100):03d}"
            parameters = {"vector_min": vector_min, "lexical_min": multi["lexical_min"],
                          "lexical_operator": multi["lexical_operator"],
                          "overlap_min": overlap_min, "agreement_min": multi["agreement_min"]}
            models.append(base_model("multi_signal", identifier, parameters, context, metadata))
    config = protocol["logistic_regression"]
    x = np.array([features(row["features"]) for row in train], dtype=np.float64)
    y = np.array([row["ground_truth_class"] == "RAG_ANSWERABLE" for row in train], dtype=np.int64)
    if set(y.tolist()) != {0, 1}:
        raise ValueError("LR_TRAIN_REQUIRES_BOTH_CLASSES")
    scaler = StandardScaler()
    standardized = scaler.fit_transform(x)
    lr = LogisticRegression(C=config["C"], penalty=config["penalty"],
                            solver=config["solver"], max_iter=config["max_iter"],
                            class_weight=config["class_weight"], random_state=config["random_state"],
                            fit_intercept=config["fit_intercept"])
    with warnings.catch_warnings():
        warnings.simplefilter("error", ConvergenceWarning)
        lr.fit(standardized, y)
    if lr.classes_.tolist() != [0, 1]:
        raise ValueError("LR_BINARY_CLASS_ORDER")
    parameters = {"coefficients": lr.coef_[0].tolist(), "intercept": float(lr.intercept_[0]),
                  "scaler_mean": scaler.mean_.tolist(), "scaler_scale": scaler.scale_.tolist(),
                  "threshold": config["thresholds"][0]}
    roundtrip = base_model("logistic_regression", "lr_roundtrip", parameters, context, metadata)
    all_rows = train+cal
    native = lr.predict_proba(scaler.transform(np.array(
        [features(row["features"]) for row in all_rows], dtype=np.float64)))[:, 1]
    portable = np.array([evaluate_binary(roundtrip, row["features"])["answerability_score"]
                         for row in all_rows])
    max_difference = float(np.max(np.abs(native-portable)))
    if max_difference > 1e-10:
        raise ValueError("PORTABLE_LR_ROUNDTRIP_MISMATCH")
    for threshold in config["thresholds"]:
        identifier = f"lr_t{round(threshold*100):03d}"
        models.append(base_model("logistic_regression", identifier,
                                 parameters | {"threshold": threshold}, context, metadata))
    diagnostics = {"fit_rows": len(train), "cal_rows_not_fit": len(cal),
                   "train_positive_n": int(y.sum()), "train_negative_n": int(len(y)-y.sum()),
                   "iterations": lr.n_iter_.tolist(), "C": config["C"],
                   "solver": config["solver"], "penalty": config["penalty"],
                   "class_weight": config["class_weight"], "seed": config["random_state"],
                   "scaler_fit_split": "train", "roundtrip_max_abs_error": max_difference,
                   "roundtrip_rows": len(all_rows), "fit_count": 1}
    return models, diagnostics

def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--features", type=Path, required=True)
    parser.add_argument("--features-sha256", required=True)
    args = parser.parse_args()
    selection_path = ARTIFACT_ROOT/"sealed-selection.json"
    if selection_path.exists() or (ARTIFACT_ROOT/"holdout-started.json").exists():
        raise ValueError("TRAIN_CAL_ALREADY_SEALED_OR_HOLDOUT_STARTED")
    seal, protocol = load_preregistration()
    queries_path = ARTIFACT_ROOT/"train-cal-queries.json"
    author = read_author_queries(queries_path, seal["input_sha256"][queries_path.name], {"train", "cal"})
    export = read_feature_export(args.features, args.features_sha256)
    if export["input_sha256"] != sha256_file(queries_path):
        raise ValueError("TRAIN_CAL_EXPORT_AUTHOR_HASH_MISMATCH")
    rows = join_rows(author, export)
    context = runtime_context(export)
    train = [row for row in rows if row["split"] == "train"]
    cal = [row for row in rows if row["split"] == "cal"]
    if len(train) != 108 or len(cal) != 36:
        raise ValueError("FROZEN_TRAIN_CAL_COUNTS")
    metadata = {
        "protocol_sha256": seal["protocol_sha256"],
        "preregistration_seal_sha256": sha256_file(ARTIFACT_ROOT/"preregistration-seal.json"),
        "train_cal_author_sha256": sha256_file(queries_path),
        "feature_export_sha256": args.features_sha256,
        "feature_export_integrity": export["_validation"],
        "train_feature_label_rows_sha256": sha256_json([
            {"id": r["id"], "features": r["features"], "label": r["ground_truth_class"]} for r in train]),
        "cal_rows_sha256": sha256_json(cal),
        "library_versions": {name: importlib.metadata.version(name)
                             for name in ["scikit-learn", "numpy", "scipy"]},
        "python_version": sys.version, "source_sha256": source_hashes(),
        "trained_on": "train_only", "selected_on": "cal_only",
        "label_features_allowed": False,
    }
    models, fit_diagnostics = fit_models(train, cal, protocol, context, metadata)
    candidates = []
    for model in models:
        train_result, cal_result = report(model, train), report(model, cal)
        candidates.append({"model": model, "train": train_result, "cal": cal_result,
                           "train_gates_diagnostic": check_gates(train_result, protocol),
                           "cal_gates": check_gates(cal_result, protocol)})
    representatives = []
    for kind in protocol["policy_families"]:
        representative, reason = select_representative(
            [candidate for candidate in candidates if candidate["model"]["policy_kind"] == kind])
        model = representative["model"]
        model_path = ARTIFACT_ROOT/"models"/f"{model['candidate_id']}.json"
        write_private(model_path, model, exclusive=True)
        report_path = ARTIFACT_ROOT/f"representative-{kind}.json"
        write_private(report_path, representative, exclusive=True)
        representatives.append({"candidate_id": model["candidate_id"], "policy_kind": kind,
                                "model_path": str(model_path), "model_sha256": sha256_file(model_path),
                                "report_path": str(report_path), "report_sha256": sha256_file(report_path),
                                "cal_eligible": representative["cal_gates"]["passed"],
                                "selection_reason": reason})
    # All fixed configurations are reported, not just the selected curves.
    curves = []
    for candidate in candidates:
        record = candidate.copy()
        record["train"] = {key: value for key, value in record["train"].items() if key != "predictions"}
        record["cal"] = {key: value for key, value in record["cal"].items() if key != "predictions"}
        curves.append(record)
    write_private(ARTIFACT_ROOT/"train-cal-curves.json",
                  {"schema_version": "mati-rag-gate-train-cal-results-2",
                   "fit_diagnostics": fit_diagnostics, "candidates": curves,
                   "metadata": metadata, "context": context}, exclusive=True)
    selection = {"schema_version": "mati-rag-gate-sealed-selection-2",
                 "sealed_utc": datetime.datetime.now(datetime.timezone.utc).isoformat(),
                 "protocol_sha256": seal["protocol_sha256"],
                 "preregistration_seal_sha256": metadata["preregistration_seal_sha256"],
                 "train_cal_features_sha256": args.features_sha256,
                 "dataset_sha256": seal["input_sha256"]["dataset-frozen.json"],
                 "split_manifest_sha256": seal["input_sha256"]["split-manifest.json"],
                 "expected_holdout_sha256": seal["input_sha256"]["holdout-sealed.json"],
                 "context": context, "representatives": representatives,
                 "any_cal_eligible": any(r["cal_eligible"] for r in representatives),
                 "promotion_priority": protocol["selection"]["promotion_priority"],
                 "source_sha256": source_hashes(), "holdout_read": False,
                 "holdout_authorized": False, "fit_diagnostics": fit_diagnostics}
    write_private(selection_path, selection, exclusive=True)
    print({"selection_path": str(selection_path), "sha256": sha256_file(selection_path),
           "representatives": [{key: row[key] for key in
                                ("candidate_id", "policy_kind", "cal_eligible", "selection_reason")}
                               for row in representatives]})
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
