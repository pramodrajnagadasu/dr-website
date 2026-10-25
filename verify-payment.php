<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';
require_once 'lib/razorpay.php';
require_once 'mailer.php';
require_once 'config.php';

if (!isPatientLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized.']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]); exit;
}

$ref       = trim($_POST['ref'] ?? '');
$orderId   = trim($_POST['razorpay_order_id'] ?? '');
$paymentId = trim($_POST['razorpay_payment_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');

$appt = $ref ? findAppointmentByRef($ref) : null;
if (!$appt || $appt['patient_id'] !== $_SESSION['portal_patient_id']) {
    echo json_encode(['success' => false, 'message' => 'Not authorized.']); exit;
}

$payment = findPaymentByRef($ref);
if (!$payment || $payment['razorpay_order_id'] !== $orderId) {
    echo json_encode(['success' => false, 'message' => 'Payment record mismatch.']); exit;
}

if ($payment['status'] === 'paid') {
    echo json_encode(['success' => true, 'payment' => $payment]); exit;
}

if (!$orderId || !$paymentId || !$signature || !razorpayVerifyPaymentSignature($orderId, $paymentId, $signature)) {
    echo json_encode(['success' => false, 'message' => 'Payment signature could not be verified.']); exit;
}

$updated = markPaymentPaidByOrderId($orderId, $paymentId, $signature);
if (!$updated || $updated['status'] !== 'paid') {
    echo json_encode(['success' => false, 'message' => 'Could not record payment. Please contact the clinic.']); exit;
}

$patient      = findPatientById($appt['patient_id']);
$patientName  = $patient
    ? trim(($patient['salutation'] ? $patient['salutation'] . '. ' : '') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? ''))
    : 'Patient';
$amountRupees = number_format($updated['amount_paise'] / 100, 2);

if ($patient && !empty($patient['email'])) {
    sendPaymentReceivedEmail($patient['email'], $ref, $patientName, $amountRupees, $paymentId);
}
sendPaymentReceivedEmail(DOCTOR_NOTIFY_EMAIL, $ref, $patientName, $amountRupees, $paymentId);

echo json_encode(['success' => true, 'payment' => $updated]);
