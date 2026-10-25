<?php
/**
 * Authentication helpers.
 * Always include AFTER session_start().
 */

function isDoctorLoggedIn(): bool {
    return !empty($_SESSION['doctor_logged_in']);
}

function isPatientLoggedIn(): bool {
    return !empty($_SESSION['portal_patient_id']);
}

function requireDoctor(): void {
    if (!isDoctorLoggedIn()) { header('Location: admin.php'); exit; }
}

function requirePatient(): void {
    if (!isPatientLoggedIn()) { header('Location: patient-portal.php'); exit; }
}
