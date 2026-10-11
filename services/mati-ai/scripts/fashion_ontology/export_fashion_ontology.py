#!/usr/bin/env python3
"""Regenerate design reports/hashes from JSON masters; never imports the AI service.

This is an explicit file exporter. The validator is a separate, read-only CLI.
Only the four named generated artifacts inside the supplied directory are written.
"""
from __future__ import annotations

import argparse
import csv
import hashlib
import json
from pathlib import Path


def export(root: Path) -> dict:
    def read(name: str) -> dict:
        return json.loads((root / name).read_text(encoding='utf-8'))

    ontology = read('ontology.json')
    version = ontology['ontology_version']
    envelope = {'ontology_version': version, 'schema_version': ontology['schema_version'],
                'status': ontology['status']}
    vocabularies = [read(name) for name in ontology['vocabularies']]
    concepts = [node for group in ('families', 'categories', 'subcategories', 'attributes')
                for node in ontology[group]]
    concepts += [node for v in vocabularies for node in v.get('concepts', v.get('roles', []))]
    counts = {group: len(ontology[group]) for group in ('families', 'categories', 'subcategories', 'attributes')}
    counts.update({group: len(read(group + '.json')['concepts'])
                   for group in ('styles', 'materials', 'patterns', 'body_zones')})
    counts.update(concepts=len(concepts),
                  relations=len(ontology['relationships']) + len(read('layering.json')['relations']),
                  relation_types=len(ontology['relation_types']),
                  garment_identity_concepts=sum(len(ontology[g]) for g in ('families', 'categories', 'subcategories')),
                  style_core=sum(c['domain'] == 'style' for c in read('styles.json')['concepts']),
                  aesthetics=sum(c['domain'] == 'aesthetic' for c in read('styles.json')['concepts']),
                  fibers=sum(c['domain'] == 'fiber' for c in read('materials.json')['concepts']),
                  non_textile_materials=sum(c['domain'] == 'non_textile_material' for c in read('materials.json')['concepts']),
                  label_fields=len(read('label_schema.json')['labels']),
                  commercial_categories=len(read('commercial_mapping.json')['mappings']),
                  coverage_rows=len(read('attribute_applicability.json')['rows']))

    def dump(name: str, data: dict) -> None:
        (root / name).write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')

    dump('summary.json', dict(envelope, artifact_type='summary', counts=counts,
        checks={'domain_freeze_approved': False, 'validation': 'Run the read-only validator; counts are not domain approval.'},
        concept_count_policy='All bilingual IDs: taxonomy, attribute descriptors, vocabulary values, zones and layer roles. Excludes relationship IDs and relation type IDs.'))

    def write_csv(name: str, fields: list[str], rows: list[dict]) -> None:
        with (root / name).open('w', encoding='utf-8', newline='') as stream:
            writer = csv.DictWriter(stream, fieldnames=fields)
            writer.writeheader()
            for row in rows:
                writer.writerow({key: json.dumps(row[key], ensure_ascii=False)
                                 if isinstance(row[key], (dict, list)) else row[key] for key in fields})

    write_csv('coverage_matrix.csv', ['attribute_id', 'family_id', 'status', 'unknown_allowed', 'category_scope'],
              read('attribute_applicability.json')['rows'])
    write_csv('annotation_matrix.csv', ['attribute_id', 'label_type', 'annotation_sources', 'visually_learnable',
              'annotation_difficulty', 'future_head', 'trainable_visual'], read('label_schema.json')['labels'])
    files = {p.name: hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(root.iterdir())
             if p.is_file() and p.suffix in {'.json', '.csv'} and p.name != 'hashes.json'}
    dump('hashes.json', dict(envelope, artifact_type='hash_manifest', algorithm='sha256', files=files,
         generated_reports=['summary.json', 'coverage_matrix.csv', 'annotation_matrix.csv'],
         excludes=['hashes.json'], hash_policy='SHA256 of exact UTF-8 file bytes; no circular self-hash.'))
    return counts


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('artifact_dir', nargs='?', type=Path,
        default=Path(__file__).resolve().parents[2] / 'resources/fashion_ontology/v1')
    args = parser.parse_args()
    print(json.dumps(export(args.artifact_dir.resolve()), ensure_ascii=False, indent=2))
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
