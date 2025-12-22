<?php
header('Content-Type: application/json; charset=utf-8');

// -------- input --------
$batchId = $_POST['batch_id'] ?? null;
$takenBy = $_POST['taken_by'] ?? 'production';

// -------- validation --------
if (!$batchId) {
    http_response_code(400);
    echo json_encode([
        "error" => "batch_id is required"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// защита
$batchId = basename($batchId);
$batchId = preg_replace('/\.json$/', '', $batchId);

// -------- file path --------
$batchFile = __DIR__ . '/logs/batch_' . $batchId . '.json';

if (!file_exists($batchFile)) {
    http_response_code(404);
    echo json_encode([
        "error" => "Batch not found",
        "batch_id" => $batchId
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------- load --------
$data = json_decode(file_get_contents($batchFile), true);

if (!$data) {
    http_response_code(500);
    echo json_encode([
        "error" => "Invalid batch file"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------- state check --------
if (($data['status'] ?? '') !== 'new') {
    http_response_code(409);
    echo json_encode([
        "error" => "Batch already taken",
        "status" => $data['status'] ?? null
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// -------- update --------
$data['status']   = 'taken';
$data['taken_at'] = date('c');
$data['taken_by'] = $takenBy;

// -------- save --------
file_put_contents(
    $batchFile,
    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

// -------- response --------
echo json_encode([
    "status" => "ok",
    "batch_id" => $batchId,
    "taken_at" => $data['taken_at'],
    "taken_by" => $data['taken_by']
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
