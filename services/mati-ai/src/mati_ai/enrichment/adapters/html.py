"""Small domain-specific HTML adapter. Selectors and URLs are config, never input."""

import time
from datetime import UTC, datetime
from urllib.parse import quote, urljoin

from bs4 import BeautifulSoup

from ..contracts import ExternalProduct, SourceConfig
from ..safe_fetch import FetchError, SafeFetcher, checked_url
from .manual import ManualCatalogAdapter


class HtmlCatalogAdapter(ManualCatalogAdapter):
    def __init__(self, config: SourceConfig, fetcher: SafeFetcher):
        super().__init__(config)
        self.fetcher = fetcher

    def _search(self, value: str, deadline: float | None = None) -> list[ExternalProduct]:
        if not self.config.search_url or "{query}" not in self.config.search_url:
            raise FetchError("SOURCE_CONFIGURATION_INVALID")
        source_deadline = time.monotonic() + 12
        deadline = min(deadline, source_deadline) if deadline is not None else source_deadline
        url = self.config.search_url.replace("{query}", quote(value, safe=""))
        fetched = self.fetcher.fetch(
            url, {"text/html", "application/xhtml+xml"}, deadline=deadline
        )
        soup = BeautifulSoup(fetched.content, "html.parser")
        self._reject_challenge(soup)
        selector = self.config.selectors.get("product_links")
        if not selector:
            raise FetchError("SOURCE_CONFIGURATION_INVALID")
        products = []
        seen = set()
        for link in soup.select(selector):
            if not link.get("href"):
                continue
            target = urljoin(fetched.url, str(link["href"]))
            if target in seen:
                continue
            seen.add(target)
            checked_url(target, self.config.domains)
            products.append(self._fetch_product(target, deadline))
            if len(products) >= self.config.max_pages:
                break
        return products

    @staticmethod
    def _reject_challenge(soup: BeautifulSoup) -> None:
        title = soup.title.get_text(" ", strip=True).casefold() if soup.title else ""
        if any(
            t in title
            for t in ("captcha", "just a moment", "access denied", "sign in", "login")
        ) or soup.select_one(
            'input[type="password"], iframe[src*="captcha"], .g-recaptcha'
        ):
            raise FetchError("SOURCE_ACCESS_BLOCKED")

    def search_by_gtin(
        self, gtin: str, *, deadline: float | None = None
    ) -> list[ExternalProduct]:
        return self._search(gtin, deadline)

    def search_by_model_code(
        self, model_code: str, brand: str | None = None, *, deadline: float | None = None
    ) -> list[ExternalProduct]:
        return self._search(" ".join(filter(None, [brand, model_code])), deadline)

    def search_by_text(
        self, text: str, *, deadline: float | None = None
    ) -> list[ExternalProduct]:
        return self._search(text, deadline)

    def fetch_product(self, external_id: str) -> ExternalProduct:
        return self._fetch_product(external_id, time.monotonic() + 12)

    def _fetch_product(self, url: str, deadline: float) -> ExternalProduct:
        fetched = self.fetcher.fetch(
            url, {"text/html", "application/xhtml+xml"}, deadline=deadline
        )
        soup = BeautifulSoup(fetched.content, "html.parser")
        self._reject_challenge(soup)
        # Remove active/non-product text before extracting descriptions.
        for element in soup(["script", "style", "noscript"]):
            element.decompose()

        def value(name: str) -> str | None:
            selector = self.config.selectors.get(name)
            node = soup.select_one(selector) if selector else None
            if node is None:
                return None
            raw = str(
                node.get("content")
                or node.get("value")
                or node.get("href")
                or node.get_text(" ", strip=True)
            ).strip()
            return raw[: 10000 if name == "description" else 500] or None

        title = value("title")
        if not title:
            raise FetchError("SOURCE_PARSER_CHANGED")
        canonical_selector = self.config.selectors.get(
            "canonical", 'link[rel="canonical"]'
        )
        canonical = soup.select_one(canonical_selector)
        canonical_url = (
            urljoin(fetched.url, str(canonical.get("href")))
            if canonical and canonical.get("href")
            else fetched.url
        )
        checked_url(canonical_url, self.config.domains)
        images = []
        image_selector = self.config.selectors.get("images")
        for node in soup.select(image_selector)[:10] if image_selector else []:
            original = node.get("src") or node.get("content") or node.get("data-src")
            if original:
                image_url = urljoin(fetched.url, str(original))
                checked_url(image_url, self.config.domains)
                images.append(image_url)
        row = {
            name: value(name)
            for name in (
                "brand",
                "model_code",
                "gtin",
                "description",
                "category",
                "color",
                "price",
                "currency",
                "availability",
            )
        }
        return self.normalize_product(
            {
                **row,
                "title": title,
                "canonical_url": canonical_url,
                "external_id": canonical_url,
                "images": images,
                "fetched_at": datetime.now(UTC).isoformat(),
            }
        )
