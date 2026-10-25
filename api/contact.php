<?php
/** Same inquiry-mail logic as index.php's contact form POST block. */
require_once __DIR__ . '/../lib/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Invalid request.', 405);

$fname   = trim($_POST['fname'] ?? '');
$lname   = trim($_POST['lname'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($fname) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($phone)) {
    apiError('Please fill in all required fields with valid details.');
}

$to      = 'docprasanna@yahoo.com';
$subject = 'New Inquiry from ' . $fname . ' ' . $lname;
$body    = "Name: $fname $lname\nEmail: $email\nPhone: $phone\nService: $service\n\nMessage:\n$message";
$headers = "From: noreply@drprasannakumar.com\r\nReply-To: $email\r\n";
@mail($to, $subject, $body, $headers);

apiJson(['success' => true]);
