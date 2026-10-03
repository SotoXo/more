from typing import Protocol

from .contracts import ExternalImage, ExternalProduct


class ExternalProductSource(Protocol):
    def search_by_gtin(
        self, gtin: str, *, deadline: float | None = None
    ) -> list[ExternalProduct]: ...

    def search_by_model_code(
        self,
        model_code: str,
        brand: str | None = None,
        *,
        deadline: float | None = None,
    ) -> list[ExternalProduct]: ...

    def search_by_text(
        self, text: str, *, deadline: float | None = None
    ) -> list[ExternalProduct]: ...

    def fetch_product(self, external_id: str) -> ExternalProduct: ...

    def fetch_images(self, external_id: str) -> list[ExternalImage]: ...
