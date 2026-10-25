<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';
require_once 'config.php';
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]); exit;
}

$patient_id = $_SESSION['patient_id'] ?? null;
if (!$patient_id) {
    echo json_encode(['success' => false, 'message' => 'Patient session lost. Please refresh and try again.']); exit;
}

$doctor   = trim($_POST['doctor']   ?? '');
$date     = trim($_POST['date']     ?? '');
$timeslot = trim($_POST['timeslot'] ?? '');

if (!$doctor || !$date || !$timeslot) {
    echo json_encode(['success' => false, 'message' => 'Missing booking details.']); exit;
}

// Save appointment
$ref = saveAppointment([
    'patient_id'       => $patient_id,
    'doctor'           => $doctor,
    'appointment_date' => $date,
    'time_slot'        => $timeslot,
]);

// Email the doctor
$patient = findPatientById($patient_id);
if ($patient) {
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
}

echo json_encode(['success' => true, 'ref' => $ref]);
