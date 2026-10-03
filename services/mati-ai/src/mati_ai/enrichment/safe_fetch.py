"""HTTPS allowlist, DNS pinning, bounded redirects/streams, robots and source pacing.

Connections use the validated IP while preserving Host and TLS SNI. This closes
the DNS validation/connection race; merely resolving then fetching a hostname
would remain vulnerable to DNS rebinding. No ambient proxies, cookies or auth.
"""

import ipaddress
import socket
import threading
import time
from dataclasses import dataclass
from urllib.parse import urljoin, urlsplit
from urllib.robotparser import RobotFileParser

import httpx

from .contracts import SourceConfig

USER_AGENT = "MatiBoutiqueCatalogBot/1.0"


class FetchError(Exception):
    def __init__(self, code: str):
        self.code = code
        super().__init__(code)


@dataclass(frozen=True)
class Fetched:
    content: bytes
    url: str
    content_type: str


def checked_url(url: str, domains: list[str]) -> httpx.URL:
    try:
        parts = urlsplit(url)
        candidate = httpx.URL(url)
        host = candidate.host.lower()
        if (
            parts.scheme != "https"
            or not host
            or candidate.port not in (None, 443)
            or parts.username is not None
            or parts.password is not None
            or parts.fragment
            or "\\" in url
            or any(ord(c) < 32 for c in url)
            or len(url) > 2048
            or host not in {d.lower() for d in domains}
        ):
            raise ValueError
        if host == "localhost" or host.endswith((".localhost", ".local", ".internal")):
            raise ValueError
        try:
            address = ipaddress.ip_address(host)
        except ValueError:
            address = None
        if address and not address.is_global:
            raise ValueError
        return candidate
    except (ValueError, httpx.InvalidURL):
        raise FetchError("UNSAFE_REMOTE_URL") from None


def resolve_public(host: str, port: int) -> list[str]:
    try:
        addresses = sorted(
            {
                row[4][0]
                for row in socket.getaddrinfo(host, port, type=socket.SOCK_STREAM)
            }
        )
    except OSError:
        raise FetchError("SOURCE_DNS_FAILED") from None
    if not addresses:
        raise FetchError("SOURCE_DNS_FAILED")
    for raw in addresses:
        address = ipaddress.ip_address(raw)
        # Reject all non-public answers and transition/mapped formats, including
        # mixed public/private DNS answers and metadata addresses.
        if (
            not address.is_global
            or address.is_multicast
            or (
                isinstance(address, ipaddress.IPv6Address)
                and (address.ipv4_mapped or address.sixtofour or address.teredo)
            )
        ):
            raise FetchError("UNSAFE_REMOTE_URL")
    return addresses


class SafeFetcher:
    def __init__(
        self, config: SourceConfig, transport: httpx.BaseTransport | None = None
    ):
        self.config = config
        self.transport = (
            transport  # Test injection only; never controlled by API payload.
        )
        self._lock = threading.Lock()
        self._next_request = 0.0
        self._robots: dict[str, tuple[float, RobotFileParser]] = {}

    def _pace(self, deadline: float) -> None:
        with self._lock:
            delay = max(0.0, self._next_request - time.monotonic())
            if time.monotonic() + delay >= deadline:
                raise FetchError("SOURCE_RATE_LIMITED")
            self._next_request = (
                time.monotonic() + delay + self.config.min_interval_seconds
            )
        if delay:
            time.sleep(delay)

    def _robots_allowed(self, url: str, deadline: float) -> None:
        parsed = checked_url(url, self.config.domains)
        origin = f"https://{parsed.host}"
        cached = self._robots.get(origin)
        if not cached or cached[0] < time.monotonic():
            result = self._fetch(
                origin + "/robots.txt", {"text/plain"}, deadline, False, 256_000
            )
            parser = RobotFileParser()
            parser.parse(result.content.decode("utf-8", errors="replace").splitlines())
            cached = (time.monotonic() + 600, parser)
            self._robots[origin] = cached
        parser = cached[1]
        if not parser.can_fetch(USER_AGENT, url):
            raise FetchError("SOURCE_ROBOTS_BLOCKED")
        delay = parser.crawl_delay(USER_AGENT)
        rate = parser.request_rate(USER_AGENT)
        required = max(
            float(delay or 0),
            rate.seconds / rate.requests if rate and rate.requests else 0,
        )
        if required > self.config.min_interval_seconds:
            # Don't disregard stricter server pacing. Configure it explicitly.
            raise FetchError("SOURCE_ROBOTS_RATE_LIMIT")

    def fetch(
        self,
        url: str,
        content_types: set[str],
        *,
        deadline: float | None = None,
        max_bytes: int | None = None,
    ) -> Fetched:
        if not (
            self.config.enabled
            and self.config.terms_approved
            and self.config.terms_url
            and self.config.reviewed_at
        ):
            raise FetchError("SOURCE_TERMS_BLOCKED")
        return self._fetch(
            url,
            content_types,
            deadline or time.monotonic() + 15,
            True,
            max_bytes or self.config.max_bytes,
        )

    def _fetch(
        self, url: str, types: set[str], deadline: float, robots: bool, maximum: int
    ) -> Fetched:
        for redirect in range(4):
            parsed = checked_url(url, self.config.domains)
            if robots:
                self._robots_allowed(url, deadline)
            addresses = resolve_public(parsed.host, parsed.port or 443)
            self._pace(deadline)
            remaining = min(self.config.timeout_seconds, deadline - time.monotonic())
            if remaining <= 0:
                raise FetchError("SOURCE_TIMEOUT")
            # One new client per request: cookies and pooled TLS connections are
            # never reused across origins sharing an IP address.
            pinned = parsed.copy_with(host=addresses[0])
            try:
                with (
                    httpx.Client(
                        transport=self.transport,
                        trust_env=False,
                        follow_redirects=False,
                        timeout=httpx.Timeout(remaining),
                        headers={
                            "User-Agent": USER_AGENT,
                            "Host": parsed.host,
                            "Accept-Encoding": "identity",
                        },
                    ) as client,
                    client.stream(
                        "GET", pinned, extensions={"sni_hostname": parsed.host}
                    ) as response,
                ):
                    if response.status_code in (301, 302, 303, 307, 308):
                        if redirect == 3 or not response.headers.get("location"):
                            raise FetchError("SOURCE_REDIRECT_LIMIT")
                        url = urljoin(url, response.headers["location"])
                        continue
                    if response.status_code == 429:
                        # Persist a cooldown; do not retry or evade.
                        try:
                            cooldown = min(
                                3600,
                                max(
                                    60,
                                    int(response.headers.get("retry-after", "60")),
                                ),
                            )
                        except ValueError:
                            cooldown = 60
                        with self._lock:
                            self._next_request = max(
                                self._next_request, time.monotonic() + cooldown
                            )
                        raise FetchError("SOURCE_RATE_LIMITED")
                    if response.status_code != 200:
                        raise FetchError("SOURCE_UNAVAILABLE")
                    content_type = (
                        response.headers.get("content-type", "")
                        .split(";")[0]
                        .strip()
                        .lower()
                    )
                    if content_type not in types:
                        raise FetchError("SOURCE_CONTENT_TYPE")
                    encoding = response.headers.get(
                        "content-encoding", "identity"
                    ).lower()
                    if encoding not in ("", "identity"):
                        raise FetchError("SOURCE_CONTENT_ENCODING")
                    length = response.headers.get("content-length")
                    if length and (not length.isdigit() or int(length) > maximum):
                        raise FetchError("SOURCE_TOO_LARGE")
                    chunks = bytearray()
                    for chunk in response.iter_bytes(chunk_size=16384):
                        if time.monotonic() > deadline:
                            raise FetchError("SOURCE_TIMEOUT")
                        chunks.extend(chunk)
                        if len(chunks) > maximum:
                            raise FetchError("SOURCE_TOO_LARGE")
                    return Fetched(bytes(chunks), url, content_type)
            except httpx.TimeoutException:
                raise FetchError("SOURCE_TIMEOUT") from None
            except httpx.HTTPError:
                raise FetchError("SOURCE_UNAVAILABLE") from None
        raise FetchError("SOURCE_REDIRECT_LIMIT")
