<?php
header('Content-Type: text/html; charset=utf-8');

/*
|--------------------------------------------------------------------------
| Production Dashboard
|--------------------------------------------------------------------------
| batch_id:
|  - если передан → открываем конкретный batch
|  - если нет → берём последний batch
|--------------------------------------------------------------------------
*/

$batchId = $_GET['batch_id'] ?? null;

$batchDir = __DIR__ . '/logs';
$batchFile = null;

// --------------------------------------------------
// Если batch_id передан — используем его
// --------------------------------------------------
if ($batchId) {
    $candidate = $batchDir . '/batch_' . basename($batchId) . '.json';
    if (!file_exists($candidate)) {
        die('Batch не найден: ' . htmlspecialchars($batchId));
    }
    $batchFile = $candidate;
}

// --------------------------------------------------
// Если batch_id НЕ передан — берём последний batch
// --------------------------------------------------
if (!$batchFile) {
    $files = glob($batchDir . '/batch_*.json');
    if (!$files) {
        die('Нет доступных batch');
    }

    rsort($files, SORT_STRING); // самый новый — первый
    $batchFile = $files[0];
}

// --------------------------------------------------
// Загружаем batch
// --------------------------------------------------
$batchData = json_decode(file_get_contents($batchFile), true);
if (!$batchData) {
    die('Ошибка чтения batch');
}

$batchId   = $batchData['batch_id'];
$items     = $batchData['items'] ?? [];

// --------------------------------------------------
// Группировка по категориям
// --------------------------------------------------
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
<title>Производство — batch <?= htmlspecialchars($batchId) ?></title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f7f7f7;
    margin: 20px;
}
.block {
    background: #fff;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 6px;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 6px 8px;
    border-bottom: 1px solid #ddd;
}
th {
    background: #eee;
}
.small {
    font-size: 13px;
    color: #666;
}
</style>
</head>
<body>

<h1>Производственный batch</h1>

<div class="block">
    <strong>Batch ID:</strong> <?= htmlspecialchars($batchId) ?><br>
    <strong>Создан:</strong> <?= htmlspecialchars($batchData['batch_created_at']) ?><br>
    <strong>Заказов:</strong> <?= (int)$batchData['total_orders'] ?><br>
    <strong>Статус:</strong> <?= htmlspecialchars($batchData['status']) ?>
</div>

<?php foreach ($byCategory as $cat => $rows): ?>
<div class="block">
    <h2><?= htmlspecialchars($cat) ?></h2>
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

<div class="small">
    <div>Открыт файл: <?= basename($batchFile) ?></div>
</div>

</body>
</html>
