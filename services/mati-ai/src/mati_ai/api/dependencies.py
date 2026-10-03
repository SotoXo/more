import hmac

from fastapi import Request

from mati_ai.core.errors import PipelineError


def require_service_token(request: Request) -> None:
    expected = request.app.state.settings.token.get_secret_value()
    auth = request.headers.get("authorization", "")
    supplied = auth[7:] if auth.startswith("Bearer ") else ""
    if not expected or not supplied or not hmac.compare_digest(expected, supplied):
        raise PipelineError(
            "UNAUTHORIZED_SERVICE", "Autenticación interna requerida.", 401
        )
