-- Dr. Prasanna Kumar K portal — MySQL schema
-- Mirrors the field names used by the JSON-file storage it replaces (db.php),
-- so no query/data-shape changes are needed anywhere outside db.php itself.

CREATE TABLE IF NOT EXISTS patients (
  id            VARCHAR(32)  NOT NULL,
  created_at    DATETIME     NOT NULL,
  salutation    VARCHAR(10)  NULL,
  first_name    VARCHAR(100) NOT NULL,
  last_name     VARCHAR(100) NULL,
  gender        VARCHAR(20)  NULL,
  dob           VARCHAR(10)  NULL,
  mobile        VARCHAR(20)  NOT NULL,
  email         VARCHAR(150) NULL,
  PRIMARY KEY (id),
  INDEX idx_patients_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS appointments (
  ref_number          VARCHAR(20)  NOT NULL,
  patient_id          VARCHAR(32)  NOT NULL,
  doctor              VARCHAR(50)  NOT NULL,
  appointment_date    VARCHAR(10)  NOT NULL,  -- kept as DD-MM-YYYY string; every caller already parses/compares this format
  time_slot           VARCHAR(30)  NOT NULL,
  status              VARCHAR(20)  NOT NULL DEFAULT 'confirmed',
  created_at          DATETIME     NOT NULL,
  cancel_reason       TEXT         NULL,
  cancelled_at        DATETIME     NULL,
  close_reason        TEXT         NULL,
  closed_at           DATETIME     NULL,
  reschedule_history  JSON         NULL,
  PRIMARY KEY (ref_number),
  INDEX idx_appts_patient (patient_id),
  INDEX idx_appts_date (appointment_date),
  CONSTRAINT fk_appts_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS records (
  id                VARCHAR(32) NOT NULL,
  appointment_ref   VARCHAR(20) NOT NULL,
  patient_id        VARCHAR(32) NOT NULL,
  created_at        DATETIME    NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX idx_records_ref (appointment_ref),
  INDEX idx_records_patient (patient_id),
  CONSTRAINT fk_records_appt FOREIGN KEY (appointment_ref) REFERENCES appointments(ref_number),
  CONSTRAINT fk_records_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS record_entries (
  id              VARCHAR(40)  NOT NULL,
  record_id       VARCHAR(32)  NOT NULL,
  by_role         VARCHAR(20)  NOT NULL,  -- 'doctor' | 'patient' — `by` is a reserved-ish word, avoided as a column name
  description     TEXT         NULL,
  filename        VARCHAR(255) NULL,
  original_name   VARCHAR(255) NULL,
  created_at      DATETIME     NOT NULL,
  PRIMARY KEY (id),
  INDEX idx_entries_record (record_id),
  CONSTRAINT fk_entries_record FOREIGN KEY (record_id) REFERENCES records(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS doctor_auth (
  username        VARCHAR(50)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  created_at      DATETIME     NOT NULL,
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mail_log (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sent_at     DATETIME     NOT NULL,
  status      VARCHAR(10)  NOT NULL,  -- 'SENT' | 'FAILED'
  to_email    VARCHAR(150) NOT NULL,
  subject     VARCHAR(255) NOT NULL,
  error       TEXT         NULL,
  PRIMARY KEY (id),
  INDEX idx_mail_log_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id                  VARCHAR(40)  NOT NULL,
  appointment_ref     VARCHAR(20)  NOT NULL,
  patient_id          VARCHAR(32)  NOT NULL,
  amount_paise        INT UNSIGNED NOT NULL,
  currency            VARCHAR(3)   NOT NULL DEFAULT 'INR',
  status              VARCHAR(20)  NOT NULL DEFAULT 'created', -- 'created' | 'paid' | 'failed'
  razorpay_order_id   VARCHAR(64)  NULL,
  razorpay_payment_id VARCHAR(64)  NULL,
  razorpay_signature  VARCHAR(128) NULL,
  created_at          DATETIME     NOT NULL,
  paid_at             DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX idx_payments_ref (appointment_ref),
  INDEX idx_payments_patient (patient_id),
  CONSTRAINT fk_payments_appt FOREIGN KEY (appointment_ref) REFERENCES appointments(ref_number),
  CONSTRAINT fk_payments_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS doctor_availability (
  day_of_week  TINYINT UNSIGNED NOT NULL, -- 0=Sunday .. 6=Saturday
  enabled      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS doctor_availability_slots (
  day_of_week  TINYINT UNSIGNED NOT NULL, -- 0=Sunday .. 6=Saturday
  slot_start   TIME         NOT NULL,     -- start of a 15-minute slot, e.g. 08:30:00
  PRIMARY KEY (day_of_week, slot_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Mobile app (api/) token auth ──
-- Standalone OTP flow for the app's stateless HTTP calls — deliberately
-- separate from the website's PHP-session-based OTP (send_otp.php /
-- verify_otp.php), so the website is unaffected by anything here.
CREATE TABLE IF NOT EXISTS api_otp_requests (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  mobile                 VARCHAR(20)  NOT NULL,
  code_hash              CHAR(64)     NOT NULL, -- sha256(code)
  attempts               TINYINT UNSIGNED NOT NULL DEFAULT 0,
  verify_token           CHAR(64)     NULL,      -- issued once the code is verified
  verify_token_expires   DATETIME     NULL,
  created_at             DATETIME     NOT NULL,
  expires_at             DATETIME     NOT NULL,
  PRIMARY KEY (id),
  INDEX idx_api_otp_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS api_tokens (
  id              VARCHAR(40)  NOT NULL,
  patient_id      VARCHAR(32)  NOT NULL,
  token_hash      CHAR(64)     NOT NULL, -- sha256(token); the raw token is only ever shown to the client once
  created_at      DATETIME     NOT NULL,
  expires_at      DATETIME     NOT NULL,
  last_used_at    DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX idx_api_tokens_hash (token_hash),
  INDEX idx_api_tokens_patient (patient_id),
  CONSTRAINT fk_api_tokens_patient FOREIGN KEY (patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
