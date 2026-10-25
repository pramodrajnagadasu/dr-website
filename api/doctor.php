<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

$doctors = [
    'prasanna' => [
        'key'           => 'prasanna',
        'name'          => 'Dr. Prasanna Kumar K',
        'qualification' => 'MBBS, AFIH',
        'specialist'    => 'Occupational Health & Primary Care',
    ],
];

$enabledDays = [];
foreach (getDoctorAvailability() as $row) {
    if ($row['enabled']) $enabledDays[] = (int) $row['day_of_week'];
}

apiJson([
    'doctors'      => array_values($doctors),
    'enabled_days' => $enabledDays,
]);
