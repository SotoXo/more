import socket
from io import BytesIO

import httpx
import pytest
from mati_ai.enrichment.adapters.html import HtmlCatalogAdapter
from mati_ai.enrichment.adapters.manual import ManualCatalogAdapter
from mati_ai.enrichment.contracts import (
    ExternalProduct,
    ExternalQuery,
    ImageRequest,
    SourceConfig,
)
from mati_ai.enrichment.ranking import normalized_gtin, rank_products
from mati_ai.enrichment.safe_fetch import (
    FetchError,
    SafeFetcher,
    checked_url,
    resolve_public,
)
from mati_ai.enrichment.service import EnrichmentService
from PIL import Image


def config(**kwargs):
    return SourceConfig(
        id="fixture",
        name="Fixture",
        adapter="manual",
        enabled=True,
        domains=["catalog.example", "cdn.example"],
        terms_approved=True,
        terms_url="https://catalog.example/terms",
        reviewed_at="2026-09-28",
        min_interval_seconds=0.1,
        **kwargs,
    )


@pytest.fixture(autouse=True)
def public_dns(monkeypatch):
    monkeypatch.setattr(
        socket,
        "getaddrinfo",
        lambda *a, **k: [
            (socket.AF_INET, socket.SOCK_STREAM, 6, "", ("93.184.216.34", 443))
        ],
    )


@pytest.mark.parametrize(
    "url",
    [
        "file:///etc/passwd",
        "ftp://catalog.example/x",
        "gopher://catalog.example",
        "data:text/plain,x",
        "http://catalog.example/x",
        "https://127.0.0.1/x",
        "https://localhost/x",
        "https://169.254.169.254/latest",
        "https://[::1]/",
        "https://catalog.example:8443/",
        "https://user:pass@catalog.example/",
        "https://other.example/",
        "https://catalog.example.evil/",
        "https://catalog.example/#fragment",
        "https://catalog.example\\@other.example/",
    ],
)
def test_url_policy(url):
    with pytest.raises(FetchError):
        checked_url(
            url, ["catalog.example", "127.0.0.1", "169.254.169.254", "::1", "localhost"]
        )


@pytest.mark.parametrize(
    "address",
    [
        "127.0.0.1",
        "10.0.0.1",
        "172.16.0.1",
        "192.168.1.1",
        "169.254.169.254",
        "100.64.0.1",
        "::1",
        "fd00::1",
        "::ffff:127.0.0.1",
        "224.0.0.1",
    ],
)
def test_dns_ssrf(monkeypatch, address):
    monkeypatch.setattr(
        socket, "getaddrinfo", lambda *a, **k: [(2, 1, 6, "", (address, 443))]
    )
    with pytest.raises(FetchError, match="UNSAFE_REMOTE_URL"):
        resolve_public("catalog.example", 443)


def transport(handler):
    def wrapped(request):
        # DNS is pinned, while certificate validation retains the real host.
        assert request.url.host == "93.184.216.34"
        assert request.extensions["sni_hostname"] in ["catalog.example", "cdn.example"]
        assert request.headers["host"] in ["catalog.example", "cdn.example"]
        if request.url.path == "/robots.txt":
            return httpx.Response(
                200,
                headers={"content-type": "text/plain"},
                content=b"User-agent: *\nAllow: /\n",
            )
        return handler(request)

    return httpx.MockTransport(wrapped)


def test_stream_and_pin():
    fetcher = SafeFetcher(
        config(),
        transport(
            lambda r: httpx.Response(
                200, headers={"content-type": "text/html"}, content=b"<h1>Dress</h1>"
            )
        ),
    )
    result = fetcher.fetch("https://catalog.example/p", {"text/html"})
    assert result.url == "https://catalog.example/p"
    assert b"Dress" in result.content


def test_redirect_revalidates():
    fetcher = SafeFetcher(
        config(),
        transport(
            lambda r: httpx.Response(
                302, headers={"location": "https://169.254.169.254/latest"}
            )
        ),
    )
    with pytest.raises(FetchError, match="UNSAFE_REMOTE_URL"):
        fetcher.fetch("https://catalog.example/p", {"text/html"})


def test_dns_rebinding_is_not_resolved_by_httpx(monkeypatch):
    calls = []

    def dns(*args, **kwargs):
        calls.append(args)
        return [
            (2, 1, 6, "", ("93.184.216.34" if len(calls) == 1 else "127.0.0.1", 443))
        ]

    monkeypatch.setattr(socket, "getaddrinfo", dns)
    fetcher = SafeFetcher(
        config(),
        transport(lambda r: httpx.Response(200, headers={"content-type": "text/html"})),
    )
    with pytest.raises(FetchError, match="UNSAFE_REMOTE_URL"):
        fetcher.fetch("https://catalog.example/p", {"text/html"})
    assert len(calls) == 2  # Robots lookup then page; second private answer is blocked.


@pytest.mark.parametrize(
    "headers,body,code",
    [
        (
            {"content-type": "text/html", "content-length": "999999999"},
            b"x",
            "SOURCE_TOO_LARGE",
        ),
        ({"content-type": "image/svg+xml"}, b"<svg/>", "SOURCE_CONTENT_TYPE"),
        (
            {"content-type": "text/html", "content-encoding": "gzip"},
            b"x",
            "SOURCE_CONTENT_ENCODING",
        ),
        ({"content-type": "text/html"}, b"x" * 2000, "SOURCE_TOO_LARGE"),
    ],
)
def test_response_limits(headers, body, code):
    fetcher = SafeFetcher(
        config(),
        transport(lambda r: httpx.Response(200, headers=headers, content=body)),
    )
    with pytest.raises(FetchError, match=code):
        fetcher.fetch("https://catalog.example/p", {"text/html"}, max_bytes=1024)


def test_robots_forbidden():
    mock = httpx.MockTransport(
        lambda r: httpx.Response(
            200,
            headers={"content-type": "text/plain"},
            content=b"User-agent: *\nDisallow: /\n",
        )
    )
    with pytest.raises(FetchError, match="SOURCE_ROBOTS_BLOCKED"):
        SafeFetcher(config(), mock).fetch("https://catalog.example/p", {"text/html"})


def test_missing_terms_fail_closed():
    cfg = config().model_copy(update={"terms_approved": False})
    with pytest.raises(FetchError, match="SOURCE_TERMS_BLOCKED"):
        SafeFetcher(cfg).fetch("https://catalog.example", {"text/html"})


def test_rate_limit_has_persistent_cooldown():
    fetcher = SafeFetcher(
        config(),
        transport(lambda r: httpx.Response(429, headers={"retry-after": "120"})),
    )
    with pytest.raises(FetchError, match="SOURCE_RATE_LIMITED"):
        fetcher.fetch("https://catalog.example/p", {"text/html"})
    with pytest.raises(FetchError, match="SOURCE_RATE_LIMITED"):
        fetcher.fetch("https://catalog.example/p", {"text/html"})


def product(**kwargs):
    return ExternalProduct(
        source="Fixture",
        source_id="fixture",
        title="Pantalón beige",
        canonical_url="https://catalog.example/p",
        **kwargs,
    )


def test_ranking_conflicts_cannot_be_compensated():
    query = ExternalQuery(gtin="4006381333931", brand="ZARA", query="Pantalón beige")
    wrong = product(gtin="5901234123457", brand="ZARA")
    right = product(gtin="4006381333931", brand="ZARA")
    wrong.canonical_url += "2"
    result = rank_products(query, [wrong, right])
    assert result[0].identifier_score == 1
    assert result[1].final_score == 0
    assert "GTIN_CONFLICT" in result[1].conflicts


def test_gtin_leading_zeros():
    assert normalized_gtin("036000291452") == normalized_gtin("0036000291452")
    assert normalized_gtin("4006381333932") is None


def test_manual_adapter_and_empty_service():
    cfg = config(
        products=[
            {
                "title": "Pantalón",
                "model_code": "1234/567",
                "gtin": "4006381333931",
                "canonical_url": "https://catalog.example/p",
                "images": ["https://cdn.example/p.jpg"],
            }
        ]
    )
    adapter = ManualCatalogAdapter(cfg)
    assert adapter.search_by_gtin("4006381333931")[0].title == "Pantalón"
    assert (
        adapter.search_by_model_code("1234/567")[0].images[0].rights_status == "unknown"
    )
    assert adapter.search_by_text("pantalon")
    assert adapter.fetch_images("https://catalog.example/p")
    assert EnrichmentService([]).search(ExternalQuery(query="pantalon"))[
        "warnings"
    ] == ["NO_EXTERNAL_SOURCES_CONFIGURED"]


def test_html_adapter_real_parser():
    cfg = config().model_copy(
        update={
            "adapter": "html",
            "search_url": "https://catalog.example/search?q={query}",
            "selectors": {
                "product_links": "a.product",
                "title": "h1",
                "brand": ".brand",
                "model_code": ".model",
                "images": "img",
                "price": "[itemprop=price]",
                "availability": ".stock",
            },
        }
    )

    def handler(request):
        body = (
            b'<a class="product" href="/p">product</a>'
            if request.url.path == "/search"
            else b'<h1>Beige trousers</h1><b class="brand">ZARA</b><b class="model">1234/567</b>'
            b'<img src="https://cdn.example/a.jpg"><meta itemprop="price" content="12.5">'
            b'<span class="stock">In stock</span>'
        )
        return httpx.Response(200, headers={"content-type": "text/html"}, content=body)

    adapter = HtmlCatalogAdapter(cfg, SafeFetcher(cfg, transport(handler)))
    result = adapter.search_by_model_code("1234/567", "ZARA")[0]
    assert result.title == "Beige trousers"
    assert result.model_code == "1234/567"
    assert result.images[0].original_url == "https://cdn.example/a.jpg"
    assert result.price == "12.5"


def test_external_image_requires_configured_rights():
    cfg = config(
        products=[
            {
                "title": "P",
                "canonical_url": "https://catalog.example/p",
                "images": ["https://cdn.example/a.jpg"],
            }
        ]
    )
    service = EnrichmentService([cfg])
    request = ImageRequest(
        source_id="fixture",
        product_url="https://catalog.example/p",
        original_url="https://cdn.example/a.jpg",
    )
    with pytest.raises(FetchError, match="RIGHTS_NOT_APPROVED"):
        service.image(request)
    permitted = cfg.model_copy(
        update={
            "rights_status": "provider_authorized",
            "rights_evidence": "agreement-2026",
        }
    )
    service = EnrichmentService([permitted])
    stream = BytesIO()
    Image.new("RGB", (30, 40), "beige").save(stream, "PNG")
    service.fetchers["fixture"] = SafeFetcher(
        permitted,
        transport(
            lambda r: httpx.Response(
                200, headers={"content-type": "image/png"}, content=stream.getvalue()
            )
        ),
    )
    result = service.image(request)
    assert result["width"] == 30 and result["height"] == 40
    assert result["sha256"] and result["image_base64"]


def test_unknown_rights_can_be_referenced_but_not_published():
    cfg = config(products=[{
        "title": "Reference", "canonical_url": "https://catalog.example/p",
        "images": ["https://cdn.example/a.jpg"],
    }])
    service = EnrichmentService([cfg])
    stream = BytesIO()
    Image.new("RGB", (30, 40), "beige").save(stream, "PNG")
    service.fetchers["fixture"] = SafeFetcher(cfg, transport(lambda r: httpx.Response(
        200, headers={"content-type": "image/png"}, content=stream.getvalue())))
    request = ImageRequest(source_id="fixture", product_url="https://catalog.example/p",
                           original_url="https://cdn.example/a.jpg", purpose="reference")
    assert service.image(request)["rights_status"] == "unknown"
    with pytest.raises(FetchError, match="RIGHTS_NOT_APPROVED"):
        service.image(request.model_copy(update={"purpose": "publication"}))


def test_prohibited_image_is_never_a_reference():
    cfg = config(rights_status="prohibited", products=[{
        "title": "Blocked", "canonical_url": "https://catalog.example/p",
        "images": ["https://cdn.example/a.jpg"],
    }])
    service = EnrichmentService([cfg])
    with pytest.raises(FetchError, match="RIGHTS_NOT_APPROVED"):
        service.image(ImageRequest(source_id="fixture", product_url="https://catalog.example/p",
                                   original_url="https://cdn.example/a.jpg", purpose="reference"))


def test_brand_can_start_configured_text_search():
    cfg = config(products=[{
        "title": "Pantalón beige",
        "brand": "ZARA",
        "canonical_url": "https://catalog.example/p",
        "images": [],
    }])
    result = EnrichmentService([cfg]).search(
        ExternalQuery(brand="ZARA", category="Pantalón", color="beige")
    )
    assert result["candidates"][0]["brand"] == "ZARA"
    assert "INSUFFICIENT_SEARCH_EVIDENCE" not in result["warnings"]


def test_external_cosine_keeps_extreme_finite_values_bounded():
    query = ExternalQuery(
        query="pantalon",
        embedding=[1e300, 1e300],
        model_id="fixture",
        model_revision="one",
    )
    candidate = product(
        embedding=[1e300, 1e300],
        model_id="fixture",
        model_revision="one",
    )
    ranked = rank_products(query, [candidate])[0]
    assert ranked.visual_score == pytest.approx(1.0)
    assert 0 <= ranked.final_score <= 1
