class PaymentSummary {
  final String status; // 'created' | 'paid'
  final int amountPaise;
  PaymentSummary({required this.status, required this.amountPaise});

  bool get isPaid => status == 'paid';
  double get amountRupees => amountPaise / 100;

  factory PaymentSummary.fromJson(Map<String, dynamic> j) => PaymentSummary(
        status: j['status'] as String,
        amountPaise: j['amount_paise'] as int,
      );
}

class Appointment {
  final String refNumber;
  final String doctor;
  final String appointmentDate; // DD-MM-YYYY
  final String timeSlot;
  final String status; // confirmed | cancelled | postponed
  final String? cancelReason;
  final List<dynamic>? rescheduleHistory;
  final PaymentSummary? payment;

  Appointment({
    required this.refNumber,
    required this.doctor,
    required this.appointmentDate,
    required this.timeSlot,
    required this.status,
    this.cancelReason,
    this.rescheduleHistory,
    this.payment,
  });

  DateTime get date {
    final parts = appointmentDate.split('-');
    return DateTime(int.parse(parts[2]), int.parse(parts[1]), int.parse(parts[0]));
  }

  factory Appointment.fromJson(Map<String, dynamic> j) => Appointment(
        refNumber: j['ref_number'] as String,
        doctor: j['doctor'] as String,
        appointmentDate: j['appointment_date'] as String,
        timeSlot: j['time_slot'] as String,
        status: j['status'] as String? ?? 'confirmed',
        cancelReason: j['cancel_reason'] as String?,
        rescheduleHistory: j['reschedule_history'] as List<dynamic>?,
        payment: j['payment'] != null ? PaymentSummary.fromJson(j['payment'] as Map<String, dynamic>) : null,
      );
}

class RecordEntry {
  final String id;
  final String by; // 'doctor' | 'patient'
  final String? description;
  final String? filename;
  final String? originalName;
  final String createdAt;

  RecordEntry({
    required this.id,
    required this.by,
    this.description,
    this.filename,
    this.originalName,
    required this.createdAt,
  });

  bool get isImage {
    final f = filename?.toLowerCase() ?? '';
    return f.endsWith('.jpg') || f.endsWith('.jpeg') || f.endsWith('.png') || f.endsWith('.gif') || f.endsWith('.webp');
  }

  factory RecordEntry.fromJson(Map<String, dynamic> j) => RecordEntry(
        id: j['id'] as String,
        by: j['by'] as String,
        description: j['description'] as String?,
        filename: j['filename'] as String?,
        originalName: j['original_name'] as String?,
        createdAt: j['created_at'] as String,
      );
}
