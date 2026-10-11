"""Irreversible opening and one-shot evaluation of frozen HOLD inputs."""
from __future__ import annotations
import argparse
import datetime
from pathlib import Path
from typing import Any
from inputs import (ARTIFACT_ROOT, join_rows, load_preregistration, read_author_queries,
                    read_feature_export, runtime_context, source_hashes)
from metrics import check_gates, report
from model import (checked_file, read_json, sha256_file, validate_model, write_private)

def utc_now() -> str:
    return datetime.datetime.now(datetime.timezone.utc).isoformat()

def load_selection(seal: dict[str, Any], protocol: dict[str, Any]) -> dict[str, Any]:
    selection = read_json(ARTIFACT_ROOT/"sealed-selection.json")
    if selection.get("schema_version") != "mati-rag-gate-sealed-selection-2":
        raise ValueError("SEALED_SELECTION_SCHEMA")
    if selection.get("protocol_sha256") != seal["protocol_sha256"]:
        raise ValueError("SELECTION_PROTOCOL")
    if selection.get("expected_holdout_sha256") != seal["input_sha256"]["holdout-sealed.json"]:
        raise ValueError("SELECTION_HOLDOUT_HASH")
    if selection.get("source_sha256") != source_hashes():
        raise ValueError("SELECTION_SOURCE_CHANGED_AFTER_TRAIN_CAL")
    if selection.get("preregistration_seal_sha256") != sha256_file(ARTIFACT_ROOT/"preregistration-seal.json"):
        raise ValueError("SELECTION_PREREGISTRATION_SEAL_CHANGED")
    representatives = selection.get("representatives")
    if not isinstance(representatives, list) or [r["policy_kind"] for r in representatives] != protocol["policy_families"]:
        raise ValueError("SEALED_FAMILIES")
    for item in representatives:
        model_path = checked_file({"path": item["model_path"], "sha256": item["model_sha256"]}, "model")
        if model_path.parent != (ARTIFACT_ROOT/"models").resolve():
            raise ValueError("MODEL_PATH_OUTSIDE_FROZEN_ROOT")
        model = read_json(model_path)
        validate_model(model, selection["context"])
        if model["candidate_id"] != item["candidate_id"] or model["policy_kind"] != item["policy_kind"]:
            raise ValueError("SEALED_MODEL_IDENTITY")
        checked_file({"path": item["report_path"], "sha256": item["report_sha256"]}, "cal_report")
    return selection

def check_authorization(path: Path, seal: dict[str, Any],
                        selection: dict[str, Any]) -> dict[str, Any]:
    if path.resolve().parent != ARTIFACT_ROOT.resolve():
        raise ValueError("AUTHORIZATION_PATH_OUTSIDE_FROZEN_ROOT")
    grant = read_json(path)
    if grant.get("schema_version") != "mati-rag-gate-holdout-authorization-2" or grant.get("authorized") is not True:
        raise ValueError("HOLDOUT_NOT_AUTHORIZED")
    expected = {
        "protocol_sha256": seal["protocol_sha256"],
        "sealed_selection_sha256": sha256_file(ARTIFACT_ROOT/"sealed-selection.json"),
        "expected_holdout_sha256": selection["expected_holdout_sha256"],
    }
    if any(grant.get(key) != value for key, value in expected.items()):
        raise ValueError("HOLDOUT_AUTHORIZATION_HASH_MISMATCH")
    if grant.get("source_sha256") != source_hashes():
        raise ValueError("HOLDOUT_SOURCE_HASH_MISMATCH")
    return grant

def begin(authorization: Path) -> dict[str, Any]:
    seal, protocol = load_preregistration()
    selection = load_selection(seal, protocol)
    check_authorization(authorization, seal, selection)
    marker = {
        "schema_version": "mati-rag-gate-holdout-started-2", "started_utc": utc_now(),
        "protocol_sha256": seal["protocol_sha256"],
        "preregistration_seal_sha256": sha256_file(ARTIFACT_ROOT/"preregistration-seal.json"),
        "sealed_selection_sha256": sha256_file(ARTIFACT_ROOT/"sealed-selection.json"),
        "authorization_path": str(authorization.resolve()),
        "authorization_sha256": sha256_file(authorization),
        "expected_holdout_sha256": selection["expected_holdout_sha256"],
        "source_sha256": source_hashes(), "context": selection["context"],
        "models": {r["candidate_id"]: r["model_sha256"] for r in selection["representatives"]},
        "expected_queries": 36, "retry_allowed": False,
        "holdout_opened_before_marker": False,
    }
    marker_path = ARTIFACT_ROOT/"holdout-started.json"
    # The exclusive persisted marker is created BEFORE the first HOLD bytes read.
    write_private(marker_path, marker, exclusive=True)
    try:
        author = read_author_queries(ARTIFACT_ROOT/"holdout-sealed.json",
                                     selection["expected_holdout_sha256"], {"holdout"})
        if len(author) != 36:
            raise ValueError("HOLDOUT_FROZEN_COUNT")
        train_cal = read_author_queries(
            ARTIFACT_ROOT/"train-cal-queries.json",
            seal["input_sha256"]["train-cal-queries.json"], {"train", "cal"})
        if {r["group_id"] for r in author} & {r["group_id"] for r in train_cal}:
            raise ValueError("HOLDOUT_GROUP_LEAKAGE")
        queries = {"schema_version": "mati-rag-gate-extraction-queries-2",
                   "dataset_sha256": selection["dataset_sha256"],
                   "split_sha256": selection["split_manifest_sha256"],
                   "queries": [{"id": row["id"], "query": row["query"], "split": "holdout"} for row in author]}
        queries_path = ARTIFACT_ROOT/"holdout-queries-only.json"
        write_private(queries_path, queries, exclusive=True)
        write_private(ARTIFACT_ROOT/"holdout-open-metadata.json", {
            "marker_sha256": sha256_file(marker_path), "opened_utc": utc_now(),
            "actual_holdout_sha256": sha256_file(ARTIFACT_ROOT/"holdout-sealed.json"),
            "queries_only_sha256": sha256_file(queries_path), "n": len(author),
            "group_count": len({r["group_id"] for r in author}),
            "train_cal_group_overlap": 0, "labels_not_exported_to_extractor": True,
        }, exclusive=True)
        return {"marker": str(marker_path), "marker_sha256": sha256_file(marker_path),
                "queries_only": str(queries_path), "queries_only_sha256": sha256_file(queries_path),
                "n": len(author)}
    except BaseException as error:
        write_private(ARTIFACT_ROOT/"holdout-open-failure.json",
                      {"utc": utc_now(), "error_type": type(error).__name__,
                       "error": str(error), "retry_allowed": False, "promotion": "NO_PROMOTION"},
                      exclusive=True)
        raise

def critical_checks(path: Path, expected_sha: str,
                    selection: dict[str, Any]) -> dict[str, dict[str, Any]]:
    if path.resolve().parent != ARTIFACT_ROOT.resolve() or sha256_file(path) != expected_sha:
        raise ValueError("CRITICAL_RESULT_PATH_OR_HASH")
    payload = read_json(path)
    if payload.get("schema_version") != "mati-rag-gate-critical-results-2":
        raise ValueError("CRITICAL_RESULT_SCHEMA")
    if payload.get("protocol_sha256") != selection["protocol_sha256"] or payload.get("context") != selection["context"]:
        raise ValueError("CRITICAL_RESULT_CONTEXT")
    rows = payload.get("representatives")
    if not isinstance(rows, list):
        raise ValueError("CRITICAL_RESULT_ROWS")
    indexed = {row["candidate_id"]: row for row in rows}
    expected_ids = {row["candidate_id"] for row in selection["representatives"]}
    if len(indexed) != len(rows) or set(indexed) != expected_ids:
        raise ValueError("CRITICAL_RESULT_CANDIDATE_IDS")
    result = {}
    for item in selection["representatives"]:
        row = indexed[item["candidate_id"]]
        if row.get("model_sha256") != item["model_sha256"]:
            raise ValueError("CRITICAL_RESULT_MODEL_HASH")
        checks = {
            "minimum_tool_cases": (isinstance(row.get("tool_cases_n"), int)
                                   and not isinstance(row["tool_cases_n"], bool)
                                   and row["tool_cases_n"] >= 6),
            "zero_tool_bypass": (isinstance(row.get("tool_bypass_count"), int)
                                 and not isinstance(row["tool_bypass_count"], bool)
                                 and row["tool_bypass_count"] == 0),
            "tool_routes_correct": (isinstance(row.get("tool_routes_correct_n"), int)
                                    and not isinstance(row["tool_routes_correct_n"], bool)
                                    and row["tool_routes_correct_n"] == row.get("tool_cases_n")),
            "camisa_blusa_accepted": row.get("camisa_blusa_accepted") is True,
            "camisa_blusa_all3": row.get("camisa_blusa_all3") is True,
        }
        result[item["candidate_id"]] = {"passed": all(checks.values()), "checks": checks,
                                       "evidence": row}
    return result

def evaluate(feature_path: Path, feature_sha: str, critical_path: Path,
             critical_sha: str) -> dict[str, Any]:
    marker_path = ARTIFACT_ROOT/"holdout-started.json"
    marker = read_json(marker_path)
    seal, protocol = load_preregistration()
    selection = load_selection(seal, protocol)
    authorization_path = Path(marker["authorization_path"])
    check_authorization(authorization_path, seal, selection)
    if marker.get("source_sha256") != source_hashes() or marker.get("authorization_sha256") != sha256_file(authorization_path):
        raise ValueError("HOLDOUT_CHANGED_AFTER_OPEN")
    if marker.get("sealed_selection_sha256") != sha256_file(ARTIFACT_ROOT/"sealed-selection.json"):
        raise ValueError("SELECTION_CHANGED_AFTER_OPEN")
    critical = critical_checks(critical_path, critical_sha, selection)
    evaluation_marker = {
        "schema_version": "mati-rag-gate-holdout-evaluation-started-2",
        "started_utc": utc_now(), "open_marker_sha256": sha256_file(marker_path),
        "source_sha256": source_hashes(), "feature_export_expected_sha256": feature_sha,
        "critical_results_sha256": critical_sha, "retry_allowed": False,
    }
    write_private(ARTIFACT_ROOT/"holdout-evaluation-started.json", evaluation_marker, exclusive=True)
    try:
        author = read_author_queries(ARTIFACT_ROOT/"holdout-sealed.json",
                                     selection["expected_holdout_sha256"], {"holdout"})
        export = read_feature_export(feature_path, feature_sha)
        if export["input_sha256"] != sha256_file(ARTIFACT_ROOT/"holdout-queries-only.json"):
            raise ValueError("HOLDOUT_EXPORT_QUERY_INPUT_HASH_MISMATCH")
        if runtime_context(export) != selection["context"]:
            raise ValueError("HOLDOUT_RUNTIME_CONTEXT_CHANGED")
        rows = join_rows(author, export)
        if len(rows) != 36:
            raise ValueError("HOLDOUT_EVALUATION_COUNT")
        results = []
        for representative in selection["representatives"]:
            model = read_json(Path(representative["model_path"]))
            result = report(model, rows)
            result["cal_eligible"] = representative["cal_eligible"]
            result["holdout_gates"] = check_gates(result, protocol)
            result["critical_gates"] = critical[model["candidate_id"]]
            result["promotable"] = (result["cal_eligible"] and result["holdout_gates"]["passed"]
                                    and result["critical_gates"]["passed"])
            results.append(result)
            write_private(ARTIFACT_ROOT/f"holdout-{model['policy_kind']}.json", result, exclusive=True)
        passing = [r for r in results if r["promotable"]]
        priority = protocol["selection"]["promotion_priority"]
        winner = min(passing, key=lambda r: priority.index(r["policy_kind"])) if passing else None
        decision = {
            "schema_version": "mati-rag-gate-decision-2", "completed_utc": utc_now(),
            "approved": False, "promotion_recommended": winner is not None,
            "decision": "ELIGIBLE_FOR_ROOT_PROMOTION" if winner else "NO_PROMOTION",
            "winner_candidate_id": winner["candidate_id"] if winner else None,
            "winner_policy_kind": winner["policy_kind"] if winner else None,
            "gate_model_sha256": next((r["model_sha256"] for r in selection["representatives"]
                                     if winner and r["candidate_id"] == winner["candidate_id"]), None),
            "protocol_sha256": seal["protocol_sha256"], "context": selection["context"],
            "sealed_selection_sha256": sha256_file(ARTIFACT_ROOT/"sealed-selection.json"),
            "holdout_author_sha256": sha256_file(ARTIFACT_ROOT/"holdout-sealed.json"),
            "holdout_features_sha256": feature_sha,
            "feature_export_integrity": export["_validation"],
            "critical_results_sha256": critical_sha,
            "holdout_open_marker_sha256": sha256_file(marker_path),
            "evaluation_started_marker_sha256": sha256_file(ARTIFACT_ROOT/"holdout-evaluation-started.json"),
            "source_sha256": source_hashes(), "evaluation_count": 1,
            "public_enablement_performed": False,
            "summaries": [{key: value for key, value in result.items() if key != "predictions"}
                          for result in results],
        }
        write_private(ARTIFACT_ROOT/"holdout-final.json", decision, exclusive=True)
        errors = []
        reports_by_id = {r["candidate_id"]: r for r in results}
        for representative in selection["representatives"]:
            candidate_id = representative["candidate_id"]
            by_id = {r["id"]: r for r in reports_by_id[candidate_id]["predictions"]}
            for row in rows:
                prediction = by_id[row["id"]]
                if prediction["positive"] == prediction["operational_accepted"]:
                    continue
                errors.append({"candidate_id": candidate_id,
                               "error_type": "FN" if prediction["positive"] else "FP",
                               "query": row["query"], "id": row["id"],
                               "ground_truth_class": row["ground_truth_class"],
                               "predicted_class": prediction["predicted_class"],
                               "label_rationale": row.get("label_rationale"),
                               "expected_document_ids": row["expected_document_ids"],
                               "features": row["features"], "router_decision": row["router_decision"],
                               "vector_top3": row["vector_top3"], "lexical_top3": row["lexical_top3"],
                               "prediction": prediction,
                               "cause_hypothesis": ("gate_or_router_false_reject_with_retrieval_support"
                                   if prediction["positive"] and prediction["supporting_document_hit"]
                                   else "retrieval_or_corpus_support_miss" if prediction["positive"]
                                   else "gate_false_accept_without_sufficient_corpus_support"),
                               "cause_is_inference": True})
        write_private(ARTIFACT_ROOT/"holdout-errors.json",
                      {"schema_version": "mati-rag-gate-errors-2", "errors": errors,
                       "post_result_tuning_performed": False}, exclusive=True)
        return {"decision": decision["decision"], "winner": decision["winner_candidate_id"],
                "n": len(rows), "errors": len(errors),
                "final_sha256": sha256_file(ARTIFACT_ROOT/"holdout-final.json")}
    except BaseException as error:
        write_private(ARTIFACT_ROOT/"holdout-evaluation-failure.json", {
            "utc": utc_now(), "error_type": type(error).__name__, "error": str(error),
            "promotion": "NO_PROMOTION", "retry_allowed": False,
        }, exclusive=True)
        raise

def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    commands = parser.add_subparsers(dest="command", required=True)
    opening = commands.add_parser("begin")
    opening.add_argument("--authorization", type=Path, required=True)
    final = commands.add_parser("evaluate")
    final.add_argument("--features", type=Path, required=True)
    final.add_argument("--features-sha256", required=True)
    final.add_argument("--critical-results", type=Path, required=True)
    final.add_argument("--critical-sha256", required=True)
    args = parser.parse_args()
    result = (begin(args.authorization) if args.command == "begin"
              else evaluate(args.features, args.features_sha256, args.critical_results, args.critical_sha256))
    print(result)
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
