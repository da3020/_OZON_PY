<?php
$ROOT = dirname(__DIR__, 1);
$ITEMS_DIR = $ROOT . '/data/items';

$items = [];
foreach (glob($ITEMS_DIR . '/*.json') as $file) {
    $item = json_decode(file_get_contents($file), true);
    $items[] = $item;
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Production dashboard</title>
<style>
body { font-family: Arial; }
table { border-collapse: collapse; width: 100%; }
th, td { border:1px solid #ccc; padding:6px; font-size:14px; }
th { background:#f0f0f0; }

.actions button {
    margin:2px;
    padding:4px 8px;
    cursor:pointer;
}

.status-new { background:#eef; }
.status-print_today { background:#ffeeba; }
.status-delayed { background:#f8d7da; }
.status-stock { background:#d4edda; }
.status-done { background:#cce5ff; }
</style>
</head>
<body>

<h1>Производство</h1>

<table id="items-table">
<tr>
    <th>Аккаунт</th>
    <th>Posting</th>
    <th>Offer</th>
    <th>Категория</th>
    <th>Кол-во</th>
    <th>Статус</th>
    <th>Действия</th>
</tr>

<?php foreach ($items as $item): ?>
<tr
    data-item-id="<?= h($item['item_id']) ?>"
    class="status-<?= h($item['status']) ?>"
>
    <td><?= h($item['account']) ?></td>
    <td><?= h($item['posting_number']) ?></td>
    <td><?= h($item['offer_id']) ?></td>
    <td><?= h($item['category']) ?></td>
    <td><?= h($item['quantity']) ?></td>
    <td class="status-cell"><?= h($item['status']) ?></td>
    <td class="actions">
        <button onclick="setStatus(this,'print_today')">Печать сегодня</button>
        <button onclick="setStatus(this,'stock')">Со склада</button>
        <button onclick="setStatus(this,'delayed')">Отложить</button>
        <button onclick="setStatus(this,'done')">Готово</button>
    </td>
</tr>
<?php endforeach; ?>

</table>

<script>
async function setStatus(btn, status) {
    const row = btn.closest('tr');
    const itemId = row.dataset.itemId;

    const res = await fetch('production_item_update.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            item_id: itemId,
            status: status
        })
    });

    const data = await res.json();
    if (data.status !== 'ok') {
        alert('Ошибка обновления');
        return;
    }

    row.className = 'status-' + status;
    row.querySelector('.status-cell').innerText = status;
}
</script>

</body>
</html>
