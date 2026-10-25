<?php
session_start();
require_once 'db.php';
require_once 'auth.php';
require_once 'config.php';

// Already logged in
if (isPatientLoggedIn()) { header('Location: patient-history.php'); exit; }

$loggedOut = isset($_GET['logged_out']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Patient Login — Dr. Prasanna Kumar K</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="portal.css" />
</head>
<body>
<div class="login-page">
  <div class="login-card">

    <div class="login-logo">
      <div class="logo-icon"><i class="fas fa-stethoscope"></i></div>
      <h2>Dr. Prasanna Kumar K</h2>
      <p>Access your medical records</p>
      <span class="role-badge patient-badge"><i class="fas fa-user"></i> Patient Portal</span>
    </div>

    <?php if ($loggedOut): ?>
      <div class="login-success show"><i class="fas fa-check-circle"></i> Logged out successfully.</div>
    <?php endif; ?>

    <div id="alertBox" class="login-error"></div>
    <div id="successBox" class="login-success"></div>

    <!-- Step 1: Mobile number -->
    <div class="step active" id="step1">
      <div class="input-group">
        <label>Your Mobile Number</label>
        <input type="tel" id="mobileInput" placeholder="10-digit mobile number" maxlength="10" inputmode="numeric" />
      </div>
      <button class="login-btn" id="sendOtpBtn" onclick="sendOtp()">
        <i class="fas fa-paper-plane"></i> Send Code
      </button>
    </div>

    <!-- Step 2: verification -->
    <div class="step" id="step2">
      <p style="font-size:13px;color:var(--gray);text-align:center;margin-bottom:4px;">Enter the 6-digit code sent to</p>
      <p style="font-size:14px;font-weight:700;color:var(--navy);text-align:center;margin-bottom:4px;" id="mobileDisplay"></p>
      <div class="otp-boxes" id="otpBoxes">
        <input type="text" maxlength="1" inputmode="numeric" />
        <input type="text" maxlength="1" inputmode="numeric" />
        <input type="text" maxlength="1" inputmode="numeric" />
        <input type="text" maxlength="1" inputmode="numeric" />
        <input type="text" maxlength="1" inputmode="numeric" />
        <input type="text" maxlength="1" inputmode="numeric" />
      </div>
      <button class="login-btn" id="verifyOtpBtn" onclick="verifyOtp()">
        <i class="fas fa-check-circle"></i> Verify Code
      </button>
      <div class="resend-row">
        <span id="resendTimer"></span>
        <button id="resendBtn" onclick="resendOtp()" disabled>Resend Code</button>
      </div>
    </div>

    <!-- Step 3: Select profile (if multiple patients on same mobile) -->
    <div class="step" id="step3">
      <p style="font-size:14px;color:var(--gray);margin-bottom:12px;">Multiple profiles found. Select yours:</p>
      <div class="patient-select-list" id="patientList"></div>
      <button class="login-btn" style="margin-top:16px;" id="selectProfileBtn" onclick="selectProfile()" disabled>
        <i class="fas fa-sign-in-alt"></i> Continue
      </button>
    </div>

    <div style="text-align:center;margin-top:28px;">
      <a href="index.php" style="font-size:13px;color:var(--gray);"><i class="fas fa-arrow-left"></i> Back to website</a>
    </div>
  </div>
</div>

<script>
let currentMobile = '';
let resendCountdown = null;
let selectedPatientId = null;

function showAlert(msg, isSuccess = false) {
  const el = document.getElementById(isSuccess ? 'successBox' : 'alertBox');
  const other = document.getElementById(isSuccess ? 'alertBox' : 'successBox');
  other.className = isSuccess ? 'login-error' : 'login-success';
  el.textContent = msg;
  el.className = (isSuccess ? 'login-success' : 'login-error') + ' show';
}
function clearAlerts() {
  document.getElementById('alertBox').className = 'login-error';
  document.getElementById('successBox').className = 'login-success';
}
function goStep(n) {
  document.querySelectorAll('.step').forEach((s,i) => s.classList.toggle('active', i+1 === n));
  clearAlerts();
}
function setLoading(btnId, loading) {
  const btn = document.getElementById(btnId);
  if (loading) {
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Please wait…';
  } else {
    btn.disabled = false;
  }
}

async function sendOtp() {
  const mobile = document.getElementById('mobileInput').value.trim();
  if (!/^\d{10}$/.test(mobile)) { showAlert('Enter a valid 10-digit mobile number.'); return; }
  currentMobile = mobile;
  setLoading('sendOtpBtn', true);
  clearAlerts();

  const res  = await fetch('send_otp.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'mobile=' + encodeURIComponent(mobile) });
  const data = await res.json();
  document.getElementById('sendOtpBtn').innerHTML = '<i class="fas fa-paper-plane"></i> Send Code';
  document.getElementById('sendOtpBtn').disabled = false;

  if (data.success) {
    document.getElementById('mobileDisplay').textContent = data.email || 'your email';
    goStep(2);
    startResendTimer(60);
    document.querySelector('#otpBoxes input').focus();
  } else {
    showAlert(data.message || 'Failed to send code.');
  }
}

async function resendOtp() {
  currentMobile = document.getElementById('mobileInput').value.trim();
  setLoading('resendBtn', true);
  const res  = await fetch('send_otp.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'mobile=' + encodeURIComponent(currentMobile) });
  const data = await res.json();
  document.getElementById('resendBtn').innerHTML = 'Resend Code';
  document.getElementById('resendBtn').disabled = false;
  if (data.success) { showAlert('Code resent successfully.', true); startResendTimer(60); }
  else { showAlert(data.message || 'Failed to resend code.'); }
}

function startResendTimer(sec) {
  clearInterval(resendCountdown);
  document.getElementById('resendBtn').disabled = true;
  const timer = document.getElementById('resendTimer');
  let s = sec;
  timer.textContent = `Resend in ${s}s — `;
  resendCountdown = setInterval(() => {
    s--;
    if (s <= 0) {
      clearInterval(resendCountdown);
      timer.textContent = '';
      document.getElementById('resendBtn').disabled = false;
    } else {
      timer.textContent = `Resend in ${s}s — `;
    }
  }, 1000);
}

async function verifyOtp() {
  const boxes = document.querySelectorAll('#otpBoxes input');
  const otp   = Array.from(boxes).map(b => b.value).join('');
  if (otp.length < 6) { showAlert('Enter the complete 6-digit code.'); return; }
  setLoading('verifyOtpBtn', true);
  clearAlerts();

  const res  = await fetch('verify_otp.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'otp=' + encodeURIComponent(otp) });
  const data = await res.json();
  document.getElementById('verifyOtpBtn').innerHTML = '<i class="fas fa-check-circle"></i> Verify Code';
  document.getElementById('verifyOtpBtn').disabled = false;

  if (data.success) {
    // Find patients by mobile
    const res2  = await fetch('check_patient.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'mobile=' + encodeURIComponent(currentMobile) });
    const data2 = await res2.json();
    if (!data2.exists || !data2.patients || data2.patients.length === 0) {
      showAlert('No patient record found for this mobile. Please book an appointment first.');
      return;
    }
    if (data2.patients.length === 1) {
      await loginPatient(data2.patients[0].id);
    } else {
      // Multiple profiles
      const list = document.getElementById('patientList');
      list.innerHTML = '';
      data2.patients.forEach(p => {
        const div = document.createElement('div');
        div.className = 'patient-option';
        div.dataset.id = p.id;
        div.innerHTML = `<strong>${escHtml(p.name)}</strong><div class="opt-sub">${escHtml(p.gender || '')}${p.dob ? ' &bull; DOB: ' + escHtml(p.dob) : ''}</div>`;
        div.onclick = () => {
          document.querySelectorAll('.patient-option').forEach(o => o.classList.remove('selected'));
          div.classList.add('selected');
          selectedPatientId = p.id;
          document.getElementById('selectProfileBtn').disabled = false;
        };
        list.appendChild(div);
      });
      goStep(3);
    }
  } else {
    showAlert(data.message || 'Incorrect code.');
  }
}

async function selectProfile() {
  if (!selectedPatientId) return;
  await loginPatient(selectedPatientId);
}

async function loginPatient(patientId) {
  const res  = await fetch('portal-login.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'patient_id=' + encodeURIComponent(patientId) });
  const data = await res.json();
  if (data.success) { window.location.href = 'patient-history.php'; }
  else { showAlert(data.message || 'Login failed. Please try again.'); }
}

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// OTP box keyboard navigation
document.addEventListener('DOMContentLoaded', () => {
  const boxes = document.querySelectorAll('#otpBoxes input');
  boxes.forEach((box, i) => {
    box.addEventListener('input', () => {
      if (box.value && i < boxes.length - 1) boxes[i+1].focus();
    });
    box.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !box.value && i > 0) boxes[i-1].focus();
    });
    box.addEventListener('paste', e => {
      e.preventDefault();
      const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
      boxes.forEach((b, j) => { b.value = digits[j] || ''; });
      if (digits.length > 0) boxes[Math.min(digits.length, 5)].focus();
    });
  });
});
</script>
</body>
</html>
