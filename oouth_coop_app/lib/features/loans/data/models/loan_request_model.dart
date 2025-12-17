// lib/features/loans/data/models/loan_request_model.dart
class LoanRequest {
  final int? id;
  final String requesterCoopId;
  final double requestedAmount;
  final double monthlyRepayment;
  final String? payslipFilePath;
  final String status;
  final DateTime createdAt;
  final DateTime? updatedAt;
  final DateTime? submittedAt;
  final int approvedGuarantors;
  final int rejectedGuarantors;
  final int totalGuarantors;
  final int? periodId;

  LoanRequest({
    this.id,
    required this.requesterCoopId,
    required this.requestedAmount,
    required this.monthlyRepayment,
    this.payslipFilePath,
    required this.status,
    required this.createdAt,
    this.updatedAt,
    this.submittedAt,
    this.approvedGuarantors = 0,
    this.rejectedGuarantors = 0,
    this.totalGuarantors = 0,
    this.periodId,
  });

  factory LoanRequest.fromJson(Map<String, dynamic> json) {
    return LoanRequest(
      id: json['id'] is int 
          ? json['id'] as int?
          : json['id'] is String 
              ? int.tryParse(json['id'] as String)
              : null,
      requesterCoopId: json['requester_coop_id'] as String,
      requestedAmount: (json['requested_amount'] as num).toDouble(),
      monthlyRepayment: (json['monthly_repayment'] as num).toDouble(),
      payslipFilePath: json['payslip_file_path'] as String?,
      status: json['status'] as String,
      createdAt: DateTime.parse(json['created_at'] as String),
      updatedAt: json['updated_at'] != null
          ? DateTime.parse(json['updated_at'] as String)
          : null,
      submittedAt: json['submitted_at'] != null
          ? DateTime.parse(json['submitted_at'] as String)
          : null,
      approvedGuarantors: json['approved_guarantors'] is int
          ? json['approved_guarantors'] as int
          : json['approved_guarantors'] is String
              ? int.tryParse(json['approved_guarantors'] as String) ?? 0
              : (json['approved_guarantors'] as num?)?.toInt() ?? 0,
      rejectedGuarantors: json['rejected_guarantors'] is int
          ? json['rejected_guarantors'] as int
          : json['rejected_guarantors'] is String
              ? int.tryParse(json['rejected_guarantors'] as String) ?? 0
              : (json['rejected_guarantors'] as num?)?.toInt() ?? 0,
      totalGuarantors: json['total_guarantors'] is int
          ? json['total_guarantors'] as int
          : json['total_guarantors'] is String
              ? int.tryParse(json['total_guarantors'] as String) ?? 0
              : (json['total_guarantors'] as num?)?.toInt() ?? 0,
      periodId: json['period_id'] is int
          ? json['period_id'] as int
          : json['period_id'] is String
              ? int.tryParse(json['period_id'] as String)
              : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'requester_coop_id': requesterCoopId,
      'requested_amount': requestedAmount,
      'monthly_repayment': monthlyRepayment,
      'payslip_file_path': payslipFilePath,
      'status': status,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
      'submitted_at': submittedAt?.toIso8601String(),
      'approved_guarantors': approvedGuarantors,
      'rejected_guarantors': rejectedGuarantors,
      'total_guarantors': totalGuarantors,
    };
  }
}

class GuarantorRequest {
  final int? id;
  final int loanRequestId;
  final String guarantorCoopId;
  final String requesterName;
  final double requestedAmount;
  final double monthlyRepayment;
  final String status;
  final DateTime? responseDate;
  final String? responseNotes;
  final DateTime createdAt;
  final DateTime? updatedAt;
  final String? loanStatus;

  GuarantorRequest({
    this.id,
    required this.loanRequestId,
    required this.guarantorCoopId,
    required this.requesterName,
    required this.requestedAmount,
    required this.monthlyRepayment,
    required this.status,
    this.responseDate,
    this.responseNotes,
    required this.createdAt,
    this.updatedAt,
    this.loanStatus,
  });

  factory GuarantorRequest.fromJson(Map<String, dynamic> json) {
    return GuarantorRequest(
      id: json['id'] as int?,
      loanRequestId: json['loan_request_id'] as int,
      guarantorCoopId: json['guarantor_coop_id'] as String,
      requesterName: json['requester_name'] as String,
      requestedAmount: (json['requested_amount'] as num).toDouble(),
      monthlyRepayment: (json['monthly_repayment'] as num).toDouble(),
      status: json['status'] as String,
      responseDate: json['response_date'] != null
          ? DateTime.parse(json['response_date'] as String)
          : null,
      responseNotes: json['response_notes'] as String?,
      createdAt: DateTime.parse(json['created_at'] as String),
      updatedAt: json['updated_at'] != null
          ? DateTime.parse(json['updated_at'] as String)
          : null,
      loanStatus: json['loan_status'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'loan_request_id': loanRequestId,
      'guarantor_coop_id': guarantorCoopId,
      'requester_name': requesterName,
      'requested_amount': requestedAmount,
      'monthly_repayment': monthlyRepayment,
      'status': status,
      'response_date': responseDate?.toIso8601String(),
      'response_notes': responseNotes,
      'created_at': createdAt.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
      'loan_status': loanStatus,
    };
  }
}

class MemberSearchResult {
  final String coopId;
  final String fullName;
  final String firstName;
  final String? middleName;
  final String lastName;
  final String? email;
  final String? mobile;
  final String? department;
  final String? jobPosition;

  MemberSearchResult({
    required this.coopId,
    required this.fullName,
    required this.firstName,
    this.middleName,
    required this.lastName,
    this.email,
    this.mobile,
    this.department,
    this.jobPosition,
  });

  factory MemberSearchResult.fromJson(Map<String, dynamic> json) {
    return MemberSearchResult(
      coopId: json['coop_id'] as String,
      fullName: json['full_name'] as String,
      firstName: json['first_name'] as String,
      middleName: json['middle_name'] as String?,
      lastName: json['last_name'] as String,
      email: json['email'] as String?,
      mobile: json['mobile'] as String?,
      department: json['department'] as String?,
      jobPosition: json['job_position'] as String?,
    );
  }
}

class SalaryValidationResult {
  final double totalEarnings;
  final double currentDeductions;
  final double netSalary;
  final double availableCapacity;
  final double monthlyRepayment;
  final bool canAfford;
  final double shortfall;
  final int periodId;
  
  // New fields from updated API response
  final double netPay;
  final double currentDeductionAmount;

  SalaryValidationResult({
    required this.totalEarnings,
    required this.currentDeductions,
    required this.netSalary,
    required this.availableCapacity,
    required this.monthlyRepayment,
    required this.canAfford,
    required this.shortfall,
    required this.periodId,
    this.netPay = 0,
    this.currentDeductionAmount = 0,
  });

  factory SalaryValidationResult.fromJson(Map<String, dynamic> json) {
    // Support both old and new API response formats
    final netPay = (json['net_pay'] ?? json['net_salary'] ?? 0) as num;
    final currentDeductionAmount = (json['current_deduction_amount'] ?? json['current_deductions'] ?? 0) as num;
    final totalEarnings = (json['total_earnings'] ?? 0) as num;
    
    return SalaryValidationResult(
      totalEarnings: totalEarnings.toDouble(),
      currentDeductions: currentDeductionAmount.toDouble(),
      netSalary: netPay.toDouble(),
      availableCapacity: (json['available_capacity'] as num).toDouble(),
      monthlyRepayment: (json['monthly_repayment'] as num).toDouble(),
      canAfford: json['can_afford'] as bool,
      shortfall: (json['shortfall'] as num).toDouble(),
      periodId: json['period_id'] as int,
      netPay: netPay.toDouble(),
      currentDeductionAmount: currentDeductionAmount.toDouble(),
    );
  }
}

