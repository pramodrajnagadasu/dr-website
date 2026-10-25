<?php
/**
 * Shared patient/appointment list renderer.
 * Expects: $visiblePatients (array of [patient, appointments]), $emptyIcon, $emptyMessage, $showActions (bool).
 */
?>
<!-- Search -->
<div class="search-box">
  <i class="fas fa-search"></i>
  <input type="text" id="searchInput" placeholder="Search by patient name or mobile…" oninput="filterPatients()" />
</div>

<!-- Patient list -->
<div id="patientList">
  <?php if (empty($visiblePatients)): ?>
    <div class="card" style="text-align:center;padding:48px;color:var(--gray);">
      <i class="fas <?= htmlspecialchars($emptyIcon) ?>" style="font-size:40px;display:block;margin-bottom:12px;color:var(--border);"></i>
      <?= htmlspecialchars($emptyMessage) ?>
    </div>
  <?php else: ?>
    <?php foreach ($visiblePatients as [$p, $patAppts]):
      $fullName   = trim(($p['salutation'] ? $p['salutation'] . '. ' : '') . $p['first_name'] . ' ' . ($p['last_name'] ?? ''));
      $initials   = strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'] ?? '', 0, 1));
      $apptCount  = count($patAppts);
      $openCount  = count(array_filter($patAppts, fn($a) => !in_array($a['status'] ?? 'confirmed', ['cancelled', 'closed'], true)));
      $groupId    = 'grp-' . $p['id'];
    ?>
    <div class="patient-group" data-name="<?= htmlspecialchars(strtolower($fullName)) ?>" data-mobile="<?= htmlspecialchars($p['mobile']) ?>">
      <div class="patient-group-header" onclick="toggleGroup('<?= $groupId ?>', this)">
        <div class="patient-avatar"><?= htmlspecialchars($initials ?: '?') ?></div>
        <div class="patient-info">
          <div class="patient-name"><?= htmlspecialchars($fullName) ?></div>
          <div class="patient-meta">
            <i class="fas fa-phone" style="font-size:10px;"></i> <?= htmlspecialchars($p['mobile']) ?>
            &nbsp;&bull;&nbsp;
            <i class="fas fa-venus-mars" style="font-size:10px;"></i> <?= htmlspecialchars($p['gender'] ?? '—') ?>
            &nbsp;&bull;&nbsp;
            DOB: <?= htmlspecialchars($p['dob'] ?? '—') ?>
            <?php if ($p['email']): ?>
              &nbsp;&bull;&nbsp;<i class="fas fa-envelope" style="font-size:10px;"></i> <?= htmlspecialchars($p['email']) ?>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($openCount > 0): ?>
          <span class="appt-count appt-count-open"><?= $openCount ?> open</span>
        <?php endif; ?>
        <span class="appt-count"><?= $apptCount ?> appointment<?= $apptCount !== 1 ? 's' : '' ?></span>
        <i class="fas fa-chevron-down chevron" id="chev-<?= $groupId ?>"></i>
      </div>

      <div class="appt-list" id="<?= $groupId ?>">
        <?php if (empty($patAppts)): ?>
          <div class="appt-row" style="color:var(--gray);font-size:13px;">No appointments yet.</div>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="appt-table">
              <thead>
                <tr>
                  <th>Ref</th>
                  <th>Date</th>
                  <th>Time Slot</th>
                  <th>Status</th>
                  <th>Record</th>
                  <?php if ($showActions): ?><th>Actions</th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($patAppts as $a):
                  $status      = $a['status'] ?? 'confirmed';
                  $statusLabel = $status === 'confirmed' ? 'Open' : ucfirst($status);
                  $reason      = '';
                  if ($status === 'cancelled' && !empty($a['cancel_reason'])) {
                      $reason = $a['cancel_reason'];
                  } elseif ($status === 'postponed' && !empty($a['reschedule_history'])) {
                      $lastR  = end($a['reschedule_history']);
                      $reason = 'Rescheduled from ' . $lastR['from_date'] . ', ' . $lastR['from_slot'] . (!empty($lastR['reason']) ? ' — ' . $lastR['reason'] : '');
                  } elseif ($status === 'closed' && !empty($a['close_reason'])) {
                      $reason = $a['close_reason'];
                  }
                  $payment = findPaymentByRef($a['ref_number']);
                ?>
                <tr>
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
                  <?php if ($showActions): ?>
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
                              onclick="openCloseModal('<?= htmlspecialchars($a['ref_number'], ENT_QUOTES) ?>', '<?= htmlspecialchars($fullName, ENT_QUOTES) ?>', '<?= htmlspecialchars($a['appointment_date'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['time_slot'], ENT_QUOTES) ?>')">
                        <i class="fas fa-check-circle"></i> Close Case
                      </button>
                    </div>
                    <?php endif; ?>
                  </td>
                  <?php endif; ?>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
