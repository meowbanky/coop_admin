// lib/features/products/presentation/screens/product_screen.dart
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/main_layout.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';
import '../../data/models/transaction_summary.dart';
import '../../services/product_service.dart';

class ProductScreen extends StatefulWidget {
  const ProductScreen({Key? key}) : super(key: key);

  @override
  State<ProductScreen> createState() => _ProductScreenState();
}

class _ProductScreenState extends State<ProductScreen>
    with SingleTickerProviderStateMixin {
  final ProductService _productService = ProductService();
  String? _selectedFromPeriod;
  String? _selectedToPeriod;
  List<TransactionSummary> _summaries = [];
  bool _isLoading = false;
  List<Map<String, dynamic>> _periods = [];
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _loadPeriods();
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

  Future<void> _loadPeriods() async {
    setState(() => _isLoading = true);
    try {
      final result = await _productService.getPeriods();
      if (result['success']) {
        setState(() {
          _periods = List<Map<String, dynamic>>.from(result['data']);
        });
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(result['message'])),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error loading periods')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _fetchTransactionSummary() async {
    if (_selectedFromPeriod == null || _selectedToPeriod == null) return;

    setState(() => _isLoading = true);
    try {
      final result = await _productService.getTransactionSummary(
        _selectedFromPeriod!,
        _selectedToPeriod!,
      );

      if (result['success']) {
        if (mounted) {
          setState(() {
            _summaries = (result['data'] as List)
                .map((item) => TransactionSummary.fromJson(item))
                .toList();
          });
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(result['message'])),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Error fetching transaction summary')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final currencyFormat = NumberFormat.currency(symbol: '₦');
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return MainLayout(
      currentIndex: 1,
      body: Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        body: FadeTransition(
          opacity: _fadeAnimation,
          child: SafeArea(
            child: Column(
              children: [
                // Header
                Padding(
                  padding: const EdgeInsets.all(AppTheme.spacingMD),
                  child: Row(
                    children: [
                      Text(
                        'Transaction History',
                        style: AppTheme.displayMedium.copyWith(
                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                        ),
                      ),
                    ],
                  ),
                ),

                // Filters Card
                ModernCard(
                  margin: const EdgeInsets.all(AppTheme.spacingMD),
                  padding: const EdgeInsets.all(AppTheme.spacingLG),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Select Period Range',
                        style: AppTheme.headlineMedium.copyWith(
                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                        ),
                      ),
                      const SizedBox(height: AppTheme.spacingLG),
                      DropdownButtonFormField<String>(
                        value: _selectedFromPeriod,
                        decoration: const InputDecoration(
                          labelText: 'Period From',
                        ),
                        items: _periods.map((period) {
                          return DropdownMenuItem(
                            value: period['id'].toString(),
                            child: Text(period['PayrollPeriod']),
                          );
                        }).toList(),
                        onChanged: (value) =>
                            setState(() => _selectedFromPeriod = value),
                      ),
                      const SizedBox(height: AppTheme.spacingMD),
                      DropdownButtonFormField<String>(
                        value: _selectedToPeriod,
                        decoration: const InputDecoration(
                          labelText: 'Period To',
                        ),
                        items: _periods.map((period) {
                          return DropdownMenuItem(
                            value: period['id'].toString(),
                            child: Text(period['PayrollPeriod']),
                          );
                        }).toList(),
                        onChanged: (value) =>
                            setState(() => _selectedToPeriod = value),
                      ),
                      const SizedBox(height: AppTheme.spacingLG),
                      PrimaryButton(
                        text: 'Get Summary',
                        isLoading: _isLoading,
                        icon: Icons.search_rounded,
                        onPressed: _fetchTransactionSummary,
                        width: double.infinity,
                      ),
                    ],
                  ),
                ),

                // Results
                Expanded(
                  child: _isLoading
                      ? const Center(child: CircularProgressIndicator())
                      : _summaries.isEmpty
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
                                        Icons.history_rounded,
                                        color: AppTheme.primaryColor,
                                        size: 40,
                                      ),
                                    ),
                                    const SizedBox(height: AppTheme.spacingLG),
                                    Text(
                                      'No Data Available',
                                      style: AppTheme.headlineLarge.copyWith(
                                        color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                      ),
                                    ),
                                    const SizedBox(height: AppTheme.spacingSM),
                                    Text(
                                      'Select a period range and click "Get Summary"',
                                      style: AppTheme.bodyMedium.copyWith(
                                        color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                      ),
                                      textAlign: TextAlign.center,
                                    ),
                                  ],
                                ),
                              ),
                            )
                          : ListView.builder(
                              padding: const EdgeInsets.all(AppTheme.spacingMD),
                              itemCount: _summaries.length,
                              itemBuilder: (context, index) {
                                return _buildSummaryCard(
                                    _summaries[index], currencyFormat);
                              },
                            ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSummaryCard(
      TransactionSummary summary, NumberFormat currencyFormat) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return ModernCard(
      margin: const EdgeInsets.only(bottom: AppTheme.spacingMD),
      padding: const EdgeInsets.all(AppTheme.spacingLG),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(AppTheme.spacingSM),
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(AppTheme.radiusSM),
                ),
                child: Icon(
                  Icons.calendar_today_rounded,
                  color: AppTheme.primaryColor,
                  size: 20,
                ),
              ),
              const SizedBox(width: AppTheme.spacingMD),
              Expanded(
                child: Text(
                  summary.payrollPeriod,
                  style: AppTheme.headlineMedium.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: AppTheme.spacingLG),
          Divider(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
          const SizedBox(height: AppTheme.spacingMD),
          _buildSummaryRow('Dev Levy', summary.devLevy, currencyFormat),
          _buildSummaryRow('Stationery', summary.stationery, currencyFormat),
          _buildSummaryRow('Savings', summary.savingsAmount, currencyFormat),
          _buildSummaryRow(
              'Savings Balance', summary.savingsBalance, currencyFormat),
          _buildSummaryRow('Shares', summary.sharesAmount, currencyFormat),
          _buildSummaryRow(
              'Shares Balance', summary.sharesBalance, currencyFormat),
          _buildSummaryRow('Interest Paid', summary.interestPaid, currencyFormat),
          _buildSummaryRow('Commodity', summary.commodity, currencyFormat),
          _buildSummaryRow('Commodity Repayment', summary.commodityRepayment,
              currencyFormat),
          _buildSummaryRow(
              'Commodity Balance', summary.commodityBalance, currencyFormat),
          _buildSummaryRow('Loan', summary.loan, currencyFormat),
          _buildSummaryRow(
              'Loan Repayment', summary.loanRepayment, currencyFormat),
          _buildSummaryRow('Loan Balance', summary.loanBalance, currencyFormat),
          const SizedBox(height: AppTheme.spacingSM),
          Divider(color: isDark ? AppTheme.borderDark : AppTheme.borderLight, thickness: 2),
          const SizedBox(height: AppTheme.spacingSM),
          _buildSummaryRow('Total', summary.total, currencyFormat,
              isTotal: true),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(String label, double amount,
      NumberFormat currencyFormat,
      {bool isTotal = false}) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppTheme.spacingXS),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: (isTotal
                    ? AppTheme.headlineMedium
                    : AppTheme.bodyMedium)
                .copyWith(
              color: isTotal
                  ? (isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight)
                  : (isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight),
            ),
          ),
          Text(
            currencyFormat.format(amount),
            style: (isTotal
                    ? AppTheme.headlineMedium
                    : AppTheme.bodyMedium)
                .copyWith(
              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
              fontWeight: isTotal ? FontWeight.w700 : FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
