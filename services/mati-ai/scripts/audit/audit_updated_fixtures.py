"""Audit-only refreshed fixtures for contracts; original tests remain unchanged."""
import io
import json
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import patch

from fastapi.testclient import TestClient
from PIL import Image, ImageDraw

from mati_ai.core.config import Settings
from mati_ai.core.model_manager import ModelManager
from mati_ai.core.errors import PipelineError
from mati_ai.main import create_app

ROOT = Path(__file__).resolve().parents[4]
checks = []
def check(name, passed, **kw):
    checks.append(dict(name=name, passed=bool(passed), **kw))

settings = Settings(token="audit-mock-service-token", device="cpu", local_files_only=True, _env_file=None)
app = create_app(settings)
revision = "a" * 40
class Model:
    def infer(self, image, prompts):
        return dict(similarities=[.30 - .05 * i for i in range(len(prompts))],
            embedding=[.6, .8], embedding_metadata=dict(values=[.6, .8], dimension=2,
                model_id="audit_fixture", model_revision=revision, pipeline_version="registration-1.2.0",
                embedding_version="projected-image-l2-1", preprocess_version="srgb-exif-transformers-image-1",
                normalized=True, normalization="l2", norm=1., dimension_source="generated_vector"),
            model=dict(model_id="audit_fixture", model_revision=revision, pipeline_version="registration-1.2.0"),
            device="cpu", vram_peak_mb=0., metrics={"inference": 0., "model_load": 0.})
image = Image.new("RGB", (400, 500), "white")
ImageDraw.Draw(image).rectangle((80, 60, 320, 440), fill="#d9c5a0")
stream = io.BytesIO()
image.save(stream, "PNG")
raw = stream.getvalue()
headers = {"Authorization": "Bearer audit-mock-service-token", "X-Request-Id": "audit-mock-123"}
app.state.model_manager = Model()
client = TestClient(app)
r = client.post("/api/v1/garments/analyze", headers=headers,
    data={"categories": '[{"id":44,"name":"POLERA"},{"id":2,"name":"PANTALÓN"}]',
          "colors": '[{"id":6,"name":"BEIGE","hex":"#d9c5a0"}]', "include_embedding": "true"},
    files={"image": ("fixture.png", raw, "image/png")})
body = r.json()
check("updated_contract_fixture_200", r.status_code == 200, http_status=r.status_code)
check("updated_fixture_embedding", body.get("data", {}).get("embedding", {}).get("values") == [.6, .8])
check("updated_fixture_color_id", body.get("data", {}).get("colors", {}).get("dominant_color", {}).get("catalog_id") == 6)
class Unavailable:
    def infer(self, *a):
        raise PipelineError("MODEL_UNAVAILABLE", "offline", 503)
app.state.model_manager = Unavailable()
r = client.post("/api/v1/garments/analyze", headers=headers, data={"categories": '[{"id":1,"name":"POLERA"}]'},
    files={"image": ("fixture.png", raw, "image/png")})
b = r.json()
check("supported_foreground_model_failure_retains_color", r.status_code == 200 and b["status"] == "partial" and bool(b["data"]["colors"]["palette"]), abstentions=b.get("abstentions"))
class OCRLow:
    def read(self, image):
        return {"raw_text": "ZARA\nM", "regions": [{"text": "ZARA", "confidence_score": .01, "bounding_box": [[1,1],[2,1],[2,2],[1,2]]}],
                "provider": "audit-low-confidence-fixture"}
app.state.ocr_provider = OCRLow()
r = client.post("/api/v1/identification/label", headers=headers,
    data={"brands": '[{"id":3,"name":"ZARA"}]', "sizes": '[{"id":9,"name":"M"}]'},
    files={"image": ("fixture.png", raw, "image/png")})
b = r.json()
check("low_confidence_ocr_fixture_does_not_block_normalized_candidate", r.status_code == 200 and b["data"]["brand_candidate"]["confidence_score"] == 1.,
      ocr_confidence=.01, candidate_confidence=b["data"]["brand_candidate"]["confidence_score"],
      human_review=b["human_review_required"], kind="behavior_observation_not_accuracy")
# Mock loader verifies cache/lock without loading visual weights.
import torch
from transformers import AutoModel, AutoProcessor
loads = []
class Inputs(dict):
    def to(self, device):
        return self
class Processor:
    def __call__(self, **kw):
        return Inputs()
class ModelValid:
    config = SimpleNamespace(_commit_hash=revision)
    def to(self, device):
        return self
    def eval(self):
        return self
    def __call__(self, **kw):
        return SimpleNamespace(image_embeds=torch.tensor([[3.,4.]]), text_embeds=torch.tensor([[3.,4.],[4.,3.]]))
def loader(*a, **kw):
    loads.append(1)
    return ModelValid()
with patch.object(AutoModel, "from_pretrained", loader), patch.object(AutoProcessor, "from_pretrained", lambda *a, **kw: Processor()):
    manager = ModelManager(settings)
    first = manager.infer(image, ["a", "b"])
    second = manager.infer(image, ["a", "b"])
    check("updated_revision_fixture_one_load", len(loads) == 1)
    check("updated_revision_immutable_norm", first["model"]["model_revision"] == revision and abs(first["embedding_metadata"]["norm"] - 1) <= 1e-5)
    manager.settings.inference_lock_timeout = .001
    manager._lock.acquire()
    try:
        try:
            manager.infer(image, ["a"])
            code = "ALLOWED"
        except PipelineError as exc:
            code = exc.code
        check("model_lock_busy_503", code == "GPU_BUSY", error_code=code)
    finally:
        manager._lock.release()
# CUDA failure branch mocked; no CUDA weights/tensors allocated.
manager = ModelManager(settings)
manager.device = "cuda"
manager._model = ModelValid()
calls=[]
def forward(inputs):
    calls.append(manager.device)
    if manager.device == "cuda":
        raise RuntimeError("simulated CUDA failure")
    return "cpu-result"
with patch.object(torch.cuda, "empty_cache", lambda: None):
    result = manager._run(Inputs(), forward)
check("cuda_failure_controlled_fallback_cpu", result == "cpu-result" and manager.device == "cpu" and calls == ["cuda", "cpu"], kind="mock_no_real_gpu")
output={"kind": "updated_contract_mocks_no_real_visual_inference", "checks": checks,
        "summary": {"n": len(checks), "passed": sum(c["passed"] for c in checks), "failed": sum(not c["passed"] for c in checks)}}
(ROOT / "storage/app/ai-audit/python-updated-fixtures.json").write_text(json.dumps(output, ensure_ascii=False, indent=2) + "\n")
print(json.dumps(output, ensure_ascii=False, indent=2))
