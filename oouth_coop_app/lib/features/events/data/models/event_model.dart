class EventModel {
  final int id;
  final String title;
  final String? description;
  final DateTime startTime;
  final DateTime endTime;
  final double locationLat;
  final double locationLng;
  final int geofenceRadius;
  final String status; // 'upcoming', 'active', 'past'
  final bool hasCheckedIn;
  final DateTime? createdAt;

  EventModel({
    required this.id,
    required this.title,
    this.description,
    required this.startTime,
    required this.endTime,
    required this.locationLat,
    required this.locationLng,
    required this.geofenceRadius,
    required this.status,
    required this.hasCheckedIn,
    this.createdAt,
  });

  factory EventModel.fromJson(Map<String, dynamic> json) {
    return EventModel(
      id: json['id'] as int,
      title: json['title'] as String,
      description: json['description'] as String?,
      startTime: DateTime.parse(json['start_time'] as String),
      endTime: DateTime.parse(json['end_time'] as String),
      locationLat: (json['location_lat'] as num).toDouble(),
      locationLng: (json['location_lng'] as num).toDouble(),
      geofenceRadius: json['geofence_radius'] as int,
      status: json['status'] as String,
      hasCheckedIn: json['has_checked_in'] as bool? ?? false,
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'] as String)
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'description': description,
      'start_time': startTime.toIso8601String(),
      'end_time': endTime.toIso8601String(),
      'location_lat': locationLat,
      'location_lng': locationLng,
      'geofence_radius': geofenceRadius,
      'status': status,
      'has_checked_in': hasCheckedIn,
      'created_at': createdAt?.toIso8601String(),
    };
  }

  bool get isActive {
    final now = DateTime.now();
    return now.isAfter(startTime) && now.isBefore(endTime);
  }

  bool get isUpcoming {
    return DateTime.now().isBefore(startTime);
  }

  bool get isPast {
    return DateTime.now().isAfter(endTime);
  }
}

