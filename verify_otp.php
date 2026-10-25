<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$entered = trim($_POST['otp'] ?? '');

// Session check
if (empty($_SESSION['otp'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please request a new code.']);
    exit;
}

// Expiry check
if (time() > $_SESSION['otp_expiry']) {
    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_attempts'], $_SESSION['otp_sent_at']);
    echo json_encode(['success' => false, 'message' => 'Code expired. Please request a new one.']);
    exit;
}

// Attempt limit check
$_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
if ($_SESSION['otp_attempts'] > OTP_MAX_ATTEMPTS) {
    unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_attempts'], $_SESSION['otp_sent_at']);
    echo json_encode(['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.']);
    exit;
}

// Verify
if ($entered !== $_SESSION['otp']) {
    $remaining = OTP_MAX_ATTEMPTS - $_SESSION['otp_attempts'];
    $msg = $remaining > 0
        ? "Incorrect code. {$remaining} attempt(s) remaining."
        : 'Incorrect code.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Success — clear verification session data
unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_attempts'], $_SESSION['otp_sent_at'], $_SESSION['otp_mobile']);

echo json_encode(['success' => true, 'message' => 'Code verified successfully.']);
