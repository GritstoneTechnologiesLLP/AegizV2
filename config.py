from functools import lru_cache
from pathlib import Path
from typing import Literal
from urllib.parse import quote_plus
import os

from dotenv import load_dotenv
from pydantic import Field
from pydantic_settings import BaseSettings, SettingsConfigDict

_ENV_FILES_LOADED = False


def _load_environment_files() -> None:
    global _ENV_FILES_LOADED
    if _ENV_FILES_LOADED:
        return

    project_root = Path(__file__).resolve().parent
    base_candidates = [
        project_root / ".env",
        project_root / "env" / "common.env",
    ]
    for candidate in base_candidates:
        if candidate.exists():
            load_dotenv(candidate, override=False)

    env_name = os.getenv("ENVIRONMENT", "dev").lower().strip()
    env_candidates = [
        project_root / f".env.{env_name}",
        project_root / "env" / f"{env_name}.env",
    ]
    for candidate in env_candidates:
        if candidate.exists():
            load_dotenv(candidate, override=True)
            break

    _ENV_FILES_LOADED = True


_load_environment_files()


class Settings(BaseSettings):
    environment: Literal["dev", "prod", "test"] = Field("dev", alias="ENVIRONMENT")
    db_host: str = Field("127.0.0.1", alias="DB_HOST")
    db_port: int = Field(3306, alias="DB_PORT")
    db_user: str = Field("root", alias="DB_USER")
    db_password: str = Field("", alias="DB_PASSWORD")
    db_name: str = Field("aegiz", alias="DB_NAME")

    model_config = SettingsConfigDict(env_file=None, env_file_encoding="utf-8", populate_by_name=True)

    @property
    def database_url(self) -> str:
        user = quote_plus(self.db_user)
        password = quote_plus(self.db_password) if self.db_password else ""
        auth = f"{user}:{password}" if password else user
        return f"mysql+mysqlconnector://{auth}@{self.db_host}:{self.db_port}/{self.db_name}"


@lru_cache
def get_settings() -> Settings:
    return Settings()

