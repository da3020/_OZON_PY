<?php
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

$itemId = $input['item_id'] ?? null;
$newStatus = $input['status'] ?? null;

if (!$itemId || !$newStatus) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'item_id and status required'
    ]);
    exit;
}

$allowedStatuses = [
    'new',
    'stock',
    'print_today',
    'delayed',
    'printed'
];

if (!in_array($newStatus, $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'invalid status'
    ]);
    exit;
}

$dataDir = dirname(__DIR__) . '/data/items';
$file = $dataDir . '/' . $itemId . '.json';

if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'item not found'
    ]);
    exit;
}

$item = json_decode(file_get_contents($file), true);
$item['status'] = $newStatus;
$item['updated_at'] = date('c');

file_put_contents(
    $file,
    json_encode($item, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

echo json_encode([
    'status' => 'ok',
    'item_id' => $itemId,
    'new_status' => $newStatus
]);
