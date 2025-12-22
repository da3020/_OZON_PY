<?php
header('Content-Type: text/html; charset=utf-8');

$dataDir = dirname(__DIR__) . '/data/items';
$items = [];

foreach (glob($dataDir . '/*.json') as $file) {
    $items[] = json_decode(file_get_contents($file), true);
}

function group(array $items, string $status): array {
    return array_filter($items, fn($i) => $i['status'] === $status);
}

$groups = [
    'Новые' => group($items, 'new'),
    'Печать сегодня' => group($items, 'print_today'),
    'Отложены' => group($items, 'delayed'),
    'Готовы' => array_filter($items, fn($i) =>
        in_array($i['status'], ['stock', 'printed'], true)
    )
];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Production Dashboard</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        h2 { margin-top: 40px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        button { margin-right: 4px; }
    </style>
</head>
<body>

<h1>Производство — рабочий день</h1>

<?php foreach ($groups as $title => $group): ?>
<h2><?= htmlspecialchars($title) ?> (<?= count($group) ?>)</h2>

<table>
<tr>
    <th>Аккаунт</th>
    <th>Posting</th>
    <th>Offer</th>
    <th>Категория</th>
    <th>Статус</th>
    <th>Действие</th>
</tr>

<?php foreach ($group as $item): ?>
<tr>
    <td><?= htmlspecialchars($item['account']) ?></td>
    <td><?= htmlspecialchars($item['posting_number']) ?></td>
    <td><?= htmlspecialchars($item['offer_id']) ?></td>
    <td><?= htmlspecialchars($item['category']) ?></td>
    <td><?= htmlspecialchars($item['status']) ?></td>
    <td>
        <?php foreach (['stock','print_today','delayed','printed'] as $st): ?>
        <button onclick="updateStatus('<?= $item['item_id'] ?>','<?= $st ?>')">
            <?= $st ?>
        </button>
        <?php endforeach; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php endforeach; ?>

<script>
function updateStatus(itemId, status) {
    fetch('production_item_update.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({item_id: itemId, status: status})
    }).then(() => location.reload());
}
</script>

</body>
</html>
