"""Read frozen benchmark artifacts and emit a per-consumer promotion decision.

This report has no inference, training, HTTP or database operations. Undefined
conditional metrics do not pass a gate. Qualitative review cannot unlock IDs.
"""
from __future__ import annotations

import argparse
from datetime import datetime, timezone
import hashlib
import json
from pathlib import Path

PROTOCOL_SHA = '5cdd1eba3ab99d862d039a70a93769f821d7c4ba8c89f8a6508025b6ce6ba0f4'
LOCK_SHA = '8f5fd343a93d9c09a2adbfb14a573f6f234005fe2cf933d8f2b8d82426ee8443'


def sha(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def load(path: Path):
    return json.loads(path.read_text())


def subtract(after, before):
    return None if after is None or before is None else after - before


def above(value, limit):
    return value is not None and value >= limit


def below(value, limit):
    return value is not None and value <= limit


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root', type=Path, default=Path.cwd())
    args = parser.parse_args()
    root = args.root.resolve()
    base = root / 'storage/app/ai-segmentation'
    protocol_path = root / 'services/mati-ai/scripts/segmentation/protocol.json'
    assert sha(protocol_path) == PROTOCOL_SHA, 'FROZEN_PROTOCOL_CHANGED'
    assert sha(base / 'model-lock.json') == LOCK_SHA, 'MODEL_LOCK_CHANGED'
    protocol = load(protocol_path)
    assert sha(root / protocol['manifest_path']) == protocol['manifest_sha256']
    frozen_ids = protocol['image_ids']
    assert len(frozen_ids) == len(set(frozen_ids)) == 130
    full = load(base / 'consumer-full.json')
    assert full['classification']['n'] == full['retrieval']['n_queries'] == 130
    assert full['identity']['protocol_sha256'] == PROTOCOL_SHA
    lock = load(base / 'model-lock.json')
    visual = load(base / 'visual-review.json')
    assert visual['review_status'] == 'complete'
    assert visual['reviewed_row_count'] == 60
    assert set(visual['sample_image_ids']) == set(load(base / 'visual-sample-freeze.json')['image_ids'])
    tests = (base / 'policy-metrics-tests.txt').read_text()
    assert '84 passed' in tests and 'failed' not in tests.lower()
    gate = protocol['gate']
    decisions = {}
    for candidate in protocol['candidates']:
        name = candidate['id']
        result_path = base / name / 'results.json'
        result = load(result_path)
        consumer_path = base / name / 'consumers.json'
        consumers = load(consumer_path)
        rows = result['rows']
        assert [r['image_id'] for r in rows] == frozen_ids
        assert result['metadata']['protocol_sha256'] == PROTOCOL_SHA
        assert result['metadata']['model_lock_sha256'] == LOCK_SHA
        assert consumers['protocol_sha256'] == PROTOCOL_SHA
        assert visual['source_results_sha256'][name] == sha(result_path)
        selected = sum(r['status'] == 'selected' for r in rows)
        assert selected == consumers['automatic_selected_n']
        valid = [len(r['selection']['candidates']) for r in rows]
        status_counts = result['summary']['status_counts']
        memory = [result['summary']['memory_before_cold_load'], result['summary']['memory_peak']]
        memory.extend(m for r in rows for m in r['memory'].values() if isinstance(m, dict))
        free = min(m['device_free_mib'] for m in memory)
        summary = result['summary']
        resource_checks = {
            'zero_oom': not any('OOM' in str(r.get('error', '')).upper() for r in rows),
            'zero_unhandled_errors': not summary['error_counts'] and all(r['error'] is None for r in rows),
            'minimum_free_device_memory': free >= gate['resource']['min_free_device_mib'],
            'peak_incremental_reserved': summary['memory_peak']['incremental_peak_reserved_mib'] <= gate['resource']['peak_incremental_reserved_mib_max'],
            'warm_p95': summary['warm_pipeline']['p95_ms'] <= gate['resource']['warm_pipeline_p95_ms_max'],
        }
        license_checked = all(lock['models'][candidate[k]]['license'] == 'apache-2.0' for k in ('detector', 'segmenter'))
        by_profile = {}
        for profile, values in consumers['all130'].items():
            classification = values['classification']
            color = values['color']
            retrieval = values['retrieval']['metrics']
            assert classification['n'] == color['n'] == values['retrieval']['n_queries'] == 130
            assert classification['missing_target_n'] == 130 - selected
            cohort = consumers['automatic_selected_only']
            selected_drop = None if selected == 0 else subtract(
                cohort['full_matched_cohort']['classification']['top1'], cohort[profile]['classification']['top1'])
            class_deltas = {k: subtract(classification[k], full['classification'][k])
                            for k in ('top1', 'top3', 'macro_f1', 'formal_coverage')}
            classification_checks = {
                'top1_material_gain': above(class_deltas['top1'], gate['classification_material']['all130_top1_gain_min']),
                'macro_f1_material_gain': above(class_deltas['macro_f1'], gate['classification_material']['all130_macro_f1_gain_min']),
                'selected_only_nonregression': below(selected_drop, gate['classification_material']['selected_only_top1_drop_max']),
                'formal_coverage_nonregression': above(class_deltas['formal_coverage'], -gate['classification_material']['all130_formal_coverage_drop_max']),
            }
            color_deltas = {k: subtract(color[k], full['color'][k]) for k in (
                'exact_proxy_correct_all_n', 'palette_coverage', 'conditional_proxy_id_accuracy', 'family_correct_all_n')}
            color_deltas['delta_e_mean'] = subtract(color['delta_e00']['mean'], full['color']['delta_e00']['mean'])
            color_checks = {
                'exact_proxy_material_gain': above(color_deltas['exact_proxy_correct_all_n'], gate['color_material']['all130_exact_proxy_correct_gain_min']),
                'palette_coverage_nonregression': above(color_deltas['palette_coverage'], -gate['color_material']['all130_palette_coverage_drop_max']),
                'conditional_precision_nonregression': above(color_deltas['conditional_proxy_id_accuracy'], -gate['color_material']['conditional_proxy_precision_drop_max']),
                'unpaired_delta_e_mean_limit_only': below(color_deltas['delta_e_mean'], gate['color_material']['delta_e_mean_increase_max']),
                'commercial_guard_retained': color['applicable_commercial_id_coverage'] == 0 and summary['commercial_ids_authorized'] is False,
            }
            retrieval_deltas = {k: subtract(retrieval[k], full['retrieval']['metrics'][k]) for k in ('ndcg10', 'hit10', 'recall10')}
            retrieval_checks = {
                'ndcg10_material_gain': above(retrieval_deltas['ndcg10'], gate['retrieval_material']['all130_ndcg10_gain_min']),
                'hit10_nonregression': above(retrieval_deltas['hit10'], -gate['retrieval_material']['all130_hit10_drop_max']),
                'recall10_nonregression': above(retrieval_deltas['recall10'], -gate['retrieval_material']['all130_recall10_drop_max']),
            }
            by_profile[profile] = {
                'classification': {'deltas_all130': class_deltas, 'selected_only_top1_drop': selected_drop,
                                   'checks': classification_checks, 'quantitative_gate_pass': all(classification_checks.values())},
                'color': {'deltas_all130': color_deltas, 'delta_e_comparison_is_unpaired': True,
                          'checks': color_checks, 'quantitative_gate_pass': all(color_checks.values())},
                'retrieval': {'deltas_all130': retrieval_deltas, 'checks': retrieval_checks,
                              'quantitative_gate_pass': all(retrieval_checks.values())},
            }
        quantitative_pass = any(v['quantitative_gate_pass'] for p in by_profile.values() for v in p.values())
        # This task's stopping rule is determined by measured consumer failure.
        # A visually pleasing diagnostic is not promoted as isolation accuracy.
        assert not quantitative_pass, 'REQUIRES_INDEPENDENT_PROMOTION_REVIEW_NOT_AUTOMATIC_DEPLOYMENT'
        decisions[name] = {
            'coverage_counts': {'n': 130, 'raw_candidates_present': sum(bool(r['candidates']) for r in rows),
                'valid_candidate_present': sum(n > 0 for n in valid), 'no_valid_candidate': sum(n == 0 for n in valid),
                'single_valid_candidate': sum(n == 1 for n in valid), 'automatic_selected': selected,
                'ambiguous': status_counts.get('ambiguous', 0), 'insufficient_evidence': status_counts.get('insufficient_evidence', 0)},
            'publisher_code_and_weights_license_accepted': license_checked,
            'dataset_licenses_separate': True, 'resource_checks': resource_checks,
            'minimum_observed_free_device_mib': free, 'resource_gate_pass': all(resource_checks.values()),
            'benchmark_policy_and_boundary_tests': '84 passed; unit contracts only, not deployed E2E',
            'stratified_visual_review': {'reviewed_images': 20, 'human_mask_ground_truth': False,
                'image13_auto_target': next(r for r in rows if r['image_id'] == 13)['target_garment']['status'],
                'commercial_identity_authorized': False, 'qualitative_only': True},
            'profiles': by_profile, 'quantified_consumer_gain_demonstrated': False,
            'promotion': 'NONE', 'decision_basis': 'Frozen quantitative gates fail for every consumer; no isolation-only promotion permitted.',
            'source_sha256': {'results': sha(result_path), 'consumers': sha(consumer_path)},
        }
    report = {
        'schema_version': 'mati-frozen-promotion-report-1', 'created_utc': datetime.now(timezone.utc).isoformat(),
        'protocol_sha256': PROTOCOL_SHA, 'model_lock_sha256': LOCK_SHA,
        'visual_review_sha256': sha(base / 'visual-review.json'), 'tests_record_sha256': sha(base / 'policy-metrics-tests.txt'),
        'baseline_full_sha256': sha(base / 'consumer-full.json'), 'criteria_changed_after_results': False,
        'candidates': decisions, 'winner': 'NONE', 'production_integrated': False,
        'consumer_strategy': {'classification': 'full', 'embedding': 'full', 'commercial_color': 'none',
                              'technical_color': 'existing full-image heuristic; guard unchanged'},
        'image13_assisted_diagnostic_in_aggregate': False,
    }
    output = base / 'gate-decision.json'
    temporary = output.with_suffix('.json.tmp')
    temporary.write_text(json.dumps(report, ensure_ascii=False, indent=2) + '\n')
    temporary.chmod(0o600)
    temporary.replace(output)
    print(json.dumps({'winner': report['winner'], 'sha256': sha(output), 'candidate_count': len(decisions)}))


if __name__ == '__main__':
    main()
