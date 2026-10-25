<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

$ref = trim($_GET['ref'] ?? '');
if (!$ref) { header('Location: index.php'); exit; }

// Access control
$isDoctor  = isDoctorLoggedIn();
$isPatient = isPatientLoggedIn();

if (!$isDoctor && !$isPatient) {
    // Not logged in — send to patient portal with a hint
    header('Location: patient-portal.php');
    exit;
}

// Load appointment
$appt = findAppointmentByRef($ref);
if (!$appt) {
    http_response_code(404);
    die('<h2 style="font-family:sans-serif;padding:40px;">Appointment not found.</h2>');
}

// Patient can only view their own records
if ($isPatient && !$isDoctor) {
    if ($appt['patient_id'] !== $_SESSION['portal_patient_id']) {
        http_response_code(403);
        die('<h2 style="font-family:sans-serif;padding:40px;">Access denied.</h2>');
    }
}

$patient = findPatientById($appt['patient_id']);
$fullName = $patient ? trim(($patient['salutation'] ? $patient['salutation'].'. ':'') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? '')) : 'Unknown';

// Ensure record container exists
ensureRecord($ref, $appt['patient_id']);
$record = findRecordByRef($ref);
$entries = $record['entries'] ?? [];

// Sort entries oldest first
usort($entries, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));

$backUrl  = $isDoctor ? 'admin-dashboard.php' : 'patient-history.php';
$backLabel = $isDoctor ? 'Admin Dashboard' : 'My Appointments';

// Helper: file is image?
function isImage(string $filename): bool {
    return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $filename);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Record <?= htmlspecialchars($ref) ?> — Dr. Prasanna Kumar K</title>
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
    Dr. Prasanna Kumar K
  </div>
  <div class="bar-right">
    <?php if ($isDoctor): ?>
      <span style="font-size:13px;opacity:.7;">Doctor View</span>
      <a href="logout.php?role=doctor" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <?php else: ?>
      <span style="font-size:13px;opacity:.7;"><?= htmlspecialchars($_SESSION['portal_patient_name'] ?? '') ?></span>
      <a href="logout.php?role=patient" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <?php endif; ?>
  </div>
</div>

<div class="portal-wrap">

  <!-- Back link + ref badge -->
  <div class="record-header">
    <a href="<?= htmlspecialchars($backUrl) ?>" class="back-link">
      <i class="fas fa-arrow-left"></i> <?= htmlspecialchars($backLabel) ?>
    </a>
    <div class="record-ref-badge"><?= htmlspecialchars($ref) ?></div>

    <!-- Appointment + Patient Meta -->
    <div class="record-meta">
      <div class="meta-group">
        <label>Patient</label>
        <span><?= htmlspecialchars($fullName) ?></span>
      </div>
      <div class="meta-group">
        <label>Appointment Date</label>
        <span><?= htmlspecialchars($appt['appointment_date']) ?></span>
      </div>
      <div class="meta-group">
        <label>Time Slot</label>
        <span><?= htmlspecialchars($appt['time_slot']) ?></span>
      </div>
      <div class="meta-group">
        <label>Doctor</label>
        <span>Dr. Prasanna Kumar K</span>
      </div>
      <?php if ($patient): ?>
      <div class="meta-group">
        <label>Gender</label>
        <span><?= htmlspecialchars($patient['gender'] ?? '—') ?></span>
      </div>
      <div class="meta-group">
        <label>Date of Birth</label>
        <span><?= htmlspecialchars($patient['dob'] ?? '—') ?></span>
      </div>
      <div class="meta-group">
        <label>Mobile</label>
        <span><?= htmlspecialchars($patient['mobile'] ?? '—') ?></span>
      </div>
      <?php if ($isDoctor && !empty($patient['email'])): ?>
      <div class="meta-group">
        <label>Email</label>
        <span><?= htmlspecialchars($patient['email']) ?></span>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Timeline of entries -->
  <div class="card" style="margin-bottom:24px;">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-history"></i> Medical Record &amp; Notes</span>
      <span style="font-size:12px;color:var(--gray);"><?= count($entries) ?> entr<?= count($entries) !== 1 ? 'ies' : 'y' ?></span>
    </div>

    <div class="timeline">
      <?php if (empty($entries)): ?>
        <div class="timeline-empty">
          <i class="fas fa-notes-medical"></i>
          No entries yet. Add a note or upload a file below.
        </div>
      <?php else: ?>
        <?php foreach ($entries as $e):
          $byDoctor  = ($e['by'] === 'doctor');
          $entryName = $byDoctor ? 'Dr. Prasanna Kumar K' : htmlspecialchars($fullName);
          $tag       = $byDoctor ? '<span class="entry-tag-doc">Doctor</span>' : '<span class="entry-tag-pat">Patient</span>';
          $avatarIcon= $byDoctor ? '<i class="fas fa-user-md"></i>' : '<i class="fas fa-user"></i>';
          $avatarCls = $byDoctor ? 'doctor' : 'patient';
          $cardCls   = $byDoctor ? 'doctor-entry' : 'patient-entry';
          $datetime  = date('d M Y, h:i A', strtotime($e['created_at']));
        ?>
        <div class="entry">
          <div class="entry-avatar <?= $avatarCls ?>"><?= $avatarIcon ?></div>
          <div class="entry-body">
            <div class="entry-meta">
              <span class="entry-by"><?= $entryName ?></span>
              <?= $tag ?>
              <span style="font-size:11px;"><?= $datetime ?></span>
            </div>
            <div class="entry-card <?= $cardCls ?>">
              <?php if (!empty($e['description'])): ?>
                <div class="entry-desc"><?= htmlspecialchars($e['description']) ?></div>
              <?php endif; ?>
              <?php if (!empty($e['filename']) && file_exists(__DIR__ . '/' . $e['filename'])):
                $fileUrl     = 'serve-file.php?ref=' . urlencode($ref) . '&f=' . urlencode(basename($e['filename']));
                $fileUrlDl   = $fileUrl . '&dl=1';
              ?>
                <div class="entry-image" style="<?= !empty($e['description']) ? 'margin-top:14px;' : '' ?>">
                  <?php if (isImage($e['filename'])): ?>
                    <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" title="<?= htmlspecialchars($e['original_name'] ?? 'View image') ?>">
                      <img src="<?= htmlspecialchars($fileUrl) ?>" alt="<?= htmlspecialchars($e['original_name'] ?? 'attachment') ?>" loading="lazy" />
                    </a>
                    <div style="font-size:11px;color:var(--gray);margin-top:6px;"><i class="fas fa-image"></i> <?= htmlspecialchars($e['original_name'] ?? '') ?> — <a href="<?= htmlspecialchars($fileUrlDl) ?>" style="color:var(--blue);font-weight:600;">Download</a></div>
                  <?php else: ?>
                    <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="pdf-link">
                      <i class="fas fa-file-pdf"></i>
                      <?= htmlspecialchars($e['original_name'] ?? 'Download file') ?>
                    </a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Add Entry Form -->
  <div class="add-entry-card">
    <div class="card-title" style="margin-bottom:18px;">
      <i class="fas fa-plus-circle" style="color:var(--blue);"></i>
      <?= $isDoctor ? 'Add Prescription / Note' : 'Add Note / Upload File' ?>
    </div>

    <form id="entryForm" enctype="multipart/form-data">
      <input type="hidden" name="ref" value="<?= htmlspecialchars($ref) ?>" />
      <input type="hidden" name="by" value="<?= $isDoctor ? 'doctor' : 'patient' ?>" />

      <div class="form-group">
        <label><?= $isDoctor ? 'Prescription / Clinical Notes' : 'Your Note' ?></label>
        <textarea name="description" id="descInput" placeholder="<?= $isDoctor ? 'e.g. Diagnosis, medications prescribed, instructions…' : 'e.g. Symptom update, questions for doctor…' ?>"></textarea>
      </div>

      <div class="form-group">
        <label><?= $isDoctor ? 'Upload Prescription / Report Image' : 'Upload Lab Report / Document' ?></label>
        <div class="file-input-wrap">
          <input type="file" name="file" id="fileInput" accept="image/*,.pdf" />
          <label for="fileInput" class="file-label">
            <i class="fas fa-paperclip"></i> Choose File
          </label>
          <div class="file-name" id="fileName">No file chosen</div>
          <div class="file-hint">Accepted: JPG, PNG, GIF, WEBP, PDF &bull; Max 10 MB</div>
        </div>
      </div>

      <button type="submit" class="submit-btn" id="submitBtn">
        <i class="fas fa-save"></i> Save Entry
      </button>
      <div id="formMsg" class="form-msg"></div>
    </form>
  </div>

</div><!-- /portal-wrap -->

<script>
// File name preview
document.getElementById('fileInput').addEventListener('change', function() {
  document.getElementById('fileName').textContent = this.files[0] ? this.files[0].name : 'No file chosen';
});

// Form submission
document.getElementById('entryForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const desc = document.getElementById('descInput').value.trim();
  const file = document.getElementById('fileInput').files[0];
  if (!desc && !file) {
    showMsg('Please add a note or upload a file.', false);
    return;
  }
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Saving…';

  const formData = new FormData(this);
  try {
    const res  = await fetch('add-record-entry.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      showMsg('Entry saved successfully!', true);
      setTimeout(() => location.reload(), 1000);
    } else {
      showMsg(data.message || 'Failed to save entry.', false);
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-save"></i> Save Entry';
    }
  } catch (err) {
    showMsg('Network error. Please try again.', false);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Save Entry';
  }
});

function showMsg(msg, success) {
  const el = document.getElementById('formMsg');
  el.textContent = msg;
  el.className = 'form-msg ' + (success ? 'success' : 'error');
}
</script>
</body>
</html>
