from fastapi.testclient import TestClient

from mati_ai.core.config import Settings
from mati_ai.enrichment.contracts import SourceConfig
from mati_ai.enrichment.service import EnrichmentService
from mati_ai.main import create_app


def client():
    app = create_app(Settings(token="unit-test-service-token"))
    app.state.enrichment_service = EnrichmentService(
        [
            SourceConfig(
                id="permitted",
                name="Configured fixture",
                adapter="manual",
                enabled=True,
                domains=["catalog.example"],
                terms_approved=True,
                terms_url="https://catalog.example/terms",
                reviewed_at="2026-09-28",
                products=[
                    {
                        "title": "Pantalón beige",
                        "brand": "ZARA",
                        "gtin": "4006381333931",
                        "canonical_url": "https://catalog.example/p",
                        "images": ["https://catalog.example/p.jpg"],
                    }
                ],
            )
        ]
    )
    return TestClient(app)


def test_external_search_contract_and_origin():
    result = client().post(
        "/api/v1/products/external-search",
        headers={
            "Authorization": "Bearer unit-test-service-token",
            "X-Request-Id": "external-123",
        },
        json={"gtin": "4006381333931", "source_ids": ["permitted"]},
    )
    assert result.status_code == 200
    body = result.json()
    assert body["request_id"] == "external-123"
    assert body["human_review_required"] is True
    candidate = body["data"]["candidates"][0]
    assert candidate["source_id"] == "permitted"
    assert candidate["identifier_score"] == 1
    assert candidate["images"][0]["rights_status"] == "unknown"
    assert "embedding" not in candidate


def test_external_auth_and_closed_payload():
    assert (
        client()
        .post("/api/v1/products/external-search", json={"query": "pants"})
        .status_code
        == 401
    )
    result = client().post(
        "/api/v1/products/external-search",
        headers={"Authorization": "Bearer unit-test-service-token"},
        json={"query": "pants", "url": "http://localhost/"},
    )
    assert result.status_code == 422
    assert result.json()["error"]["code"] == "INVALID_REQUEST"


def test_unknown_image_rights_are_rejected():
    result = client().post(
        "/api/v1/products/external-image",
        headers={"Authorization": "Bearer unit-test-service-token"},
        json={
            "source_id": "permitted",
            "product_url": "https://catalog.example/p",
            "original_url": "https://catalog.example/p.jpg",
        },
    )
    assert result.status_code == 422
    assert result.json()["error"]["code"] == "RIGHTS_NOT_APPROVED"


def test_external_empty_and_invalid_identifier_are_explicit():
    result = client().post(
        "/api/v1/products/external-search",
        headers={"Authorization": "Bearer unit-test-service-token"},
        json={"gtin": "123"},
    )
    assert result.status_code == 200
    assert result.json()["status"] == "partial"
    assert result.json()["data"]["candidates"] == []
    assert "INVALID_GTIN" in result.json()["warnings"]
