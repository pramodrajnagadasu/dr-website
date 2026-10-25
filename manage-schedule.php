<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

if (!isDoctorLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized.']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$days = $body['days'] ?? [];

if (!is_array($days) || count($days) !== 7) {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule data.']); exit;
}

$validSlots = array_flip(allPossibleSlotStarts());

foreach ($days as $d) {
    $dow = (int) ($d['day_of_week'] ?? -1);
    if ($dow < 0 || $dow > 6) {
        echo json_encode(['success' => false, 'message' => 'Invalid day.']); exit;
    }

    $enabled = !empty($d['enabled']) && $d['enabled'] !== '0';
    $slots   = is_array($d['slots'] ?? null) ? $d['slots'] : [];

    $clean = [];
    foreach ($slots as $t) {
        $t = trim((string) $t);
        if (!isset($validSlots[$t])) {
            echo json_encode(['success' => false, 'message' => 'Invalid time slot submitted.']); exit;
        }
        $clean[] = $t;
    }

    setDayAvailability($dow, $enabled, $clean);
}

echo json_encode(['success' => true]);
