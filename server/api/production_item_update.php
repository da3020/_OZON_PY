<?php
/*
|--------------------------------------------------------------------------
| Production Item Update
|--------------------------------------------------------------------------
| Обновляет статус отдельного item в batch
| batch_id принимается БЕЗ префикса
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

$batchId        = $data['batch_id'] ?? null;
$postingNumber  = $data['posting_number'] ?? null;
$offerId        = $data['offer_id'] ?? null;
$newStatus      = $data['status'] ?? null;

if (!$batchId || !$postingNumber || !$offerId || !$newStatus) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields'], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = __DIR__ . '/logs/batch_' . $batchId . '.json';

if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Batch not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$batch = json_decode(file_get_contents($file), true);
if (!$batch) {
    http_response_code(500);
    echo json_encode(['error' => 'Corrupted batch file'], JSON_UNESCAPED_UNICODE);
    exit;
}

$updated = false;

foreach ($batch['items'] as &$item) {
    if (
        ($item['posting_number'] ?? null) === $postingNumber &&
        ($item['offer_id'] ?? null) === $offerId
    ) {
        $item['status'] = $newStatus;
        $item['updated_at'] = date('c');
        $updated = true;
        break;
    }
}

if (!$updated) {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

file_put_contents(
    $file,
    json_encode($batch, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

echo json_encode([
    'status'   => 'ok',
    'batch_id' => $batchId,
    'updated'  => true
], JSON_UNESCAPED_UNICODE);
