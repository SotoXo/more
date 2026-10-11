"""Synthetic boundary tests; never read any dataset or use embedding inference."""
from __future__ import annotations
import copy
import hashlib
import math
import sys
from pathlib import Path
import pytest
sys.path.insert(0, str(Path(__file__).resolve().parent))
import evaluate_holdout as hold
from metrics import check_gates, negative_subgroup, report, wilson
from model import (CONTEXT_KEYS, FEATURE_ORDER, base_model, evaluate_binary,
                   features, read_json, routed_decision, validate_model, write_private)
from train_cal import select_representative

CONTEXT = {key: "1"*64 for key in CONTEXT_KEYS}

def values(top1=.8, **changes):
    row = dict(zip(FEATURE_ORDER, [top1, .1, .2, 1/3, 0, .5], strict=True))
    row.update(changes)
    return row

def threshold(t=.75):
    return base_model("threshold", "fixture_threshold", {"threshold": t}, CONTEXT, {})

def multi():
    return base_model("multi_signal", "fixture_multi",
                      {"vector_min": .8, "lexical_min": 0, "lexical_operator": "gt",
                       "overlap_min": .5, "agreement_min": 1/3}, CONTEXT, {})

def lr(intercept=0):
    return base_model("logistic_regression", "fixture_lr", {
        "coefficients": [.5,-.3,.2,.4,-.2,.1], "intercept": intercept,
        "scaler_mean": [.2,.1,.3,.2,.1,.2],
        "scaler_scale": [.4,.2,.3,.2,.3,.1], "threshold": .5}, CONTEXT, {})

def author(identifier, label="RAG_ANSWERABLE", family="taxonomy_geometry",
           top1=.8, router="candidate_rag", expected=None):
    return {"id": identifier, "ground_truth_class": label,
            "semantic_family": family, "difficulty": "hard_positive",
            "language": "es", "group_id": identifier, "features": values(top1),
            "feature_order": FEATURE_ORDER.copy(), "router_decision": router,
            "vector_top3": [{"document_key": "doc-a", "chunk_id": "chunk-a", "score": top1}],
            "lexical_top3": [], "expected_document_ids": expected or (
                ["doc-a"] if label == "RAG_ANSWERABLE" else []),
            "runtime_context": CONTEXT.copy()}

@pytest.mark.parametrize("number", [math.nan, math.inf, -math.inf, True, "0.5", None])
def test_features_reject_non_numeric_and_nonfinite(number):
    with pytest.raises(ValueError):
        features(values(vector_top1=number))

@pytest.mark.parametrize("key,number", [
    ("vector_top1",1.000002),("margin_1_2",-1e-9),("margin_1_2",2.000003),
    ("lexical_top1",-1e-9),("vector_lexical_agreement",1.0000001),
    ("normalized_keyword_overlap",-1e-9),("exact_taxonomy_name_match",.5)])
def test_feature_ranges_match_php(key,number):
    with pytest.raises(ValueError):
        features(values(**{key:number}))

def test_numeric_edges_not_clipped():
    result=features(values(vector_top1=1.000001,margin_1_2=2.000002))
    assert result[:2]==[1.000001,2.000002]

def test_feature_order_missing_and_extra_rejected():
    for item in [dict(reversed(list(values().items()))),
                 {k:v for k,v in values().items() if k!="margin_1_2"},
                 values()|{"ground_truth_class":1}]:
        with pytest.raises(ValueError):
            features(item)

@pytest.mark.parametrize("path,value", [
    (("schema_version",),"bad"), (("feature_schema_version",),"bad"),
    (("normalization_version",),"bad"), (("routing_version",),"bad"),
    (("feature_order",), list(reversed(FEATURE_ORDER))),
    (("text_model","dimensions"),768), (("text_model","model_id"),"another/model"),
    (("text_model","revision"),"a"*40), (("context","corpus_sha256"),"invalid"),
    (("parameters","coefficients"),[1]*5), (("parameters","scaler_mean"),[0]*7),
    (("parameters","scaler_scale"),[1,1,0,1,1,1]),
    (("parameters","intercept"),math.nan), (("parameters","threshold"),math.inf)])
def test_model_malformed_identity_versions_dimensions(path,value):
    model=copy.deepcopy(lr())
    target=model
    for key in path[:-1]:
        target=target[key]
    target[path[-1]]=value
    with pytest.raises(ValueError):
        validate_model(model)

def test_stale_context_fail_closed():
    model=threshold()
    altered=CONTEXT|{"profile_sha256":"2"*64}
    with pytest.raises(ValueError,match="CONTEXT_MISMATCH"):
        validate_model(model,altered)
    with pytest.raises(ValueError):
        validate_model(model,{})
    model["context"]={key:value for key,value in CONTEXT.items() if key!="text_space_key"}
    with pytest.raises(ValueError):
        validate_model(model)

def test_scalar_lr_matches_hand_sigmoid():
    model=lr(-.2)
    x=list(values().values())
    p=model["parameters"]
    expected_z=p["intercept"]+sum((v-m)/s*c for v,m,s,c in zip(
        x,p["scaler_mean"],p["scaler_scale"],p["coefficients"],strict=True))
    expected=1/(1+math.exp(-expected_z))
    assert abs(evaluate_binary(model,values())["answerability_score"]-expected)<1e-15

@pytest.mark.parametrize("intercept,expected",[(1000,1.0),(-1000,0.0)])
def test_lr_stable_extreme_logit(intercept,expected):
    model=lr(intercept)
    model["parameters"]["coefficients"]=[0.0]*6
    assert evaluate_binary(model,values())["answerability_score"]==expected

def test_threshold_boundary_and_multi_strict_lexical():
    assert evaluate_binary(threshold(.8),values(.8))["accepted"]
    assert not evaluate_binary(threshold(.8),values(.799999))["accepted"]
    assert evaluate_binary(multi(),values(.8))["accepted"]
    assert not evaluate_binary(multi(),values(.8,lexical_top1=0))["accepted"]
    assert evaluate_binary(multi(),values(.8,exact_taxonomy_name_match=1,lexical_top1=0))["accepted"]
    assert not evaluate_binary(multi(),values(.79,exact_taxonomy_name_match=1))["accepted"]
    assert not evaluate_binary(multi(),values(.8,vector_lexical_agreement=.32))["accepted"]
    assert not evaluate_binary(multi(),values(.8,normalized_keyword_overlap=.49))["accepted"]

@pytest.mark.parametrize("router,expected",[
    ("tool","TOOL_REQUIRED"), ("required_information","INSUFFICIENT_EVIDENCE"),
    ({"route":"tool","reason":"fixture"},"TOOL_REQUIRED"),
    ({"route":"candidate_rag","reason":None},"RAG_ANSWERABLE")])
def test_router_shared_mapping(router,expected):
    assert routed_decision({"accepted":True},router,chunks_available=True)==expected

def test_empty_evidence_cannot_answer_and_unknown_router_rejected():
    assert routed_decision({"accepted":True},"candidate_rag",chunks_available=False)=="INSUFFICIENT_EVIDENCE"
    with pytest.raises(ValueError):
        routed_decision({"accepted":True},"answer",chunks_available=True)

def test_query_hit_and_fractional_recall_distinct_with_wilson_denominator():
    rows=[author("p1",expected=["doc-a","doc-b"]),author("p2"),
          author("n1","TOOL_REQUIRED","dynamic_stock",.95,{"route":"tool","reason":"test"}),
          author("n2","INSUFFICIENT_EVIDENCE","unsupported_in_domain",.1),
          author("n3","INSUFFICIENT_EVIDENCE","out_of_domain",.1),
          author("n4","INSUFFICIENT_EVIDENCE","ambiguous_query",.95,{"route":"required_information"})]
    result=report(threshold(),rows)
    metrics=result["operational"]
    assert (metrics["tp"],metrics["tn"],metrics["fp"],metrics["fn"])==(2,4,0,0)
    assert metrics["accepted_positive_query_hit_recall_at_3"]==1
    assert metrics["macro_expected_document_set_recall_at_3"]==.75
    assert metrics["wilson_95"]["answerable_recall"]["denominator"]==2
    assert result["raw_binary"]["fp"]==2
    assert result["three_class_confusion_matrix"]["TOOL_REQUIRED"]["TOOL_REQUIRED"]==1
    assert result["negative_subgroups"]["ood"]["negative_n"]==1
    protocol=read_json(Path(__file__).with_name("protocol.json"))
    assert check_gates(result,protocol)["passed"]

def test_required_empty_subgroup_ineligible_and_degenerate_mcc_null():
    result=report(threshold(),[author("p1")])
    protocol=read_json(Path(__file__).with_name("protocol.json"))
    assert not check_gates(result,protocol)["passed"]
    assert result["operational"]["mcc"] is None
    assert result["operational"]["negative_rejection"] is None
    assert wilson(0,0)["estimate"] is None
    with pytest.raises(ValueError):
        wilson(2,1)

def test_subgroup_unknown_rejected():
    with pytest.raises(ValueError):
        negative_subgroup({"ground_truth_class":"INSUFFICIENT_EVIDENCE","semantic_family":"new"})

def candidate(identifier, recall,far,f1,passed,t=.8):
    model=base_model("threshold",identifier,{"threshold":t},CONTEXT,{})
    return {"model":model,"cal":{"operational":{"answerable_recall":recall,
               "false_accept_rate":far,"f1":f1}},"cal_gates":{"passed":passed}}

def test_selection_cal_pass_primary_then_diagnostic_fixed_order():
    a=candidate("a",1,.2,.9,False,.9)
    b=candidate("b",.9,.05,.95,True,.8)
    c=candidate("c",.9,.05,.95,True,.85)
    selected,reason=select_representative([a,b,c])
    assert selected["model"]["candidate_id"]=="c" and reason=="cal_eligible"
    selected,reason=select_representative([a,candidate("d",.8,0,.99,False)])
    assert selected["model"]["candidate_id"]=="a" and reason=="diagnostic_recall_at_least_0.90"
    selected,reason=select_representative([candidate("z",.8,.1,.8,False),
                                           candidate("x",.7,0,.9,False)])
    assert selected["model"]["candidate_id"]=="x" and reason=="diagnostic_f1"

@pytest.mark.parametrize("contents",['{"x":NaN}','{"x":Infinity}','{"x":1,"x":2}','[]'])
def test_json_nonfinite_duplicate_and_nonobject(tmp_path,contents):
    path=tmp_path/"bad.json"; path.write_text(contents)
    with pytest.raises(ValueError):
        read_json(path)

def test_exclusive_marker_permissions_and_no_retry(tmp_path):
    path=tmp_path/"private"/"marker.json"
    write_private(path,{"started":True},exclusive=True)
    assert path.stat().st_mode&0o777==0o600
    assert path.parent.stat().st_mode&0o777==0o700
    with pytest.raises(FileExistsError):
        write_private(path,{"started":False},exclusive=True)
    assert read_json(path)["started"] is True

def test_holdout_start_marker_precedes_read_and_survives_failure(tmp_path,monkeypatch):
    monkeypatch.setattr(hold,"ARTIFACT_ROOT",tmp_path)
    seal={"protocol_sha256":"1"*64}
    selection={"expected_holdout_sha256":"2"*64,"context":CONTEXT,
               "representatives":[]}
    for name in ["preregistration-seal.json","sealed-selection.json","authorization.json"]:
        write_private(tmp_path/name,{})
    monkeypatch.setattr(hold,"load_preregistration",lambda:(seal,{}))
    monkeypatch.setattr(hold,"load_selection",lambda *_:selection)
    monkeypatch.setattr(hold,"check_authorization",lambda *_:{})
    monkeypatch.setattr(hold,"source_hashes",lambda:{"fixture":"3"*64})
    calls=[]
    def reader(*args,**kwargs):
        calls.append(args[0])
        assert (tmp_path/"holdout-started.json").exists()
        raise ValueError("SYNTHETIC_OPEN_FAILURE")
    monkeypatch.setattr(hold,"read_author_queries",reader)
    with pytest.raises(ValueError,match="SYNTHETIC"):
        hold.begin(tmp_path/"authorization.json")
    assert (tmp_path/"holdout-open-failure.json").exists()
    assert len(calls)==1
    with pytest.raises(FileExistsError):
        hold.begin(tmp_path/"authorization.json")
    assert len(calls)==1

def test_holdout_requires_explicit_authorization(tmp_path,monkeypatch):
    monkeypatch.setattr(hold,"ARTIFACT_ROOT",tmp_path)
    path=tmp_path/"authorization.json"
    write_private(path,{"schema_version":"mati-rag-gate-holdout-authorization-2","authorized":False})
    with pytest.raises(ValueError,match="NOT_AUTHORIZED"):
        hold.check_authorization(path,{},{})

def test_critical_correct_tool_route_mandatory(tmp_path,monkeypatch):
    monkeypatch.setattr(hold,"ARTIFACT_ROOT",tmp_path)
    path=tmp_path/"critical.json"
    selection={"context":CONTEXT,"protocol_sha256":"1"*64,
               "representatives":[{"candidate_id":"fixture","model_sha256":"2"*64}]}
    payload={"schema_version":"mati-rag-gate-critical-results-2","context":CONTEXT,
             "protocol_sha256":"1"*64,"representatives":[{"candidate_id":"fixture",
             "model_sha256":"2"*64,"tool_cases_n":6,"tool_bypass_count":0,
             "tool_routes_correct_n":5,"camisa_blusa_accepted":True,"camisa_blusa_all3":True}]}
    write_private(path,payload)
    result=hold.critical_checks(path,hashlib.sha256(path.read_bytes()).hexdigest(),selection)
    assert not result["fixture"]["passed"]
    assert result["fixture"]["checks"]["zero_tool_bypass"]
    assert not result["fixture"]["checks"]["tool_routes_correct"]

def test_document_key_preferred_over_database_numeric_id():
    row=author("p1")
    row["vector_top3"][0]["document_id"]=123
    assert report(threshold(),[row])["operational"]["accepted_positive_query_hit_recall_at_3"]==1

@pytest.mark.parametrize("raw,expected",[("TRAIN","train"),("CALIBRATION","cal"),("HOLDOUT","holdout"),("train","train"),("cal","cal"),("holdout","holdout")])
def test_frozen_author_split_canonical_copy(raw,expected):
    from inputs import canonical_split
    assert canonical_split(raw)==expected

def test_selection_source_mismatch_blocks_before_holdout_access(tmp_path,monkeypatch):
    monkeypatch.setattr(hold,"ARTIFACT_ROOT",tmp_path)
    write_private(tmp_path/"preregistration-seal.json",{})
    selection={"schema_version":"mati-rag-gate-sealed-selection-2",
               "protocol_sha256":"1"*64,"expected_holdout_sha256":"2"*64,
               "source_sha256":{"fixture":"3"*64}}
    write_private(tmp_path/"sealed-selection.json",selection)
    monkeypatch.setattr(hold,"source_hashes",lambda:{"fixture":"4"*64})
    seal={"protocol_sha256":"1"*64,"input_sha256":{"holdout-sealed.json":"2"*64}}
    with pytest.raises(ValueError,match="SOURCE_CHANGED"):
        hold.load_selection(seal,{})
    assert not (tmp_path/"holdout-started.json").exists()

@pytest.mark.parametrize("mutation",["passed_false","failure","http_missing","http500","http_bool","http_id"])
def test_failed_or_partial_feature_export_not_admitted(tmp_path,monkeypatch,mutation):
    import inputs
    monkeypatch.setattr(inputs,"ARTIFACT_ROOT",tmp_path)
    payload={"feature_schema_version":"mati-rag-gate-features-2","feature_order":FEATURE_ORDER,
             "rows":[{"id":"q1"}],"passed":True,"failure":None,"input_sha256":"1"*64,
             "http":[{"query_id":"q1","endpoint":"/api/v1/rag/embed","status":200}]}
    if mutation=="passed_false": payload["passed"]=False
    if mutation=="failure": payload["failure"]="synthetic"
    if mutation=="http_missing": payload["http"]=[]
    if mutation=="http500": payload["http"][0]["status"]=500
    if mutation=="http_bool": payload["http"][0]["status"]=True
    if mutation=="http_id": payload["http"][0]["query_id"]="other"
    path=tmp_path/"features.json";write_private(path,payload)
    with pytest.raises(ValueError):
        inputs.read_feature_export(path,hashlib.sha256(path.read_bytes()).hexdigest())

def test_complete_successful_feature_export_admitted_with_no500_metadata(tmp_path,monkeypatch):
    import inputs
    monkeypatch.setattr(inputs,"ARTIFACT_ROOT",tmp_path)
    payload={"feature_schema_version":"mati-rag-gate-features-2","feature_order":FEATURE_ORDER,
             "rows":[{"id":"q1"}],"passed":True,"failure":None,"input_sha256":"1"*64,
             "http":[{"query_id":"q1","endpoint":"/api/v1/rag/embed","status":200}]}
    path=tmp_path/"features.json";write_private(path,payload)
    result=inputs.read_feature_export(path,hashlib.sha256(path.read_bytes()).hexdigest())
    assert result["_validation"]["no_500"]
    assert result["_validation"]["http_responses"]==1
