import 'dart:io';

import 'package:dio/dio.dart';

import '../models/appointment.dart';
import '../models/patient.dart';
import 'api_client.dart';

class DoctorInfo {
  final String key;
  final String name;
  final String qualification;
  final String specialist;
  DoctorInfo({required this.key, required this.name, required this.qualification, required this.specialist});

  factory DoctorInfo.fromJson(Map<String, dynamic> j) => DoctorInfo(
        key: j['key'] as String,
        name: j['name'] as String,
        qualification: j['qualification'] as String,
        specialist: j['specialist'] as String,
      );
}

class SlotsResult {
  final List<String> slots;
  final Set<String> booked;
  final bool enabled;
  SlotsResult({required this.slots, required this.booked, required this.enabled});
}

/// Result of api/login.php step 1 — either a token was issued directly, or
/// the caller must pick a profile via [loginWithTicket].
class OtpVerifyResult {
  final String? token;
  final Patient? patient;
  final bool needsSelection;
  final String? verifyToken;
  final List<PatientOption> patients;

  OtpVerifyResult({this.token, this.patient, this.needsSelection = false, this.verifyToken, this.patients = const []});
}

class LoginResult {
  final String token;
  final Patient patient;
  LoginResult({required this.token, required this.patient});
}

/// One method per api/*.php endpoint. Screens call through this instead of
/// touching ApiClient directly.
class Repository {
  final ApiClient _client;
  Repository(this._client);

  Future<(List<DoctorInfo>, List<int>)> getDoctor() async {
    final data = await _client.get('/doctor.php');
    final doctors = (data['doctors'] as List).map((d) => DoctorInfo.fromJson(d as Map<String, dynamic>)).toList();
    final enabledDays = (data['enabled_days'] as List).map((e) => e as int).toList();
    return (doctors, enabledDays);
  }

  Future<SlotsResult> getSlots(String doctorKey, String dateLabel) async {
    final data = await _client.get('/slots.php', query: {'doctor': doctorKey, 'date': dateLabel});
    return SlotsResult(
      slots: (data['slots'] as List).map((s) => s as String).toList(),
      booked: (data['booked'] as List).map((s) => s as String).toSet(),
      enabled: data['enabled'] as bool? ?? false,
    );
  }

  Future<List<PatientOption>?> checkPatient(String mobile) async {
    final data = await _client.post('/check-patient.php', data: {'mobile': mobile});
    if (data['exists'] != true) return null;
    return (data['patients'] as List).map((p) => PatientOption.fromJson(p as Map<String, dynamic>)).toList();
  }

  Future<String> registerPatient({
    required String salutation,
    required String firstName,
    String lastName = '',
    required String gender,
    required String dob,
    required String mobile,
    required String email,
  }) async {
    final data = await _client.post('/register-patient.php', data: {
      'salutation': salutation,
      'first_name': firstName,
      'last_name': lastName,
      'gender': gender,
      'dob': dob,
      'mobile': mobile,
      'email': email,
    });
    return data['patient_id'] as String;
  }

  Future<String> sendOtp(String mobile) async {
    final data = await _client.post('/send-otp.php', data: {'mobile': mobile});
    return data['email'] as String? ?? 'your email';
  }

  Future<OtpVerifyResult> verifyOtp(String mobile, String otp) async {
    final data = await _client.post('/login.php', data: {'mobile': mobile, 'otp': otp});
    if (data['needs_patient_selection'] == true) {
      return OtpVerifyResult(
        needsSelection: true,
        verifyToken: data['verify_token'] as String,
        patients: (data['patients'] as List).map((p) => PatientOption.fromJson(p as Map<String, dynamic>)).toList(),
      );
    }
    return OtpVerifyResult(token: data['token'] as String, patient: Patient.fromJson(data['patient'] as Map<String, dynamic>));
  }

  Future<LoginResult> loginWithTicket(String mobile, String verifyToken, String patientId) async {
    final data = await _client.post('/login.php', data: {
      'mobile': mobile,
      'verify_token': verifyToken,
      'patient_id': patientId,
    });
    return LoginResult(token: data['token'] as String, patient: Patient.fromJson(data['patient'] as Map<String, dynamic>));
  }

  Future<String> bookAppointment({required String doctorKey, required String dateLabel, required String timeslot}) async {
    final data = await _client.post('/book-appointment.php',
        data: {'doctor': doctorKey, 'date': dateLabel, 'timeslot': timeslot}, auth: true);
    return data['ref'] as String;
  }

  Future<(Patient, List<Appointment>)> getAppointments() async {
    final data = await _client.get('/appointments.php', auth: true);
    final patient = Patient.fromJson(data['patient'] as Map<String, dynamic>);
    final appts = (data['appointments'] as List).map((a) => Appointment.fromJson(a as Map<String, dynamic>)).toList();
    return (patient, appts);
  }

  Future<List<RecordEntry>> getRecord(String ref) async {
    final data = await _client.get('/record.php', query: {'ref': ref}, auth: true);
    return (data['entries'] as List).map((e) => RecordEntry.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<void> addRecordEntry({required String ref, String description = '', File? file}) async {
    final form = FormData.fromMap({
      'ref': ref,
      'description': description,
      if (file != null) 'file': await MultipartFile.fromFile(file.path, filename: file.uri.pathSegments.last),
    });
    await _client.postMultipart('/record-entry.php', form, auth: true);
  }

  Future<List<int>> getRecordFileBytes(String ref, String filename) =>
      _client.getBytes('/record-file.php?ref=${Uri.encodeQueryComponent(ref)}&f=${Uri.encodeQueryComponent(filename)}');

  Future<Map<String, dynamic>> getPaymentOrder(String ref) =>
      _client.get('/payment-order.php', query: {'ref': ref}, auth: true);

  Future<Map<String, dynamic>> verifyPayment({
    required String ref,
    required String orderId,
    required String paymentId,
    required String signature,
  }) =>
      _client.post('/payment-verify.php', auth: true, data: {
        'ref': ref,
        'razorpay_order_id': orderId,
        'razorpay_payment_id': paymentId,
        'razorpay_signature': signature,
      });

  Future<void> sendContact({
    required String fname,
    String lname = '',
    required String email,
    required String phone,
    String service = '',
    String message = '',
  }) =>
      _client.post('/contact.php', data: {
        'fname': fname,
        'lname': lname,
        'email': email,
        'phone': phone,
        'service': service,
        'message': message,
      });

  Future<void> logout() async {
    try {
      await _client.post('/logout.php', auth: true);
    } catch (_) {
      // best-effort revoke; local token clear happens regardless
    }
  }
}
