<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';
require_once 'config.php';
require_once 'mailer.php';
require_once 'lib/razorpay.php';

if (!isDoctorLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized.']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]); exit;
}

$action = trim($_POST['action'] ?? '');
$ref    = trim($_POST['ref'] ?? '');
$appt   = $ref ? findAppointmentByRef($ref) : null;

if (!$appt) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found.']); exit;
}
if (($appt['status'] ?? 'confirmed') === 'cancelled') {
    echo json_encode(['success' => false, 'message' => 'This appointment is already cancelled.']); exit;
}
if (($appt['status'] ?? 'confirmed') === 'closed') {
    echo json_encode(['success' => false, 'message' => 'This case is already closed.']); exit;
}

$patient     = findPatientById($appt['patient_id']);
$patientName = $patient
    ? trim(($patient['salutation'] ? $patient['salutation'] . '. ' : '') . $patient['first_name'] . ' ' . ($patient['last_name'] ?? ''))
    : 'Patient';

if ($action === 'cancel') {
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
        echo json_encode(['success' => false, 'message' => 'Please provide a reason for cancellation.']); exit;
    }

    $updated = updateAppointmentByRef($ref, [
        'status'        => 'cancelled',
        'cancel_reason' => $reason,
        'cancelled_at'  => date('Y-m-d H:i:s'),
    ]);

    if ($patient && !empty($patient['email'])) {
        sendAppointmentCancelledEmail($patient['email'], $ref, $patientName, $appt['appointment_date'], $appt['time_slot'], $reason);
    }
    sendAppointmentCancelledEmail(DOCTOR_NOTIFY_EMAIL, $ref, $patientName, $appt['appointment_date'], $appt['time_slot'], $reason);

    echo json_encode(['success' => true, 'appointment' => $updated]); exit;
}

if ($action === 'postpone') {
    $newDate = trim($_POST['new_date'] ?? '');
    $newSlot = trim($_POST['new_slot'] ?? '');
    $reason  = trim($_POST['reason'] ?? '');

    if ($newDate === '' || $newSlot === '') {
        echo json_encode(['success' => false, 'message' => 'Please provide the new date and time slot.']); exit;
    }

    foreach (getAllAppointments() as $a) {
        if ($a['ref_number'] !== $ref
            && ($a['status'] ?? 'confirmed') !== 'cancelled'
            && $a['doctor'] === $appt['doctor']
            && $a['appointment_date'] === $newDate
            && $a['time_slot'] === $newSlot) {
            echo json_encode(['success' => false, 'message' => 'That slot is already booked.']); exit;
        }
    }

    $oldDate = $appt['appointment_date'];
    $oldSlot = $appt['time_slot'];

    $history   = $appt['reschedule_history'] ?? [];
    $history[] = ['from_date' => $oldDate, 'from_slot' => $oldSlot, 'reason' => $reason, 'at' => date('Y-m-d H:i:s')];

    $updated = updateAppointmentByRef($ref, [
        'appointment_date'   => $newDate,
        'time_slot'          => $newSlot,
        'status'             => 'postponed',
        'reschedule_history' => $history,
    ]);

    if ($patient && !empty($patient['email'])) {
        sendAppointmentPostponedEmail($patient['email'], $ref, $patientName, $oldDate, $oldSlot, $newDate, $newSlot, $reason);
    }
    sendAppointmentPostponedEmail(DOCTOR_NOTIFY_EMAIL, $ref, $patientName, $oldDate, $oldSlot, $newDate, $newSlot, $reason);

    echo json_encode(['success' => true, 'appointment' => $updated]); exit;
}

if ($action === 'close') {
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
        echo json_encode(['success' => false, 'message' => 'Please select a reason.']); exit;
    }

    $amountRupees = trim($_POST['amount'] ?? '');
    if ($amountRupees !== '' && (!is_numeric($amountRupees) || (float)$amountRupees < 0)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid fee amount.']); exit;
    }

    $updated = updateAppointmentByRef($ref, [
        'status'       => 'closed',
        'close_reason' => $reason,
        'closed_at'    => date('Y-m-d H:i:s'),
    ]);

    if ($amountRupees !== '' && (float)$amountRupees > 0 && RAZORPAY_ENABLED) {
        $amountPaise = (int) round((float)$amountRupees * 100);
        createPayment($ref, $appt['patient_id'], $amountPaise);
        if ($patient && !empty($patient['email'])) {
            sendPaymentRequestEmail($patient['email'], $ref, $patientName, number_format((float)$amountRupees, 2));
        }
    }

    echo json_encode(['success' => true, 'appointment' => $updated]); exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
