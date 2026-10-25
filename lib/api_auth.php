<?php
/**
 * Shared helpers for the mobile app's api/ endpoints — JSON responses and
 * bearer-token auth. Always require this AFTER db.php.
 */

// CORS: the app authenticates with a Bearer header, not cookies, so a
// wide-open origin carries no CSRF-style risk and lets the Flutter web
// build (its own origin/port) and local dev servers call these endpoints.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function apiJson($data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function apiError(string $message, int $code = 400): never {
    apiJson(['success' => false, 'message' => $message], $code);
}

/** Reads the Authorization: Bearer header, resolves it to a patient row, or 401s. */
function apiRequirePatient(): array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header  = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
        apiError('Not authenticated.', 401);
    }
    $patient = findPatientByApiToken(trim($m[1]));
    if (!$patient) {
        apiError('Session expired. Please log in again.', 401);
    }
    return $patient;
}
