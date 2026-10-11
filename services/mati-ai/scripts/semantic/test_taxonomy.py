"""Validate frozen semantic inputs, independently of models or a writable DB."""
from __future__ import annotations

import copy
import hashlib
import json
import unicodedata
from pathlib import Path

import pytest

RESOURCE_ROOT = Path(__file__).resolve().parents[2] / "resources"
TAXONOMY = RESOURCE_ROOT / "taxonomy/mati-taxonomy-v1.json"
SNAPSHOT = RESOURCE_ROOT / "taxonomy/commercial-categories-v1.json"
CORPUS = RESOURCE_ROOT / "rag/corpus-v1.json"
EVALUATION = RESOURCE_ROOT / "rag/evaluation-v1.json"


def read(path: Path) -> dict:
    return json.loads(path.read_text(encoding="utf-8"))


def sha(text: str) -> str:
    return hashlib.sha256(text.encode("utf-8")).hexdigest()


def alias_key(value: str) -> str:
    decomposed = unicodedata.normalize("NFKD", value).casefold()
    return " ".join("".join(c for c in decomposed if not unicodedata.combining(c)).split())


def check_aliases(taxonomy: dict) -> None:
    seen: dict[str, set[int]] = {}
    for category in taxonomy["categories"]:
        for alias in [category["commercial_name"], *category["aliases_es"], *category["aliases_en"]]:
            assert alias.strip(), "empty alias"
            seen.setdefault(alias_key(alias), set()).add(category["commercial_id"])
    explanations = {alias_key(row["term"]): row for row in taxonomy["alias_ambiguities"]}
    for term, ids in seen.items():
        if len(ids) > 1:
            explanation = explanations.get(term)
            assert explanation and explanation["explanation"].strip(), f"unexplained alias: {term}"
            assert ids <= set(explanation["category_ids"]), f"uncovered alias: {term}"


def check_hierarchy(taxonomy: dict) -> None:
    nodes = {row["id"]: row["parent"] for row in taxonomy["hierarchy"]}
    assert len(nodes) == len(taxonomy["hierarchy"]), "duplicate hierarchy node"
    for node in nodes:
        trail: set[str] = set()
        current = node
        while current is not None:
            assert current in nodes, "missing hierarchy parent"
            assert current not in trail, "hierarchy cycle"
            trail.add(current)
            current = nodes[current]


def test_commercial_ids_names_and_profiles_match_source_snapshot():
    t, s = read(TAXONOMY), read(SNAPSHOT)
    assert s["read_only"] is True
    assert t["commercial_snapshot"]["sha256"] == hashlib.sha256(SNAPSHOT.read_bytes()).hexdigest()
    actual = {r["id_categoria"]: r for r in s["categories"]}
    assert len(actual) == len(s["categories"]) == 11
    assert {r["commercial_id"] for r in t["categories"]} == set(actual)
    for r in t["categories"]:
        a = actual[r["commercial_id"]]
        assert r["commercial_name"] == a["nom_cat"]
        assert r["commercial_description"] == a["des_cat"]
        assert r["commercial_active"] == a["est_cat"]
        assert r["compatible_size_group"]["system"] == a["tip_tal_cat"]
        assert r["compatible_size_group"]["group"] == a["gru_tal_cat"]


def test_categories_unique_and_semantics_explicitly_proposed():
    t = read(TAXONOMY)
    for field in ["commercial_id", "commercial_name", "canonical_name"]:
        assert len({r[field] for r in t["categories"]}) == 11
    for r in t["categories"]:
        assert r["version"] == t["version"] == "1.0.0"
        assert "pending_mati_domain_review" in r["review_status"]
        for key in ["definition", "visual_characteristics", "positive_cues", "negative_cues",
                    "distinction_rules", "broader_role", "examples", "source"]:
            assert r[key], f"missing {key}"


def test_confusion_source_and_ambiguity_references_are_valid():
    t = read(TAXONOMY)
    ids = {r["commercial_id"] for r in t["categories"]}
    sources = {r["id"] for r in t["sources"]}
    assert len(sources) == len(t["sources"])
    for r in t["categories"]:
        assert r["commercial_id"] not in r["commonly_confused_with"]
        assert set(r["commonly_confused_with"]) <= ids
        assert len(r["commonly_confused_with"]) == len(set(r["commonly_confused_with"]))
        assert set(r["source"]) <= sources
    for row in t["alias_ambiguities"]:
        assert len(set(row["category_ids"])) >= 2
        assert set(row["category_ids"]) <= ids
        assert row["explanation"]


def test_aliases_and_hierarchy_are_valid():
    t = read(TAXONOMY)
    check_aliases(t)
    check_hierarchy(t)
    nodes = {r["id"] for r in t["hierarchy"]}
    assert all(r["broader_role"] in nodes for r in t["categories"])


def test_unexplained_duplicate_alias_is_detected():
    t = copy.deepcopy(read(TAXONOMY))
    t["categories"][1]["aliases_es"].append(t["categories"][0]["aliases_es"][0])
    with pytest.raises(AssertionError, match="unexplained alias"):
        check_aliases(t)


def test_hierarchy_cycle_is_detected():
    t = copy.deepcopy(read(TAXONOMY))
    t["hierarchy"][0]["parent"] = "UPPER"
    with pytest.raises(AssertionError, match="cycle"):
        check_hierarchy(t)


def test_polera_nulls_and_multi_piece_sets_are_preserved():
    cats = {r["commercial_id"]: r for r in read(TAXONOMY)["categories"]}
    assert cats[11]["commercial_description"] is None
    assert cats[11]["compatible_size_group"]["group"] is None
    assert cats[11]["aliases_en"] == []
    assert "ambiguous" in cats[11]["review_status"]
    assert cats[10]["broader_role"] == "MULTI_PIECE"
    assert cats[10]["compatible_size_group"]["group"] == "SUPERIOR"
    assert cats[5]["broader_role"] == "ONE_PIECE"


def test_comparison_links_cover_all_four_required_pairs():
    t, c = read(TAXONOMY), read(CORPUS)
    docs = {r["id"]: r for r in c["documents"]}
    expected = {"comparison:camisa-blusa": {3, 4}, "comparison:blazer-abrigo": {1, 2},
                "comparison:polera-knitwear": {8, 11}, "comparison:vestido-conjunto": {5, 10}}
    links = {r["id"]: r for r in t["comparisons"]}
    for key, ids in expected.items():
        assert set(links[key]["category_ids"]) == ids
        assert set(docs[key]["metadata"]["category_ids"]) == ids
        assert docs[key]["document_type"] == "comparison"


def test_corpus_is_small_semantic_units_and_no_dynamic_authority():
    t, c = read(TAXONOMY), read(CORPUS)
    assert c["taxonomy_sha256"] == hashlib.sha256(TAXONOMY.read_bytes()).hexdigest()
    assert c["corpus_version"] == "1.0.0"
    assert len(c["documents"]) == 23
    assert len({d["id"] for d in c["documents"]}) == 23
    chunks = [ch for d in c["documents"] for ch in d["chunks"]]
    assert len(chunks) == len({ch["id"] for ch in chunks}) == 23
    sources = {s["id"] for s in t["sources"]}
    cats = {r["commercial_id"] for r in t["categories"]}
    for d in c["documents"]:
        assert d["status"] == "active"
        assert d["source_version"] == "1.0.0"
        assert d["source"].startswith("mati-curated://")
        assert d["metadata"]["dynamic_data_authority"] is False
        assert set(d["metadata"]["source_ids"]) <= sources
        assert d["metadata"]["source_ids"]
        assert set(d["metadata"]["category_ids"]) <= cats
        assert len(d["chunks"]) == 1
        assert d["document_type"] in {"taxonomy_category", "comparison", "color_knowledge", "technical_explanation"}
        assert len((d["title"] + " " + d["chunks"][0]["content"]).split()) <= 80


def test_all_content_hashes_and_chunk_identities_recalculate_exactly():
    for d in read(CORPUS)["documents"]:
        inputs = []
        for ch in d["chunks"]:
            assert ch["id"] == d["id"] + "#" + ch["section"]
            assert ch["content_hash"] == sha(ch["content"])
            inputs.append(d["title"] + "\n" + ch["section"] + "\n" + ch["content"])
        assert d["content_hash"] == sha("\n\n".join(inputs))


def test_frozen_evaluation_positive_negative_denominators_and_languages():
    e = read(EVALUATION)
    assert e["corpus_sha256"] == hashlib.sha256(CORPUS.read_bytes()).hexdigest()
    assert len(e["queries"]) == 48
    assert len(e["negatives"]) == 16
    rows = e["queries"] + e["negatives"]
    assert len({q["id"] for q in rows}) == len(rows)
    assert len({q["text"] for q in rows}) == len(rows)
    assert {q["language"] for q in e["queries"]} == {"es", "en"}
    assert {"exact_category", "english_paraphrase", "small_typo", "category_comparison",
            "semantic_paraphrase"} <= {q["type"] for q in e["queries"]}
    assert all(q["is_negative"] is False and q["relevant_chunk_ids"] for q in e["queries"])


def test_explicit_relevance_ids_and_grades_are_valid():
    c, e = read(CORPUS), read(EVALUATION)
    documents = {d["id"]: d for d in c["documents"]}
    chunks = {ch["id"]: d["id"] for d in c["documents"] for ch in d["chunks"]}
    for q in e["queries"]:
        assert set(q["relevant_document_ids"]) <= set(documents)
        assert set(q["relevant_chunk_ids"]) <= set(chunks)
        assert {chunks[ch] for ch in q["relevant_chunk_ids"]} == set(q["relevant_document_ids"])
        assert set(q["relevance"]) == set(q["relevant_chunk_ids"])
        assert all(isinstance(grade, int) and 1 <= grade <= 3 for grade in q["relevance"].values())


def test_essential_shirt_blouse_query_labels_the_three_explicit_units():
    e = read(EVALUATION)
    q = next(q for q in e["queries"] if q["id"] == e["essential_query_id"])
    assert q["text"] == "¿Cuál es la diferencia entre una camisa y una blusa?"
    assert set(q["relevant_document_ids"]) == {"category:3", "category:4", "comparison:camisa-blusa"}
    assert q["relevance"]["comparison:camisa-blusa#comparison"] == 3


@pytest.mark.parametrize("policy,kind", [("requires_tool", "dynamic_data"), ("no_evidence", "no_corpus_evidence")])
def test_negative_groups_never_label_dynamic_or_unsupported_values(policy, kind):
    rows = [q for q in read(EVALUATION)["negatives"] if q["expected_policy"] == policy]
    assert len(rows) == 8
    for q in rows:
        assert q["type"] == kind
        assert q["is_negative"] is True
        assert q["expected_status"] == "insufficient_evidence"
        assert q["expected_chunks"] == []
        assert q["relevant_document_ids"] == q["relevant_chunk_ids"] == []
        assert q["relevance"] == {}
