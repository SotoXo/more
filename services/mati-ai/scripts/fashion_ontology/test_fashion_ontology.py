"""Standalone contract and mutation tests; uses unittest, no app/GPU/database.

Run from this directory with the same interpreter and jsonschema environment as
validate_fashion_ontology.py: python -m unittest -v test_fashion_ontology.py.
All mutations live in temporary fixture copies, never in the reviewed artifacts.
"""

from __future__ import annotations

import copy
import hashlib
import json
import shutil
import tempfile
import unittest
from pathlib import Path

from validate_fashion_ontology import OntologyValidator, normalized_term


ARTIFACT_DIR = Path(__file__).resolve().parents[2] / "resources/fashion_ontology/v1"
REPO_ROOT = Path(__file__).resolve().parents[4]


class FashionOntologyTests(unittest.TestCase):
    def setUp(self) -> None:
        scratch_parent = Path(__file__).resolve().parent
        self.temporary = tempfile.TemporaryDirectory(prefix=".ontology-test-", dir=scratch_parent)
        if not Path(self.temporary.name).resolve().is_relative_to(scratch_parent):
            raise ValueError("Temporary fixture must remain under the intended script workspace")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name) / "v1"
        shutil.copytree(ARTIFACT_DIR, self.root)

    def document(self, name: str) -> dict:
        return json.loads((self.root / name).read_text(encoding="utf-8-sig"))

    def write(self, name: str, value: dict) -> None:
        (self.root / name).write_text(json.dumps(value, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

    def rehash(self) -> None:
        # Ontology mutations deliberately repin the synthetic contract fixture
        # to isolate each tested invariant from its independent hash binding.
        if (self.root / "product_intelligence.example.json").exists():
            example = self.document("product_intelligence.example.json")
            example["ontology_content_hash"] = hashlib.sha256((self.root / "ontology.json").read_bytes()).hexdigest()
            self.write("product_intelligence.example.json", example)
        manifest = self.document("hashes.json")
        manifest["files"] = {path.name: hashlib.sha256(path.read_bytes()).hexdigest() for path in self.root.iterdir() if path.is_file() and path.suffix in {".json", ".csv"} and path.name != "hashes.json"}
        self.write("hashes.json", manifest)

    def validate(self, rehash: bool = True) -> dict:
        if rehash:
            self.rehash()
        return OntologyValidator(self.root, REPO_ROOT).validate()

    def assert_code(self, result: dict, code: str) -> None:
        self.assertFalse(result["valid"])
        self.assertIn(code, {error["code"] for error in result["errors"]}, result["errors"])

    def test_real_artifacts_pass_and_are_not_frozen(self) -> None:
        result = OntologyValidator(ARTIFACT_DIR, REPO_ROOT).validate()
        self.assertTrue(result["valid"], result["errors"])
        self.assertTrue(result["read_only"])
        self.assertFalse(result["domain_freeze_approved"])
        self.assertGreater(result["counts"]["families"], 0)

    def test_validator_does_not_write_artifacts(self) -> None:
        before = {path.name: hashlib.sha256(path.read_bytes()).hexdigest() for path in self.root.iterdir() if path.is_file()}
        OntologyValidator(self.root, REPO_ROOT).validate()
        after = {path.name: hashlib.sha256(path.read_bytes()).hexdigest() for path in self.root.iterdir() if path.is_file()}
        self.assertEqual(before, after)

    def test_bad_json_is_reported(self) -> None:
        (self.root / "styles.json").write_text("{broken", encoding="utf-8")
        self.assert_code(self.validate(), "PARSE")

    def test_non_json_nan_constant_is_rejected(self) -> None:
        (self.root / "styles.json").write_text('{"invalid_numeric":NaN}', encoding="utf-8")
        self.assert_code(self.validate(), "PARSE")

    def test_duplicate_json_keys_are_rejected(self) -> None:
        (self.root / "styles.json").write_text('{"artifact_type":"vocabulary","artifact_type":"ontology"}', encoding="utf-8")
        self.assert_code(self.validate(), "PARSE")

    def test_missing_artifact_is_reported(self) -> None:
        (self.root / "patterns.json").unlink()
        self.assert_code(self.validate(), "MISSING_FILE")

    def test_attribute_without_type_fails_schema(self) -> None:
        document = self.document("ontology.json")
        del document["attributes"][0]["value_type"]
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_missing_language_label_fails_schema(self) -> None:
        document = self.document("ontology.json")
        del document["categories"][0]["label_en"]
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_whitespace_language_label_fails_semantics(self) -> None:
        document = self.document("ontology.json")
        document["categories"][0]["label_en"] = "   "
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "LANGUAGE_LABEL")

    def test_duplicate_ids_across_different_nodes_are_rejected(self) -> None:
        document = self.document("ontology.json")
        document["categories"][1]["canonical_id"] = document["categories"][0]["canonical_id"]
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "DUPLICATE_ID")

    def test_unknown_parent_reference_is_rejected(self) -> None:
        document = self.document("ontology.json")
        document["categories"][0]["parent_id"] = "FAMILY_MISSING_FOR_TEST"
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "REFERENCE")

    def test_category_parent_cannot_be_a_category(self) -> None:
        document = self.document("ontology.json")
        document["categories"][0]["parent_id"] = document["categories"][1]["canonical_id"]
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "TAXONOMY_PARENT")

    def test_parent_cycle_is_rejected(self) -> None:
        document = self.document("body_zones.json")
        document["concepts"][0]["parent_id"] = document["concepts"][0]["canonical_id"]
        self.write("body_zones.json", document)
        self.assert_code(self.validate(), "PARENT_CYCLE")

    def test_alias_normalization_handles_accents_case_and_dashes(self) -> None:
        self.assertEqual(normalized_term(" LÍNEA—A "), normalized_term("linea_a"))
        self.assertEqual(normalized_term("T-SHIRT"), normalized_term("t shirt"))

    def test_spanish_alias_conflict_is_rejected(self) -> None:
        document = self.document("ontology.json")
        document["categories"][0]["aliases_es"].append(document["categories"][1]["label_es"].upper())
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "ALIAS_CONFLICT")

    def test_english_alias_conflict_is_rejected(self) -> None:
        document = self.document("ontology.json")
        document["categories"][0]["aliases_en"].append(document["categories"][1]["label_en"].upper())
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "ALIAS_CONFLICT")

    def test_explicit_scoped_alias_ambiguity_is_permitted(self) -> None:
        document = self.document("ontology.json")
        first, second = document["categories"][:2]
        term = "regional ontology fixture term"
        first["aliases_es"].append(term)
        second["aliases_es"].append(term)
        document["alias_ambiguities"].append({"term": term, "language": "es", "scope": "category", "concept_ids": [first["canonical_id"], second["canonical_id"]], "notes": "Explicit test-only regional ambiguity."})
        self.write("ontology.json", document)
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_nonexistent_allowed_value_domain_is_rejected(self) -> None:
        document = self.document("ontology.json")
        attribute = next(item for item in document["attributes"] if item["vocabulary_ref"])
        attribute["vocabulary_ref"]["domain"] = "missing_domain_for_test"
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "ALLOWED_VALUES_REFERENCE")

    def test_duplicate_allowed_value_is_rejected(self) -> None:
        document = self.document("patterns.json")
        clone = copy.deepcopy(document["concepts"][0])
        clone["label_es"] = "Etiqueta distinta de prueba"
        document["concepts"].append(clone)
        self.write("patterns.json", document)
        self.assert_code(self.validate(), "DUPLICATE_ALLOWED_VALUE")

    def test_applicability_requires_every_attribute_family_pair(self) -> None:
        document = self.document("attribute_applicability.json")
        document["rows"].pop()
        self.write("attribute_applicability.json", document)
        self.assert_code(self.validate(), "APPLICABILITY_COVERAGE")

    def test_not_applicable_cannot_allow_unknown(self) -> None:
        document = self.document("attribute_applicability.json")
        row = next(row for row in document["rows"] if row["status"] == "not_applicable")
        row["unknown_allowed"] = True
        self.write("attribute_applicability.json", document)
        self.assert_code(self.validate(), "APPLICABILITY_UNKNOWN")

    def test_label_missing_from_plan_is_rejected(self) -> None:
        document = self.document("label_schema.json")
        document["labels"].pop()
        self.write("label_schema.json", document)
        self.assert_code(self.validate(), "LABEL_COVERAGE")

    def test_label_plan_cannot_drift_from_attribute(self) -> None:
        document = self.document("label_schema.json")
        label = document["labels"][0]
        label["annotation_difficulty"] = "high" if label["annotation_difficulty"] != "high" else "low"
        self.write("label_schema.json", document)
        self.assert_code(self.validate(), "LABEL_SYNC")

    def test_known_unknown_and_not_applicable_are_distinct(self) -> None:
        document = self.document("label_schema.json")
        document["labels"][0]["unknown_policy"] = {"unknown_allowed": True, "states": ["KNOWN", "UNKNOWN"]}
        self.write("label_schema.json", document)
        self.assert_code(self.validate(), "LABEL_STATES")

    def test_nonvisual_attribute_cannot_be_visual_trainable(self) -> None:
        document = self.document("ontology.json")
        attribute = next(item for item in document["attributes"] if item["visually_learnable"] == "no")
        attribute["trainable_visual"] = "yes"
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "VISUAL_TRAINABILITY")

    def test_relation_endpoint_must_exist(self) -> None:
        document = self.document("ontology.json")
        document["relationships"][0]["object"] = "MISSING_RELATION_TARGET"
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "RELATION_ENDPOINT")

    def test_relation_predicate_must_exist(self) -> None:
        document = self.document("ontology.json")
        document["relationships"][0]["predicate"] = "MISSING_RELATION_TYPE"
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "RELATION_PREDICATE")

    def test_body_zone_reference_must_exist(self) -> None:
        document = self.document("body_zones.json")
        document["bindings"][0]["primary_zone"] = "BODY_ZONE_MISSING"
        self.write("body_zones.json", document)
        self.assert_code(self.validate(), "BODY_ZONE_REFERENCE")

    def test_carrying_bag_is_not_body_coverage(self) -> None:
        document = self.document("body_zones.json")
        ontology = self.document("ontology.json")
        bag_category = next(category for category in ontology["categories"] if "BAG" in category["parent_id"].upper())
        binding = {"category_id": bag_category["canonical_id"], "primary_zone": document["concepts"][0]["canonical_id"], "secondary_zones": [], "coverage_role": "covers", "notes": "Invalid test-only coverage claim."}
        document["bindings"].append(binding)
        self.write("body_zones.json", document)
        self.assert_code(self.validate(), "CARRY_IS_NOT_COVERAGE")

    def test_body_roles_cannot_collapse_carrying_into_coverage(self) -> None:
        document = self.document("body_zones.json")
        document["semantics"]["coverage_roles"] = ["covers"]
        self.write("body_zones.json", document)
        self.assert_code(self.validate(), "BODY_ZONE_ROLES")

    def test_only_instance_layering_constraints_have_dag_validation(self) -> None:
        document = self.document("layering.json")
        ontology = self.document("ontology.json")
        predicate = next((item["canonical_id"] for item in ontology["relation_types"] if "OVER" in item["canonical_id"].upper()), "TEST_LAYERED_OVER")
        if predicate == "TEST_LAYERED_OVER":
            ontology["relation_types"].append({"canonical_id": predicate, "definition": "Test fixture instance ordering."})
            self.write("ontology.json", ontology)
        first, second = [item["canonical_id"] for item in document["roles"][:2]]
        for index, (subject, target) in enumerate(((first, second), (second, first))):
            document["relations"].append({"canonical_id": f"TEST_INSTANCE_ORDER_{index}", "subject": subject, "predicate": predicate, "object": target, "rule_kind": "structural_constraint", "conditions": [{"scope": "instance"}], "notes": "Test-only impossible instance ordering."})
        self.write("layering.json", document)
        self.assert_code(self.validate(), "LAYERING_INSTANCE_CYCLE")

    def test_soft_layering_cycles_remain_contextual(self) -> None:
        document = self.document("layering.json")
        ontology = self.document("ontology.json")
        predicate = next(iter(ontology["relation_types"]))["canonical_id"]
        first, second = [item["canonical_id"] for item in document["roles"][:2]]
        for index, (subject, target) in enumerate(((first, second), (second, first))):
            document["relations"].append({"canonical_id": f"TEST_SOFT_ORDER_{index}", "subject": subject, "predicate": predicate, "object": target, "rule_kind": "soft_rule", "conditions": ["Contextual test-only styling"], "notes": "Preference is not a universal order."})
        self.write("layering.json", document)
        result = self.validate()
        self.assertNotIn("LAYERING_INSTANCE_CYCLE", {error["code"] for error in result["errors"]})

    def test_commercial_name_must_match_snapshot_exactly(self) -> None:
        document = self.document("commercial_mapping.json")
        document["mappings"][0]["commercial_name"] += " TEST RENAME"
        self.write("commercial_mapping.json", document)
        self.assert_code(self.validate(), "COMMERCIAL_NAME")

    def test_commercial_mapping_must_cover_source(self) -> None:
        document = self.document("commercial_mapping.json")
        document["mappings"].pop()
        self.write("commercial_mapping.json", document)
        self.assert_code(self.validate(), "COMMERCIAL_COVERAGE")

    def test_commercial_target_must_resolve_to_taxonomy(self) -> None:
        document = self.document("commercial_mapping.json")
        document["mappings"][0]["ontology_targets"].append("MISSING_ONTOLOGY_TARGET")
        self.write("commercial_mapping.json", document)
        self.assert_code(self.validate(), "COMMERCIAL_TARGET")

    def test_commercial_source_fingerprint_is_enforced(self) -> None:
        document = self.document("commercial_mapping.json")
        document["source_snapshot"]["sha256"] = "0" * 64
        self.write("commercial_mapping.json", document)
        self.assert_code(self.validate(), "COMMERCIAL_SOURCE_HASH")

    def test_artifact_fingerprint_is_enforced(self) -> None:
        document = self.document("styles.json")
        document["concepts"][0]["definition"] += " Mutation after hashing."
        self.write("styles.json", document)
        self.assert_code(self.validate(rehash=False), "HASH_MISMATCH")

    def test_manifest_must_cover_exact_artifact_set(self) -> None:
        document = self.document("hashes.json")
        document["files"].pop("styles.json")
        self.write("hashes.json", document)
        self.assert_code(self.validate(rehash=False), "HASH_FILE_SET")

    def test_summary_cannot_report_wrong_counts(self) -> None:
        document = self.document("summary.json")
        key = "families" if "families" in document["counts"] else "families_count"
        document["counts"][key] = 999999
        self.write("summary.json", document)
        self.assert_code(self.validate(), "SUMMARY_COUNT")

    def test_product_intelligence_contract_rejects_commercial_stock(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["stock"] = 123
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_product_unknown_cannot_carry_a_value(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["subcategory"]["value"] = "garment.subcategory.fake"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_product_known_cannot_use_null_for_a_value(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["family"]["value"] = None
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_product_taxonomy_path_must_match(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["family"]["value"] = "garment.family.lower"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_TAXONOMY_PATH")

    def test_product_not_applicable_cannot_be_known(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.rise")
        slot["assertion"] = {"state": "KNOWN", "value": "value.rise.high", "evidence_refs": [document["evidence"][0]["evidence_id"]]}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_APPLICABILITY")

    def test_product_required_slot_can_be_unknown_but_cannot_be_missing(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["attributes"] = [item for item in document["attributes"] if item["attribute_id"] != "attribute.pattern"]
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_REQUIRED_ATTRIBUTE")

    def test_product_required_unknown_is_valid(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.pattern")
        slot["assertion"] = {"state": "UNKNOWN", "reason": "No visual evidence for this test.", "evidence_refs": []}
        self.write("product_intelligence.example.json", document)
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_product_multilabel_requires_a_list(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.pattern")
        slot["assertion"]["value"] = "value.pattern.solid"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_VALUE_TYPE")

    def test_product_allowed_value_cannot_cross_an_ontology_axis(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.pattern")
        slot["assertion"]["value"] = ["garment.category.tshirt"]
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_ALLOWED_VALUE")

    def test_product_multilabel_values_are_unique(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.pattern")
        slot["assertion"]["value"].append(slot["assertion"]["value"][0])
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_DUPLICATE_VALUE")

    def test_product_evidence_references_must_resolve(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["family"]["evidence_refs"] = ["missing_evidence"]
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_EVIDENCE_REFERENCE")

    def test_product_known_assertions_need_evidence(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["family"]["evidence_refs"] = []
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_KNOWN_EVIDENCE")

    def test_product_evidence_ids_are_unique(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["evidence"].append(copy.deepcopy(document["evidence"][0]))
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_DUPLICATE_EVIDENCE")

    def test_product_section_views_reference_central_assertions(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["materials"]["attribute_refs"] = ["attribute.weave"]
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_SECTION_REFERENCE")

    def test_product_spatial_zone_reference_must_resolve(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.spatial_semantics")
        slot["assertion"]["value"][0]["primary_zone"] = "body.zone.unknown_for_test"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_BODY_ZONE_REFERENCE")

    def test_complete_fiber_percentages_must_total_one_hundred(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.fiber_composition")
        fiber = next(item["canonical_id"] for item in self.document("materials.json")["concepts"] if item["domain"] == "fiber")
        slot["assertion"] = {"state": "KNOWN", "value": [{"component_ref": "shell", "composition_completeness": "complete", "fibers": [{"fiber_id": fiber, "percent": 95}]}], "evidence_refs": [document["evidence"][0]["evidence_id"]]}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_COMPOSITION_TOTAL")

    def test_fiber_composition_cannot_use_a_fabric_as_a_fiber(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.fiber_composition")
        slot["assertion"] = {"state": "KNOWN", "value": [{"component_ref": "shell", "composition_completeness": "complete", "fibers": [{"fiber_id": "value.textile_structure.woven", "percent": 100}]}], "evidence_refs": [document["evidence"][0]["evidence_id"]]}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_FIBER_REFERENCE")

    def test_weave_cannot_be_known_when_textile_structure_is_unknown(self) -> None:
        document = self.document("product_intelligence.example.json")
        weave = next(item["canonical_id"] for item in self.document("fabrics.json")["concepts"] if item["domain"] == "weave")
        document["attributes"].append({"attribute_id": "attribute.weave", "assertion": {"state": "KNOWN", "value": weave, "evidence_refs": [document["evidence"][0]["evidence_id"]]}})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_TEXTILE_DEPENDENCY")

    def test_product_layering_cycles_are_scoped_to_instance_zone_context(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["related_instance_refs"] = ["piece-a", "piece-b"]
        edges = [{"over_instance_ref": over, "under_instance_ref": under, "zone_refs": ["body.zone.torso"], "wear_context_ref": "fixture:same-context", "rule_kind": "structural_constraint", "conditions": [], "evidence_refs": [document["evidence"][0]["evidence_id"]]} for over, under in (("piece-a", "piece-b"), ("piece-b", "piece-a"))]
        document["attributes"].append({"attribute_id": "attribute.layering", "assertion": {"state": "KNOWN", "value": {"edges": edges}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_LAYERING_CYCLE")

    def test_product_layering_opposite_orders_in_different_contexts_are_valid(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["related_instance_refs"] = ["piece-a", "piece-b"]
        edges = [{"over_instance_ref": over, "under_instance_ref": under, "zone_refs": ["body.zone.torso"], "wear_context_ref": f"fixture:context-{index}", "rule_kind": "structural_constraint", "conditions": [], "evidence_refs": [document["evidence"][0]["evidence_id"]]} for index, (over, under) in enumerate((("piece-a", "piece-b"), ("piece-b", "piece-a")))]
        document["attributes"].append({"attribute_id": "attribute.layering", "assertion": {"state": "KNOWN", "value": {"edges": edges}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}})
        self.write("product_intelligence.example.json", document)
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_human_reviewed_status_cannot_be_asserted_without_review(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["document_status"] = "human_reviewed"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_HUMAN_REVIEW_STATUS")

    def test_confidence_probability_cannot_use_negative_cosine_score(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["family"]["confidence"] = {"confidence_type": "probability", "score": -0.2}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_product_pins_the_exact_ontology_hash(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["ontology_content_hash"] = "0" * 64
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(rehash=False), "PI_ONTOLOGY_HASH")

    def style_affinity_fixture(self) -> dict:
        document = self.document("product_intelligence.example.json")
        styles = [item["canonical_id"] for item in self.document("styles.json")["concepts"] if item["domain"] == "style"][:2]
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.styles")
        refs = [document["evidence"][0]["evidence_id"]]
        slot["assertion"] = {"state": "KNOWN", "value": styles, "evidence_refs": refs, "affinity_scores": [{"concept_id": style, "confidence": {"confidence_type": "probability", "score": score}, "evidence_refs": refs} for style, score in zip(styles, (0.91, 0.87))]}
        return document

    def test_style_affinities_are_independent_and_need_not_sum_to_one(self) -> None:
        document = self.style_affinity_fixture()
        self.write("product_intelligence.example.json", document)
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_style_affinity_must_reference_an_asserted_style(self) -> None:
        document = self.style_affinity_fixture()
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.styles")
        slot["assertion"]["affinity_scores"][0]["concept_id"] = "garment.category.tshirt"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_AFFINITY_REFERENCE")

    def test_style_affinity_without_evidence_is_rejected(self) -> None:
        document = self.style_affinity_fixture()
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.styles")
        slot["assertion"]["affinity_scores"][0]["evidence_refs"] = []
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_season_target_must_be_a_calendar_season(self) -> None:
        document = self.document("commercial_season_mapping.json")
        document["mappings"][0]["semantic_targets"] = ["garment.category.tshirt"]
        self.write("commercial_season_mapping.json", document)
        self.assert_code(self.validate(), "SEASON_TARGET")

    def test_cross_language_alias_conflict_with_accents_is_rejected(self) -> None:
        document = self.document("ontology.json")
        document["categories"][0]["aliases_en"].append(document["categories"][1]["label_es"].upper() + "---")
        self.write("ontology.json", document)
        self.assert_code(self.validate(), "CROSS_LANGUAGE_ALIAS_CONFLICT")

    def test_empty_known_multilabel_requires_complete_annotation(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.pattern")
        slot["assertion"]["value"] = []
        slot["assertion"]["annotation_completeness"] = "partial"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_EMPTY_MULTILABEL")

    def test_empty_known_multilabel_with_complete_evidence_is_valid(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.pattern")
        slot["assertion"]["value"] = []
        slot["assertion"]["annotation_completeness"] = "complete"
        self.write("product_intelligence.example.json", document)
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_sleeveless_requires_not_applicable_sleeve_shape(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.sleeve_length")
        slot["assertion"]["value"] = "value.sleeve_length.sleeveless"
        shape = next(item["canonical_id"] for item in self.document("attribute_values.json")["concepts"] if item["domain"] == "sleeve_shape")
        document["attributes"].append({"attribute_id": "attribute.sleeve_shape", "assertion": {"state": "KNOWN", "value": [shape], "evidence_refs": [document["evidence"][0]["evidence_id"]]}})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_SLEEVE_DEPENDENCY")

    def test_sleeveless_with_explicit_not_applicable_sleeve_shape_is_valid(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.sleeve_length")
        slot["assertion"]["value"] = "value.sleeve_length.sleeveless"
        document["attributes"].append({"attribute_id": "attribute.sleeve_shape", "assertion": {"state": "NOT_APPLICABLE", "reason": "Confirmed garment without sleeves", "evidence_refs": [document["evidence"][0]["evidence_id"]]}})
        self.write("product_intelligence.example.json", document)
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_set_requires_two_declared_components(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["identity"] = {"entity_ref": "example:test-set", "entity_kind": "set", "component_refs": ["piece-a"]}
        document["taxonomy"]["family"]["value"] = "garment.family.multi_piece"
        document["taxonomy"]["category"]["value"] = "garment.category.coordinated_set"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_coordinated_set_cannot_use_single_product_identity(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["family"]["value"] = "garment.family.multi_piece"
        document["taxonomy"]["category"]["value"] = "garment.category.coordinated_set"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_fiber_cannot_claim_a_phantom_component(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.fiber_composition")
        fiber = next(item["canonical_id"] for item in self.document("materials.json")["concepts"] if item["domain"] == "fiber")
        slot["assertion"] = {"state": "KNOWN", "value": [{"component_ref": "phantom", "composition_completeness": "complete", "fibers": [{"fiber_id": fiber, "percent": 100}]}], "context": {"textile_component_present": True}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_COMPONENT_REFERENCE")

    def test_known_fiber_conflicts_with_confirmed_absence_of_textile(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.fiber_composition")
        fiber = next(item["canonical_id"] for item in self.document("materials.json")["concepts"] if item["domain"] == "fiber")
        slot["assertion"] = {"state": "KNOWN", "value": [{"component_ref": "shell", "composition_completeness": "complete", "fibers": [{"fiber_id": fiber, "percent": 100}]}], "context": {"textile_component_present": False}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_APPLICABILITY")

    def test_textile_absence_requires_evidence(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.fiber_composition")
        slot["assertion"] = {"state": "NOT_APPLICABLE", "reason": "Confirmed absence of textile", "context": {"textile_component_present": False}, "evidence_refs": []}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_COMPONENT_ABSENCE_EVIDENCE")

    def test_documented_nontextile_item_can_have_not_applicable_fiber(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.fiber_composition")
        slot["assertion"] = {"state": "NOT_APPLICABLE", "reason": "Catalog documents no textile component", "context": {"textile_component_present": False}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}
        self.write("product_intelligence.example.json", document)
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_observed_color_cannot_receive_fiber_composition_structure(self) -> None:
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.observed_color")
        slot["assertion"] = {"state": "KNOWN", "value": {"fibers": ["cotton"]}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_product_json_schema_itself_rejects_wrong_structured_color(self) -> None:
        from jsonschema import Draft202012Validator
        document = self.document("product_intelligence.example.json")
        slot = next(item for item in document["attributes"] if item["attribute_id"] == "attribute.observed_color")
        slot["assertion"] = {"state": "KNOWN", "value": {"fibers": ["cotton"]}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}
        validator = Draft202012Validator(self.document("product_intelligence.schema.json"))
        self.assertFalse(validator.is_valid(document))

    def test_product_json_schema_itself_rejects_wrong_structured_insulation(self) -> None:
        from jsonschema import Draft202012Validator
        document = self.document("product_intelligence.example.json")
        document["attributes"].append({"attribute_id": "attribute.insulation", "assertion": {"state": "KNOWN", "value": {"rgb": [12, 34, 56]}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}})
        validator = Draft202012Validator(self.document("product_intelligence.schema.json"))
        self.assertFalse(validator.is_valid(document))

    def test_invalid_recorded_at_is_rejected_without_optional_format_packages(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["evidence"][0]["recorded_at"] = "never"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "SCHEMA")

    def test_derived_evidence_requires_real_premise_references(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["evidence"][0]["source_type"] = "derived_rule"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_DERIVED_PREMISES")

    def test_instance_layering_soft_rules_still_cannot_form_a_physical_cycle(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["related_instance_refs"] = ["piece-a", "piece-b"]
        edges = [{"over_instance_ref": over, "under_instance_ref": under, "zone_refs": ["body.zone.torso"], "wear_context_ref": "fixture:same-context", "rule_kind": "soft_rule", "conditions": ["Context-specific intended order"], "evidence_refs": [document["evidence"][0]["evidence_id"]]} for over, under in (("piece-a", "piece-b"), ("piece-b", "piece-a"))]
        document["attributes"].append({"attribute_id": "attribute.layering", "assertion": {"state": "KNOWN", "value": {"edges": edges}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_LAYERING_CYCLE")

    def test_instance_layering_refs_must_be_registered(self) -> None:
        document = self.document("product_intelligence.example.json")
        edge = {"over_instance_ref": document["identity"]["entity_ref"], "under_instance_ref": "phantom", "zone_refs": ["body.zone.torso"], "wear_context_ref": "fixture:same-context", "rule_kind": "structural_constraint", "conditions": [], "evidence_refs": [document["evidence"][0]["evidence_id"]]}
        document["attributes"].append({"attribute_id": "attribute.layering", "assertion": {"state": "KNOWN", "value": {"edges": [edge]}, "evidence_refs": [document["evidence"][0]["evidence_id"]]}})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_INSTANCE_REFERENCE")

    def test_jewelry_allowed_values_are_scoped_by_category(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["family"]["value"] = "garment.family.jewelry"
        document["taxonomy"]["category"]["value"] = "garment.category.ring"
        document["attributes"].append({"attribute_id": "attribute.jewelry_form", "assertion": {"state": "KNOWN", "value": ["value.jewelry_form.stud"], "evidence_refs": [document["evidence"][0]["evidence_id"]]}})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_CATEGORY_ALLOWED_VALUE")

    def test_human_corrected_attribute_value_must_use_the_same_axis(self) -> None:
        document = self.document("product_intelligence.example.json")
        predicted = copy.deepcopy(next(item["assertion"] for item in document["attributes"] if item["attribute_id"] == "attribute.pattern"))
        corrected = copy.deepcopy(predicted)
        corrected["value"] = ["garment.category.tshirt"]
        document["human_review"].append({"review_id": "review-test", "target_ref": "attribute.pattern", "decision": "corrected", "predicted_value": predicted, "human_value": corrected, "evidence_refs": [document["evidence"][0]["evidence_id"]], "reviewer_ref": "fixture:reviewer", "reviewed_at": "2026-10-08T00:00:00Z"})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_ALLOWED_VALUE")

    def test_exported_matrix_cannot_drift_from_json(self) -> None:
        path = self.root / "coverage_matrix.csv"
        path.write_text(path.read_text(encoding="utf-8").replace(",True,", ",False,", 1), encoding="utf-8")
        self.assert_code(self.validate(), "CSV_SYNC")

    def test_schema_cannot_request_remote_resolution(self) -> None:
        document = self.document("ontology.schema.json")
        document["$defs"]["node"] = {"$ref": "https://invalid.example/remote-schema.json"}
        self.write("ontology.schema.json", document)
        self.assert_code(self.validate(), "EXTERNAL_SCHEMA_REFERENCE")

    def test_schema_local_reference_must_exist(self) -> None:
        document = self.document("ontology.schema.json")
        document["$defs"]["node"] = {"$ref": "#/$defs/not_existing_for_test"}
        self.write("ontology.schema.json", document)
        self.assert_code(self.validate(), "INVALID_SCHEMA_REFERENCE")


    def cuff_fixture(self, state: str = "KNOWN") -> dict:
        document = self.document("product_intelligence.example.json")
        assertion = {"state": state, "evidence_refs": [document["evidence"][0]["evidence_id"]], "context": {"sleeve_cuff_present": state == "KNOWN"}}
        if state == "KNOWN":
            assertion["value"] = ["value.sleeve_cuff_type.button"]
        else:
            assertion["reason"] = "No distinct cuff confirmed in the reviewed sleeve view"
        document["attributes"].append({"attribute_id": "attribute.sleeve_cuff_type", "assertion": assertion})
        return document

    def test_known_cuff_with_observed_presence_is_valid(self) -> None:
        self.write("product_intelligence.example.json", self.cuff_fixture())
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_cuff_rejects_trouser_hem_value(self) -> None:
        document = self.cuff_fixture()
        document["attributes"][-1]["assertion"]["value"] = ["value.hem.cuffed"]
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_ALLOWED_VALUE")

    def test_known_cuff_without_presence_context_is_rejected(self) -> None:
        document = self.cuff_fixture()
        document["attributes"][-1]["assertion"].pop("context")
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_COMPONENT_CONTEXT")

    def test_sleeveless_rejects_known_cuff(self) -> None:
        document = self.cuff_fixture()
        next(x for x in document["attributes"] if x["attribute_id"] == "attribute.sleeve_length")["assertion"]["value"] = "value.sleeve_length.sleeveless"
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_SLEEVE_DEPENDENCY")

    def test_confirmed_absent_cuff_is_not_applicable(self) -> None:
        self.write("product_intelligence.example.json", self.cuff_fixture("NOT_APPLICABLE"))
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_hidden_cuff_can_remain_unknown(self) -> None:
        document = self.cuff_fixture("UNKNOWN")
        document["attributes"][-1]["assertion"].pop("context")
        self.write("product_intelligence.example.json", document)
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_polera_cannot_drift_to_sweater(self) -> None:
        document = self.document("commercial_mapping.json")
        next(x for x in document["mappings"] if x["commercial_category_id"] == 11)["ontology_targets"] = ["garment.category.sweater"]
        self.write("commercial_mapping.json", document)
        self.assert_code(self.validate(), "POLICY_POLERA")

    def test_accessories_cannot_include_footwear(self) -> None:
        document = self.document("commercial_mapping.json")
        next(x for x in document["mappings"] if x["commercial_category_id"] == 9)["ontology_targets"].append("garment.category.sneaker")
        self.write("commercial_mapping.json", document)
        self.assert_code(self.validate(), "POLICY_ACCESSORIES_FOOTWEAR")

    def test_taxonomy_unknown_candidate_requires_correct_level(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["category"] = {"state": "UNKNOWN", "reason": "Ambiguous", "evidence_refs": [], "candidates": [{"value": "value.style.minimalist", "evidence_refs": [document["evidence"][0]["evidence_id"]]}]}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_CANDIDATE_REFERENCE")

    def test_taxonomy_candidate_cannot_contradict_known_family(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["category"] = {"state": "UNKNOWN", "reason": "Ambiguous", "evidence_refs": [], "candidates": [{"value": "garment.category.trousers", "evidence_refs": [document["evidence"][0]["evidence_id"]]}]}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_CANDIDATE_PATH")

    def test_external_technical_hypothesis_can_remain_unknown(self) -> None:
        document = self.external_measurement_fixture()
        source = document["evidence"][-1]
        source.update(source_type="external_import", authority="unverified")
        document["attributes"][-1]["assertion"] = {"state": "UNKNOWN", "reason": "Unverified similar external variant", "evidence_refs": [], "candidates": [{"value": 77, "evidence_refs": [source["evidence_id"]]}]}
        self.write("product_intelligence.example.json", document)
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def external_measurement_fixture(self, exact: bool = False) -> dict:
        document = self.document("product_intelligence.example.json")
        document["taxonomy"]["family"]["value"] = "garment.family.lower"
        document["taxonomy"]["category"]["value"] = "garment.category.trousers"
        definitions = {a["canonical_id"]: a for a in self.document("ontology.json")["attributes"]}
        for slot in document["attributes"]:
            state = "UNKNOWN" if "garment.family.lower" in definitions[slot["attribute_id"]]["applies_to"] else "NOT_APPLICABLE"
            slot["assertion"] = {"state": state, "reason": "Synthetic trousers fixture with no observed attribute facts", "evidence_refs": []}
        source = {**document["evidence"][0], "evidence_id": "external-measurement", "source_type": "catalog", "authority": "declared", "source_details": {"source_kind": "manufacturer_catalog_extension", "source_scope": "external", "source_url": "https://example.invalid/fixture", "identity_match_status": "similar"}}
        if exact:
            source["source_details"].update(identity_match_status="exact_verified", same_variant_verified=True, external_product_ref="fixture:product", external_variant_ref="fixture:variant", match_assessment_ref="fixture:match", variant_verification_ref="fixture:variant-check")
        document["evidence"].append(source)
        document["attributes"].append({"attribute_id": "attribute.inseam", "assertion": {"state": "KNOWN", "value": 77, "evidence_refs": [source["evidence_id"]]}})
        return document

    def test_external_technical_known_requires_exact_variant(self) -> None:
        self.write("product_intelligence.example.json", self.external_measurement_fixture())
        self.assert_code(self.validate(), "PI_EXTERNAL_IDENTITY")

    def test_extensible_source_kind_cannot_bypass_external_verification(self) -> None:
        document = self.external_measurement_fixture()
        document["evidence"][-1]["source_details"].pop("source_scope")
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_EXTERNAL_IDENTITY")

    def test_exact_documented_external_measurement_is_valid(self) -> None:
        self.write("product_intelligence.example.json", self.external_measurement_fixture(exact=True))
        result = self.validate()
        self.assertTrue(result["valid"], result["errors"])

    def test_real_probability_requires_calibration_reference(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["document_status"] = "draft"
        document["taxonomy"]["family"]["confidence"] = {"confidence_type": "probability", "score": 0.75}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_UNCALIBRATED_PROBABILITY")

    def test_frame_observation_requires_scan_and_timestamp(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["evidence"][0]["source_details"] = {"source_kind": "visual_frame"}
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_FRAME_PROVENANCE")

    def test_catalog_cannot_masquerade_as_visual_frame(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["evidence"][0].update(source_type="catalog", source_details={"source_kind": "visual_frame", "source_scope": "local_catalog", "scan_session_ref": "fixture:scan", "frame_ref": "fixture:frame", "captured_at": "2026-10-08T00:00:00Z"})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_VISUAL_SOURCE_KIND")

    def test_visual_accumulation_requires_multiple_frame_premises(self) -> None:
        document = self.document("product_intelligence.example.json")
        document["evidence"][0].update(source_type="derived_rule", source_details={"source_kind": "visual_accumulated", "scan_session_ref": "fixture:scan"})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_ACCUMULATION_PREMISES")

    def test_human_review_candidates_use_same_attribute_domain(self) -> None:
        document = self.document("product_intelligence.example.json")
        predicted = {"state": "UNKNOWN", "reason": "Unreviewed", "evidence_refs": [], "candidates": [{"value": ["garment.category.tshirt"], "evidence_refs": [document["evidence"][0]["evidence_id"]]}]}
        document["human_review"].append({"review_id": "fixture:invalid-candidate-review", "target_ref": "attribute.styles", "decision": "pending", "predicted_value": predicted, "evidence_refs": []})
        self.write("product_intelligence.example.json", document)
        self.assert_code(self.validate(), "PI_ALLOWED_VALUE")

    def test_definition_cannot_be_approved_without_evidence(self) -> None:
        document = self.document("governance.json")
        document["scopes"][0]["phases"][0]["status"] = "APPROVED"
        self.write("governance.json", document)
        self.assert_code(self.validate(), "GOVERNANCE_REVIEW_EVIDENCE")

    def test_generic_evidence_cannot_replace_definition_approval_gates(self) -> None:
        document = self.document("governance.json")
        scope = document["scopes"][0]
        scope.update(responsible_person_ref="fixture:responsible", ambiguities_reviewed=True)
        scope["phases"][0].update(status="APPROVED", reviewer_ref="fixture:reviewer", reviewed_at="2026-10-08T00:00:00Z", evidence_refs=["fixture:generic-evidence"], decision_ref="fixture:decision")
        self.write("governance.json", document)
        self.assert_code(self.validate(), "GOVERNANCE_DEFINITION_GATES")

    def test_model_approval_cannot_skip_dataset_stage(self) -> None:
        document = self.document("governance.json")
        scope = document["scopes"][0]
        scope["responsible_person_ref"] = "fixture:responsible"
        scope["phases"][3].update(status="APPROVED", reviewer_ref="fixture:reviewer", reviewed_at="2026-10-08T00:00:00Z", evidence_refs=["fixture:model"], decision_ref="fixture:decision")
        self.write("governance.json", document)
        self.assert_code(self.validate(), "GOVERNANCE_PHASE_PREREQUISITE")


if __name__ == "__main__":
    unittest.main()
