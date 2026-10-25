<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/razorpay.php';
require_once __DIR__ . '/../mailer.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Invalid request.', 405);

$patient = apiRequirePatient();

$ref       = trim($_POST['ref'] ?? '');
$orderId   = trim($_POST['razorpay_order_id'] ?? '');
$paymentId = trim($_POST['razorpay_payment_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');

$appt = $ref ? findAppointmentByRef($ref) : null;
if (!$appt || $appt['patient_id'] !== $patient['id']) apiError('Not authorized.', 403);

$payment = findPaymentByRef($ref);
if (!$payment || $payment['razorpay_order_id'] !== $orderId) apiError('Payment record mismatch.');

if ($payment['status'] === 'paid') {
    apiJson(['success' => true, 'payment' => $payment]);
}

if (!$orderId || !$paymentId || !$signature || !razorpayVerifyPaymentSignature($orderId, $paymentId, $signature)) {
    apiError('Payment signature could not be verified.');
}

$updated = markPaymentPaidByOrderId($orderId, $paymentId, $signature);
if (!$updated || $updated['status'] !== 'paid') {
    apiError('Could not record payment. Please contact the clinic.');
}

$patientName  = trim(($patient['salutation'] ? $patient['salutation'] . '. ' : '') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? ''));
$amountRupees = number_format($updated['amount_paise'] / 100, 2);

if (!empty($patient['email'])) {
    sendPaymentReceivedEmail($patient['email'], $ref, $patientName, $amountRupees, $paymentId);
}
sendPaymentReceivedEmail(DOCTOR_NOTIFY_EMAIL, $ref, $patientName, $amountRupees, $paymentId);

apiJson(['success' => true, 'payment' => $updated]);
