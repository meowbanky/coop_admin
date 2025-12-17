import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import '../models/event_model.dart';
import '../../../../config/env.dart';
import '../../../../services/device_id_service.dart';

class EventService {
  static final String baseUrl = Env.apiBaseUrl;

  Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token');
  }

  Future<List<EventModel>> getEvents({String filter = 'upcoming'}) async {
    try {
      final token = await _getToken();
      if (token == null) {
        throw Exception('Authentication required');
      }

      final response = await http.get(
        Uri.parse('$baseUrl/events/list.php?filter=$filter'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );

      debugPrint('Events API response status: ${response.statusCode}');
      debugPrint('Events API response body: ${response.body}');

      if (response.statusCode == 200) {
        final result = json.decode(response.body);
        if (result['success'] == true) {
          final List<dynamic> data = result['data'] ?? [];
          return data.map((json) => EventModel.fromJson(json)).toList();
        } else {
          throw Exception(result['message'] ?? 'Failed to load events');
        }
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      debugPrint('Error fetching events: $e');
      rethrow;
    }
  }

  Future<EventModel> getEventDetails(int eventId) async {
    try {
      final token = await _getToken();
      if (token == null) {
        throw Exception('Authentication required');
      }

      final response = await http.get(
        Uri.parse('$baseUrl/events/details.php?id=$eventId'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );

      debugPrint('Event details API response status: ${response.statusCode}');
      debugPrint('Event details API response body: ${response.body}');

      if (response.statusCode == 200) {
        final result = json.decode(response.body);
        if (result['success'] == true) {
          return EventModel.fromJson(result['data']);
        } else {
          throw Exception(result['message'] ?? 'Failed to load event details');
        }
      } else {
        throw Exception('HTTP ${response.statusCode}: ${response.body}');
      }
    } catch (e) {
      debugPrint('Error fetching event details: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> checkIn({
    required int eventId,
    required double latitude,
    required double longitude,
  }) async {
    try {
      final token = await _getToken();
      if (token == null) {
        throw Exception('Authentication required');
      }

      // Get device ID
      final deviceId = await DeviceIdService.getDeviceId();

      final response = await http.post(
        Uri.parse('$baseUrl/events/checkin.php'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'event_id': eventId,
          'latitude': latitude,
          'longitude': longitude,
          'device_id': deviceId,
        }),
      );

      debugPrint('Check-in API response status: ${response.statusCode}');
      debugPrint('Check-in API response body: ${response.body}');

      final result = json.decode(response.body);
      
      if (response.statusCode == 200 && result['success'] == true) {
        return {
          'success': true,
          'message': result['message'] ?? 'Check-in successful',
          'data': result['data'],
        };
      } else {
        return {
          'success': false,
          'message': result['message'] ?? 'Check-in failed',
          'distance': result['distance'],
          'within_range': result['within_range'] ?? false,
        };
      }
    } catch (e) {
      debugPrint('Error checking in: $e');
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }
}

