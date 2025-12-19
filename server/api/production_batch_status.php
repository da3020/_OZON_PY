<?php
header('Content-Type: application/json; charset=utf-8');

$batchId = basename($_GET['batch_id'] ?? '');

if (!$batchId) {
    http_response_code(400);
    echo json_encode(["error" => "batch_id required"]);
    exit;
}

$file = __DIR__ . "/production_batches/$batchId.production.json";

if (!file_exists($file)) {
    echo json_encode([
        "status" => "ok",
        "message" => "no production started",
        "progress" => 0
    ]);
    exit;
}

$data = json_decode(file_get_contents($file), true);
$items = $data['items'] ?? [];

$planned = 0;
$produced = 0;

foreach ($items as $item) {
    $planned += $item['planned_quantity'];
    $produced += $item['produced_quantity'];
}

$progress = $planned > 0 ? round(($produced / $planned) * 100, 1) : 0;

echo json_encode([
    "status" => "ok",
    "batch_id" => $batchId,
    "updated_at" => $data['updated_at'],
    "planned_quantity" => $planned,
    "produced_quantity" => $produced,
    "progress_percent" => $progress,
    "items" => array_values($items)
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
