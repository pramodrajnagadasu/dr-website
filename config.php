<?php
// ─────────────────────────────────────────────
//  Email / Verification Configuration
// ─────────────────────────────────────────────

// Address patient/booking verification codes and appointment mail are sent from
define('MAIL_FROM', 'noreply@drprasannakumark.com');

// Doctor's email — receives a notification for every new booking
define('DOCTOR_NOTIFY_EMAIL', 'docprasanna@yahoo.com');

// Base site URL, used to build links in outbound emails (no trailing slash)
define('SITE_URL', 'https://drprasannakumark.com');

// Verification code settings
define('OTP_EXPIRY_SECONDS', 300);   // 5 minutes
define('OTP_MAX_ATTEMPTS',   3);     // max wrong tries before lockout
define('OTP_RESEND_COOLDOWN', 60);   // seconds before resend is allowed
