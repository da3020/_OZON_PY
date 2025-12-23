<?php
$ROOT = dirname(__DIR__, 1);

$ICON_DIR = $ROOT . '/data/icons';
$META_DIR = $ROOT . '/data/icons_meta';

@mkdir($ICON_DIR, 0777, true);
@mkdir($META_DIR, 0777, true);

$offer = $_GET['offer_id'] ?? null;
$url = $_GET['url'] ?? null;

if (!$offer || !$url) {
    http_response_code(400);
    exit;
}

$iconFile = "$ICON_DIR/$offer.jpg";
$metaFile = "$META_DIR/$offer.json";

$download = true;

if (file_exists($metaFile)) {
    $meta = json_decode(file_get_contents($metaFile), true);
    if (($meta['image_url'] ?? '') === $url && file_exists($iconFile)) {
        $download = false;
    }
}

if ($download) {
    $img = @file_get_contents($url);
    if ($img) {
        file_put_contents($iconFile, $img);
        file_put_contents($metaFile, json_encode([
            'offer_id' => $offer,
            'image_url' => $url,
            'cached_at' => date('c')
        ], JSON_PRETTY_PRINT));
    }
}

header('Content-Type: image/jpeg');
readfile($iconFile);
