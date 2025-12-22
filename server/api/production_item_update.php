<?php
header('Content-Type: application/json');

$ROOT = dirname(__DIR__, 1);

$ITEMS_DIR   = $ROOT . '/data/items';
$HISTORY_DIR = $ROOT . '/data/history';

if (!is_dir($HISTORY_DIR)) {
    mkdir($HISTORY_DIR, 0777, true);
}

// -----------------------------
// READ INPUT
// -----------------------------
$input = json_decode(file_get_contents('php://input'), true);

if (
    !$input ||
    empty($input['item_id']) ||
    empty($input['status'])
) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid payload'
    ]);
    exit;
}

$itemId    = basename($input['item_id']);
$newStatus = $input['status'];
$now       = date('c');

$itemFile = $ITEMS_DIR . '/' . $itemId . '.json';

if (!file_exists($itemFile)) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Item not found'
    ]);
    exit;
}

// -----------------------------
// LOAD ITEM
// -----------------------------
$item = json_decode(file_get_contents($itemFile), true);
$oldStatus = $item['status'] ?? null;

// -----------------------------
// UPDATE ITEM
// -----------------------------
$item['status'] = $newStatus;
$item['updated_at'] = $now;

file_put_contents(
    $itemFile,
    json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// -----------------------------
// WRITE HISTORY
// -----------------------------
$historyRecord = [
    'item_id'    => $itemId,
    'from'       => $oldStatus,
    'to'         => $newStatus,
    'changed_at' => $now
];

$historyFile = $HISTORY_DIR . '/' . $itemId . '_' . time() . '.json';

file_put_contents(
    $historyFile,
    json_encode($historyRecord, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// -----------------------------
// RESPONSE
// -----------------------------
echo json_encode([
    'status'     => 'ok',
    'item_id'    => $itemId,
    'old_status' => $oldStatus,
    'new_status' => $newStatus
]);
