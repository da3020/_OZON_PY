<?php
// /api/production_dashboard.php

$ROOT = dirname(__DIR__, 1);

$ITEMS_DIR = $ROOT . '/data/items';

$items = [];

if (is_dir($ITEMS_DIR)) {
    $dir = new DirectoryIterator($ITEMS_DIR);
    foreach ($dir as $file) {
        if ($file->isFile() && $file->getExtension() === 'json') {
            $data = json_decode(file_get_contents($file->getPathname()), true);
            if (is_array($data)) {
                $items[] = $data;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Производство</title>

    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            vertical-align: middle;
        }

        th {
            background: #f5f5f5;
        }

        img.icon {
            width: 48px;
            height: 48px;
            object-fit: contain;
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

        button {
            margin-right: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <h1>Производство</h1>

    <table>
        <tr>
            <th>Иконка</th>
            <th>Артикул</th>
            <th>Категория</th>
            <th>Статус</th>
            <th>Действия</th>
        </tr>

        <?php if (empty($items)): ?>
            <tr>
                <td colspan="5">Нет элементов</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($items as $item): ?>
            <tr data-item-id="<?= htmlspecialchars($item['item_id']) ?>">
                <td>
                    <?php if (!empty($item['image_url'])): ?>
                        <img class="icon" src="<?= htmlspecialchars($item['image_url']) ?>">
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($item['offer_id'] ?? '') ?></td>
                <td><?= htmlspecialchars($item['category'] ?? '') ?></td>
                <td class="status status-<?= htmlspecialchars($item['status']) ?>">
                    <?= htmlspecialchars($item['status']) ?>
                </td>
                <td>
                    <button onclick="setStatus('<?= $item['item_id'] ?>','in_work')">В работу</button>
                    <button onclick="setStatus('<?= $item['item_id'] ?>','ready')">Готов</button>
                    <button onclick="setStatus('<?= $item['item_id'] ?>','delayed')">Отложить</button>
                </td>
            </tr>
        <?php endforeach; ?>

    </table>

    <script>
        function setStatus(itemId, status) {
            fetch('/api/production_item_update.php', {
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
                .then(res => {
                    if (res.status !== 'ok') {
                        alert('Ошибка');
                        return;
                    }

                    const row = document.querySelector(
                        'tr[data-item-id="' + itemId + '"]'
                    );
                    const cell = row.querySelector('.status');

                    cell.textContent = status;
                    cell.className = 'status status-' + status;
                });
        }
    </script>

</body>

</html>