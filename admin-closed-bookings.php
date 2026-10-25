<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
requireDoctor();

$patients     = getAllPatients();
$appointments = getAllAppointments();

$isClosedOrCancelled = fn($a) => in_array($a['status'] ?? 'confirmed', ['cancelled', 'closed'], true);

// Group closed/cancelled appointments by patient_id
$apptsByPatient = [];
foreach ($appointments as $a) {
    if (!$isClosedOrCancelled($a)) continue;
    $apptsByPatient[$a['patient_id']][] = $a;
}
foreach ($apptsByPatient as &$appts) {
    usort($appts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
}
unset($appts);

$visiblePatients = [];
foreach ($patients as $p) {
    if (empty($apptsByPatient[$p['id']])) continue;
    $visiblePatients[] = [$p, $apptsByPatient[$p['id']]];
}

$totalClosed = count(array_filter($appointments, $isClosedOrCancelled));

$emptyIcon    = 'fa-box-archive';
$emptyMessage = 'No closed or cancelled bookings yet.';
$showActions  = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Closed Bookings — Dr. Prasanna Kumar K</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="portal.css" />
</head>
<body>

<!-- Top bar -->
<div class="portal-bar">
  <div class="brand">
    <i class="fas fa-stethoscope"></i>
    Dr. Prasanna Kumar K &mdash; Admin
  </div>
  <div class="bar-right">
    <a href="admin-dashboard.php" class="logout-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  </div>
</div>

<div class="portal-wrap">

  <!-- Page title -->
  <div class="page-title">
    <h2>Closed Bookings</h2>
    <p><?= $totalClosed ?> closed or cancelled appointment<?= $totalClosed !== 1 ? 's' : '' ?></p>
  </div>

  <?php include 'admin-patient-list.php'; ?>

</div><!-- /portal-wrap -->

<script src="admin-appointments.js"></script>
</body>
</html>
