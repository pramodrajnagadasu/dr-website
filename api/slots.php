<?php
/** Same logic as get_slots.php, exposed under api/ for the app. No auth — public availability info. */
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/api_auth.php';

$doctor = trim($_GET['doctor'] ?? '');
$date   = trim($_GET['date']   ?? '');

if (!$doctor || !$date) {
    apiJson(['slots' => [], 'booked' => [], 'enabled' => false]);
}

$parts = explode('-', $date);
if (count($parts) !== 3) {
    apiJson(['slots' => [], 'booked' => [], 'enabled' => false]);
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

apiJson(['slots' => $slots, 'booked' => $booked, 'enabled' => (bool) ($dayRow['enabled'] ?? false)]);
