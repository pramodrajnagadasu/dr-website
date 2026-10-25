<?php
/**
 * Auth-gated proxy for uploaded record files (uploads/ is Deny-from-all).
 * Only the owning patient or a logged-in doctor may fetch a file, and only
 * if it's actually attached to a record entry for the given appointment ref.
 */
session_start();
require_once 'db.php';
require_once 'auth.php';

$ref  = trim($_GET['ref'] ?? '');
$file = trim($_GET['f']   ?? '');

if (!$ref || !$file) { http_response_code(400); exit; }

$isDoctor  = isDoctorLoggedIn();
$isPatient = isPatientLoggedIn();
if (!$isDoctor && !$isPatient) { http_response_code(403); exit; }

$appt = findAppointmentByRef($ref);
if (!$appt) { http_response_code(404); exit; }

if (!$isDoctor && $isPatient && $appt['patient_id'] !== $_SESSION['portal_patient_id']) {
    http_response_code(403); exit;
}

// The requested filename must belong to an actual entry on this record
$record = findRecordByRef($ref);
$safeName = basename($file);
$attached = false;
if ($record) {
    foreach ($record['entries'] as $e) {
        if (!empty($e['filename']) && basename($e['filename']) === $safeName) { $attached = true; break; }
    }
}
if (!$attached) { http_response_code(404); exit; }

$safeRef     = preg_replace('/[^A-Z0-9\-]/i', '', $ref);
$uploadsRoot = realpath(__DIR__ . '/uploads');
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
