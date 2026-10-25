<?php
/**
 * Server-to-server fallback for Razorpay payment confirmation. Registered as
 * a webhook URL in the Razorpay Dashboard (Settings → Webhooks), subscribed
 * to the "payment.captured" event. Exists because the client-side Checkout.js
 * callback in payment.php can be missed (browser closed, network drop)
 * even after the payment succeeded on Razorpay's side.
 *
 * No session/login here — trust is established purely via the HMAC
 * signature on the raw body, signed with RAZORPAY_WEBHOOK_SECRET.
 */
require_once 'db.php';
require_once 'lib/razorpay.php';
require_once 'mailer.php';
require_once 'config.php';

header('Content-Type: application/json');

$body      = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (!$signature || !razorpayVerifyWebhookSignature($body, $signature)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']); exit;
}

$payload = json_decode($body, true);
if (($payload['event'] ?? '') !== 'payment.captured') {
    echo json_encode(['status' => 'ignored']); exit; // not an event we act on
}

$entity    = $payload['payload']['payment']['entity'] ?? [];
$orderId   = $entity['order_id'] ?? '';
$paymentId = $entity['id'] ?? '';

if (!$orderId || !$paymentId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order/payment id']); exit;
}

$payment = findPaymentByOrderId($orderId);
if (!$payment) {
    echo json_encode(['status' => 'unknown_order']); exit;
}
if ($payment['status'] === 'paid') {
    echo json_encode(['status' => 'already_paid']); exit; // idempotent — no duplicate email
}

// No per-payment signature is provided in the webhook payload the way
// Checkout.js gives one — the webhook HMAC already verified above (over the
// whole body) is what authenticates this call, so null is stored here.
$updated = markPaymentPaidByOrderId($orderId, $paymentId, null);
if (!$updated || $updated['status'] !== 'paid') {
    http_response_code(500);
    echo json_encode(['error' => 'Could not update payment']); exit;
}

$appt = findAppointmentByRef($updated['appointment_ref']);
$patient = $appt ? findPatientById($appt['patient_id']) : null;
$patientName = $patient
    ? trim(($patient['salutation'] ? $patient['salutation'] . '. ' : '') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? ''))
    : 'Patient';
$amountRupees = number_format($updated['amount_paise'] / 100, 2);

if ($patient && !empty($patient['email'])) {
    sendPaymentReceivedEmail($patient['email'], $updated['appointment_ref'], $patientName, $amountRupees, $paymentId);
}
sendPaymentReceivedEmail(DOCTOR_NOTIFY_EMAIL, $updated['appointment_ref'], $patientName, $amountRupees, $paymentId);

echo json_encode(['status' => 'ok']);
