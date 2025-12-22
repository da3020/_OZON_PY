<?php
$ROOT = dirname(__DIR__, 1);
$ITEMS_DIR = $ROOT . '/data/items';

$items = [];
foreach (glob($ITEMS_DIR . '/*.json') as $file) {
    $item = json_decode(file_get_contents($file), true);
    if (
        ($item['status'] ?? '') === 'print_today' &&
        ($item['category'] ?? '') === 'Флаг'
    ) {
        $items[] = $item;
    }
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Печатный лист — Флаги</title>
<style>
body { font-family: Arial; }
h1 { margin-bottom:10px; }
table { width:100%; border-collapse: collapse; }
th, td { border:1px solid #000; padding:6px; font-size:14px; }
</style>
</head>
<body>

<h1>Флаги — печать сегодня</h1>
<p><?= date('d.m.Y H:i') ?></p>

<table>
<tr>
    <th>Posting</th>
    <th>Offer</th>
    <th>Кол-во</th>
</tr>

<?php foreach ($items as $item): ?>
<tr>
    <td><?= h($item['posting_number']) ?></td>
    <td><?= h($item['offer_id']) ?></td>
    <td><?= h($item['quantity']) ?></td>
</tr>
<?php endforeach; ?>

</table>

<script>
window.print();
</script>

</body>
</html>
