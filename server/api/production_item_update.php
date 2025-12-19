<?php
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);

$required = ['batch_id', 'account', 'offer_id', 'planned_quantity', 'produced_quantity', 'status'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(["error" => "missing field: $field"]);
        exit;
    }
}

$batchId = basename($input['batch_id']);
$prodDir = __DIR__ . '/production_batches';

if (!is_dir($prodDir)) {
    mkdir($prodDir, 0755, true);
}

$file = "$prodDir/$batchId.production.json";

$data = file_exists($file)
    ? json_decode(file_get_contents($file), true)
    : [
        "batch_id" => $batchId,
        "updated_at" => null,
        "items" => []
    ];

$key = $input['account'] . '|' . $input['offer_id'];

$data['items'][$key] = [
    "offer_id" => $input['offer_id'],
    "account" => $input['account'],
    "planned_quantity" => (int)$input['planned_quantity'],
    "produced_quantity" => (int)$input['produced_quantity'],
    "status" => $input['status'],
    "comment" => $input['comment'] ?? ''
];

$data['updated_at'] = date('c');

file_put_contents(
    $file,
    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

echo json_encode([
    "status" => "ok",
    "saved" => basename($file)
]);
