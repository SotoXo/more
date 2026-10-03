import os

import pytest
from PIL import Image

from mati_ai.core.config import Settings
from mati_ai.core.model_manager import ModelManager


@pytest.mark.model_integration
@pytest.mark.skipif(
    os.environ.get("MATI_AI_RUN_MODEL_TESTS") != "1",
    reason="Requires prepared model cache; explicitly opt in",
)
def test_real_siglip_normalized_embedding():
    import numpy as np

    manager = ModelManager(Settings(_env_file=None))
    result = manager.infer(
        Image.new("RGB", (224, 224), "beige"),
        ["This is a photo of trousers.", "This is a photo of a blouse."],
    )
    assert len(result["similarities"]) == 2
    assert len(result["embedding"]) >= 512
    assert np.isclose(np.linalg.norm(result["embedding"]), 1, atol=1e-5)
    assert result["model"]["model_id"].startswith("google/siglip")
