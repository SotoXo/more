from typing import Literal

from pydantic import Field, SecretStr
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_prefix="MATI_AI_", env_file=".env", extra="ignore"
    )
    token: SecretStr = SecretStr("")
    model_provider: Literal["siglip", "clip"] = "siglip"
    vision_model: str | None = None
    model_revision: str = "main"
    device: Literal["auto", "cpu", "cuda"] = "auto"
    local_files_only: bool = True
    max_image_bytes: int = Field(default=10 * 1024 * 1024, ge=1024, le=30 * 1024 * 1024)
    max_pixels: int = Field(default=24_000_000, ge=10000, le=40_000_000)
    max_dimension: int = 12000
    inference_lock_timeout: float = 2.0
    model_retry_seconds: float = 60.0
    ocr_enabled: bool = True
    ocr_download_enabled: bool = False
    ocr_model_directory: str | None = None
    external_sources_file: str | None = None
    text_embedding_enabled: bool = False
    text_embedding_profile_path: str = "/app/resources/rag/model-profile.json"
    text_embedding_cache_dir: str = "/cache/text-embeddings"
    text_embedding_timeout_seconds: float = Field(default=30.0, ge=1, le=120)
    text_embedding_retry_seconds: float = Field(default=60.0, ge=1, le=600)
    text_embedding_max_request_bytes: int = Field(default=65536, ge=1024, le=65536)

    @property
    def model_id(self) -> str:
        return (
            self.vision_model
            or {
                "siglip": "google/siglip-base-patch16-224",
                "clip": "openai/clip-vit-base-patch32",
            }[self.model_provider]
        )
