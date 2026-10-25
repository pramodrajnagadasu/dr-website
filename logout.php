<?php
session_start();
$role = $_GET['role'] ?? 'patient';
session_destroy();
if ($role === 'doctor') {
    header('Location: admin.php?logged_out=1');
} else {
    header('Location: patient-portal.php?logged_out=1');
}
exit;
