"""Human selection event contract, deliberately disconnected from production UI.

The caller must obtain snapshot and current_context from authenticated server
state. Browser metadata is not authority. No persistence or weight update occurs.
"""
from __future__ import annotations

from datetime import datetime, timezone
import hashlib
import math
from typing import Literal
from uuid import UUID, uuid5

from pydantic import BaseModel, ConfigDict, Field, model_validator

OPAQUE = r"^[A-Za-z0-9:_-]{1,128}$"
SHA = r"^[a-f0-9]{64}$"
REV = r"^[a-f0-9]{40}$"
NAMESPACE = UUID('ae317a7f-4cb1-4f61-9dcf-00a1df935a2b')


class Contract(BaseModel):
    model_config = ConfigDict(extra='forbid', frozen=True, allow_inf_nan=False)


class ModelIdentity(Contract):
    model_id: str = Field(min_length=1, max_length=180)
    revision: str = Field(pattern=REV)
    checkpoint_sha256: str = Field(pattern=SHA)
    pipeline_version: str = Field(min_length=1, max_length=100)
    preprocess_version: str = Field(min_length=1, max_length=100)


class BoundingBox(Contract):
    x: float = Field(ge=0)
    y: float = Field(ge=0)
    width: float = Field(gt=0)
    height: float = Field(gt=0)


class MaskMetadata(Contract):
    artifact_id: str = Field(pattern=OPAQUE)
    mask_sha256: str = Field(pattern=SHA)
    mask_pixels: int = Field(gt=0)
    quality_score: float | None = None
    quality_kind: Literal['uncalibrated_model_score', 'not_available']


class VisualEmbeddingReference(Contract):
    vector_space: Literal['visual'] = 'visual'
    image_sha256: str = Field(pattern=SHA)
    row_id: int = Field(gt=0)
    model_id: str = Field(min_length=1, max_length=180)
    revision: str = Field(pattern=REV)
    dimensions: Literal[768] = 768
    normalization: Literal['l2'] = 'l2'
    preprocess_version: str = Field(min_length=1, max_length=100)


class CandidateFeatures(Contract):
    candidate_id: str = Field(pattern=OPAQUE)
    external_class: str = Field(min_length=1, max_length=120)
    detector_score: float = Field(ge=0, le=1)
    detector_score_kind: Literal['uncalibrated_model_score'] = 'uncalibrated_model_score'
    bbox: BoundingBox
    area_ratio: float = Field(gt=0, le=1)
    center_distance: float = Field(ge=0, le=1)
    border_touch: tuple[Literal['left', 'right', 'top', 'bottom'], ...] = Field(max_length=4)
    mask: MaskMetadata
    broader_role: str | None = Field(default=None, max_length=80)
    taxonomy_version: str | None = Field(default=None, max_length=100)
    visual_embedding_reference: VisualEmbeddingReference | None = None

    @model_validator(mode='after')
    def borders_unique(self):
        if len(set(self.border_touch)) != len(self.border_touch):
            raise ValueError('DUPLICATE_BORDER')
        if (self.broader_role is None) != (self.taxonomy_version is None):
            raise ValueError('SEMANTIC_FEATURE_REQUIRES_VERSION')
        return self


class SelectionContext(Contract):
    run_id: UUID
    owner_id: int = Field(gt=0)
    image_id: int | None = Field(default=None, gt=0)
    image_sha256: str = Field(pattern=SHA)
    image_generation: int = Field(ge=0)
    width: int = Field(gt=0, le=12000)
    height: int = Field(gt=0, le=12000)
    captured_at: datetime
    expires_at: datetime
    category_hint_before: int | None = Field(default=None, gt=0)
    category_hint_recorded_at: datetime | None = None

    @model_validator(mode='after')
    def chronology(self):
        for timestamp in (self.captured_at, self.expires_at, self.category_hint_recorded_at):
            if timestamp is not None and timestamp.utcoffset() is None:
                raise ValueError('TIMEZONE_REQUIRED')
        if self.expires_at <= self.captured_at:
            raise ValueError('INVALID_EXPIRY')
        if (self.category_hint_before is None) != (self.category_hint_recorded_at is None):
            raise ValueError('HINT_REQUIRES_PREDECISION_TIME')
        if self.category_hint_recorded_at and self.category_hint_recorded_at > self.captured_at:
            raise ValueError('POSTDECISION_HINT_NOT_A_FEATURE')
        return self


class CandidateSnapshot(Contract):
    schema_version: Literal['target-candidate-snapshot-1'] = 'target-candidate-snapshot-1'
    context: SelectionContext
    detector: ModelIdentity
    segmenter: ModelIdentity
    selection_policy_version: str = Field(min_length=1, max_length=100)
    candidates: tuple[CandidateFeatures, ...] = Field(min_length=1, max_length=12)

    @model_validator(mode='after')
    def geometry_and_identity(self):
        ids = [c.candidate_id for c in self.candidates]
        if len(set(ids)) != len(ids):
            raise ValueError('DUPLICATE_CANDIDATE_IDS')
        width, height = self.context.width, self.context.height
        for candidate in self.candidates:
            box = candidate.bbox
            if box.x + box.width > width or box.y + box.height > height:
                raise ValueError('CANDIDATE_OUTSIDE_IMAGE')
            expected_ratio = candidate.mask.mask_pixels / (width * height)
            if abs(expected_ratio - candidate.area_ratio) > 1e-6:
                raise ValueError('MASK_AREA_INCONSISTENT')
            expected_distance = math.hypot((box.x + box.width / 2 - width / 2) / width,
                                           (box.y + box.height / 2 - height / 2) / height) / math.sqrt(.5)
            if abs(expected_distance - candidate.center_distance) > 1e-6:
                raise ValueError('CENTER_DISTANCE_INCONSISTENT')
            reference = candidate.visual_embedding_reference
            if reference and reference.image_sha256 != self.context.image_sha256:
                raise ValueError('VISUAL_REFERENCE_PHOTO_MISMATCH')
        return self


class CandidateLabel(Contract):
    candidate_id: str = Field(pattern=OPAQUE)
    selected: bool


class PostDecisionLabels(Contract):
    # Labels are a separate object. They are never included in feature_rows().
    final_product_category: int | None = Field(default=None, gt=0)
    recorded_at: datetime


class HumanSelectionEvent(Contract):
    schema_version: Literal['target-human-selection-event-1'] = 'target-human-selection-event-1'
    feature_schema: Literal['target-predecision-features-1'] = 'target-predecision-features-1'
    label_policy: Literal['one_human_choice_complete_group-1'] = 'one_human_choice_complete_group-1'
    event_id: UUID
    action_id: UUID
    group_id: UUID
    snapshot_sha256: str = Field(pattern=SHA)
    selection_mode: Literal['human'] = 'human'
    actor_id: int = Field(gt=0)
    created_at: datetime
    snapshot: CandidateSnapshot
    labels: tuple[CandidateLabel, ...] = Field(min_length=1, max_length=12)
    post_decision: PostDecisionLabels
    training_ready: Literal[False] = False

    @model_validator(mode='after')
    def complete_choice(self):
        context = self.snapshot.context
        if self.created_at.utcoffset() is None or self.post_decision.recorded_at.utcoffset() is None:
            raise ValueError('TIMEZONE_REQUIRED')
        if not context.captured_at <= self.created_at < context.expires_at:
            raise ValueError('STALE_OR_FUTURE_SNAPSHOT')
        if self.post_decision.recorded_at < self.created_at:
            raise ValueError('POSTDECISION_LABEL_CHRONOLOGY')
        if self.actor_id != context.owner_id or self.group_id != context.run_id:
            raise ValueError('OWNER_OR_RUN_MISMATCH')
        ids = [label.candidate_id for label in self.labels]
        if ids != [c.candidate_id for c in self.snapshot.candidates] or len(ids) != len(set(ids)):
            raise ValueError('INCOMPLETE_OR_DUPLICATE_LABEL_GROUP')
        if sum(label.selected for label in self.labels) != 1:
            raise ValueError('EXACTLY_ONE_HUMAN_CHOICE_REQUIRED')
        if self.snapshot_sha256 != snapshot_digest(self.snapshot):
            raise ValueError('SNAPSHOT_DIGEST_MISMATCH')
        expected_id = uuid5(NAMESPACE, f'{context.owner_id}:{context.run_id}:{self.action_id}')
        if self.event_id != expected_id:
            raise ValueError('EVENT_IDENTITY_MISMATCH')
        return self


def snapshot_digest(snapshot: CandidateSnapshot) -> str:
    return hashlib.sha256(snapshot.model_dump_json().encode()).hexdigest()


def build_human_event(snapshot: CandidateSnapshot, current_context: SelectionContext,
                      candidate_id: str, action_id: UUID, decided_at: datetime,
                      final_category_id: int | None = None) -> HumanSelectionEvent:
    """Build only from trusted source/current state; caller persists idempotently later."""
    if current_context != snapshot.context:
        raise ValueError('STALE_PHOTO_RUN_OWNER_OR_HINT')
    if candidate_id not in {c.candidate_id for c in snapshot.candidates}:
        raise ValueError('UNKNOWN_CANDIDATE')
    context = snapshot.context
    return HumanSelectionEvent(
        event_id=uuid5(NAMESPACE, f'{context.owner_id}:{context.run_id}:{action_id}'),
        action_id=action_id, group_id=context.run_id, snapshot_sha256=snapshot_digest(snapshot),
        actor_id=context.owner_id, created_at=decided_at, snapshot=snapshot,
        labels=[CandidateLabel(candidate_id=c.candidate_id, selected=c.candidate_id == candidate_id)
                for c in snapshot.candidates],
        post_decision=PostDecisionLabels(final_product_category=final_category_id,
                                        recorded_at=decided_at.astimezone(timezone.utc)))


def feature_rows(event: HumanSelectionEvent) -> list[dict]:
    """Feature-only export; no labels, actor, final category or post-decision timestamps."""
    context = event.snapshot.context
    return [{**c.model_dump(mode='json'), 'group_id': str(event.group_id),
             'image_sha256': context.image_sha256,
             'category_hint_before': context.category_hint_before,
             'detector': event.snapshot.detector.model_dump(mode='json'),
             'segmenter': event.snapshot.segmenter.model_dump(mode='json')}
            for c in event.snapshot.candidates]
