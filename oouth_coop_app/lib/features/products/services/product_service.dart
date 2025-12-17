// lib/features/products/data/services/product_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import '../../../../config/env.dart';
import '../../products/data/models/transaction_summary.dart';

class ProductService {
  static final String baseUrl = Env.apiBaseUrl;

  Future<Map<String, String>> getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';
    
    // Debug logging
    debugPrint('ProductService - Token exists: ${token.isNotEmpty}');
    debugPrint('ProductService - Token length: ${token.length}');
    
    if (token.isEmpty) {
      debugPrint('ProductService - ERROR: Token is empty! User may need to login again.');
      throw Exception('Authentication token not found. Please login again.');
    }
    
    return {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> getPeriods() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/transactions/get_periods.php'),
        headers: await getHeaders(),
      );

      final result = json.decode(response.body);
      if (result['success']) {
        await _storePeriodData(result['data']);
      }
      return result;
    } catch (e) {
      debugPrint('Error loading periods: $e');
      return {
        'success': false,
        'message': 'Network or server error occurred',
        'data': []
      };
    }
  }

  Future<void> _storePeriodData(List<dynamic> periodData) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('period_data', json.encode(periodData));
    } catch (e) {
      debugPrint('Error storing period data: $e');
    }
  }

  Future<Map<String, dynamic>> getTransactionSummary(
    String fromPeriod,
    String toPeriod,
  ) async {
    try {
      // Initialize SharedPreferences
      final prefs = await SharedPreferences.getInstance();
      final userData = prefs.getString('user_data');

      if (userData == null) {
        return {
          'success': false,
          'message': 'User data not found',
          'data': [],
        };
      }

      // Decode user data and extract CoopID
      final user = json.decode(userData);
      final coopId = user['CoopID'];

      // Make the API request
      final headers = await getHeaders();
      debugPrint('ProductService - Request URL: $baseUrl/transactions/transaction-summary.php');
      debugPrint('ProductService - Headers: ${headers.keys.toList()}');
      debugPrint('ProductService - Has Authorization header: ${headers.containsKey('Authorization')}');
      
      final response = await http.post(
        Uri.parse('$baseUrl/transactions/transaction-summary.php'),
        headers: headers,
        body: json.encode({
          'coopId': coopId, // Include CoopID in the request body
          'fromPeriod': fromPeriod,
          'toPeriod': toPeriod,
        }),
      );
      
      debugPrint('ProductService - Response status: ${response.statusCode}');
      debugPrint('ProductService - Response body: ${response.body}');

      // Decode the response
      final result = json.decode(response.body);

      // Check if the request was successful
      if (result['success']) {
        await _storeTransactionData(
            result['data']); // Store transaction data if needed
      }

      return result;
    } catch (e) {
      debugPrint('Error fetching transaction summary: $e');
      return {
        'success': false,
        'message': 'Network or server error occurred',
        'data': [],
      };
    }
  }

  Future<void> _storeTransactionData(List<dynamic> transactionData) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('transaction_data', json.encode(transactionData));
    } catch (e) {
      debugPrint('Error storing transaction data: $e');
    }
  }

  Future<List<Map<String, dynamic>>> getCachedPeriods() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final periodData = prefs.getString('period_data');
      if (periodData != null) {
        final List<dynamic> decoded = json.decode(periodData);
        return List<Map<String, dynamic>>.from(decoded);
      }
      return [];
    } catch (e) {
      debugPrint('Error getting cached period data: $e');
      return [];
    }
  }

  Future<List<TransactionSummary>> getCachedTransactions() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final transactionData = prefs.getString('transaction_data');
      if (transactionData != null) {
        final List<dynamic> decoded = json.decode(transactionData);
        return decoded
            .map((item) => TransactionSummary.fromJson(item))
            .toList();
      }
      return [];
    } catch (e) {
      debugPrint('Error getting cached transaction data: $e');
      return [];
    }
  }
}
