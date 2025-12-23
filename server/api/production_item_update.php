<?php
header('Content-Type: application/json');

$ROOT = dirname(__DIR__, 1);

$ITEMS_DIR   = $ROOT . '/data/items';
$HISTORY_DIR = $ROOT . '/data/history';

if (!is_dir($ITEMS_DIR) || !is_dir($HISTORY_DIR)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Storage not ready']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (
    empty($input['item_id']) ||
    empty($input['status'])
) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
    exit;
}

$itemId = $input['item_id'];
$newStatus = $input['status'];
$now = date('c');

$itemFile = $ITEMS_DIR . '/' . $itemId . '.json';

if (!file_exists($itemFile)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    exit;
}

$item = json_decode(file_get_contents($itemFile), true);
$oldStatus = $item['status'] ?? null;

$item['status'] = $newStatus;
$item['updated_at'] = $now;

file_put_contents(
    $itemFile,
    json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// -----------------------------
// HISTORY LOG
// -----------------------------
$history = [
    'item_id'    => $itemId,
    'from'       => $oldStatus,
    'to'         => $newStatus,
    'changed_at' => $now,
];

file_put_contents(
    $HISTORY_DIR . '/' . $itemId . '_' . time() . '.json',
    json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo json_encode([
    'status' => 'ok',
    'item'   => $itemId,
    'new'    => $newStatus
]);
