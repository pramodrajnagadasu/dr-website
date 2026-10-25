<?php
/**
 * Combines web's verify_otp.php + portal-login.php into one bearer-token
 * issuing endpoint for the app.
 *
 * Call 1: { mobile, otp } -> verifies the code.
 *   - exactly one patient on that mobile: issues a token immediately.
 *   - multiple patients: returns needs_patient_selection + a short-lived
 *     verify_token, so the app can let the user pick a profile without
 *     re-sending the OTP.
 * Call 2 (only if needed): { mobile, verify_token, patient_id } -> issues a token.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

const TICKET_EXPIRY_SECONDS = 300;      // 5 minutes to pick a profile
const TOKEN_EXPIRY_SECONDS  = 15552000; // 180 days

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Invalid request.', 405);

function normalizeMobile(string $raw): string {
    $digits = preg_replace('/\D/', '', $raw);
    if (strlen($digits) === 12 && substr($digits, 0, 2) === '91') {
        $digits = substr($digits, 2);
    }
    return $digits;
}

function issueTokenForPatient(array $patient): never {
    $token = createApiToken($patient['id'], TOKEN_EXPIRY_SECONDS);
    apiJson(['success' => true, 'token' => $token, 'patient' => $patient]);
}

$mobile = normalizeMobile(trim($_POST['mobile'] ?? ''));
if (strlen($mobile) !== 10) apiError('Invalid mobile number.');

$verifyToken = trim($_POST['verify_token'] ?? '');
$patientId   = trim($_POST['patient_id'] ?? '');

// ── Step 2: already verified, just picking a profile ──
if ($verifyToken !== '') {
    if ($patientId === '') apiError('Missing patient_id.');
    $ticket = findValidOtpTicket($mobile, $verifyToken);
    if (!$ticket) apiError('Session expired. Please request a new code.', 401);

    $patient = findPatientById($patientId);
    if (!$patient || $patient['mobile'] !== $mobile) apiError('Patient not found for this mobile number.');

    issueTokenForPatient($patient);
}

// ── Step 1: verify the OTP ──
$entered = trim($_POST['otp'] ?? '');
if ($entered === '') apiError('Missing verification code.');

$pending = findLatestOtpRequest($mobile);
if (!$pending) apiError('Session expired. Please request a new code.');

if (strtotime($pending['expires_at']) < time()) {
    apiError('Code expired. Please request a new one.');
}
if ((int) $pending['attempts'] >= OTP_MAX_ATTEMPTS) {
    apiError('Too many incorrect attempts. Please request a new code.');
}

if (hash('sha256', $entered) !== $pending['code_hash']) {
    incrementOtpAttempts((int) $pending['id']);
    $remaining = OTP_MAX_ATTEMPTS - ((int) $pending['attempts'] + 1);
    $msg = $remaining > 0 ? "Incorrect code. {$remaining} attempt(s) remaining." : 'Incorrect code.';
    apiError($msg);
}

$patients = findPatientsByMobile($mobile);
if (empty($patients)) {
    apiError('No patient record found for this mobile. Please register or book an appointment first.');
}

if (count($patients) === 1) {
    issueTokenForPatient($patients[0]);
}

$ticketToken = markOtpVerified((int) $pending['id'], TICKET_EXPIRY_SECONDS);
apiJson([
    'success'                 => true,
    'needs_patient_selection' => true,
    'verify_token'            => $ticketToken,
    'patients'                => array_map(fn($p) => [
        'id'     => $p['id'],
        'name'   => strtoupper(trim($p['first_name'] . ' ' . $p['last_name'])),
        'gender' => $p['gender'] ?? null,
        'dob'    => $p['dob'] ?? null,
    ], $patients),
]);
