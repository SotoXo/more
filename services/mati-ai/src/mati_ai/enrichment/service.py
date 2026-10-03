import base64
import hashlib
import json
import logging
import time
from datetime import UTC, datetime
from functools import lru_cache
from io import BytesIO
from pathlib import Path

from PIL import Image, UnidentifiedImageError

from .adapters.html import HtmlCatalogAdapter
from .adapters.manual import ManualCatalogAdapter
from .contracts import PUBLISHABLE_RIGHTS, ExternalQuery, ImageRequest, SourceConfig
from .ranking import normalized_gtin, rank_products
from .safe_fetch import FetchError, SafeFetcher

logger = logging.getLogger(__name__)


class EnrichmentService:
    def __init__(self, configurations: list[SourceConfig]):
        self.sources = {}
        self.fetchers = {}
        self.warnings: list[str] = []
        for config in configurations:
            if not config.enabled:
                continue
            if not (config.terms_approved and config.terms_url and config.reviewed_at):
                self.warnings.append(f"{config.id}:SOURCE_TERMS_BLOCKED")
                continue
            if config.id in self.sources:
                raise ValueError("duplicate source id")
            fetcher = SafeFetcher(config)
            self.fetchers[config.id] = fetcher
            self.sources[config.id] = (
                HtmlCatalogAdapter(config, fetcher)
                if config.adapter == "html"
                else ManualCatalogAdapter(config)
            )

    def capabilities(self) -> list[dict]:
        return [
            {
                "id": key,
                "name": adapter.config.name,
                "adapter": adapter.config.adapter,
                "rights_status": adapter.config.rights_status,
            }
            for key, adapter in self.sources.items()
        ]

    def search(self, query: ExternalQuery) -> dict:
        warnings = list(self.warnings)
        candidates = []
        identifiers = query.source_ids or list(self.sources)
        if not identifiers:
            return {
                "candidates": [],
                "warnings": [*warnings, "NO_EXTERNAL_SOURCES_CONFIGURED"],
            }
        if not query.query and query.brand:
            query = query.model_copy(update={
                "query": " ".join(filter(None, [query.brand, query.category, query.color]))
            })
        if not any([query.gtin, query.model_code, query.query]):
            return {
                "candidates": [],
                "warnings": [*warnings, "INSUFFICIENT_SEARCH_EVIDENCE"],
            }
        # Keep request deadline bounded independently of the number of sources.
        deadline = time.monotonic() + 18
        priority = {
            "supplier": 0,
            "official_brand": 1,
            "authorized_api": 2,
            "public_catalog": 3,
            "manual": 4,
            "html": 5,
        }
        identifiers = sorted(
            set(identifiers),
            key=lambda key: (
                priority.get(self.sources[key].config.source_type, 6)
                if key in self.sources
                else 7
            ),
        )
        for source_id in identifiers:
            adapter = self.sources.get(source_id)
            if adapter is None:
                warnings.append(f"{source_id}:SOURCE_NOT_CONFIGURED")
                continue
            if time.monotonic() > deadline:
                warnings.append("SOURCE_DEADLINE_REACHED")
                break
            try:
                if query.gtin:
                    if not normalized_gtin(query.gtin):
                        warnings.append("INVALID_GTIN")
                        continue
                    rows = adapter.search_by_gtin(query.gtin, deadline=deadline)
                elif query.model_code:
                    rows = adapter.search_by_model_code(
                        query.model_code, query.brand, deadline=deadline
                    )
                else:
                    rows = adapter.search_by_text(query.query or "", deadline=deadline)
                candidates.extend(rows)
            except Exception as exc:
                code = (
                    exc.code
                    if isinstance(exc, FetchError)
                    else "SOURCE_CONFIGURATION_INVALID"
                )
                warnings.append(f"{source_id}:{code}")
                # Never log queries, OCR, URLs with parameters or external body.
                logger.warning(
                    "external_source status=failed source=%s error_code=%s",
                    source_id,
                    code,
                )
        return {
            "candidates": [p.model_dump() for p in rank_products(query, candidates)],
            "warnings": warnings,
        }

    def image(self, request: ImageRequest) -> dict:
        adapter = self.sources.get(request.source_id)
        if adapter is None:
            raise FetchError("SOURCE_NOT_CONFIGURED")
        product = adapter.fetch_product(request.product_url)
        image = next(
            (i for i in product.images if i.original_url == request.original_url), None
        )
        if image is None or image.rights_status == "prohibited":
            raise FetchError("RIGHTS_NOT_APPROVED")
        if request.purpose == "publication" and (
            image.rights_status not in PUBLISHABLE_RIGHTS or not image.rights_evidence
        ):
            raise FetchError("RIGHTS_NOT_APPROVED")
        result = self.fetchers[request.source_id].fetch(
            image.original_url,
            {"image/jpeg", "image/png", "image/webp"},
            max_bytes=10_000_000,
        )
        try:
            with Image.open(BytesIO(result.content)) as decoded:
                if (
                    decoded.format not in ("JPEG", "PNG", "WEBP")
                    or decoded.width * decoded.height > 20_000_000
                ):
                    raise FetchError("INVALID_REMOTE_IMAGE")
                if getattr(decoded, "n_frames", 1) != 1:
                    raise FetchError("INVALID_REMOTE_IMAGE")
                width, height = decoded.size
                mime = Image.MIME[decoded.format]
                decoded.verify()
            if mime != result.content_type:
                raise FetchError("SOURCE_MIME_MISMATCH")
        except (UnidentifiedImageError, OSError, Image.DecompressionBombError):
            raise FetchError("INVALID_REMOTE_IMAGE") from None
        return {
            **image.model_dump(),
            "source": request.source_id,
            "width": width,
            "height": height,
            "fetched_at": datetime.now(UTC).isoformat(),
            "mime": mime,
            "sha256": hashlib.sha256(result.content).hexdigest(),
            "image_base64": base64.b64encode(result.content).decode("ascii"),
        }


@lru_cache(maxsize=2)
def load_service(path: str) -> EnrichmentService:
    """Read only an operator-configured file, never a client-provided path."""
    if not path:
        return EnrichmentService([])
    source = Path(path)
    if source.stat().st_size > 5_000_000:
        raise ValueError("source configuration too large")
    data = json.loads(source.read_text(encoding="utf-8"))
    if not isinstance(data, list) or len(data) > 10:
        raise ValueError("invalid source configuration")
    return EnrichmentService([SourceConfig.model_validate(c) for c in data])
