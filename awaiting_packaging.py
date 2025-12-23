import os
import yaml
import json
import pandas as pd
import requests
import uuid

from datetime import datetime, timezone
from dotenv import load_dotenv

from ozon_client import OzonClient
from ozon_product_client import OzonProductClient
from utils.html_report import save_html_report


# -----------------------------
# LOADERS
# -----------------------------
def load_accounts():
    with open("config/accounts.yaml", "r", encoding="utf-8") as f:
        return yaml.safe_load(f)["accounts"]


def load_category_config():
    with open("config/product_categories.yaml", "r", encoding="utf-8") as f:
        cfg = yaml.safe_load(f) or {}

    return cfg.get("categories", {}), cfg.get("default", "Иное")


def map_category_name(category_id, category_map, default_name):
    if category_id is None:
        return default_name
    return category_map.get(int(category_id), default_name)


# -----------------------------
# SERVER COMMUNICATION
# -----------------------------
def send_batch_to_server(batch_endpoint: str, payload: dict):
    try:
        response = requests.post(
            batch_endpoint,
            json=payload,
            timeout=20,
        )
    except Exception as e:
        raise RuntimeError(f"Ошибка соединения с сервером: {e}")

    if response.status_code != 200:
        raise RuntimeError(
            f"Ошибка отправки batch: {response.status_code} {response.text}"
        )

    print("Batch успешно отправлен на сервер:")
    print(response.json())


# -----------------------------
# MAIN
# -----------------------------
def main():
    load_dotenv()

    batch_endpoint = os.getenv("PRODUCTION_BATCH_CREATE_URL")
    if not batch_endpoint:
        raise RuntimeError("Не задан PRODUCTION_BATCH_CREATE_URL в .env")

    accounts = load_accounts()
    category_map, default_category = load_category_config()

    all_rows = []
    batch_items = []

    # batch_id формируется НА СТОРОНЕ PYTHON
    batch_id = (
        datetime.now().strftime("%Y%m%d-%H%M%S")
        + "-"
        + uuid.uuid4().hex[:4]
    )

    batch_created_at = datetime.now(timezone.utc).isoformat()

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

        # -----------------------------
        # COLLECT OFFER IDS
        # -----------------------------
        offer_ids = set()
        for p in postings:
            for item in p.get("products", []):
                if item.get("offer_id"):
                    offer_ids.add(item["offer_id"])

        # -----------------------------
        # LOAD PRODUCT INFO (ICONS)
        # -----------------------------
        product_info = {}
        if offer_ids:
            print(f"Загрузка информации о товарах ({len(offer_ids)})")
            product_info = product_client.get_products_info_by_offer_ids(
                list(offer_ids)
            )

        # -----------------------------
        # PROCESS POSTINGS
        # -----------------------------
        for p in postings:
            products = p.get("products", [])
            order_date = p.get("in_process_at")

            items_str = ", ".join(
                f"{item.get('offer_id')} "
                f"({map_category_name(item.get('description_category_id'), category_map, default_category)}) "
                f"x{item.get('quantity')}"
                for item in products
            )

            all_rows.append(
                {
                    "account": acc["name"],
                    "posting_number": p.get("posting_number"),
                    "order_date": order_date,
                    "items": items_str,
                }
            )

            for item in products:
                offer_id = item.get("offer_id")

                info = product_info.get(offer_id, {})
                if not isinstance(info, dict):
                    info = {}

                image_url = info.get("primary_image")

                category_name = map_category_name(
                    item.get("description_category_id"),
                    category_map,
                    default_category,
                )

                batch_items.append(
                    {
                        "account": acc["name"],
                        "posting_number": p.get("posting_number"),
                        "offer_id": offer_id,
                        "quantity": item.get("quantity", 1),
                        "category": category_name,
                        "image_url": image_url,
                    }
                )

    # -----------------------------
    # DATAFRAME OUTPUT
    # -----------------------------
    df = pd.DataFrame(all_rows)

    print("\n=== Заказы awaiting_packaging ===\n")
    if df.empty:
        print("Нет заказов")
    else:
        print(df.to_string(index=False))
        print("\nВсего заказов:", len(df))

    os.makedirs("reports", exist_ok=True)

    html_path = save_html_report(
        df,
        "reports/unfulfilled.html",
        title="Невыполненные заказы (awaiting_packaging)",
    )

    excel_path = "reports/unfulfilled.xlsx"
    df.to_excel(excel_path, index=False, sheet_name="awaiting_packaging")

    print(f"\nHTML-отчёт сохранён: {html_path}")
    print(f"Excel-отчёт сохранён: {excel_path}")

    # -----------------------------
    # SEND BATCH TO SERVER
    # -----------------------------
    batch_payload = {
        "batch_id": batch_id,
        "batch_created_at": batch_created_at,
        "total_orders": len(df),
        "items": batch_items,
    }

    send_batch_to_server(batch_endpoint, batch_payload)


if __name__ == "__main__":
    main()
