import 'dart:convert';
import 'package:http/http.dart' as http;
import '../../../../config/env.dart';
import '../models/notification_model.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';

class NotificationService {
  final String baseUrl = Env.apiBaseUrl;

  Future<String?> _getCoopId() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userData = prefs.getString('user_data');
      debugPrint('User data from prefs: $userData');

      if (userData != null) {
        final userMap = json.decode(userData);
        final coopId = userMap['CoopID'];
        debugPrint('CoopID extracted: $coopId');
        return coopId;
      }
      return null;
    } catch (e) {
      debugPrint('Error getting CoopID: $e');
      return null;
    }
  }

  Future<List<NotificationModel>> getNotifications() async {
    final prefs = await SharedPreferences.getInstance();
    final coopId = prefs.getString('CoopID');

    if (coopId == null) {
      throw Exception('CoopID not found');
    }

    debugPrint('Fetching notifications for CoopID: $coopId'); // Debug log

    final response = await http.get(
      Uri.parse('$baseUrl/auth/notifications.php?coop_id=$coopId'),
      headers: {
        'Authorization': 'Bearer ${await _getToken()}',
      },
    );

    debugPrint('Response status: ${response.statusCode}'); // Debug log
    debugPrint('Response body: ${response.body}'); // Debug log

    if (response.statusCode == 200) {
      final result = json.decode(response.body);
      if (result['success']) {
        return (result['data'] as List)
            .map((item) => NotificationModel.fromJson(item))
            .toList();
      }
      throw Exception(result['message']);
    }
    throw Exception('Failed to load notifications');
  }

  Future<int> getUnreadCount() async {
    final prefs = await SharedPreferences.getInstance();
    final coopId = prefs.getString('CoopID');
    final token = await _getToken();

    // Debug logging
    debugPrint('NotificationService - Token exists: ${token.isNotEmpty}');
    debugPrint('NotificationService - Token length: ${token.length}');
    debugPrint('NotificationService - CoopID: $coopId');
    
    if (token.isEmpty) {
      debugPrint('NotificationService - ERROR: Token is empty! User may need to login again.');
      throw Exception('Authentication token not found. Please login again.');
    }
    
    if (coopId == null || coopId.isEmpty) {
      debugPrint('NotificationService - ERROR: CoopID is empty!');
      throw Exception('User ID not found. Please login again.');
    }

    final headers = {
      'Authorization': 'Bearer $token',
    };
    
    debugPrint('NotificationService - Request URL: $baseUrl/auth/notifications.php?coop_id=$coopId&unread-count=true&count=true');
    debugPrint('NotificationService - Headers: ${headers.keys.toList()}');
    debugPrint('NotificationService - Has Authorization header: ${headers.containsKey('Authorization')}');

    final response = await http.get(
      Uri.parse(
          '$baseUrl/auth/notifications.php?coop_id=$coopId&unread-count=true&count=true'),
      headers: headers,
    );

    debugPrint('NotificationService - Response status: ${response.statusCode}');
    debugPrint('NotificationService - Response body: ${response.body}');

    if (response.statusCode == 200) {
      final result = json.decode(response.body);
      if (result['success']) {
        return result['count'];
      }
      throw Exception(result['message']);
    }
    throw Exception('Failed to get unread count');
  }

  Future<void> markAsRead(int notificationId) async {
    final response = await http.put(
      Uri.parse('$baseUrl/auth/notifications.php/$notificationId/read'),
      headers: {
        'Authorization': 'Bearer ${await _getToken()}',
      },
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to mark notification as read');
    }
  }

  Future<String> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token') ?? '';
  }
}
