// lib/features/wallet/data/models/wallet_data.dart
class WalletData {
  final double totalBalance;
  final double sharesBalance;
  final double unpaidLoan;
  final double savingsBalance;

  WalletData({
    required this.totalBalance,
    required this.sharesBalance,
    required this.unpaidLoan,
    required this.savingsBalance,
  });

  factory WalletData.fromJson(Map<String, dynamic> json) {
    return WalletData(
      totalBalance: (json['total_balance'] as num).toDouble(),
      sharesBalance: (json['shares_balance'] as num).toDouble(),
      savingsBalance: (json['savings_balance'] as num).toDouble(),
      unpaidLoan: (json['unpaid_loan'] as num).toDouble(),
    );
  }
}
