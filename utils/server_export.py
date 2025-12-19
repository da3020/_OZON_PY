import requests
from datetime import datetime, timezone


def export_snapshot_to_server(rows, server_url):
    payload = {
        "source": "ozon",
        "created_at": datetime.now(timezone.utc).isoformat(),
        "orders": rows,
    }

    response = requests.post(
        server_url,
        json=payload,
        timeout=30,
    )

    if response.status_code != 200:
        raise RuntimeError(
            f"Ошибка сервера {response.status_code}: {response.text}"
        )

    try:
        return response.json()
    except Exception:
        return response.text
