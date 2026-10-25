<?php
/**
 * AJAX endpoint — add a note/image entry to an appointment record.
 * Accessible by logged-in doctor (any record) or patient (their own).
 */
session_start();
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
}

$isDoctor  = isDoctorLoggedIn();
$isPatient = isPatientLoggedIn();

if (!$isDoctor && !$isPatient) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']); exit;
}

$ref = trim($_POST['ref'] ?? '');
$by  = ($isDoctor && !$isPatient) ? 'doctor' : ($_POST['by'] ?? 'patient');
// Doctor overrides
if ($isDoctor) $by = 'doctor';
if (!$isDoctor && $isPatient) $by = 'patient';

if (!$ref) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment reference.']); exit;
}

$appt = findAppointmentByRef($ref);
if (!$appt) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found.']); exit;
}

// Patients can only add to their own records
if (!$isDoctor && $isPatient) {
    if ($appt['patient_id'] !== $_SESSION['portal_patient_id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']); exit;
    }
}

$description = trim($_POST['description'] ?? '');
$filename    = null;
$originalName = null;

// Handle file upload
if (!empty($_FILES['file']['name'])) {
    $file     = $_FILES['file'];
    $tmpPath  = $file['tmp_name'];
    $origName = $file['name'];
    $size     = $file['size'];
    $error    = $file['error'];

    if ($error !== UPLOAD_ERR_OK) {
        $errMsg = match($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large. Max 10 MB.',
            UPLOAD_ERR_PARTIAL => 'Upload incomplete.',
            default => 'Upload error.',
        };
        echo json_encode(['success' => false, 'message' => $errMsg]); exit;
    }

    if ($size > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large. Max 10 MB.']); exit;
    }

    // Validate MIME type
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime     = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);
    $allowed  = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
    if (!in_array($mime, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP, PDF.']); exit;
    }

    // Determine extension from MIME
    $ext = match($mime) {
        'image/jpeg'     => 'jpg',
        'image/png'      => 'png',
        'image/gif'      => 'gif',
        'image/webp'     => 'webp',
        'application/pdf'=> 'pdf',
        default          => 'bin',
    };

    // Create uploads directory
    $uploadDir = __DIR__ . '/uploads/' . preg_replace('/[^A-Z0-9\-]/i', '', $ref) . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeFilename = uniqid($by . '_', true) . '.' . $ext;
    $destPath     = $uploadDir . $safeFilename;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file.']); exit;
    }

    $filename     = 'uploads/' . preg_replace('/[^A-Z0-9\-]/i', '', $ref) . '/' . $safeFilename;
    $originalName = htmlspecialchars(basename($origName));
}

if (!$description && !$filename) {
    echo json_encode(['success' => false, 'message' => 'Please provide a note or upload a file.']); exit;
}

// Ensure record exists
ensureRecord($ref, $appt['patient_id']);

$entry = [
    'by'            => $by,
    'description'   => $description,
    'filename'      => $filename,
    'original_name' => $originalName,
];

$ok = addRecordEntry($ref, $entry);
echo json_encode(['success' => $ok, 'message' => $ok ? 'Entry saved.' : 'Failed to save entry.']);
