<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
requireDoctor();

$appointments = getAllAppointments();
$patients     = getAllPatients();
$patientMap   = [];
foreach ($patients as $p) $patientMap[$p['id']] = $p;

// Count non-cancelled appointments per Y-M-D
$counts = [];
foreach ($appointments as $a) {
    if (($a['status'] ?? 'confirmed') === 'cancelled') continue;
    [$d, $m, $y] = explode('-', $a['appointment_date']);
    $key = (int) $y . '-' . (int) $m . '-' . (int) $d;
    $counts[$key] = ($counts[$key] ?? 0) + 1;
}

$todayY = (int) date('Y');
$todayM = (int) date('n');
$todayD = (int) date('j');

$startY = isset($_GET['year'])  ? (int) $_GET['year']  : $todayY;
$startM = isset($_GET['month']) ? (int) $_GET['month'] : $todayM;
if ($startM < 1 || $startM > 12) $startM = $todayM;

$monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$yearOptions = range($todayY - 1, $todayY + 2);

function buildMonthCells(int $year, int $month, array $counts, int $todayY, int $todayM, int $todayD): array {
    $daysInMonth  = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
    $startWeekday = (int) date('w', mktime(0, 0, 0, $month, 1, $year));
    $cells = array_fill(0, $startWeekday, null);
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $cells[] = [
            'day'     => $d,
            'count'   => $counts["$year-$month-$d"] ?? 0,
            'isToday' => $year === $todayY && $month === $todayM && $d === $todayD,
        ];
    }
    return $cells;
}

$monthsToRender = [];
$y = $startY; $m = $startM;
for ($i = 0; $i < 12; $i++) {
    $monthsToRender[] = [$y, $m];
    $m++;
    if ($m > 12) { $m = 1; $y++; }
}

// Day-detail view: ?date=YYYY-M-D
$dayAppts = null;
$dayLabel = null;
if (!empty($_GET['date']) && preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $_GET['date'], $mt)) {
    [, $dy, $dm, $dd] = $mt;
    $dateStr  = sprintf('%02d-%02d-%04d', (int) $dd, (int) $dm, (int) $dy);
    $dayLabel = date('d F Y', mktime(0, 0, 0, (int) $dm, (int) $dd, (int) $dy));
    $dayAppts = array_values(array_filter($appointments, fn($a) => $a['appointment_date'] === $dateStr));
    usort($dayAppts, fn($a, $b) => strcmp($a['time_slot'], $b['time_slot']));
}
$backUrl = '?month=' . $startM . '&year=' . $startY;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Appointment Calendar — Dr. Prasanna Kumar K</title>
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
  <div class="page-title" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div>
      <h2>Appointment Calendar</h2>
      <p>Number of bookings per day, excluding cancelled appointments</p>
    </div>
    <form method="GET" id="jumpForm" class="cal-jump-form">
      <select name="month" onchange="document.getElementById('jumpForm').submit()">
        <?php foreach ($monthNames as $idx => $mn): ?>
          <option value="<?= $idx + 1 ?>" <?= ($idx + 1) === $startM ? 'selected' : '' ?>><?= $mn ?></option>
        <?php endforeach; ?>
      </select>
      <select name="year" onchange="document.getElementById('jumpForm').submit()">
        <?php foreach ($yearOptions as $yr): ?>
          <option value="<?= $yr ?>" <?= $yr === $startY ? 'selected' : '' ?>><?= $yr ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <?php if ($dayAppts !== null): ?>

  <!-- Day detail view -->
  <div class="card" style="margin-top:24px;">
    <div class="card-header">
      <div class="card-title"><i class="fas fa-calendar-day"></i> Bookings — <?= htmlspecialchars($dayLabel) ?></div>
      <a href="<?= htmlspecialchars($backUrl) ?>" class="btn-cta"><i class="fas fa-arrow-left"></i> Back to Calendar</a>
    </div>

    <?php if (empty($dayAppts)): ?>
      <p style="color:var(--gray);text-align:center;padding:24px;">No bookings on this day.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="appt-table">
          <thead>
            <tr>
              <th>Patient</th>
              <th>Mobile</th>
              <th>Ref</th>
              <th>Time Slot</th>
              <th>Status</th>
              <th>Record</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dayAppts as $a):
              $status      = $a['status'] ?? 'confirmed';
              $statusLabel = $status === 'confirmed' ? 'Open' : ucfirst($status);
              $patient     = $patientMap[$a['patient_id']] ?? null;
              $patientName = $patient
                  ? trim(($patient['salutation'] ? $patient['salutation'] . '. ' : '') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? ''))
                  : 'Unknown Patient';
              $reason = '';
              if ($status === 'cancelled' && !empty($a['cancel_reason'])) {
                  $reason = $a['cancel_reason'];
              } elseif ($status === 'closed' && !empty($a['close_reason'])) {
                  $reason = $a['close_reason'];
              }
            ?>
            <tr>
              <td class="appt-patient"><?= htmlspecialchars($patientName) ?></td>
              <td><?= htmlspecialchars($patient['mobile'] ?? '—') ?></td>
              <td><span class="appt-ref"><?= htmlspecialchars($a['ref_number']) ?></span></td>
              <td><?= htmlspecialchars($a['time_slot']) ?></td>
              <td>
                <span class="status-badge status-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($statusLabel) ?></span>
                <?php if ($reason !== ''): ?>
                  <div class="appt-reason" style="flex-basis:auto;padding-top:6px;"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($reason) ?></div>
                <?php endif; ?>
              </td>
              <td><a href="record.php?ref=<?= urlencode($a['ref_number']) ?>" class="view-btn"><i class="fas fa-folder-open"></i> View Record</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?php else: ?>

  <div class="calendar-multi-grid view-year">
    <?php foreach ($monthsToRender as [$y, $m]):
      $label = date('F Y', mktime(0, 0, 0, $m, 1, $y));
      $cells = buildMonthCells($y, $m, $counts, $todayY, $todayM, $todayD);
    ?>
    <div class="card calendar-card mini">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($label) ?></div>
      </div>
      <div class="calendar-grid">
        <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $wd): ?>
          <div class="cal-weekday"><?= $wd ?></div>
        <?php endforeach; ?>
        <?php foreach ($cells as $cell): ?>
          <?php if ($cell === null): ?>
            <div class="cal-cell empty"></div>
          <?php elseif ($cell['count'] > 0): ?>
            <a class="cal-cell<?= $cell['isToday'] ? ' is-today' : '' ?> has-appts" href="?date=<?= $y ?>-<?= $m ?>-<?= $cell['day'] ?>&month=<?= $startM ?>&year=<?= $startY ?>">
              <span class="cal-count"><?= $cell['count'] ?></span>
              <span class="cal-day"><?= $cell['day'] ?></span>
            </a>
          <?php else: ?>
            <div class="cal-cell<?= $cell['isToday'] ? ' is-today' : '' ?>">
              <span class="cal-day"><?= $cell['day'] ?></span>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>

</div><!-- /portal-wrap -->

</body>
</html>
