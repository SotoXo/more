import io

import pytest
from fastapi.testclient import TestClient
from PIL import Image

from mati_ai.core.config import Settings
from mati_ai.main import create_app


@pytest.fixture
def settings():
    return Settings(token="test-token", local_files_only=True, _env_file=None)


@pytest.fixture
def app(settings):
    return create_app(settings)


@pytest.fixture
def client(app):
    with TestClient(app) as client:
        yield client


@pytest.fixture
def headers():
    return {"Authorization": "Bearer test-token", "X-Request-Id": "test-request-123"}


@pytest.fixture
def image_bytes():
    image = Image.new("RGB", (400, 500), "#d9c5a0")
    buffer = io.BytesIO()
    image.save(buffer, format="PNG")
    return buffer.getvalue()
