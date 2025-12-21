<?php
/*
|--------------------------------------------------------------------------
| Production Batch Status Update
|--------------------------------------------------------------------------
| Обновляет статус batch
| batch_id принимается БЕЗ префикса batch_
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=utf-8');

$batchId = $_POST['batch_id'] ?? null;
$status  = $_POST['status'] ?? null;

if (!$batchId || !$status) {
    echo json_encode([
        'status' => 'error',
        'message' => 'batch_id and status are required'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$dir = __DIR__ . '/logs';
$file = $dir . '/batch_' . basename($batchId) . '.json';

if (!file_exists($file)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Batch not found'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode(file_get_contents($file), true);
if (!$data) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid batch file'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
|--------------------------------------------------------------------------
| Update status
|--------------------------------------------------------------------------
*/

$data['status'] = $status;

if ($status === 'taken') {
    $data['taken_at'] = date('c');
}

file_put_contents(
    $file,
    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

echo json_encode([
    'status'    => 'ok',
    'batch_id'  => $batchId,
    'new_state' => $status
], JSON_UNESCAPED_UNICODE);
