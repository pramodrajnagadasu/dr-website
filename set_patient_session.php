<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]); exit;
}

$patient_id = trim($_POST['patient_id'] ?? '');
$patient    = findPatientById($patient_id);

if ($patient) {
    $_SESSION['patient_id']     = $patient['id'];
    $_SESSION['patient_mobile'] = $patient['mobile'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Patient not found.']);
}
