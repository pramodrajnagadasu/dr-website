<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
requireDoctor();

$dayNames  = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$availRows = getDoctorAvailability();
$enabledByDay = [];
foreach ($availRows as $r) $enabledByDay[(int) $r['day_of_week']] = (bool) $r['enabled'];

$slotsByDay = [];
for ($d = 0; $d <= 6; $d++) $slotsByDay[$d] = getAvailabilitySlots($d);

$allSlots = allPossibleSlotStarts(); // e.g. "08:30:00"

function slotLabel(string $t): string {
    return date('g:i A', strtotime($t));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Schedule — Dr. Prasanna Kumar K</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="portal.css" />
  <style>
    .day-row { border-top: 1px solid var(--border); padding: 14px 0; }
    .day-row-head { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .day-row-head .day-name { font-weight: 700; color: var(--navy); width: 110px; }
    .day-summary { font-size: 12px; color: var(--gray); flex: 1; }
    .expand-btn { background: none; border: 1px solid var(--border); border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 600; color: var(--blue); cursor: pointer; }
    .slot-panel { display: none; margin-top: 14px; padding: 14px; background: #f7f9fc; border-radius: 10px; }
    .slot-panel.open { display: block; }
    .slot-panel-actions { margin-bottom: 10px; display: flex; gap: 8px; }
    .slot-panel-actions button { font-size: 12px; padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border); background: #fff; cursor: pointer; }
    .slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(84px, 1fr)); gap: 6px; user-select: none; }
    .slot-btn { padding: 7px 4px; text-align: center; font-size: 12px; border-radius: 6px; border: 1px solid var(--border); background: #fff; color: var(--gray); cursor: pointer; }
    .slot-btn.selected { background: var(--blue); border-color: var(--blue); color: #fff; font-weight: 600; }
  </style>
</head>
<body>

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

  <div class="page-title" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div>
      <h2>Weekly Schedule</h2>
      <p>Set which days you see patients, and click the times you're available on each</p>
    </div>
    <a href="admin-dashboard.php" class="btn-cta btn-cta-muted">
      <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-calendar-week"></i> Availability</span>
    </div>

    <p id="scheduleMsg" class="form-msg" style="display:none;margin-bottom:16px;"></p>

    <?php foreach ($dayNames as $dow => $name):
      $enabled     = $enabledByDay[$dow] ?? true;
      $daySlots    = $slotsByDay[$dow] ?? [];
      $selectedSet = array_flip($daySlots);
    ?>
    <div class="day-row" data-day="<?= $dow ?>">
      <div class="day-row-head">
        <label style="display:flex;align-items:center;gap:8px;width:110px;">
          <input type="checkbox" class="day-enabled" <?= $enabled ? 'checked' : '' ?> style="width:16px;height:16px;" />
          <span class="day-name" style="width:auto;"><?= htmlspecialchars($name) ?></span>
        </label>
        <span class="day-summary"><?= count($daySlots) ?> slot<?= count($daySlots) !== 1 ? 's' : '' ?> selected</span>
        <button type="button" class="expand-btn" onclick="toggleDay(<?= $dow ?>)">
          <i class="fas fa-clock"></i> Edit Times
        </button>
      </div>
      <div class="slot-panel" id="panel-<?= $dow ?>">
        <div class="slot-panel-actions">
          <button type="button" onclick="setAllSlots(<?= $dow ?>, true)">Select All</button>
          <button type="button" onclick="setAllSlots(<?= $dow ?>, false)">Clear All</button>
        </div>
        <div class="slot-grid" id="grid-<?= $dow ?>">
          <?php foreach ($allSlots as $t): ?>
            <div class="slot-btn <?= isset($selectedSet[$t]) ? 'selected' : '' ?>" data-time="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(slotLabel($t)) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <button type="button" class="submit-btn" id="saveBtn" style="margin-top:20px;" onclick="saveSchedule()">
      <i class="fas fa-save"></i> Save Schedule
    </button>
  </div>

</div>

<script>
function toggleDay(dow) {
  document.getElementById('panel-' + dow).classList.toggle('open');
}

function setAllSlots(dow, on) {
  document.querySelectorAll(`#grid-${dow} .slot-btn`).forEach(el => el.classList.toggle('selected', on));
  updateSummary(dow);
}

function updateSummary(dow) {
  const row     = document.querySelector(`.day-row[data-day="${dow}"]`);
  const count   = row.querySelectorAll('.slot-btn.selected').length;
  row.querySelector('.day-summary').textContent = `${count} slot${count !== 1 ? 's' : ''} selected`;
}

// Click a single slot, or mousedown-drag across several to paint the same state.
let painting = false;
let paintValue = true;

document.querySelectorAll('.slot-grid').forEach(grid => {
  grid.addEventListener('mousedown', (e) => {
    const btn = e.target.closest('.slot-btn');
    if (!btn) return;
    painting = true;
    paintValue = !btn.classList.contains('selected');
    btn.classList.toggle('selected', paintValue);
    e.preventDefault();
  });
  grid.addEventListener('mouseenter', (e) => {
    if (!painting) return;
    const btn = e.target.closest('.slot-btn');
    if (btn) btn.classList.toggle('selected', paintValue);
  }, true);
});
document.addEventListener('mouseup', () => {
  if (!painting) return;
  painting = false;
  document.querySelectorAll('.day-row').forEach(row => updateSummary(row.dataset.day));
});
// Touch support: tap toggles a single slot.
document.querySelectorAll('.slot-btn').forEach(btn => {
  btn.addEventListener('touchend', (e) => {
    e.preventDefault();
    btn.classList.toggle('selected');
    updateSummary(btn.closest('.day-row').dataset.day);
  });
});

async function saveSchedule() {
  const btn = document.getElementById('saveBtn');
  const msg = document.getElementById('scheduleMsg');
  btn.disabled = true;
  msg.style.display = 'none';

  const days = Array.from(document.querySelectorAll('.day-row')).map(row => ({
    day_of_week: row.dataset.day,
    enabled:     row.querySelector('.day-enabled').checked ? '1' : '0',
    slots:       Array.from(row.querySelectorAll('.slot-btn.selected')).map(el => el.dataset.time),
  }));

  try {
    const res  = await fetch('manage-schedule.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ days }),
    });
    const data = await res.json();
    msg.textContent = data.success ? 'Schedule saved.' : (data.message || 'Failed to save schedule.');
    msg.className = 'form-msg ' + (data.success ? 'success' : 'error');
    msg.style.display = 'block';
  } catch (e) {
    msg.textContent = 'Network error while saving.';
    msg.className = 'form-msg error';
    msg.style.display = 'block';
  }
  btn.disabled = false;
}
</script>
</body>
</html>
