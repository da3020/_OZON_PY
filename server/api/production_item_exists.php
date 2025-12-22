<?php
header('Content-Type: application/json; charset=utf-8');

$dataDir = dirname(__DIR__) . '/data/items';

$itemId = $_GET['item_id'] ?? '';

if ($itemId === '') {
    http_response_code(400);
    echo json_encode(['exists' => false]);
    exit;
}

$file = $dataDir . '/' . $itemId . '.json';

echo json_encode([
    
    'exists' => file_exists($file)
]);
