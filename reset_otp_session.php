<?php
session_start();
header('Content-Type: application/json');
unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['otp_attempts'], $_SESSION['otp_sent_at'], $_SESSION['otp_mobile']);
echo json_encode(['success' => true]);
