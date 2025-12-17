// lib/features/loans/data/services/loan_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/loan_tracker.dart';
import '../../../../config/env.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LoanService {
  static final String baseUrl = Env.apiBaseUrl;

  Future<Map<String, String>> getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    return {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  // In your service class
  Future<LoanTracker> getLoanTracking() async {
    final prefs = await SharedPreferences.getInstance();
    final coopId = prefs.getString('CoopID') ?? '';
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/loans/tracking.php?coopId=$coopId'),
        headers: await getHeaders(),
      );

      if (response.statusCode == 200) {
        final result = json.decode(response.body);
        if (result['success'] && result['data'] != null) {
          return LoanTracker.fromJson(result['data']);
        }
      }
      return LoanTracker.empty();
    } catch (e) {
      // debugPrint('Error fetching loan tracking: $e');
      return LoanTracker.empty();
    }
  }
}
