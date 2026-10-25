<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../mailer.php';
require_once __DIR__ . '/../lib/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Invalid request.', 405);

$digits = preg_replace('/\D/', '', trim($_POST['mobile'] ?? ''));
if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
    $digits = substr($digits, 2);
}
if (strlen($digits) !== 10) {
    apiError('Please enter a valid 10-digit mobile number.');
}

// Rate limiting: prevent resend spam
$pending = findLatestOtpRequest($digits);
if ($pending) {
    $sentAt = strtotime($pending['created_at']);
    if ((time() - $sentAt) < OTP_RESEND_COOLDOWN) {
        $wait = OTP_RESEND_COOLDOWN - (time() - $sentAt);
        apiError("Please wait {$wait}s before requesting another code.");
    }
}

// Verification codes go to the email on file — find matching patient account(s)
$patients = findPatientsByMobile($digits);
if (empty($patients)) {
    apiError('No account found for this mobile number. Please register or book an appointment first.');
}

$emails = [];
foreach ($patients as $p) {
    if (!empty($p['email'])) $emails[] = strtolower(trim($p['email']));
}
$emails = array_values(array_unique($emails));

if (empty($emails)) {
    apiError('No email is on file for this account. Please contact the clinic.');
}

$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

$sent = false;
foreach ($emails as $email) {
    if (sendVerificationEmail($email, $code)) $sent = true;
}
if (!$sent) {
    apiError('Failed to send verification email. Please try again.');
}

createOtpRequest($digits, $code, OTP_EXPIRY_SECONDS);

$maskedEmail = maskEmail($emails[0]);
apiJson(['success' => true, 'message' => "Verification code sent to {$maskedEmail}", 'email' => $maskedEmail]);
