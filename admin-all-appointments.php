<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
requireDoctor();

$patients     = getAllPatients();
$appointments = getAllAppointments();

$patientMap = [];
foreach ($patients as $p) $patientMap[$p['id']] = $p;

usort($appointments, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

function apptTs(array $a): int {
    [$d, $m, $y] = explode('-', $a['appointment_date']);
    return mktime(0, 0, 0, (int) $m, (int) $d, (int) $y);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>All Appointments — Dr. Prasanna Kumar K</title>
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

<div class="portal-wrap portal-wrap-full">

  <!-- Page title -->
  <div class="page-title">
    <h2>All Appointments</h2>
    <p><?= count($appointments) ?> appointment<?= count($appointments) !== 1 ? 's' : '' ?> across every patient</p>
  </div>

  <!-- Search + Sort -->
  <div class="table-toolbar">
    <div class="search-box" style="margin-bottom:0;flex:1;">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Search by patient name, mobile, or ref number…" oninput="filterAppts()" />
    </div>
    <select id="sortSelect" onchange="sortAppts()">
      <option value="created-desc">Sort: Newest booked first</option>
      <option value="created-asc">Sort: Oldest booked first</option>
      <option value="date-asc">Sort: Appointment date ↑</option>
      <option value="date-desc">Sort: Appointment date ↓</option>
      <option value="name-asc">Sort: Patient name A–Z</option>
      <option value="status">Sort: Status</option>
    </select>
  </div>

  <!-- Appointment table -->
  <div class="card" style="padding:0;overflow-x:auto;">
    <table class="appt-table" id="apptTable">
      <thead>
        <tr>
          <th>Patient</th>
          <th>Ref</th>
          <th>Date</th>
          <th>Time Slot</th>
          <th>Status</th>
          <th>Record</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="apptTableBody">
        <?php if (empty($appointments)): ?>
          <tr><td colspan="7" style="color:var(--gray);font-size:13px;text-align:center;padding:32px;">No appointments yet.</td></tr>
        <?php else: ?>
          <?php foreach ($appointments as $a):
            $patient     = $patientMap[$a['patient_id']] ?? null;
            $patientName = $patient
                ? trim(($patient['salutation'] ? $patient['salutation'] . '. ' : '') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? ''))
                : 'Unknown Patient';
            $status      = $a['status'] ?? 'confirmed';
            $statusLabel = $status === 'confirmed' ? 'Open' : ucfirst($status);
            $reason      = '';
            if ($status === 'cancelled' && !empty($a['cancel_reason'])) {
                $reason = $a['cancel_reason'];
            } elseif ($status === 'closed' && !empty($a['close_reason'])) {
                $reason = $a['close_reason'];
            } elseif ($status === 'postponed' && !empty($a['reschedule_history'])) {
                $lastR  = end($a['reschedule_history']);
                $reason = 'Rescheduled from ' . $lastR['from_date'] . ', ' . $lastR['from_slot'] . (!empty($lastR['reason']) ? ' — ' . $lastR['reason'] : '');
            }
            $payment = findPaymentByRef($a['ref_number']);
          ?>
          <tr data-name="<?= htmlspecialchars(strtolower($patientName)) ?>"
              data-mobile="<?= htmlspecialchars($patient['mobile'] ?? '') ?>"
              data-ref="<?= htmlspecialchars(strtolower($a['ref_number'])) ?>"
              data-created="<?= htmlspecialchars($a['created_at']) ?>"
              data-date-ts="<?= apptTs($a) ?>"
              data-status="<?= htmlspecialchars($status) ?>">
            <td class="appt-patient"><?= htmlspecialchars($patientName) ?></td>
            <td><span class="appt-ref"><?= htmlspecialchars($a['ref_number']) ?></span></td>
            <td><?= htmlspecialchars($a['appointment_date']) ?></td>
            <td><?= htmlspecialchars($a['time_slot']) ?></td>
            <td>
              <span class="status-badge status-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($statusLabel) ?></span>
              <?php if ($payment): $payAmount = number_format($payment['amount_paise'] / 100, 2); ?>
                <span class="status-badge <?= $payment['status'] === 'paid' ? 'status-paid' : 'status-payment-pending' ?>">
                  <?= $payment['status'] === 'paid' ? 'Paid' : 'Due' ?> ₹<?= htmlspecialchars($payAmount) ?>
                </span>
              <?php endif; ?>
              <?php if ($reason !== ''): ?>
                <div class="appt-reason" style="flex-basis:auto;padding-top:6px;"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($reason) ?></div>
              <?php endif; ?>
            </td>
            <td><a href="record.php?ref=<?= urlencode($a['ref_number']) ?>" class="view-btn"><i class="fas fa-folder-open"></i> View Record</a></td>
            <td>
              <?php if ($status !== 'cancelled' && $status !== 'closed'): ?>
              <div class="appt-actions">
                <button type="button" class="action-btn postpone-btn"
                        onclick="openPostponeModal('<?= htmlspecialchars($a['ref_number'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['doctor'], ENT_QUOTES) ?>')">
                  <i class="fas fa-calendar-day"></i> Postpone
                </button>
                <button type="button" class="action-btn cancel-btn"
                        onclick="openCancelModal('<?= htmlspecialchars($a['ref_number'], ENT_QUOTES) ?>')">
                  <i class="fas fa-times-circle"></i> Cancel
                </button>
                <button type="button" class="action-btn close-btn"
                        onclick="openCloseModal('<?= htmlspecialchars($a['ref_number'], ENT_QUOTES) ?>', '<?= htmlspecialchars($patientName, ENT_QUOTES) ?>', '<?= htmlspecialchars($a['appointment_date'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['time_slot'], ENT_QUOTES) ?>')">
                  <i class="fas fa-check-circle"></i> Close Case
                </button>
              </div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div><!-- /portal-wrap -->

<?php include 'admin-appointment-modals.php'; ?>

<script src="admin-appointments.js"></script>
<script>
function filterAppts() {
  const q = document.getElementById('searchInput').value.toLowerCase().trim();
  document.querySelectorAll('#apptTableBody tr[data-name]').forEach(row => {
    const name   = row.dataset.name   || '';
    const mobile = row.dataset.mobile || '';
    const ref    = row.dataset.ref    || '';
    row.style.display = (!q || name.includes(q) || mobile.includes(q) || ref.includes(q)) ? '' : 'none';
  });
}

function sortAppts() {
  const mode = document.getElementById('sortSelect').value;
  const tbody = document.getElementById('apptTableBody');
  const rows = Array.from(tbody.querySelectorAll('tr[data-name]'));
  if (!rows.length) return;

  const cmp = {
    'created-desc': (a, b) => b.dataset.created.localeCompare(a.dataset.created),
    'created-asc':  (a, b) => a.dataset.created.localeCompare(b.dataset.created),
    'date-asc':     (a, b) => Number(a.dataset.dateTs) - Number(b.dataset.dateTs),
    'date-desc':    (a, b) => Number(b.dataset.dateTs) - Number(a.dataset.dateTs),
    'name-asc':     (a, b) => a.dataset.name.localeCompare(b.dataset.name),
    'status':       (a, b) => a.dataset.status.localeCompare(b.dataset.status),
  }[mode];

  rows.sort(cmp).forEach(row => tbody.appendChild(row));
}
</script>
</body>
</html>
