from datetime import UTC, datetime

from ..contracts import ExternalImage, ExternalProduct, SourceConfig
from ..ranking import normalize, normalized_gtin
from ..safe_fetch import FetchError, checked_url


class ManualCatalogAdapter:
    """A local, operator-curated catalog/feed. Never reads paths from a request."""

    def __init__(self, config: SourceConfig):
        self.config = config
        self.products = [self.normalize_product(row) for row in config.products]

    def normalize_product(self, row: dict) -> ExternalProduct:
        url = str(checked_url(row["canonical_url"], self.config.domains))
        rights = self.config.rights_status
        evidence = self.config.rights_evidence
        images = []
        for entry in row.get("images", [])[:10]:
            entry = {"original_url": entry} if isinstance(entry, str) else entry
            image_url = str(checked_url(entry["original_url"], self.config.domains))
            images.append(
                ExternalImage(
                    source=self.config.id,
                    original_url=image_url,
                    canonical_product_url=url,
                    width=entry.get("width"),
                    height=entry.get("height"),
                    rights_status=rights,
                    rights_evidence=evidence,
                    warning="Uso pendiente de derechos."
                    if rights == "unknown"
                    else None,
                )
            )
        data = {
            **row,
            "source": self.config.name,
            "source_id": self.config.id,
            "source_type": self.config.source_type,
            "canonical_url": url,
            "images": images,
            "rights_status": rights,
            "rights_evidence": evidence,
            "fetched_at": datetime.now(UTC).isoformat(),
        }
        return ExternalProduct.model_validate(data)

    def search_by_gtin(
        self, gtin: str, *, deadline: float | None = None
    ) -> list[ExternalProduct]:
        code = normalized_gtin(gtin)
        return [p for p in self.products if code and normalized_gtin(p.gtin) == code]

    def search_by_model_code(
        self, model_code: str, brand: str | None = None, *, deadline: float | None = None
    ) -> list[ExternalProduct]:
        return [
            p for p in self.products if normalize(p.model_code) == normalize(model_code)
        ]

    def search_by_text(
        self, text: str, *, deadline: float | None = None
    ) -> list[ExternalProduct]:
        tokens = normalize(text).split()
        return [
            p
            for p in self.products
            if tokens
            and any(
                t in normalize(f"{p.title} {p.brand or ''} {p.description or ''}")
                for t in tokens
            )
        ][:20]

    def fetch_product(self, external_id: str) -> ExternalProduct:
        for product in self.products:
            if external_id in (product.external_id, product.canonical_url):
                return product.model_copy(deep=True)
        raise FetchError("SOURCE_PRODUCT_NOT_FOUND")

    def fetch_images(self, external_id: str) -> list[ExternalImage]:
        return self.fetch_product(external_id).images
