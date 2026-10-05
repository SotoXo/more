"""Read-only deterministic/CPU audit. Synthetic fixtures are never catalog images.

Run from services/mati-ai with .venv/bin/python scripts/audit/audit_python.py.
No downloads, database access, or product/storage edits. Only audit output writes.
"""
import base64
import colorsys
import hashlib
import io
import json
import math
import random
import socket
import time
from datetime import UTC, datetime
from pathlib import Path
from unittest.mock import patch

import cv2
import httpx
import numpy as np
import zxingcpp
from PIL import Image, ImageDraw, ImageEnhance, ImageFilter, ImageFont

from mati_ai.compatibility.engine import evaluate_compatibility
from mati_ai.contracts.colorimetry import ColorimetrySwatch, GarmentColorimetry
from mati_ai.contracts.embedding import Embedding
from mati_ai.contracts.identification import MatchItem
from mati_ai.contracts.vision import CatalogValue
from mati_ai.core.config import Settings
from mati_ai.core.errors import PipelineError
from mati_ai.enrichment.contracts import ExternalProduct, ExternalQuery, SourceConfig
from mati_ai.enrichment.ranking import rank_products
from mati_ai.enrichment.safe_fetch import FetchError, SafeFetcher, checked_url, resolve_public
from mati_ai.identification.codes import decode_codes, normalize_code
from mati_ai.identification.matching import rank_matches
from mati_ai.identification.normalization import normalize_label
from mati_ai.identification.ocr import EasyOCRProvider
from mati_ai.publication.pipeline import prepare_publication
from mati_ai.vision.color import extract_colors
from mati_ai.vision.color_distance import delta_e_2000

ROOT = Path(__file__).resolve().parents[4]
OUTPUT = ROOT / "storage/app/ai-audit"
OUTPUT.mkdir(parents=True, exist_ok=True)
REPORT = {"created_utc": datetime.now(UTC).isoformat(), "scope": "synthetic_readonly_cpu",
          "database_access": False, "downloads": False, "checks": []}


def record(name, passed, **details):
    REPORT["checks"].append({"name": name, "passed": bool(passed), **details})


def encoded(image, fmt="PNG", **kwargs):
    stream = io.BytesIO()
    image.save(stream, fmt, **kwargs)
    return stream.getvalue()


def transformed(image):
    noise = np.random.default_rng(42).normal(0, 10, np.asarray(image).shape)
    arr = np.asarray(image).astype(float)
    return {
        "original": image,
        "brightness_065": ImageEnhance.Brightness(image).enhance(0.65),
        "brightness_125": ImageEnhance.Brightness(image).enhance(1.25),
        "blur_2": image.filter(ImageFilter.GaussianBlur(2)),
        "rotation_10": image.rotate(10, expand=True, fillcolor="white"),
        "jpeg_60": Image.open(io.BytesIO(encoded(image, "JPEG", quality=60))).convert("RGB"),
        "warm_cast": Image.fromarray(np.clip(arr * [1.08, 1, 0.92], 0, 255).astype(np.uint8)),
        "cool_cast": Image.fromarray(np.clip(arr * [0.92, 1, 1.08], 0, 255).astype(np.uint8)),
        "noise_10": Image.fromarray(np.clip(arr + noise, 0, 255).astype(np.uint8)),
    }


# The 34 numerical reference pairs published by Sharma/Wu/Dalal.
# https://hajim.rochester.edu/ece/sites/gsharma/ciede2000/dataNprograms/ciede2000testdata.txt
DATA = """50 2.6772 -79.7751 50 0 -82.7485 2.0425
50 3.1571 -77.2803 50 0 -82.7485 2.8615
50 2.8361 -74.0200 50 0 -82.7485 3.4412
50 -1.3802 -84.2814 50 0 -82.7485 1
50 -1.1848 -84.8006 50 0 -82.7485 1
50 -.9009 -85.5211 50 0 -82.7485 1
50 0 0 50 -1 2 2.3669
50 -1 2 50 0 0 2.3669
50 2.49 -.001 50 -2.49 .0009 7.1792
50 2.49 -.001 50 -2.49 .001 7.1792
50 2.49 -.001 50 -2.49 .0011 7.2195
50 2.49 -.001 50 -2.49 .0012 7.2195
50 -.001 2.49 50 .0009 -2.49 4.8045
50 -.001 2.49 50 .001 -2.49 4.8045
50 -.001 2.49 50 .0011 -2.49 4.7461
50 2.5 0 50 0 -2.5 4.3065
50 2.5 0 73 25 -18 27.1492
50 2.5 0 61 -5 29 22.8977
50 2.5 0 56 -27 -3 31.903
50 2.5 0 58 24 15 19.4535
50 2.5 0 50 3.1736 .5854 1
50 2.5 0 50 3.2972 0 1
50 2.5 0 50 1.8634 .5757 1
50 2.5 0 50 3.2592 .335 1
60.2574 -34.0099 36.2677 60.4626 -34.1751 39.4387 1.2644
63.0109 -31.0961 -5.8663 62.8187 -29.7946 -4.0864 1.263
61.2901 3.7196 -5.3901 61.4292 2.248 -4.962 1.8731
35.0831 -44.1164 3.7933 35.0232 -40.0716 1.5901 1.8645
22.7233 20.0904 -46.694 23.0331 14.973 -42.5619 2.0373
36.4612 47.858 18.3852 36.2715 50.5065 21.2231 1.4146
90.8027 -2.0831 1.441 91.1528 -1.6435 .0447 1.4441
90.9257 -.5406 -.9208 88.6381 -.8985 -.7239 1.5381
6.7747 -.2908 -2.4247 5.8714 -.0985 -2.2286 .6377
2.0776 .0795 -1.135 .9033 -.0636 -.5514 .9082"""
rows = np.array([[float(v) for v in row.split()] for row in DATA.splitlines()])
actual = delta_e_2000(rows[:, :3], rows[:, 3:6])
errors = np.abs(actual - rows[:, 6])
record("ciede2000_34_published_reference_pairs", bool(np.all(errors <= 0.00005)),
       n=34, maximum_absolute_error=float(errors.max()), tolerance=0.00005,
       reference_url="https://hajim.rochester.edu/ece/sites/gsharma/ciede2000/dataNprograms/ciede2000testdata.txt",
       failed_rows=(np.where(errors > .00005)[0] + 1).tolist())
record("ciede2000_symmetry", np.allclose(actual, delta_e_2000(rows[:, 3:6], rows[:, :3]), atol=1e-12))
record("ciede2000_identity_zero", np.all(delta_e_2000(rows[:, :3], rows[:, :3]) == 0))

COLORS = [
    ("beige", "#d9c5a0"), ("navy", "#24324c"), ("wine", "#84334b"),
    ("olive", "#68784e"), ("pink", "#d99aaa"), ("blue", "#417eb5"),
    ("white", "#f0f0ef"), ("black", "#161616"), ("gray", "#838383"),
    ("red", "#d44848"), ("yellow", "#d9bb49"), ("pure_red", "#ff0000"),
]
catalog = [CatalogValue(id=i + 1, name=name, hex=value) for i, (name, value) in enumerate(COLORS)]
color_results = []
for i, (name, value) in enumerate(COLORS):
    image = Image.new("RGB", (320, 400), "white")
    ImageDraw.Draw(image).rectangle((65, 50, 255, 350), fill=value)
    original_hash = hashlib.sha256(image.tobytes()).hexdigest()
    for transform, modified in transformed(image).items():
        started = time.perf_counter()
        data = extract_colors(modified, catalog)["colorimetry"]
        top = data["palette"][0] if data["palette"] else None
        color_results.append({
            "fixture": name, "transform": transform, "status": data["status"],
            "foreground_confidence": data["foreground_confidence"],
            "palette_size": len(data["palette"]), "coverage": data["coverage"],
            "catalog_id": top["catalog_color_id"] if top else None,
            "expected_fixture_id": i + 1, "top_delta_e": top["delta_e"] if top else None,
            "primary": data["primary_color"] is not None, "warnings": data["warnings"],
            "duration_ms": round((time.perf_counter() - started) * 1000, 3),
        })
    record("original_immutable_" + name, hashlib.sha256(image.tobytes()).hexdigest() == original_hash)
baseline = [r for r in color_results if r["transform"] == "original"]
REPORT["synthetic_color"] = {"n": len(color_results), "baseline_n": len(baseline),
    "baseline_nonempty": sum(r["palette_size"] > 0 for r in baseline),
    "baseline_completed": sum(r["status"] == "completed" for r in baseline),
    "baseline_exact_catalog_matches": sum(r["catalog_id"] == r["expected_fixture_id"] for r in baseline),
    "rows": color_results}
for fixture, image in [
    ("whole_uniform", Image.new("RGB", (320, 400), "#d9c5a0")),
    ("complex_background", Image.fromarray(np.random.default_rng(3).integers(0, 256, (400, 320, 3), dtype=np.uint8))),
]:
    result = extract_colors(image, catalog)["colorimetry"]
    record("foreground_abstain_" + fixture,
           result["status"] == "insufficient_evidence" and not result["palette"],
           status=result["status"], warnings=result["warnings"])

# Synthetic profiles test pair rules directly; no claim of photographic accuracy.
def swatch(rgb, weight=1.0):
    lab = cv2.cvtColor(np.array(rgb, np.float32).reshape(1, 1, 3) / 255, cv2.COLOR_RGB2LAB)[0, 0]
    return ColorimetrySwatch(rgb=rgb, hex="#" + "".join(f"{c:02x}" for c in rgb),
        lab=tuple(float(x) for x in lab), percentage=weight, confidence_score=.9,
        family="gray" if math.hypot(*lab[1:]) < 12 else "red", descriptive_name="synthetic")


def profile(colors, weights=None, temperature="neutral"):
    weights = weights or [1 / len(colors)] * len(colors)
    palette = [swatch(c, w) for c, w in zip(colors, weights)]
    return GarmentColorimetry(status="completed", foreground_confidence=.9,
        palette=palette, primary_color=palette[0] if len(palette) == 1 else None,
        secondary_colors=palette if len(palette) > 1 else [], temperature=temperature,
        lightness="medium", chroma="medium", saturation="medium", neutrality="medium",
        internal_contrast="medium", color_variety="monochrome" if len(colors) == 1 else "multicolor",
        coverage=sum(weights))


rgb_hues = [tuple(round(c * 255) for c in colorsys.hsv_to_rgb(h / 360, .60, .75))
            for h in range(0, 360, 30)]
profiles = [profile([rgb], temperature="warm" if i < 4 else "cool") for i, rgb in enumerate(rgb_hues)]
profiles += [profile([(230, 230, 230)]), profile([(20, 20, 20)]), profile([(130, 130, 130)])]
rand = random.Random(27)
for _ in range(15):
    samples = rand.sample(rgb_hues, rand.randint(2, 4))
    weights = [rand.random() + .05 for _ in samples]
    weights = [w / sum(weights) for w in weights]
    profiles.append(profile(samples, weights))
scores = []
symmetry_errors = []
role_errors = []
for i, a in enumerate(profiles):
    for b in profiles[i:]:
        first = evaluate_compatibility(a, b)
        second = evaluate_compatibility(b, a)
        scores.append(first.compatibility_score)
        symmetry_errors.append(abs(first.compatibility_score - second.compatibility_score))
        changed = a.model_copy(update={"primary_color": None, "secondary_colors": [], "accent_colors": a.palette})
        role_errors.append(abs(first.compatibility_score - evaluate_compatibility(changed, b).compatibility_score))
record("compatibility_symmetry", max(symmetry_errors) <= .01, n=len(scores), max_score_error=max(symmetry_errors))
record("compatibility_roles_do_not_duplicate_mass", max(role_errors) <= .01, n=len(scores), max_error=max(role_errors))
record("compatibility_scores_bounded", all(s is not None and 0 <= s <= 100 for s in scores),
       n=len(scores), min_score=min(scores), max_score=max(scores))
bad = profiles[0].model_copy(update={"foreground_confidence": .69})
record("compatibility_insufficient_is_null", evaluate_compatibility(bad, profiles[1]).compatibility_score is None)
unknown = profiles[0].model_copy(update={"temperature": "unknown"})
partial = evaluate_compatibility(unknown, profiles[1])
record("compatibility_missing_temperature_excluded",
       partial.components.temperature is None and "TEMPERATURE_COMPONENT_UNAVAILABLE" in partial.warnings,
       status=partial.status, score=partial.compatibility_score)
REPORT["compatibility"] = {"n_profiles": len(profiles), "n_pairs": len(scores),
                           "score_kind": "heuristic_ranking_signal"}

barcodes = [
    ("EAN13", zxingcpp.BarcodeFormat.EAN13, "4006381333931"),
    ("EAN8", zxingcpp.BarcodeFormat.EAN8, "96385074"),
    ("UPCA", zxingcpp.BarcodeFormat.UPCA, "036000291452"),
    ("UPCE", zxingcpp.BarcodeFormat.UPCE, "04210007"),
    ("Code128", zxingcpp.BarcodeFormat.Code128, "MATI-ABC-123"),
    ("QR_URL", zxingcpp.BarcodeFormat.QRCode, "https://example.org/product/42"),
]
barcode_rows = []
for name, fmt, value in barcodes:
    img = Image.fromarray(np.asarray(zxingcpp.write_barcode_to_image(zxingcpp.create_barcode(value, fmt), scale=3))).convert("RGB")
    variants = {"original": img, "rotation_90": img.rotate(90, expand=True),
                "blur_1": img.filter(ImageFilter.GaussianBlur(1)),
                "half_size": img.resize((max(16, img.width // 2), max(16, img.height // 2))),
                "jpeg_60": Image.open(io.BytesIO(encoded(img, "JPEG", quality=60))).convert("RGB"),
                "crop_20pct": img.crop((round(img.width * .2), 0, img.width, img.height))}
    for variant, altered in variants.items():
        started = time.perf_counter()
        codes = decode_codes(altered)["codes"]
        found = next((c for c in codes if c["normalized_value"] == value), None)
        barcode_rows.append({"symbology": name, "variant": variant, "expected_detected": found is not None,
                             "count": len(codes), "checksum_valid": found["checksum_valid"] if found else None,
                             "warnings": found["warnings"] if found else [], "ms": round((time.perf_counter() - started) * 1000, 3)})
record("barcode_six_baselines", all(r["expected_detected"] for r in barcode_rows if r["variant"] == "original"),
       n=6)
record("qr_url_never_fetches", "QR_URL_NOT_FETCHED" in next(r for r in barcode_rows if r["symbology"] == "QR_URL" and r["variant"] == "original")["warnings"])
invalid_codes = ["4006381333932", "96385075", "036000291453", "00012345600013", "１２３４５６７０"]
record("invalid_checksum_never_valid", all(normalize_code(v, "GTIN")["checksum_valid"] is False for v in invalid_codes), n=len(invalid_codes))
REPORT["barcode"] = {"n": len(barcode_rows), "success": sum(r["expected_detected"] for r in barcode_rows), "rows": barcode_rows,
    "kind": "generated_raster_real_decoder", "unknown_confidence": True}

# Local OCR CPU weights only. No download fallbacks.
font = ImageFont.truetype("/usr/share/fonts/truetype/ubuntu/Ubuntu-R.ttf", 44)
labels = [
    ("ZARA\nART: 1234/567\nM\n95% COTTON\n5% ELASTANE", "ZARA", "M", "1234/567"),
    ("NIKE\nMODEL: AB1234\nL\n100% POLYESTER", "NIKE", "L", "AB1234"),
    ("MANGO\nREF: 887766\nS\n70% COTTON\n30% LINEN", "MANGO", "S", "887766"),
]
ocr = EasyOCRProvider(Settings(device="cpu", local_files_only=True, ocr_download_enabled=False, _env_file=None))
ocr_rows = []
for text, brand, size, model in labels:
    label = Image.new("RGB", (1000, 450), "white")
    ImageDraw.Draw(label).multiline_text((40, 30), text, fill="black", font=font, spacing=14)
    started = time.perf_counter()
    try:
        result = ocr.read(label)
        normalized = normalize_label(result["raw_text"],
            [CatalogValue(id=brand, name=brand)], [CatalogValue(id=size, name=size)])
        ocr_rows.append({"status": "completed", "ms": round((time.perf_counter() - started) * 1000, 3),
            "expected_brand": brand, "brand_correct": bool(normalized["brand_candidate"] and normalized["brand_candidate"]["id"] == brand),
            "size_correct": bool(normalized["size_candidate"] and normalized["size_candidate"]["id"] == size),
            "model_correct": normalized["model_code_candidate"] == model,
            "regions": len(result["regions"]), "text_characters": len(result["raw_text"])})
    except PipelineError as exc:
        ocr_rows.append({"status": "unavailable", "error_code": exc.code,
                         "ms": round((time.perf_counter() - started) * 1000, 3)})
REPORT["ocr_cpu"] = {"fixture_kind": "synthetic_rendered_labels", "n": len(ocr_rows), "rows": ocr_rows,
                    "real_photographic_labels_tested": 0, "downloads": False}
for text, brand, size, model in labels:
    data = normalize_label(text, [CatalogValue(id=brand, name=brand)], [CatalogValue(id=size, name=size)])
    record("normalization_exact_" + brand, data["brand_candidate"]["id"] == brand and data["size_candidate"]["id"] == size and data["model_code_candidate"] == model)
low = normalize_label("ZARA", [CatalogValue(id=1, name="ZARA")], [])
REPORT["ocr_normalization_limitation"] = {"note": "OCR region confidence is not an input to normalize_label", "exact_text_candidate_score": low["brand_candidate"]["confidence_score"]}

revision = "a" * 40
def embedding(values, **kwargs):
    norm = math.hypot(*values)
    return Embedding(values=values, model_id="synthetic", model_revision=revision,
        dimension=len(values), pipeline_version="registration-1.2.0",
        embedding_version="projected-image-l2-1", preprocess_version="srgb-exif-transformers-image-1",
        normalized=True, normalization="l2", norm=norm, **kwargs)
vec = embedding([1., 0.])
query = MatchItem(sku="SKU-1", gtin="4006381333931", brand="ZARA", model_code="AA1", embedding=vec)
matches = rank_matches(query, [
    MatchItem(candidate_id="exact", sku="SKU-1", gtin="4006381333931", brand="ZARA", model_code="AA1"),
    MatchItem(candidate_id="conflict", sku="SKU-1", gtin="036000291452", brand="MANGO", model_code="ZZ9", embedding=vec),
    MatchItem(candidate_id="visual", embedding=vec),
])
record("internal_identifiers_over_visual", matches[0]["candidate_id"] == "exact" and matches[1]["candidate_id"] == "visual", rows=matches)
record("internal_conflict_cannot_visual_compensate", next(r for r in matches if r["candidate_id"] == "conflict")["score"] <= .05)
legacy = rank_matches(MatchItem(embedding=Embedding(values=[1., 0.], model_id="m", model_revision="main")),
                      [MatchItem(candidate_id=1, embedding=Embedding(values=[1., 0.], model_id="m", model_revision="main"))])[0]
record("internal_legacy_embeddings_rejected", "EMBEDDING_MODEL_MISMATCH" in legacy["warnings"] and "visual" not in legacy["components"])
external = rank_products(ExternalQuery(embedding=[1., 0.], model_id="m", model_revision="main"), [
    ExternalProduct(source="fixture", source_id="fixture", title="fixture", canonical_url="https://catalog.example/a",
        embedding=[1., 0.], model_id="m", model_revision="main")])[0]
REPORT["external_embedding_version_guard_gap"] = {"unresolved_revision_accepted_for_cosine": external.visual_score == 1., "visual_score": external.visual_score}

settings = Settings(_env_file=None)
publication_image = Image.new("RGB", (2000, 1200), "#d9c5a0")
source = encoded(publication_image, "JPEG")
publication = prepare_publication(source, settings, True, "owned", "audit-id")
for derivative, size in zip(publication["derivatives"], [240, 640, 1600]):
    raw = base64.b64decode(derivative["content_base64"])
    img = Image.open(io.BytesIO(raw))
    record("publication_" + derivative["profile"],
        max(img.size) <= size and img.format == "WEBP" and not img.getexif() and hashlib.sha256(raw).hexdigest() == derivative["sha256"],
        dimensions=list(img.size), byte_count=len(raw))
record("publication_original_hash", publication["source_sha256"] == hashlib.sha256(source).hexdigest() and publication["original_modified"] is False)
for approved, rights, approval_id in [(False, "owned", "id"), (True, "unknown", "id"),
    (True, "prohibited", "id"), (True, "owned", ""), (True, "owned", "   ")]:
    try:
        prepare_publication(source, settings, approved, rights, approval_id)
        code = "ALLOWED"
    except PipelineError as exc:
        code = exc.code
    record(f"rights_{approved}_{rights}_{approval_id!r}", code == "RIGHTS_NOT_APPROVED", result=code)

def cfg():
    return SourceConfig(id="audit", name="audit", adapter="manual", enabled=True,
        domains=["catalog.example", "cdn.example"], terms_approved=True,
        terms_url="https://catalog.example/terms", reviewed_at="2026-10-04", min_interval_seconds=.1)
urls = ["http://catalog.example/a", "https://catalog.example:8080/a", "https://x.catalog.example/a",
    "https://catalog.example.evil/a", "https://catalog.example@evil.example/a",
    "https://evil.example@catalog.example/a", "https://catalog.example/a#f",
    "https://catalog.example\\@evil.example/a", "https://catalog.example/\r\nx",
    "https://127.0.0.1/a", "https://[::1]/a", "https://169.254.169.254/latest",
    "file:///etc/passwd", "data:image/svg+xml,x", "ftp://catalog.example/a",
    "https://catalog.example/" + "a" * 2049]
for i, url in enumerate(urls):
    try:
        checked_url(url, cfg().domains + ["127.0.0.1", "::1", "169.254.169.254"])
        code = "ALLOWED"
    except FetchError as exc:
        code = exc.code
    record("ssrf_url_" + str(i), code == "UNSAFE_REMOTE_URL", result=code)
addresses = ["127.0.0.1", "10.0.0.1", "172.16.0.1", "192.168.1.1", "169.254.169.254",
    "100.64.0.1", "::1", "fd00::1", "::ffff:93.184.216.34", "2002:5db8:d822::", "224.0.0.1", "fe80::1"]
for address in addresses:
    with patch("socket.getaddrinfo", return_value=[(2, 1, 6, "", (address, 443))]):
        try:
            resolve_public("catalog.example", 443)
            code = "ALLOWED"
        except FetchError as exc:
            code = exc.code
    record("ssrf_dns_" + address, code == "UNSAFE_REMOTE_URL", result=code)
with patch("socket.getaddrinfo", return_value=[(2, 1, 6, "", ("93.184.216.34", 443)), (2, 1, 6, "", ("10.1.2.3", 443))]):
    try:
        resolve_public("catalog.example", 443)
        code = "ALLOWED"
    except FetchError as exc:
        code = exc.code
record("ssrf_mixed_public_private_dns", code == "UNSAFE_REMOTE_URL")
scenarios = {
    "redirect_private": (302, {"location": "https://127.0.0.1/a"}, b"", "UNSAFE_REMOTE_URL"),
    "redirect_cross_domain": (302, {"location": "https://evil.example/a"}, b"", "UNSAFE_REMOTE_URL"),
    "redirect_limit": (302, {"location": "/a"}, b"", "SOURCE_REDIRECT_LIMIT"),
    "response_gzip": (200, {"content-type": "text/html", "content-encoding": "gzip"}, b"x", "SOURCE_CONTENT_ENCODING"),
    "response_svg": (200, {"content-type": "image/svg+xml"}, b"<svg/>", "SOURCE_CONTENT_TYPE"),
    "response_html_as_image": (200, {"content-type": "text/html"}, b"<h1/>", "SOURCE_CONTENT_TYPE"),
    "response_oversized_declared": (200, {"content-type": "text/html", "content-length": "999999"}, b"x", "SOURCE_TOO_LARGE"),
    "response_oversized_stream": (200, {"content-type": "text/html"}, b"x" * 5000, "SOURCE_TOO_LARGE"),
    "response_429": (429, {"retry-after": "1"}, b"", "SOURCE_RATE_LIMITED"),
    "response_403": (403, {}, b"", "SOURCE_UNAVAILABLE"),
}
with patch("socket.getaddrinfo", return_value=[(2, 1, 6, "", ("93.184.216.34", 443))]):
    for name, (status, headers, body, expected) in scenarios.items():
        calls = []
        def handler(request):
            calls.append((request.url.host, request.headers["host"], request.extensions.get("sni_hostname")))
            if request.url.path == "/robots.txt":
                return httpx.Response(200, headers={"content-type": "text/plain"}, content=b"User-agent: *\nAllow: /\n")
            return httpx.Response(status, headers=headers, content=body)
        fetcher = SafeFetcher(cfg(), httpx.MockTransport(handler))
        try:
            types = {"image/png"} if name == "response_html_as_image" else {"text/html"}
            fetcher.fetch("https://catalog.example/a", types, max_bytes=1024)
            code = "ALLOWED"
        except FetchError as exc:
            code = exc.code
        record("fetch_" + name, code == expected, result=code, expected=expected)
        record("pin_host_sni_" + name, all(ip == "93.184.216.34" and host == sni == "catalog.example" for ip, host, sni in calls))
    for name, status, content, expected in [
        ("unavailable", 404, b"", "SOURCE_UNAVAILABLE"),
        ("disallow", 200, b"User-agent: *\nDisallow: /\n", "SOURCE_ROBOTS_BLOCKED"),
        ("crawl_delay", 200, b"User-agent: *\nCrawl-delay: 5\n", "SOURCE_ROBOTS_RATE_LIMIT"),
    ]:
        def handler(request):
            return httpx.Response(status, headers={"content-type": "text/plain"}, content=content)
        try:
            SafeFetcher(cfg(), httpx.MockTransport(handler)).fetch("https://catalog.example/a", {"text/html"})
            code = "ALLOWED"
        except FetchError as exc:
            code = exc.code
        record("robots_" + name, code == expected, result=code)

REPORT["summary"] = {"checks": len(REPORT["checks"]),
    "passed": sum(c["passed"] for c in REPORT["checks"]),
    "failed": sum(not c["passed"] for c in REPORT["checks"])}
(OUTPUT / "python-synthetic-audit.json").write_text(json.dumps(REPORT, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
print(json.dumps({**REPORT["summary"], "color_baselines": {k: v for k, v in REPORT["synthetic_color"].items() if k != "rows"},
    "barcode": {k: v for k, v in REPORT["barcode"].items() if k != "rows"},
    "ocr": REPORT["ocr_cpu"], "compatibility": REPORT["compatibility"]}, ensure_ascii=False, indent=2))
