<?php
// server/api/production_batch_create.php

header('Content-Type: application/json');

$ROOT = dirname(__DIR__, 2);

$LOG_DIR     = $ROOT . '/api/logs';
$ITEMS_DIR   = $ROOT . '/data/items';

if (!is_dir($LOG_DIR)) {
    mkdir($LOG_DIR, 0777, true);
}

if (!is_dir($ITEMS_DIR)) {
    mkdir($ITEMS_DIR, 0777, true);
}

// -----------------------------
// READ INPUT
// -----------------------------
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['batch_id']) || empty($input['items'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid payload'
    ]);
    exit;
}

$batchId = $input['batch_id'];
$now = date('c');

// -----------------------------
// SAVE BATCH LOG (IMMUTABLE)
// -----------------------------
$logFile = $LOG_DIR . "/batch_{$batchId}.json";

if (!file_exists($logFile)) {
    file_put_contents(
        $logFile,
        json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

// -----------------------------
// PROCESS ITEMS
// -----------------------------
$created = 0;
$skipped = 0;

foreach ($input['items'] as $item) {

    if (
        empty($item['posting_number']) ||
        empty($item['offer_id'])
    ) {
        continue;
    }

    $itemId = $item['posting_number'] . '_' . $item['offer_id'];
    $itemFile = $ITEMS_DIR . '/' . $itemId . '.json';

    // -----------------------------
    // CREATE NEW ITEM
    // -----------------------------
    if (!file_exists($itemFile)) {

        $itemData = [
            'item_id'         => $itemId,
            'posting_number'  => $item['posting_number'],
            'offer_id'        => $item['offer_id'],
            'account'         => $item['account'] ?? null,
            'category'        => $item['category'] ?? null,
            'quantity'        => $item['quantity'] ?? 1,

            'status'          => 'new',
            'created_at'      => $now,
            'updated_at'      => $now,

            'source_batch_id' => $batchId
        ];

        file_put_contents(
            $itemFile,
            json_encode($itemData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $created++;
    } else {
        // -----------------------------
        // UPDATE "SEEN" TIMESTAMP ONLY
        // -----------------------------
        $existing = json_decode(file_get_contents($itemFile), true);
        $existing['updated_at'] = $now;

        file_put_contents(
            $itemFile,
            json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $skipped++;
    }
}

// -----------------------------
// RESPONSE
// -----------------------------
echo json_encode([
    'status'        => 'ok',
    'batch_id'      => $batchId,
    'items_created' => $created,
    'items_skipped' => $skipped,
    'received_at'   => $now
]);
