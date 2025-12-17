// lib/features/loans/presentation/screens/loan_tracker_screen.dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/main_layout.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';
import '../../../../shared/widgets/progress_indicator_custom.dart';
import '../../../../config/routes/routes.dart';
import '../../data/models/loan_tracker.dart';
import '../../data/services/loan_service.dart';

class LoanTrackerScreen extends StatefulWidget {
  const LoanTrackerScreen({Key? key}) : super(key: key);

  @override
  State<LoanTrackerScreen> createState() => _LoanTrackerScreenState();
}

class _LoanTrackerScreenState extends State<LoanTrackerScreen>
    with SingleTickerProviderStateMixin {
  final LoanService _loanService = LoanService();
  bool _isLoading = true;
  LoanTracker? _loanTracker;
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _loadLoanTracking();
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

  Future<void> _loadLoanTracking() async {
    try {
      final loanTracker = await _loanService.getLoanTracking();
      if (mounted) {
        setState(() {
          _loanTracker = loanTracker;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString())),
        );
        setState(() => _isLoading = false);
      }
    }
  }

  String _formatDate(DateTime? date) {
    if (date == null) return 'N/A';
    final dateFormat = DateFormat('MMM dd, yyyy');
    return dateFormat.format(date);
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormat = NumberFormat.currency(symbol: '₦');
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return MainLayout(
      currentIndex: 2,
      body: Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        body: FadeTransition(
          opacity: _fadeAnimation,
          child: SafeArea(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _loadLoanTracking,
                    color: AppTheme.primaryColor,
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(AppTheme.spacingMD),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Header
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Loan Tracker',
                                style: AppTheme.displayMedium.copyWith(
                                  color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                ),
                              ),
                              IconButton(
                                icon: Icon(
                                  Icons.add_circle_outline_rounded,
                                  color: AppTheme.primaryColor,
                                ),
                                onPressed: () {
                                  Navigator.pushNamed(
                                      context, AppRoutes.loanRequestForm);
                                },
                                tooltip: 'New Loan Request',
                              ),
                            ],
                          ),
                          const SizedBox(height: AppTheme.spacingLG),

                          // Quick Actions
                          Row(
                            children: [
                              Expanded(
                                flex: 1,
                                child: ModernCard(
                                  margin: EdgeInsets.only(right: AppTheme.spacingSM),
                                  padding: const EdgeInsets.symmetric(
                                    vertical: AppTheme.spacingLG,
                                    horizontal: AppTheme.spacingSM,
                                  ),
                                  onTap: () {
                                    Navigator.pushNamed(
                                        context, AppRoutes.loanRequestForm);
                                  },
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                        Icons.add_circle_rounded,
                                        color: AppTheme.primaryColor,
                                        size: 32,
                                      ),
                                      const SizedBox(height: AppTheme.spacingSM),
                                      Text(
                                        'New Request',
                                        style: AppTheme.labelMedium.copyWith(
                                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                        ),
                                        textAlign: TextAlign.center,
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                              Expanded(
                                flex: 1,
                                child: ModernCard(
                                  margin: EdgeInsets.symmetric(horizontal: AppTheme.spacingSM),
                                  padding: const EdgeInsets.symmetric(
                                    vertical: AppTheme.spacingLG,
                                    horizontal: AppTheme.spacingSM,
                                  ),
                                  onTap: () {
                                    Navigator.pushNamed(
                                        context, AppRoutes.loanRequestStatus);
                                  },
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                        Icons.list_alt_rounded,
                                        color: AppTheme.primaryColor,
                                        size: 32,
                                      ),
                                      const SizedBox(height: AppTheme.spacingSM),
                                      Text(
                                        'My Requests',
                                        style: AppTheme.labelMedium.copyWith(
                                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                        ),
                                        textAlign: TextAlign.center,
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                              Expanded(
                                flex: 1,
                                child: ModernCard(
                                  margin: EdgeInsets.only(left: AppTheme.spacingSM),
                                  padding: const EdgeInsets.symmetric(
                                    vertical: AppTheme.spacingLG,
                                    horizontal: AppTheme.spacingSM,
                                  ),
                                  onTap: () {
                                    Navigator.pushNamed(
                                        context, AppRoutes.guarantorRequests);
                                  },
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(
                                        Icons.how_to_reg_rounded,
                                        color: AppTheme.primaryColor,
                                        size: 32,
                                      ),
                                      const SizedBox(height: AppTheme.spacingSM),
                                      Text(
                                        'Guarantor',
                                        style: AppTheme.labelMedium.copyWith(
                                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                        ),
                                        textAlign: TextAlign.center,
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: AppTheme.spacingLG),

                          // Show loan tracker details only if loan exists
                          if (_loanTracker != null) ...[
                            // Progress Card
                            ModernCard(
                              padding: const EdgeInsets.all(AppTheme.spacingLG),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      Text(
                                        'Payment Progress',
                                        style: AppTheme.headlineMedium.copyWith(
                                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                        ),
                                      ),
                                      Text(
                                        '${_loanTracker!.paymentProgress.toStringAsFixed(1)}%',
                                        style: AppTheme.displaySmall.copyWith(
                                          color: AppTheme.primaryColor,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: AppTheme.spacingMD),
                                  ProgressIndicatorCustom(
                                    progress:
                                        _loanTracker!.paymentProgress / 100,
                                    height: 12,
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: AppTheme.spacingMD),

                            // Loan Details Grid
                            GridView.count(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              crossAxisCount: 2,
                              childAspectRatio: 1.2,
                              crossAxisSpacing: AppTheme.spacingMD,
                              mainAxisSpacing: AppTheme.spacingMD,
                              children: [
                                _buildInfoCard(
                                  'Principal Amount',
                                  currencyFormat
                                      .format(_loanTracker!.principalAmount),
                                  Icons.account_balance_wallet_rounded,
                                ),
                                _buildInfoCard(
                                  'Remaining Balance',
                                  currencyFormat
                                      .format(_loanTracker!.remainingBalance),
                                  Icons.money_off_rounded,
                                ),
                                _buildInfoCard(
                                  'Monthly Payment',
                                  currencyFormat
                                      .format(_loanTracker!.monthlyPayment),
                                  Icons.payment_rounded,
                                ),
                                _buildInfoCard(
                                  'Total Repaid',
                                  currencyFormat
                                      .format(_loanTracker!.totalRepaid),
                                  Icons.check_circle_rounded,
                                ),
                                _buildInfoCard(
                                  'Interest Paid',
                                  currencyFormat
                                      .format(_loanTracker!.interestPaid),
                                  Icons.trending_up_rounded,
                                ),
                                _buildInfoCard(
                                  'Months Remaining',
                                  '${_loanTracker!.monthsRemaining} months',
                                  Icons.calendar_today_rounded,
                                ),
                              ],
                            ),
                            const SizedBox(height: AppTheme.spacingMD),

                            // Additional Information
                            ModernCard(
                              padding: const EdgeInsets.all(AppTheme.spacingMD),
                              child: Row(
                                children: [
                                  Icon(
                                    Icons.calendar_today_rounded,
                                    color: AppTheme.primaryColor,
                                    size: 20,
                                  ),
                                  const SizedBox(width: AppTheme.spacingMD),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'Start Date',
                                          style: AppTheme.bodySmall.copyWith(
                                            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                          ),
                                        ),
                                        Text(
                                          _formatDate(_loanTracker!.startDate),
                                          style: AppTheme.bodyMedium.copyWith(
                                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: AppTheme.spacingSM),
                            ModernCard(
                              padding: const EdgeInsets.all(AppTheme.spacingMD),
                              child: Row(
                                children: [
                                  Icon(
                                    Icons.update_rounded,
                                    color: AppTheme.primaryColor,
                                    size: 20,
                                  ),
                                  const SizedBox(width: AppTheme.spacingMD),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          'Last Payment',
                                          style: AppTheme.bodySmall.copyWith(
                                            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                          ),
                                        ),
                                        Text(
                                          _formatDate(
                                              _loanTracker!.lastPaymentDate),
                                          style: AppTheme.bodyMedium.copyWith(
                                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ] else ...[
                            // Empty state
                            ModernCard(
                              padding: const EdgeInsets.all(AppTheme.spacingXL),
                              child: Column(
                                children: [
                                  Container(
                                    width: 80,
                                    height: 80,
                                    decoration: BoxDecoration(
                                      color: AppTheme.primaryColor
                                          .withOpacity(0.1),
                                      shape: BoxShape.circle,
                                    ),
                                    child: Icon(
                                      Icons.info_outline_rounded,
                                      color: AppTheme.primaryColor,
                                      size: 40,
                                    ),
                                  ),
                                  const SizedBox(height: AppTheme.spacingLG),
                                  Text(
                                    'No Active Loans',
                                    style: AppTheme.headlineLarge.copyWith(
                                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                    ),
                                  ),
                                  const SizedBox(height: AppTheme.spacingSM),
                                  Text(
                                    'Use "New Request" to apply for a loan',
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
                                      Navigator.pushNamed(
                                          context, AppRoutes.loanRequestForm);
                                    },
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
          ),
        ),
      ),
    );
  }

  Widget _buildInfoCard(String title, String value, IconData icon) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return ModernCard(
      padding: const EdgeInsets.all(AppTheme.spacingMD),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Icon(
                icon,
                color: AppTheme.primaryColor,
                size: 20,
              ),
              const SizedBox(width: AppTheme.spacingXS),
              Expanded(
                child: Text(
                  title,
                  style: AppTheme.bodySmall.copyWith(
                    color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppTheme.spacingSM),
          Flexible(
            child: FittedBox(
              fit: BoxFit.scaleDown,
              alignment: Alignment.centerLeft,
              child: Text(
                value,
                style: AppTheme.headlineMedium.copyWith(
                  color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
