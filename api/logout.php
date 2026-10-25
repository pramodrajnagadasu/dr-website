<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Invalid request.', 405);

$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($header === '' && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $header  = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}
if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
    deleteApiToken(trim($m[1]));
}

apiJson(['success' => true]);
