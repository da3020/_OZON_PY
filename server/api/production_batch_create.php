<?php
header('Content-Type: application/json; charset=utf-8');

// -----------------------------
// Чтение входного JSON
// -----------------------------
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data === null) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "error" => "Invalid JSON"
    ]);
    exit;
}

// -----------------------------
// Генерация batch_id
// Формат: YYYYMMDD-HHMMSS-XXXX
// -----------------------------
$batchId = date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);

// -----------------------------
// Каталог логов
// -----------------------------
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// -----------------------------
// Сохраняем файл
// -----------------------------
$filename = $logDir . '/batch_' . $batchId . '.json';

$data['_meta'] = [
    'batch_id'   => $batchId,
    'received_at' => date('c'),
    'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
];

file_put_contents(
    $filename,
    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// -----------------------------
// Ответ
// -----------------------------
echo json_encode([
    "status"     => "ok",
    "batch_id"   => $batchId,
    "saved_as"   => basename($filename),
    "received_at" => date('c')
]);
