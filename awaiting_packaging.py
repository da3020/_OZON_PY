import os
from pathlib import Path
import yaml
import pandas as pd

from dotenv import load_dotenv

from ozon_client import OzonClient
from ozon_product_client import OzonProductClient
from utils.html_report import save_html_report


# -----------------------------
# BASE DIR + ENV
# -----------------------------
BASE_DIR = Path(__file__).resolve().parent
load_dotenv(BASE_DIR / ".env")


# -----------------------------
# Загрузка аккаунтов
# -----------------------------
def load_accounts():
    with open(BASE_DIR / "config/accounts.yaml", "r", encoding="utf-8") as f:
        return yaml.safe_load(f)["accounts"]


# -----------------------------
# Загрузка маппинга категорий
# -----------------------------
def load_category_config():
    with open(BASE_DIR / "config/product_categories.yaml", encoding="utf-8") as f:
        cfg = yaml.safe_load(f) or {}

    return (
        cfg.get("categories", {}),
        cfg.get("default", "Иное"),
    )


def map_category_name(category_id, category_map, default_name):
    if category_id is None:
        return default_name
    return category_map.get(int(category_id), default_name)


# -----------------------------
# MAIN
# -----------------------------
def main():
    accounts = load_accounts()
    category_map, default_category = load_category_config()

    rows = []

    for acc in accounts:
        print(f"\n=== Аккаунт: {acc['name']} ===")

        client_id = os.getenv(acc["client_id_env"])
        api_key = os.getenv(acc["api_key_env"])

        if not client_id or not api_key:
            raise RuntimeError(f"Нет ключей для аккаунта {acc['name']}")

        ozon_client = OzonClient(client_id, api_key)
        product_client = OzonProductClient(client_id, api_key)

        postings = ozon_client.get_unfulfilled()
        print(f"Получено заказов: {len(postings)}")

        # собираем offer_id
        offer_ids = set()
        for p in postings:
            for item in p.get("products", []):
                if item.get("offer_id"):
                    offer_ids.add(item["offer_id"])

        if offer_ids:
            print(
                f"Обновление кэша категорий "
                f"({len(offer_ids)} товаров)"
            )
            categories = product_client.get_categories_by_offer_ids(list(offer_ids))
        else:
            categories = {}

        for p in postings:
            products = p.get("products", [])

            items_str = ", ".join(
                f"{item.get('offer_id')} "
                f"({map_category_name(categories.get(item.get('offer_id')), category_map, default_category)}) "
                f"x{item.get('quantity')}"
                for item in products
            )

            rows.append({
                "account": acc["name"],
                "posting_number": p.get("posting_number"),
                "order_date": p.get("in_process_at"),
                "items": items_str,
            })

    df = pd.DataFrame(rows)

    # ===== ВЫВОД В ТЕРМИНАЛ =====
    if df.empty:
        print("\nНет заказов awaiting_packaging")
    else:
        print("\n=== Заказы awaiting_packaging ===\n")
        print(df.to_string(index=False))
        print("\nВсего заказов:", len(df))

    # ===== HTML =====
    reports_dir = BASE_DIR / "reports"
    reports_dir.mkdir(exist_ok=True)

    html_path = save_html_report(
        df,
        reports_dir / "unfulfilled.html",
        title="Невыполненные заказы (awaiting_packaging)"
    )

    print(f"\nHTML-отчёт сохранён: {html_path}")

    # ===== EXCEL =====
    excel_path = reports_dir / "unfulfilled.xlsx"
    df.to_excel(excel_path, index=False, sheet_name="awaiting_packaging")
    print(f"Excel-отчёт сохранён: {excel_path}")


if __name__ == "__main__":
    main()
