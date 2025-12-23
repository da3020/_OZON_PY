<?php
$ROOT = dirname(__DIR__, 1);
$ITEMS_DIR = $ROOT . '/data/items';

$items = [];

foreach (glob($ITEMS_DIR . '/*.json') as $file) {
    $items[] = json_decode(file_get_contents($file), true);
}

usort($items, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Производство</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f6f6;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        th {
            background: #eee;
        }

        .status-new {
            background: #eef;
        }

        .status-in_work {
            background: #fff3cd;
        }

        .status-ready {
            background: #d4edda;
        }

        .status-delayed {
            background: #f8d7da;
        }

        .status-done {
            background: #e2e3e5;
        }

        button {
            margin: 2px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <h2>Производство — Items</h2>

    <table>
        <thead>
            <tr>
                <th>Offer</th>
                <th>Категория</th>
                <th>Кол-во</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($items as $item): ?>
                <tr class="status-<?= htmlspecialchars($item['status']) ?>">
                    <td><?= htmlspecialchars($item['offer_id']) ?></td>
                    <td><?= htmlspecialchars($item['category']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td class="status-cell"><?= htmlspecialchars($item['status']) ?></td>
                    <td>
                        <?php foreach (['in_work', 'ready', 'delayed', 'done'] as $st): ?>
                            <button onclick="updateStatus('<?= $item['item_id'] ?>','<?= $st ?>', this)">
                                <?= $st ?>
                            </button>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        function updateStatus(itemId, status, btn) {
            fetch('./production_item_update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        status: status
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'ok') {
                        alert('Ошибка: ' + (data.message || 'unknown'));
                        return;
                    }

                    const row = btn.closest('tr');
                    row.className = 'status-' + status;
                    row.querySelector('.status-cell').innerText = status;
                })
                .catch(err => {
                    alert('Ошибка запроса: ' + err);
                });
        }
    </script>

</body>

</html>