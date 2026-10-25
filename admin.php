<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

// Already logged in
if (isDoctorLoggedIn()) { header('Location: admin-dashboard.php'); exit; }

// No admin configured yet
$authData = getDoctorAuth();
if (empty($authData['password_hash'])) { header('Location: admin-setup.php'); exit; }

$error      = '';
$loggedOut  = isset($_GET['logged_out']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === $authData['username'] && password_verify($password, $authData['password_hash'])) {
        $_SESSION['doctor_logged_in'] = true;
        $_SESSION['doctor_username']  = $username;
        header('Location: admin-dashboard.php');
        exit;
    } else {
        // Brief sleep to slow brute force
        sleep(1);
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Doctor Login — Dr. Prasanna Kumar K</title>
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
      <p>Medical Management Portal</p>
      <span class="role-badge doctor-badge"><i class="fas fa-user-md"></i> Doctor Access</span>
    </div>

    <?php if ($loggedOut): ?>
      <div class="login-success show"><i class="fas fa-check-circle"></i> Logged out successfully.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="login-error show"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
      <div class="input-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Doctor username" required autofocus autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />
      </div>
      <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Your password" required autocomplete="current-password" />
      </div>
      <button type="submit" class="login-btn">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div style="text-align:center;margin-top:28px;">
      <a href="index.php" style="font-size:13px;color:var(--gray);"><i class="fas fa-arrow-left"></i> Back to website</a>
    </div>
  </div>
</div>
</body>
</html>
