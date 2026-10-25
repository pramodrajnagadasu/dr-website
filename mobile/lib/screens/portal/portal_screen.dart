import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../models/appointment.dart';
import '../../state/auth_state.dart';
import '../../theme/app_theme.dart';
import '../../widgets/login_flow_sheet.dart';

class PortalScreen extends StatelessWidget {
  const PortalScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Records')),
      body: Consumer<AuthState>(
        builder: (context, auth, _) {
          if (auth.status == AuthStatus.signedIn) return const _AppointmentsList();
          return _LoginGate(auth: auth);
        },
      ),
    );
  }
}

class _LoginGate extends StatelessWidget {
  final AuthState auth;
  const _LoginGate({required this.auth});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.folder_shared_outlined, size: 56, color: AppColors.gray),
          const SizedBox(height: 16),
          const Text('Log in with your mobile number to view your appointments and medical records.',
              textAlign: TextAlign.center, style: TextStyle(color: AppColors.gray)),
          const SizedBox(height: 20),
          ElevatedButton(
            onPressed: () async {
              final result = await LoginFlowSheet.show(context, auth.repo);
              if (result != null) auth.setSignedIn(result.token, result.patient);
            },
            child: const Text('Log In'),
          ),
        ]),
      ),
    );
  }
}

class _AppointmentsList extends StatefulWidget {
  const _AppointmentsList();
  @override
  State<_AppointmentsList> createState() => _AppointmentsListState();
}

class _AppointmentsListState extends State<_AppointmentsList> {
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh() async {
    setState(() => _loading = true);
    try {
      await context.read<AuthState>().refreshAppointments();
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthState>();
    final patient = auth.patient;
    final appts = auth.appointments;

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (patient != null)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(children: [
                  const CircleAvatar(backgroundColor: AppColors.blue, child: Icon(Icons.person, color: Colors.white)),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(patient.fullName, style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.navy)),
                      Text(patient.mobile ?? '', style: const TextStyle(color: AppColors.gray, fontSize: 13)),
                    ]),
                  ),
                  IconButton(
                    onPressed: () => auth.signOut(),
                    icon: const Icon(Icons.logout, color: AppColors.gray),
                    tooltip: 'Logout',
                  ),
                ]),
              ),
            ),
          const SizedBox(height: 16),
          Text('${appts.length} appointment${appts.length != 1 ? 's' : ''} found',
              style: const TextStyle(color: AppColors.gray, fontSize: 13)),
          const SizedBox(height: 10),
          if (_loading)
            const Padding(padding: EdgeInsets.all(40), child: Center(child: CircularProgressIndicator()))
          else if (appts.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 40),
              child: Center(child: Text('No appointments found.', style: TextStyle(color: AppColors.gray))),
            )
          else
            ...appts.map((a) => _AppointmentCard(appt: a)),
          const SizedBox(height: 12),
          Center(
            child: TextButton.icon(
              onPressed: () => context.push('/book'),
              icon: const Icon(Icons.add_circle_outline),
              label: const Text('Book New Appointment'),
            ),
          ),
        ],
      ),
    );
  }
}

class _AppointmentCard extends StatelessWidget {
  final Appointment appt;
  const _AppointmentCard({required this.appt});

  @override
  Widget build(BuildContext context) {
    final today = DateTime.now();
    final upcoming = !appt.date.isBefore(DateTime(today.year, today.month, today.day));
    Color statusColor = AppColors.gray;
    String statusLabel = upcoming ? 'Upcoming' : 'Past';
    if (appt.status == 'cancelled') {
      statusColor = AppColors.danger;
      statusLabel = 'Cancelled';
    } else if (appt.status == 'postponed') {
      statusColor = AppColors.goldLight;
      statusLabel = 'Rescheduled';
    } else if (upcoming) {
      statusColor = AppColors.success;
    }

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Text(appt.refNumber, style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.blue, fontSize: 13)),
            const Spacer(),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(color: statusColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
              child: Text(statusLabel, style: TextStyle(color: statusColor, fontSize: 11, fontWeight: FontWeight.w700)),
            ),
          ]),
          const SizedBox(height: 8),
          Row(children: [
            const Icon(Icons.calendar_today, size: 14, color: AppColors.blue),
            const SizedBox(width: 6),
            Text(appt.appointmentDate),
          ]),
          const SizedBox(height: 4),
          Row(children: [
            const Icon(Icons.access_time, size: 14, color: AppColors.gray),
            const SizedBox(width: 6),
            Text(appt.timeSlot),
          ]),
          if (appt.payment != null) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: (appt.payment!.isPaid ? AppColors.success : AppColors.goldLight).withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                appt.payment!.isPaid
                    ? 'Paid ₹${appt.payment!.amountRupees.toStringAsFixed(2)}'
                    : 'Payment Pending — ₹${appt.payment!.amountRupees.toStringAsFixed(2)}',
                style: TextStyle(
                    color: appt.payment!.isPaid ? AppColors.success : AppColors.gold, fontSize: 12, fontWeight: FontWeight.w600),
              ),
            ),
          ],
          const SizedBox(height: 10),
          Row(children: [
            if (appt.payment != null && !appt.payment!.isPaid)
              Expanded(
                child: OutlinedButton(
                  onPressed: () => context.push('/pay/${appt.refNumber}'),
                  child: const Text('Pay Now'),
                ),
              ),
            if (appt.payment != null && !appt.payment!.isPaid) const SizedBox(width: 8),
            Expanded(
              child: ElevatedButton(
                onPressed: () => context.push('/record/${appt.refNumber}'),
                child: const Text('View Record'),
              ),
            ),
          ]),
        ]),
      ),
    );
  }
}
