function toggleGroup(id, header) {
  const list = document.getElementById(id);
  const chev = document.getElementById('chev-' + id);
  const open = list.classList.toggle('open');
  if (chev) chev.classList.toggle('open', open);
}

function filterPatients() {
  const q = document.getElementById('searchInput').value.toLowerCase().trim();
  document.querySelectorAll('.patient-group').forEach(g => {
    const name   = g.dataset.name   || '';
    const mobile = g.dataset.mobile || '';
    g.style.display = (!q || name.includes(q) || mobile.includes(q)) ? '' : 'none';
  });
}

/* ── Cancel / Postpone ── */
let activeRef = null, activeDoctor = null;

function openCancelModal(ref) {
  activeRef = ref;
  document.getElementById('cancelReason').value = '';
  document.getElementById('cancelErr').style.display = 'none';
  document.getElementById('cancelSubmitBtn').disabled = false;
  document.getElementById('cancelSubmitBtn').textContent = 'Cancel Appointment';
  document.getElementById('cancelModalOverlay').classList.add('open');
}
function closeCancelModal() { document.getElementById('cancelModalOverlay').classList.remove('open'); }

async function submitCancel() {
  const reason = document.getElementById('cancelReason').value.trim();
  const errEl  = document.getElementById('cancelErr');
  if (!reason) { errEl.textContent = 'Please enter a reason.'; errEl.style.display = 'block'; return; }

  const btn = document.getElementById('cancelSubmitBtn');
  btn.disabled = true; btn.textContent = 'Cancelling...';
  try {
    const body = new URLSearchParams({ action: 'cancel', ref: activeRef, reason });
    const res  = await fetch('manage-appointment.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      errEl.textContent = data.message || 'Failed to cancel appointment.';
      errEl.style.display = 'block';
      btn.disabled = false; btn.textContent = 'Cancel Appointment';
    }
  } catch (e) {
    errEl.textContent = 'Network error. Please try again.';
    errEl.style.display = 'block';
    btn.disabled = false; btn.textContent = 'Cancel Appointment';
  }
}

/* ── Close case ── */
let activeCloseRef = null;
let pendingClose    = null; // { reason, amount } staged during the confirm step

function openCloseModal(ref, patientName, apptDate, apptSlot) {
  activeCloseRef = ref;
  document.getElementById('closeReason').value = '';
  document.getElementById('closeOtherReason').value = '';
  document.getElementById('closeOtherGroup').style.display = 'none';
  document.getElementById('closeFeeAmount').value = '';
  document.getElementById('closeFeeGroup').style.display = 'none';
  document.getElementById('closeErr').style.display = 'none';
  document.getElementById('closeConfirmErr').style.display = 'none';
  document.getElementById('closeReviewBtn').disabled = false;
  document.getElementById('closeReviewBtn').textContent = 'Review & Close';
  document.getElementById('closeSubmitBtn').disabled = false;
  document.getElementById('closeSubmitBtn').textContent = 'Confirm & Close';
  document.getElementById('closeFormView').style.display = 'block';
  document.getElementById('closeConfirmView').style.display = 'none';
  document.getElementById('closeModalOverlay').dataset.patientName = patientName || '';
  document.getElementById('closeModalOverlay').dataset.apptDate    = apptDate || '';
  document.getElementById('closeModalOverlay').dataset.apptSlot    = apptSlot || '';
  document.getElementById('closeModalOverlay').classList.add('open');
}
function closeCloseModal() { document.getElementById('closeModalOverlay').classList.remove('open'); }

function toggleCloseOtherReason() {
  const reasonVal = document.getElementById('closeReason').value;
  document.getElementById('closeOtherGroup').style.display = reasonVal === 'Others' ? 'block' : 'none';
  document.getElementById('closeFeeGroup').style.display = reasonVal === 'Doctor saw the patient' ? 'block' : 'none';
}

function reviewClose() {
  const reasonSel = document.getElementById('closeReason').value;
  const errEl     = document.getElementById('closeErr');
  if (!reasonSel) { errEl.textContent = 'Please select a reason.'; errEl.style.display = 'block'; return; }

  let reason = reasonSel;
  if (reasonSel === 'Others') {
    reason = document.getElementById('closeOtherReason').value.trim();
    if (!reason) { errEl.textContent = 'Please specify the reason.'; errEl.style.display = 'block'; return; }
  }

  const amount = document.getElementById('closeFeeAmount').value.trim();
  if (amount && (isNaN(amount) || Number(amount) < 0)) {
    errEl.textContent = 'Please enter a valid fee amount.'; errEl.style.display = 'block'; return;
  }

  errEl.style.display = 'none';
  pendingClose = { reason, amount };

  const overlay = document.getElementById('closeModalOverlay');
  document.getElementById('confirmPatientName').textContent = overlay.dataset.patientName || '—';
  document.getElementById('confirmRef').textContent          = activeCloseRef;
  document.getElementById('confirmDateSlot').textContent     = `${overlay.dataset.apptDate || '—'}, ${overlay.dataset.apptSlot || '—'}`;
  document.getElementById('confirmReason').textContent       = reason;
  document.getElementById('confirmFee').textContent          = amount ? `₹${amount}` : 'No fee — closing without payment request';

  document.getElementById('closeFormView').style.display    = 'none';
  document.getElementById('closeConfirmView').style.display = 'block';
}

function backToCloseForm() {
  document.getElementById('closeConfirmView').style.display = 'none';
  document.getElementById('closeFormView').style.display    = 'block';
}

async function submitClose() {
  if (!pendingClose) return;
  const errEl = document.getElementById('closeConfirmErr');
  const btn   = document.getElementById('closeSubmitBtn');
  btn.disabled = true; btn.textContent = 'Closing...';
  try {
    const body = new URLSearchParams({ action: 'close', ref: activeCloseRef, reason: pendingClose.reason, amount: pendingClose.amount });
    const res  = await fetch('manage-appointment.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      errEl.textContent = data.message || 'Failed to close case.';
      errEl.style.display = 'block';
      btn.disabled = false; btn.textContent = 'Confirm & Close';
    }
  } catch (e) {
    errEl.textContent = 'Network error. Please try again.';
    errEl.style.display = 'block';
    btn.disabled = false; btn.textContent = 'Confirm & Close';
  }
}

/* ── Slot generation (mirrors doctor-details.php) ── */
function generateSlots() {
  const slots = [];
  const ranges = [ { start: [8, 30], end: [11, 0] }, { start: [16, 30], end: [21, 0] } ];
  ranges.forEach(({ start, end }) => {
    let [h, m] = start;
    while (h < end[0] || (h === end[0] && m < end[1])) {
      const s = fmtSlot(h, m);
      let nh = h, nm = m + 15;
      if (nm >= 60) { nh++; nm -= 60; }
      const e = fmtSlot(nh, nm);
      slots.push(`${s} - ${e}`);
      h = nh; m = nm;
    }
  });
  return slots;
}
function fmtSlot(h, m) {
  const ampm = h < 12 ? 'AM' : 'PM';
  const hh   = h % 12 === 0 ? 12 : h % 12;
  return `${String(hh).padStart(2,'0')}:${String(m).padStart(2,'0')} ${ampm}`;
}
const ALL_SLOTS = generateSlots();

function openPostponeModal(ref, doctor) {
  activeRef = ref; activeDoctor = doctor;
  document.getElementById('postponeDate').value = '';
  document.getElementById('postponeReason').value = '';
  document.getElementById('postponeSlot').innerHTML = '<option value="">Select a date first</option>';
  document.getElementById('postponeErr').style.display = 'none';
  document.getElementById('postponeSubmitBtn').disabled = false;
  document.getElementById('postponeSubmitBtn').textContent = 'Save New Date';
  document.getElementById('postponeModalOverlay').classList.add('open');
}
function closePostponeModal() { document.getElementById('postponeModalOverlay').classList.remove('open'); }

async function loadPostponeSlots() {
  const dateVal = document.getElementById('postponeDate').value; // yyyy-mm-dd
  const select  = document.getElementById('postponeSlot');
  if (!dateVal) { select.innerHTML = '<option value="">Select a date first</option>'; return; }

  const [y, m, d] = dateVal.split('-');
  const dateLabel = `${d}-${m}-${y}`;
  select.innerHTML = '<option value="">Loading slots...</option>';

  let booked = new Set();
  try {
    const res  = await fetch(`get_slots.php?doctor=${encodeURIComponent(activeDoctor)}&date=${encodeURIComponent(dateLabel)}`);
    const data = await res.json();
    booked = new Set(data.booked || []);
  } catch (e) { /* fall back to no exclusions */ }

  select.innerHTML = '<option value="">Select a time slot</option>' +
    ALL_SLOTS.map(s => `<option value="${s}" ${booked.has(s) ? 'disabled' : ''}>${s}${booked.has(s) ? ' (booked)' : ''}</option>`).join('');
}

async function submitPostpone() {
  const dateVal = document.getElementById('postponeDate').value;
  const slot    = document.getElementById('postponeSlot').value;
  const reason  = document.getElementById('postponeReason').value.trim();
  const errEl   = document.getElementById('postponeErr');

  if (!dateVal || !slot) { errEl.textContent = 'Please select a new date and time slot.'; errEl.style.display = 'block'; return; }

  const [y, m, d] = dateVal.split('-');
  const dateLabel = `${d}-${m}-${y}`;

  const btn = document.getElementById('postponeSubmitBtn');
  btn.disabled = true; btn.textContent = 'Saving...';
  try {
    const body = new URLSearchParams({ action: 'postpone', ref: activeRef, new_date: dateLabel, new_slot: slot, reason });
    const res  = await fetch('manage-appointment.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const data = await res.json();
    if (data.success) {
      location.reload();
    } else {
      errEl.textContent = data.message || 'Failed to postpone appointment.';
      errEl.style.display = 'block';
      btn.disabled = false; btn.textContent = 'Save New Date';
    }
  } catch (e) {
    errEl.textContent = 'Network error. Please try again.';
    errEl.style.display = 'block';
    btn.disabled = false; btn.textContent = 'Save New Date';
  }
}
