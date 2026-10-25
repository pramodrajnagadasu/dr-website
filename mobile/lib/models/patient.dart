class Patient {
  final String id;
  final String? salutation;
  final String firstName;
  final String? lastName;
  final String? gender;
  final String? dob;
  final String? mobile;
  final String? email;

  Patient({
    required this.id,
    this.salutation,
    required this.firstName,
    this.lastName,
    this.gender,
    this.dob,
    this.mobile,
    this.email,
  });

  String get fullName =>
      [if (salutation != null && salutation!.isNotEmpty) '$salutation.', firstName, lastName]
          .where((s) => s != null && s.isNotEmpty)
          .join(' ');

  factory Patient.fromJson(Map<String, dynamic> j) => Patient(
        id: j['id'] as String,
        salutation: j['salutation'] as String?,
        firstName: j['first_name'] as String,
        lastName: j['last_name'] as String?,
        gender: j['gender'] as String?,
        dob: j['dob'] as String?,
        mobile: j['mobile'] as String?,
        email: j['email'] as String?,
      );
}

/// The lightweight {id, name, gender, dob} shape returned by check-patient / login profile lists.
class PatientOption {
  final String id;
  final String name;
  final String? gender;
  final String? dob;

  PatientOption({required this.id, required this.name, this.gender, this.dob});

  factory PatientOption.fromJson(Map<String, dynamic> j) => PatientOption(
        id: j['id'] as String,
        name: j['name'] as String,
        gender: j['gender'] as String?,
        dob: j['dob'] as String?,
      );
}
