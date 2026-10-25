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

    /* HEADER */
    .appt-header {
      padding: 18px 36px;
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .appt-header a {
      display: inline-flex;
      align-items: center;
      gap: 14px;
      text-decoration: none;
    }
    .logo-icon {
      width: 62px;
      height: 62px;
      background: linear-gradient(135deg, #0f2b5b, #1565c0);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
      font-weight: 800;
      box-shadow: 0 4px 14px rgba(21,101,192,0.3);
    }
    .logo-text h1 { font-size: 17px; font-weight: 700; color: #1a1a2e; line-height: 1.2; }
    .logo-text span { font-size: 12px; color: #1565c0; font-weight: 500; }

    /* MAIN */
    .appt-main {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }

    .appt-card {
      background: white;
      border-radius: 16px;
      padding: 44px 48px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.18);
      animation: slideUp 0.4s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .appt-card h2 {
      font-size: 26px;
      font-weight: 700;
      color: #1a1a2e;
      text-align: center;
      margin-bottom: 32px;
    }

    .form-group { margin-bottom: 18px; }

    .select-wrap { position: relative; }
    .select-wrap::after {
      content: '\f078';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
      pointer-events: none;
      font-size: 12px;
    }

    .select-wrap select {
      width: 100%;
      padding: 13px 40px 13px 14px;
      border: 2px solid #e8eaed;
      border-radius: 10px;
      font-size: 14px;
      font-family: 'Roboto', sans-serif;
      color: #1a1a2e;
      background: white;
      outline: none;
      appearance: none;
      -webkit-appearance: none;
      cursor: pointer;
      transition: border-color 0.25s;
    }
    .select-wrap select:focus { border-color: #1565c0; }

    .error-msg {
      color: #e53e3e;
      font-size: 12px;
      margin-top: 6px;
      display: none;
    }
    .error-msg.show { display: block; }

    @media (max-width: 520px) {
      .appt-card { padding: 30px 22px; }
      .appt-header { padding: 14px 20px; }
    }
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

<main class="appt-main">
  <div class="appt-card">
    <h2>Book an Appointment</h2>

    <div class="form-group">
      <div class="select-wrap">
        <select id="doctorSelect" onchange="handleDoctorSelect(this)">
          <option value="">Select Doctor Name</option>
          <option value="prasanna">Dr. Prasanna Kumar K</option>
        </select>
      </div>
      <p class="error-msg" id="err-doctor">Please select doctor from the list</p>
    </div>
  </div>
</main>

<script>
  function handleDoctorSelect(sel) {
    if (!sel.value) return;
    window.location.href = 'doctor-details.php?doctor=' + sel.value;
  }
</script>
</body>
</html>
