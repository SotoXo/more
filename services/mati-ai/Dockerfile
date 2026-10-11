# Same Python/CUDA package lock as development; GPU is optional at runtime.
FROM python:3.11-slim
COPY --from=ghcr.io/astral-sh/uv:0.12.1 /uv /uvx /bin/
WORKDIR /app
ENV UV_COMPILE_BYTECODE=1 \
    UV_LINK_MODE=copy \
    HF_HOME=/cache/huggingface \
    MATI_AI_OCR_MODEL_DIRECTORY=/cache/easyocr \
    MATI_AI_LOCAL_FILES_ONLY=true \
    MATI_AI_OCR_DOWNLOAD_ENABLED=false \
    PYTHONUNBUFFERED=1
RUN apt-get update && apt-get install -y --no-install-recommends libgomp1 ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && useradd --create-home --uid 10001 mati \
    && mkdir -p /cache/huggingface /cache/easyocr \
    && chown -R mati:mati /cache
COPY pyproject.toml uv.lock README.md ./
RUN uv sync --frozen --no-dev --no-install-project
COPY src ./src
COPY resources ./resources
COPY config ./config
COPY scripts ./scripts
RUN uv sync --frozen --no-dev
USER mati
EXPOSE 8000
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s CMD ["/app/.venv/bin/python", "-c", "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8000/health', timeout=3)"]
CMD ["/app/.venv/bin/uvicorn", "mati_ai.main:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "1", "--no-access-log"]
