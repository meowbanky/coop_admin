class NotificationModel {
  final int id;
  final String coopId;
  final String title;
  final String message;
  final String status;
  final DateTime createdAt;
  final DateTime updatedAt;

  NotificationModel({
    required this.id,
    required this.coopId,
    required this.title,
    required this.message,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'],
      coopId: json['coop_id'],
      title: json['title'],
      message: json['message'],
      status: json['status'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}
