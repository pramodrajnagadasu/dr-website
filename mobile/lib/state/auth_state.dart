import 'package:flutter/foundation.dart';

import '../api/api_client.dart';
import '../api/repository.dart';
import '../models/appointment.dart';
import '../models/patient.dart';

enum AuthStatus { unknown, signedOut, signedIn }

/// Holds the current login state (token + patient) and the patient's
/// appointments once loaded, shared across the booking flow and the portal.
class AuthState extends ChangeNotifier {
  final ApiClient client;
  final Repository repo;
  AuthState(this.client) : repo = Repository(client);

  AuthStatus status = AuthStatus.unknown;
  Patient? patient;
  List<Appointment> appointments = [];

  Future<void> bootstrap() async {
    final token = await client.token;
    if (token == null) {
      status = AuthStatus.signedOut;
      notifyListeners();
      return;
    }
    try {
      final (p, appts) = await repo.getAppointments();
      patient = p;
      appointments = appts;
      status = AuthStatus.signedIn;
    } catch (_) {
      await client.setToken(null);
      status = AuthStatus.signedOut;
    }
    notifyListeners();
  }

  void setSignedIn(String token, Patient p) {
    client.setToken(token);
    patient = p;
    status = AuthStatus.signedIn;
    notifyListeners();
  }

  Future<void> refreshAppointments() async {
    if (status != AuthStatus.signedIn) return;
    final (p, appts) = await repo.getAppointments();
    patient = p;
    appointments = appts;
    notifyListeners();
  }

  Future<void> signOut() async {
    await repo.logout();
    await client.setToken(null);
    patient = null;
    appointments = [];
    status = AuthStatus.signedOut;
    notifyListeners();
  }
}
