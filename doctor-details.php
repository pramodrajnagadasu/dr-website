<?php
require_once 'db.php';

$doctors = [
    'prasanna' => [
        'name'          => 'Dr. Prasanna Kumar K',
        'qualification' => 'MBBS, AFIH',
        'specialist'    => 'Occupational Health & Primary Care',
    ],
];

$key = $_GET['doctor'] ?? '';
if (!isset($doctors[$key])) {
    header('Location: appointment.php');
    exit;
}
$doctor = $doctors[$key];

$enabledDays = [];
foreach (getDoctorAvailability() as $row) {
    if ($row['enabled']) $enabledDays[] = (int) $row['day_of_week'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dr. Prasanna Kumar K – Appointment Booking</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="icon" type="image/svg+xml" href="favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Roboto', sans-serif;
      min-height: 100vh;
      background: url('https://images.unsplash.com/photo-1584820927498-cfe5211fd8bf?w=1800&q=85') center/cover no-repeat fixed;
      display: flex;
      flex-direction: column;
    }

    /* ── HEADER ── */
    .appt-header {
      padding: 18px 36px;
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .appt-header a { display: inline-flex; align-items: center; gap: 14px; text-decoration: none; }
    .logo-icon {
      width: 62px; height: 62px;
      background: linear-gradient(135deg, #0f2b5b, #1565c0);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      color: white; font-size: 18px; font-weight: 800;
      box-shadow: 0 4px 14px rgba(21,101,192,0.3);
    }
    .logo-text h1 { font-size: 17px; font-weight: 700; color: #1a1a2e; line-height: 1.2; }
    .logo-text span { font-size: 12px; color: #1565c0; font-weight: 500; }

    /* ── PAGE LAYOUT ── */
    .page-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px 60px;
      gap: 24px;
    }

    /* ── DOCTOR CARD ── */
    .appt-card {
      background: white;
      border-radius: 16px;
      padding: 40px 48px;
      width: 100%; max-width: 480px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.18);
      animation: slideUp 0.4s ease;
      position: relative;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .appt-card h2 {
      font-size: 26px; font-weight: 400;
      color: #1a1a2e; text-align: center; margin-bottom: 28px;
    }

    .detail-row { font-size: 15px; color: #1a1a2e; margin-bottom: 16px; }
    .detail-row strong { font-weight: 700; }
    .detail-row .value { color: #1565c0; font-weight: 500; }

    .date-label { font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 10px; }

    /* Date input row */
    .date-field-wrap { display: flex; align-items: stretch; position: relative; }
    #dateDisplay {
      flex: 1;
      padding: 12px 14px;
      border: 1.5px solid #cdd5e0; border-right: none;
      border-radius: 8px 0 0 8px;
      font-size: 14px; font-family: 'Roboto', sans-serif;
      color: #aaa; background: white;
      cursor: pointer; outline: none; user-select: none;
    }
    #dateDisplay.filled { color: #1a1a2e; }
    .cal-btn {
      width: 46px;
      background: #f0a500; border: 1.5px solid #f0a500;
      border-radius: 0 8px 8px 0;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: white; font-size: 17px; flex-shrink: 0;
    }
    .cal-btn:hover { background: #d9920a; }

    /* ── CUSTOM CALENDAR ── */
    .custom-cal {
      display: none;
      position: absolute;
      top: calc(100% + 4px); left: 0;
      z-index: 999;
      background: white;
      border: 1px solid #ddd; border-radius: 6px;
      box-shadow: 0 8px 28px rgba(0,0,0,0.14);
      width: 280px; font-size: 13px; overflow: hidden;
    }
    .custom-cal.open { display: block; }

    .cal-head {
      background: #f0a500;
      display: flex; align-items: center; justify-content: space-between;
      padding: 8px 10px;
    }
    .cal-nav {
      background: none; border: none; color: white;
      font-size: 15px; cursor: pointer; padding: 2px 6px;
      border-radius: 4px; line-height: 1;
    }
    .cal-nav:hover { background: rgba(255,255,255,0.2); }
    .cal-selects { display: flex; gap: 6px; }
    .cal-selects select {
      background: white; border: none; border-radius: 4px;
      padding: 3px 6px; font-size: 13px;
      font-family: 'Roboto', sans-serif; cursor: pointer; outline: none; color: #1a1a2e;
    }

    .cal-dow {
      display: grid; grid-template-columns: repeat(7,1fr);
      background: #fafafa; border-bottom: 1px solid #eee;
    }
    .cal-dow span {
      text-align: center; padding: 7px 0;
      font-size: 12px; font-weight: 600; color: #777;
    }
    .cal-dow span:first-child { color: #e57373; }

    .cal-days { display: grid; grid-template-columns: repeat(7,1fr); padding: 6px 4px 10px; gap: 2px; }
    .cal-day {
      text-align: center; padding: 7px 0; border-radius: 50%;
      cursor: pointer; font-size: 13px; color: #1565c0; font-weight: 500;
      transition: background 0.15s;
    }
    .cal-day:hover                  { background: rgba(21,101,192,0.12); }
    .cal-day.past                   { color: #ccc; pointer-events: none; }
    .cal-day.today                  { background: #f0a500; color: white; font-weight: 700; }
    .cal-day.today:hover            { background: #d9920a; }
    .cal-day.selected               { background: #1565c0; color: white; font-weight: 700; }
    .cal-day.selected:hover         { background: #015f77; }
    .cal-day.sunday                 { color: #e57373; }
    .cal-day.sunday.past            { color: #f5c0c0; }
    .cal-day.closed                 { color: #ccc; pointer-events: none; text-decoration: line-through; }
    .cal-day.empty                  { pointer-events: none; }

    .error-msg { color: #e53e3e; font-size: 12px; margin-top: 6px; display: none; }
    .error-msg.show { display: block; }

    /* ── TIME SLOTS PANEL ── */
    .slots-panel {
      display: none;
      background: white;
      border-radius: 16px;
      padding: 30px 32px 36px;
      width: 100%; max-width: 800px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
      animation: slideUp 0.35s ease;
    }
    .slots-panel.show { display: block; }

    .slots-title {
      font-size: 16px; font-weight: 700; color: #1a1a2e;
      margin-bottom: 8px;
    }

    .slots-legend {
      display: flex; gap: 20px; margin-bottom: 20px; font-size: 13px; color: #555;
    }
    .legend-dot {
      display: inline-block; width: 12px; height: 12px;
      border-radius: 3px; margin-right: 6px; vertical-align: middle;
    }
    .legend-dot.green { background: #28a745; }
    .legend-dot.red   { background: #dc3545; }
    .legend-dot.sel   { background: #005f8a; }

    .slots-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 10px;
    }

    .slot {
      padding: 12px 6px;
      border-radius: 6px;
      text-align: center;
      font-size: 12.5px;
      font-weight: 600;
      color: white;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.15s, box-shadow 0.15s;
      user-select: none;
    }
    .slot.available {
      background: #28a745;
      box-shadow: 0 2px 6px rgba(40,167,69,0.3);
    }
    .slot.available:hover {
      opacity: 0.88; transform: translateY(-2px);
      box-shadow: 0 6px 14px rgba(40,167,69,0.4);
    }
    .slot.booked {
      background: #dc3545;
      cursor: not-allowed;
      box-shadow: 0 2px 6px rgba(220,53,69,0.25);
    }
    .slot.selected-slot {
      background: #005f8a !important;
      box-shadow: 0 4px 14px rgba(0,95,138,0.5) !important;
      transform: translateY(-2px);
    }
    .slot.past {
      background: #b0bec5;
      cursor: not-allowed;
      box-shadow: none;
      opacity: 0.6;
    }

    .slots-err { color: #e53e3e; font-size: 12px; margin-top: 10px; display: none; }
    .slots-err.show { display: block; }

    .btn-confirm {
      margin-top: 24px; width: 100%; padding: 13px;
      border: none; border-radius: 10px;
      font-size: 15px; font-weight: 600; cursor: pointer;
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #0f2b5b, #1565c0);
      color: white; transition: opacity 0.3s, transform 0.2s;
    }
    .btn-confirm:hover { opacity: 0.88; transform: translateY(-1px); }

    /* ── SUCCESS ── */
    .success-screen {
      display: none; text-align: center;
      background: white; border-radius: 16px;
      padding: 50px 40px; width: 100%; max-width: 480px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.18);
    }
    .success-icon {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, #0f2b5b, #1565c0);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      color: white; font-size: 30px; margin: 0 auto 20px;
    }
    .success-screen h3 { font-size: 22px; font-weight: 700; color: #1a1a2e; margin-bottom: 10px; }
    .success-screen p  { font-size: 14px; color: #666; line-height: 1.7; margin-bottom: 6px; }
    .ref-num {
      display: inline-block; margin: 16px 0; padding: 8px 22px;
      background: rgba(21,101,192,0.08); border-radius: 8px;
      font-weight: 700; color: #1565c0; font-size: 15px;
    }
    .back-home {
      display: inline-flex; align-items: center; gap: 8px;
      margin-top: 20px; padding: 11px 28px;
      background: linear-gradient(135deg, #0f2b5b, #1565c0);
      color: white; border-radius: 50px;
      font-weight: 600; font-size: 14px; text-decoration: none;
      transition: opacity 0.3s;
    }
    .back-home:hover { opacity: 0.88; }

    .back-link {
      display: flex; align-items: center; gap: 6px;
      font-size: 13px; color: #1565c0; cursor: pointer;
      margin-bottom: 22px; width: fit-content;
    }
    .back-link:hover { text-decoration: underline; }

    @media (max-width: 700px) {
      .slots-grid { grid-template-columns: repeat(3,1fr); }
      .appt-card  { padding: 30px 22px; }
      .slots-panel { padding: 24px 18px; }
    }
    @media (max-width: 420px) {
      .slots-grid { grid-template-columns: repeat(2,1fr); }
    }

    /* ── MODAL OVERLAY ── */
    .modal-overlay {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 2000;
      align-items: center; justify-content: center;
      padding: 20px;
    }
    .modal-overlay.open { display: flex; }

    .modal {
      background: white;
      border-radius: 12px;
      width: 100%; max-width: 460px;
      box-shadow: 0 24px 60px rgba(0,0,0,0.25);
      animation: modalIn 0.25s ease;
      overflow: hidden;
    }
    @keyframes modalIn {
      from { opacity:0; transform: scale(0.94) translateY(-16px); }
      to   { opacity:1; transform: scale(1) translateY(0); }
    }

    .modal-head {
      padding: 20px 24px 16px;
      border-bottom: 1px solid #eee;
      display: flex; align-items: center; justify-content: space-between;
    }
    .modal-head h3 { font-size: 18px; font-weight: 600; color: #1a1a2e; }
    .modal-close {
      background: none; border: none; font-size: 20px;
      color: #999; cursor: pointer; line-height: 1; padding: 2px 6px;
      border-radius: 4px;
    }
    .modal-close:hover { background: #f5f5f5; color: #333; }

    .modal-body { padding: 24px; }

    .modal-field { margin-bottom: 18px; }
    .modal-field label {
      display: block; font-size: 14px; font-weight: 600;
      color: #1a1a2e; margin-bottom: 8px;
    }
    .modal-field input[type="text"],
    .modal-field input[type="tel"] {
      width: 100%; padding: 11px 14px;
      border: 1.5px solid #cdd5e0; border-radius: 8px;
      font-size: 14px; font-family: 'Roboto', sans-serif;
      color: #1a1a2e; outline: none;
      transition: border-color 0.25s; background: white;
    }
    .modal-field input[readonly] { background: #f4f6f8; color: #555; cursor: default; }
    .modal-field input:focus { border-color: #1565c0; }
    .dob-input-wrap { position: relative; }
    .dob-input-wrap input[type="text"] { padding-right: 40px; }
    .dob-cal-icon {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      color: #888; cursor: pointer; font-size: 15px;
    }
    .dob-cal-icon:hover { color: #1565c0; }
    .dob-hidden-picker { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }

    .modal-err { color: #e53e3e; font-size: 12px; margin-top: 6px; display: none; }
    .modal-err.show { display: block; }

    /* OTP boxes */
    .otp-boxes {
      display: flex; gap: 10px; justify-content: center; margin: 6px 0 4px;
    }
    .otp-box {
      width: 46px; height: 52px;
      border: 2px solid #cdd5e0; border-radius: 8px;
      text-align: center; font-size: 22px; font-weight: 700;
      font-family: 'Roboto', sans-serif; color: #1a1a2e;
      outline: none; transition: border-color 0.2s;
    }
    .otp-box:focus { border-color: #1565c0; }

    .otp-hint {
      font-size: 12px; color: #888; text-align: center; margin-bottom: 4px;
    }
    .otp-demo {
      font-size: 12px; color: #1565c0; text-align: center;
      font-weight: 600; margin-bottom: 2px;
    }

    .resend-row {
      text-align: center; margin-top: 8px;
      font-size: 13px; color: #888;
    }
    .resend-btn {
      background: none; border: none; color: #1565c0;
      font-size: 13px; font-weight: 600; cursor: pointer;
      font-family: 'Roboto', sans-serif;
    }
    .resend-btn:hover { text-decoration: underline; }
    .resend-btn:disabled { color: #aaa; cursor: default; text-decoration: none; }

    .modal-footer { padding: 0 24px 24px; }

    .btn-modal {
      width: 100%; padding: 13px;
      border: none; border-radius: 8px;
      font-size: 15px; font-weight: 600; cursor: pointer;
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(135deg, #0f2b5b, #1565c0);
      color: white; transition: opacity 0.3s, transform 0.2s;
    }
    .btn-modal:hover { opacity: 0.88; transform: translateY(-1px); }
    .btn-modal:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }

    /* countdown timer */
    #otpTimer { font-weight: 600; color: #e57c00; }

    /* registration prompt inside booking modal */
    .reg-prompt {
      display: none;
      background: #fff8f8;
      border: 1px solid #fcc;
      border-radius: 8px;
      padding: 12px 16px;
      margin-top: 10px;
      text-align: center;
    }
    .reg-prompt p { font-size: 13px; color: #e53e3e; margin-bottom: 10px; }
    .btn-register {
      padding: 9px 24px;
      border: none; border-radius: 8px;
      background: #28a745; color: white;
      font-size: 14px; font-weight: 600;
      cursor: pointer; font-family: 'Roboto', sans-serif;
      transition: background 0.2s;
    }
    .btn-register:hover { background: #218838; }

    /* patient found section */
    .patient-found { display: none; margin-top: 4px; }

    /* two-button row */
    .btn-row { display: flex; gap: 10px; }
    .btn-row .btn-modal { flex: 1; }
    .btn-submit  { background: #1a6fd4 !important; }
    .btn-getotp  { background: #28a745 !important; }

    /* registration modal extras */
    .modal-field select {
      width: 100%; padding: 11px 14px;
      border: 1.5px solid #cdd5e0; border-radius: 8px;
      font-size: 14px; font-family: 'Roboto', sans-serif;
      color: #1a1a2e; outline: none; background: white;
      transition: border-color 0.25s; appearance: none;
    }
    .modal-field select:focus { border-color: #1565c0; }
  </style>
</head>
<body>

<header class="appt-header">
  <a href="index.php">
    <div class="logo-icon"><i class="fas fa-stethoscope"></i></div>
    <div class="logo-text">
      <h1>Dr. Prasanna Kumar K</h1>
    </div>
  </a>
</header>

<div class="page-body" id="pageBody">

  <!-- ── Doctor Card ── -->
  <div class="appt-card" id="doctorCard">
    <a class="back-link" onclick="history.back()">
      <i class="fas fa-arrow-left"></i> Back
    </a>

    <h2>Doctor Details</h2>

    <div class="detail-row">
      <strong>Doctor Name:</strong>&nbsp;<span class="value"><?= htmlspecialchars($doctor['name']) ?></span>
    </div>
    <div class="detail-row">
      <strong>Qualification:</strong>&nbsp;<span class="value"><?= htmlspecialchars($doctor['qualification']) ?></span>
    </div>
    <div class="detail-row">
      <strong>Specialist:</strong>&nbsp;<span><?= htmlspecialchars($doctor['specialist']) ?></span>
    </div>

    <div class="date-label">Appointment Date</div>
    <div style="position:relative;">
      <div class="date-field-wrap">
        <div id="dateDisplay" onclick="toggleCal()">DD-MM-YYYY</div>
        <button class="cal-btn" onclick="toggleCal()" type="button">
          <i class="fas fa-calendar-alt"></i>
        </button>
      </div>
      <p class="error-msg" id="err-date">Please select an appointment date</p>

      <!-- Custom Calendar -->
      <div class="custom-cal" id="customCal">
        <div class="cal-head">
          <button class="cal-nav" onclick="prevMonth()" type="button">&#8249;</button>
          <div class="cal-selects">
            <select id="calMonth" onchange="onMonthChange()"></select>
            <select id="calYear"  onchange="onYearChange()"></select>
          </div>
          <button class="cal-nav" onclick="nextMonth()" type="button">&#8250;</button>
        </div>
        <div class="cal-dow">
          <span>Su</span><span>Mo</span><span>Tu</span>
          <span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
        </div>
        <div class="cal-days" id="calDays"></div>
      </div>
    </div>
  </div>

  <!-- ── Time Slots Panel ── -->
  <div class="slots-panel" id="slotsPanel">
    <div class="slots-title" id="slotsTitle">Available Slots</div>
    <div class="slots-legend">
      <span><span class="legend-dot green"></span>Available</span>
      <span><span class="legend-dot red"></span>Booked</span>
      <span><span class="legend-dot sel"></span>Selected</span>
      <span><span class="legend-dot" style="background:#b0bec5;"></span>Passed</span>
    </div>
    <div class="slots-grid" id="slotsGrid"></div>
    <p style="font-size:13px;color:#555;margin-top:14px;"><i class="fas fa-info-circle" style="color:#1565c0;"></i>&nbsp; Click on a green slot to book your appointment.</p>
  </div>

  <!-- ── Success ── -->
  <div class="success-screen" id="successView">
    <div class="success-icon"><i class="fas fa-check"></i></div>
    <h3>Appointment Booked!</h3>
    <p>Thank you! Your appointment request has been received.</p>
    <p>Our team will contact you shortly to confirm.</p>
    <div class="ref-num" id="refNum">REF: #<i class="fas fa-stethoscope"></i>-000000</div><br />
    <a href="index.php" class="back-home"><i class="fas fa-home"></i> Back to Home</a>
  </div>

</div><!-- end page-body -->

<!-- ── BOOKING MODAL ── -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal" id="bookingModal">

    <div class="modal-head">
      <h3 id="modalTitle">Booking at —</h3>
      <button class="modal-close" onclick="closeModal()" type="button">&times;</button>
    </div>

    <!-- Step 1: Mobile number -->
    <div id="stepMobile">
      <div class="modal-body">
        <div class="modal-field">
          <label>Time Slot</label>
          <input type="text" id="modalSlotDisplay" readonly />
        </div>
        <div class="modal-field">
          <label>Mobile Number</label>
          <input type="tel" id="mobileInput" placeholder="Enter Mobile Number" maxlength="15"
                 oninput="this.value=this.value.replace(/[^0-9+]/,'')" />
          <p class="modal-err" id="err-mobile">Please enter a valid 10-digit mobile number</p>
        </div>

        <!-- Shown when existing patient found -->
        <div class="patient-found modal-field" id="patientFoundSection">
          <label>Patient Name</label>
          <div class="select-wrap" style="border:1.5px solid #cdd5e0;border-radius:8px;padding:0;">
            <select id="patientSelect" onchange="onPatientSelect()" style="border:none;border-radius:8px;">
              <option value="">Select Patient Name</option>
            </select>
          </div>
          <p class="modal-err" id="err-patient">Please select a patient</p>
          <p id="otpHint" style="display:none;color:#e53e3e;font-size:13px;text-align:center;margin-top:10px;">
            Please click Get Code button
          </p>
        </div>

        <!-- Shown when NOT an existing patient -->
        <div class="reg-prompt" id="regPrompt">
          <p>As you are not an existing patient, kindly click on "Register".</p>
          <button class="btn-register" onclick="openRegModal()" type="button">Register</button>
        </div>
      </div>
      <div class="modal-footer">
        <!-- Default: single Send Code button -->
        <button class="btn-modal" onclick="checkPatient()" type="button" id="btnSendOTP">
          <i class="fas fa-paper-plane"></i>&nbsp; Send Code
        </button>
        <!-- Shown after patient selected: Get Code only -->
        <div class="btn-row" id="btnRowExisting" style="display:none;">
          <button class="btn-modal btn-getotp" onclick="sendOTPForExisting()" type="button" id="btnGetOTP">
            <i class="fas fa-paper-plane"></i>&nbsp; Get Code
          </button>
        </div>
      </div>
    </div>

    <!-- Step 2: verification -->
    <div id="stepOTP" style="display:none;">
      <div class="modal-body">
        <div class="modal-field">
          <label>Enter Verification Code</label>
          <p class="otp-hint">A 6-digit code has been sent to <strong id="sentToNum"></strong></p>
          <div class="otp-boxes" id="otpBoxes">
            <input class="otp-box" maxlength="1" type="tel" />
            <input class="otp-box" maxlength="1" type="tel" />
            <input class="otp-box" maxlength="1" type="tel" />
            <input class="otp-box" maxlength="1" type="tel" />
            <input class="otp-box" maxlength="1" type="tel" />
            <input class="otp-box" maxlength="1" type="tel" />
          </div>
          <p class="modal-err" id="err-otp">Incorrect code. Please try again.</p>
        </div>
        <div class="resend-row">
          Resend code in <span id="otpTimer">60s</span> &nbsp;
          <button class="resend-btn" id="resendBtn" onclick="resendOTP()" disabled type="button">Resend</button>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal" onclick="verifyOTP()" type="button">
          <i class="fas fa-check-circle"></i>&nbsp; Verify &amp; Book Appointment
        </button>
      </div>
    </div>

  </div>
</div>

<!-- ── REGISTRATION MODAL ── -->
<div class="modal-overlay" id="regModalOverlay">
  <div class="modal" id="regModal" style="max-width:500px;">
    <div class="modal-head">
      <h3>Registration</h3>
      <button class="modal-close" onclick="closeRegModal()" type="button">&times;</button>
    </div>
    <div class="modal-body" style="max-height:65vh;overflow-y:auto;">
      <div class="modal-field">
        <label>Salutation <span style="color:#e53e3e">*</span></label>
        <select id="reg-salutation">
          <option value="">Select Salutation</option>
          <option>Mr</option><option>Mrs</option><option>Ms</option>
          <option>Dr</option><option>Master</option><option>Baby</option>
        </select>
        <p class="modal-err" id="err-salutation">Please select a salutation</p>
      </div>
      <div class="modal-field">
        <label>First Name <span style="color:#e53e3e">*</span></label>
        <input type="text" id="reg-firstname" placeholder="Enter First Name" />
        <p class="modal-err" id="err-firstname">Please enter first name</p>
      </div>
      <div class="modal-field">
        <label>Last Name</label>
        <input type="text" id="reg-lastname" placeholder="Enter Last Name" />
      </div>
      <div class="modal-field">
        <label>Gender <span style="color:#e53e3e">*</span></label>
        <select id="reg-gender">
          <option value="">Select Gender</option>
          <option>Male</option><option>Female</option><option>Other</option>
        </select>
        <p class="modal-err" id="err-gender">Please select gender</p>
      </div>
      <div class="modal-field">
        <label>Date of Birth <span style="color:#e53e3e">*</span></label>
        <div class="dob-input-wrap">
          <input type="text" id="reg-dob" placeholder="DD-MM-YYYY" maxlength="10"
                 oninput="formatDOB(this)" />
          <i class="fas fa-calendar-alt dob-cal-icon" onclick="openDOBPicker()" title="Pick a date"></i>
          <input type="date" id="reg-dob-picker" class="dob-hidden-picker" onchange="onDOBPicked(this)" />
        </div>
        <p class="modal-err" id="err-dob">Please enter a valid date (DD-MM-YYYY)</p>
      </div>
      <div class="modal-field">
        <label>Mobile Number <span style="color:#e53e3e">*</span></label>
        <input type="tel" id="reg-mobile" readonly style="background:#f4f6f8;color:#555;" />
      </div>
      <div class="modal-field">
        <label>Email <span style="color:#e53e3e">*</span></label>
        <input type="text" id="reg-email" placeholder="Enter Your Email ID" />
        <p class="modal-err" id="err-email">Please enter a valid email — verification codes are sent there</p>
      </div>
    </div>
    <div class="modal-footer">
      <p class="modal-err" id="err-reg-general" style="margin-top:0;margin-bottom:10px;"></p>
      <button class="btn-modal" onclick="submitRegistration()" type="button" id="btnRegSubmit"
              style="background:linear-gradient(135deg,#28a745,#1565c0);">
        Submit
      </button>
    </div>
  </div>
</div>

<script>
/* ── TIME SLOTS (doctor-configured availability) ── */
const ENABLED_DAYS = <?= json_encode($enabledDays) ?>;

async function fetchSlots(dateStr) {
  try {
    const res  = await fetch(`get_slots.php?doctor=${encodeURIComponent(DOCTOR_KEY)}&date=${encodeURIComponent(dateStr)}`);
    const data = await res.json();
    return { slots: data.slots || [], booked: new Set(data.booked || []), enabled: !!data.enabled };
  } catch (e) {
    return { slots: [], booked: new Set(), enabled: false };
  }
}

/* ── CALENDAR ── */
const todayObj = new Date(); todayObj.setHours(0,0,0,0);
let curYear = todayObj.getFullYear(), curMonth = todayObj.getMonth();
let selectedDate = null;
let selectedSlot = null;

const SHORT_MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

const monthSel = document.getElementById('calMonth');
SHORT_MONTHS.forEach((m, i) => {
  const o = document.createElement('option');
  o.value = i; o.textContent = m; monthSel.appendChild(o);
});
const yearSel = document.getElementById('calYear');
for (let y = todayObj.getFullYear(); y <= todayObj.getFullYear()+5; y++) {
  const o = document.createElement('option'); o.value=y; o.textContent=y; yearSel.appendChild(o);
}

function syncSelects() { monthSel.value = curMonth; yearSel.value = curYear; }

function renderCal() {
  syncSelects();
  const grid = document.getElementById('calDays');
  grid.innerHTML = '';
  const firstDay = new Date(curYear, curMonth, 1).getDay();
  const daysInMonth = new Date(curYear, curMonth+1, 0).getDate();
  for (let i=0; i<firstDay; i++) {
    const b = document.createElement('div'); b.className='cal-day empty'; grid.appendChild(b);
  }
  for (let d=1; d<=daysInMonth; d++) {
    const dt   = new Date(curYear, curMonth, d);
    const cell = document.createElement('div');
    cell.textContent = d;
    let cls = 'cal-day';
    if (dt.getDay()===0) cls += ' sunday';
    if (!ENABLED_DAYS.includes(dt.getDay())) cls += ' closed';
    if (dt < todayObj)   cls += ' past';
    else if (dt.getTime()===todayObj.getTime()) cls += ' today';
    if (selectedDate && dt.getTime()===selectedDate.getTime()) cls = cls.replace('today','') + ' selected';
    cell.className = cls;
    if (!cls.includes('past') && !cls.includes('closed')) cell.addEventListener('click', () => pickDate(dt));
    grid.appendChild(cell);
  }
}

function pickDate(dt) {
  selectedDate = dt;
  selectedSlot = null;
  const dd = String(dt.getDate()).padStart(2,'0');
  const mm = String(dt.getMonth()+1).padStart(2,'0');
  const yy = dt.getFullYear();
  const disp = document.getElementById('dateDisplay');
  disp.textContent = `${dd}-${mm}-${yy}`;
  disp.classList.add('filled');
  document.getElementById('err-date').classList.remove('show');
  closeCal();
  renderCal();
  showSlots(`${dd}-${mm}-${yy}`);
}

function toggleCal() {
  const cal = document.getElementById('customCal');
  cal.classList.contains('open') ? closeCal() : (renderCal(), cal.classList.add('open'));
}
function closeCal() { document.getElementById('customCal').classList.remove('open'); }
function prevMonth() {
  curMonth--; if (curMonth<0){curMonth=11;curYear--;}
  if (curYear<todayObj.getFullYear()||(curYear===todayObj.getFullYear()&&curMonth<todayObj.getMonth())){curMonth=todayObj.getMonth();curYear=todayObj.getFullYear();}
  renderCal();
}
function nextMonth() { curMonth++; if(curMonth>11){curMonth=0;curYear++;} renderCal(); }
function onMonthChange() { curMonth=parseInt(monthSel.value); renderCal(); }
function onYearChange()  { curYear=parseInt(yearSel.value);  renderCal(); }

document.addEventListener('click', (e) => {
  const cal  = document.getElementById('customCal');
  const wrap = document.querySelector('.date-field-wrap');
  if (!cal.contains(e.target) && !wrap.contains(e.target)) closeCal();
});

/* ── SLOTS ── */
async function showSlots(dateLabel) {
  const panel  = document.getElementById('slotsPanel');
  const grid   = document.getElementById('slotsGrid');
  const title  = document.getElementById('slotsTitle');
  title.textContent = `Available Slots – ${dateLabel}`;
  grid.innerHTML = '<p style="color:#888;font-size:13px;">Loading slots...</p>';
  panel.classList.add('show');

  const { slots, booked, enabled } = await fetchSlots(dateLabel);
  grid.innerHTML = '';

  if (!enabled || slots.length === 0) {
    grid.innerHTML = '<p style="color:#888;font-size:13px;">Doctor is not available on this day. Please choose another date.</p>';
    panel.classList.add('show');
    setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
    return;
  }

  // Check if selected date is today
  const _t = new Date();
  const todayLabel = `${String(_t.getDate()).padStart(2,'0')}-${String(_t.getMonth()+1).padStart(2,'0')}-${_t.getFullYear()}`;
  const isToday = dateLabel === todayLabel;

  slots.forEach((label) => {
    const isBooked = booked.has(label);

    // For today, check if the slot's end time has already passed
    let isPast = false;
    if (isToday) {
      const endPart = label.split(' - ')[1]; // e.g. "08:45 AM"
      const [timePart, ampm] = endPart.split(' ');
      const [hh, mm] = timePart.split(':').map(Number);
      let hours = hh % 12;
      if (ampm === 'PM') hours += 12;
      const slotEnd = new Date();
      slotEnd.setHours(hours, mm, 0, 0);
      isPast = slotEnd <= new Date();
    }

    const div = document.createElement('div');
    div.textContent = label;
    if (isBooked) {
      div.className = 'slot booked';
      div.title     = 'Already booked';
    } else if (isPast) {
      div.className = 'slot past';
      div.title     = 'This slot has passed';
    } else {
      div.className = 'slot available';
      div.title     = 'Click to select';
      div.addEventListener('click', () => openBookingModal(label));
    }
    grid.appendChild(div);
  });

  panel.classList.add('show');
  setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
}

/* ── SLOT CLICK → OPEN MODAL ── */
function openBookingModal(slot) {
  selectedSlot = slot;
  const dateLabel = document.getElementById('dateDisplay').textContent;
  document.getElementById('modalTitle').textContent   = 'Booking at ' + dateLabel;
  document.getElementById('modalSlotDisplay').value   = slot;
  document.getElementById('mobileInput').value        = '';
  document.getElementById('stepMobile').style.display = 'block';
  document.getElementById('stepOTP').style.display    = 'none';
  document.getElementById('err-otp').classList.remove('show');
  resetStep1();
  // Clear any previous OTP session so a fresh OTP can be sent (different doctor/slot)
  fetch('reset_otp_session.php', { method: 'POST' });
  document.getElementById('modalOverlay').classList.add('open');
  setTimeout(() => document.getElementById('mobileInput').focus(), 120);
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  clearInterval(timerInterval);
}

document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

/* ── DOCTOR KEY (for booking) ── */
const DOCTOR_KEY = '<?= htmlspecialchars($key) ?>';

/* ── STATE ── */
let timerInterval   = null;
let selectedPatient = null;   // { id, name }

/* ── RESET MODAL STEP 1 ── */
function resetStep1() {
  document.getElementById('regPrompt').style.display          = 'none';
  document.getElementById('patientFoundSection').style.display = 'none';
  document.getElementById('btnSendOTP').style.display          = 'inline-block';
  document.getElementById('btnRowExisting').style.display      = 'none';
  document.getElementById('err-mobile').classList.remove('show');
  document.getElementById('err-patient').classList.remove('show');
  document.getElementById('patientSelect').innerHTML           = '<option value="">Select Patient Name</option>';
  document.getElementById('otpHint').style.display            = 'none';
  selectedPatient = null;
}

/* ── STEP 1: CHECK PATIENT ── */
async function checkPatient() {
  const mobile = document.getElementById('mobileInput').value.trim();
  const digits = mobile.replace(/\D/g, '');
  if (digits.length < 10) {
    document.getElementById('err-mobile').classList.add('show');
    return;
  }
  document.getElementById('err-mobile').classList.remove('show');
  document.getElementById('regPrompt').style.display           = 'none';
  document.getElementById('patientFoundSection').style.display = 'none';
  document.getElementById('btnRowExisting').style.display      = 'none';
  document.getElementById('btnSendOTP').style.display          = 'inline-block';

  const btn = document.getElementById('btnSendOTP');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp; Checking...';

  try {
    const res  = await fetch('check_patient.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    'mobile=' + encodeURIComponent(digits),
    });
    const data = await res.json();

    if (data.exists) {
      // Populate patient dropdown
      const sel = document.getElementById('patientSelect');
      sel.innerHTML = '<option value="">Select Patient Name</option>';
      data.patients.forEach(p => {
        const opt = document.createElement('option');
        opt.value       = p.id;
        opt.textContent = p.name;
        sel.appendChild(opt);
      });
      document.getElementById('patientFoundSection').style.display = 'block';
      // Hide Send OTP button (will show Submit + Get OTP after patient selected)
      btn.style.display = 'none';
    } else {
      // New patient
      document.getElementById('regPrompt').style.display = 'block';
      document.getElementById('reg-mobile').value = digits;
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i>&nbsp; Send Code';
    }
  } catch (e) {
    document.getElementById('err-mobile').textContent = 'Network error. Please try again.';
    document.getElementById('err-mobile').classList.add('show');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i>&nbsp; Send OTP';
  }
}

/* ── PATIENT DROPDOWN CHANGE ── */
function onPatientSelect() {
  const sel = document.getElementById('patientSelect');
  document.getElementById('err-patient').classList.remove('show');
  if (sel.value) {
    selectedPatient = { id: sel.value, name: sel.options[sel.selectedIndex].text };
    document.getElementById('btnRowExisting').style.display = 'flex';
    document.getElementById('otpHint').style.display = 'block';
  } else {
    selectedPatient = null;
    document.getElementById('btnRowExisting').style.display = 'none';
    document.getElementById('otpHint').style.display = 'none';
  }
}

/* ── SUBMIT DIRECTLY (no OTP) ── */
async function submitDirect() {
  if (!selectedPatient) {
    document.getElementById('err-patient').classList.add('show'); return;
  }
  const btns = document.querySelectorAll('#btnRowExisting button');
  btns.forEach(b => b.disabled = true);
  document.querySelector('.btn-submit').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

  // Set session patient_id via a quick register call (reuses existing patient)
  await setSessionPatient(selectedPatient.id);

  const dateLabel = document.getElementById('dateDisplay').textContent;
  try {
    const body    = new URLSearchParams({ doctor: DOCTOR_KEY, date: dateLabel, timeslot: selectedSlot });
    const bookRes = await fetch('book_appointment.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const bookData = await bookRes.json();
    if (bookData.success) {
      showSuccess(bookData.ref);
    } else {
      alert(bookData.message || 'Booking failed.');
      btns.forEach(b => b.disabled = false);
      document.querySelector('.btn-submit').innerHTML = 'Submit';
    }
  } catch (e) {
    alert('Network error. Please try again.');
    btns.forEach(b => b.disabled = false);
    document.querySelector('.btn-submit').innerHTML = 'Submit';
  }
}

async function setSessionPatient(patientId) {
  await fetch('set_patient_session.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body:    'patient_id=' + encodeURIComponent(patientId),
  });
}

function showSuccess(ref) {
  clearInterval(timerInterval);
  closeModal();
  document.getElementById('refNum').textContent = 'REF: #' + (ref || '<i class="fas fa-stethoscope"></i>-' + Math.floor(100000 + Math.random()*900000));

  // Show only the booking confirmation — hide the doctor details / date picker and the slots panel
  document.getElementById('doctorCard').style.display = 'none';
  document.getElementById('slotsPanel').classList.remove('show');
  document.getElementById('successView').style.display = 'block';
  document.getElementById('successView').scrollIntoView({ behavior: 'smooth', block: 'center' });

  selectedDate = null;
  selectedSlot = null;
  const disp = document.getElementById('dateDisplay');
  disp.textContent = 'DD-MM-YYYY';
  disp.classList.remove('filled');
}

/* ── GET OTP FOR EXISTING PATIENT ── */
async function sendOTPForExisting() {
  if (!selectedPatient) {
    document.getElementById('err-patient').classList.add('show'); return;
  }
  document.getElementById('otpHint').style.display = 'none';

  const btn = document.getElementById('btnGetOTP');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp; Sending...';

  await setSessionPatient(selectedPatient.id);
  const mobile = document.getElementById('mobileInput').value.trim().replace(/\D/g, '');
  await doSendOTP(mobile, btn);
}

async function doSendOTP(mobile, btn) {
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp; Sending...'; }
  try {
    const res  = await fetch('send_otp.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    'mobile=' + encodeURIComponent(mobile),
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById('stepMobile').style.display = 'none';
      document.getElementById('stepOTP').style.display    = 'block';
      document.getElementById('sentToNum').textContent    = data.email || 'your email';
      document.querySelectorAll('.otp-box').forEach(b => { b.value = ''; b.style.borderColor = ''; });
      document.getElementById('err-otp').classList.remove('show');
      setTimeout(() => document.querySelectorAll('.otp-box')[0].focus(), 100);
      startTimer();
    } else {
      document.getElementById('err-mobile').textContent = data.message || 'Failed to send code. Try again.';
      document.getElementById('err-mobile').classList.add('show');
    }
  } catch (e) {
    document.getElementById('err-mobile').textContent = 'Network error. Please try again.';
    document.getElementById('err-mobile').classList.add('show');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = btn.id === 'btnGetOTP'
        ? '<i class="fas fa-paper-plane"></i>&nbsp; Get Code'
        : '<i class="fas fa-paper-plane"></i>&nbsp; Send Code';
    }
  }
}

/* ── REGISTRATION MODAL ── */
function openRegModal() {
  document.getElementById('regModalOverlay').classList.add('open');
  document.getElementById('reg-salutation').value = '';
  document.getElementById('reg-firstname').value  = '';
  document.getElementById('reg-lastname').value   = '';
  document.getElementById('reg-gender').value     = '';
  document.getElementById('reg-dob').value        = '';
  document.getElementById('reg-email').value      = '';
  document.querySelectorAll('#regModal .modal-err').forEach(e => e.classList.remove('show'));
  setTimeout(() => document.getElementById('reg-firstname').focus(), 150);
}

function closeRegModal() {
  document.getElementById('regModalOverlay').classList.remove('open');
}

function formatDOB(input) {
  let v = input.value.replace(/\D/g, '').slice(0, 8);
  if (v.length > 4) v = v.slice(0,2) + '-' + v.slice(2,4) + '-' + v.slice(4);
  else if (v.length > 2) v = v.slice(0,2) + '-' + v.slice(2);
  input.value = v;
}

function openDOBPicker() {
  const picker = document.getElementById('reg-dob-picker');
  const today  = new Date();
  picker.max   = today.toISOString().split('T')[0];
  if (picker.showPicker) { picker.showPicker(); } else { picker.click(); }
}

function onDOBPicked(input) {
  if (!input.value) return;
  const [y, m, d] = input.value.split('-');
  document.getElementById('reg-dob').value = `${d}-${m}-${y}`;
  document.getElementById('err-dob').classList.remove('show');
}

async function submitRegistration() {
  let valid = true;
  const salutation = document.getElementById('reg-salutation').value;
  const firstName  = document.getElementById('reg-firstname').value.trim();
  const lastName   = document.getElementById('reg-lastname').value.trim();
  const gender     = document.getElementById('reg-gender').value;
  const dob        = document.getElementById('reg-dob').value.trim();
  const mobile     = document.getElementById('reg-mobile').value.trim();
  const email      = document.getElementById('reg-email').value.trim();

  const fieldErrors = [];
  const show = (id, label) => { document.getElementById(id).classList.add('show'); fieldErrors.push(label); valid = false; };
  const hide = (id) => document.getElementById(id).classList.remove('show');

  !salutation ? show('err-salutation', 'Salutation') : hide('err-salutation');
  !firstName  ? show('err-firstname', 'First Name')  : hide('err-firstname');
  !gender     ? show('err-gender', 'Gender')          : hide('err-gender');
  if (!dob || !/^\d{2}-\d{2}-\d{4}$/.test(dob)) { show('err-dob', 'Date of Birth'); } else { hide('err-dob'); }
  if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { show('err-email', 'Email'); } else { hide('err-email'); }

  const generalErr = document.getElementById('err-reg-general');
  if (!valid) {
    generalErr.textContent = 'Please fix: ' + fieldErrors.join(', ');
    generalErr.classList.add('show');
    return;
  }
  generalErr.classList.remove('show');

  const btn = document.getElementById('btnRegSubmit');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp; Registering...';

  try {
    const body = new URLSearchParams({ salutation, first_name: firstName, last_name: lastName, gender, dob, mobile, email });
    const res  = await fetch('register_patient.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const data = await res.json();

    if (data.success) {
      closeRegModal();
      document.getElementById('regPrompt').style.display = 'none';
      // Automatically send OTP after registration
      await doSendOTP(mobile, null);
    } else {
      document.getElementById('err-reg-general').textContent = data.message || 'Registration failed.';
      document.getElementById('err-reg-general').classList.add('show');
    }
  } catch (e) {
    document.getElementById('err-reg-general').textContent = 'Network error. Please try again.';
    document.getElementById('err-reg-general').classList.add('show');
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Submit';
  }
}

/* ── OTP BOX NAVIGATION ── */
document.querySelectorAll('.otp-box').forEach((box, idx, boxes) => {
  box.addEventListener('input', () => {
    box.value = box.value.replace(/\D/,'').slice(-1);
    if (box.value && idx < boxes.length - 1) boxes[idx+1].focus();
  });
  box.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace' && !box.value && idx > 0) boxes[idx-1].focus();
  });
  box.addEventListener('paste', (e) => {
    e.preventDefault();
    const txt = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
    boxes.forEach((b, i) => { b.value = txt[i] || ''; });
    const next = Math.min(txt.length, 5);
    boxes[next].focus();
  });
});

/* ── TIMER ── */
function startTimer() {
  let sec = 60;
  const timerEl  = document.getElementById('otpTimer');
  const resendBtn = document.getElementById('resendBtn');
  resendBtn.disabled = true;
  timerEl.textContent = sec + 's';
  clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    sec--;
    timerEl.textContent = sec > 0 ? sec + 's' : '';
    if (sec <= 0) {
      clearInterval(timerInterval);
      resendBtn.disabled = false;
      timerEl.textContent = '';
    }
  }, 1000);
}

async function resendOTP() {
  const mobile = document.getElementById('sentToNum').textContent;
  document.querySelectorAll('.otp-box').forEach(b => { b.value = ''; b.style.borderColor = ''; });
  document.getElementById('err-otp').classList.remove('show');
  const btn = document.getElementById('resendBtn');
  btn.disabled = true;
  await doSendOTP(mobile, null);
  startTimer();
}

/* ── VERIFY OTP ── */
async function verifyOTP() {
  const entered = Array.from(document.querySelectorAll('.otp-box')).map(b=>b.value).join('');
  if (entered.length < 6) {
    document.getElementById('err-otp').textContent = 'Please enter the complete 6-digit OTP.';
    document.getElementById('err-otp').classList.add('show');
    return;
  }

  const btn = document.querySelector('#stepOTP .btn-modal');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp; Verifying...';

  try {
    const res  = await fetch('verify_otp.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    'otp=' + encodeURIComponent(entered),
    });
    const data = await res.json();

    if (data.success) {
      // Book the appointment
      const dateLabel = document.getElementById('dateDisplay').textContent;
      const body = new URLSearchParams({ doctor: DOCTOR_KEY, date: dateLabel, timeslot: selectedSlot });
      const bookRes  = await fetch('book_appointment.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
      const bookData = await bookRes.json();
      showSuccess(bookData.ref);
    } else {
      document.getElementById('err-otp').textContent = data.message || 'Incorrect OTP. Please try again.';
      document.getElementById('err-otp').classList.add('show');
      document.querySelectorAll('.otp-box').forEach(b => b.style.borderColor = '#dc3545');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-check-circle"></i>&nbsp; Verify &amp; Book Appointment';
    }
  } catch (e) {
    document.getElementById('err-otp').textContent = 'Network error. Please try again.';
    document.getElementById('err-otp').classList.add('show');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-check-circle"></i>&nbsp; Verify &amp; Book Appointment';
  }
}
</script>
</body>
</html>
