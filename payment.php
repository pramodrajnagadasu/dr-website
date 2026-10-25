<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
require_once 'lib/razorpay.php';
requirePatient();

$ref = trim($_GET['ref'] ?? '');
if (!$ref) { header('Location: patient-history.php'); exit; }

$appt = findAppointmentByRef($ref);
if (!$appt) {
    http_response_code(404);
    die('<h2 style="font-family:sans-serif;padding:40px;">Appointment not found.</h2>');
}
if ($appt['patient_id'] !== $_SESSION['portal_patient_id']) {
    http_response_code(403);
    die('<h2 style="font-family:sans-serif;padding:40px;">Access denied.</h2>');
}

$payment = findPaymentByRef($ref);
if (!$payment) {
    http_response_code(404);
    die('<h2 style="font-family:sans-serif;padding:40px;">No payment is due for this appointment.</h2>');
}

$patient      = findPatientById($appt['patient_id']);
$patientName  = $patient
    ? trim(($patient['salutation'] ? $patient['salutation'] . '. ' : '') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? ''))
    : 'Patient';
$amountRupees = number_format($payment['amount_paise'] / 100, 2);

$orderError = false;
if ($payment['status'] !== 'paid') {
    if (!RAZORPAY_ENABLED) {
        $orderError = 'Online payment is temporarily unavailable. Please contact the clinic.';
    } elseif (empty($payment['razorpay_order_id'])) {
        $order = razorpayCreateOrder($payment['amount_paise'], $ref);
        if ($order) {
            updatePaymentOrder($ref, $order['id']);
            $payment['razorpay_order_id'] = $order['id'];
        } else {
            $orderError = 'Could not start the payment. Please try again in a moment.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment — Dr. Prasanna Kumar K</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="portal.css" />
</head>
<body>

<div class="portal-bar">
  <div class="brand">
    <i class="fas fa-stethoscope"></i>
    Dr. Prasanna Kumar K
  </div>
  <div class="bar-right">
    <span style="font-size:13px;opacity:.7;">Welcome, <strong><?= htmlspecialchars($patientName) ?></strong></span>
    <a href="logout.php?role=patient" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<div class="portal-wrap narrow">
  <a href="patient-history.php" style="font-size:13px;color:var(--blue);display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;"><i class="fas fa-arrow-left"></i> My Appointments</a>

  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-file-invoice-dollar"></i> Consultation Payment</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;margin-bottom:20px;">
      <div><div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--gray);margin-bottom:3px;">Reference</div><div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($ref) ?></div></div>
      <div><div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--gray);margin-bottom:3px;">Date</div><div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($appt['appointment_date']) ?></div></div>
      <div><div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--gray);margin-bottom:3px;">Amount</div><div style="font-weight:700;color:var(--navy);font-size:18px;">₹<?= htmlspecialchars($amountRupees) ?></div></div>
    </div>

    <?php if ($payment['status'] === 'paid'): ?>
      <div class="form-msg success" style="display:block;">
        <i class="fas fa-check-circle"></i> Payment received. Thank you!
        <?php if (!empty($payment['razorpay_payment_id'])): ?>
          <div style="margin-top:6px;font-size:12px;opacity:.85;">Payment ID: <?= htmlspecialchars($payment['razorpay_payment_id']) ?></div>
        <?php endif; ?>
      </div>
    <?php elseif ($orderError): ?>
      <p class="form-msg error" style="display:block;"><?= htmlspecialchars($orderError) ?></p>
    <?php else: ?>
      <p class="form-msg error" id="payErr" style="display:none;"></p>
      <button type="button" class="submit-btn" id="payBtn" style="width:100%;justify-content:center;padding:14px;" onclick="startPayment()">
        <i class="fas fa-lock"></i> Pay ₹<?= htmlspecialchars($amountRupees) ?> Securely
      </button>
      <p style="font-size:12px;color:var(--gray);margin-top:12px;text-align:center;">Supports UPI, debit and credit cards via Razorpay. Card details are never seen by this site.</p>

      <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
      <script>
        function startPayment() {
          const btn = document.getElementById('payBtn');
          const errEl = document.getElementById('payErr');
          errEl.style.display = 'none';
          btn.disabled = true;

          const rzp = new Razorpay({
            key: <?= json_encode(RAZORPAY_KEY_ID) ?>,
            order_id: <?= json_encode($payment['razorpay_order_id']) ?>,
            amount: <?= (int) $payment['amount_paise'] ?>,
            currency: 'INR',
            name: 'Dr. Prasanna Kumar K',
            description: 'Consultation fee — <?= htmlspecialchars($ref, ENT_QUOTES) ?>',
            prefill: {
              name: <?= json_encode($patientName) ?>,
              email: <?= json_encode($patient['email'] ?? '') ?>,
              contact: <?= json_encode($patient['mobile'] ?? '') ?>
            },
            handler: async function (response) {
              try {
                const body = new URLSearchParams({
                  ref: <?= json_encode($ref) ?>,
                  razorpay_payment_id: response.razorpay_payment_id,
                  razorpay_order_id: response.razorpay_order_id,
                  razorpay_signature: response.razorpay_signature
                });
                const res = await fetch('verify-payment.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
                const data = await res.json();
                if (data.success) {
                  location.reload();
                } else {
                  errEl.textContent = data.message || 'Payment could not be verified. If money was deducted, contact the clinic with your reference number.';
                  errEl.style.display = 'block';
                  btn.disabled = false;
                }
              } catch (e) {
                errEl.textContent = 'Network error while verifying payment. If money was deducted, contact the clinic.';
                errEl.style.display = 'block';
                btn.disabled = false;
              }
            },
            modal: {
              ondismiss: function () { btn.disabled = false; }
            },
            theme: { color: '#1a5fb4' }
          });
          rzp.on('payment.failed', function (response) {
            errEl.textContent = 'Payment failed: ' + (response.error && response.error.description ? response.error.description : 'please try again.');
            errEl.style.display = 'block';
            btn.disabled = false;
          });
          rzp.open();
        }
      </script>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
