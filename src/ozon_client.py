import requests
from typing import List, Dict


class OzonClient:
    BASE_URL = "https://api-seller.ozon.ru"

    def __init__(self, client_id: str, api_key: str):
        self.client_id = client_id
        self.api_key = api_key

        self.headers = {
            "Client-Id": self.client_id,
            "Api-Key": self.api_key,
            "Content-Type": "application/json",
        }

    def get_unfulfilled(
        self,
        cutoff_from: str = "2023-11-01T14:15:22Z",
        status: str = "awaiting_packaging",
        limit: int = 1000,
        with_analytics: bool = True,
        with_financial: bool = True,
    ) -> List[Dict]:
        """
        Получает список невыполненных FBS-заказов OZON.

        cutoff_from — ОБЯЗАТЕЛЬНЫЙ параметр OZON API
        """

        url = f"{self.BASE_URL}/v3/posting/fbs/unfulfilled/list"

        payload = {
            "filter": {
                "cutoff_from": cutoff_from,
                "status": status,
            },
            "limit": limit,
            "with": {
                "analytics_data": with_analytics,
                "financial_data": with_financial,
            },
        }

        response = requests.post(
            url,
            headers=self.headers,
            json=payload,
            timeout=30,
        )

        if not response.ok:
            self._print_error(response)

        response.raise_for_status()

        return response.json().get("result", {}).get("postings", [])

    @staticmethod
    def _print_error(response: requests.Response) -> None:
        print("\n=== OZON API ERROR ===")
        print("HTTP status:", response.status_code)
        try:
            print("Response JSON:", response.json())
        except ValueError:
            print("Response text:", response.text)
        print("=====================\n")
