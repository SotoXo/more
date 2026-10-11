from datetime import datetime, timedelta, timezone
from uuid import uuid4

import pytest
from pydantic import ValidationError

from mati_ai.learning.target_events import (
    CandidateSnapshot, HumanSelectionEvent, SelectionContext, build_human_event,
    feature_rows,
)

NOW = datetime(2026, 10, 5, 14, 0, tzinfo=timezone.utc)


def snapshot_data():
    identity = {'model_id': 'publisher/example', 'revision': 'a' * 40,
                'checkpoint_sha256': 'b' * 64, 'pipeline_version': 'test-1',
                'preprocess_version': 'test-1'}
    return {'context': {'run_id': str(uuid4()), 'owner_id': 1, 'image_id': None,
                       'image_sha256': 'c' * 64, 'image_generation': 1, 'width': 100,
                       'height': 100, 'captured_at': NOW, 'expires_at': NOW + timedelta(minutes=15),
                       'category_hint_before': 3, 'category_hint_recorded_at': NOW - timedelta(seconds=1)},
            'detector': identity, 'segmenter': identity, 'selection_policy_version': 'test-1',
            'candidates': [{'candidate_id': key, 'external_class': 'shirt', 'detector_score': .5,
                            'bbox': {'x': 25, 'y': 25, 'width': 50, 'height': 50},
                            'area_ratio': .2, 'center_distance': 0, 'border_touch': [],
                            'mask': {'artifact_id': f'mask:{key}', 'mask_sha256': 'd' * 64,
                                     'mask_pixels': 2000, 'quality_score': .9,
                                     'quality_kind': 'uncalibrated_model_score'}} for key in ['A', 'B', 'C']]}


def event():
    source = CandidateSnapshot.model_validate(snapshot_data())
    return build_human_event(source, source.context, 'B', uuid4(), NOW + timedelta(seconds=1), 4)


def test_complete_group_one_positive_and_two_competing_negatives():
    value = event()
    assert [(v.candidate_id, v.selected) for v in value.labels] == [('A', False), ('B', True), ('C', False)]
    assert value.selection_mode == 'human' and value.training_ready is False
    assert HumanSelectionEvent.model_validate_json(value.model_dump_json()) == value


def test_same_action_replay_has_stable_event_identity():
    source = CandidateSnapshot.model_validate(snapshot_data()); action = uuid4()
    a = build_human_event(source, source.context, 'B', action, NOW + timedelta(seconds=1))
    b = build_human_event(source, source.context, 'B', action, NOW + timedelta(seconds=1))
    assert a == b
    c = build_human_event(source, source.context, 'B', uuid4(), NOW + timedelta(seconds=1))
    assert a.event_id != c.event_id


@pytest.mark.parametrize('field,value', [('owner_id', 2), ('image_generation', 2),
    ('image_sha256', 'e' * 64), ('run_id', str(uuid4()))])
def test_current_server_context_rejects_stale_or_cross_owner(field, value):
    source = CandidateSnapshot.model_validate(snapshot_data()); current = source.context.model_dump()
    current[field] = value
    with pytest.raises(ValueError, match='STALE_PHOTO_RUN_OWNER_OR_HINT'):
        build_human_event(source, SelectionContext.model_validate(current), 'B', uuid4(), NOW + timedelta(seconds=1))


def test_unknown_candidate_is_not_a_human_choice():
    source = CandidateSnapshot.model_validate(snapshot_data())
    with pytest.raises(ValueError, match='UNKNOWN_CANDIDATE'):
        build_human_event(source, source.context, 'D', uuid4(), NOW + timedelta(seconds=1))


@pytest.mark.parametrize('seconds', [-1, 900, 901])
def test_time_bounds_reject_future_snapshot_or_expired_event(seconds):
    source = CandidateSnapshot.model_validate(snapshot_data())
    with pytest.raises(ValidationError, match='STALE_OR_FUTURE_SNAPSHOT'):
        build_human_event(source, source.context, 'B', uuid4(), NOW + timedelta(seconds=seconds))


def test_postdecision_category_never_becomes_a_feature():
    value = event(); rows = feature_rows(value)
    assert value.post_decision.final_product_category == 4
    assert all(r['category_hint_before'] == 3 for r in rows)
    assert all('final_product_category' not in r and 'selected' not in r and 'actor_id' not in r for r in rows)
    assert 'post_decision' not in str(rows)


def test_postdecision_hint_cannot_be_backdated_as_available_feature():
    data = snapshot_data(); data['context']['category_hint_recorded_at'] = NOW + timedelta(seconds=1)
    with pytest.raises(ValidationError, match='POSTDECISION_HINT_NOT_A_FEATURE'):
        CandidateSnapshot.model_validate(data)


@pytest.mark.parametrize('mutation', ['empty', 'duplicate', 'outside', 'wrong_area', 'wrong_center', 'nan', 'path', 'extra_label'])
def test_corrupted_candidate_groups_fail_closed(mutation):
    data = snapshot_data()
    if mutation == 'empty': data['candidates'] = []
    elif mutation == 'duplicate': data['candidates'][1]['candidate_id'] = 'A'
    elif mutation == 'outside': data['candidates'][0]['bbox']['x'] = 80
    elif mutation == 'wrong_area': data['candidates'][0]['area_ratio'] = .3
    elif mutation == 'wrong_center': data['candidates'][0]['center_distance'] = .4
    elif mutation == 'nan': data['candidates'][0]['detector_score'] = float('nan')
    elif mutation == 'path': data['candidates'][0]['mask']['artifact_id'] = '../mask.png'
    elif mutation == 'extra_label': data['candidates'][0]['final_product_category'] = 4
    with pytest.raises(ValidationError): CandidateSnapshot.model_validate(data)


@pytest.mark.parametrize('mutation', ['two_positive', 'none_positive', 'incomplete', 'digest', 'event_id', 'training_ready'])
def test_tampered_event_or_label_group_rejected(mutation):
    data = event().model_dump(mode='json')
    if mutation == 'two_positive': data['labels'][0]['selected'] = True
    elif mutation == 'none_positive': data['labels'][1]['selected'] = False
    elif mutation == 'incomplete': data['labels'].pop()
    elif mutation == 'digest': data['snapshot_sha256'] = 'e' * 64
    elif mutation == 'event_id': data['event_id'] = str(uuid4())
    elif mutation == 'training_ready': data['training_ready'] = True
    with pytest.raises(ValidationError): HumanSelectionEvent.model_validate(data)


def test_deep_arrays_cannot_be_mutated_after_snapshot():
    value = event()
    assert isinstance(value.snapshot.candidates, tuple) and isinstance(value.labels, tuple)
    with pytest.raises(ValidationError): value.snapshot.candidates[0].external_class = 'dress'


def test_text_embedding_reference_cannot_enter_visual_features():
    data = snapshot_data(); data['candidates'][0]['visual_embedding_reference'] = {
        'vector_space': 'text', 'image_sha256': 'c' * 64, 'row_id': 1,
        'model_id': 'text/model', 'revision': 'a' * 40, 'preprocess_version': 'text-1'}
    with pytest.raises(ValidationError): CandidateSnapshot.model_validate(data)


def test_visual_reference_must_belong_to_same_photo():
    data = snapshot_data(); data['candidates'][0]['visual_embedding_reference'] = {
        'vector_space': 'visual', 'image_sha256': 'e' * 64, 'row_id': 1,
        'model_id': 'google/siglip-base-patch16-224', 'revision': 'a' * 40,
        'preprocess_version': 'srgb-exif-transformers-image-1'}
    with pytest.raises(ValidationError, match='VISUAL_REFERENCE_PHOTO_MISMATCH'):
        CandidateSnapshot.model_validate(data)
