import io

import numpy as np
import pytest
from PIL import Image, ImageDraw

from mati_ai.contracts.vision import CatalogValue
from mati_ai.core.errors import PipelineError
from mati_ai.core.model_manager import ModelManager
from mati_ai.vision.classification import category_prompts, classify
from mati_ai.vision.color import extract_colors
from mati_ai.vision.embeddings import cosine_similarity
from mati_ai.vision.validation import validate_image


def test_orientation_hash_and_original_immutable(settings):
    image = Image.new("RGB", (60, 30), "red")
    exif = Image.Exif()
    exif[274] = 6
    buffer = io.BytesIO()
    image.save(buffer, format="JPEG", exif=exif)
    raw = buffer.getvalue()
    result = validate_image(raw, settings)
    assert result.image.size == (30, 60)
    assert result.image.getexif().get(274) is None
    assert buffer.getvalue() == raw
    assert len(result.metadata["sha256"]) == 64
    assert len(result.metadata["perceptual_hash"]) == 16


def test_limits_and_animated_image(settings):
    settings.max_pixels = 10000
    buffer = io.BytesIO()
    Image.new("RGB", (101, 100)).save(buffer, format="PNG")
    with pytest.raises(PipelineError, match="dimensiones"):
        validate_image(buffer.getvalue(), settings)
    settings.max_image_bytes = 2
    with pytest.raises(PipelineError, match="tamaño"):
        validate_image(buffer.getvalue(), settings)


def test_color_reduces_white_border_but_requires_target_garment_evidence():
    image = Image.new("RGB", (100, 100), "white")
    ImageDraw.Draw(image).rectangle((20, 10, 80, 90), fill="#d9c5a0")
    result = extract_colors(
        image,
        [
            CatalogValue(id=7, name="BEIGE", hex="#d9c5a0"),
            CatalogValue(id=8, name="BLANCO", hex="#ffffff"),
        ],
    )
    assert result["dominant_color"]["catalog_id"] is None
    assert result["colorimetry"]["commercial_color_status"] == "insufficient_evidence"
    assert result["dominant_color"]["evidence"]["observed_catalog_match_accepted"] is True
    assert result["foreground_confidence"] > 0.7
    assert result["dominant_color"]["percentage"] > 0.9


def test_unknown_color_does_not_create_entity():
    result = extract_colors(Image.new("RGB", (100, 100), "green"), [])
    assert result["dominant_color"] is None
    assert result["palette"] == []
    assert result["colorimetry"]["status"] == "insufficient_evidence"


def test_classification_scores_not_probabilities():
    categories = [CatalogValue(id=1, name="POLERA"), CatalogValue(id=2, name="FALDA")]
    assert "t-shirt" in category_prompts(categories)[0]
    result = classify(categories, [0.2, 0.201])
    assert result["abstained"] is True
    assert result["top_k"][0]["category_id"] == 2
    assert "probability" not in result["top_k"][0]


def test_cosine_invalid_and_normalized():
    assert cosine_similarity([3, 4], [3, 4]) == 1
    assert cosine_similarity([0, 0], [1, 2]) is None
    assert cosine_similarity([1], [1, 2]) is None
    assert cosine_similarity([float("nan")], [1]) is None


def test_model_manager_single_load_cpu_and_lock(settings, monkeypatch):
    from types import SimpleNamespace

    import torch
    from transformers import AutoModel, AutoProcessor

    counts = {"loads": 0}

    class Inputs(dict):
        def to(self, device):
            return self

    class Processor:
        def __call__(self, **kwargs):
            return Inputs()

    class Model:
        config = SimpleNamespace(_commit_hash="pinned-sha")

        def to(self, device):
            return self

        def eval(self):
            return self

        def __call__(self, **kwargs):
            return SimpleNamespace(
                image_embeds=torch.tensor([[3.0, 4.0]]),
                text_embeds=torch.tensor([[3.0, 4.0], [4.0, 3.0]]),
            )

    def load(*args, **kwargs):
        counts["loads"] += 1
        return Model()

    monkeypatch.setattr(AutoModel, "from_pretrained", load)
    monkeypatch.setattr(AutoProcessor, "from_pretrained", lambda *a, **k: Processor())
    monkeypatch.setattr(torch.cuda, "is_available", lambda: False)
    settings.inference_lock_timeout = 0.001
    manager = ModelManager(settings)
    for _ in range(2):
        result = manager.infer(Image.new("RGB", (224, 224)), ["a", "b"])
        assert np.isclose(np.linalg.norm(result["embedding"]), 1)
        assert result["device"] == "cpu"
        assert result["model"]["model_revision"] == "pinned-sha"
    assert counts["loads"] == 1
    manager._lock.acquire()
    try:
        with pytest.raises(PipelineError, match="ocupado"):
            manager.infer(Image.new("RGB", (224, 224)), ["a"])
    finally:
        manager._lock.release()


@pytest.mark.parametrize("name, english", [
    ("PANTALONES", "trousers"),
    ("BLUSAS", "blouse"),
    ("FALDAS", "skirt"),
    ("CAMISAS", "shirt"),
    ("VESTIDOS", "dress"),
    ("CONJUNTOS", "matching clothing sets"),
    ("POLERAS", "t-shirts"),
    ("POLOS Y KNITWEAR", "polo shirt or knitwear"),
])
def test_real_plural_catalog_categories_receive_visual_aliases(name, english):
    prompts = category_prompts([CatalogValue(id=99, name=name)])
    assert prompts == [f"This is a photo of {english}."]


@pytest.mark.parametrize("magnitude", [1e300, 1e-300])
def test_cosine_finite_extreme_magnitudes_remain_stable(magnitude):
    assert cosine_similarity([magnitude, magnitude], [1.0, 1.0]) == pytest.approx(1.0)
    assert cosine_similarity([magnitude, 0], [-magnitude, 0]) == pytest.approx(-1.0)
    assert cosine_similarity([magnitude, 0], [0, magnitude]) == pytest.approx(0.0)
