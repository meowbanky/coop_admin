class LoanTracker {
  final String loanId;
  final double principalAmount;
  final double totalRepaid;
  final double remainingBalance;
  final double interestPaid;
  final DateTime? startDate;
  final DateTime? lastPaymentDate;
  final double monthlyPayment;
  final int totalMonths;
  final int monthsRemaining;
  final double paymentProgress;

  LoanTracker({
    required this.loanId,
    required this.principalAmount,
    required this.totalRepaid,
    required this.remainingBalance,
    required this.interestPaid,
    this.startDate,
    this.lastPaymentDate,
    required this.monthlyPayment,
    required this.totalMonths,
    required this.monthsRemaining,
    required this.paymentProgress,
  });

  factory LoanTracker.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return LoanTracker.empty();
    }

    // Safe date parsing function
    DateTime? parseDate(dynamic dateStr) {
      if (dateStr == null || dateStr.toString().isEmpty) return null;
      try {
        return DateTime.parse(dateStr.toString());
      } catch (e) {
        // debugPrint('Error parsing date: $dateStr - $e');
        return null;
      }
    }

    return LoanTracker(
      loanId: json['loanId']?.toString() ?? '',
      principalAmount:
          double.tryParse(json['principalAmount']?.toString() ?? '0') ?? 0,
      totalRepaid: double.tryParse(json['totalRepaid']?.toString() ?? '0') ?? 0,
      remainingBalance:
          double.tryParse(json['remainingBalance']?.toString() ?? '0') ?? 0,
      interestPaid:
          double.tryParse(json['interestPaid']?.toString() ?? '0') ?? 0,
      startDate: parseDate(json['startDate']),
      lastPaymentDate: parseDate(json['lastPaymentDate']),
      monthlyPayment:
          double.tryParse(json['monthlyPayment']?.toString() ?? '0') ?? 0,
      totalMonths: int.tryParse(json['totalMonths']?.toString() ?? '0') ?? 0,
      monthsRemaining:
          int.tryParse(json['monthsRemaining']?.toString() ?? '0') ?? 0,
      paymentProgress:
          double.tryParse(json['paymentProgress']?.toString() ?? '0') ?? 0,
    );
  }

  factory LoanTracker.empty() {
    return LoanTracker(
      loanId: '',
      principalAmount: 0,
      totalRepaid: 0,
      remainingBalance: 0,
      interestPaid: 0,
      startDate: null,
      lastPaymentDate: null,
      monthlyPayment: 0,
      totalMonths: 0,
      monthsRemaining: 0,
      paymentProgress: 0,
    );
  }

  // Helper method to format dates for display
  String formatDate(DateTime? date) {
    if (date == null) return 'N/A';
    return '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  // Getters for formatted dates
  String get formattedStartDate => formatDate(startDate);
  String get formattedLastPaymentDate => formatDate(lastPaymentDate);
}
