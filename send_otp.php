<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';
require_once 'db.php';
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$mobile = trim($_POST['mobile'] ?? '');
$digits = preg_replace('/\D/', '', $mobile);

if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
    $digits = substr($digits, 2);
}
if (strlen($digits) !== 10) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.']);
    exit;
}

// Rate limiting: prevent resend spam
if (
    isset($_SESSION['otp_sent_at']) &&
    (time() - $_SESSION['otp_sent_at']) < OTP_RESEND_COOLDOWN
) {
    $wait = OTP_RESEND_COOLDOWN - (time() - $_SESSION['otp_sent_at']);
    echo json_encode(['success' => false, 'message' => "Please wait {$wait}s before requesting another code."]);
    exit;
}

// Verification codes go to the email on file — find matching patient account(s)
$patients = findPatientsByMobile($digits);
if (empty($patients)) {
    echo json_encode(['success' => false, 'message' => 'No account found for this mobile number. Please book an appointment first.']);
    exit;
}

$emails = [];
foreach ($patients as $p) {
    if (!empty($p['email'])) $emails[] = strtolower(trim($p['email']));
}
$emails = array_values(array_unique($emails));

if (empty($emails)) {
    echo json_encode(['success' => false, 'message' => 'No email is on file for this account. Please contact the clinic.']);
    exit;
}

$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

$sent = false;
foreach ($emails as $email) {
    if (sendVerificationEmail($email, $code)) $sent = true;
}
if (!$sent) {
    echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again.']);
    exit;
}

// Store in session
$_SESSION['otp']          = $code;
$_SESSION['otp_mobile']   = $digits;
$_SESSION['otp_expiry']   = time() + OTP_EXPIRY_SECONDS;
$_SESSION['otp_attempts'] = 0;
$_SESSION['otp_sent_at']  = time();

$maskedEmail = maskEmail($emails[0]);
echo json_encode(['success' => true, 'message' => "Verification code sent to {$maskedEmail}", 'email' => $maskedEmail]);
