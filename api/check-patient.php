<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Invalid request.', 405);

$mobile = preg_replace('/\D/', '', trim($_POST['mobile'] ?? ''));
if (strlen($mobile) === 12 && substr($mobile, 0, 2) === '91') {
    $mobile = substr($mobile, 2);
}
if (strlen($mobile) !== 10) {
    apiError('Invalid mobile number.');
}

$patients = findPatientsByMobile($mobile);
if (empty($patients)) {
    apiJson(['exists' => false]);
}

$list = array_map(fn($p) => [
    'id'   => $p['id'],
    'name' => strtoupper(trim($p['first_name'] . ' ' . $p['last_name'])),
    'gender' => $p['gender'] ?? null,
    'dob'    => $p['dob'] ?? null,
], $patients);

apiJson(['exists' => true, 'patients' => $list]);
