// lib/features/wallet/data/services/wallet_service.dart
import '../../../../config/env.dart';
import 'package:http/http.dart' as http;
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../features/wallet/data/models/wallet_data.dart';

class WalletService {
  final String baseUrl = Env.apiBaseUrl;

  Future<WalletData> getWalletData({bool refresh = false}) async {
    final prefs = await SharedPreferences.getInstance();

    if (!refresh) {
      final storedData = prefs.getString('wallet_data');
      if (storedData != null) {
        final walletData = json.decode(storedData);
        return WalletData(
          totalBalance:
              (num.parse(walletData['total_balance'].toString())).toDouble(),
          sharesBalance:
              (num.parse(walletData['shares_balance'].toString())).toDouble(),
          savingsBalance:
              (num.parse(walletData['savings_balance'].toString())).toDouble(),
          unpaidLoan:
              (num.parse(walletData['unpaid_loan'].toString())).toDouble(),
        );
      }
    }

    final token = prefs.getString('token');
    final response = await http.get(
      Uri.parse('$baseUrl/auth/get_wallet_data.php'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final result = json.decode(response.body);
      if (result['success']) {
        await prefs.setString('wallet_data', json.encode(result['data']));
        return WalletData.fromJson(result['data']);
      }
      throw Exception(result['message']);
    }
    throw Exception('Failed to load wallet data');
  }

  Future<String> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token') ?? '';
  }
}
