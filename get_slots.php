<?php
header('Content-Type: application/json');
require_once 'db.php';

$doctor = trim($_GET['doctor'] ?? '');
$date   = trim($_GET['date']   ?? '');

if (!$doctor || !$date) {
    echo json_encode(['slots' => [], 'booked' => [], 'enabled' => false]); exit;
}

$parts = explode('-', $date);
if (count($parts) !== 3) {
    echo json_encode(['slots' => [], 'booked' => [], 'enabled' => false]); exit;
}
[$d, $m, $y] = $parts;
$dayOfWeek  = (int) date('w', mktime(0, 0, 0, (int) $m, (int) $d, (int) $y));
$dayRow     = getAvailabilityForDay($dayOfWeek);
$slotStarts = getAvailabilitySlots($dayOfWeek);
$slots      = generateSlotsForDay($dayRow, $slotStarts);

$appts  = getAllAppointments();
$booked = [];
foreach ($appts as $a) {
    if (($a['status'] ?? 'confirmed') === 'cancelled') continue;
    if ($a['doctor'] === $doctor && $a['appointment_date'] === $date) {
        $booked[] = $a['time_slot'];
    }
}

echo json_encode(['slots' => $slots, 'booked' => $booked, 'enabled' => (bool) ($dayRow['enabled'] ?? false)]);
