import io

import numpy as np
import pytest
import zxingcpp
from PIL import Image

from mati_ai.contracts.identification import Embedding, MatchItem
from mati_ai.contracts.vision import CatalogValue
from mati_ai.identification.codes import (
    decode_codes,
    expand_upce,
    normalize_code,
    valid_gtin,
)
from mati_ai.identification.matching import rank_matches
from mati_ai.identification.normalization import normalize_label


@pytest.mark.parametrize(
    "value", ["4006381333931", "96385074", "036000291452", "00012345600012"]
)
def test_valid_gtin_checksums(value):
    assert valid_gtin(value)
    assert not valid_gtin(value[:-1] + str((int(value[-1]) + 1) % 10))


def test_checksums_are_ascii_and_preserve_zero():
    assert not valid_gtin("１２３４５６７０")
    assert normalize_code("036000291452", "UPC-A")["normalized_value"] == "036000291452"
    assert expand_upce("04210007") == "042000001007"
    assert valid_gtin(expand_upce("04210007"))


@pytest.mark.parametrize(
    "format,value",
    [
        (zxingcpp.BarcodeFormat.EAN13, "4006381333931"),
        (zxingcpp.BarcodeFormat.EAN8, "96385074"),
        (zxingcpp.BarcodeFormat.UPCA, "036000291452"),
        (zxingcpp.BarcodeFormat.UPCE, "04210007"),
        (zxingcpp.BarcodeFormat.Code128, "MATI-ABC-123"),
        (zxingcpp.BarcodeFormat.QRCode, "https://example.org/product/42"),
    ],
)
def test_real_barcode_and_qr_decode(format, value):
    barcode = zxingcpp.create_barcode(value, format)
    array = np.asarray(zxingcpp.write_barcode_to_image(barcode, scale=3))
    image = Image.fromarray(array).convert("RGB")
    result = decode_codes(image)
    assert result["state"] == "detected"
    assert any(item["normalized_value"] == value for item in result["codes"])
    if format == zxingcpp.BarcodeFormat.QRCode:
        assert result["codes"][0]["contains_url"]
        assert "QR_URL_NOT_FETCHED" in result["codes"][0]["warnings"]


def test_barcode_endpoint(client, headers):
    barcode = zxingcpp.create_barcode("4006381333931", zxingcpp.BarcodeFormat.EAN13)
    image = Image.fromarray(
        np.asarray(zxingcpp.write_barcode_to_image(barcode, scale=3))
    )
    out = io.BytesIO()
    image.save(out, format="PNG")
    response = client.post(
        "/api/v1/identification/barcode",
        headers=headers,
        files={"image": ("barcode.png", out.getvalue())},
    )
    assert response.status_code == 200, response.text
    assert response.json()["data"]["codes"][0]["checksum_valid"] is True


def test_ocr_normalization_preserves_raw_and_only_maps_known_catalog():
    raw = "ＺＡＲＡ WOMAN\n ART: 1234/567\n M\n 95% COTTON\n 5% ELASTANE"
    result = normalize_label(
        raw, [CatalogValue(id=3, name="ZARA")], [CatalogValue(id=9, name="M")]
    )
    assert result["raw_text"] == raw
    assert result["brand_candidate"]["id"] == 3
    assert result["size_candidate"]["id"] == 9
    assert result["model_code_candidate"] == "1234/567"
    assert sum(item["percentage"] for item in result["composition"]) == 100
    assert normalize_label("SOME UNLISTED BRAND", [], [])["brand_candidate"] is None


def test_matching_identifier_priority_and_conflicts():
    query = MatchItem(
        sku="AB1",
        gtin="4006381333931",
        brand="ZARA",
        model_code="123/4",
        embedding=Embedding(values=[1, 0], model_id="m", model_revision="r"),
    )
    candidates = [
        MatchItem(candidate_id=1, sku="AB1", gtin="4006381333931", brand="ZARA"),
        MatchItem(candidate_id=2, gtin="036000291452", embedding=query.embedding),
        MatchItem(candidate_id=3, embedding=query.embedding),
        MatchItem(candidate_id=4, model_code="123/4", brand="ZARA"),
    ]
    ranked = rank_matches(query, candidates)
    assert ranked[0]["candidate_id"] == 1
    assert ranked[1]["candidate_id"] == 4
    assert ranked[-1]["candidate_id"] == 2
    assert ranked[-1]["score"] <= 0.05
    assert ranked[2]["match_type"] == "similar_candidate"


def test_embedding_versions_cannot_mix():
    ranked = rank_matches(
        MatchItem(
            embedding=Embedding(values=[1], model_id="siglip", model_revision="a")
        ),
        [
            MatchItem(
                candidate_id=1,
                embedding=Embedding(values=[1], model_id="clip", model_revision="a"),
            )
        ],
    )
    assert "EMBEDDING_MODEL_MISMATCH" in ranked[0]["warnings"]
    assert "visual" not in ranked[0]["components"]


def test_matching_preserves_gtin_equivalence_across_upc_ean_padding():
    result = rank_matches(
        MatchItem(gtin="036000291452"),
        [
            MatchItem(candidate_id=1, gtin="0036000291452"),
            MatchItem(candidate_id=2, gtin="00036000291452"),
        ],
    )
    assert all(item["match_type"] == "exact_gtin" for item in result)
    assert all(item["components"]["gtin"] == 1 for item in result)
    assert all("GTIN_CONFLICT" not in item["conflicts"] for item in result)


def test_matching_prefers_authoritative_catalog_ids_over_display_labels():
    query = MatchItem.model_validate({
        "category_id": 10,
        "category": "PANTALONES",
        "color_id": 20,
        "color": "AZUL",
    })
    candidate = MatchItem.model_validate({
        "category_id": 10,
        "category": "TROUSERS",
        "color_id": 20,
        "color": "BLUE",
    })
    result = rank_matches(query, [candidate])[0]
    assert result["components"]["category"] == 1
    assert result["components"]["color"] == 1


@pytest.mark.parametrize("value", [float("nan"), float("inf"), float("-inf")])
def test_embedding_contract_rejects_non_finite_values(value):
    from pydantic import ValidationError

    with pytest.raises(ValidationError):
        Embedding(values=[1, value], model_id="fixture", model_revision="r1")


def test_opencv_multi_qr_retains_each_bounding_box(monkeypatch):
    from types import SimpleNamespace

    import cv2

    boxes = np.array([
        [[1, 2], [3, 2], [3, 4], [1, 4]],
        [[5, 6], [7, 6], [7, 8], [5, 8]],
    ], dtype=float)
    monkeypatch.setattr(cv2, "QRCodeDetector", lambda: SimpleNamespace(
        detectAndDecodeMulti=lambda image: (True, ["FIRST", "SECOND"], boxes, [])
    ))
    monkeypatch.setattr(cv2, "barcode_BarcodeDetector", lambda: SimpleNamespace(
        detectAndDecodeWithType=lambda image: (False, [], [], None)
    ), raising=False)
    monkeypatch.setattr(zxingcpp, "read_barcodes", lambda *args, **kwargs: [])
    result = decode_codes(Image.new("RGB", (20, 20)))
    assert len(result["codes"]) == 2
    assert result["codes"][0]["bounding_box"] == boxes[0].tolist()
    assert result["codes"][1]["bounding_box"] == boxes[1].tolist()
