"""Text endpoint, unavailable state and bounded child lifecycle regressions."""
from concurrent.futures import Future
import hashlib
import json
from types import SimpleNamespace
from uuid import uuid4

import pytest

from mati_ai.core.errors import PipelineError
from mati_ai.rag.contracts import TextEmbeddingRequest
from mati_ai.rag.profile import TextModelProfile
from mati_ai.rag.service import TextEmbeddingService

IDENTITY = {
    "model_id": "intfloat/multilingual-e5-small",
    "model_revision": "614241f622f53c4eeff9890bdc4f31cfecc418b3",
    "dimensions": 384, "normalization": "l2",
    "preprocess_version": "text-nfc-strip-meanpool-l2-e5querypassage512-1",
    "vector_space": "text", "pipeline_version": "text-embedding-1.0.0",
}


def completed(texts=None):
    texts = texts or ["camisa"]
    return {
        "status": "completed", "model_loaded": True,
        "data": {
            "embeddings": [{
                "index": index, "vector": [1.0] + [0.0] * 383,
                "dimensions": 384, "norm": 1.0,
                "text_sha256": hashlib.sha256(text.encode()).hexdigest(),
                "truncated": False, "token_count_before_truncation": 4,
            } for index, text in enumerate(texts)],
            "model": IDENTITY, "device": "cpu", "input_type": "query", "truncated": False,
        },
        "timing_ms": {"worker_total": 1.0, "cold_load": 0.0, "integrity_verification": 0.0},
        "runtime": {
            "child_pid": 100, "child_rss_mib": 500.0,
            "child_peak_rss_mib": 600.0, "threads": 4,
        },
    }


class FakeService:
    def embed(self, texts, input_type):
        result = completed(texts)
        result["data"]["input_type"] = input_type
        return result

    def close(self):
        pass


def payload(texts=None):
    return {
        "schema_version": "mati-text-embed-request-1",
        "request_id": str(uuid4()), "texts": texts or ["camisa algodón", "blusa"],
        "input_type": "query",
    }


def test_internal_auth_is_required(client):
    response = client.post("/api/v1/rag/embed", json=payload())
    assert response.status_code == 401
    assert response.json()["error"]["code"] == "UNAUTHORIZED_SERVICE"


def test_missing_approved_profile_degrades_without_spawn(client, headers, app):
    response = client.post("/api/v1/rag/embed", headers=headers, json=payload())
    assert response.status_code == 503
    assert response.json()["error"]["code"] == "TEXT_MODEL_UNAVAILABLE"
    assert app.state.text_embedding_service._executor is None


def test_text_identity_extras_and_exact_original_sha_survive_envelope(client, headers, app):
    app.state.text_embedding_service = FakeService()
    value = payload(["  algodo\u0301n  ", "CAMISA"])
    response = client.post("/api/v1/rag/embed", headers=headers, json=value)
    assert response.status_code == 200
    result = response.json()
    assert result["request_id"] == value["request_id"]
    assert result["model"] == result["data"]["model"] == IDENTITY
    assert result["human_review_required"] is False
    entries = result["data"]["embeddings"]
    assert [entry["index"] for entry in entries] == [0, 1]
    assert entries[0]["text_sha256"] == hashlib.sha256(value["texts"][0].encode()).hexdigest()
    assert entries[0]["dimensions"] == len(entries[0]["vector"]) == 384
    assert result["data"]["device"] == "cpu"


@pytest.mark.parametrize("texts", [[], [" "], ["x"] * 17, ["x" * 8193], [123], ["\ud800"]])
def test_bounded_input_and_invalid_unicode_are_422(client, headers, texts):
    value = payload()
    value["texts"] = texts
    response = client.post("/api/v1/rag/embed", headers=headers, content=json.dumps(value),
                           follow_redirects=False)
    assert response.status_code == 422


def test_request_bytes_limited_before_parser(client, headers):
    response = client.post("/api/v1/rag/embed", headers=headers, content=b"x" * 65537)
    assert response.status_code == 413
    assert response.json()["error"]["code"] == "PAYLOAD_TOO_LARGE"
    # The text-only cap does not lower existing image/multipart limits.
    assert client.request("GET", "/health", content=b"x" * 65537).status_code == 200


def test_request_rejects_unknown_model_selector():
    with pytest.raises(ValueError):
        TextEmbeddingRequest.model_validate(payload() | {"model_id": "other/model"})


class FakeExecutor:
    def __init__(self):
        self.future = Future()
        self.submissions = 0
        self.closed = False

    def submit(self, *args):
        self.submissions += 1
        return self.future

    def shutdown(self, **kwargs):
        self.closed = True


def fake_cpu_service():
    settings = SimpleNamespace(
        text_embedding_enabled=True, text_embedding_timeout_seconds=0.01,
        text_embedding_retry_seconds=60.0,
    )
    service = TextEmbeddingService(settings)
    profile = SimpleNamespace(source_json="{}", cache_root="/cache/text-embeddings",
                              identity=IDENTITY)
    service._get_profile = lambda: profile
    service._executor = FakeExecutor()
    return service


def test_timeout_keeps_capacity_until_real_job_completion():
    service = fake_cpu_service()
    executor = service._executor
    with pytest.raises(PipelineError) as timed:
        service.embed(["camisa"], "query")
    assert timed.value.code == "TEXT_EMBEDDING_TIMEOUT"
    with pytest.raises(PipelineError) as busy:
        service.embed(["blusa"], "query")
    assert busy.value.code == "TEXT_EMBEDDING_BUSY"
    assert executor.submissions == 1
    executor.future.set_result(completed())
    executor.future = Future()
    executor.future.set_result(completed())
    assert service.embed(["camisa"], "query")["status"] == "completed"
    assert executor.submissions == 2
    service.close()


def test_worker_error_is_safe_503_and_capacity_released():
    service = fake_cpu_service()
    service._executor.future.set_result({
        "status": "error", "code": "TEXT_MODEL_UNAVAILABLE", "http_status": 503,
        "model_loaded": False,
    })
    with pytest.raises(PipelineError) as failure:
        service.embed(["camisa"], "query")
    assert failure.value.status_code == 503
    assert failure.value.retryable is True
    assert service._pending.acquire(blocking=False)
    service._pending.release()
    service.close()


def test_long_query_is_422_without_unavailable_backoff():
    service = fake_cpu_service()
    service._executor.future.set_result({
        "status": "error", "code": "TEXT_TOO_LONG", "http_status": 422,
        "model_loaded": True,
    })
    with pytest.raises(PipelineError) as failure:
        service.embed(["camisa"], "query")
    assert failure.value.code == "TEXT_TOO_LONG"
    assert failure.value.status_code == 422
    assert service._failed_until == 0
    service.close()


def test_profile_requires_approved_gate_and_immutable_hashes(tmp_path):
    file = tmp_path / "profile.json"
    file.write_text(json.dumps({"schema_version": "mati-text-model-profile-1", "approved": False}))
    with pytest.raises(ValueError, match="not approved"):
        TextModelProfile.read(file, tmp_path)
    file.write_text(json.dumps({
        "schema_version": "mati-text-model-profile-1", "approved": True,
        "pipeline_version": "text-embedding-1.0.0", "vector_space": "text", "revision": "main",
    }))
    with pytest.raises(ValueError, match="Immutable"):
        TextModelProfile.read(file, tmp_path)


def test_changed_weight_bytes_fail_integrity(tmp_path):
    model = tmp_path / "model.safetensors"
    model.write_bytes(b"changed")
    profile = TextModelProfile(json.dumps({
        "snapshot_path": str(tmp_path),
        "files": [{"path": model.name, "bytes": 7, "sha256": "0" * 64}],
    }), str(tmp_path))
    with pytest.raises(ValueError, match="changed"):
        profile.verify_files()
