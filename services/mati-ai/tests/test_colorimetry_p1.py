"""Commercial color abstention must preserve useful geometric observations."""

import hashlib

import pytest
from PIL import Image, ImageDraw
from pydantic import ValidationError

from mati_ai.compatibility.engine import evaluate_compatibility
from mati_ai.contracts.colorimetry import GarmentColorimetry
from mati_ai.contracts.vision import CatalogValue
from mati_ai.vision.color import extract_colors


def observed_object(multiple_colors=False):
    image = Image.new("RGB", (300, 400), "white")
    draw = ImageDraw.Draw(image)
    draw.rectangle((70, 40, 230, 360), fill="#d9c5a0")
    if multiple_colors:
        # Connected upper/lower regions are still one geometric foreground.
        draw.rectangle((70, 210, 230, 360), fill="#252525")
    return image


def catalog():
    return [
        CatalogValue(id=7, name="BEIGE", hex="#d9c5a0"),
        CatalogValue(id=8, name="NEGRO CARBON", hex="#252525"),
    ]


@pytest.mark.parametrize("multiple_colors", [False, True])
def test_connected_foreground_never_proves_a_target_garment(multiple_colors):
    image = observed_object(multiple_colors)
    before = hashlib.sha256(image.tobytes()).digest()
    result = extract_colors(image, catalog())
    profile = GarmentColorimetry.model_validate(result["colorimetry"])
    assert hashlib.sha256(image.tobytes()).digest() == before
    assert profile.foreground_confidence >= 0.70
    assert profile.palette
    assert profile.coverage > 0.85
    assert profile.status == "completed"
    assert profile.commercial_color_status == "insufficient_evidence"
    assert profile.evidence["observation_status"] == "completed"
    assert profile.evidence["target_garment_identity_verified"] is False
    assert profile.evidence["properties_scope"] == "observed_geometric_foreground_only"
    assert "TARGET_GARMENT_IDENTITY_UNVERIFIED" in profile.warnings
    assert sum(s.percentage for s in profile.palette) == pytest.approx(profile.coverage)
    roles = [profile.primary_color, *profile.secondary_colors, *profile.accent_colors]
    for swatch in [*result["palette"], result["dominant_color"], *result["secondary_colors"]]:
        if swatch is None:
            continue
        assert swatch["catalog_color_id"] is None
        assert swatch["catalog_id"] is None
        assert swatch["catalog_color_name"] is None
        assert swatch["catalog_name"] is None
        assert swatch["evidence"]["catalog_match_accepted"] is False
    assert all(s.catalog_color_id is None for s in roles if s is not None)


def test_commercial_abstention_keeps_technical_palette_compatibility():
    profile = GarmentColorimetry.model_validate(extract_colors(observed_object(), catalog())["colorimetry"])
    result = evaluate_compatibility(profile, profile)
    assert result.compatibility_score is not None
    assert 0 <= result.compatibility_score <= 100
    assert profile.commercial_color_status == "insufficient_evidence"


def test_uniform_image_remains_insufficient_without_manufacturing_palette():
    result = extract_colors(Image.new("RGB", (300, 400), "white"), catalog())
    profile = result["colorimetry"]
    assert profile["status"] == "insufficient_evidence"
    assert profile["commercial_color_status"] == "insufficient_evidence"
    assert profile["palette"] == []
    assert profile["primary_color"] is None
    assert "BACKGROUND_INTERFERENCE" in profile["warnings"]


def test_contract_cannot_claim_completed_commercial_color_from_geometry():
    profile = extract_colors(observed_object(), catalog())["colorimetry"]
    profile["commercial_color_status"] = "completed"
    with pytest.raises(ValidationError):
        GarmentColorimetry.model_validate(profile)


def test_ocr_capabilities_distinguish_presence_from_loaded_reader(settings, tmp_path):
    from mati_ai.identification.ocr import EasyOCRProvider

    settings.ocr_model_directory = str(tmp_path)
    settings.ocr_download_enabled = False
    provider = EasyOCRProvider(settings)
    missing = provider.capabilities_metadata()
    assert missing["weights_present"] == {"craft_mlt_25k.pth": False, "latin_g2.pth": False}
    for filename in missing["weights_present"]:
        (tmp_path / filename).write_bytes(b"not-actual-model-weights")
    present = provider.capabilities_metadata()
    assert all(present["weights_present"].values())
    assert present["reader_loaded"] is False
    assert present["download_enabled"] is False
    assert present["weights_presence_is_not_load_validation"] is True
    assert present["quality_evaluated"] is False
    assert provider._reader is None


def test_capabilities_report_color_abstention_and_ocr_preparation(client, headers):
    result = client.get("/api/v1/capabilities", headers=headers).json()["data"]
    assert result["colorimetry"]["segmentation"] == "geometric_foreground_heuristic"
    assert result["colorimetry"]["target_garment_identity_verified"] is False
    assert result["colorimetry"]["automatic_catalog_assignment"] is False
    assert result["colorimetry"]["commercial_color_status"] == "insufficient_evidence"
    assert result["ocr"]["device"] == "cpu"
    assert result["ocr"]["download_enabled"] is False
    assert result["ocr"]["reader_loaded"] is False
    assert set(result["ocr"]["weights_present"]) == {"craft_mlt_25k.pth", "latin_g2.pth"}
