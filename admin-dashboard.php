<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
requireDoctor();

$patients     = getAllPatients();
$appointments = getAllAppointments();

// Group appointments by patient_id
$apptsByPatient = [];
foreach ($appointments as $a) {
    $apptsByPatient[$a['patient_id']][] = $a;
}
// Sort each patient's appointments newest first
foreach ($apptsByPatient as &$appts) {
    usort($appts, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
}
unset($appts);

$todayStr = date('d-m-Y');
$todayTs  = mktime(0, 0, 0, (int) date('m'), (int) date('d'), (int) date('Y'));

function apptTs(array $a): int {
    [$d, $m, $y] = explode('-', $a['appointment_date']);
    return mktime(0, 0, 0, (int) $m, (int) $d, (int) $y);
}

// Landing page shows today's bookings + still-open bookings from before today (backlog).
// Future (tomorrow or later) bookings live on the Upcoming page; closed/cancelled (any date)
// live on the Closed Bookings page.
$isTodayOrPastOpen = function (array $a) use ($todayStr, $todayTs) {
    if ($a['appointment_date'] === $todayStr) return true;
    if (in_array($a['status'] ?? 'confirmed', ['cancelled', 'closed'], true)) return false;
    return apptTs($a) < $todayTs;
};

$visiblePatients = [];
foreach ($patients as $p) {
    $filtered = array_values(array_filter($apptsByPatient[$p['id']] ?? [], $isTodayOrPastOpen));
    $allPatAppts = $apptsByPatient[$p['id']] ?? [];
    // Patient has appointments, but none qualify — belongs on Upcoming/Closed, not here.
    if (!empty($allPatAppts) && empty($filtered)) continue;
    $visiblePatients[] = [$p, $filtered];
}

// Stats
$totalPatients    = count($patients);
$totalAppts       = count($appointments);
$todayAppts       = count(array_filter($appointments, fn($a) => $a['appointment_date'] === $todayStr && !in_array($a['status'] ?? 'confirmed', ['cancelled', 'closed'], true)));
$upcomingAppts    = count(array_filter($appointments, function($a) use ($todayStr) {
    if (in_array($a['status'] ?? 'confirmed', ['cancelled', 'closed'], true)) return false;
    // Parse DD-MM-YYYY
    [$d,$m,$y] = explode('-', $a['appointment_date']);
    return mktime(0,0,0,(int)$m,(int)$d,(int)$y) >= mktime(0,0,0,date('m'),date('d'),date('Y'));
}));

$emptyIcon    = 'fa-calendar-check';
$emptyMessage = 'No bookings for today, and nothing still open from earlier.';
$showActions  = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard — Dr. Prasanna Kumar K</title>
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
    <span style="font-size:13px;opacity:.7;">Logged in as <strong><?= htmlspecialchars($_SESSION['doctor_username'] ?? 'Doctor') ?></strong></span>
    <a href="logout.php?role=doctor" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</div>

<div class="portal-wrap">

  <!-- Page title -->
  <div class="page-title" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div>
      <h2>Today's Appointments</h2>
      <p>Bookings for today, <?= htmlspecialchars($todayStr) ?>, plus still-open bookings from earlier</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a href="admin-upcoming-bookings.php" target="_blank" rel="noopener" class="btn-cta btn-cta-muted">
        <i class="fas fa-hourglass-half"></i> Upcoming
      </a>
      <a href="admin-calendar.php" target="_blank" rel="noopener" class="btn-cta">
        <i class="fas fa-calendar-alt"></i> All Bookings
      </a>
      <a href="admin-closed-bookings.php" target="_blank" rel="noopener" class="btn-cta btn-cta-muted">
        <i class="fas fa-box-archive"></i> Closed Bookings
      </a>
      <a href="admin-schedule.php" target="_blank" rel="noopener" class="btn-cta btn-cta-muted">
        <i class="fas fa-calendar-week"></i> Schedule
      </a>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <a href="admin-all-patients.php" target="_blank" rel="noopener" class="stat-box">
      <div class="stat-val"><?= $totalPatients ?></div>
      <div class="stat-lbl">Total Patients</div>
    </a>
    <a href="admin-all-appointments.php" target="_blank" rel="noopener" class="stat-box">
      <div class="stat-val"><?= $totalAppts ?></div>
      <div class="stat-lbl">Total Appointments</div>
    </a>
    <a href="admin-dashboard.php" class="stat-box">
      <div class="stat-val"><?= $todayAppts ?></div>
      <div class="stat-lbl">Today's Appointments</div>
    </a>
    <a href="admin-upcoming-bookings.php" target="_blank" rel="noopener" class="stat-box">
      <div class="stat-val"><?= $upcomingAppts ?></div>
      <div class="stat-lbl">Upcoming</div>
    </a>
  </div>

  <?php include 'admin-patient-list.php'; ?>

</div><!-- /portal-wrap -->

<?php include 'admin-appointment-modals.php'; ?>

<script src="admin-appointments.js"></script>
</body>
</html>
