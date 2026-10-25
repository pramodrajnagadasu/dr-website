<?php
/**
 * Outbound email helpers — replaces the old Fast2SMS OTP/notification service.
 * Sends via authenticated SMTP (PHPMailer), since Gmail and most real mail
 * providers reject unauthenticated mail from PHP's native mail().
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php'; // logMailAttempt() writes to the mail_log table

// config-smtp.local.php is a local-dev-only file (real Gmail credentials) and must
// never be uploaded to the production server. When absent, mail falls back to
// PHP's native mail() exactly as before — the server's own relay is untouched.
$__smtpLocalConfig = __DIR__ . '/config-smtp.local.php';
if (file_exists($__smtpLocalConfig)) {
    require_once $__smtpLocalConfig;
    require_once __DIR__ . '/lib/phpmailer/Exception.php';
    require_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/lib/phpmailer/SMTP.php';
}
unset($__smtpLocalConfig);

function sendAppEmail(string $to, string $subject, string $body): bool {
    if (defined('SMTP_HOST')) {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->Port       = SMTP_PORT;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;

            $mail->setFrom(SMTP_USERNAME, 'Dr. Prasanna Kumar K');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->CharSet = 'UTF-8';

            $ok = $mail->send();
            logMailAttempt($ok, $to, $subject, $ok ? '' : $mail->ErrorInfo);
            return $ok;
        } catch (\Throwable $e) {
            logMailAttempt(false, $to, $subject, $e->getMessage());
            error_log('sendAppEmail failed: ' . $e->getMessage());
            return false;
        }
    }

    // Production: original behavior, unchanged — native mail() via the server's own relay.
    $headers = "From: " . MAIL_FROM . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $ok = @mail($to, $subject, $body, $headers);
    logMailAttempt($ok, $to, $subject);
    return $ok;
}

function sendVerificationEmail(string $to, string $code): bool {
    $minutes = (int) (OTP_EXPIRY_SECONDS / 60);
    $subject = 'Your verification code — Dr. Prasanna Kumar K';
    $body    = "Your verification code is: {$code}\n\n"
             . "This code is valid for {$minutes} minutes.\n"
             . "If you did not request this, you can safely ignore this email.";
    return sendAppEmail($to, $subject, $body);
}

function sendDoctorBookingAlert(string $ref, string $patientName, string $mobile, string $gender, string $dob, string $date, string $slot): bool {
    $subject = "New Appointment — {$ref}";
    $body    = "A new appointment has been booked.\n\n"
             . "Ref: {$ref}\n"
             . "Patient: {$patientName}\n"
             . "Mobile: {$mobile}\n"
             . "Gender: {$gender}\n"
             . "DOB: {$dob}\n"
             . "Date: {$date}\n"
             . "Slot: {$slot}";
    return sendAppEmail(DOCTOR_NOTIFY_EMAIL, $subject, $body);
}

function sendAppointmentConfirmedEmail(string $to, string $ref, string $patientName, string $date, string $slot): bool {
    $subject = "Appointment Confirmed — {$ref}";
    $body    = "Dear {$patientName},\n\n"
             . "Your appointment with Dr. Prasanna Kumar K has been successfully booked.\n\n"
             . "Ref: {$ref}\n"
             . "Date: {$date}\n"
             . "Slot: {$slot}\n\n"
             . "Please arrive 10 minutes early. If you need to cancel or reschedule, contact the clinic.";
    return sendAppEmail($to, $subject, $body);
}

function sendAppointmentCancelledEmail(string $to, string $ref, string $patientName, string $date, string $slot, string $reason): bool {
    $subject = "Appointment Cancelled — {$ref}";
    $body    = "Appointment {$ref} has been cancelled.\n\n"
             . "Patient: {$patientName}\n"
             . "Date: {$date}\n"
             . "Slot: {$slot}\n"
             . "Reason: {$reason}";
    return sendAppEmail($to, $subject, $body);
}

function sendAppointmentPostponedEmail(string $to, string $ref, string $patientName, string $oldDate, string $oldSlot, string $newDate, string $newSlot, string $reason): bool {
    $subject = "Appointment Rescheduled — {$ref}";
    $body    = "Appointment {$ref} has been rescheduled.\n\n"
             . "Patient: {$patientName}\n"
             . "Previous: {$oldDate}, {$oldSlot}\n"
             . "New: {$newDate}, {$newSlot}\n"
             . ($reason !== '' ? "Reason: {$reason}" : '');
    return sendAppEmail($to, $subject, $body);
}

function sendPaymentRequestEmail(string $to, string $ref, string $patientName, string $amountRupees): bool {
    $subject = "Consultation Fee Due — {$ref}";
    $link    = SITE_URL . '/payment.php?ref=' . urlencode($ref);
    $body    = "Dear {$patientName},\n\n"
             . "Thank you for your recent consultation with Dr. Prasanna Kumar K.\n\n"
             . "Ref: {$ref}\n"
             . "Amount due: ₹{$amountRupees}\n\n"
             . "You can pay securely (UPI, debit or credit card) here:\n{$link}\n\n"
             . "Please log in to the patient portal with your registered mobile number to complete payment.";
    return sendAppEmail($to, $subject, $body);
}

function sendPaymentReceivedEmail(string $to, string $ref, string $patientName, string $amountRupees, string $paymentId): bool {
    $subject = "Payment Received — {$ref}";
    $body    = "Dear {$patientName},\n\n"
             . "We've received your payment for the consultation.\n\n"
             . "Ref: {$ref}\n"
             . "Amount paid: ₹{$amountRupees}\n"
             . "Payment ID: {$paymentId}\n\n"
             . "Thank you.";
    return sendAppEmail($to, $subject, $body);
}

function maskEmail(string $email): string {
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) return $email;
    [$user, $domain] = $parts;
    if (strlen($user) <= 2) return $user[0] . '***@' . $domain;
    return substr($user, 0, 2) . str_repeat('*', max(strlen($user) - 2, 1)) . '@' . $domain;
}
