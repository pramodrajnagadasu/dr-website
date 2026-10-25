# Deploying to GoDaddy cPanel (fresh MySQL start)

This switches the live site from JSON-file storage to MySQL, starting
with **empty tables** — no data is carried over from the old
`data/*.json` files. Patients and appointments will build up fresh from
this point on, and the doctor account will need to be created again via
first-time admin setup.

**Database name: `doctor_prasanna_application`** · **DB username: `admin`**
(cPanel will prefix both, e.g. `youracct_doctor_prasanna_application` /
`youracct_admin` — use whatever the full prefixed values turn out to be
everywhere below)

## 1. Back up the live JSON data first (archival only)

Log into cPanel → **File Manager** → navigate to the site's folder
(usually `public_html`, or `public_html/<subdomain>` if it's an addon
domain) → open `data/`. Select `patients.json`, `appointments.json`,
`records.json`, `doctor_auth.json` → **Compress** (or download them
individually) and save the copy to your own computer.

This isn't used by anything below — it's just so the old bookings aren't
gone forever in case you ever need to reference them.

## 2. Upload the updated code

Two ways to get the new files onto the server:

**Option A — File Manager (no extra tools needed):**
1. On your computer, zip the project folder — **exclude** `.git/`,
   `data/`, `sessions/`, `uploads/`, `.claude/`, `.idea/`, `mobile/`, and any
   `config-*.local.php` files (only the `.example` versions should go up).
   `mobile/` is the Flutter app source — it's a separate build artifact
   (App Store/Play Store), never uploaded to the PHP host. The `api/`
   directory (its backend) does need to go up like any other PHP file.
2. cPanel → File Manager → navigate to the site folder → **Upload** →
   select the zip
3. Right-click the uploaded zip → **Extract** — when prompted about
   overwriting existing files, confirm (this replaces the PHP/CSS/JS
   files with the new versions)
4. Delete the uploaded zip afterward to keep things tidy

**Option B — FTP/SFTP client** (e.g. FileZilla): get credentials from
cPanel → **FTP Accounts** (or use the main account login). Connect and
sync the project folder over.

Either way, leave `data/`, `sessions/`, and `uploads/` alone — if your
FTP client has a "mirror" or "delete files that don't exist locally"
option, turn it off or exclude those folders.

## 3. Enable the MySQL extension

cPanel → **Select PHP Version** → **Extensions** → enable `pdo_mysql`
(and `mysqli` if you want it too). Without this, `db.php` will
fatal-error on every request once the config is in place.

## 4. Create the database and user

cPanel → **MySQL Databases**:
- Database name: `doctor_prasanna_application`
- Database user: `admin`, set a strong password
- Add the user to the database with **All Privileges**

Note the full prefixed names cPanel generates (e.g.
`youracct_doctor_prasanna_application` / `youracct_admin`) — you'll need
them in step 6.

## 5. Import the schema

cPanel → **phpMyAdmin** → select the new database → **Import** → upload
`schema.sql` (in the repo root, now on the server from step 2). This
creates the empty tables: `patients`, `appointments`, `records`,
`record_entries`, `doctor_auth`, `mail_log`, `payments`.

If SSH access is available instead:
```bash
mysql -u youracct_admin -p youracct_doctor_prasanna_application < schema.sql
```

## 6. Configure the connection

On the server, copy `config-db.local.php.example` to `config-db.local.php`
(File Manager: duplicate + rename, or upload a new file) and fill in the
real values from step 4:
```php
define('DB_HOST', 'localhost');   // GoDaddy shared hosting: MySQL is on the same box
define('DB_PORT', 3306);
define('DB_NAME', 'youracct_doctor_prasanna_application');
define('DB_USER', 'youracct_admin');
define('DB_PASS', 'the-password-you-set-in-cpanel');
```
This file only needs to exist on the server — it's gitignored, never
committed.

## 7. Verify

Visit the site — `admin.php` should redirect to `admin-setup.php` since
`doctor_auth` is empty (this is expected for a fresh start). Create the
doctor login there.

Then do a smoke test on the patient-facing side: register a test patient,
book an appointment, confirm it shows up in the admin dashboard. This
confirms reads/writes are hitting MySQL correctly.

## 8. Clean up

The old `data/*.json` files can stay on the server as an inert archive
(nothing reads them anymore), or be removed once you're confident the
MySQL setup is solid.

## 9. Set up Razorpay for consultation payments

Patients pay the consultation fee (UPI, debit, credit card) through
Razorpay's hosted Checkout after the doctor closes a case. Card details
never touch this site — Razorpay handles that, which keeps the app out of
PCI-DSS scope.

1. **Create a Razorpay account** at razorpay.com if you don't have one
   (business PAN and a bank account are needed for KYC before you can go
   live — this can take a few days, so start test-mode integration while
   it's pending).
2. **Get API keys**: Dashboard → **Settings → API Keys** → generate the
   **test mode** key pair first (`rzp_test_...`). Switch to **live mode**
   keys only once you're ready to accept real payments.
3. On the server, copy `config-razorpay.local.php.example` to
   `config-razorpay.local.php` and fill in:
   ```php
   define('RAZORPAY_KEY_ID', 'rzp_test_xxxxxxxxxxxx');
   define('RAZORPAY_KEY_SECRET', 'the-key-secret');
   define('RAZORPAY_WEBHOOK_SECRET', 'a-secret-you-choose-below');
   ```
   Like the other `config-*.local.php` files, this only needs to exist on
   the server — it's gitignored, never committed.
4. **Register the webhook**: Dashboard → **Settings → Webhooks** → add
   `https://drprasannakumar.com/razorpay-webhook.php`, subscribe to the
   **`payment.captured`** event, and set a secret — use that same value for
   `RAZORPAY_WEBHOOK_SECRET` above. This is a fallback that confirms
   payment server-to-server even if the patient closes their browser right
   after paying, before the on-page confirmation fires.
5. Confirm `SITE_URL` in `config.php` matches the real domain — it's used
   to build the payment link sent in the "Consultation Fee Due" email.
6. **Test it**: close a case as the doctor with a fee amount, then pay from
   the patient portal using [Razorpay's published test card/UPI
   details](https://razorpay.com/docs/payments/payments/test-card-upi-details/)
   (test mode only — real cards won't work against test keys). Confirm the
   appointment shows "Paid" and both patient and doctor receive a receipt
   email.
7. When ready to accept real money, swap the three `config-razorpay.local.php`
   values for the **live mode** key pair and a live-mode webhook secret
   (Razorpay keeps test and live webhooks separate).
