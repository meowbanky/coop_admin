// lib/features/loans/presentation/screens/guarantor_requests_screen.dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';
import '../../../../shared/widgets/status_badge.dart';
import '../../data/services/loan_request_service.dart';
import '../../data/models/loan_request_model.dart';

class GuarantorRequestsScreen extends StatefulWidget {
  const GuarantorRequestsScreen({super.key});

  @override
  State<GuarantorRequestsScreen> createState() =>
      _GuarantorRequestsScreenState();
}

class _GuarantorRequestsScreenState extends State<GuarantorRequestsScreen>
    with SingleTickerProviderStateMixin {
  final LoanRequestService _loanRequestService = LoanRequestService();
  List<GuarantorRequest> _guarantorRequests = [];
  bool _isLoading = true;
  String? _guarantorCoopId;
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
      _guarantorCoopId = prefs.getString('CoopID');
    });
    await _loadGuarantorRequests();
  }

  Future<void> _loadGuarantorRequests() async {
    if (_guarantorCoopId == null) return;

    setState(() {
      _isLoading = true;
    });

    try {
      final requests = await _loanRequestService.getGuarantorRequests(
        guarantorCoopId: _guarantorCoopId!,
      );
      setState(() {
        _guarantorRequests = requests;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading guarantor requests: $e')),
        );
      }
    }
  }

  Future<void> _respondToRequest(
      GuarantorRequest request, String response) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(
          response == 'approved'
              ? 'Approve Guarantor Request?'
              : 'Reject Guarantor Request?',
          style: AppTheme.headlineMedium,
        ),
        content: Text(
          response == 'approved'
              ? 'Are you sure you want to approve this guarantor request?'
              : 'Are you sure you want to reject this guarantor request?',
          style: AppTheme.bodyMedium,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: Text('Cancel', style: AppTheme.labelMedium),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(
              foregroundColor:
                  response == 'approved' ? AppTheme.success : AppTheme.error,
            ),
            child: Text(
              response == 'approved' ? 'Approve' : 'Reject',
              style: AppTheme.labelMedium.copyWith(
                color: response == 'approved' ? AppTheme.success : AppTheme.error,
              ),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    String? notes;
    if (response == 'rejected') {
      notes = await showDialog<String>(
        context: context,
        builder: (context) {
          final notesController = TextEditingController();
          return AlertDialog(
            title: Text('Rejection Notes (Optional)', style: AppTheme.headlineMedium),
            content: TextField(
              controller: notesController,
              decoration: const InputDecoration(
                hintText: 'Enter reason for rejection...',
              ),
              maxLines: 3,
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: Text('Cancel', style: AppTheme.labelMedium),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context, notesController.text),
                child: Text('Submit', style: AppTheme.labelMedium),
              ),
            ],
          );
        },
      );
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final success = await _loanRequestService.respondToGuarantorRequest(
        guarantorRequestId: request.id!,
        response: response,
        responseNotes: notes,
      );

      if (success) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                response == 'approved'
                    ? 'Guarantor request approved successfully'
                    : 'Guarantor request rejected',
              ),
              backgroundColor: AppTheme.success,
            ),
          );
        }
        await _loadGuarantorRequests();
      } else {
        throw Exception('Failed to respond to guarantor request');
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: AppTheme.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
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
          'Guarantor Requests',
          style: AppTheme.headlineLarge.copyWith(
            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
          ),
        ),
      ),
      body: FadeTransition(
        opacity: _fadeAnimation,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _guarantorRequests.isEmpty
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
                              Icons.how_to_reg_outlined,
                              color: AppTheme.primaryColor,
                              size: 40,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingLG),
                          Text(
                            'No Requests',
                            style: AppTheme.headlineLarge.copyWith(
                              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingSM),
                          Text(
                            'You don\'t have any pending guarantor requests',
                            style: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: _loadGuarantorRequests,
                    color: AppTheme.primaryColor,
                    child: ListView.builder(
                      padding: const EdgeInsets.all(AppTheme.spacingMD),
                      itemCount: _guarantorRequests.length,
                      itemBuilder: (context, index) {
                        final request = _guarantorRequests[index];
                        final isPending = request.status == 'pending';
                        final isApproved = request.status == 'approved';

                        return ModernCard(
                          margin: const EdgeInsets.only(bottom: AppTheme.spacingMD),
                          padding: const EdgeInsets.all(AppTheme.spacingLG),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
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
                                          request.requesterName,
                                          style: AppTheme.displaySmall.copyWith(
                                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                          ),
                                        ),
                                        const SizedBox(height: AppTheme.spacingXS),
                                        Text(
                                          currencyFormat.format(
                                              request.requestedAmount),
                                          style: AppTheme.headlineMedium.copyWith(
                                            color: AppTheme.primaryColor,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  StatusBadge(
                                    text: request.status.toUpperCase(),
                                    type: isPending
                                        ? StatusType.pending
                                        : isApproved
                                            ? StatusType.success
                                            : StatusType.error,
                                  ),
                                ],
                              ),
                              const SizedBox(height: AppTheme.spacingLG),
                              Divider(
                                color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                              ),
                              const SizedBox(height: AppTheme.spacingMD),
                              _buildDetailRow(
                                Icons.payment_rounded,
                                'Monthly Repayment',
                                currencyFormat.format(request.monthlyRepayment),
                              ),
                              const SizedBox(height: AppTheme.spacingSM),
                              _buildDetailRow(
                                Icons.calendar_today_rounded,
                                'Requested',
                                dateFormat.format(request.createdAt),
                              ),
                              if (request.responseDate != null) ...[
                                const SizedBox(height: AppTheme.spacingSM),
                                _buildDetailRow(
                                  Icons.check_circle_rounded,
                                  'Responded',
                                  dateFormat.format(request.responseDate!),
                                ),
                              ],
                              if (request.responseNotes != null &&
                                  request.responseNotes!.isNotEmpty) ...[
                                const SizedBox(height: AppTheme.spacingMD),
                                ModernCard(
                                  padding: const EdgeInsets.all(AppTheme.spacingMD),
                                  backgroundColor: isDark
                                      ? AppTheme.cardDark.withOpacity(0.7)
                                      : AppTheme.backgroundLight.withOpacity(0.5),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'Notes',
                                        style: AppTheme.labelMedium.copyWith(
                                          color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                        ),
                                      ),
                                      const SizedBox(height: AppTheme.spacingXS),
                                      Text(
                                        request.responseNotes!,
                                        style: AppTheme.bodyMedium.copyWith(
                                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                              if (isPending) ...[
                                const SizedBox(height: AppTheme.spacingMD),
                                Row(
                                  children: [
                                    Expanded(
                                      child: OutlinedButton.icon(
                                        onPressed: () =>
                                            _respondToRequest(request, 'rejected'),
                                        icon: Icon(Icons.close_rounded,
                                            color: AppTheme.error),
                                        label: Text(
                                          'Reject',
                                          style: AppTheme.labelMedium.copyWith(
                                            color: AppTheme.error,
                                          ),
                                        ),
                                        style: OutlinedButton.styleFrom(
                                          padding: const EdgeInsets.symmetric(
                                              vertical: AppTheme.spacingMD),
                                          side: BorderSide(color: AppTheme.error),
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: AppTheme.spacingMD),
                                    Expanded(
                                      child: PrimaryButton(
                                        text: 'Approve',
                                        icon: Icons.check_rounded,
                                        onPressed: () =>
                                            _respondToRequest(request, 'approved'),
                                      ),
                                    ),
                                  ],
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
}
