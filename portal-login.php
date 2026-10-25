<?php
/**
 * Called after OTP verification to set the patient portal session.
 */
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
}

$patientId = trim($_POST['patient_id'] ?? '');
if (!$patientId) {
    echo json_encode(['success' => false, 'message' => 'Missing patient ID.']); exit;
}

$patient = findPatientById($patientId);
if (!$patient) {
    echo json_encode(['success' => false, 'message' => 'Patient not found.']); exit;
}

$_SESSION['portal_patient_id']   = $patient['id'];
$_SESSION['portal_patient_name'] = trim(($patient['salutation'] ? $patient['salutation'] . '. ' : '') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? ''));

echo json_encode(['success' => true]);
