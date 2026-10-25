<?php
/**
 * First-time doctor admin setup.
 * Only accessible when no password has been configured yet.
 * Once set up, this page redirects to admin.php.
 */
session_start();
require_once 'db.php';

$existing = getDoctorAuth();

// Already configured — go to login
if (!empty($existing['password_hash'])) {
    header('Location: admin.php');
    exit;
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        saveDoctorAuth($username, password_hash($password, PASSWORD_BCRYPT));
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Setup — Dr. Prasanna Kumar K</title>
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
      <p>First-time admin setup</p>
      <span class="role-badge doctor-badge">Doctor Portal Setup</span>
    </div>

    <?php if ($success): ?>
      <div class="login-success show">
        <i class="fas fa-check-circle"></i> Admin account created successfully!
      </div>
      <a href="admin.php" class="login-btn" style="text-decoration:none;margin-top:0;">
        <i class="fas fa-sign-in-alt"></i> Go to Login
      </a>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="login-error show"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="input-group">
          <label>Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="e.g. prasanna" required autocomplete="username" />
        </div>
        <div class="input-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Min. 8 characters" required autocomplete="new-password" />
        </div>
        <div class="input-group">
          <label>Confirm Password</label>
          <input type="password" name="confirm" placeholder="Repeat password" required autocomplete="new-password" />
        </div>
        <button type="submit" class="login-btn">
          <i class="fas fa-lock"></i> Create Admin Account
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
