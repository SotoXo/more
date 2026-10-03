import json
import time

from fastapi import APIRouter, Depends, Request
from pydantic import TypeAdapter, ValidationError
from starlette.concurrency import run_in_threadpool
from starlette.datastructures import UploadFile

from mati_ai.api.dependencies import require_service_token
from mati_ai.contracts.common import envelope
from mati_ai.contracts.identification import MatchRequest
from mati_ai.contracts.vision import CatalogValue
from mati_ai.core.errors import PipelineError
from mati_ai.identification.codes import decode_codes
from mati_ai.identification.matching import rank_matches
from mati_ai.identification.normalization import normalize_label
from mati_ai.publication.pipeline import prepare_publication
from mati_ai.vision.attributes import pattern_prediction, pattern_prompts
from mati_ai.vision.classification import category_prompts, classify
from mati_ai.vision.color import extract_colors
from mati_ai.vision.quality import assess_quality
from mati_ai.vision.validation import validate_image

router = APIRouter(prefix="/api/v1", dependencies=[Depends(require_service_token)])


def catalog(form, name: str, *, required=False) -> list[CatalogValue]:
    try:
        encoded = form.get(name)
        if encoded:
            values = json.loads(encoded)
        else:
            values = [json.loads(value) for value in form.getlist(name + "[]")]
        if (
            not isinstance(values, list)
            or len(values) > 150
            or (required and not values)
        ):
            raise ValueError()
        result = TypeAdapter(list[CatalogValue]).validate_python(values)
        if len({str(item.id) for item in result}) != len(result):
            raise ValueError()
        return result
    except (ValueError, TypeError, ValidationError):
        raise PipelineError(
            "INVALID_TAXONOMY",
            f"El catálogo {name} debe contener valores válidos proporcionados por Laravel.",
        ) from None


async def upload(request: Request):
    form = await request.form(max_files=1, max_fields=1000, max_part_size=100_000)
    item = form.get("image")
    if not isinstance(item, UploadFile):
        raise PipelineError("INVALID_REQUEST", "Se requiere el archivo image.")
    raw = await item.read(request.app.state.settings.max_image_bytes + 1)
    await item.close()
    supplied = form.get("request_id")
    if supplied and not request.headers.get("x-request-id"):
        import re

        if re.fullmatch(r"[A-Za-z0-9._:-]{1,100}", str(supplied)):
            request.state.request_id = str(supplied)
    return form, raw


@router.get("/capabilities")
def capabilities(request: Request):
    settings = request.app.state.settings
    from mati_ai.enrichment.api import get_service

    source_warnings = []
    try:
        sources = get_service(request).capabilities()
    except PipelineError as exc:
        sources = []
        source_warnings.append(exc.code)
    return envelope(
        request,
        {
            "operations": [
                "garments.analyze",
                "identification.barcode",
                "identification.label",
                "products.match",
                "products.external-search",
                "products.external-image",
                "images.prepare-publication",
            ],
            "visual_model": {
                "provider": settings.model_provider,
                "model_id": settings.model_id,
                "local_files_only": settings.local_files_only,
                "loaded": request.app.state.model_manager._model is not None,
            },
            "ocr": {
                "provider": "easyocr",
                "enabled": settings.ocr_enabled,
                "device": "cpu",
            },
            "barcode_symbologies": [
                "EAN-13",
                "EAN-8",
                "UPC-A",
                "UPC-E",
                "CODE128",
                "QR",
            ],
            "external_sources_configured": bool(sources),
            "external_sources": sources,
            "limits": {
                "image_bytes": settings.max_image_bytes,
                "image_pixels": settings.max_pixels,
            },
        },
        warnings=source_warnings,
    )


def analyze_pipeline(
    request: Request, raw: bytes, categories, colors, patterns, include_embedding
):
    started = time.perf_counter()
    timings = {}

    def measure(name, fn):
        start = time.perf_counter()
        result = fn()
        timings[name] = round((time.perf_counter() - start) * 1000, 3)
        return result

    image = measure(
        "validation", lambda: validate_image(raw, request.app.state.settings)
    )
    quality = measure("quality", lambda: assess_quality(image.image))
    palette = measure("color", lambda: extract_colors(image.image, colors))
    codes = measure("barcode", lambda: decode_codes(image.image))
    data = {
        "image": image.metadata,
        "quality": quality,
        "colors": palette,
        "codes": codes["codes"],
        "classification": {"top_k": [], "abstained": True},
        "attributes": {"pattern": None},
    }
    warnings = quality["warnings"] + palette["warnings"]
    abstentions = []
    model = None
    try:
        prompts = category_prompts(categories) + pattern_prompts(patterns)
        result = request.app.state.model_manager.infer(image.image, prompts)
        model = result["model"]
        data["classification"] = classify(
            categories, result["similarities"][: len(categories)]
        )
        data["attributes"]["pattern"] = pattern_prediction(
            patterns, result["similarities"][len(categories) :]
        )
        data["inference"] = {
            "device": result["device"],
            "vram_peak_mb": result["vram_peak_mb"],
        }
        timings.update(result["metrics"])
        if include_embedding:
            data["embedding"] = {
                "values": result["embedding"],
                "model_id": model["model_id"],
                "model_revision": model["model_revision"],
            }
        if data["classification"]["abstained"]:
            abstentions.append("CATEGORY_LOW_MARGIN_OR_SIMILARITY")
        warnings.append("VISUAL_SCORES_ARE_NOT_CALIBRATED_PROBABILITIES")
    except PipelineError as exc:
        abstentions.append(exc.code)
        warnings.append("VISUAL_MODEL_UNAVAILABLE_MANUAL_ENTRY_AVAILABLE")
    timings["total"] = round((time.perf_counter() - started) * 1000, 3)
    return envelope(
        request,
        data,
        status="partial" if abstentions else "completed",
        warnings=warnings,
        abstentions=abstentions,
        model=model,
        timing_ms=timings,
    )


@router.post("/garments/analyze")
async def analyze(request: Request):
    form, raw = await upload(request)
    categories = catalog(form, "categories", required=True)
    colors = catalog(form, "colors")
    patterns = catalog(form, "patterns")
    return await run_in_threadpool(
        analyze_pipeline,
        request,
        raw,
        categories,
        colors,
        patterns,
        str(form.get("include_embedding", "false")).lower() in ("true", "1"),
    )


@router.post("/identification/barcode")
async def barcode(request: Request):
    _, raw = await upload(request)

    def process():
        start = time.perf_counter()
        image = validate_image(raw, request.app.state.settings)
        result = decode_codes(image.image)
        result["image"] = image.metadata
        return envelope(
            request,
            result,
            status="completed" if result["codes"] else "insufficient_evidence",
            timing_ms={"barcode": round((time.perf_counter() - start) * 1000, 3)},
        )

    return await run_in_threadpool(process)


@router.post("/identification/label")
async def label(request: Request):
    form, raw = await upload(request)
    brands, sizes = catalog(form, "brands"), catalog(form, "sizes")

    def process():
        start = time.perf_counter()
        image = validate_image(raw, request.app.state.settings)
        try:
            result = request.app.state.ocr_provider.read(image.image)
        except PipelineError as exc:
            return envelope(
                request,
                {
                    **normalize_label("", brands, sizes),
                    "regions": [],
                    "image": image.metadata,
                },
                status="partial",
                abstentions=[exc.code],
            )
        data = normalize_label(result["raw_text"], brands, sizes)
        data.update(
            regions=result["regions"], provider=result["provider"], image=image.metadata
        )
        return envelope(
            request,
            data,
            status="completed" if data["raw_text"] else "insufficient_evidence",
            warnings=data["warnings"],
            timing_ms={"ocr": round((time.perf_counter() - start) * 1000, 3)},
            model={"model_id": "easyocr_cpu", "model_revision": "1.7.2"},
        )

    return await run_in_threadpool(process)


@router.post("/products/match")
def match(request: Request, payload: MatchRequest):
    start = time.perf_counter()
    matches = rank_matches(payload.query, payload.candidates, payload.limit)
    return envelope(
        request,
        {"matches": matches},
        status="completed" if matches else "insufficient_evidence",
        timing_ms={"matching": round((time.perf_counter() - start) * 1000, 3)},
    )


@router.post("/images/prepare-publication")
async def publication(request: Request):
    form, raw = await upload(request)

    def process():
        start = time.perf_counter()
        data = prepare_publication(
            raw,
            request.app.state.settings,
            str(form.get("approved", "false")).lower() in ("true", "1"),
            str(form.get("rights_status", "unknown")),
            str(form.get("approval_id", "")),
        )
        return envelope(
            request,
            data,
            timing_ms={"publication": round((time.perf_counter() - start) * 1000, 3)},
        )

    return await run_in_threadpool(process)
