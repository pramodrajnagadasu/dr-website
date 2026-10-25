<?php
/** Same logic as add-record-entry.php, but token-authed and patient-only (no doctor app yet). */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Invalid request.', 405);

$patient = apiRequirePatient();

$ref = trim($_POST['ref'] ?? '');
if (!$ref) apiError('Missing appointment reference.');

$appt = findAppointmentByRef($ref);
if (!$appt) apiError('Appointment not found.', 404);
if ($appt['patient_id'] !== $patient['id']) apiError('Access denied.', 403);

$description  = trim($_POST['description'] ?? '');
$filename     = null;
$originalName = null;

if (!empty($_FILES['file']['name'])) {
    $file     = $_FILES['file'];
    $tmpPath  = $file['tmp_name'];
    $origName = $file['name'];
    $size     = $file['size'];
    $error    = $file['error'];

    if ($error !== UPLOAD_ERR_OK) {
        $errMsg = match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large. Max 10 MB.',
            UPLOAD_ERR_PARTIAL => 'Upload incomplete.',
            default => 'Upload error.',
        };
        apiError($errMsg);
    }

    if ($size > 10 * 1024 * 1024) {
        apiError('File too large. Max 10 MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    if (!in_array($mime, $allowed)) {
        apiError('Invalid file type. Allowed: JPG, PNG, GIF, WEBP, PDF.');
    }

    $ext = match ($mime) {
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/gif'       => 'gif',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
        default           => 'bin',
    };

    $uploadDir = __DIR__ . '/../uploads/' . preg_replace('/[^A-Z0-9\-]/i', '', $ref) . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeFilename = uniqid('patient_', true) . '.' . $ext;
    $destPath     = $uploadDir . $safeFilename;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        apiError('Failed to save file.');
    }

    $filename     = 'uploads/' . preg_replace('/[^A-Z0-9\-]/i', '', $ref) . '/' . $safeFilename;
    $originalName = htmlspecialchars(basename($origName));
}

if (!$description && !$filename) {
    apiError('Please provide a note or upload a file.');
}

ensureRecord($ref, $appt['patient_id']);

$ok = addRecordEntry($ref, [
    'by'            => 'patient',
    'description'   => $description,
    'filename'      => $filename,
    'original_name' => $originalName,
]);

apiJson(['success' => $ok, 'message' => $ok ? 'Entry saved.' : 'Failed to save entry.']);
