import json
import os
from datetime import datetime, timedelta, timezone

CACHE_DIR = "cache"
CACHE_FILE = os.path.join(CACHE_DIR, "product_categories.json")
TTL_HOURS = 24


def _utcnow():
    return datetime.now(timezone.utc)


def load_cache():
    """
    Загружает кэш из файла.
    Возвращает dict с ключами: meta, data
    """
    if not os.path.exists(CACHE_FILE):
        return {
            "meta": {
                "created_at": None,
                "ttl_hours": TTL_HOURS,
            },
            "data": {}
        }

    with open(CACHE_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


def save_cache(cache: dict):
    """
    Сохраняет кэш в файл
    """
    os.makedirs(CACHE_DIR, exist_ok=True)
    with open(CACHE_FILE, "w", encoding="utf-8") as f:
        json.dump(cache, f, ensure_ascii=False, indent=2)


def is_cache_expired(cache: dict) -> bool:
    """
    Проверяет, истёк ли TTL у кэша
    """
    created_at = cache.get("meta", {}).get("created_at")
    if not created_at:
        return True

    created_dt = datetime.fromisoformat(created_at)
    ttl = timedelta(hours=cache.get("meta", {}).get("ttl_hours", TTL_HOURS))

    return _utcnow() - created_dt > ttl


def get_cached_categories(cache: dict) -> dict:
    """
    Возвращает словарь:
    { offer_id: category_id }
    """
    result = {}
    for offer_id, info in cache.get("data", {}).items():
        result[offer_id] = info.get("category_id")
    return result


def update_cache(cache: dict, new_categories: dict):
    """
    new_categories: { offer_id: category_id }
    """
    now = _utcnow().isoformat()

    for offer_id, category_id in new_categories.items():
        cache.setdefault("data", {})[offer_id] = {
            "category_id": category_id,
            "updated_at": now
        }

    cache["meta"]["created_at"] = now
    cache["meta"]["ttl_hours"] = TTL_HOURS
