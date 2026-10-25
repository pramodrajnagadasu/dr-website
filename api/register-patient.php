<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Invalid request.', 405);

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
    apiError('Please fill all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiError('Please enter a valid email address — verification codes are sent there.');
}
if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $dob)) {
    apiError('Date of birth must be in DD-MM-YYYY format.');
}

$patient = savePatient(compact('salutation', 'first_name', 'last_name', 'gender', 'dob', 'mobile', 'email'));

apiJson(['success' => true, 'patient_id' => $patient['id']]);
