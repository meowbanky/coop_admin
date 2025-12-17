// lib/features/auth/data/models/user_search.dart
class UserSearch {
  final String coopId;
  final String fullName;
  final String email;

  UserSearch({
    required this.coopId,
    required this.fullName,
    required this.email,
  });

  factory UserSearch.fromJson(Map<String, dynamic> json) {
    return UserSearch(
      coopId: json['CoopID'] ?? '',
      fullName: '${json['FirstName'] ?? ''} ${json['LastName'] ?? ''}'.trim(),
      email: json['EmailAddress'] ?? '',
    );
  }
}
