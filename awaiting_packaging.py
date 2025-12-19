import os
import yaml
import json
import pandas as pd
import requests

from datetime import datetime
from zoneinfo import ZoneInfo
from dotenv import load_dotenv

from ozon_client import OzonClient
from ozon_product_client import OzonProductClient
from utils.html_report import save_html_report
from utils.category_mapper import map_category_name


# -----------------------------
# CONFIG
# -----------------------------

BATCH_API_URL = "https://ozon.chdesign.ru/api/ozon/production_batch_create.php"
PROJECT_VERSION = "6.3.3"


# -----------------------------
# HELPERS
# -----------------------------

def load_accounts():
    with open("config/accounts.yaml", "r", encoding="utf-8") as f:
        return yaml.safe_load(f)["accounts"]


def send_production_batch(payload: dict) -> dict:
    r = requests.post(
        BATCH_API_URL,
        json=payload,
        timeout=20
    )
    r.raise_for_status()
    return r.json()


# -----------------------------
# MAIN
# -----------------------------

def main():
    load_dotenv()

    accounts = load_accounts()
    rows = []
    orders_payload = []

    print("\nОбновление кэша категорий товаров...\n")

    for acc in accounts:
        print(f"=== Аккаунт: {acc['name']} ===")

        client_id = os.getenv(acc["client_id_env"])
        api_key = os.getenv(acc["api_key_env"])

        if not client_id or not api_key:
            raise RuntimeError(f"Нет ключей для аккаунта {acc['name']}")

        client = OzonClient(client_id, api_key)
        product_client = OzonProductClient(client_id, api_key)

        postings = client.get_unfulfilled()

        # собираем offer_id для запроса категорий
        offer_ids = set()
        for p in postings:
            for item in p.get("products", []):
                if item.get("offer_id"):
                    offer_ids.add(item["offer_id"])

        # обновляем кэш категорий ДЛЯ ЭТОГО АККАУНТА
        categories = product_client.get_categories_by_offer_ids(list(offer_ids))

        for p in postings:
            products = p.get("products", [])
            items = []

            for item in products:
                offer_id = item.get("offer_id")
                qty = item.get("quantity", 0)

                category_id = categories.get(offer_id)
                category_name = map_category_name(category_id)

                items.append({
                    "offer_id": offer_id,
                    "qty": qty,
                    "category": category_name
                })

            # строка для таблицы
            items_str = ", ".join(
                f"{i['offer_id']} ({i['category']}) x{i['qty']}"
                for i in items
            )

            rows.append({
                "account": acc["name"],
                "posting_number": p.get("posting_number"),
                "order_date": p.get("in_process_at"),
                "items": items_str
            })

            # структура для production batch
            orders_payload.append({
                "account": acc["name"],
                "posting_number": p.get("posting_number"),
                "order_date": p.get("in_process_at"),
                "items": items
            })

        print(f"Заказов найдено: {len(postings)}\n")

    df = pd.DataFrame(rows)

    # ===== ВЫВОД В ТЕРМИНАЛ =====
    if df.empty:
        print("Нет заказов со статусом awaiting_packaging")
        return

    print("\n=== Невыполненные заказы (awaiting_packaging) ===\n")
    print(df.to_string(index=False))
    print("\nВсего заказов:", len(df))

    # ===== HTML =====
    os.makedirs("reports", exist_ok=True)
    html_path = save_html_report(
        df,
        "reports/unfulfilled.html",
        title="Невыполненные заказы (все аккаунты)"
    )
    print(f"\nHTML-отчёт сохранён: {html_path}")

    # ===== EXCEL =====
    excel_path = "reports/unfulfilled.xlsx"
    df.to_excel(
        excel_path,
        index=False,
        sheet_name="awaiting_packaging"
    )
    print(f"Excel-отчёт сохранён: {excel_path}")

    # ===== PRODUCTION BATCH =====
    batch_payload = {
        "meta": {
            "source": "ozon_py",
            "version": PROJECT_VERSION,
            "created_at": datetime.now(
                ZoneInfo("Europe/Moscow")
            ).isoformat()
        },
        "accounts": sorted(df["account"].unique().tolist()),
        "orders": orders_payload,
        "summary": {
            "orders_total": len(orders_payload),
            "items_total": sum(
                item["qty"]
                for o in orders_payload
                for item in o["items"]
            )
        }
    }

    print("\nОтправка производственного наряда на сервер...")
    response = send_production_batch(batch_payload)
    print("Сервер ответил:", response)


if __name__ == "__main__":
    main()
