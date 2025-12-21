<?php
/*
|--------------------------------------------------------------------------
| Production Batch List
|--------------------------------------------------------------------------
| Возвращает список batch
| batch_id отдается БЕЗ префикса batch_
|--------------------------------------------------------------------------
*/

$dir = __DIR__ . '/logs';

if (!is_dir($dir)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Logs directory not found'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$files = glob($dir . '/batch_*.json');

$batches = [];

foreach ($files as $file) {
    $filename = basename($file);

    if (!preg_match('/^batch_(.+)\.json$/', $filename, $m)) {
        continue;
    }

    $cleanBatchId = $m[1];

    $data = json_decode(file_get_contents($file), true);
    if (!$data) {
        continue;
    }

    $batches[] = [
        'batch_id'         => $cleanBatchId,   // ⬅ БЕЗ batch_
        'created_at'       => $data['batch_created_at'] ?? null,
        'total_orders'     => $data['total_orders'] ?? 0,
        'status'           => $data['status'] ?? 'unknown',
        'taken_at'         => $data['taken_at'] ?? null,
        'taken_by'         => $data['taken_by'] ?? null,
    ];
}

/*
|--------------------------------------------------------------------------
| Sort: newest first
|--------------------------------------------------------------------------
*/

usort($batches, function ($a, $b) {
    return strcmp($b['batch_id'], $a['batch_id']);
});

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status'  => 'ok',
    'count'   => count($batches),
    'batches' => $batches
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
