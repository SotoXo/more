from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from starlette.exceptions import HTTPException

from mati_ai.api.v1.routes import router
from mati_ai.core.config import Settings
from mati_ai.core.errors import PipelineError
from mati_ai.core.http import RequestContextMiddleware
from mati_ai.core.model_manager import ModelManager
from mati_ai.enrichment.api import router as enrichment_router
from mati_ai.identification.ocr import EasyOCRProvider


def create_app(settings: Settings | None = None) -> FastAPI:
    app = FastAPI(
        title="Mati AI",
        version="1.0.0",
        docs_url=None,
        redoc_url=None,
        openapi_url=None,
    )
    app.state.settings = settings or Settings()
    app.state.model_manager = ModelManager(app.state.settings)
    app.state.ocr_provider = EasyOCRProvider(app.state.settings)

    app.add_middleware(RequestContextMiddleware, settings=app.state.settings)

    @app.exception_handler(PipelineError)
    async def pipeline_error(request: Request, exc: PipelineError):
        request.state.error_code = exc.code
        return JSONResponse(
            {
                "request_id": request.state.request_id,
                "status": "failed",
                "error": {
                    "code": exc.code,
                    "message": exc.message,
                    "retryable": exc.retryable,
                },
            },
            status_code=exc.status_code,
        )

    @app.exception_handler(RequestValidationError)
    async def validation_error(request: Request, exc: RequestValidationError):
        request.state.error_code = "INVALID_REQUEST"
        return JSONResponse(
            {
                "request_id": request.state.request_id,
                "status": "failed",
                "error": {
                    "code": "INVALID_REQUEST",
                    "message": "Los campos enviados no cumplen el contrato.",
                    "retryable": False,
                },
            },
            status_code=422,
        )

    @app.exception_handler(HTTPException)
    async def http_error(request: Request, exc: HTTPException):
        request.state.error_code = "INVALID_REQUEST"
        return JSONResponse(
            {
                "request_id": request.state.request_id,
                "status": "failed",
                "error": {
                    "code": "INVALID_REQUEST",
                    "message": "Solicitud no válida.",
                    "retryable": False,
                },
            },
            status_code=exc.status_code,
        )

    @app.get("/health")
    def health():
        return {"status": "ok", "service": "mati-ai", "contract_version": "1"}

    app.include_router(router)
    app.include_router(enrichment_router)
    return app


app = create_app()


def serve():
    import uvicorn

    uvicorn.run("mati_ai.main:app", host="0.0.0.0", port=8000, workers=1)
