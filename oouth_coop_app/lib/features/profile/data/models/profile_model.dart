// lib/features/profile/data/models/profile_model.dart
class Profile {
  final String coopId;
  final String firstName;
  final String middleName;
  final String lastName;
  final String email;
  final String mobileNumber;
  final String streetAddress;
  final String town;
  final String state;
  final String department;
  final String jobPosition;
  final String nokFirstName;
  final String nokMiddleName;
  final String nokLastName;
  final String nokTel;
  final double completionPercentage;

  Profile({
    required this.coopId,
    required this.firstName,
    required this.middleName,
    required this.lastName,
    required this.email,
    required this.mobileNumber,
    required this.streetAddress,
    required this.town,
    required this.state,
    required this.department,
    required this.jobPosition,
   required this.nokFirstName,
    required this.nokMiddleName,
    required this.nokLastName,
    required this.nokTel,
    required this.completionPercentage,
  });

  factory Profile.fromJson(Map<String, dynamic> json) {
    return Profile(
      coopId: json['CoopID'] ?? '',
      firstName: json['FirstName'] ?? '',
      middleName: json['MiddleName'] ?? '',
      lastName: json['LastName'] ?? '',
      email: json['EmailAddress'] ?? '',
      mobileNumber: json['MobileNumber'] ?? '',
      streetAddress: json['StreetAddress'] ?? '',
      town: json['Town'] ?? '',
      state: json['State'] ?? '',
      department: json['Department'] ?? '',
      jobPosition: json['JobPosition'] ?? '',
      nokFirstName: json['nokfirstname'] ?? '',
      nokMiddleName: json['nokmiddlename'] ?? '',
      nokLastName: json['noklastname'] ?? '',
      nokTel: json['noktel'] ?? '',
      completionPercentage: _calculateCompletionPercentage(json),
    );
  }

  static double _calculateCompletionPercentage(Map<String, dynamic> json) {
    int totalFields = 13; // Total number of important fields
    int filledFields = 0;

    if ((json['FirstName'] ?? '').isNotEmpty) filledFields++;
    if ((json['LastName'] ?? '').isNotEmpty) filledFields++;
    if ((json['EmailAddress'] ?? '').isNotEmpty) filledFields++;
    if ((json['MobileNumber'] ?? '').isNotEmpty) filledFields++;
    if ((json['StreetAddress'] ?? '').isNotEmpty) filledFields++;
    if ((json['Town'] ?? '').isNotEmpty) filledFields++;
    if ((json['State'] ?? '').isNotEmpty) filledFields++;
    if ((json['Department'] ?? '').isNotEmpty) filledFields++;
    if ((json['JobPosition'] ?? '').isNotEmpty) filledFields++;
    if ((json['NOKFirstName'] ?? '').isNotEmpty) filledFields++;
    if ((json['NOKLastName'] ?? '').isNotEmpty) filledFields++;
    if ((json['NOKTel'] ?? '').isNotEmpty) filledFields++;

    return (filledFields / totalFields) * 100;
  }
}
