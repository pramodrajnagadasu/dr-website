<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]); exit;
}

$salutation = trim($_POST['salutation'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name']  ?? '');
$gender     = trim($_POST['gender']     ?? '');
$dob        = trim($_POST['dob']        ?? '');
$mobile     = preg_replace('/\D/', '', trim($_POST['mobile'] ?? ''));
$email      = trim($_POST['email']      ?? '');

if (strlen($mobile) === 12 && substr($mobile, 0, 2) === '91') {
    $mobile = substr($mobile, 2);
}

if (!$first_name || !$gender || !$dob || strlen($mobile) !== 10 || !$email) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address — verification codes are sent there.']); exit;
}
if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $dob)) {
    echo json_encode(['success' => false, 'message' => 'Date of birth must be in DD-MM-YYYY format.']); exit;
}

$patient = savePatient(compact('salutation', 'first_name', 'last_name', 'gender', 'dob', 'mobile', 'email'));

$_SESSION['patient_id']     = $patient['id'];
$_SESSION['patient_mobile'] = $mobile;

echo json_encode(['success' => true, 'patient_id' => $patient['id']]);
