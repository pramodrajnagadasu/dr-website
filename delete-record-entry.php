<?php
/**
 * AJAX endpoint — delete a record entry (note and/or attached file).
 * Doctor may delete any entry; a patient may only delete entries they
 * authored themselves on their own record.
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

$ref     = trim($_POST['ref']   ?? '');
$entryId = trim($_POST['entry'] ?? '');

if (!$ref || !$entryId) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']); exit;
}

$entry = findRecordEntryById($entryId);
if (!$entry || $entry['appointment_ref'] !== $ref) {
    echo json_encode(['success' => false, 'message' => 'Entry not found.']); exit;
}

if ($isDoctor) {
    // Doctor may delete any entry
} elseif ($isPatient) {
    if ($entry['patient_id'] !== $_SESSION['portal_patient_id'] || $entry['by_role'] !== 'patient') {
        echo json_encode(['success' => false, 'message' => 'Access denied.']); exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Access denied.']); exit;
}

$ok = deleteRecordEntry($entryId);
echo json_encode(['success' => $ok, 'message' => $ok ? 'Entry deleted.' : 'Failed to delete entry.']);
