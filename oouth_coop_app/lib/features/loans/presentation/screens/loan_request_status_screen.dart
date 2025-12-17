// lib/features/loans/presentation/screens/loan_request_status_screen.dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';
import '../../../../shared/widgets/status_badge.dart';
import '../../data/services/loan_request_service.dart';
import '../../data/models/loan_request_model.dart';
import '../widgets/member_search_dialog.dart';

class LoanRequestStatusScreen extends StatefulWidget {
  const LoanRequestStatusScreen({super.key});

  @override
  State<LoanRequestStatusScreen> createState() =>
      _LoanRequestStatusScreenState();
}

class _LoanRequestStatusScreenState extends State<LoanRequestStatusScreen>
    with SingleTickerProviderStateMixin {
  final LoanRequestService _loanRequestService = LoanRequestService();
  List<LoanRequest> _loanRequests = [];
  bool _isLoading = true;
  String? _requesterCoopId;
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _loadUserData();
    _animationController = AnimationController(
      duration: AppTheme.animationMedium,
      vsync: this,
    );
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeOut),
    );
    _animationController.forward();
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _loadUserData() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _requesterCoopId = prefs.getString('CoopID');
    });
    await _loadLoanRequests();
  }

  Future<void> _loadLoanRequests() async {
    if (_requesterCoopId == null) return;

    setState(() {
      _isLoading = true;
    });

    try {
      final requests =
          await _loanRequestService.getLoanRequests(_requesterCoopId!);
      setState(() {
        _loanRequests = requests;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading loan requests: $e')),
        );
      }
    }
  }

  StatusType _getStatusType(String status) {
    switch (status) {
      case 'draft':
        return StatusType.info;
      case 'pending_guarantors':
      case 'partially_guaranteed':
        return StatusType.pending;
      case 'fully_guaranteed':
      case 'submitted':
      case 'approved':
      case 'disbursed':
        return StatusType.success;
      case 'rejected':
        return StatusType.error;
      default:
        return StatusType.info;
    }
  }

  String _getStatusLabel(String status) {
    switch (status) {
      case 'draft':
        return 'Draft';
      case 'pending_guarantors':
        return 'Pending Guarantors';
      case 'partially_guaranteed':
        return 'Partially Guaranteed';
      case 'fully_guaranteed':
        return 'Fully Guaranteed';
      case 'rejected':
        return 'Rejected';
      case 'submitted':
        return 'Submitted';
      case 'approved':
        return 'Approved';
      case 'disbursed':
        return 'Disbursed';
      default:
        return status;
    }
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormat = NumberFormat.currency(symbol: '₦');
    final dateFormat = DateFormat('MMM dd, yyyy');
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
      appBar: AppBar(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        elevation: 0,
        leading: IconButton(
          icon: Icon(
            Icons.arrow_back_rounded,
            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'My Loan Requests',
          style: AppTheme.headlineLarge.copyWith(
            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
          ),
        ),
      ),
      body: FadeTransition(
        opacity: _fadeAnimation,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _loanRequests.isEmpty
                ? Center(
                    child: ModernCard(
                      padding: const EdgeInsets.all(AppTheme.spacingXL),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            width: 80,
                            height: 80,
                            decoration: BoxDecoration(
                              color: AppTheme.primaryColor.withOpacity(0.1),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              Icons.inbox_outlined,
                              color: AppTheme.primaryColor,
                              size: 40,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingLG),
                          Text(
                            'No Loan Requests',
                            style: AppTheme.headlineLarge.copyWith(
                              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingSM),
                          Text(
                            'You haven\'t submitted any loan requests yet',
                            style: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: AppTheme.spacingLG),
                          PrimaryButton(
                            text: 'Request a Loan',
                            icon: Icons.add_rounded,
                            onPressed: () {
                              Navigator.pop(context);
                            },
                          ),
                        ],
                      ),
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: _loadLoanRequests,
                    color: AppTheme.primaryColor,
                    child: ListView.builder(
                      padding: const EdgeInsets.all(AppTheme.spacingMD),
                      itemCount: _loanRequests.length,
                      itemBuilder: (context, index) {
                        final request = _loanRequests[index];

                        return ModernCard(
                          margin: const EdgeInsets.only(bottom: AppTheme.spacingMD),
                          padding: const EdgeInsets.all(AppTheme.spacingLG),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Header Row
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          currencyFormat
                                              .format(request.requestedAmount),
                                          style: AppTheme.displaySmall.copyWith(
                                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                          ),
                                        ),
                                        const SizedBox(height: AppTheme.spacingXS),
                                        Text(
                                          'Requested Amount',
                                          style: AppTheme.bodySmall.copyWith(
                                            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  StatusBadge(
                                    text: _getStatusLabel(request.status),
                                    type: _getStatusType(request.status),
                                  ),
                                ],
                              ),
                              const SizedBox(height: AppTheme.spacingLG),
                              Divider(
                                color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                              ),
                              const SizedBox(height: AppTheme.spacingMD),

                              // Details
                              _buildDetailRow(
                                Icons.calendar_today_rounded,
                                'Created',
                                dateFormat.format(request.createdAt),
                              ),
                              const SizedBox(height: AppTheme.spacingSM),
                              _buildDetailRow(
                                Icons.payment_rounded,
                                'Monthly Repayment',
                                currencyFormat.format(request.monthlyRepayment),
                              ),
                              const SizedBox(height: AppTheme.spacingMD),

                              // Guarantor Status
                              ModernCard(
                                padding: const EdgeInsets.all(AppTheme.spacingMD),
                                backgroundColor: isDark
                                    ? AppTheme.cardDark.withOpacity(0.7)
                                    : AppTheme.backgroundLight.withOpacity(0.5),
                                child: Column(
                                  children: [
                                    Text(
                                      'Guarantor Status',
                                      style: AppTheme.labelMedium.copyWith(
                                        color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                      ),
                                    ),
                                    const SizedBox(height: AppTheme.spacingMD),
                                    Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceAround,
                                      children: [
                                        _buildGuarantorStatus(
                                          'Total',
                                          request.totalGuarantors,
                                          2,
                                        ),
                                        _buildGuarantorStatus(
                                          'Approved',
                                          request.approvedGuarantors,
                                          2,
                                          isApproved: true,
                                        ),
                                        _buildGuarantorStatus(
                                          'Rejected',
                                          request.rejectedGuarantors,
                                          2,
                                          isRejected: true,
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),

                              // Replace Guarantor Button
                              if (request.rejectedGuarantors > 0 &&
                                  request.status != 'submitted' &&
                                  request.status != 'fully_guaranteed') ...[
                                const SizedBox(height: AppTheme.spacingMD),
                                SizedBox(
                                  width: double.infinity,
                                  child: OutlinedButton.icon(
                                    onPressed: () =>
                                        _replaceRejectedGuarantors(request),
                                    icon: Icon(
                                      Icons.person_add_rounded,
                                      color: AppTheme.warning,
                                    ),
                                    label: Text(
                                      'Replace Rejected Guarantor(s)',
                                      style: AppTheme.labelMedium.copyWith(
                                        color: AppTheme.warning,
                                      ),
                                    ),
                                    style: OutlinedButton.styleFrom(
                                      padding: const EdgeInsets.symmetric(
                                          vertical: AppTheme.spacingMD),
                                      side: BorderSide(color: AppTheme.warning),
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        );
                      },
                    ),
                  ),
      ),
    );
  }

  Widget _buildDetailRow(IconData icon, String label, String value) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Row(
      children: [
        Icon(
          icon,
          size: 18,
          color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
        ),
        const SizedBox(width: AppTheme.spacingSM),
        Text(
          '$label: ',
          style: AppTheme.bodyMedium.copyWith(
            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: AppTheme.bodyMedium.copyWith(
              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildGuarantorStatus(
    String label,
    int count,
    int required, {
    bool isApproved = false,
    bool isRejected = false,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    Color countColor = isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight;
    if (isApproved) {
      countColor = AppTheme.success;
    } else if (isRejected) {
      countColor = AppTheme.error;
    }

    return Column(
      children: [
        Text(
          label,
          style: AppTheme.bodySmall.copyWith(
            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
          ),
        ),
        const SizedBox(height: AppTheme.spacingXS),
        Text(
          '$count/$required',
          style: AppTheme.headlineMedium.copyWith(
            color: countColor,
          ),
        ),
      ],
    );
  }

  Future<void> _replaceRejectedGuarantors(LoanRequest request) async {
    if (request.status == 'submitted' ||
        request.status == 'fully_guaranteed') {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
              'Cannot replace guarantors for a submitted loan request'),
          backgroundColor: AppTheme.error,
        ),
      );
      return;
    }

    try {
      final guarantorRequests =
          await _loanRequestService.getGuarantorRequests(
        loanRequestId: request.id!,
      );

      final rejectedGuarantors = guarantorRequests
          .where((gr) => gr.status == 'rejected')
          .toList();
      final approvedGuarantors = guarantorRequests
          .where((gr) => gr.status == 'approved')
          .toList();
      final pendingGuarantors = guarantorRequests
          .where((gr) => gr.status == 'pending')
          .toList();

      if (rejectedGuarantors.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('No rejected guarantors to replace'),
            backgroundColor: AppTheme.warning,
          ),
        );
        return;
      }

      if (approvedGuarantors.length >= 2) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
                'You already have 2 approved guarantors. Loan request will be submitted automatically.'),
            backgroundColor: AppTheme.success,
          ),
        );
        return;
      }

      if (approvedGuarantors.length + pendingGuarantors.length >= 2) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
                'You already have 2 active guarantor requests. Please wait for responses.'),
            backgroundColor: AppTheme.warning,
          ),
        );
        return;
      }

      final replacement = await showDialog<MemberSearchResult>(
        context: context,
        builder: (context) => const MemberSearchDialog(),
      );

      if (replacement == null) return;

      if (replacement.coopId == _requesterCoopId) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('You cannot select yourself as a guarantor'),
            backgroundColor: AppTheme.error,
          ),
        );
        return;
      }

      for (var approved in approvedGuarantors) {
        if (approved.guarantorCoopId == replacement.coopId) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('This guarantor has already approved'),
              backgroundColor: AppTheme.error,
            ),
          );
          return;
        }
      }

      for (var pending in pendingGuarantors) {
        if (pending.guarantorCoopId == replacement.coopId) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('A request is already pending for this guarantor'),
              backgroundColor: AppTheme.error,
            ),
          );
          return;
        }
      }

      for (var rejected in rejectedGuarantors) {
        if (rejected.guarantorCoopId == replacement.coopId) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Cannot resend to the same rejected guarantor'),
              backgroundColor: AppTheme.error,
            ),
          );
          return;
        }
      }

      final success = await _loanRequestService.sendGuarantorRequest(
        loanRequestId: request.id!,
        guarantorCoopId: replacement.coopId,
      );

      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Replacement guarantor request sent successfully'),
            backgroundColor: AppTheme.success,
          ),
        );
        await _loadLoanRequests();
      } else {
        throw Exception('Failed to send replacement guarantor request');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
                'Error replacing guarantor: ${e.toString().replaceAll('Exception: ', '')}'),
            backgroundColor: AppTheme.error,
            duration: const Duration(seconds: 4),
          ),
        );
      }
    }
  }
}
