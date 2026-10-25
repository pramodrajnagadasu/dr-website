import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../api/api_client.dart';
import '../api/repository.dart';
import '../models/patient.dart';
import '../theme/app_theme.dart';
import 'otp_boxes.dart';

enum _Step { mobile, register, otp }

/// Shared mobile-number + OTP login/registration flow, used both for
/// booking an appointment and for the patient-portal login — mirrors
/// doctor-details.php's booking modal + patient-portal.php's login screen.
class LoginFlowSheet extends StatefulWidget {
  final Repository repo;
  const LoginFlowSheet({super.key, required this.repo});

  static Future<LoginResult?> show(BuildContext context, Repository repo) {
    return showModalBottomSheet<LoginResult>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(18)),
      ),
      builder: (_) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
        child: LoginFlowSheet(repo: repo),
      ),
    );
  }

  @override
  State<LoginFlowSheet> createState() => _LoginFlowSheetState();
}

class _LoginFlowSheetState extends State<LoginFlowSheet> {
  _Step _step = _Step.mobile;
  bool _loading = false;
  String? _error;

  final _mobileCtrl = TextEditingController();
  List<PatientOption>? _existingPatients;
  String? _selectedPatientId;

  // registration fields
  String _regSalutation = '';
  final _regFirstName = TextEditingController();
  final _regLastName = TextEditingController();
  String _regGender = '';
  final _regDob = TextEditingController();
  final _regEmail = TextEditingController();

  final _otpKey = GlobalKey<OtpBoxesState>();
  String _otpValue = '';
  String _maskedEmail = '';
  int _resendIn = 0;
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
    _mobileCtrl.dispose();
    _regFirstName.dispose();
    _regLastName.dispose();
    _regDob.dispose();
    _regEmail.dispose();
    super.dispose();
  }

  String get _digits => _mobileCtrl.text.replaceAll(RegExp(r'\D'), '');

  Future<void> _runGuarded(Future<void> Function() action) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await action();
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _checkMobile() async {
    if (_digits.length != 10) {
      setState(() => _error = 'Enter a valid 10-digit mobile number.');
      return;
    }
    await _runGuarded(() async {
      final patients = await widget.repo.checkPatient(_digits);
      if (patients == null) {
        setState(() => _step = _Step.register);
      } else if (patients.length == 1) {
        _selectedPatientId = patients.first.id;
        await _sendOtp();
      } else {
        setState(() => _existingPatients = patients);
      }
    });
  }

  Future<void> _register() async {
    if (_regFirstName.text.trim().isEmpty || _regGender.isEmpty || _regDob.text.trim().isEmpty || _regEmail.text.trim().isEmpty) {
      setState(() => _error = 'Please fill in all required fields.');
      return;
    }
    await _runGuarded(() async {
      final id = await widget.repo.registerPatient(
        salutation: _regSalutation,
        firstName: _regFirstName.text.trim(),
        lastName: _regLastName.text.trim(),
        gender: _regGender,
        dob: _regDob.text.trim(),
        mobile: _digits,
        email: _regEmail.text.trim(),
      );
      _selectedPatientId = id;
      await _sendOtp();
    });
  }

  Future<void> _sendOtp() async {
    await _runGuarded(() async {
      final masked = await widget.repo.sendOtp(_digits);
      _maskedEmail = masked;
      _step = _Step.otp;
      _startResendTimer();
    });
  }

  void _startResendTimer() {
    _timer?.cancel();
    setState(() => _resendIn = 60);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (!mounted) return;
      setState(() => _resendIn--);
      if (_resendIn <= 0) t.cancel();
    });
  }

  Future<void> _verifyOtp() async {
    if (_otpValue.length != 6) {
      setState(() => _error = 'Enter the complete 6-digit code.');
      return;
    }
    await _runGuarded(() async {
      final result = await widget.repo.verifyOtp(_digits, _otpValue);
      if (!result.needsSelection) {
        Navigator.of(context).pop(LoginResult(token: result.token!, patient: result.patient!));
        return;
      }
      // Multiple patients on this mobile — use the one already chosen
      // (from check-patient / fresh registration) without asking again.
      final patientId = _selectedPatientId ?? result.patients.first.id;
      final login = await widget.repo.loginWithTicket(_digits, result.verifyToken!, patientId);
      if (mounted) Navigator.of(context).pop(login);
    });
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(24, 20, 24, 28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                margin: const EdgeInsets.only(bottom: 16),
                decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
              ),
            ),
            if (_step == _Step.mobile) ..._buildMobileStep(),
            if (_step == _Step.register) ..._buildRegisterStep(),
            if (_step == _Step.otp) ..._buildOtpStep(),
            if (_error != null) ...[
              const SizedBox(height: 10),
              Text(_error!, style: const TextStyle(color: AppColors.danger, fontSize: 13)),
            ],
          ],
        ),
      ),
    );
  }

  List<Widget> _buildMobileStep() {
    return [
      const Text('Mobile Number', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
      const SizedBox(height: 12),
      TextField(
        controller: _mobileCtrl,
        keyboardType: TextInputType.phone,
        maxLength: 10,
        inputFormatters: [FilteringTextInputFormatter.digitsOnly],
        decoration: const InputDecoration(hintText: 'Enter 10-digit mobile number', counterText: ''),
      ),
      if (_existingPatients != null) ...[
        const SizedBox(height: 14),
        const Text('Select Patient', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
        const SizedBox(height: 6),
        ..._existingPatients!.map((p) => RadioListTile<String>(
              value: p.id,
              groupValue: _selectedPatientId,
              onChanged: (v) => setState(() => _selectedPatientId = v),
              title: Text(p.name),
              subtitle: Text([p.gender, p.dob].where((s) => s != null && s.isNotEmpty).join(' • ')),
              dense: true,
              contentPadding: EdgeInsets.zero,
            )),
        const SizedBox(height: 4),
        ElevatedButton(
          onPressed: _loading || _selectedPatientId == null ? null : () => _runGuarded(_sendOtp),
          child: _loading ? const _Spinner() : const Text('Get Code'),
        ),
      ] else ...[
        const SizedBox(height: 18),
        ElevatedButton(
          onPressed: _loading ? null : _checkMobile,
          child: _loading ? const _Spinner() : const Text('Continue'),
        ),
      ],
    ];
  }

  List<Widget> _buildRegisterStep() {
    return [
      const Text('New here? Register', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
      const SizedBox(height: 14),
      DropdownButtonFormField<String>(
        initialValue: _regSalutation.isEmpty ? null : _regSalutation,
        decoration: const InputDecoration(labelText: 'Salutation'),
        items: const ['Mr', 'Mrs', 'Ms', 'Dr', 'Master', 'Baby']
            .map((s) => DropdownMenuItem(value: s, child: Text(s)))
            .toList(),
        onChanged: (v) => setState(() => _regSalutation = v ?? ''),
      ),
      const SizedBox(height: 10),
      TextField(controller: _regFirstName, decoration: const InputDecoration(labelText: 'First Name *')),
      const SizedBox(height: 10),
      TextField(controller: _regLastName, decoration: const InputDecoration(labelText: 'Last Name')),
      const SizedBox(height: 10),
      DropdownButtonFormField<String>(
        initialValue: _regGender.isEmpty ? null : _regGender,
        decoration: const InputDecoration(labelText: 'Gender *'),
        items: const ['Male', 'Female', 'Other'].map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
        onChanged: (v) => setState(() => _regGender = v ?? ''),
      ),
      const SizedBox(height: 10),
      TextField(
        controller: _regDob,
        decoration: const InputDecoration(labelText: 'Date of Birth (DD-MM-YYYY) *'),
        keyboardType: TextInputType.datetime,
        onTap: () async {
          final picked = await showDatePicker(
            context: context,
            firstDate: DateTime(1900),
            lastDate: DateTime.now(),
            initialDate: DateTime(1990),
          );
          if (picked != null) {
            _regDob.text =
                '${picked.day.toString().padLeft(2, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.year}';
          }
        },
        readOnly: true,
      ),
      const SizedBox(height: 10),
      TextField(controller: _regEmail, decoration: const InputDecoration(labelText: 'Email *'), keyboardType: TextInputType.emailAddress),
      const SizedBox(height: 18),
      ElevatedButton(
        onPressed: _loading ? null : _register,
        style: ElevatedButton.styleFrom(backgroundColor: AppColors.success),
        child: _loading ? const _Spinner() : const Text('Register & Send Code'),
      ),
    ];
  }

  List<Widget> _buildOtpStep() {
    return [
      const Text('Enter Verification Code', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
      const SizedBox(height: 6),
      Text('A 6-digit code was sent to $_maskedEmail', style: const TextStyle(color: AppColors.gray, fontSize: 13)),
      const SizedBox(height: 16),
      OtpBoxes(key: _otpKey, onChanged: (v) => _otpValue = v),
      const SizedBox(height: 14),
      Center(
        child: _resendIn > 0
            ? Text('Resend in ${_resendIn}s', style: const TextStyle(color: AppColors.gray, fontSize: 13))
            : TextButton(onPressed: _loading ? null : () => _runGuarded(_sendOtp), child: const Text('Resend Code')),
      ),
      const SizedBox(height: 10),
      ElevatedButton(
        onPressed: _loading ? null : _verifyOtp,
        child: _loading ? const _Spinner() : const Text('Verify & Continue'),
      ),
    ];
  }
}

class _Spinner extends StatelessWidget {
  const _Spinner();
  @override
  Widget build(BuildContext context) => const SizedBox(
        width: 20,
        height: 20,
        child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white),
      );
}
