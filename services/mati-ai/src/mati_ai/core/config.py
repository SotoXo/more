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

    @property
    def model_id(self) -> str:
        return (
            self.vision_model
            or {
                "siglip": "google/siglip-base-patch16-224",
                "clip": "openai/clip-vit-base-patch32",
            }[self.model_provider]
        )
