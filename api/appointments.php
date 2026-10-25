<?php
/** JSON equivalent of patient-history.php's data. */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

$patient = apiRequirePatient();

$appointments = findAppointmentsByPatient($patient['id']);
$out = [];
foreach ($appointments as $a) {
    $payment = findPaymentByRef($a['ref_number']);
    $out[] = [
        'ref_number'         => $a['ref_number'],
        'doctor'              => $a['doctor'],
        'appointment_date'    => $a['appointment_date'],
        'time_slot'           => $a['time_slot'],
        'status'              => $a['status'] ?? 'confirmed',
        'cancel_reason'       => $a['cancel_reason'] ?? null,
        'reschedule_history'  => $a['reschedule_history'] ?? null,
        'payment'             => $payment ? [
            'status'       => $payment['status'],
            'amount_paise' => (int) $payment['amount_paise'],
        ] : null,
    ];
}

apiJson([
    'patient' => [
        'id'         => $patient['id'],
        'salutation' => $patient['salutation'] ?? null,
        'first_name' => $patient['first_name'],
        'last_name'  => $patient['last_name'] ?? null,
        'gender'     => $patient['gender'] ?? null,
        'dob'        => $patient['dob'] ?? null,
        'mobile'     => $patient['mobile'] ?? null,
        'email'      => $patient['email'] ?? null,
    ],
    'appointments' => $out,
]);
