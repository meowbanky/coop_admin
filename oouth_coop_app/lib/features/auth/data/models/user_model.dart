class UserModel {
  final String coopId;
  final String firstName;
  final String lastName;
  final String mobileNumber;

  UserModel({
    required this.coopId,
    required this.firstName,
    required this.lastName,
    required this.mobileNumber,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      coopId: json['CoopID'],
      firstName: json['FirstName'],
      lastName: json['LastName'],
      mobileNumber: json['MobileNumber'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'CoopID': coopId,
      'FirstName': firstName,
      'LastName': lastName,
      'MobileNumber': mobileNumber,
    };
  }
}