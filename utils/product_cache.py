import json
import os
from datetime import datetime, timezone, timedelta

CACHE_DIR = "cache"
CACHE_FILE = os.path.join(CACHE_DIR, "product_categories.json")
TTL_HOURS = 24


def _utcnow():
    return datetime.now(timezone.utc)


def load_cache() -> dict:
    """
    Возвращает словарь:
    {
        "meta": {...},
        "data": {
            "offer_id": {
                "category_id": int,
                "updated_at": iso
            }
        }
    }
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
    os.makedirs(CACHE_DIR, exist_ok=True)
    with open(CACHE_FILE, "w", encoding="utf-8") as f:
        json.dump(cache, f, ensure_ascii=False, indent=2)


def is_cache_expired(cache: dict) -> bool:
    created_at = cache.get("meta", {}).get("created_at")
    if not created_at:
        return True

    created_dt = datetime.fromisoformat(created_at)
    ttl = timedelta(hours=cache.get("meta", {}).get("ttl_hours", TTL_HOURS))

    return _utcnow() - created_dt > ttl


def update_category_cache(new_categories: dict):
    """
    new_categories:
    { offer_id (str): category_id (int) }
    """
    cache = load_cache()
    now = _utcnow().isoformat()

    for offer_id, category_id in new_categories.items():
        cache.setdefault("data", {})[str(offer_id)] = {
            "category_id": category_id,
            "updated_at": now
        }

    cache["meta"]["created_at"] = now
    cache["meta"]["ttl_hours"] = TTL_HOURS

    save_cache(cache)


def get_category_id_by_offer_id(offer_id: str):
    cache = load_cache()
    item = cache.get("data", {}).get(str(offer_id))
    if not item:
        print(f"⚠️ Категория не найдена в кэше: {offer_id}")
        return None
    return item.get("category_id")
