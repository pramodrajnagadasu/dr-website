<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
requirePatient();

$patientId   = $_SESSION['portal_patient_id'];
$patientName = $_SESSION['portal_patient_name'] ?? 'Patient';
$patient     = findPatientById($patientId);
$appointments = findAppointmentsByPatient($patientId);
$paymentsByRef = [];
foreach ($appointments as $a) {
    $p = findPaymentByRef($a['ref_number']);
    if ($p) $paymentsByRef[$a['ref_number']] = $p;
}

$today = mktime(0,0,0,date('m'),date('d'),date('Y'));

function parseDate(string $dmy): int {
    [$d,$m,$y] = explode('-', $dmy);
    return mktime(0,0,0,(int)$m,(int)$d,(int)$y);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Appointments — Dr. Prasanna Kumar K</title>
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

<div class="portal-wrap">

  <?php if ($patient): ?>
  <!-- Patient info card -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-user-circle"></i> My Profile</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
      <div><div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--gray);margin-bottom:3px;">Name</div><div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars(trim(($patient['salutation'] ? $patient['salutation'].'. ':'').$patient['first_name'].' '.($patient['last_name']??''))) ?></div></div>
      <div><div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--gray);margin-bottom:3px;">Gender</div><div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($patient['gender'] ?? '—') ?></div></div>
      <div><div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--gray);margin-bottom:3px;">Date of Birth</div><div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($patient['dob'] ?? '—') ?></div></div>
      <div><div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;font-weight:700;color:var(--gray);margin-bottom:3px;">Mobile</div><div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($patient['mobile'] ?? '—') ?></div></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Appointments -->
  <div class="page-title">
    <h2>My Appointments</h2>
    <p><?= count($appointments) ?> appointment<?= count($appointments) !== 1 ? 's' : '' ?> found</p>
  </div>

  <?php if (empty($appointments)): ?>
    <div class="card" style="text-align:center;padding:56px 24px;">
      <i class="fas fa-calendar-times" style="font-size:44px;color:var(--border);display:block;margin-bottom:16px;"></i>
      <p style="color:var(--gray);font-size:15px;">No appointments found.</p>
      <a href="appointment.php" style="display:inline-block;margin-top:16px;padding:10px 24px;background:linear-gradient(135deg,var(--blue),var(--blue-viv));color:#fff;border-radius:10px;font-weight:600;font-size:14px;">Book an Appointment</a>
    </div>
  <?php else: ?>
    <?php foreach ($appointments as $a):
      $apptTs   = parseDate($a['appointment_date']);
      $upcoming = $apptTs >= $today;
      $status   = $a['status'] ?? 'confirmed';
      $payment  = $paymentsByRef[$a['ref_number']] ?? null;
    ?>
    <div class="appt-card" style="flex-wrap:wrap;">
      <div class="ref-badge"><?= htmlspecialchars($a['ref_number']) ?></div>
      <div class="appt-info">
        <div class="date">
          <i class="fas fa-calendar-alt" style="color:var(--blue);margin-right:6px;"></i>
          <?= htmlspecialchars($a['appointment_date']) ?>
          <?php if ($status === 'cancelled'): ?>
            <span class="status-badge status-cancelled">Cancelled</span>
          <?php elseif ($status === 'postponed'): ?>
            <span class="status-badge status-postponed">Rescheduled</span>
          <?php else: ?>
            <?= $upcoming ? '<span class="badge-upcoming">Upcoming</span>' : '<span class="badge-past">Past</span>' ?>
          <?php endif; ?>
        </div>
        <div class="slot" style="margin-top:4px;">
          <i class="fas fa-clock" style="color:var(--gray);margin-right:6px;"></i>
          <?= htmlspecialchars($a['time_slot']) ?>
          &nbsp;&bull;&nbsp;Dr. Prasanna Kumar K
        </div>
        <?php if ($status === 'cancelled' && !empty($a['cancel_reason'])): ?>
          <div class="appt-reason" style="margin-top:6px;"><i class="fas fa-info-circle"></i> Reason: <?= htmlspecialchars($a['cancel_reason']) ?></div>
        <?php elseif ($status === 'postponed' && !empty($a['reschedule_history'])): $lastR = end($a['reschedule_history']); ?>
          <div class="appt-reason" style="margin-top:6px;"><i class="fas fa-info-circle"></i> Rescheduled from <?= htmlspecialchars($lastR['from_date']) ?>, <?= htmlspecialchars($lastR['from_slot']) ?><?= !empty($lastR['reason']) ? ' — ' . htmlspecialchars($lastR['reason']) : '' ?></div>
        <?php endif; ?>
        <?php if ($payment): $payAmount = number_format($payment['amount_paise'] / 100, 2); ?>
          <div style="margin-top:6px;">
            <?php if ($payment['status'] === 'paid'): ?>
              <span class="status-badge status-paid"><i class="fas fa-check-circle"></i> Paid ₹<?= htmlspecialchars($payAmount) ?></span>
            <?php else: ?>
              <span class="status-badge status-payment-pending">Payment Pending — ₹<?= htmlspecialchars($payAmount) ?></span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <?php if ($payment && $payment['status'] !== 'paid'): ?>
        <a href="payment.php?ref=<?= urlencode($a['ref_number']) ?>" class="pay-now-btn">
          <i class="fas fa-credit-card"></i> Pay Now
        </a>
      <?php endif; ?>
      <a href="record.php?ref=<?= urlencode($a['ref_number']) ?>" class="view-record-btn">
        <i class="fas fa-folder-open"></i> View Record
      </a>
    </div>
    <?php endforeach; ?>

    <div style="text-align:center;margin-top:24px;">
      <a href="appointment.php" style="font-size:14px;color:var(--blue);font-weight:600;"><i class="fas fa-plus-circle"></i> Book New Appointment</a>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
