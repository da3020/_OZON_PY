<?php
// /api/production_item_update.php

header('Content-Type: application/json');

$ROOT = dirname(__DIR__, 1);

$ITEMS_DIR   = $ROOT . '/data/items';
$HISTORY_DIR = $ROOT . '/data/history';

if (!is_dir($HISTORY_DIR)) {
    mkdir($HISTORY_DIR, 0777, true);
}

$input = json_decode(file_get_contents('php://input'), true);

if (
    empty($input['item_id']) ||
    empty($input['status'])
) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'bad request'
    ]);
    exit;
}

$itemFile = $ITEMS_DIR . '/' . $input['item_id'] . '.json';

if (!file_exists($itemFile)) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'item not found'
    ]);
    exit;
}

$item = json_decode(file_get_contents($itemFile), true);

$oldStatus = $item['status'];
$newStatus = $input['status'];

$item['status'] = $newStatus;
$item['updated_at'] = date('c');

file_put_contents(
    $itemFile,
    json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// история изменений
file_put_contents(
    $HISTORY_DIR . '/' . $input['item_id'] . '_' . time() . '.json',
    json_encode([
        'item_id'    => $input['item_id'],
        'from'       => $oldStatus,
        'to'         => $newStatus,
        'changed_at' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo json_encode(['status' => 'ok']);
