<?php
header('Content-Type: application/json; charset=utf-8');

$batchId = $_GET['batch_id'] ?? null;

if (!$batchId) {
    http_response_code(400);
    echo json_encode(["error" => "batch_id is required"]);
    exit;
}

/*
|--------------------------------------------------------------------------
| Normalize batch_id
|--------------------------------------------------------------------------
| Принимаем:
|   20251220-211608-6965
|   batch_20251220-211608-6965
|   batch_20251220-211608-6965.json
|--------------------------------------------------------------------------
*/

$batchId = basename($batchId);
$batchId = preg_replace('/\.json$/', '', $batchId);

if (!str_starts_with($batchId, 'batch_')) {
    $batchId = 'batch_' . $batchId;
}

$filename = __DIR__ . '/logs/' . $batchId . '.json';

if (!file_exists($filename)) {
    http_response_code(404);
    echo json_encode([
        "error" => "batch not found",
        "expected_file" => basename($filename)
    ]);
    exit;
}

$data = json_decode(file_get_contents($filename), true);

if (!$data) {
    http_response_code(500);
    echo json_encode(["error" => "invalid batch file"]);
    exit;
}

$items = $data['items'] ?? [];

/*
|--------------------------------------------------------------------------
| Aggregation
|--------------------------------------------------------------------------
*/

$byCategory = [];
$byOffer = [];
$byAccount = [];

foreach ($items as $item) {
    $category = $item['category'] ?? 'Иное';
    $offerId  = $item['offer_id'] ?? 'UNKNOWN';
    $qty      = (int)($item['quantity'] ?? 0);
    $account  = $item['account'] ?? 'UNKNOWN';

    // category
    $byCategory[$category] = ($byCategory[$category] ?? 0) + $qty;

    // offer
    if (!isset($byOffer[$offerId])) {
        $byOffer[$offerId] = [
            "offer_id" => $offerId,
            "total_quantity" => 0,
            "category" => $category
        ];
    }
    $byOffer[$offerId]["total_quantity"] += $qty;

    // account
    $byAccount[$account] = ($byAccount[$account] ?? 0) + $qty;
}

ksort($byCategory);
ksort($byOffer);
ksort($byAccount);

echo json_encode([
    "status" => "ok",
    "batch" => [
        "batch_id" => preg_replace('/^batch_/', '', $batchId),
        "batch_created_at" => $data["batch_created_at"] ?? null,
        "total_orders" => $data["total_orders"] ?? 0,
        "items_count" => count($items),
    ],
    "summary" => [
        "by_category" => $byCategory,
        "by_offer" => array_values($byOffer),
        "by_account" => $byAccount
    ],
    "items" => $items
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
