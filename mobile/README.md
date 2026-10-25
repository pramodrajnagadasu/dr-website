# Dr. Prasanna Kumar K — patient app (Flutter)

Native iOS/Android app covering the public site (home, doctor info, booking)
and the patient portal (OTP login, appointments, medical records, Razorpay
payment). Talks to the PHP backend's `api/` JSON endpoints (see `../api/`)
over bearer-token auth — see `../lib/api_auth.php` and `../schema.sql`
(`api_otp_requests` / `api_tokens` tables) for the backend half.

This directory is a separate build artifact (App Store / Play Store) and is
**never uploaded to the PHP host** — see `DEPLOYMENT.md` at the repo root.

## Setup

```
flutter pub get
```

## Running

The API base URL defaults to `https://drprasannakumark.com/api`. Override it
at run/build time to point at a local dev server instead:

```
# Android emulator reaching your host machine's local PHP dev server:
flutter run --dart-define=API_BASE_URL=http://10.0.2.2/dr-website/api

# iOS simulator (can use localhost directly):
flutter run --dart-define=API_BASE_URL=http://localhost/dr-website/api
```

## Building

```
flutter build apk --debug      # Android, unsigned debug build
flutter build appbundle        # Android, Play Store release (needs signing config)
flutter build ios              # iOS — requires a Mac with Xcode; not possible on Windows
```

## Structure

- `lib/api/` — `ApiClient` (Dio + bearer token storage) and `Repository`
  (one method per `api/*.php` endpoint).
- `lib/state/auth_state.dart` — current login/session state, shared via
  `provider`.
- `lib/models/` — `Patient`, `Appointment`, `RecordEntry`, etc.
- `lib/screens/` — `home/`, `booking/`, `portal/`, `payment/`.
- `lib/widgets/login_flow_sheet.dart` — the shared mobile+OTP login/register
  flow, used both when booking an appointment and logging into the portal.
- `lib/theme/app_theme.dart` — brand colors pulled from `../portal.css`.

## Known follow-ups

- App icon is still the Flutter default placeholder — swap in a real
  1024×1024 icon (e.g. via `flutter_launcher_icons`) before store submission.
- Doctor/admin-side features (closing cases, admin dashboard, schedule
  management) are intentionally out of scope for this app.
- iOS build/signing/TestFlight/App Store submission needs to happen on a Mac
  (or a cloud Mac CI like Codemagic) — not possible from this Windows
  machine.
