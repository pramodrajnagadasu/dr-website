<?php
/** Token-authed equivalent of serve-file.php. The app fetches this with an Authorization header (e.g. Image.network(headers:)). */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

$patient = apiRequirePatient();

$ref  = trim($_GET['ref'] ?? '');
$file = trim($_GET['f']   ?? '');
if (!$ref || !$file) { http_response_code(400); exit; }

$appt = findAppointmentByRef($ref);
if (!$appt || $appt['patient_id'] !== $patient['id']) { http_response_code(403); exit; }

$record   = findRecordByRef($ref);
$safeName = basename($file);
$attached = false;
if ($record) {
    foreach ($record['entries'] as $e) {
        if (!empty($e['filename']) && basename($e['filename']) === $safeName) { $attached = true; break; }
    }
}
if (!$attached) { http_response_code(404); exit; }

$safeRef     = preg_replace('/[^A-Z0-9\-]/i', '', $ref);
$uploadsRoot = realpath(__DIR__ . '/../uploads');
$path        = realpath($uploadsRoot . '/' . $safeRef . '/' . $safeName);

if (!$path || strpos($path, $uploadsRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
    http_response_code(404); exit;
}

$mime = mime_content_type($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
if (isset($_GET['dl'])) {
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
}
readfile($path);
