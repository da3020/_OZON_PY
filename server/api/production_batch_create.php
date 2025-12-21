<?php
/*
|--------------------------------------------------------------------------
| Production Batch Create
|--------------------------------------------------------------------------
| Создаёт batch
| batch_id — ЧИСТЫЙ (без batch_)
| Файл сохраняется как batch_<batch_id>.json
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- batch_id ----------
$batchId = date('Ymd-His') . '-' . substr(uniqid(), -4);

// ---------- batch ----------
$batch = [
    'batch_id'         => $batchId,
    'batch_created_at' => $data['batch_created_at'] ?? null,
    'total_orders'     => $data['total_orders'] ?? 0,
    'items'            => $data['items'] ?? [],
    'status'           => 'new',
    'taken_at'         => null,
    'taken_by'         => null,
];

// ---------- save ----------
$dir = __DIR__ . '/logs';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$filename = $dir . '/batch_' . $batchId . '.json';
file_put_contents(
    $filename,
    json_encode($batch, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

echo json_encode([
    'status'    => 'ok',
    'batch_id'  => $batchId,
    'saved_as'  => basename($filename),
    'received_at' => date('c'),
], JSON_UNESCAPED_UNICODE);
