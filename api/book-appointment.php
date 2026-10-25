<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../mailer.php';
require_once __DIR__ . '/../lib/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Invalid request.', 405);

$patient = apiRequirePatient();

$doctor   = trim($_POST['doctor']   ?? '');
$date     = trim($_POST['date']     ?? '');
$timeslot = trim($_POST['timeslot'] ?? '');

if (!$doctor || !$date || !$timeslot) {
    apiError('Missing booking details.');
}

$ref = saveAppointment([
    'patient_id'       => $patient['id'],
    'doctor'           => $doctor,
    'appointment_date' => $date,
    'time_slot'        => $timeslot,
]);

$patientName = trim(
    ($patient['salutation'] ? $patient['salutation'] . '. ' : '') .
    $patient['first_name'] . ' ' . ($patient['last_name'] ?? '')
);
sendDoctorBookingAlert(
    $ref,
    $patientName,
    $patient['mobile'] ?? '',
    $patient['gender'] ?? '',
    $patient['dob']    ?? '',
    $date,
    $timeslot
);
if (!empty($patient['email'])) {
    sendAppointmentConfirmedEmail($patient['email'], $ref, $patientName, $date, $timeslot);
}

apiJson(['success' => true, 'ref' => $ref]);
