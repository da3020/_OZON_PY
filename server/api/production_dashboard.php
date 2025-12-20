<?php
header('Content-Type: text/html; charset=utf-8');

/**
 * Production Dashboard
 * Read-only HTML panel
 */

// -----------------------------
// LOAD CONFIG
// -----------------------------
$config = require __DIR__ . '/config.php';
$apiBaseUrl = $config['api_base_url'];

// endpoints
$listBatchUrl = $apiBaseUrl . '/production_batch_list.php';
$getBatchUrl  = $apiBaseUrl . '/production_batch_get.php';

// -----------------------------
// GET LAST BATCH
// -----------------------------
$listJson = @file_get_contents($listBatchUrl);
if ($listJson === false) {
    die('Ошибка: не удалось получить список batch');
}

$listData = json_decode($listJson, true);
if (!$listData || empty($listData['batches'])) {
    die('Нет batch данных');
}

$lastBatchId = $listData['batches'][0]['batch_id'];

// -----------------------------
// GET BATCH DATA
// -----------------------------
$batchJson = @file_get_contents(
    $getBatchUrl . '?batch_id=' . urlencode($lastBatchId)
);

if ($batchJson === false) {
    die('Ошибка: не удалось загрузить batch');
}

$batchData = json_decode($batchJson, true);
if (!$batchData || ($batchData['status'] ?? '') !== 'ok') {
    die('Ошибка данных batch');
}

$batch   = $batchData['batch'];
$summary = $batchData['summary'];
$items   = $batchData['items'];

// -----------------------------
// GROUP ITEMS BY CATEGORY
// -----------------------------
$byCategory = [];

foreach ($items as $item) {
    $cat = $item['category'] ?? 'Иное';
    $byCategory[$cat][] = $item;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Производство — текущий batch</title>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #f7f7f7;
}
h1, h2 {
    margin-bottom: 10px;
}
.block {
    background: #fff;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 6px;
}
.category {
    margin-bottom: 15px;
}
.category h3 {
    margin-bottom: 5px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 6px 8px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}
th {
    background: #eee;
}
.small {
    color: #666;
    font-size: 13px;
}
</style>
</head>
<body>

<h1>Производство — текущий batch</h1>

<div class="block">
    <strong>Batch ID:</strong> <?= htmlspecialchars($batch['batch_id']) ?><br>
    <strong>Создан:</strong> <?= htmlspecialchars($batch['batch_created_at']) ?><br>
    <strong>Заказов:</strong> <?= (int)$batch['total_orders'] ?><br>
    <strong>Позиций:</strong> <?= (int)$batch['items_count'] ?>
</div>

<div class="block">
    <h2>Сводка по категориям</h2>
    <?php foreach ($summary['by_category'] as $cat => $qty): ?>
        <div><?= htmlspecialchars($cat) ?> — <strong><?= (int)$qty ?></strong></div>
    <?php endforeach; ?>
</div>

<div class="block">
    <h2>Что делать сегодня</h2>

    <?php foreach ($byCategory as $cat => $rows): ?>
        <div class="category">
            <h3><?= htmlspecialchars($cat) ?></h3>
            <table>
                <tr>
                    <th>Offer</th>
                    <th>Кол-во</th>
                    <th>Аккаунт</th>
                </tr>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['offer_id']) ?></td>
                    <td><?= (int)$row['quantity'] ?></td>
                    <td><?= htmlspecialchars($row['account']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endforeach; ?>

</div>

<div class="small">
    Обновляется автоматически при новом batch
</div>

</body>
</html>
