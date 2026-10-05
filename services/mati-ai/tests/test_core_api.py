import base64
import io
import json

import pytest
from fastapi.testclient import TestClient
from PIL import Image, ImageDraw

from mati_ai.core.config import Settings
from mati_ai.core.errors import PipelineError
from mati_ai.main import create_app


class FakeModel:
    _model = None

    def infer(self, image, prompts):
        return {
            "similarities": [0.3, 0.2, 0.1][: len(prompts)],
            "embedding": [0.6, 0.8],
            "embedding_metadata": {
                "model_id": "fixture", "model_revision": "a" * 40,
                "pipeline_version": "registration-1.2.0", "values": [0.6, 0.8],
                "dimension": 2, "normalization": "l2", "normalized": True,
                "norm": 1.0, "dimension_source": "generated_vector",
                "embedding_version": "projected-image-l2-1",
                "preprocess_version": "srgb-exif-transformers-image-1",
            },
            "model": {
                "model_id": "fixture",
                "model_revision": "a" * 40,
                "pipeline_version": "registration-1.2.0",
            },
            "device": "cpu",
            "vram_peak_mb": 0,
            "metrics": {"inference": 1, "model_load": 0},
        }


def isolated_image_bytes():
    image = Image.new("RGB", (400, 500), "white")
    ImageDraw.Draw(image).rectangle((80, 50, 320, 450), fill="#d9c5a0")
    buffer = io.BytesIO()
    image.save(buffer, format="PNG")
    return buffer.getvalue()


def test_health_and_fail_closed_auth(client, headers):
    assert client.get("/health").json()["status"] == "ok"
    assert client.get("/api/v1/capabilities").status_code == 401
    assert (
        client.get(
            "/api/v1/capabilities", headers={"Authorization": "Bearer wrong"}
        ).status_code
        == 401
    )
    response = client.get("/api/v1/capabilities", headers=headers)
    assert response.status_code == 200
    assert response.json()["request_id"] == "test-request-123"
    assert response.json()["model"]["pipeline_version"]
    assert response.json()["human_review_required"] is True
    app = create_app(Settings(token="", _env_file=None))
    assert (
        TestClient(app).get("/api/v1/capabilities", headers=headers).status_code == 401
    )


def test_invalid_image_has_safe_error(client, headers):
    response = client.post(
        "/api/v1/garments/analyze",
        headers=headers,
        data={"categories": '[{"id":3,"name":"PANTALÓN"}]'},
        files={"image": ("x.jpg", b"not-image", "image/jpeg")},
    )
    assert response.status_code == 422
    assert response.json()["error"]["code"] == "DECODE_FAILED"
    assert "traceback" not in response.text.lower()


def test_valid_image_contract_uses_only_laravel_taxonomy(
    client, headers, app, image_bytes
):
    app.state.model_manager = FakeModel()
    image_bytes = isolated_image_bytes()
    response = client.post(
        "/api/v1/garments/analyze",
        headers=headers,
        data={
            "categories": json.dumps(
                [{"id": 44, "name": "POLERA"}, {"id": 2, "name": "PANTALÓN"}]
            ),
            "colors": '[{"id":6,"name":"BEIGE","hex":"#d9c5a0"}]',
            "include_embedding": "true",
        },
        files={"image": ("x.png", image_bytes, "image/png")},
    )
    assert response.status_code == 200, response.text
    result = response.json()
    assert result["data"]["classification"]["top_k"][0]["category_id"] == 44
    assert {
        item["category_id"] for item in result["data"]["classification"]["top_k"]
    } == {44, 2}
    assert result["data"]["colors"]["dominant_color"]["catalog_id"] is None
    assert result["data"]["colorimetry"]["palette"]
    assert result["data"]["colorimetry"]["status"] == "completed"
    assert result["data"]["colorimetry"]["commercial_color_status"] == "insufficient_evidence"
    assert "COLORIMETRY_INSUFFICIENT_EVIDENCE" in result["abstentions"]
    assert result["data"]["embedding"]["values"] == [0.6, 0.8]
    assert result["data"]["image"]["mime"] == "image/png"
    assert result["timing_ms"]["color"] >= 0
    assert result["model"]["model_id"] == "fixture"


def test_visual_model_failure_returns_useful_partial(client, headers, app, image_bytes):
    class Unavailable:
        def infer(self, *args):
            raise PipelineError("MODEL_UNAVAILABLE", "offline", 503)

    app.state.model_manager = Unavailable()
    image_bytes = isolated_image_bytes()
    result = client.post(
        "/api/v1/garments/analyze",
        headers=headers,
        data={"categories": '[{"id":1,"name":"POLERA"}]'},
        files={"image": ("x.png", image_bytes)},
    ).json()
    assert result["status"] == "partial"
    assert result["data"]["colors"]["palette"]
    assert result["data"]["classification"]["top_k"] == []
    assert "embedding" not in result["data"]
    assert result["abstentions"] == ["COLORIMETRY_INSUFFICIENT_EVIDENCE", "MODEL_UNAVAILABLE"]


@pytest.mark.parametrize(
    "categories",
    [
        "[]",
        "not-json",
        '[{"id":1,"name":""}]',
        '[{"id":1,"name":"A"},{"id":1,"name":"B"}]',
    ],
)
def test_taxonomy_rejected(client, headers, image_bytes, categories):
    response = client.post(
        "/api/v1/garments/analyze",
        headers=headers,
        data={"categories": categories},
        files={"image": ("x.png", image_bytes)},
    )
    assert response.status_code == 422


def test_oversized_payload(client, headers):
    response = client.post(
        "/api/v1/identification/barcode",
        headers={**headers, "Content-Length": "999999999"},
        content=b"x",
    )
    assert response.status_code == 413


def test_label_ocr_structured(client, headers, app, image_bytes):
    class OCR:
        def read(self, image):
            return {
                "raw_text": "ZARA WOMAN\nART 7543/220\nM\n95% COTTON\n5% ELASTANE",
                "regions": [],
                "provider": "fixture",
            }

    app.state.ocr_provider = OCR()
    data = client.post(
        "/api/v1/identification/label",
        headers=headers,
        data={"brands": '[{"id":1,"name":"ZARA"}]', "sizes": '[{"id":9,"name":"M"}]'},
        files={"image": ("label.png", image_bytes)},
    ).json()["data"]
    assert data["brand_candidate"]["id"] == 1
    assert data["size_candidate"]["id"] == 9
    assert data["model_code_candidate"] == "7543/220"
    assert data["composition"][0]["material"] == "algodón"


def test_ocr_failure_returns_partial(client, headers, app, image_bytes):
    class OCR:
        def read(self, image):
            raise PipelineError("OCR_UNAVAILABLE", "offline", 503)

    app.state.ocr_provider = OCR()
    result = client.post(
        "/api/v1/identification/label",
        headers=headers,
        files={"image": ("label.png", image_bytes)},
    ).json()
    assert result["status"] == "partial"
    assert result["data"]["raw_text"] == ""


def test_publication_requires_approval_and_rights(client, headers, image_bytes):
    for data in [
        {},
        {"approved": "true", "rights_status": "unknown", "approval_id": "42"},
        {"approved": "true", "rights_status": "owned"},
    ]:
        response = client.post(
            "/api/v1/images/prepare-publication",
            headers=headers,
            data=data,
            files={"image": ("x.png", image_bytes)},
        )
        assert response.status_code == 403
    response = client.post(
        "/api/v1/images/prepare-publication",
        headers=headers,
        data={"approved": "true", "rights_status": "owned", "approval_id": "42"},
        files={"image": ("x.png", image_bytes)},
    )
    assert response.status_code == 200, response.text
    derivatives = response.json()["data"]["derivatives"]
    assert [item["profile"] for item in derivatives] == ["thumbnail", "card", "detail"]
    for item in derivatives:
        image = Image.open(io.BytesIO(base64.b64decode(item["content_base64"])))
        assert image.format == "WEBP"
        assert not image.getexif()


def test_match_endpoint_invalid_input_is_safe(client, headers):
    response = client.post(
        "/api/v1/products/match", headers=headers, json={"query": {"text": "a" * 4001}}
    )
    assert response.status_code == 422
    assert "a" * 50 not in response.text


@pytest.mark.asyncio
@pytest.mark.parametrize("declared_length", [None, b"1"])
async def test_chunked_body_limit_counts_actual_bytes_before_endpoint(declared_length):
    from mati_ai.core.http import RequestContextMiddleware

    called = False
    consumed = 0
    messages = iter([
        {"type": "http.request", "body": b"a" * 600_000, "more_body": True},
        {"type": "http.request", "body": b"b" * 500_000, "more_body": True},
        {"type": "http.request", "body": b"unread", "more_body": False},
    ])

    async def endpoint(scope, receive, send):
        nonlocal called
        called = True

    async def receive():
        nonlocal consumed
        consumed += 1
        return next(messages)

    sent = []

    async def send(message):
        sent.append(message)

    headers = [(b"x-request-id", b"chunked-example")]
    if declared_length is not None:
        headers.append((b"content-length", declared_length))
    scope = {
        "type": "http",
        "method": "POST",
        "path": "/api/v1/products/match",
        "headers": headers,
        "state": {},
    }
    middleware = RequestContextMiddleware(
        endpoint, Settings(max_image_bytes=1024, _env_file=None)
    )
    await middleware(scope, receive, send)
    assert called is False
    assert consumed == 2
    assert sent[0]["status"] == 413
    assert dict(sent[0]["headers"])[b"x-request-id"] == b"chunked-example"
    body = json.loads(sent[1]["body"])
    assert body["error"]["code"] == "PAYLOAD_TOO_LARGE"
    assert body["request_id"] == "chunked-example"


@pytest.mark.asyncio
async def test_bounded_body_preserves_chunked_content_and_updated_request_id():
    from mati_ai.core.http import RequestContextMiddleware
    from starlette.responses import JSONResponse

    chunks = iter([
        {"type": "http.request", "body": b'{"query":', "more_body": True},
        {"type": "http.request", "body": b'{"sku":"A"}}', "more_body": False},
    ])
    received = []
    sent = []

    async def endpoint(scope, receive, send):
        message = await receive()
        received.append(message)
        scope["state"]["request_id"] = "form-request-id"
        await JSONResponse({"ok": True})(scope, receive, send)

    async def receive():
        return next(chunks)

    async def send(message):
        sent.append(message)

    scope = {
        "type": "http",
        "method": "POST",
        "path": "/api/v1/products/match",
        "headers": [],
        "state": {},
    }
    middleware = RequestContextMiddleware(endpoint, Settings(_env_file=None))
    await middleware(scope, receive, send)
    assert json.loads(received[0]["body"]) == {"query": {"sku": "A"}}
    assert received[0]["more_body"] is False
    assert sent[0]["status"] == 200
    assert dict(sent[0]["headers"])[b"x-request-id"] == b"form-request-id"


def test_capabilities_reports_loaded_source_configuration(client, headers, app):
    class ConfiguredSources:
        def capabilities(self):
            return [{
                "id": "authorized-fixture",
                "name": "Authorized fixture",
                "adapter": "manual",
                "rights_status": "unknown",
            }]

    app.state.enrichment_service = ConfiguredSources()
    response = client.get("/api/v1/capabilities", headers=headers)
    data = response.json()["data"]
    assert data["external_sources_configured"] is True
    assert data["external_sources"][0]["id"] == "authorized-fixture"


@pytest.mark.asyncio
@pytest.mark.parametrize(
    "lengths",
    [
        [(b"content-length", b"-1")],
        [(b"content-length", b"invalid")],
        [(b"content-length", b"1"), (b"content-length", b"1")],
    ],
)
async def test_invalid_content_length_rejected_without_consuming_body(lengths):
    from mati_ai.core.http import RequestContextMiddleware

    called = False
    sent = []

    async def endpoint(scope, receive, send):
        nonlocal called
        called = True

    async def receive():
        raise AssertionError("Malformed length must be rejected before body ingestion")

    async def send(message):
        sent.append(message)

    scope = {
        "type": "http",
        "method": "POST",
        "path": "/api/v1/products/match",
        "headers": lengths,
        "state": {},
    }
    await RequestContextMiddleware(endpoint, Settings(_env_file=None))(
        scope, receive, send
    )
    assert called is False
    assert sent[0]["status"] == 413
    assert json.loads(sent[1]["body"])["error"]["code"] == "PAYLOAD_TOO_LARGE"


@pytest.mark.asyncio
async def test_disconnected_body_does_not_invoke_endpoint_or_send_response():
    from mati_ai.core.http import RequestContextMiddleware

    called = False
    sent = []

    async def endpoint(scope, receive, send):
        nonlocal called
        called = True

    async def receive():
        return {"type": "http.disconnect"}

    async def send(message):
        sent.append(message)

    scope = {
        "type": "http",
        "method": "POST",
        "path": "/api/v1/products/match",
        "headers": [],
        "state": {},
    }
    await RequestContextMiddleware(endpoint, Settings(_env_file=None))(
        scope, receive, send
    )
    assert called is False
    assert sent == []


def test_unexpected_failure_has_safe_error_and_request_id(headers):
    app = create_app(Settings(token="test-service-token", _env_file=None))

    @app.get("/fixture-error")
    def failure():
        raise RuntimeError("secret-fixture-payload")

    response = TestClient(app).get(
        "/fixture-error", headers={"X-Request-ID": "failure-request"}
    )
    assert response.status_code == 500
    assert response.headers["x-request-id"] == "failure-request"
    assert response.json()["error"]["code"] == "INTERNAL_ERROR"
    assert "secret-fixture-payload" not in response.text
    assert "RuntimeError" not in response.text
