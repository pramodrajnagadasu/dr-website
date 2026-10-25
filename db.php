<?php
/**
 * MySQL-backed storage (via PDO). Public function signatures are unchanged
 * from the previous JSON-file implementation, so no caller needs to change.
 */

require_once __DIR__ . '/config-db.local.php';

function dbConnect(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/* ── patients ── */
function findPatientsByMobile(string $mobile): array {
    $stmt = dbConnect()->prepare('SELECT * FROM patients WHERE mobile = ?');
    $stmt->execute([$mobile]);
    return $stmt->fetchAll();
}

function findPatientById(string $id): ?array {
    $stmt = dbConnect()->prepare('SELECT * FROM patients WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function savePatient(array $data): array {
    $pdo = dbConnect();

    // Prevent exact duplicate (same mobile + same first_name)
    $stmt = $pdo->prepare('SELECT * FROM patients WHERE mobile = ? AND LOWER(first_name) = LOWER(?)');
    $stmt->execute([$data['mobile'], $data['first_name']]);
    $existing = $stmt->fetch();
    if ($existing) return $existing;

    $patient = array_merge([
        'id'         => uniqid('P'),
        'created_at' => date('Y-m-d H:i:s'),
    ], $data);

    $stmt = $pdo->prepare(
        'INSERT INTO patients (id, created_at, salutation, first_name, last_name, gender, dob, mobile, email)
         VALUES (:id, :created_at, :salutation, :first_name, :last_name, :gender, :dob, :mobile, :email)'
    );
    $stmt->execute([
        'id'         => $patient['id'],
        'created_at' => $patient['created_at'],
        'salutation' => $patient['salutation'] ?? null,
        'first_name' => $patient['first_name'],
        'last_name'  => $patient['last_name'] ?? null,
        'gender'     => $patient['gender'] ?? null,
        'dob'        => $patient['dob'] ?? null,
        'mobile'     => $patient['mobile'],
        'email'      => $patient['email'] ?? null,
    ]);
    return $patient;
}

/* ── appointment lookups ── */
function findAppointmentByRef(string $ref): ?array {
    $stmt = dbConnect()->prepare('SELECT * FROM appointments WHERE ref_number = ?');
    $stmt->execute([$ref]);
    $row = $stmt->fetch();
    return $row ? decodeAppointment($row) : null;
}

function findAppointmentsByPatient(string $patientId): array {
    $stmt = dbConnect()->prepare('SELECT * FROM appointments WHERE patient_id = ? ORDER BY created_at DESC');
    $stmt->execute([$patientId]);
    return array_map('decodeAppointment', $stmt->fetchAll());
}

function getAllAppointments(): array {
    $stmt = dbConnect()->query('SELECT * FROM appointments');
    return array_map('decodeAppointment', $stmt->fetchAll());
}

function getAllPatients(): array {
    return dbConnect()->query('SELECT * FROM patients')->fetchAll();
}

function decodeAppointment(array $row): array {
    $row['reschedule_history'] = $row['reschedule_history'] !== null
        ? json_decode($row['reschedule_history'], true)
        : null;
    // Drop null optional fields to match the old JSON shape (keys only present when set)
    foreach (['cancel_reason', 'cancelled_at', 'close_reason', 'closed_at', 'reschedule_history'] as $k) {
        if ($row[$k] === null) unset($row[$k]);
    }
    return $row;
}

/* ── records ── */
function findRecordByRef(string $ref): ?array {
    $pdo  = dbConnect();
    $stmt = $pdo->prepare('SELECT * FROM records WHERE appointment_ref = ?');
    $stmt->execute([$ref]);
    $record = $stmt->fetch();
    if (!$record) return null;
    $record['entries'] = fetchRecordEntries($record['id']);
    return $record;
}

function findRecordsByPatient(string $patientId): array {
    $pdo  = dbConnect();
    $stmt = $pdo->prepare('SELECT * FROM records WHERE patient_id = ?');
    $stmt->execute([$patientId]);
    $records = $stmt->fetchAll();
    foreach ($records as &$r) {
        $r['entries'] = fetchRecordEntries($r['id']);
    }
    unset($r);
    return $records;
}

function fetchRecordEntries(string $recordId): array {
    $stmt = dbConnect()->prepare('SELECT * FROM record_entries WHERE record_id = ? ORDER BY created_at ASC');
    $stmt->execute([$recordId]);
    return array_map(function ($e) {
        return [
            'id'            => $e['id'],
            'by'            => $e['by_role'],
            'description'   => $e['description'],
            'filename'      => $e['filename'],
            'original_name' => $e['original_name'],
            'created_at'    => $e['created_at'],
        ];
    }, $stmt->fetchAll());
}

function ensureRecord(string $ref, string $patientId): void {
    $pdo  = dbConnect();
    $stmt = $pdo->prepare('SELECT id FROM records WHERE appointment_ref = ?');
    $stmt->execute([$ref]);
    if ($stmt->fetch()) return;

    $stmt = $pdo->prepare(
        'INSERT INTO records (id, appointment_ref, patient_id, created_at) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute(['REC-' . strtoupper(substr(md5(uniqid()), 0, 8)), $ref, $patientId, date('Y-m-d H:i:s')]);
}

function addRecordEntry(string $ref, array $entry): bool {
    $pdo  = dbConnect();
    $stmt = $pdo->prepare('SELECT id FROM records WHERE appointment_ref = ?');
    $stmt->execute([$ref]);
    $record = $stmt->fetch();
    if (!$record) return false;

    $stmt = $pdo->prepare(
        'INSERT INTO record_entries (id, record_id, by_role, description, filename, original_name, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    return $stmt->execute([
        'E-' . uniqid(),
        $record['id'],
        $entry['by'] ?? 'patient',
        $entry['description'] ?? null,
        $entry['filename'] ?? null,
        $entry['original_name'] ?? null,
        date('Y-m-d H:i:s'),
    ]);
}

/* ── appointments ── */
function saveAppointment(array $data): string {
    $pdo = dbConnect();
    $ref = 'TG-' . strtoupper(substr(md5(uniqid()), 0, 6));

    $stmt = $pdo->prepare(
        'INSERT INTO appointments (ref_number, patient_id, doctor, appointment_date, time_slot, status, created_at)
         VALUES (:ref_number, :patient_id, :doctor, :appointment_date, :time_slot, :status, :created_at)'
    );
    $stmt->execute([
        'ref_number'        => $ref,
        'patient_id'        => $data['patient_id'],
        'doctor'            => $data['doctor'],
        'appointment_date'  => $data['appointment_date'],
        'time_slot'         => $data['time_slot'],
        'status'            => $data['status'] ?? 'confirmed',
        'created_at'        => date('Y-m-d H:i:s'),
    ]);
    return $ref;
}

function updateAppointmentByRef(string $ref, array $changes): ?array {
    $pdo = dbConnect();
    $existing = findAppointmentByRef($ref);
    if (!$existing) return null;

    $columns = ['patient_id', 'doctor', 'appointment_date', 'time_slot', 'status',
                'cancel_reason', 'cancelled_at', 'close_reason', 'closed_at', 'reschedule_history'];
    $sets   = [];
    $params = ['ref_number' => $ref];
    foreach ($changes as $key => $value) {
        if (!in_array($key, $columns, true)) continue;
        $sets[]        = "$key = :$key";
        $params[$key]  = $key === 'reschedule_history' ? json_encode($value) : $value;
    }
    if (empty($sets)) return $existing;

    $stmt = $pdo->prepare('UPDATE appointments SET ' . implode(', ', $sets) . ' WHERE ref_number = :ref_number');
    $stmt->execute($params);

    return findAppointmentByRef($ref);
}

/* ── doctor auth ── */
function getDoctorAuth(): array {
    $stmt = dbConnect()->query('SELECT * FROM doctor_auth LIMIT 1');
    $row  = $stmt->fetch();
    return $row ?: [];
}

function saveDoctorAuth(string $username, string $passwordHash): void {
    $pdo = dbConnect();
    $pdo->exec('DELETE FROM doctor_auth'); // single-admin app — one row only
    $stmt = $pdo->prepare('INSERT INTO doctor_auth (username, password_hash, created_at) VALUES (?, ?, ?)');
    $stmt->execute([$username, $passwordHash, date('Y-m-d H:i:s')]);
}

/* ── payments ── */
function createPayment(string $ref, string $patientId, int $amountPaise): array {
    $pdo     = dbConnect();
    $payment = [
        'id'              => 'PAY-' . strtoupper(substr(md5(uniqid()), 0, 10)),
        'appointment_ref' => $ref,
        'patient_id'      => $patientId,
        'amount_paise'    => $amountPaise,
        'currency'        => 'INR',
        'status'          => 'created',
        'created_at'      => date('Y-m-d H:i:s'),
    ];
    $stmt = $pdo->prepare(
        'INSERT INTO payments (id, appointment_ref, patient_id, amount_paise, currency, status, created_at)
         VALUES (:id, :appointment_ref, :patient_id, :amount_paise, :currency, :status, :created_at)'
    );
    $stmt->execute($payment);
    return $payment;
}

function findPaymentByRef(string $ref): ?array {
    $stmt = dbConnect()->prepare('SELECT * FROM payments WHERE appointment_ref = ?');
    $stmt->execute([$ref]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function findPaymentByOrderId(string $orderId): ?array {
    $stmt = dbConnect()->prepare('SELECT * FROM payments WHERE razorpay_order_id = ?');
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function updatePaymentOrder(string $ref, string $orderId): void {
    $stmt = dbConnect()->prepare('UPDATE payments SET razorpay_order_id = ? WHERE appointment_ref = ?');
    $stmt->execute([$orderId, $ref]);
}

function markPaymentPaidByOrderId(string $orderId, string $paymentId, ?string $signature): ?array {
    $pdo  = dbConnect();
    $stmt = $pdo->prepare(
        "UPDATE payments SET status = 'paid', razorpay_payment_id = ?, razorpay_signature = ?, paid_at = ?
         WHERE razorpay_order_id = ? AND status != 'paid'"
    );
    $stmt->execute([$paymentId, $signature, date('Y-m-d H:i:s'), $orderId]);

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE razorpay_order_id = ?');
    $stmt->execute([$orderId]);
    return $stmt->fetch() ?: null;
}

/* ── doctor availability ── */

/** Every selectable 15-minute slot start time, 06:00 through 21:45. */
function allPossibleSlotStarts(): array {
    $starts = [];
    for ($mins = 6 * 60; $mins < 22 * 60; $mins += 15) {
        $starts[] = sprintf('%02d:%02d:00', intdiv($mins, 60), $mins % 60);
    }
    return $starts;
}

function seedDoctorAvailability(): void {
    $pdo = dbConnect();
    $dayStmt  = $pdo->prepare('INSERT IGNORE INTO doctor_availability (day_of_week, enabled) VALUES (?, 1)');
    $slotStmt = $pdo->prepare('INSERT IGNORE INTO doctor_availability_slots (day_of_week, slot_start) VALUES (?, ?)');
    // Default: mirrors the original hardcoded hours (8:30-11:00 AM, 4:30-9:00 PM), every day.
    $defaults = array_filter(allPossibleSlotStarts(), function ($t) {
        return ($t >= '08:30:00' && $t < '11:00:00') || ($t >= '16:30:00' && $t < '21:00:00');
    });
    for ($d = 0; $d <= 6; $d++) {
        $dayStmt->execute([$d]);
        foreach ($defaults as $t) $slotStmt->execute([$d, $t]);
    }
}

function getDoctorAvailability(): array {
    $pdo  = dbConnect();
    $rows = $pdo->query('SELECT * FROM doctor_availability ORDER BY day_of_week')->fetchAll();
    if (count($rows) < 7) {
        seedDoctorAvailability();
        $rows = $pdo->query('SELECT * FROM doctor_availability ORDER BY day_of_week')->fetchAll();
    }
    return $rows;
}

function getAvailabilityForDay(int $dayOfWeek): ?array {
    $rows = getDoctorAvailability();
    foreach ($rows as $r) {
        if ((int) $r['day_of_week'] === $dayOfWeek) return $r;
    }
    return null;
}

/** Selected slot_start times (e.g. "08:30:00") for one day, sorted. */
function getAvailabilitySlots(int $dayOfWeek): array {
    $stmt = dbConnect()->prepare('SELECT slot_start FROM doctor_availability_slots WHERE day_of_week = ? ORDER BY slot_start');
    $stmt->execute([$dayOfWeek]);
    return array_column($stmt->fetchAll(), 'slot_start');
}

/** Replaces a day's enabled flag and full slot set in one go. $slotStarts: e.g. ["08:30:00", "08:45:00"]. */
function setDayAvailability(int $dayOfWeek, bool $enabled, array $slotStarts): void {
    $pdo = dbConnect();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE doctor_availability SET enabled = ? WHERE day_of_week = ?');
        $stmt->execute([$enabled ? 1 : 0, $dayOfWeek]);

        $stmt = $pdo->prepare('DELETE FROM doctor_availability_slots WHERE day_of_week = ?');
        $stmt->execute([$dayOfWeek]);

        if (!empty($slotStarts)) {
            $stmt = $pdo->prepare('INSERT INTO doctor_availability_slots (day_of_week, slot_start) VALUES (?, ?)');
            foreach (array_unique($slotStarts) as $t) $stmt->execute([$dayOfWeek, $t]);
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** 15-minute slot labels (e.g. "08:30 AM - 08:45 AM") for one day, from its selected slot_starts. */
function generateSlotsForDay(?array $dayRow, array $slotStarts = []): array {
    if (!$dayRow || !$dayRow['enabled']) return [];
    $slots = [];
    foreach ($slotStarts as $start) {
        $cur  = strtotime($start);
        $next = $cur + 15 * 60;
        $slots[] = date('h:i A', $cur) . ' - ' . date('h:i A', $next);
    }
    return $slots;
}

/* ── mobile app (api/) OTP + token auth ── */

/** Creates a new OTP request row for a mobile number, storing only the code's hash. */
function createOtpRequest(string $mobile, string $code, int $expirySeconds): void {
    $pdo = dbConnect();
    $stmt = $pdo->prepare(
        'INSERT INTO api_otp_requests (mobile, code_hash, attempts, created_at, expires_at)
         VALUES (?, ?, 0, ?, ?)'
    );
    $stmt->execute([
        $mobile,
        hash('sha256', $code),
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s', time() + $expirySeconds),
    ]);
}

/** Most recent still-pending OTP request for a mobile number (already verified rows are ignored). */
function findLatestOtpRequest(string $mobile): ?array {
    $stmt = dbConnect()->prepare(
        'SELECT * FROM api_otp_requests WHERE mobile = ? AND verify_token IS NULL ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$mobile]);
    return $stmt->fetch() ?: null;
}

function incrementOtpAttempts(int $id): void {
    $stmt = dbConnect()->prepare('UPDATE api_otp_requests SET attempts = attempts + 1 WHERE id = ?');
    $stmt->execute([$id]);
}

/** Marks an OTP request verified and returns a short-lived verify_token proving it. */
function markOtpVerified(int $id, int $ticketExpirySeconds): string {
    $token = bin2hex(random_bytes(32));
    $stmt  = dbConnect()->prepare(
        'UPDATE api_otp_requests SET verify_token = ?, verify_token_expires = ? WHERE id = ?'
    );
    $stmt->execute([$token, date('Y-m-d H:i:s', time() + $ticketExpirySeconds), $id]);
    return $token;
}

/** Looks up a still-valid (unexpired) verify_token for a mobile number. */
function findValidOtpTicket(string $mobile, string $verifyToken): ?array {
    $stmt = dbConnect()->prepare(
        'SELECT * FROM api_otp_requests WHERE mobile = ? AND verify_token = ? AND verify_token_expires >= ?
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$mobile, $verifyToken, date('Y-m-d H:i:s')]);
    return $stmt->fetch() ?: null;
}

/** Issues a new bearer token for a patient. Returns the raw token (only ever shown once). */
function createApiToken(string $patientId, int $expirySeconds): string {
    $token = bin2hex(random_bytes(32));
    $pdo   = dbConnect();
    $stmt  = $pdo->prepare(
        'INSERT INTO api_tokens (id, patient_id, token_hash, created_at, expires_at)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        'TOK-' . strtoupper(substr(md5(uniqid()), 0, 16)),
        $patientId,
        hash('sha256', $token),
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s', time() + $expirySeconds),
    ]);
    return $token;
}

/** Resolves a bearer token to its patient row, or null if missing/expired. Bumps last_used_at. */
function findPatientByApiToken(string $token): ?array {
    $pdo  = dbConnect();
    $stmt = $pdo->prepare(
        'SELECT * FROM api_tokens WHERE token_hash = ? AND expires_at >= ?'
    );
    $stmt->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
    $row = $stmt->fetch();
    if (!$row) return null;

    $upd = $pdo->prepare('UPDATE api_tokens SET last_used_at = ? WHERE id = ?');
    $upd->execute([date('Y-m-d H:i:s'), $row['id']]);

    return findPatientById($row['patient_id']);
}

function deleteApiToken(string $token): void {
    $stmt = dbConnect()->prepare('DELETE FROM api_tokens WHERE token_hash = ?');
    $stmt->execute([hash('sha256', $token)]);
}

/* ── mail log ── */
function logMailAttempt(bool $ok, string $to, string $subject, string $detail = ''): void {
    $stmt = dbConnect()->prepare(
        'INSERT INTO mail_log (sent_at, status, to_email, subject, error) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([date('Y-m-d H:i:s'), $ok ? 'SENT' : 'FAILED', $to, $subject, $detail !== '' ? $detail : null]);
}
