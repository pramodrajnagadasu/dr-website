<?php
/** JSON equivalent of payment.php's PHP half — order creation/lookup, no HTML. */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/razorpay.php';
require_once __DIR__ . '/../lib/api_auth.php';

$patient = apiRequirePatient();

$ref = trim($_GET['ref'] ?? '');
if (!$ref) apiError('Missing appointment reference.');

$appt = findAppointmentByRef($ref);
if (!$appt) apiError('Appointment not found.', 404);
if ($appt['patient_id'] !== $patient['id']) apiError('Access denied.', 403);

$payment = findPaymentByRef($ref);
if (!$payment) apiError('No payment is due for this appointment.', 404);

if ($payment['status'] !== 'paid') {
    if (!RAZORPAY_ENABLED) {
        apiError('Online payment is temporarily unavailable. Please contact the clinic.');
    }
    if (empty($payment['razorpay_order_id'])) {
        $order = razorpayCreateOrder((int) $payment['amount_paise'], $ref);
        if (!$order) apiError('Could not start the payment. Please try again in a moment.');
        updatePaymentOrder($ref, $order['id']);
        $payment['razorpay_order_id'] = $order['id'];
    }
}

apiJson([
    'ref_number'        => $ref,
    'status'            => $payment['status'],
    'amount_paise'      => (int) $payment['amount_paise'],
    'currency'          => $payment['currency'],
    'razorpay_order_id' => $payment['razorpay_order_id'],
    'razorpay_key_id'   => defined('RAZORPAY_KEY_ID') ? RAZORPAY_KEY_ID : null,
    'razorpay_payment_id' => $payment['razorpay_payment_id'] ?? null,
    'patient_name'      => trim(($patient['salutation'] ? $patient['salutation'] . '. ' : '') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? '')),
    'patient_email'     => $patient['email'] ?? '',
    'patient_mobile'    => $patient['mobile'] ?? '',
]);
