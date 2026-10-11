"""ASGI request context and bounded body ingestion before multipart/JSON parsing."""
import logging
import re
import time
import uuid

from starlette.datastructures import Headers, MutableHeaders
from starlette.responses import JSONResponse
from starlette.types import ASGIApp, Message, Receive, Scope, Send

from mati_ai.core.config import Settings

logger = logging.getLogger("mati_ai")


class RequestContextMiddleware:
    def __init__(self, app: ASGIApp, settings: Settings):
        self.app = app
        self.settings = settings

    async def __call__(self, scope: Scope, receive: Receive, send: Send) -> None:
        if scope["type"] != "http":
            await self.app(scope, receive, send)
            return

        state = scope.setdefault("state", {})
        headers = Headers(scope=scope)
        supplied = headers.get("x-request-id", "")
        state["request_id"] = (
            supplied
            if re.fullmatch(r"[A-Za-z0-9._:-]{1,100}", supplied)
            else str(uuid.uuid4())
        )
        started = time.perf_counter()
        response_started = False
        status_code = 500

        async def send_with_context(message: Message) -> None:
            nonlocal response_started, status_code
            if message["type"] == "http.response.start":
                response_started = True
                status_code = message["status"]
                MutableHeaders(scope=message)["X-Request-Id"] = state["request_id"]
            await send(message)

        async def fail(code: str, message: str, status: int, retryable=False) -> None:
            state["error_code"] = code
            response = JSONResponse(
                {
                    "request_id": state["request_id"],
                    "status": "failed",
                    "error": {
                        "code": code,
                        "message": message,
                        "retryable": retryable,
                    },
                },
                status_code=status,
            )
            await response(scope, receive, send_with_context)

        try:
            # Multipart metadata has its own small allowance; the image limit is
            # still enforced by validate_image after extraction.
            maximum = (
                self.settings.text_embedding_max_request_bytes
                if scope["path"] == "/api/v1/rag/embed"
                else self.settings.max_image_bytes + 1_000_000
            )
            lengths = headers.getlist("content-length")
            if lengths and (
                len(lengths) != 1
                or re.fullmatch(r"[0-9]{1,20}", lengths[0]) is None
                or int(lengths[0]) > maximum
            ):
                await fail(
                    "PAYLOAD_TOO_LARGE",
                    "La solicitud supera el tamaño permitido.",
                    413,
                )
                return

            # Count actual ASGI bytes, including chunked requests and requests
            # with a false Content-Length. No parser sees an unbounded body.
            body = bytearray()
            while True:
                message = await receive()
                if message["type"] == "http.disconnect":
                    return
                if message["type"] != "http.request":
                    continue
                chunk = message.get("body", b"")
                if len(body) + len(chunk) > maximum:
                    await fail(
                        "PAYLOAD_TOO_LARGE",
                        "La solicitud supera el tamaño permitido.",
                        413,
                    )
                    return
                body.extend(chunk)
                if not message.get("more_body", False):
                    break

            delivered = False

            async def bounded_receive() -> Message:
                nonlocal delivered
                if not delivered:
                    delivered = True
                    payload = bytes(body)
                    body.clear()
                    return {
                        "type": "http.request",
                        "body": payload,
                        "more_body": False,
                    }
                return await receive()

            await self.app(scope, bounded_receive, send_with_context)
        except Exception:
            if response_started:
                raise
            await fail(
                "INTERNAL_ERROR",
                "No fue posible completar el análisis.",
                500,
                retryable=True,
            )
        finally:
            logger.info(
                "request_id=%s operation=%s duration_ms=%.3f model=%s status=%s error_code=%s",
                state["request_id"],
                scope["path"],
                (time.perf_counter() - started) * 1000,
                state.get("model_id", self.settings.model_id),
                status_code,
                state.get("error_code", "none"),
            )
