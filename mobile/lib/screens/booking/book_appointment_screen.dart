import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:table_calendar/table_calendar.dart';

import '../../api/api_client.dart';
import '../../api/repository.dart';
import '../../state/auth_state.dart';
import '../../theme/app_theme.dart';
import '../../widgets/login_flow_sheet.dart';

const _doctorKey = 'prasanna';

class BookAppointmentScreen extends StatefulWidget {
  const BookAppointmentScreen({super.key});

  @override
  State<BookAppointmentScreen> createState() => _BookAppointmentScreenState();
}

class _BookAppointmentScreenState extends State<BookAppointmentScreen> {
  List<int> _enabledDays = []; // 0=Sunday..6=Saturday, matches DateTime.weekday % 7
  bool _loadingDoctor = true;
  DateTime? _selectedDate;
  bool _loadingSlots = false;
  List<String> _slots = [];
  Set<String> _booked = {};
  bool _dayEnabled = true;
  String? _selectedSlot;
  bool _booking = false;
  String? _error;

  Repository get _repo => context.read<AuthState>().repo;

  @override
  void initState() {
    super.initState();
    _loadDoctor();
  }

  Future<void> _loadDoctor() async {
    try {
      final (_, enabledDays) = await _repo.getDoctor();
      setState(() {
        _enabledDays = enabledDays;
        _loadingDoctor = false;
      });
    } catch (e) {
      setState(() => _loadingDoctor = false);
    }
  }

  bool _isDayEnabled(DateTime day) => _enabledDays.contains(day.weekday % 7);

  String _dateLabel(DateTime d) =>
      '${d.day.toString().padLeft(2, '0')}-${d.month.toString().padLeft(2, '0')}-${d.year}';

  Future<void> _onDaySelected(DateTime day) async {
    setState(() {
      _selectedDate = day;
      _selectedSlot = null;
      _loadingSlots = true;
      _error = null;
    });
    try {
      final result = await _repo.getSlots(_doctorKey, _dateLabel(day));
      setState(() {
        _slots = result.slots;
        _booked = result.booked;
        _dayEnabled = result.enabled;
        _loadingSlots = false;
      });
    } on ApiException catch (e) {
      setState(() {
        _error = e.message;
        _loadingSlots = false;
      });
    }
  }

  bool _isSlotPast(String label) {
    if (_selectedDate == null) return false;
    final today = DateTime.now();
    final isToday = _selectedDate!.year == today.year && _selectedDate!.month == today.month && _selectedDate!.day == today.day;
    if (!isToday) return false;
    final end = label.split(' - ').last.trim(); // e.g. "08:45 AM"
    final match = RegExp(r'^(\d{1,2}):(\d{2}) (AM|PM)$').firstMatch(end);
    if (match == null) return false;
    var hour = int.parse(match.group(1)!) % 12;
    if (match.group(3) == 'PM') hour += 12;
    final minute = int.parse(match.group(2)!);
    final slotEnd = DateTime(today.year, today.month, today.day, hour, minute);
    return slotEnd.isBefore(today);
  }

  Future<void> _onSlotTap(String slot) async {
    setState(() => _selectedSlot = slot);
    final auth = context.read<AuthState>();

    if (auth.status != AuthStatus.signedIn) {
      final result = await LoginFlowSheet.show(context, _repo);
      if (result == null) return;
      auth.setSignedIn(result.token, result.patient);
    }

    setState(() => _booking = true);
    try {
      final ref = await _repo.bookAppointment(doctorKey: _doctorKey, dateLabel: _dateLabel(_selectedDate!), timeslot: slot);
      if (!mounted) return;
      _showSuccess(ref);
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _booking = false);
    }
  }

  void _showSuccess(String ref) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => AlertDialog(
        icon: const Icon(Icons.check_circle, color: AppColors.success, size: 48),
        title: const Text('Appointment Booked!'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          const Text('Our team will contact you shortly to confirm.'),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(color: AppColors.blue.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(8)),
            child: Text('REF: $ref', style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.blue)),
          ),
        ]),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              setState(() {
                _selectedDate = null;
                _selectedSlot = null;
                _slots = [];
              });
            },
            child: const Text('Done'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Book an Appointment')),
      body: _loadingDoctor
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      const Text('Dr. Prasanna Kumar K', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.navy)),
                      const Text('MBBS, AFIH — Occupational Health & Primary Care', style: TextStyle(color: AppColors.gray, fontSize: 13)),
                      const SizedBox(height: 16),
                      TableCalendar(
                        firstDay: DateTime.now(),
                        lastDay: DateTime.now().add(const Duration(days: 180)),
                        focusedDay: _selectedDate ?? DateTime.now(),
                        selectedDayPredicate: (d) => _selectedDate != null && isSameDay(d, _selectedDate),
                        enabledDayPredicate: _isDayEnabled,
                        onDaySelected: (day, focused) => _onDaySelected(day),
                        calendarStyle: const CalendarStyle(
                          selectedDecoration: BoxDecoration(color: AppColors.blue, shape: BoxShape.circle),
                          todayDecoration: BoxDecoration(color: AppColors.goldLight, shape: BoxShape.circle),
                          disabledTextStyle: TextStyle(color: Colors.grey),
                        ),
                        headerStyle: const HeaderStyle(formatButtonVisible: false, titleCentered: true),
                      ),
                    ]),
                  ),
                ),
                if (_selectedDate != null) ...[
                  const SizedBox(height: 16),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('Available Slots — ${_dateLabel(_selectedDate!)}',
                            style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.navy)),
                        const SizedBox(height: 12),
                        if (_loadingSlots)
                          const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()))
                        else if (!_dayEnabled || _slots.isEmpty)
                          const Padding(
                            padding: EdgeInsets.symmetric(vertical: 12),
                            child: Text('Doctor is not available on this day. Please choose another date.', style: TextStyle(color: AppColors.gray)),
                          )
                        else
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: _slots.map((slot) {
                              final isBooked = _booked.contains(slot);
                              final isPast = _isSlotPast(slot);
                              final isSelected = _selectedSlot == slot;
                              Color bg = AppColors.success;
                              if (isBooked) {
                                bg = AppColors.danger;
                              } else if (isPast) {
                                bg = Colors.grey;
                              } else if (isSelected) {
                                bg = AppColors.navyMid;
                              }
                              return GestureDetector(
                                onTap: (isBooked || isPast || _booking) ? null : () => _onSlotTap(slot),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                  decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(6)),
                                  child: Text(slot, style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
                                ),
                              );
                            }).toList(),
                          ),
                      ]),
                    ),
                  ),
                ],
                if (_error != null) ...[
                  const SizedBox(height: 12),
                  Text(_error!, style: const TextStyle(color: AppColors.danger)),
                ],
                if (_booking) ...[
                  const SizedBox(height: 16),
                  const Center(child: CircularProgressIndicator()),
                ],
              ],
            ),
    );
  }
}
