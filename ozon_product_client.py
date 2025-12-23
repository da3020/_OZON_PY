# ozon_product_client.py

import requests


class OzonProductClient:
    def __init__(self, client_id: str, api_key: str):
        self.headers = {
            "Client-Id": client_id,
            "Api-Key": api_key,
            "Content-Type": "application/json",
        }
        self.url = "https://api-seller.ozon.ru/v3/product/info/list"

    def get_products_info_by_offer_ids(self, offer_ids: list[str]) -> dict:
        """
        Возвращает словарь:
        {
            offer_id: { ... данные товара ... }
        }
        """
        if not offer_ids:
            return {}

        result = {}
        BATCH_SIZE = 1000

        for i in range(0, len(offer_ids), BATCH_SIZE):
            batch = offer_ids[i:i + BATCH_SIZE]

            payload = {
                "offer_id": batch
            }

            response = requests.post(
                self.url,
                headers=self.headers,
                json=payload,
                timeout=30
            )

            if not response.ok:
                print("OZON PRODUCT INFO ERROR:", response.text)
                response.raise_for_status()

            data = response.json()
            items = data.get("items", [])

            for item in items:
                offer_id = item.get("offer_id")
                if offer_id:
                    result[offer_id] = item

        return result
