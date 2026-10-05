"""CPU-only microbenchmarks and same-category retrieval from real frozen vectors."""
import json
import math
import time
from collections import Counter
from pathlib import Path

import cv2
import numpy as np

from mati_ai.compatibility.engine import evaluate_compatibility
from mati_ai.contracts.colorimetry import GarmentColorimetry
from mati_ai.contracts.embedding import Embedding
from mati_ai.contracts.identification import MatchItem
from mati_ai.contracts.vision import CatalogValue
from mati_ai.core.config import Settings
from mati_ai.identification.codes import decode_codes
from mati_ai.identification.matching import rank_matches
from mati_ai.identification.normalization import normalize_label
from mati_ai.publication.pipeline import prepare_publication
from mati_ai.vision.classification import classify
from mati_ai.vision.color import extract_colors
from mati_ai.vision.quality import assess_quality
from mati_ai.vision.segmentation import foreground_mask
from mati_ai.vision.validation import validate_image

ROOT = Path(__file__).resolve().parents[4]
OUT = ROOT / "storage/app/ai-audit"
manifest = json.loads((OUT / "evaluation_manifest.json").read_text())
predictions = json.loads((OUT / "classification_predictions.json").read_text())
meta = {r["image_id"]: r for r in manifest["images"]}
query_index = next(i for i, r in enumerate(predictions) if r["image_id"] == 31)
query = predictions[query_index]
source = meta[31]
settings = Settings(local_files_only=True, device="cpu", _env_file=None)
raw = Path(source["local_path"]).read_bytes()
image = validate_image(raw, settings).image
catalog = [CatalogValue.model_validate(r) for r in manifest["taxonomies"]["colors"]]
snapshot = query["response"]["data"]["colorimetry"]
profile = GarmentColorimetry.model_validate(snapshot)
usable_rows = [r for r in predictions if r["response"]["data"]["colorimetry"]["status"] == "completed"]
profile_a = GarmentColorimetry.model_validate(usable_rows[0]["response"]["data"]["colorimetry"])
profile_b = GarmentColorimetry.model_validate(usable_rows[1]["response"]["data"]["colorimetry"])
preview = image.copy()
preview.thumbnail((256,256))
lab = cv2.cvtColor(np.asarray(preview).astype(np.float32) / 255, cv2.COLOR_RGB2LAB)
top3 = query["response"]["data"]["classification"]["top_k"]
top3_categories = [CatalogValue(id=r["category_id"], name=r["category_name"]) for r in top3]
top3_scores = [r["similarity_score"] for r in top3]
# Typed candidates derived from actual snapshots; exclude self/hash/product group.
def match_item(row):
    m = meta[row["image_id"]]
    return MatchItem(candidate_id=row["product_id"], brand=m["brand_name"],
        text=m["description"], category=m["category_id"], color=m["catalog_color_id"],
        embedding=Embedding.model_validate(row["response"]["data"]["embedding"]),
        image_sha256=row["sha256"])
match_query = match_item(query)
match_candidates = [match_item(r) for r in predictions
                    if r["sha256"] != query["sha256"] and r["product_id"] != query["product_id"]]
label_fixture = "ZARA\nART: 1234/567\nM\n95% COTTON\n5% ELASTANE"
brands = [CatalogValue.model_validate(r) for r in manifest["taxonomies"]["brands"]]
sizes = [CatalogValue.model_validate(r) for r in manifest["taxonomies"]["sizes"]]

benchmark = {"kind": "in_process_cpu_microbenchmarks", "model_inference_excluded": True,
    "real_image_id": 31, "real_image_size": list(image.size), "image_bytes": len(raw),
    "n_per_module": 20, "warmups": 3, "module_results": {}}
def measure(name, fn, **scope):
    started = time.perf_counter()
    first = fn()
    cold_ms = (time.perf_counter() - started) * 1000
    for _ in range(2):
        fn()
    samples = []
    for _ in range(20):
        started = time.perf_counter()
        fn()
        samples.append((time.perf_counter() - started) * 1000)
    benchmark["module_results"][name] = {
        "n": len(samples), "min_ms": min(samples), "mean_ms": float(np.mean(samples)),
        "median_ms": float(np.median(samples)), "p95_ms": float(np.percentile(samples,95)),
        "max_ms": max(samples), "first_call_ms": cold_ms, "samples_ms": samples, **scope}
    return first
measure("image_validation", lambda: validate_image(raw, settings),
        includes="PIL decode EXIF ICC normalization SHA hashes", input="real_image_31_bytes")
measure("quality", lambda: assess_quality(image), input="already_validated_real_image_31")
measure("foreground", lambda: foreground_mask(lab), input="existing_real31_preview_Lab256", outcome="insufficient_foreground")
measure("color", lambda: extract_colors(image, catalog), input="real_image_31_and_55_real_catalog_colors",
        outcome="insufficient_evidence_path")
measure("classification_ranking_postprocess", lambda: classify(top3_categories, top3_scores),
        input="three_actual_returned_category_cosines_image31", scope_limit="ranking_3_candidates_not_11_class_model_inference")
measure("color_contract_postprocess", lambda: GarmentColorimetry.model_validate(snapshot).model_dump(mode="json"),
        input="actual_image31_color_snapshot")
result = measure("barcode", lambda: decode_codes(image), input="real_image_31_without_barcode_annotation")
benchmark["module_results"]["barcode"]["observed_codes"] = len(result["codes"])
measure("label_normalization", lambda: normalize_label(label_fixture, brands, sizes),
        input="synthetic_known_text_plus_real_catalogs", scope_limit="text_parser_not_OCR")
measure("internal_matching", lambda: rank_matches(match_query, match_candidates, 10),
        input="real31_query_and_129_actual_versioned_embeddings", n_candidates=len(match_candidates))
measure("compatibility_insufficient", lambda: evaluate_compatibility(profile, profile_a),
        input_image_ids=[31, usable_rows[0]["image_id"]], outcome="null")
measure("compatibility_completed", lambda: evaluate_compatibility(profile_a, profile_b),
        input_image_ids=[usable_rows[0]["image_id"], usable_rows[1]["image_id"]],
        input="existing_actual_completed_profiles")
measure("publication", lambda: prepare_publication(raw, settings, True, "owned", "audit-memory-only"),
        input="real_image_31", rights_scope="audit_declared_approval_in_memory_only_no_storage_no_publication")
(OUT / "cpu_module_benchmarks.json").write_text(json.dumps(benchmark, indent=2, ensure_ascii=False) + "\n")

# Exact cosine same-category retrieval, excluding query itself and same product/SHA.
embeddings = [Embedding.model_validate(r["response"]["data"]["embedding"]) for r in predictions]
space_fields = ["model_id","model_revision","pipeline_version","embedding_version","preprocess_version","normalization","dimension"]
spaces = {tuple(getattr(e, field) for field in space_fields) for e in embeddings}
if len(spaces) != 1:
    raise RuntimeError("Frozen embeddings span multiple incompatible spaces")
values = np.array([e.values for e in embeddings], dtype=np.float64)
norms = np.linalg.norm(values,axis=1)
values = values / norms[:,None]
sims = values @ values.T
categories = np.array([r["category_id"] for r in predictions])
image_ids = np.array([r["image_id"] for r in predictions])
products = np.array([r["product_id"] for r in predictions])
sha = np.array([r["sha256"] for r in predictions])
details=[]
for i,row in enumerate(predictions):
    eligible = (products != products[i]) & (sha != sha[i])
    gallery = np.flatnonzero(eligible)
    order = gallery[np.lexsort((image_ids[gallery], -sims[i,gallery]))]
    relevance = categories[order] == categories[i]
    total_relevant = int(np.sum(categories[gallery] == categories[i]))
    if total_relevant <= 0:
        details.append({"image_id": row["image_id"], "excluded": "no_category_positive_remaining"})
        continue
    first = int(np.flatnonzero(relevance)[0]) + 1 if relevance.any() else None
    dcg = float(np.sum(relevance[:10] / np.log2(np.arange(2, len(relevance[:10]) + 2))))
    idcg = float(np.sum(1 / np.log2(np.arange(2,min(10,total_relevant)+2))))
    detail={"image_id":row["image_id"],"category_id":row["category_id"],"gallery_n":len(gallery),
        "relevant_n":total_relevant,"first_relevant_rank":first,"rr":1/first if first else 0.,
        "ndcg_at_10":dcg/idcg if idcg else None,
        "top10":[{"image_id":int(image_ids[j]),"category_id":int(categories[j]),
            "cosine":float(sims[i,j]),"relevant":bool(categories[j]==categories[i])} for j in order[:10]]}
    for k in (1,5,10):
        hits=int(np.sum(relevance[:k]))
        detail["recall_at_"+str(k)]=hits/total_relevant
        detail["hit_at_"+str(k)]=int(hits>0)
        detail["precision_at_"+str(k)]=hits/min(k,len(gallery))
    details.append(detail)
valid=[r for r in details if "excluded" not in r]
metrics={"n_queries":len(valid),"n_vectors":len(predictions),"dimension":values.shape[1],
    "relevance_definition":"same_commercial_category_proxy_different_product_sha_not_identity",
    "exact_identity_retrieval_measured":False,"duplicate_sha_excluded":True,
    "conflicting_sha_excluded_image_ids":[2,5],"self_same_product_group_excluded":True,
    "vector_space":dict(zip(space_fields,next(iter(spaces)))),
    "cosine_computation":"NumPy_float64_full_cosine_matrix_L2_rescaled_stable_image_id_tie_break",
    "recall_definition":"mean_over_queries(number_of_same_category_results_in_topK / number_of_same_category_gallery_items)",
    "hit_definition":"fraction_of_queries_with_at_least_one_same_category_result_in_topK",
    "mrr_definition":"mean_reciprocal_rank_of_first_same_category_result_in_complete_gallery",
    "ndcg_definition":"binary_same_category_DCG_at10 / ideal_DCG_of_min(10,all_relevant_gallery_items)",
    "category_query_counts":dict(Counter(r["category_id"] for r in predictions)),
    "MRR":float(np.mean([r["rr"] for r in valid])),
    "nDCG_at_10":float(np.mean([r["ndcg_at_10"] for r in valid]))}
for k in (1,5,10):
    for key in ("recall","hit","precision"):
        metrics[key+"_at_"+str(k)]=float(np.mean([r[key+"_at_"+str(k)] for r in valid]))
metrics["per_category"]={}
for category in sorted(set(categories)):
    group=[r for r in valid if r["category_id"]==int(category)]
    metrics["per_category"][str(category)]={"n":len(group),"MRR":float(np.mean([r["rr"] for r in group])),
        "nDCG_at_10":float(np.mean([r["ndcg_at_10"] for r in group])),
        **{key+"_at_"+str(k):float(np.mean([r[key+"_at_"+str(k)] for r in group]))
           for key in ("recall","hit","precision") for k in (1,5,10)}}
(OUT / "retrieval_category_metrics.json").write_text(json.dumps({"metrics":metrics,"queries":details},indent=2,ensure_ascii=False)+"\n")
print(json.dumps({"module_results":{k:{a:b for a,b in v.items() if a!="samples_ms"} for k,v in benchmark["module_results"].items()},
    "retrieval_metrics":{k:v for k,v in metrics.items() if k!="per_category"}},indent=2,ensure_ascii=False))
