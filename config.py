from functools import lru_cache
from pathlib import Path
from urllib.parse import quote_plus

from dotenv import load_dotenv
from pydantic import Field
from pydantic_settings import BaseSettings, SettingsConfigDict


def _load_environment_file() -> None:
    project_root = Path(__file__).resolve().parent
    env_file = project_root / ".env"
    if env_file.exists():
        load_dotenv(env_file, override=True)


_load_environment_file()


class Settings(BaseSettings):
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

