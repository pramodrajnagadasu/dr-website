<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['exists' => false]); exit;
}

$mobile = preg_replace('/\D/', '', trim($_POST['mobile'] ?? ''));
if (strlen($mobile) === 12 && substr($mobile, 0, 2) === '91') {
    $mobile = substr($mobile, 2);
}
if (strlen($mobile) !== 10) {
    echo json_encode(['exists' => false, 'message' => 'Invalid mobile number.']); exit;
}

$patients = findPatientsByMobile($mobile);
if (!empty($patients)) {
    $_SESSION['patient_mobile'] = $mobile;
    $list = array_map(fn($p) => [
        'id'   => $p['id'],
        'name' => strtoupper(trim($p['first_name'] . ' ' . $p['last_name'])),
    ], $patients);
    echo json_encode(['exists' => true, 'patients' => $list]);
} else {
    echo json_encode(['exists' => false]);
}
