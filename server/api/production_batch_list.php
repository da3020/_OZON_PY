<?php
header('Content-Type: application/json; charset=utf-8');

$logDir = __DIR__ . '/logs';

if (!is_dir($logDir)) {
    echo json_encode([
        "status" => "ok",
        "batches" => []
    ]);
    exit;
}

$files = glob($logDir . '/batch_*.json');
$batches = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $data = json_decode($content, true);

    if (!$data) {
        continue;
    }

    $batches[] = [
        "batch_id" => basename($file, '.json'),
        "batch_created_at" => $data["batch_created_at"] ?? null,
        "total_orders" => $data["total_orders"] ?? 0,
        "items_count" => isset($data["items"]) ? count($data["items"]) : 0,
        "file" => basename($file)
    ];
}

// сортируем: новые сверху
usort($batches, function ($a, $b) {
    return strcmp($b["batch_created_at"], $a["batch_created_at"]);
});

echo json_encode([
    "status" => "ok",
    "total_batches" => count($batches),
    "batches" => $batches
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
