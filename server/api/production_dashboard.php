<?php
// production_dashboard.php

$ROOT = __DIR__;

$ITEMS_DIR = $ROOT . '/data/items';

$items = [];

if (is_dir($ITEMS_DIR)) {
    foreach (glob($ITEMS_DIR . '/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $items[] = $data;
        }
    }
}

// сортировка по времени создания
usort($items, function ($a, $b) {
    return strcmp($a['created_at'] ?? '', $b['created_at'] ?? '');
});
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Production dashboard</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}

th, td {
    padding: 8px;
    border-bottom: 1px solid #ddd;
    vertical-align: middle;
    text-align: left;
}

th {
    background: #eee;
}

.item-image img {
    width: 48px;
    height: 48px;
    object-fit: contain;
    border-radius: 4px;
    background: #fff;
}

.status {
    font-weight: bold;
}

.status-new { color: #444; }
.status-printing { color: #0066cc; }
.status-ready { color: #009933; }
.status-hold { color: #cc0000; }

.actions button {
    margin-right: 4px;
    padding: 4px 8px;
    cursor: pointer;
}
</style>
</head>

<body>

<h1>Производство</h1>

<table>
<thead>
<tr>
    <th>Иконка</th>
    <th>Аккаунт</th>
    <th>Posting</th>
    <th>Offer</th>
    <th>Категория</th>
    <th>Кол-во</th>
    <th>Статус</th>
    <th>Действия</th>
</tr>
</thead>

<tbody>
<?php foreach ($items as $item): 
    $itemId = $item['item_id'];
    $status = $item['status'] ?? 'new';
?>
<tr data-item="<?= htmlspecialchars($itemId) ?>">
    <td class="item-image">
        <?php if (!empty($item['image_url'])): ?>
            <img src="<?= htmlspecialchars($item['image_url']) ?>" loading="lazy">
        <?php endif; ?>
    </td>

    <td><?= htmlspecialchars($item['account'] ?? '') ?></td>
    <td><?= htmlspecialchars($item['posting_number'] ?? '') ?></td>
    <td><?= htmlspecialchars($item['offer_id'] ?? '') ?></td>
    <td><?= htmlspecialchars($item['category'] ?? '') ?></td>
    <td><?= htmlspecialchars($item['quantity'] ?? 1) ?></td>

    <td class="status status-<?= htmlspecialchars($status) ?>">
        <?= htmlspecialchars($status) ?>
    </td>

    <td class="actions">
        <button onclick="updateStatus('<?= $itemId ?>','printing')">🖨 Печать</button>
        <button onclick="updateStatus('<?= $itemId ?>','ready')">✅ Готово</button>
        <button onclick="updateStatus('<?= $itemId ?>','hold')">⏸ Пауза</button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<script>
function updateStatus(itemId, status) {
    fetch('/api/production_item_update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            item_id: itemId,
            status: status
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status !== 'ok') {
            alert(data.message || 'Ошибка');
            return;
        }

        const row = document.querySelector('[data-item="'+itemId+'"]');
        const statusCell = row.querySelector('.status');

        statusCell.textContent = status;
        statusCell.className = 'status status-' + status;
    })
    .catch(err => {
        alert('Ошибка связи с сервером');
        console.error(err);
    });
}
</script>

</body>
</html>
