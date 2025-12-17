// lib/features/profile/data/services/profile_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import '../../../../config/env.dart';
import '../models/profile_model.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ProfileService {
  static final String baseUrl = Env.apiBaseUrl;

  Future<Map<String, String>> getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';
    return {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> updateProfile(
      Map<String, dynamic> updateData) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final coopId = prefs.getString('CoopID') ?? '';

      // Add coopId to the update data
      updateData['coop_id'] = coopId;

      final response = await http.post(
        Uri.parse('$baseUrl/profile/update_profile.php'),
        headers: await getHeaders(),
        body: json.encode(updateData),
      );
      final result = json.decode(response.body);
      return result;
    } catch (e) {
      debugPrint('Error updating profile: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> getBanksList() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/profile/get_banks_list.php'),
        headers: await getHeaders(),
      );
      final result = json.decode(response.body);
      return result;
    } catch (e) {
      debugPrint('Error getting banks list: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> getBankAccount() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final coopId = prefs.getString('CoopID') ?? '';

      final response = await http.get(
        Uri.parse('$baseUrl/profile/get_bank_account.php?coop_id=$coopId'),
        headers: await getHeaders(),
      );
      final result = json.decode(response.body);
      return result;
    } catch (e) {
      debugPrint('Error getting bank account: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> updateBankAccount(
      Map<String, dynamic> bankData) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final coopId = prefs.getString('CoopID') ?? '';

      // Add coopId to the bank data
      bankData['coop_id'] = coopId;

      final response = await http.post(
        Uri.parse('$baseUrl/profile/update_bank_account.php'),
        headers: await getHeaders(),
        body: json.encode(bankData),
      );
      final result = json.decode(response.body);
      return result;
    } catch (e) {
      debugPrint('Error updating bank account: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> changePassword(
      String currentPassword, String newPassword) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final coopId = prefs.getString('CoopID') ?? '';

      final response = await http.post(
        Uri.parse('$baseUrl/profile/change_password.php'),
        headers: await getHeaders(),
        body: json.encode({
          'coop_id': coopId,
          'current_password': currentPassword,
          'new_password': newPassword,
        }),
      );
      final result = json.decode(response.body);
      return result;
    } catch (e) {
      debugPrint('Error changing password: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }

  Future<Map<String, dynamic>> updateEmergencyContact(
      Map<String, dynamic> contactData) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final coopId = prefs.getString('CoopID') ?? '';

      // Add coopId to the contact data
      contactData['coop_id'] = coopId;

      final response = await http.post(
        Uri.parse('$baseUrl/profile/update_emergency_contact.php'),
        headers: await getHeaders(),
        body: json.encode(contactData),
      );
      final result = json.decode(response.body);
      return result;
    } catch (e) {
      debugPrint('Error updating emergency contact: $e');
      return {'success': false, 'message': 'Network or server error occurred'};
    }
  }
}
