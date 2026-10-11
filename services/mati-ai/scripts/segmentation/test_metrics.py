import pytest
from metrics import classification_metrics, retrieval_metrics

def rows(n=4):
    return [{"image_id": i + 1, "prenda_id": i + 1, "sha256": str(i),
             "category_id": 1 if i < 2 else 2} for i in range(n)]

def predicted(category):
    return {"top_k": [{"category_id": category}], "abstained": False}

def vector(axis):
    values = [0.0] * 768
    values[axis] = 1.0
    return values

def test_missing_targets_remain_in_denominator_and_confusion():
    result = classification_metrics(rows(), {1: predicted(1)})
    assert result["n"] == 4
    assert result["ranked_n"] == 1
    assert result["top1"] == .25
    assert result["top3"] == .25
    assert result["formal_coverage"] == .25
    assert result["missing_target_n"] == 3
    assert sum(sum(v) for v in result["confusion_matrix"]) == 4
    assert result["macro_recall"] == .25

def test_formal_abstention_does_not_change_ranked_baseline_metric():
    prediction = predicted(1)
    prediction["abstained"] = True
    result = classification_metrics(rows(2), {1: prediction, 2: prediction})
    assert result["top1"] == 1.0
    assert result["formal_coverage"] == 0.0

def test_retrieval_missing_queries_not_excluded():
    result = retrieval_metrics(rows(), {1: vector(0), 2: vector(0)})
    assert result["n_queries"] == 4
    assert result["n_vectors"] == 2
    assert result["metrics"]["hit1"] == .5
    assert result["metrics"]["recall10"] == .5
    assert result["metrics"]["ndcg10"] == .5

def test_full_reference_relevance_not_shrunk_by_missing_gallery():
    cohort = rows(4)
    for item in cohort:
        item["category_id"] = 1
    result = retrieval_metrics(cohort, {1: vector(0), 2: vector(0)})
    assert result["queries"][0]["relevant_n"] == 3
    assert result["queries"][0]["recall10"] == pytest.approx(1 / 3)
    assert result["metrics"]["recall10"] == pytest.approx(1 / 6)

def test_retrieval_self_same_product_and_sha_excluded():
    cohort = rows()
    cohort[1]["prenda_id"] = 1
    cohort[2]["sha256"] = cohort[0]["sha256"]
    result = retrieval_metrics(cohort, {r["image_id"]: vector(0) for r in cohort})
    assert result["queries"][0]["gallery_complete_n"] == 1
    assert result["queries"][0]["top10"][0]["image_id"] == 4

def test_bad_vector_is_rejected():
    with pytest.raises(ValueError):
        retrieval_metrics(rows(), {1: [float("nan")] * 768})

def test_empty_metrics_are_explicit():
    assert classification_metrics([], {})["top1"] is None
    assert retrieval_metrics([], {})["metrics"]["hit10"] is None



def test_prompt_category_without_gt_support_is_kept_in_confusion():
    result = classification_metrics(rows(), {1: predicted(9)})
    assert result["n"] == 4
    assert 9 in result["confusion_prediction_labels"]
    assert sum(sum(row) for row in result["confusion_matrix"]) == 4
    assert result["class_labels"] == [1, 2]
    assert result["macro_f1"] == 0.0
