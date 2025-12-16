import os
import yaml
import pandas as pd

from dotenv import load_dotenv

from ozon_client import OzonClient
from ozon_product_client import OzonProductClient
from utils.html_report import save_html_report


def load_accounts():
    with open("config/accounts.yaml", "r", encoding="utf-8") as f:
        return yaml.safe_load(f)["accounts"]


def load_category_mapping():
    with open("config/product_categories.yaml", "r", encoding="utf-8") as f:
        cfg = yaml.safe_load(f)
    return cfg.get("categories", {}), cfg.get("default", "Иное")


def main():
    load_dotenv()

    accounts = load_accounts()
    category_map, default_category = load_category_mapping()

    rows = []

    # ===== сообщение об обновлении кэша =====
    account_names = ", ".join(acc["name"] for acc in accounts)
    print(f"Обновление кэша категорий товаров (по аккаунтам): {account_names}")

    for acc in accounts:
        client_id = os.getenv(acc["client_id_env"])
        api_key = os.getenv(acc["api_key_env"])

        if not client_id or not api_key:
            raise RuntimeError(f"Нет ключей для аккаунта {acc['name']}")

        print(f"\n=== Аккаунт: {acc['name']} ===")

        order_client = OzonClient(client_id, api_key)
        product_client = OzonProductClient(client_id, api_key)

        postings = order_client.get_unfulfilled()

        if not postings:
            print("Нет заказов awaiting_packaging")
            continue

        # --- собираем offer_id ТОЛЬКО этого аккаунта ---
        offer_ids = set()
        for p in postings:
            for item in p.get("products", []):
                if item.get("offer_id"):
                    offer_ids.add(str(item["offer_id"]))

        # --- получаем категории (поаккаунтно, с кэшем) ---
        categories_by_offer = {}
        if offer_ids:
            categories_by_offer = product_client.get_categories_by_offer_ids(
                list(offer_ids)
            )

        # --- формируем строки отчёта ---
        for p in postings:
            products = p.get("products", [])

            def format_item(item):
                offer_id = str(item.get("offer_id"))
                category_id = categories_by_offer.get(offer_id)
                category_name = category_map.get(category_id, default_category)
                return f"{offer_id} ({category_name}) x{item.get('quantity')}"

            items_str = ", ".join(format_item(item) for item in products)

            rows.append({
                "account": acc["name"],
                "posting_number": p.get("posting_number"),
                "order_date": p.get("in_process_at"),
                "items": items_str,
            })

    df = pd.DataFrame(rows)

    # ===== ВЫВОД В ТЕРМИНАЛ =====
    if df.empty:
        print("\nНет заказов со статусом awaiting_packaging")
    else:
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



if __name__ == "__main__":
    main()
