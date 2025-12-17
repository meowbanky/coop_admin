import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/user_model.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../config/env.dart';
import 'package:flutter/foundation.dart';
import 'package:onesignal_flutter/onesignal_flutter.dart';
import '../models/user_search.dart';

class AuthService {
  static final String baseUrl = Env.apiBaseUrl;
  static final String appId = Env.oneSignalAppId;

  Future<Map<String, dynamic>> login(String username, String password) async {
    debugPrint('Environment: ${kIsWeb ? 'Web' : 'Mobile'}');
    debugPrint('Base URL: $baseUrl');
    debugPrint('Full Endpoint: $baseUrl/auth/login.php');
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/login.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'username': username,
          'password': password,
        }),
      );

      print('Request Body: ${json.encode({
            'username': username,
            'password': password,
          })}');
      print('Response Status: ${response.statusCode}');
      print('Response Body: ${response.body}');

      // Check if response is valid JSON
      if (response.body.isEmpty || !response.body.trim().startsWith('{')) {
        debugPrint('Invalid JSON response received');
        return {
          'success': false,
          'message': 'Invalid response from server. Please try again.'
        };
      }

      Map<String, dynamic> result;
      try {
        result = json.decode(response.body) as Map<String, dynamic>;
      } catch (e) {
        debugPrint('JSON decode error: $e');
        debugPrint('Response body: ${response.body}');
        return {
          'success': false,
          'message': 'Failed to parse server response. Please try again.'
        };
      }

      if (result['success']) {
        final prefs = await SharedPreferences.getInstance();
        final userData = result['user'];

        // Store all user data
        await prefs.setString('token', result['token']);
        await prefs.setString('user_data', json.encode(userData));
        await prefs.setString('wallet_data', json.encode(result['wallet']));

        // Store individual fields for easier access
        await prefs.setString('CoopID', userData['CoopID'] ?? '');
        await prefs.setString('FirstName', userData['FirstName'] ?? '');
        await prefs.setString('LastName', userData['LastName'] ?? '');
        await prefs.setString('EmailAddress', userData['EmailAddress'] ?? '');
        await prefs.setString('MobileNumber', userData['MobileNumber'] ?? '');
        await prefs.setString('StreetAddress', userData['StreetAddress'] ?? '');
        await prefs.setString('Town', userData['Town'] ?? '');
        await prefs.setString('State', userData['State'] ?? '');

        // Store emergency contact information
        await prefs.setString(
            'nok_first_name', userData['nok_first_name'] ?? '');
        await prefs.setString(
            'nok_middle_name', userData['nok_middle_name'] ?? '');
        await prefs.setString('nok_last_name', userData['nok_last_name'] ?? '');
        await prefs.setString('nok_tel', userData['nok_tel'] ?? '');

        // Handle OneSignal ID
        final oneSignalId = await _getOneSignalId();
        if (oneSignalId != null) {
          await storeOneSignalId(oneSignalId, userData['CoopID']);
        }
      }

      return result;
    } catch (e) {
      debugPrint('Login error: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> forgotPassword(String email) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/forgot-password.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'email': email}),
      );

      return json.decode(response.body);
    } catch (e) {
      debugPrint('Forgot password error: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> getUserData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      return {
        'CoopID': prefs.getString('CoopID'),
        'FirstName': prefs.getString('FirstName'),
        'LastName': prefs.getString('LastName'),
        'EmailAddress': prefs.getString('EmailAddress'),
        'MobileNumber': prefs.getString('MobileNumber'),
      };
    } catch (e) {
      debugPrint('Error getting user data: $e');
      return {};
    }
  }

  Future<String?> _getOneSignalId() async {
    return OneSignal.User.pushSubscription.id;
  }

  Future<bool> storeOneSignalId(String oneSignalId, String coopId) async {
    try {
      final response = await http.post(
        Uri.parse('${Env.apiBaseUrl}/auth/update_device_id.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'onesignal_id': oneSignalId, 'coop_id': coopId}),
      );

      if (response.statusCode == 200) {
        final result = json.decode(response.body);
        return result['success'] ?? false;
      } else {
        debugPrint(
            'Failed to store OneSignal ID. Status: ${response.statusCode}');
        debugPrint('Response: ${response.body}');
        return false;
      }
    } catch (e) {
      debugPrint('Error storing OneSignal ID: $e');
      return false;
    }
  }

  Future<bool> logout() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      return true;
    } catch (e) {
      debugPrint('Error during logout: $e');
      return false;
    }
  }

  // lib/features/auth/data/services/auth_service.dart
  Future<List<UserSearch>> searchUsers(String query) async {
    try {
      // URL encode the query to handle special characters and spaces
      final encodedQuery = Uri.encodeComponent(query.trim());
      final url = Uri.parse('$baseUrl/auth/search_users.php?query=$encodedQuery');
      
      debugPrint('Searching users with query: $query');
      debugPrint('Search URL: $url');
      
      final response = await http.get(
        url,
        headers: {'Content-Type': 'application/json'},
      );

      debugPrint('Search response status: ${response.statusCode}');
      debugPrint('Search response body: ${response.body}');

      final result = json.decode(response.body);
      if (result['success'] == true && result['data'] != null) {
        final users = (result['data'] as List)
            .map((user) => UserSearch.fromJson(user))
            .toList();
        debugPrint('Found ${users.length} users');
        return users;
      }
      debugPrint('No users found or success is false');
      return [];
    } catch (e) {
      debugPrint('Search users error: $e');
      return [];
    }
  }

  Future<Map<String, dynamic>> requestOTP(String email) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/request_otp.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'email': email}),
      );

      return json.decode(response.body);
    } catch (e) {
      debugPrint('Request OTP error: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> verifyOTP(String email, String otp) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/verify_otp.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email,
          'otp': otp,
        }),
      );

      return json.decode(response.body);
    } catch (e) {
      debugPrint('Verify OTP error: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> verifySignupOTP(String email, String otp) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/verify_signup_otp.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email,
          'otp': otp,
        }),
      );

      return json.decode(response.body);
    } catch (e) {
      debugPrint('Verify OTP error: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> checkUserEmail(String coopId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/auth/check_email.php?coopId=$coopId'),
        headers: {'Content-Type': 'application/json'},
      );

      return json.decode(response.body);
    } catch (e) {
      debugPrint('Check email error: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> sendSignupOTP(String email) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/send_signup_otp.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'email': email}),
      );

      return json.decode(response.body);
    } catch (e) {
      debugPrint('Send OTP error: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> createAccount(
      String coopId, String email, String otp, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/create_account.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'coopId': coopId,
          'email': email,
          'otp': otp,
          'password': password,
        }),
      );

      return json.decode(response.body);
    } catch (e) {
      debugPrint('Create account error: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> resetPassword(
      String email, String otp, String newPassword) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/reset_password.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email,
          'otp': otp,
          'new_password': newPassword,
        }),
      );

      return json.decode(response.body);
    } catch (e) {
      debugPrint('Reset password error: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }
}
