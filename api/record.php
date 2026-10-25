<?php
/** JSON equivalent of record.php's data (patient's own records only — no doctor view in the app). */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

$patient = apiRequirePatient();

$ref = trim($_GET['ref'] ?? '');
if (!$ref) apiError('Missing appointment reference.');

$appt = findAppointmentByRef($ref);
if (!$appt) apiError('Appointment not found.', 404);
if ($appt['patient_id'] !== $patient['id']) apiError('Access denied.', 403);

ensureRecord($ref, $appt['patient_id']);
$record  = findRecordByRef($ref);
$entries = $record['entries'] ?? [];
usort($entries, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));

apiJson([
    'ref_number'       => $ref,
    'appointment_date' => $appt['appointment_date'],
    'time_slot'        => $appt['time_slot'],
    'entries'          => $entries,
]);
