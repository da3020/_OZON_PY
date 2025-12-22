<?php
/**
 * Global API configuration
 * Used by dashboards and internal tools
 */

$scheme = (
    !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
) ? 'https' : 'http';

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

/**
 * ВАЖНО:
 * Предполагаем, что API доступно по /api
 * Если структура изменится — меняется только эта строка.
 * например $apiPath = '/server/api';
 */
$apiPath = '/api';

return [
    'api_base_url' => $scheme . '://' . $host . $apiPath,
];

