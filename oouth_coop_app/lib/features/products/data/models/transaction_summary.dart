// lib/features/products/data/models/transaction_summary.dart
class TransactionSummary {
  final String payrollPeriod;
  final double devLevy;
  final double stationery;
  final double savingsAmount;
  final double savingsBalance;
  final double sharesAmount;
  final double sharesBalance;
  final double interestPaid;
  final double commodity;
  final double commodityRepayment;
  final double commodityBalance;
  final double loan;
  final double loanRepayment;
  final double loanBalance;
  final double total;

  TransactionSummary({
    required this.payrollPeriod,
    required this.devLevy,
    required this.stationery,
    required this.savingsAmount,
    required this.savingsBalance,
    required this.sharesAmount,
    required this.sharesBalance,
    required this.interestPaid,
    required this.commodity,
    required this.commodityRepayment,
    required this.commodityBalance,
    required this.loan,
    required this.loanRepayment,
    required this.loanBalance,
    required this.total,
  });

  factory TransactionSummary.fromJson(Map<String, dynamic> json) {
    return TransactionSummary(
      payrollPeriod: json['PayrollPeriod'] ?? '',
      devLevy: double.parse((json['devLevy'] ?? '0').toString()),
      stationery: double.parse((json['Stationery'] ?? '0').toString()),
      savingsAmount: double.parse((json['savingsAmount'] ?? '0').toString()),
      savingsBalance: double.parse((json['savingsBalance'] ?? '0').toString()),
      sharesAmount: double.parse((json['sharesAmount'] ?? '0').toString()),
      sharesBalance: double.parse((json['sharesBalance'] ?? '0').toString()),
      interestPaid: double.parse((json['InterestPaid'] ?? '0').toString()),
      commodity: double.parse((json['Commodity'] ?? '0').toString()),
      commodityRepayment:
          double.parse((json['CommodityRepayment'] ?? '0').toString()),
      commodityBalance:
          double.parse((json['CommodityBalance'] ?? '0').toString()),
      loan: double.parse((json['loan'] ?? '0').toString()),
      loanRepayment: double.parse((json['loanRepayment'] ?? '0').toString()),
      loanBalance: double.parse((json['loanBalance'] ?? '0').toString()),
      total: double.parse((json['total'] ?? '0').toString()),
    );
  }
}
