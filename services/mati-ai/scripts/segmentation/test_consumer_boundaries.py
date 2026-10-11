import pytest
from evaluate_consumers import automatic_target, safe_artifact

POLICY = 'target-selection-benchmark-1'

def record(mode='automatic', ref='run/candidate/mask.png'):
    return {'selected_candidate_id':'c1', 'target_garment':{'status':'selected', 'selection_mode':mode, 'selection_validated':True, 'selection_policy_version':POLICY, 'selected_candidate_id':'c1'}, 'candidates':[{'id':'c1','external_class':'shirt blouse sweater','mask_reference':ref,'crop_reference':'run/candidate/crop.png','masked_crop_reference':'run/candidate/masked.png'}]}

def test_human_selected_is_not_auto_metrics():
    assert automatic_target(record(mode='human'), POLICY) is None

def test_mixed_diagnostic_without_mask_never_enters_auto():
    row=record(mode='none',ref=None)
    row['target_garment']['status']='ambiguous'
    row['selected_candidate_id']=row['target_garment']['selected_candidate_id']=None
    assert automatic_target(row, POLICY) is None

@pytest.mark.parametrize('reference',[None,'','../outside/mask.png','/public/mask.png'])
def test_selected_requires_bounded_nonempty_artifact_references(reference):
    with pytest.raises(ValueError,match='INVALID_ARTIFACT_REFERENCE'):
        automatic_target(record(ref=reference), POLICY)

def test_candidate_selection_must_be_unique():
    row=record();row['candidates'].append(row['candidates'][0].copy())
    with pytest.raises(ValueError,match='DUPLICATE_CANDIDATE_IDS'):
        automatic_target(row,POLICY)

def test_selected_id_must_match_policy_snapshot():
    row=record();row['selected_candidate_id']='c2'
    with pytest.raises(ValueError,match='SELECTED_CANDIDATE_ID_MISMATCH'):
        automatic_target(row,POLICY)

def test_valid_auto_artifact_is_accepted():
    assert automatic_target(record(),POLICY)['id']=='c1'

def test_private_artifact_symlink_escape_is_rejected(tmp_path):
    private=tmp_path/'private';private.mkdir();outside=tmp_path/'outside.png';outside.write_bytes(b'mask');(private/'escape.png').symlink_to(outside)
    with pytest.raises(ValueError,match='ARTIFACT_OUTSIDE_PRIVATE_BENCHMARK'):
        safe_artifact(private,'escape.png')
