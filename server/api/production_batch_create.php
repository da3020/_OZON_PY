<?php
header('Content-Type: application/json');

$ROOT = dirname(__DIR__, 1);

$LOG_DIR   = $ROOT . '/api/logs';
$ITEMS_DIR = $ROOT . '/data/items';

@mkdir($LOG_DIR, 0777, true);
@mkdir($ITEMS_DIR, 0777, true);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['batch_id']) || empty($input['items'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid payload']));
}

$batchId = $input['batch_id'];
$now = date('c');

$logFile = "$LOG_DIR/batch_$batchId.json";
if (!file_exists($logFile)) {
    file_put_contents($logFile, json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

foreach ($input['items'] as $item) {
    $itemId = $item['posting_number'] . '_' . $item['offer_id'];
    $file = "$ITEMS_DIR/$itemId.json";

    if (!file_exists($file)) {
        file_put_contents($file, json_encode([
            'item_id' => $itemId,
            'posting_number' => $item['posting_number'],
            'offer_id' => $item['offer_id'],
            'account' => $item['account'],
            'category' => $item['category'],
            'quantity' => $item['quantity'],
            'image_url' => $item['image_url'] ?? null,
            'status' => 'new',
            'created_at' => $now,
            'updated_at' => $now,
            'source_batch_id' => $batchId
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

echo json_encode(['status' => 'ok']);
