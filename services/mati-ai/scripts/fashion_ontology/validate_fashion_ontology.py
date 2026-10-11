#!/usr/bin/env python3
"""Validate the review-only Fashion Ontology without changing any file or service.

Requires jsonschema >= 4 in the interpreter used for this standalone CLI. It does
not import mati_ai, connect to Laravel/PostgreSQL, load models, or write reports.
JSON is emitted to stdout; redirecting it is an explicit caller decision.
"""

from __future__ import annotations

import argparse
import csv
import hashlib
import json
import re
import sys
import unicodedata
from collections import defaultdict
from datetime import datetime
from pathlib import Path
from typing import Any


REQUIRED_FILES = (
    "ontology.json", "ontology.schema.json", "commercial_mapping.json",
    "attribute_applicability.json", "label_schema.json", "body_zones.json",
    "layering.json", "styles.json", "materials.json", "patterns.json",
    "product_intelligence.schema.json", "product_intelligence.example.json",
    "hashes.json", "summary.json", "governance.json",
)
STATE_NAMES = {"KNOWN", "UNKNOWN", "NOT_APPLICABLE"}


def format_checker() -> Any:
    """Check RFC3339 timestamps with the stdlib; no optional format package."""
    from jsonschema import FormatChecker
    checker = FormatChecker()
    @checker.checks("date-time", raises=(ValueError, TypeError))
    def timestamp(value: Any) -> bool:
        if not isinstance(value, str):
            return True
        if not re.fullmatch(r"\d{4}-\d{2}-\d{2}[Tt]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[Zz]|[+-]\d{2}:\d{2})", value):
            return False
        return datetime.fromisoformat(value.replace("z", "+00:00").replace("Z", "+00:00")).tzinfo is not None
    return checker


def normalized_term(term: str) -> str:
    """Accent-, case-, punctuation- and hyphen-insensitive alias comparison."""
    folded = unicodedata.normalize("NFKD", term.casefold())
    plain = "".join(char for char in folded if not unicodedata.combining(char))
    return " ".join(re.findall(r"[^\W_]+", plain, flags=re.UNICODE))


def find_repo_root(path: Path) -> Path:
    for candidate in (path, *path.parents):
        if (candidate / ".git").exists():
            return candidate
    return Path(__file__).resolve().parents[4]


class OntologyValidator:
    def __init__(self, artifact_dir: Path, repo_root: Path | None = None):
        self.root = artifact_dir.resolve()
        self.repo_root = (repo_root or find_repo_root(self.root)).resolve()
        self.artifacts: dict[str, dict[str, Any]] = {}
        self.errors: list[dict[str, str]] = []
        self.checks: dict[str, str] = {}
        self.counts: dict[str, int] = {}
        self.nodes: dict[str, dict[str, Any]] = {}
        self.locations: dict[str, str] = {}
        self.taxonomy_groups: dict[str, str] = {}

    def error(self, code: str, message: str, path: str = "") -> None:
        self.errors.append({"code": code, "path": path, "message": message})

    def phase(self, name: str, callback: Any) -> None:
        previous = len(self.errors)
        callback()
        self.checks[name] = "PASS" if len(self.errors) == previous else "FAIL"

    def load(self) -> None:
        for filename in REQUIRED_FILES:
            if not (self.root / filename).is_file():
                self.error("MISSING_FILE", "Required artifact is missing", filename)
        for path in sorted(self.root.glob("*.json")):
            try:
                def unique_object(pairs: list[tuple[str, Any]]) -> dict[str, Any]:
                    result: dict[str, Any] = {}
                    for key, value in pairs:
                        if key in result:
                            raise ValueError(f"Duplicate JSON object key: {key}")
                        result[key] = value
                    return result
                def invalid_constant(value: str) -> Any:
                    raise ValueError(f"Non-JSON numeric constant: {value}")
                value = json.loads(path.read_text(encoding="utf-8-sig"), object_pairs_hook=unique_object, parse_constant=invalid_constant)
                if not isinstance(value, dict):
                    raise ValueError("Artifact root must be an object")
                self.artifacts[path.name] = value
            except (OSError, ValueError) as exc:
                self.error("PARSE", str(exc), path.name)

    def validate_schema(self) -> None:
        try:
            from jsonschema import Draft202012Validator
        except ImportError:
            self.error("DEPENDENCY", "jsonschema >= 4 is required by this standalone validator; no service dependencies were changed")
            return
        schema = self.artifacts.get("ontology.schema.json")
        pi_schema = self.artifacts.get("product_intelligence.schema.json")
        for filename, document in (("ontology.schema.json", schema), ("product_intelligence.schema.json", pi_schema)):
            if document is None:
                continue
            try:
                Draft202012Validator.check_schema(document)
            except Exception as exc:
                self.error("INVALID_SCHEMA", str(exc), filename)
            def inspect_refs(value: Any) -> None:
                if isinstance(value, dict):
                    reference = value.get("$ref")
                    if isinstance(reference, str):
                        if not reference.startswith("#"):
                            self.error("EXTERNAL_SCHEMA_REFERENCE", "Schemas must be self-contained; remote retrieval is disabled", filename)
                        elif reference != "#":
                            target = document
                            try:
                                if not reference.startswith("#/"):
                                    raise KeyError(reference)
                                for token in reference[2:].split("/"):
                                    token = token.replace("~1", "/").replace("~0", "~")
                                    target = target[int(token)] if isinstance(target, list) else target[token]
                            except (KeyError, IndexError, TypeError, ValueError):
                                self.error("INVALID_SCHEMA_REFERENCE", f"Unresolved local schema reference {reference}", filename)
                    for child in value.values():
                        inspect_refs(child)
                elif isinstance(value, list):
                    for child in value:
                        inspect_refs(child)
            inspect_refs(document)
        if self.errors:
            return
        checker = format_checker()
        validator = Draft202012Validator(schema, format_checker=checker)
        for filename, document in self.artifacts.items():
            if filename.endswith(".schema.json"):
                continue
            current = validator
            if filename == "product_intelligence.example.json":
                current = Draft202012Validator(pi_schema, format_checker=checker)
            else:
                # Validate the selected dispatch branch directly so diagnostics
                # identify the exact missing property instead of dumping a
                # full ontology as an unhelpful top-level oneOf failure.
                for branch in schema.get("oneOf", []):
                    properties = branch.get("allOf", [{}, {}])[-1].get("properties", {})
                    if properties.get("artifact_type", {}).get("const") == document.get("artifact_type"):
                        current = Draft202012Validator({"$schema": schema["$schema"], "$defs": schema["$defs"], "allOf": branch["allOf"]}, format_checker=checker)
                        break
            for violation in sorted(current.iter_errors(document), key=lambda item: str(list(item.absolute_path))):
                location = "/".join(str(part) for part in violation.absolute_path)
                self.error("SCHEMA", violation.message, f"{filename}/{location}")

    def index_nodes(self) -> None:
        ontology = self.artifacts["ontology.json"]
        if not ontology["families"] or not ontology["categories"] or not ontology["attributes"]:
            self.error("EMPTY_CORE", "Families, categories and attribute definitions must not be empty")
        groups: list[tuple[str, str, list[dict[str, Any]]]] = []
        for group in ("families", "categories", "subcategories", "attributes", "relation_types", "relationships"):
            groups.append(("ontology.json", group, ontology.get(group, [])))
        for filename, document in self.artifacts.items():
            if document.get("artifact_type") == "vocabulary":
                groups.append((filename, "concepts", document["concepts"]))
            elif document.get("artifact_type") == "layering":
                groups.extend(((filename, "roles", document["roles"]), (filename, "relations", document["relations"])))
        for filename, group, nodes in groups:
            for offset, node in enumerate(nodes):
                identifier = node["canonical_id"]
                location = f"{filename}/{group}/{offset}"
                if identifier in self.nodes:
                    self.error("DUPLICATE_ID", f"{identifier} already exists at {self.locations[identifier]}", location)
                else:
                    self.nodes[identifier] = node
                    self.locations[identifier] = location
                for language in ("es", "en"):
                    if f"label_{language}" in node and not normalized_term(node[f"label_{language}"]):
                        self.error("LANGUAGE_LABEL", "Language labels must contain meaningful text", location)
                if filename == "ontology.json" and group in {"families", "categories", "subcategories"}:
                    self.taxonomy_groups[identifier] = group
        self.counts = {group: len(ontology[group]) for group in ("families", "categories", "subcategories", "attributes")}
        for group in ("styles", "materials", "patterns", "body_zones"):
            self.counts[group] = len(self.artifacts[f"{group}.json"]["concepts"])
        self.counts["relations"] = len(ontology["relationships"]) + len(self.artifacts["layering.json"]["relations"])
        self.counts["concepts"] = sum(1 for node in self.nodes.values() if "label_es" in node)

    def references(self) -> None:
        ontology = self.artifacts["ontology.json"]
        for filename in ontology["vocabularies"]:
            document = self.artifacts.get(filename)
            if document is None or document.get("artifact_type") not in {"vocabulary", "layering"}:
                self.error("VOCABULARY_REFERENCE", f"Unresolved vocabulary {filename}", "ontology.json/vocabularies")
        vocabulary_files = {filename for filename, document in self.artifacts.items() if document.get("artifact_type") in {"vocabulary", "layering"}}
        if set(ontology["vocabularies"]) != vocabulary_files:
            self.error("VOCABULARY_INDEX", f"Unindexed {sorted(vocabulary_files - set(ontology['vocabularies']))}; unresolved {sorted(set(ontology['vocabularies']) - vocabulary_files)}")
        for identifier, node in self.nodes.items():
            location = self.locations[identifier]
            parent = node.get("parent_id")
            source = self.taxonomy_groups.get(identifier)
            if source == "families" and parent is not None:
                self.error("TAXONOMY_PARENT", "Root garment families must have null parent_id", location)
            if source in {"categories", "subcategories"} and parent is None:
                self.error("TAXONOMY_PARENT", "Categories and subcategories require a parent_id", location)
            if parent and parent not in self.nodes:
                self.error("REFERENCE", f"Unknown parent {parent}", location)
            if parent and parent in self.nodes:
                expected = {"categories": "families", "subcategories": "categories"}.get(source)
                if expected and self.taxonomy_groups.get(parent) != expected:
                    self.error("TAXONOMY_PARENT", f"{source} must have a parent in {expected}", location)
                if source is None and "domain" in node and self.nodes[parent].get("domain") != node["domain"]:
                    self.error("VOCABULARY_PARENT", "Parent must belong to the same vocabulary domain", location)
            for confused in node.get("commonly_confused_with", []):
                if confused not in self.nodes:
                    self.error("REFERENCE", f"Unknown commonly confused concept {confused}", location)
        self.check_cycles({identifier: [node["parent_id"]] for identifier, node in self.nodes.items() if node.get("parent_id")}, "PARENT_CYCLE")
        occupied_families = {category["parent_id"] for category in ontology["categories"]}
        for family in ontology["families"]:
            if family["canonical_id"] not in occupied_families:
                self.error("ORPHAN_FAMILY", "Each proposed family needs at least one defined garment category", family["canonical_id"])
        for attribute in ontology["attributes"]:
            reference = attribute["vocabulary_ref"]
            if reference is not None:
                if reference["file"] not in ontology["vocabularies"]:
                    self.error("ALLOWED_VALUES_REFERENCE", "Attribute vocabulary is not listed in the ontology index", attribute["canonical_id"])
                document = self.artifacts.get(reference["file"])
                values = [] if document is None else document.get("concepts", document.get("roles", []))
                if not any(value.get("domain") == reference["domain"] for value in values):
                    self.error("ALLOWED_VALUES_REFERENCE", f"Unknown or empty vocabulary domain {reference}", attribute["canonical_id"])
                ids = [value["canonical_id"] for value in values if value.get("domain") == reference["domain"]]
                if len(ids) != len(set(ids)):
                    self.error("DUPLICATE_ALLOWED_VALUE", "Allowed values contain duplicate IDs", attribute["canonical_id"])
                if attribute["value_type"] == "ordinal":
                    ranks = [value.get("ordinal_rank") for value in values if value.get("domain") == reference["domain"]]
                    if any(not isinstance(rank, int) or isinstance(rank, bool) for rank in ranks) or len(ranks) != len(set(ranks)):
                        self.error("ORDINAL_RANK", "Ordinal vocabularies require explicit, unique integer ranks", attribute["canonical_id"])
            elif attribute["value_type"] in {"single_label", "multi_label", "ordinal"}:
                self.error("ALLOWED_VALUES_REFERENCE", "Categorical and ordinal attributes require a referenced vocabulary", attribute["canonical_id"])

    def check_cycles(self, graph: dict[str, list[str]], code: str) -> None:
        visiting: set[str] = set()
        done: set[str] = set()
        def visit(identifier: str, chain: list[str]) -> None:
            if identifier in visiting:
                self.error(code, " -> ".join([*chain, identifier]), identifier)
                return
            if identifier in done:
                return
            visiting.add(identifier)
            for parent in graph.get(identifier, []):
                visit(parent, [*chain, identifier])
            visiting.remove(identifier)
            done.add(identifier)
        for identifier in graph:
            visit(identifier, [])

    def aliases(self) -> None:
        buckets: dict[tuple[str, str, str], set[str]] = defaultdict(set)
        for identifier, node in self.nodes.items():
            if "label_es" not in node:
                continue
            group = self.taxonomy_groups.get(identifier)
            scope = {"families": "family", "categories": "category", "subcategories": "subcategory"}.get(group, node.get("domain", "attribute"))
            for language in ("es", "en"):
                terms = [node[f"label_{language}"], *node.get(f"aliases_{language}", [])]
                for term in terms:
                    buckets[(scope, language, normalized_term(term))].add(identifier)
        exemptions: dict[tuple[str, str, str], set[str]] = {}
        for ambiguity in self.artifacts["ontology.json"]["alias_ambiguities"]:
            scope = ambiguity["scope"].removeprefix("taxonomy.")
            key = (scope, ambiguity["language"], normalized_term(ambiguity["term"]))
            if key in exemptions:
                self.error("DUPLICATE_AMBIGUITY", "Ambiguous term declared twice", str(key))
            exemptions[key] = set(ambiguity["concept_ids"])
            if len(exemptions[key]) < 2 or not exemptions[key] <= self.nodes.keys():
                self.error("AMBIGUITY_REFERENCE", "Ambiguity must identify at least two existing concepts", str(key))
        for key, identifiers in buckets.items():
            if len(identifiers) > 1 and exemptions.get(key) != identifiers:
                self.error("ALIAS_CONFLICT", f"Normalized alias maps to {sorted(identifiers)}; declare exact scoped ambiguity", str(key))
        cross_language: dict[tuple[str, str], set[str]] = defaultdict(set)
        for (scope, language, term), identifiers in buckets.items():
            cross_language[(scope, term)].update(identifiers)
        for (scope, term), identifiers in cross_language.items():
            if len(identifiers) > 1 and not any(exemptions.get((scope, language, term)) == identifiers for language in ("es", "en")):
                self.error("CROSS_LANGUAGE_ALIAS_CONFLICT", f"Multilingual term maps to {sorted(identifiers)}; explicit scoped ambiguity required", str((scope, term)))
        # Ambiguity notes may document candidate meanings that are deliberately
        # NOT registered as aliases (POLERA), or cross-domain meanings (jersey).
        # Declaring that uncertainty must not activate automatic normalization.

    def applicability_and_labels(self) -> None:
        ontology = self.artifacts["ontology.json"]
        families = {node["canonical_id"] for node in ontology["families"]}
        categories = {node["canonical_id"]: node["parent_id"] for node in ontology["categories"]}
        attributes = {node["canonical_id"]: node for node in ontology["attributes"]}
        rows: dict[tuple[str, str], dict[str, Any]] = {}
        for row in self.artifacts["attribute_applicability.json"]["rows"]:
            key = (row["attribute_id"], row["family_id"])
            if key in rows:
                self.error("DUPLICATE_APPLICABILITY", "Repeated attribute/family pair", str(key))
            rows[key] = row
            if key[0] not in attributes or key[1] not in families:
                self.error("APPLICABILITY_REFERENCE", "Unknown attribute or family", str(key))
                continue
            attribute = attributes[key[0]]
            applies = key[1] in attribute["applies_to"]
            expected = attribute["requirement"] if applies else "not_applicable"
            if row["status"] != expected:
                self.error("APPLICABILITY_STATUS", f"Expected {expected}", str(key))
            if row["unknown_allowed"] != (attribute["unknown_allowed"] if applies else False):
                self.error("APPLICABILITY_UNKNOWN", "UNKNOWN policy contradicts applicability", str(key))
            expected_scope = sorted(category for category in attribute.get("applies_to_categories", []) if categories.get(category) == key[1])
            if sorted(row["category_scope"]) != expected_scope:
                self.error("APPLICABILITY_SCOPE", "Category scope does not match attribute declaration", str(key))
        expected_pairs = {(attribute, family) for attribute in attributes for family in families}
        if rows.keys() != expected_pairs:
            self.error("APPLICABILITY_COVERAGE", f"Missing {len(expected_pairs - rows.keys())}, unexpected {len(rows.keys() - expected_pairs)} pairs")
        for identifier, attribute in attributes.items():
            if not attribute["unknown_allowed"]:
                self.error("ATTRIBUTE_UNKNOWN", "All applicable attributes must distinguish UNKNOWN from KNOWN and NOT_APPLICABLE", identifier)
            if not attribute["applies_to"] or not set(attribute["applies_to"]) <= families:
                self.error("ATTRIBUTE_APPLIES_TO", "Attribute must refer to at least one existing family", identifier)
            for category in attribute.get("applies_to_categories", []):
                if categories.get(category) not in attribute["applies_to"]:
                    self.error("ATTRIBUTE_CATEGORY_SCOPE", "Unknown category or category outside applicable families", identifier)
            if attribute["trainable_visual"] == "yes" and attribute["visually_learnable"] == "no":
                self.error("VISUAL_TRAINABILITY", "Nonvisual attributes cannot be visual model targets", identifier)
        labels: dict[str, dict[str, Any]] = {}
        for label in self.artifacts["label_schema.json"]["labels"]:
            identifier = label["attribute_id"]
            if identifier in labels:
                self.error("DUPLICATE_LABEL", "Attribute label repeated", identifier)
            labels[identifier] = label
            attribute = attributes.get(identifier)
            if attribute is None:
                self.error("LABEL_REFERENCE", "Label references an unknown attribute", identifier)
                continue
            pairs = {"label_type": "value_type", "allowed_values_ref": "vocabulary_ref", "applicable_families": "applies_to", "requirement": "requirement", "annotation_sources": "annotation_sources", "visually_learnable": "visually_learnable", "annotation_difficulty": "annotation_difficulty", "future_head": "future_head", "trainable_visual": "trainable_visual"}
            for label_key, attribute_key in pairs.items():
                if label[label_key] != attribute[attribute_key]:
                    self.error("LABEL_SYNC", f"{label_key} diverges from attribute {attribute_key}", identifier)
            policy = label["unknown_policy"]
            allowed = policy.get("unknown_allowed") if isinstance(policy, dict) else None
            if allowed is not None and allowed != attribute["unknown_allowed"]:
                self.error("LABEL_UNKNOWN", "Label UNKNOWN policy diverges from attribute", identifier)
            if isinstance(policy, dict) and "states" in policy and (not isinstance(policy["states"], list) or not all(isinstance(state, str) for state in policy["states"]) or set(policy["states"]) != STATE_NAMES):
                self.error("LABEL_STATES", "Label policy must distinguish KNOWN, UNKNOWN and NOT_APPLICABLE", identifier)
        if labels.keys() != attributes.keys():
            self.error("LABEL_COVERAGE", f"Missing {sorted(attributes.keys() - labels.keys())}; unexpected {sorted(labels.keys() - attributes.keys())}")
        conditional = self.artifacts["attribute_applicability.json"].get("conditional_rules", [])
        expected_conditional = {(identifier, f"assertion.context.{attribute['value_constraints']['component_presence_context']}=true") for identifier, attribute in attributes.items() if "component_presence_context" in attribute["value_constraints"]}
        expected_conditional.update({("attribute.weave", "component.textile_structure=woven"), ("attribute.knit_structure", "component.textile_structure=knitted"), ("attribute.sleeve_shape", "sleeve_length!=sleeveless"), ("attribute.sleeve_attachment", "sleeve_length!=sleeveless"), ("attribute.sleeve_cuff_type", "sleeve_length!=sleeveless")})
        seen_conditional: set[tuple[str, str]] = set()
        for rule in conditional:
            identifier = rule.get("attribute_id")
            key = (identifier, rule.get("condition"))
            if key in seen_conditional:
                self.error("CONDITIONAL_APPLICABILITY_DUPLICATE", "Repeated applicability condition for one attribute", str(identifier))
            seen_conditional.add(key)
            if key not in expected_conditional or rule.get("false_state") != "NOT_APPLICABLE" or rule.get("unknown_condition_state") != "UNKNOWN":
                self.error("CONDITIONAL_APPLICABILITY", "Component-presence rule must match attribute declaration and explicit state policy", str(identifier))
        if seen_conditional != expected_conditional:
            self.error("CONDITIONAL_APPLICABILITY_COVERAGE", "Every component-presence condition must have one matrix refinement rule")

    def relationships(self) -> None:
        ontology = self.artifacts["ontology.json"]
        predicates = {node["canonical_id"] for node in ontology["relation_types"]}
        for relation in [*ontology["relationships"], *self.artifacts["layering.json"]["relations"]]:
            for key in ("subject", "object"):
                if relation[key] not in self.nodes:
                    self.error("RELATION_ENDPOINT", f"Unknown {key} {relation[key]}", relation["canonical_id"])
            if relation["predicate"] not in predicates:
                self.error("RELATION_PREDICATE", f"Unknown predicate {relation['predicate']}", relation["canonical_id"])
            if relation["subject"] == relation["object"]:
                self.error("RELATION_SELF", "Self relations are not supported in V1", relation["canonical_id"])
            contextual = relation["rule_kind"] in {"styling_preference", "trend_exception"} or (
                relation["rule_kind"] == "soft_rule" and any(term in relation["predicate"].lower() for term in ("layer", "compatib"))
            )
            if contextual and not relation["conditions"]:
                self.error("RELATION_CONTEXT", "Contextual rules require explicit conditions", relation["canonical_id"])
            if relation["rule_kind"] == "trend_exception" and not relation.get("notes"):
                self.error("TREND_CONTEXT", "Trend exceptions require a temporal knowledge boundary", relation["canonical_id"])

    def spatial_semantics(self) -> None:
        body = self.artifacts["body_zones.json"]
        zones = {node["canonical_id"] for node in body["concepts"]}
        semantics = body.get("semantics", {})
        roles = set(semantics.get("coverage_roles", []))
        if not {"covers", "worn_at", "carried_at", "contacts"} <= roles:
            self.error("BODY_ZONE_ROLES", "Coverage, wearing, carrying and contact roles must be distinct")
        if not set(semantics.get("aggregate_only", [])) <= zones:
            self.error("BODY_ZONE_AGGREGATE", "Aggregate zones must refer to the body-zone vocabulary")
        for binding in body.get("bindings", []):
            if self.taxonomy_groups.get(binding["category_id"]) != "categories":
                self.error("BODY_ZONE_CATEGORY", "Binding references an unknown category", binding["category_id"])
            for zone in [binding["primary_zone"], *binding["secondary_zones"]]:
                if zone not in zones:
                    self.error("BODY_ZONE_REFERENCE", f"Unknown zone {zone}", binding["category_id"])
            if binding["primary_zone"] in binding["secondary_zones"]:
                self.error("BODY_ZONE_DUPLICATE", "Primary zone repeated as secondary", binding["category_id"])
            if binding["coverage_role"] not in roles:
                self.error("BODY_ZONE_ROLE", "Unknown coverage role", binding["category_id"])
            family = next((node["parent_id"] for node in self.artifacts["ontology.json"]["categories"] if node["canonical_id"] == binding["category_id"]), "")
            if "BAG" in family.upper() and binding["coverage_role"] == "covers":
                self.error("CARRY_IS_NOT_COVERAGE", "A carrying location does not imply anatomical garment coverage", binding["category_id"])
        graph: dict[str, list[str]] = defaultdict(list)
        for relation in self.artifacts["layering.json"]["relations"]:
            instance_scope = any(isinstance(condition, dict) and condition.get("scope") in {"instance", "garment_instance"} for condition in relation["conditions"])
            if relation["rule_kind"] == "structural_constraint" and instance_scope:
                predicate = relation["predicate"].upper()
                if "OVER" in predicate:
                    graph[relation["subject"]].append(relation["object"])
                elif "UNDER" in predicate:
                    graph[relation["object"]].append(relation["subject"])
        self.check_cycles(graph, "LAYERING_INSTANCE_CYCLE")

    def commercial_mapping(self) -> None:
        document = self.artifacts["commercial_mapping.json"]
        snapshot = document["source_snapshot"]
        source = (self.repo_root / snapshot["path"]).resolve()
        if not source.is_relative_to(self.repo_root):
            self.error("COMMERCIAL_SOURCE_PATH", "Snapshot path must remain within the repository", snapshot["path"])
            return
        try:
            raw = source.read_bytes()
            categories = json.loads(raw.decode("utf-8-sig"))["categories"]
        except (OSError, ValueError, KeyError, TypeError) as exc:
            self.error("COMMERCIAL_SOURCE", str(exc), snapshot["path"])
            return
        if hashlib.sha256(raw).hexdigest() != snapshot["sha256"]:
            self.error("COMMERCIAL_SOURCE_HASH", "Commercial snapshot fingerprint changed", snapshot["path"])
        expected = {str(row["id_categoria"]): row["nom_cat"] for row in categories}
        if len(expected) != len(categories):
            self.error("COMMERCIAL_SOURCE_DUPLICATE", "Source snapshot repeats category IDs", snapshot["path"])
        actual: dict[str, str] = {}
        for mapping in document["mappings"]:
            identifier = str(mapping["commercial_category_id"])
            if identifier in actual:
                self.error("COMMERCIAL_DUPLICATE", "Commercial category mapped twice", identifier)
            actual[identifier] = mapping["commercial_name"]
            if expected.get(identifier) != mapping["commercial_name"]:
                self.error("COMMERCIAL_NAME", "Commercial ID/name does not match the read-only source snapshot", identifier)
            for target in mapping["ontology_targets"]:
                if target not in self.taxonomy_groups:
                    self.error("COMMERCIAL_TARGET", f"Unknown taxonomy target {target}", identifier)
            if mapping["mapping_type"] in {"one_to_many", "ambiguous", "ambiguous_local_term", "candidate_1_to_many"} and len(mapping["ontology_targets"]) < 2:
                self.error("COMMERCIAL_CARDINALITY", "Ambiguous or one-to-many mappings require multiple targets", identifier)
            if mapping["mapping_type"] in {"one_to_one", "candidate_1_to_1"} and len(mapping["ontology_targets"]) != 1:
                self.error("COMMERCIAL_CARDINALITY", "One-to-one mappings require exactly one target", identifier)
        if actual.keys() != expected.keys():
            self.error("COMMERCIAL_COVERAGE", f"Missing {sorted(expected.keys() - actual.keys())}; unexpected {sorted(actual.keys() - expected.keys())}")

    def season_mapping(self) -> None:
        document = self.artifacts.get("commercial_season_mapping.json")
        if document is None:
            return
        seen: set[int] = set()
        for mapping in document["mappings"]:
            identifier = mapping["commercial_season_id"]
            if identifier in seen:
                self.error("SEASON_DUPLICATE", "Repeated commercial season ID", str(identifier))
            seen.add(identifier)
            targets = mapping["semantic_targets"]
            for target in targets:
                if self.nodes.get(target, {}).get("domain") != "calendar_season":
                    self.error("SEASON_TARGET", f"Target {target} is not a calendar-season concept", str(identifier))
            if mapping["mapping_type"] == "NO_DIRECT_MAPPING" and targets:
                self.error("SEASON_NO_MAPPING", "NO_DIRECT_MAPPING must not invent semantic targets", str(identifier))
            if mapping["mapping_type"] == "candidate_contextual" and not targets:
                self.error("SEASON_CANDIDATE", "A candidate mapping must identify possible calendar seasons", str(identifier))

    def exported_matrices(self) -> None:
        specifications = (
            ("coverage_matrix.csv", self.artifacts["attribute_applicability.json"]["rows"], ["attribute_id", "family_id", "status", "unknown_allowed", "category_scope"]),
            ("annotation_matrix.csv", self.artifacts["label_schema.json"]["labels"], ["attribute_id", "label_type", "annotation_sources", "visually_learnable", "annotation_difficulty", "future_head", "trainable_visual"]),
        )
        for filename, source, fields in specifications:
            try:
                with (self.root / filename).open(encoding="utf-8-sig", newline="") as handle:
                    reader = csv.DictReader(handle)
                    if reader.fieldnames != fields:
                        self.error("CSV_COLUMNS", f"Expected columns {fields}", filename)
                        continue
                    actual = []
                    for row in reader:
                        if None in row or any(value is None for value in row.values()):
                            raise ValueError("CSV row has missing or extra columns")
                        if "unknown_allowed" in row:
                            if row["unknown_allowed"].lower() not in {"true", "false"}:
                                raise ValueError("unknown_allowed must be an explicit boolean")
                            row["unknown_allowed"] = row["unknown_allowed"].lower() == "true"
                        for key in ("category_scope", "annotation_sources"):
                            if key in row:
                                row[key] = json.loads(row[key])
                        if row.get("future_head") == "":
                            row["future_head"] = None
                        actual.append(row)
                expected = [{key: row[key] for key in fields} for row in source]
                if actual != expected:
                    self.error("CSV_SYNC", "Exported matrix differs from its authoritative JSON source", filename)
            except (OSError, ValueError, TypeError) as exc:
                self.error("CSV_PARSE", str(exc), filename)

    def product_intelligence(self) -> None:
        """Cross-artifact checks that JSON Schema cannot express by itself."""
        from jsonschema import Draft202012Validator
        document = self.artifacts["product_intelligence.example.json"]
        schema = self.artifacts["product_intelligence.schema.json"]
        ontology = self.artifacts["ontology.json"]
        attributes = {item["canonical_id"]: item for item in ontology["attributes"]}
        if document["ontology_content_hash"] != hashlib.sha256((self.root / "ontology.json").read_bytes()).hexdigest():
            self.error("PI_ONTOLOGY_HASH", "Product Intelligence must identify the exact ontology content it uses")

        def unique_records(rows: list[dict[str, Any]], key: str, code: str) -> dict[str, dict[str, Any]]:
            result: dict[str, dict[str, Any]] = {}
            for row in rows:
                identifier = row[key]
                if identifier in result:
                    self.error(code, f"Repeated {key}", identifier)
                result[identifier] = row
            return result

        evidence = unique_records(document["evidence"], "evidence_id", "PI_DUPLICATE_EVIDENCE")
        models = unique_records(document["model_metadata"], "model_version_id", "PI_DUPLICATE_MODEL")
        slots = unique_records(document["attributes"], "attribute_id", "PI_DUPLICATE_ATTRIBUTE")
        unique_records(document["human_review"], "review_id", "PI_DUPLICATE_REVIEW")
        evidence_graph = {identifier: item.get("supporting_evidence_refs", []) for identifier, item in evidence.items()}
        for identifier, item in evidence.items():
            if item["authority"] == "design_fixture" and document["document_status"] != "design_fixture":
                self.error("PI_FIXTURE_AUTHORITY", "Synthetic evidence cannot establish reviewed real product facts", identifier)
            for reference in item.get("supporting_evidence_refs", []):
                if reference not in evidence:
                    self.error("PI_EVIDENCE_REFERENCE", f"Unknown supporting evidence {reference}", identifier)
            if item.get("model_version_ref") and item["model_version_ref"] not in models:
                self.error("PI_MODEL_REFERENCE", "Evidence references an unknown model version", identifier)
            if item["source_type"] == "vision_model" and not item.get("model_version_ref"):
                self.error("PI_MODEL_PROVENANCE", "Visual predictions must identify a model version", identifier)
            if item["source_type"] == "derived_rule" and not item.get("supporting_evidence_refs"):
                self.error("PI_DERIVED_PREMISES", "Derived claims require explicit supporting premise evidence", identifier)
        self.check_cycles(evidence_graph, "PI_EVIDENCE_CYCLE")
        for identifier, item in evidence.items():
            details = item.get("source_details", {})
            kind = details.get("source_kind")
            if kind == "visual_frame" and item["source_type"] != "vision_model":
                self.error("PI_VISUAL_SOURCE_KIND", "Model frame observations must preserve vision_model provenance", identifier)
            if kind == "visual_accumulated" and item["source_type"] != "derived_rule":
                self.error("PI_VISUAL_SOURCE_KIND", "Visual accumulation must preserve derived_rule provenance", identifier)
            if kind == "visual_frame" and not all(details.get(key) for key in ("scan_session_ref", "frame_ref", "captured_at")):
                self.error("PI_FRAME_PROVENANCE", "Frame observations require scan session, frame and capture timestamp", identifier)
            if kind == "visual_accumulated":
                refs = item.get("supporting_evidence_refs", [])
                if len(refs) < 2 or not details.get("scan_session_ref"):
                    self.error("PI_ACCUMULATION_PREMISES", "Accumulated visual evidence requires at least two distinct observations and one scan session", identifier)
                frame_ids = {evidence.get(ref, {}).get("source_details", {}).get("frame_ref") for ref in refs}
                if len(frame_ids - {None}) < 2:
                    self.error("PI_ACCUMULATION_DISTINCT_FRAMES", "Repeated evaluations of one frame are not independent visual observations", identifier)
                for ref in refs:
                    premise = evidence.get(ref, {}).get("source_details", {})
                    if premise.get("source_kind") != "visual_frame" or premise.get("scan_session_ref") != details.get("scan_session_ref"):
                        self.error("PI_ACCUMULATION_SESSION", "Accumulated observations must be frames of the same item scan session", identifier)
        def check_confidences(value: Any) -> None:
            if isinstance(value, dict):
                if value.get("confidence_type") == "probability" and document["document_status"] != "design_fixture" and not value.get("calibration_ref"):
                    self.error("PI_UNCALIBRATED_PROBABILITY", "Real probability estimates require a referenced calibration record; similarity scores must retain their score type")
                for child in value.values():
                    check_confidences(child)
            elif isinstance(value, list):
                for child in value:
                    check_confidences(child)
        check_confidences(document)


        taxonomy = document["taxonomy"]
        known_taxonomy: dict[str, str] = {}
        for level, group in (("family", "families"), ("category", "categories"), ("subcategory", "subcategories")):
            assertion = taxonomy[level]
            if assertion["state"] == "KNOWN":
                value = assertion["value"]
                if not isinstance(value, str) or self.taxonomy_groups.get(value) != group:
                    self.error("PI_TAXONOMY_REFERENCE", f"Known {level} must reference an existing {group} concept", level)
                else:
                    known_taxonomy[level] = value
            elif assertion["state"] == "NOT_APPLICABLE" and level != "subcategory":
                self.error("PI_TAXONOMY_STATE", "Family/category uncertainty must use UNKNOWN", level)
        for level, parent_level in (("category", "family"), ("subcategory", "category")):
            if level in known_taxonomy and self.nodes[known_taxonomy[level]]["parent_id"] != known_taxonomy.get(parent_level):
                self.error("PI_TAXONOMY_PATH", f"Known {level} requires its matching known {parent_level}", level)
        family = known_taxonomy.get("family")
        category = known_taxonomy.get("category")

        def evidence_refs(assertion: dict[str, Any], location: str, attribute: dict[str, Any] | None = None, candidate: bool = False) -> None:
            refs = assertion["evidence_refs"]
            if attribute is None and "affinity_scores" in assertion:
                self.error("PI_AFFINITY_SCOPE", "Affinity scores belong to applicable style/usage multi-label attributes", location)
            if assertion["state"] == "KNOWN" and not refs:
                self.error("PI_KNOWN_EVIDENCE", "Known assertions require supporting evidence", location)
            for reference in refs:
                source = evidence.get(reference)
                if source is None:
                    self.error("PI_EVIDENCE_REFERENCE", f"Unknown evidence {reference}", location)
                elif attribute is not None and assertion["state"] == "KNOWN":
                    if source["source_type"] == "rag":
                        self.error("PI_RAG_AUTHORITY", "Retrieved explanations cannot establish known product facts", location)
                    if not candidate and source["authority"] != "design_fixture" and source["source_type"] not in attribute["annotation_sources"] and not (source.get("source_details", {}).get("source_kind") == "visual_accumulated" and "vision_model" in attribute["annotation_sources"]):
                        self.error("PI_ANNOTATION_SOURCE", "Evidence source is not an approved annotation source for this attribute", location)
                    if attribute["visually_learnable"] == "no" and (source["source_type"] == "vision_model" or source.get("source_details", {}).get("source_kind") == "visual_accumulated"):
                        self.error("PI_NONVISUAL_EVIDENCE", "A visual prediction cannot establish a nonvisual attribute", location)
                    if not candidate and attribute["visually_learnable"] == "no" and source["authority"] != "design_fixture" and (source["source_type"] == "external_import" or source.get("source_details", {}).get("external_product_ref") or (source["source_type"] == "catalog" and (source.get("source_details", {}).get("source_url") or source.get("source_details", {}).get("source_scope") not in {"local_catalog", "physical"})) or source.get("source_details", {}).get("source_kind") in {"manufacturer_sheet", "supplier_declaration", "unofficial_external"}):
                        details = source.get("source_details", {})
                        if details.get("identity_match_status") != "exact_verified" or details.get("same_variant_verified") is not True:
                            self.error("PI_EXTERNAL_IDENTITY", "External technical facts require verified exact product and variant identity", location)

        def validate_definition(value: Any, name: str, location: str, array: bool = False) -> bool:
            definition = {"type": "array", "minItems": 1, "items": {"$ref": f"#/$defs/{name}"}} if array else {"$ref": f"#/$defs/{name}"}
            current = Draft202012Validator({"$schema": schema["$schema"], "$defs": schema["$defs"], **definition})
            failures = list(current.iter_errors(value))
            for violation in failures:
                self.error("PI_STRUCTURED_VALUE", violation.message, location)
            return not failures

        def validate_value(identifier: str, assertion: dict[str, Any], candidate: bool = False) -> None:
            attribute = attributes[identifier]
            if assertion["state"] == "UNKNOWN":
                seen_proposals: set[str] = set()
                for proposal in assertion.get("candidates", []):
                    key = json.dumps(proposal["value"], sort_keys=True)
                    if key in seen_proposals:
                        self.error("PI_DUPLICATE_CANDIDATE", "Repeated attribute candidate", identifier)
                    seen_proposals.add(key)
                    tentative = {**assertion, **proposal, "state": "KNOWN"}
                    tentative.pop("candidates", None)
                    validate_value(identifier, tentative, candidate=True)
            evidence_refs(assertion, identifier, attribute, candidate)
            applicable: bool | None = None
            if family is not None:
                applicable = family in attribute["applies_to"]
                scope = attribute.get("applies_to_categories", [])
                if applicable and scope:
                    applicable = category in scope if category is not None else None
            sleeve = slots.get("attribute.sleeve_length", {}).get("assertion", {})
            if identifier in {"attribute.sleeve_shape", "attribute.sleeve_attachment", "attribute.sleeve_cuff_type"} and sleeve.get("state") == "KNOWN" and sleeve.get("value") == "value.sleeve_length.sleeveless":
                applicable = False
                if assertion["state"] != "NOT_APPLICABLE":
                    self.error("PI_SLEEVE_DEPENDENCY", "Confirmed sleeveless garments require N/A sleeve shape/attachment/cuff", identifier)
            category_values = attribute["value_constraints"].get("values_by_category", {})
            if category and category in category_values and not category_values[category]:
                applicable = False
            presence_key = attribute["value_constraints"].get("component_presence_context")
            if presence_key:
                presence = assertion.get("context", {}).get(presence_key)
                if presence is False:
                    applicable = False
                    if not assertion["evidence_refs"]:
                        self.error("PI_COMPONENT_ABSENCE_EVIDENCE", "Confirmed absence of a textile/material component requires evidence", identifier)
                elif presence is not None and not isinstance(presence, bool):
                    self.error("PI_COMPONENT_CONTEXT", "Component presence context must be an explicit boolean", identifier)
                elif presence is None and assertion["state"] == "KNOWN" and not candidate:
                    self.error("PI_COMPONENT_CONTEXT", "Known component-dependent facts require explicit documented component presence", identifier)
            if applicable is False and assertion["state"] != "NOT_APPLICABLE":
                self.error("PI_APPLICABILITY", "This attribute is outside its family/category or confirmed component scope", identifier)
            elif applicable is True and assertion["state"] == "NOT_APPLICABLE" and identifier not in {"attribute.weave", "attribute.knit_structure"}:
                self.error("PI_APPLICABILITY", "Applicable attributes must use KNOWN or UNKNOWN", identifier)
            elif applicable is None and assertion["state"] in {"KNOWN", "NOT_APPLICABLE"} and not candidate:
                self.error("PI_APPLICABILITY", "Known/N/A scope requires an established family/category", identifier)
            if assertion["state"] != "KNOWN":
                return
            value = assertion["value"]
            label_type = attribute["value_type"]
            valid_type = {
                "single_label": isinstance(value, str), "ordinal": isinstance(value, str),
                "multi_label": isinstance(value, list) and all(isinstance(item, str) for item in value),
                "continuous": isinstance(value, (int, float)) and not isinstance(value, bool),
                "boolean": isinstance(value, bool), "structured": isinstance(value, (dict, list)),
            }[label_type]
            if not valid_type:
                self.error("PI_VALUE_TYPE", f"Value does not match {label_type}", identifier)
                return
            if label_type == "multi_label" and len(value) != len(set(value)):
                self.error("PI_DUPLICATE_VALUE", "Multi-label values must be unique", identifier)
            if label_type == "multi_label" and not value and assertion.get("annotation_completeness") != "complete":
                self.error("PI_EMPTY_MULTILABEL", "An empty known set is permitted only with complete annotation and evidence", identifier)
            reference = attribute["vocabulary_ref"]
            if reference is not None:
                values = value if label_type == "multi_label" else [value]
                for concept in values:
                    if self.nodes.get(concept, {}).get("domain") != reference["domain"]:
                        self.error("PI_ALLOWED_VALUE", f"{concept} does not belong to {reference['domain']}", identifier)
            if "affinity_scores" in assertion:
                if label_type != "multi_label" or reference is None or reference["file"] not in {"styles.json", "usage.json"}:
                    self.error("PI_AFFINITY_SCOPE", "Affinity scores require a style/usage multi-label axis", identifier)
                seen_affinities: set[str] = set()
                for affinity in assertion["affinity_scores"]:
                    concept = affinity["concept_id"]
                    if concept in seen_affinities:
                        self.error("PI_DUPLICATE_AFFINITY", "Each concept can have one independent affinity estimate", identifier)
                    seen_affinities.add(concept)
                    if not isinstance(value, list) or concept not in value:
                        self.error("PI_AFFINITY_REFERENCE", "Affinity concept must occur in the known canonical value list", identifier)
                    evidence_refs({"state": "KNOWN", "evidence_refs": affinity["evidence_refs"]}, identifier, attribute)
            constraints = attribute["value_constraints"]
            if constraints.get("context_required") and not assertion.get("context") and not candidate:
                self.error("PI_CONTEXT", "This known attribute requires explicit wear/use context", identifier)
            if "values_by_category" in constraints:
                values = value if label_type == "multi_label" else [value]
                if (category is None and not candidate) or (category is not None and any(concept not in constraints["values_by_category"].get(category, []) for concept in values)):
                    self.error("PI_CATEGORY_ALLOWED_VALUE", "Attribute value is outside the known category's allowed subset", identifier)
            if label_type == "continuous":
                for key, comparison in (("minimum", lambda bound: value < bound), ("maximum", lambda bound: value > bound)):
                    if key in constraints and comparison(constraints[key]):
                        self.error("PI_VALUE_RANGE", f"Value violates {key}", identifier)
            if identifier == "attribute.spatial_semantics" and validate_definition(value, "spatial_value_list", identifier):
                zones = {item["canonical_id"] for item in self.artifacts["body_zones.json"]["concepts"]}
                for binding in value:
                    for zone in [binding["primary_zone"], *binding["secondary_zones"]]:
                        if zone not in zones:
                            self.error("PI_BODY_ZONE_REFERENCE", f"Unknown anatomical zone {zone}", identifier)
                    if binding["primary_zone"] in binding["secondary_zones"]:
                        self.error("PI_BODY_ZONE_DUPLICATE", "Primary zone repeated as secondary", identifier)
                    if family and "bag" in family and binding["coverage_role"] == "covers":
                        self.error("PI_CARRY_IS_NOT_COVERAGE", "A bag carrying site cannot be garment coverage", identifier)
            if identifier == "attribute.fiber_composition" and validate_definition(value, "fiber_composition", identifier):
                components: set[str] = set()
                for component in value:
                    component_universe = set(constraints.get("components", [])) | set(document["identity"].get("component_refs", []))
                    if component["component_ref"] not in component_universe:
                        self.error("PI_COMPONENT_REFERENCE", "Fiber composition component must be declared or a named ontology component", identifier)
                    if component["component_ref"] in components:
                        self.error("PI_DUPLICATE_COMPONENT", "Composition repeats a component", identifier)
                    components.add(component["component_ref"])
                    fibers = component["fibers"]
                    fiber_ids = [item["fiber_id"] for item in fibers]
                    if len(fiber_ids) != len(set(fiber_ids)):
                        self.error("PI_DUPLICATE_FIBER", "Composition repeats a fiber within a component", identifier)
                    for fiber in fiber_ids:
                        if self.nodes.get(fiber, {}).get("domain") != "fiber":
                            self.error("PI_FIBER_REFERENCE", f"Unknown fiber {fiber}", identifier)
                    total = sum(item.get("percent", 0) for item in fibers)
                    if total > 100 + constraints.get("tolerance", 0.5):
                        self.error("PI_COMPOSITION_TOTAL", "Component percentages exceed 100", identifier)
                    if component["composition_completeness"] == "complete" and (any("percent" not in item for item in fibers) or abs(total - 100) > constraints.get("tolerance", 0.5)):
                        self.error("PI_COMPOSITION_TOTAL", "Complete component composition requires percentages totaling 100 within tolerance", identifier)
            if identifier == "attribute.observed_color":
                validate_definition(value, "observed_color", identifier)
            if identifier == "attribute.insulation" and validate_definition(value, "insulation", identifier):
                component_universe = {"shell", "lining", "trim", "fill"} | set(document["identity"].get("component_refs", []))
                if value["component_ref"] not in component_universe:
                    self.error("PI_COMPONENT_REFERENCE", "Insulation component must be declared or named", identifier)
                for measurement in value.get("declared_measurements", []):
                    for reference in measurement["evidence_refs"]:
                        if reference not in evidence:
                            self.error("PI_EVIDENCE_REFERENCE", "Insulation measurement evidence is unresolved", identifier)

        def taxonomy_candidates(level: str, assertion: dict[str, Any]) -> None:
            group = {"family": "families", "category": "categories", "subcategory": "subcategories"}[level]
            seen: set[str] = set()
            for proposal in assertion.get("candidates", []):
                value = proposal["value"]
                if not isinstance(value, str) or self.taxonomy_groups.get(value) != group:
                    self.error("PI_CANDIDATE_REFERENCE", "Taxonomy candidates must reference the requested taxonomy level", level)
                elif value in seen:
                    self.error("PI_DUPLICATE_CANDIDATE", "Repeated taxonomy candidate", level)
                else:
                    seen.add(value)
                    parent_level = {"category": "family", "subcategory": "category"}.get(level)
                    if parent_level in known_taxonomy and self.nodes[value]["parent_id"] != known_taxonomy[parent_level]:
                        self.error("PI_CANDIDATE_PATH", "Candidates cannot contradict an established taxonomy parent", level)
                evidence_refs({"state": "UNKNOWN", "evidence_refs": proposal["evidence_refs"]}, f"taxonomy.{level}.candidates")
        for level, assertion in taxonomy.items():
            evidence_refs(assertion, f"taxonomy.{level}")
            taxonomy_candidates(level, assertion)
        for identifier, slot in slots.items():
            if identifier not in attributes:
                self.error("PI_ATTRIBUTE_REFERENCE", "Unknown ontology attribute", identifier)
            else:
                validate_value(identifier, slot["assertion"])

        if family:
            for identifier, attribute in attributes.items():
                if attribute["requirement"] == "required" and family in attribute["applies_to"] and (not attribute.get("applies_to_categories") or category in attribute["applies_to_categories"]) and identifier not in slots:
                    self.error("PI_REQUIRED_ATTRIBUTE", "Required attributes need explicit KNOWN/UNKNOWN assertions", identifier)
        for section in ("colors", "materials", "styles", "spatial_semantics", "usage_semantics"):
            for identifier in document[section]["attribute_refs"]:
                if identifier not in slots:
                    self.error("PI_SECTION_REFERENCE", "Section views must reference present central assertions", section)
        for color in document["colors"]["commercial_color_refs"]:
            for reference in color["evidence_refs"]:
                if reference not in evidence:
                    self.error("PI_EVIDENCE_REFERENCE", "Commercial color reference has unknown evidence", "colors")
        textile = slots.get("attribute.textile_structure", {}).get("assertion", {})
        for identifier, expected in (("attribute.weave", "value.textile_structure.woven"), ("attribute.knit_structure", "value.textile_structure.knitted")):
            assertion = slots.get(identifier, {}).get("assertion", {})
            if assertion.get("state") == "KNOWN" and (textile.get("state") != "KNOWN" or textile.get("value") != expected):
                context = assertion.get("context", {})
                if not context.get("component_ref") or context.get("textile_structure_ref") != expected:
                    self.error("PI_TEXTILE_DEPENDENCY", "Known weave/knit requires corresponding known textile structure or explicit component context", identifier)
            if assertion.get("state") == "NOT_APPLICABLE" and assertion.get("context", {}).get("textile_component_present") is not False and (textile.get("state") != "KNOWN" or textile.get("value") == expected):
                self.error("PI_TEXTILE_DEPENDENCY", "N/A weave/knit requires confirmed different textile structure", identifier)
        layering = slots.get("attribute.layering", {}).get("assertion", {})
        if layering.get("state") == "KNOWN":
            value = layering["value"]
            if not validate_definition(value, "layering_value", "attribute.layering"):
                self.error("PI_LAYERING_VALUE", "Layering is an object containing role_refs and/or edges", "attribute.layering")
            else:
                for role in value.get("role_refs", []):
                    if self.nodes.get(role, {}).get("domain") != "layering_role":
                        self.error("PI_LAYER_ROLE", "Unknown contextual layer role", "attribute.layering")
                graphs: dict[tuple[str, str], dict[str, list[str]]] = defaultdict(lambda: defaultdict(list))
                instances = {document["identity"]["entity_ref"]} | set(document["identity"].get("component_refs", [])) | set(document.get("related_instance_refs", []))
                for edge in value.get("edges", []):
                    if not validate_definition(edge, "layer_edge", "attribute.layering"):
                        continue
                    for reference in edge["evidence_refs"]:
                        if reference not in evidence:
                            self.error("PI_EVIDENCE_REFERENCE", "Layer edge evidence is unresolved", "attribute.layering")
                    if not edge["evidence_refs"]:
                        self.error("PI_LAYERING_EVIDENCE", "Declared instance ordering needs evidence", "attribute.layering")
                    if edge["rule_kind"] != "structural_constraint" and not edge["conditions"]:
                        self.error("PI_LAYERING_CONTEXT", "Styling/contextual instance ordering requires conditions", "attribute.layering")
                    if edge["over_instance_ref"] not in instances or edge["under_instance_ref"] not in instances:
                        self.error("PI_INSTANCE_REFERENCE", "Layer edges must identify registered product/component/related instances", "attribute.layering")
                    for zone in edge["zone_refs"]:
                        if self.nodes.get(zone, {}).get("domain") != "body_zone":
                            self.error("PI_BODY_ZONE_REFERENCE", "Layer edge zone is unresolved", "attribute.layering")
                        # Any declared instance over/under relation is an order;
                        # stylistic provenance cannot make a physical cycle valid.
                        graphs[(zone, edge["wear_context_ref"])][edge["over_instance_ref"]].append(edge["under_instance_ref"])
                for graph in graphs.values():
                    self.check_cycles(graph, "PI_LAYERING_CYCLE")
        for review in document["human_review"]:
            target = review["target_ref"]
            if target not in slots and target not in {f"taxonomy.{level}" for level in taxonomy}:
                self.error("PI_REVIEW_TARGET", "Human review target does not resolve to a central assertion", target)
            if review.get("model_version_ref") and review["model_version_ref"] not in models:
                self.error("PI_MODEL_REFERENCE", "Review references an unknown model version", target)
            for reference in review["evidence_refs"]:
                if reference not in evidence:
                    self.error("PI_EVIDENCE_REFERENCE", "Review evidence is unresolved", target)
            for field in ("predicted_value", "human_value"):
                if field not in review:
                    continue
                assertion = review[field]
                if target in attributes:
                    validate_value(target, assertion)
                else:
                    evidence_refs(assertion, target)
                    if target.startswith("taxonomy.") and target.split(".", 1)[1] in taxonomy:
                        taxonomy_candidates(target.split(".", 1)[1], assertion)
                    if target.startswith("taxonomy.") and assertion["state"] == "KNOWN":
                        level = target.split(".", 1)[1]
                        expected_group = {"family": "families", "category": "categories", "subcategory": "subcategories"}.get(level)
                        value = assertion["value"]
                        if not isinstance(value, str) or self.taxonomy_groups.get(value) != expected_group:
                            self.error("PI_REVIEW_TAXONOMY", "Review taxonomy value must reference the correct ontology level", target)
                        else:
                            parent_level = {"category": "family", "subcategory": "category"}.get(level)
                            if parent_level and self.nodes[value]["parent_id"] != known_taxonomy.get(parent_level):
                                self.error("PI_REVIEW_TAXONOMY", "Review taxonomy value must follow the known parent path", target)
        if document["document_status"] == "human_reviewed" and (not document["human_review"] or any(item["decision"] == "pending" for item in document["human_review"])):
            self.error("PI_HUMAN_REVIEW_STATUS", "Human-reviewed status requires completed human review records")

    def hashes_and_summary(self) -> None:
        manifest = self.artifacts["hashes.json"]
        actual_files = {path.name for path in self.root.iterdir() if path.is_file() and path.suffix in {".json", ".csv"} and path.name != "hashes.json"}
        if set(manifest["files"]) != actual_files:
            self.error("HASH_FILE_SET", f"Missing {sorted(actual_files - manifest['files'].keys())}; unexpected {sorted(manifest['files'].keys() - actual_files)}")
        for filename, expected in manifest["files"].items():
            if Path(filename).name != filename:
                self.error("HASH_PATH", "Hash entries must be local artifact basenames", filename)
                continue
            path = self.root / filename
            if path.is_file() and hashlib.sha256(path.read_bytes()).hexdigest() != expected:
                self.error("HASH_MISMATCH", "Artifact content changed after manifest generation", filename)
        for filename in manifest["generated_reports"]:
            if filename not in actual_files:
                self.error("GENERATED_REPORT_REFERENCE", "Generated report is absent from artifact directory", filename)
        for key, count in self.artifacts["summary.json"]["counts"].items():
            normalized_key = key.removesuffix("_count")
            if normalized_key in self.counts and count != self.counts[normalized_key]:
                self.error("SUMMARY_COUNT", f"Expected {self.counts[normalized_key]}, found {count}", key)

    def human_policies(self) -> None:
        mappings = {m["commercial_category_id"]: m for m in self.artifacts["commercial_mapping.json"]["mappings"]}
        if mappings.get(11, {}).get("ontology_targets") != ["garment.category.tshirt"]:
            self.error("POLICY_POLERA", "Human decision H01 restricts commercial POLERA ID11 to tshirt")
        if set(mappings.get(8, {}).get("ontology_targets", [])) != {"garment.category.polo", "garment.category.sweater", "garment.category.cardigan"}:
            self.error("POLICY_KNITWEAR", "Human decision H02 preserves three primary per-product candidates")
        targets = mappings.get(9, {}).get("ontology_targets", [])
        if any(self.nodes.get(ref, {}).get("parent_id") == "garment.family.footwear" for ref in targets):
            self.error("POLICY_ACCESSORIES_FOOTWEAR", "Human decision H03 excludes footwear from commercial ACCESORIOS")
        if "garment.category.tie" in targets:
            self.error("POLICY_ACCESSORIES_SCOPE", "Tie remains outside the explicitly approved accessories bridge")
        cuff = self.nodes.get("attribute.sleeve_cuff_type", {})
        if cuff.get("vocabulary_ref") != {"file": "attribute_values.json", "domain": "sleeve_cuff_type"}:
            self.error("POLICY_CUFF_AXIS", "Sleeve cuff types require their independent vocabulary axis")
        label = next((x for x in self.artifacts["label_schema.json"]["labels"] if x["attribute_id"] == "attribute.sleeve_cuff_type"), {})
        if cuff.get("future_head") is not None or label.get("trainable_visual") != "no":
            self.error("POLICY_CUFF_TRAINING", "Human decision H10 does not authorize a cuff specialist or training")

    def governance(self) -> None:
        document = self.artifacts["governance.json"]
        expected = ["ontology_definition", "annotation_guide", "validated_dataset", "trained_model", "evaluated_model", "production_authorization"]
        if document["phase_order"] != expected:
            self.error("GOVERNANCE_PHASE_ORDER", "Six approval stages must remain independent and ordered")
        seen: set[str] = set()
        families: set[str] = set()
        for scope in document["scopes"]:
            identifier = scope["scope_id"]
            if identifier in seen:
                self.error("GOVERNANCE_DUPLICATE_SCOPE", "Repeated scope", identifier)
            seen.add(identifier)
            for ref in scope["concept_refs"]:
                if ref not in self.nodes:
                    self.error("GOVERNANCE_REFERENCE", "Scope references an unknown concept", ref)
                if self.taxonomy_groups.get(ref) == "families":
                    families.add(ref)
            phases = scope["phases"]
            if [p["phase"] for p in phases] != expected:
                self.error("GOVERNANCE_PHASE_COVERAGE", "Every scope requires each stage exactly once", identifier)
            for i, phase in enumerate(phases):
                if phase["status"] in {"REVIEWED", "APPROVED"} and (not phase["reviewer_ref"] or not phase["reviewed_at"] or not phase["evidence_refs"]):
                    self.error("GOVERNANCE_REVIEW_EVIDENCE", "Review/approval needs a real reviewer, timestamp and evidence", identifier)
                if phase["status"] == "APPROVED":
                    if not phase["decision_ref"] or not scope["responsible_person_ref"]:
                        self.error("GOVERNANCE_APPROVAL_AUTHORITY", "Approval needs an explicit decision and appointed responsible person", identifier)
                    if any(previous["status"] != "APPROVED" for previous in phases[:i]):
                        self.error("GOVERNANCE_PHASE_PREREQUISITE", "Later approval cannot bypass earlier independent stages", identifier)
                    if i == 0 and not scope["ambiguities_reviewed"]:
                        self.error("GOVERNANCE_AMBIGUITIES", "Definition approval requires recorded ambiguity/discrepancy review", identifier)
                    if i == 0 and (not scope["definition_review_ref"] or not scope["example_refs"] or not scope["integrity_check_refs"] or not scope["discrepancy_review_ref"]):
                        self.error("GOVERNANCE_DEFINITION_GATES", "Definition approval requires definition review, examples, integrity results and explicit discrepancy review record", identifier)
                    if i == 0 and {example["kind"] for example in scope["example_refs"]} != {"positive", "negative", "borderline"}:
                        self.error("GOVERNANCE_EXAMPLE_COVERAGE", "Definition approval requires positive, negative and borderline examples", identifier)
            if scope["status"] == "APPROVED" and phases[0]["status"] != "APPROVED":
                self.error("GOVERNANCE_SCOPE_STATUS", "Approved scope must have an approved definition", identifier)
        expected_families = {n["canonical_id"] for n in self.artifacts["ontology.json"]["families"]}
        if families != expected_families:
            self.error("GOVERNANCE_FAMILY_COVERAGE", "All proposed families need an explicit scope record")

    def validate(self) -> dict[str, Any]:
        self.phase("parse", self.load)
        if not self.errors:
            self.phase("schema", self.validate_schema)
        if not self.errors:
            for name, callback in (
                ("unique_ids", self.index_nodes), ("references_and_hierarchy", self.references),
                ("aliases", self.aliases), ("applicability_and_labels", self.applicability_and_labels),
                ("relationships", self.relationships), ("body_zones_and_layering", self.spatial_semantics),
                ("commercial_mapping", self.commercial_mapping), ("commercial_season_mapping", self.season_mapping),
                ("exported_matrices", self.exported_matrices), ("product_intelligence", self.product_intelligence),
                ("hashes_and_summary", self.hashes_and_summary), ("human_policies", self.human_policies), ("governance", self.governance),
            ):
                self.phase(name, callback)
        return {"valid": not self.errors, "ontology_version": self.artifacts.get("ontology.json", {}).get("ontology_version"), "schema_version": self.artifacts.get("ontology.json", {}).get("schema_version"), "status": "DRAFT_FOR_REVIEW", "counts": self.counts, "checks": self.checks, "errors": self.errors, "read_only": True, "domain_freeze_approved": False,
                "validation_scope": {"commercial_categories": "Exact IDs, names and SHA256 of the declared read-only source snapshot", "commercial_seasons": "Documentary mapping structure, unique IDs and semantic references; no live season snapshot provided", "domain_review": "Schema and semantic consistency do not approve human domain freeze"}}


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("artifact_dir", nargs="?", type=Path, default=Path(__file__).resolve().parents[2] / "resources/fashion_ontology/v1")
    parser.add_argument("--repo-root", type=Path, default=None)
    args = parser.parse_args(argv)
    result = OntologyValidator(args.artifact_dir, args.repo_root).validate()
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0 if result["valid"] else 1


if __name__ == "__main__":
    sys.exit(main())
