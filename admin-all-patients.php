<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
requireDoctor();

$patients     = getAllPatients();
$appointments = getAllAppointments();

$apptsByPatient = [];
foreach ($appointments as $a) {
    $apptsByPatient[$a['patient_id']][] = $a;
}
foreach ($apptsByPatient as &$appts) {
    usort($appts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
}
unset($appts);

$visiblePatients = [];
foreach ($patients as $p) {
    $visiblePatients[] = [$p, $apptsByPatient[$p['id']] ?? []];
}

$emptyIcon    = 'fa-users';
$emptyMessage = 'No patients registered yet.';
$showActions  = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>All Patients — Dr. Prasanna Kumar K</title>
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
    <h2>All Patients</h2>
    <p><?= count($patients) ?> registered patient<?= count($patients) !== 1 ? 's' : '' ?> and their full appointment history</p>
  </div>

  <?php include 'admin-patient-list.php'; ?>

</div><!-- /portal-wrap -->

<?php include 'admin-appointment-modals.php'; ?>

<script src="admin-appointments.js"></script>
</body>
</html>
