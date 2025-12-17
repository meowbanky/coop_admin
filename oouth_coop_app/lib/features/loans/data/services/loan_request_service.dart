// lib/features/loans/data/services/loan_request_service.dart
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../config/env.dart';
import '../models/loan_request_model.dart';
import 'package:flutter/foundation.dart';

class LoanRequestService {
  static final String baseUrl = Env.apiBaseUrl;

  Future<Map<String, String>> getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    return {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  Future<List<Map<String, dynamic>>> getSalaryPeriods() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/salary/periods.php'),
        headers: await getHeaders(),
      );

      if (response.statusCode == 200) {
        final result = json.decode(response.body);
        if (result['success'] && result['data'] != null) {
          return List<Map<String, dynamic>>.from(result['data']);
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching salary periods: $e');
      return [];
    }
  }

  Future<List<MemberSearchResult>> searchMembers(String query) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/members/search.php?query=${Uri.encodeComponent(query)}'),
        headers: await getHeaders(),
      );

      if (response.statusCode == 200) {
        final result = json.decode(response.body);
        if (result['success'] && result['data'] != null) {
          return (result['data'] as List)
              .map((member) => MemberSearchResult.fromJson(member))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Search members error: $e');
      return [];
    }
  }

  Future<SalaryValidationResult?> validateSalary({
    int? staffId,
    String? coopId,
    int? deductionId,
    int? periodId,
    required double monthlyRepayment,
  }) async {
    try {
      // Build query parameters
      final queryParams = <String, String>{
        'monthly_repayment': monthlyRepayment.toString(),
      };
      
      if (staffId != null) {
        queryParams['staff_id'] = staffId.toString();
      }
      
      if (coopId != null) {
        queryParams['coop_id'] = coopId;
      }
      
      if (deductionId != null) {
        queryParams['deduction_id'] = deductionId.toString();
      }
      
      if (periodId != null) {
        queryParams['period'] = periodId.toString();
      }
      
      // Build URI with query parameters
      final uri = Uri.parse('$baseUrl/salary/validation.php').replace(
        queryParameters: queryParams,
      );
      
      final response = await http.get(
        uri,
        headers: await getHeaders(),
      );

      debugPrint('Salary validation - Status: ${response.statusCode}');
      debugPrint('Salary validation - Response body: ${response.body}');

      if (response.statusCode == 200) {
        // Check if response is valid JSON
        final responseBody = response.body.trim();
        if (responseBody.startsWith('<')) {
          debugPrint('Salary validation - ERROR: Received HTML instead of JSON');
          debugPrint('Salary validation - HTML content: $responseBody');
          throw Exception('Invalid response format from server. Please try again.');
        }
        
        final result = json.decode(responseBody);
        if (result['success'] && result['data'] != null) {
          return SalaryValidationResult.fromJson(result['data']);
        } else {
          debugPrint('Salary validation - API returned success=false: ${result['message']}');
          throw Exception(result['message'] ?? 'Validation failed');
        }
      } else {
        // Try to parse error message
        try {
          final errorResult = json.decode(response.body);
          throw Exception(errorResult['message'] ?? 'Validation request failed');
        } catch (_) {
          throw Exception('Server error (${response.statusCode}). Please try again.');
        }
      }
    } catch (e) {
      debugPrint('Salary validation error: $e');
      rethrow; // Re-throw so the UI can handle it
    }
  }

  Future<String?> uploadPayslip(File payslipFile) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token') ?? '';

      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/loans/upload_payslip.php'),
      );

      request.headers['Authorization'] = 'Bearer $token';
      request.files.add(
        await http.MultipartFile.fromPath('payslip', payslipFile.path),
      );

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        final result = json.decode(response.body);
        if (result['success'] && result['data'] != null) {
          return result['data']['file_path'] as String;
        }
      }
      return null;
    } catch (e) {
      debugPrint('Payslip upload error: $e');
      return null;
    }
  }

  Future<LoanRequest?> createLoanRequest({
    required String requesterCoopId,
    required double requestedAmount,
    required int? staffId,
    required int? deductionId,
    required int? periodId,
    String? payslipFilePath,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/loans/request.php'),
        headers: await getHeaders(),
        body: json.encode({
          'requester_coop_id': requesterCoopId,
          'requested_amount': requestedAmount,
          'staff_id': staffId,
          'deduction_id': deductionId,
          'period_id': periodId,
          'payslip_file_path': payslipFilePath,
        }),
      );

      debugPrint('Create loan request - Status: ${response.statusCode}');
      debugPrint('Create loan request - Response body: ${response.body}');

      // Check if response is valid JSON
      final responseBody = response.body.trim();
      if (responseBody.startsWith('<')) {
        debugPrint('Create loan request - ERROR: Received HTML instead of JSON');
        debugPrint('Create loan request - HTML content: $responseBody');
        throw Exception('Invalid response format from server. Please try again.');
      }

      final result = json.decode(responseBody);

      if (response.statusCode == 200) {
        if (result['success'] && result['data'] != null) {
          // Parse ID - handle both int and String types
          final id = result['data']['id'];
          final loanRequestId = id is int 
              ? id 
              : id is String 
                  ? int.tryParse(id) 
                  : null;
          
          if (loanRequestId != null) {
            // Fetch the full loan request details
            return await getLoanRequestById(loanRequestId);
          } else {
            // If ID parsing fails, try to parse the full data directly
            try {
              return LoanRequest.fromJson(result['data']);
            } catch (e) {
              debugPrint('Failed to parse loan request: $e');
              throw Exception('Failed to parse loan request response');
            }
          }
        } else {
          // API returned success=false
          final errorMessage = result['message'] ?? 'Failed to create loan request';
          throw Exception(errorMessage);
        }
      } else {
        // Error status code - try to parse error message
        final errorMessage = result['message'] ?? 'Failed to create loan request (${response.statusCode})';
        throw Exception(errorMessage);
      }
    } catch (e) {
      debugPrint('Create loan request error: $e');
      rethrow; // Re-throw so the UI can display the actual error message
    }
  }

  Future<List<LoanRequest>> getLoanRequests(String requesterCoopId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/loans/request.php?requester_coop_id=$requesterCoopId'),
        headers: await getHeaders(),
      );

      if (response.statusCode == 200) {
        final result = json.decode(response.body);
        if (result['success'] && result['data'] != null) {
          return (result['data'] as List)
              .map((loan) => LoanRequest.fromJson(loan))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Get loan requests error: $e');
      return [];
    }
  }

  Future<LoanRequest?> getLoanRequestById(int loanRequestId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final coopId = prefs.getString('CoopID') ?? '';
      
      final loanRequests = await getLoanRequests(coopId);
      return loanRequests.firstWhere(
        (loan) => loan.id == loanRequestId,
        orElse: () => loanRequests.first,
      );
    } catch (e) {
      debugPrint('Get loan request by ID error: $e');
      return null;
    }
  }

  Future<bool> sendGuarantorRequest({
    required int loanRequestId,
    required String guarantorCoopId,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/loans/guarantor-request.php'),
        headers: await getHeaders(),
        body: json.encode({
          'loan_request_id': loanRequestId,
          'guarantor_coop_id': guarantorCoopId,
        }),
      );

      // Check if response is valid JSON (not HTML)
      final responseBody = response.body.trim();
      if (responseBody.startsWith('<')) {
        debugPrint('Send guarantor request - ERROR: Received HTML instead of JSON');
        debugPrint('Send guarantor request - HTML content: $responseBody');
        return false;
      }

      if (response.statusCode == 200) {
        final result = json.decode(responseBody);
        return result['success'] == true;
      }
      
      // Try to parse error message
      try {
        final errorResult = json.decode(responseBody);
        debugPrint('Send guarantor request error: ${errorResult['message'] ?? 'Unknown error'}');
      } catch (_) {
        debugPrint('Send guarantor request error: HTTP ${response.statusCode}');
      }
      
      return false;
    } catch (e) {
      debugPrint('Send guarantor request error: $e');
      return false;
    }
  }

  Future<List<GuarantorRequest>> getGuarantorRequests({
    String? guarantorCoopId,
    int? loanRequestId,
  }) async {
    try {
      String url = '$baseUrl/loans/guarantor-request.php?';
      if (guarantorCoopId != null) {
        url += 'guarantor_coop_id=$guarantorCoopId';
      } else if (loanRequestId != null) {
        url += 'loan_request_id=$loanRequestId';
      } else {
        return [];
      }

      final response = await http.get(
        Uri.parse(url),
        headers: await getHeaders(),
      );

      if (response.statusCode == 200) {
        final result = json.decode(response.body);
        if (result['success'] && result['data'] != null) {
          return (result['data'] as List)
              .map((gr) => GuarantorRequest.fromJson(gr))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Get guarantor requests error: $e');
      return [];
    }
  }

  Future<bool> respondToGuarantorRequest({
    required int guarantorRequestId,
    required String response, // 'approved' or 'rejected'
    String? responseNotes,
  }) async {
    try {
      final responseBody = await http.put(
        Uri.parse('$baseUrl/loans/guarantor-request.php'),
        headers: await getHeaders(),
        body: json.encode({
          'guarantor_request_id': guarantorRequestId,
          'response': response,
          'response_notes': responseNotes,
        }),
      );

      if (responseBody.statusCode == 200) {
        final result = json.decode(responseBody.body);
        return result['success'] == true;
      }
      return false;
    } catch (e) {
      debugPrint('Respond to guarantor request error: $e');
      return false;
    }
  }
}

