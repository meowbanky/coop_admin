// lib/features/loans/presentation/screens/loan_request_form_screen.dart
import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import 'dart:convert';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';
import '../../data/services/loan_request_service.dart';
import '../../data/models/loan_request_model.dart';
import '../widgets/member_search_dialog.dart';

class LoanRequestFormScreen extends StatefulWidget {
  const LoanRequestFormScreen({super.key});

  @override
  State<LoanRequestFormScreen> createState() => _LoanRequestFormScreenState();
}

class _LoanRequestFormScreenState extends State<LoanRequestFormScreen>
    with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final LoanRequestService _loanRequestService = LoanRequestService();

  File? _payslipFile;
  MemberSearchResult? _guarantor1;
  MemberSearchResult? _guarantor2;

  bool _isLoading = false;
  bool _isValidatingSalary = false;
  SalaryValidationResult? _salaryValidation;

  String? _requesterCoopId;
  int? _staffId;
  int? _deductionId;
  int? _selectedPeriodId;
  List<Map<String, dynamic>> _salaryPeriods = [];
  bool _isLoadingPeriods = false;
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _loadUserData();
    _amountController.addListener(_calculateMonthlyRepayment);
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
    _amountController.dispose();
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _loadSalaryPeriods() async {
    if (_requesterCoopId == null) return;

    setState(() {
      _isLoadingPeriods = true;
    });

    try {
      final periods = await _loanRequestService.getSalaryPeriods();
      setState(() {
        _salaryPeriods = periods;
        if (_salaryPeriods.isNotEmpty) {
          _selectedPeriodId = _salaryPeriods.first['period_id'];
          _checkExistingRequest();
        }
      });
    } catch (e) {
      debugPrint('Error loading salary periods: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error loading periods: $e'),
            backgroundColor: AppTheme.error,
          ),
        );
      }
    } finally {
      setState(() {
        _isLoadingPeriods = false;
      });
    }
  }

  Future<void> _checkExistingRequest() async {
    if (_requesterCoopId == null || _selectedPeriodId == null) return;

    try {
      final requests =
          await _loanRequestService.getLoanRequests(_requesterCoopId!);
      final existingRequests = requests
          .where(
            (req) =>
                req.periodId == _selectedPeriodId &&
                (req.status == 'draft' ||
                    req.status == 'pending_guarantors' ||
                    req.status == 'partially_guaranteed' ||
                    req.status == 'submitted'),
          )
          .toList();

      if (existingRequests.isNotEmpty && mounted) {
        final existingRequest = existingRequests.first;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
                'You already have a ${existingRequest.status.replaceAll('_', ' ')} loan request for this period'),
            backgroundColor: AppTheme.warning,
            duration: const Duration(seconds: 5),
          ),
        );
      }
    } catch (e) {
      debugPrint('Error checking existing request: $e');
    }
  }

  Future<void> _loadUserData() async {
    final prefs = await SharedPreferences.getInstance();
    final coopId = prefs.getString('CoopID');

    setState(() {
      _requesterCoopId = coopId;
      _deductionId = 48;
    });

    if (coopId != null) {
      await _fetchStaffId(coopId);
      await _loadSalaryPeriods();
    }
  }

  Future<void> _fetchStaffId(String coopId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userDataString = prefs.getString('user_data');

      if (userDataString != null) {
        final userData = json.decode(userDataString);
        if (userData['StaffID'] != null) {
          setState(() {
            _staffId = userData['StaffID'] is int
                ? userData['StaffID']
                : int.tryParse(userData['StaffID'].toString());
          });
          return;
        }
      }
    } catch (e) {
      debugPrint('Error fetching StaffID: $e');
    }
  }

  void _calculateMonthlyRepayment() {
    setState(() {});
  }

  double get _monthlyRepayment {
    final amount = double.tryParse(_amountController.text) ?? 0;
    return amount * 0.10;
  }

  Future<void> _pickPayslip() async {
    try {
      FilePickerResult? result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['pdf'],
        allowMultiple: false,
      );

      if (result != null && result.files.single.path != null) {
        setState(() {
          _payslipFile = File(result.files.single.path!);
        });
      }
    } catch (e) {
      debugPrint('Error picking PDF file: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error selecting PDF file: $e'),
            backgroundColor: AppTheme.error,
          ),
        );
      }
    }
  }

  Future<void> _selectGuarantor(int guarantorNumber) async {
    if (_requesterCoopId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Please wait while we load your information'),
          backgroundColor: AppTheme.warning,
        ),
      );
      return;
    }
    final result = await showDialog<MemberSearchResult>(
      context: context,
      builder: (context) => const MemberSearchDialog(),
    );

    if (result != null) {
      if (result.coopId == _requesterCoopId) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('You cannot select yourself as a guarantor'),
            backgroundColor: AppTheme.error,
          ),
        );
        return;
      }

      setState(() {
        if (guarantorNumber == 1) {
          if (_guarantor2?.coopId == result.coopId) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Guarantor 1 cannot be the same as Guarantor 2'),
                backgroundColor: AppTheme.error,
              ),
            );
            return;
          }
          _guarantor1 = result;
        } else {
          if (_guarantor1?.coopId == result.coopId) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Guarantor 2 cannot be the same as Guarantor 1'),
                backgroundColor: AppTheme.error,
              ),
            );
            return;
          }
          _guarantor2 = result;
        }
      });
    }
  }

  Future<void> _validateSalary() async {
    if (_requesterCoopId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('User information not loaded. Please try again.'),
        ),
      );
      return;
    }

    if (_deductionId == null) {
      setState(() {
        _deductionId = 48;
      });
    }

    final amount = double.tryParse(_amountController.text);
    if (amount == null || amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please enter a valid loan amount first'),
        ),
      );
      return;
    }

    setState(() {
      _isValidatingSalary = true;
    });

    if (_selectedPeriodId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select a payroll period'),
          backgroundColor: AppTheme.error,
        ),
      );
      return;
    }

    try {
      final validation = await _loanRequestService.validateSalary(
        coopId: _requesterCoopId,
        deductionId: _deductionId,
        periodId: _selectedPeriodId,
        monthlyRepayment: _monthlyRepayment,
      );

      setState(() {
        _salaryValidation = validation;
        _isValidatingSalary = false;
      });

      if (validation != null && !validation.canAfford) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Insufficient salary capacity. Shortfall: ₦${NumberFormat('#,##0.00').format(validation.shortfall)}',
            ),
            backgroundColor: AppTheme.error,
          ),
        );
      }
    } catch (e) {
      setState(() {
        _isValidatingSalary = false;
      });

      String errorMessage = 'Error validating salary';
      if (e.toString().contains('Invalid response format')) {
        errorMessage =
            'Server returned invalid response. Please check server logs or contact support.';
      } else if (e.toString().contains('Period is required')) {
        errorMessage = 'Please select a payroll period';
      } else {
        errorMessage = e.toString().replaceAll('Exception: ', '');
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(errorMessage),
          backgroundColor: AppTheme.error,
          duration: const Duration(seconds: 5),
        ),
      );
    }
  }

  Future<void> _submitLoanRequest() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    if (_payslipFile == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please upload a PDF payslip'),
          backgroundColor: AppTheme.error,
        ),
      );
      return;
    }

    if (_guarantor1 == null || _guarantor2 == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select two different guarantors'),
          backgroundColor: AppTheme.error,
        ),
      );
      return;
    }

    if (_salaryValidation == null || !_salaryValidation!.canAfford) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please validate your salary capacity first'),
          backgroundColor: AppTheme.error,
        ),
      );
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      String? payslipFilePath;
      if (_payslipFile != null) {
        try {
          payslipFilePath =
              await _loanRequestService.uploadPayslip(_payslipFile!);
          if (payslipFilePath == null) {
            debugPrint(
                'Warning: Payslip upload failed, continuing without payslip');
          }
        } catch (e) {
          debugPrint('Payslip upload error: $e');
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(
                  'Warning: Payslip upload failed: $e. Continuing without payslip.'),
              backgroundColor: AppTheme.warning,
              duration: const Duration(seconds: 4),
            ),
          );
        }
      }

      LoanRequest? loanRequest;
      try {
        loanRequest = await _loanRequestService.createLoanRequest(
          requesterCoopId: _requesterCoopId!,
          requestedAmount: double.parse(_amountController.text),
          staffId: _staffId,
          deductionId: _deductionId,
          periodId: _selectedPeriodId,
          payslipFilePath: payslipFilePath,
        );

        if (loanRequest == null) {
          throw Exception('Failed to create loan request');
        }
      } catch (e) {
        throw e;
      }

      final guarantor1Sent = await _loanRequestService.sendGuarantorRequest(
        loanRequestId: loanRequest.id!,
        guarantorCoopId: _guarantor1!.coopId,
      );

      final guarantor2Sent = await _loanRequestService.sendGuarantorRequest(
        loanRequestId: loanRequest.id!,
        guarantorCoopId: _guarantor2!.coopId,
      );

      if (!guarantor1Sent || !guarantor2Sent) {
        throw Exception('Failed to send guarantor requests');
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
                'Guarantor requests sent successfully! You will be notified when they respond.'),
            backgroundColor: AppTheme.success,
            duration: Duration(seconds: 4),
          ),
        );
        Navigator.pop(context, true);
      }
    } catch (e) {
      if (mounted) {
        String errorMessage = e.toString();
        if (errorMessage.startsWith('Exception: ')) {
          errorMessage = errorMessage.substring(11);
        }

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(errorMessage),
            backgroundColor: AppTheme.error,
            duration: const Duration(seconds: 5),
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
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
      appBar: AppBar(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_rounded, color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Loan Request',
          style: AppTheme.headlineLarge.copyWith(color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight),
        ),
      ),
      body: FadeTransition(
        opacity: _fadeAnimation,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppTheme.spacingMD),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Requested Amount
                Text(
                  'Requested Amount',
                  style: AppTheme.headlineMedium.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                ),
                const SizedBox(height: AppTheme.spacingSM),
                TextFormField(
                  controller: _amountController,
                  keyboardType: TextInputType.number,
                  style: AppTheme.bodyLarge.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                  decoration: InputDecoration(
                    hintText: 'Enter loan amount',
                    hintStyle: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                    ),
                    prefixText: '₦ ',
                    prefixStyle: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                    filled: true,
                    fillColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppTheme.radiusMD),
                      borderSide: BorderSide(
                        color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                      ),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppTheme.radiusMD),
                      borderSide: BorderSide(
                        color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                      ),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(AppTheme.radiusMD),
                      borderSide: BorderSide(
                        color: AppTheme.primaryColor,
                        width: 2,
                      ),
                    ),
                  ),
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter the loan amount';
                    }
                    final amount = double.tryParse(value);
                    if (amount == null || amount <= 0) {
                      return 'Please enter a valid amount';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: AppTheme.spacingLG),

                // Payroll Period Selection
                Text(
                  'Payroll Period',
                  style: AppTheme.headlineMedium.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                ),
                const SizedBox(height: AppTheme.spacingSM),
                _isLoadingPeriods
                    ? const Center(child: CircularProgressIndicator())
                    : DropdownButtonFormField<int>(
                        value: _selectedPeriodId,
                        decoration: InputDecoration(
                          filled: true,
                          fillColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(AppTheme.radiusMD),
                            borderSide: BorderSide(
                              color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                            ),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(AppTheme.radiusMD),
                            borderSide: BorderSide(
                              color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                            ),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(AppTheme.radiusMD),
                            borderSide: BorderSide(
                              color: AppTheme.primaryColor,
                              width: 2,
                            ),
                          ),
                        ),
                        dropdownColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                        hint: Text(
                          'Select payroll period',
                          style: AppTheme.bodyMedium.copyWith(
                            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                          ),
                        ),
                        style: AppTheme.bodyMedium.copyWith(
                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                        ),
                        items: _salaryPeriods.map((period) {
                          return DropdownMenuItem<int>(
                            value: period['period_id'],
                            child: Text(
                              period['display_name'] ??
                                  '${period['description']} ${period['year']}',
                              style: AppTheme.bodyMedium.copyWith(
                                color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                              ),
                            ),
                          );
                        }).toList(),
                        onChanged: (value) {
                          setState(() {
                            _selectedPeriodId = value;
                            _salaryValidation = null;
                          });
                        },
                        validator: (value) {
                          if (value == null) {
                            return 'Please select a payroll period';
                          }
                          return null;
                        },
                      ),
                const SizedBox(height: AppTheme.spacingLG),

                // Monthly Repayment Card
                ModernCard(
                  padding: const EdgeInsets.all(AppTheme.spacingMD),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Monthly Repayment',
                        style: AppTheme.bodyMedium.copyWith(
                          color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                        ),
                      ),
                      const SizedBox(height: AppTheme.spacingXS),
                      Text(
                        currencyFormat.format(_monthlyRepayment),
                        style: AppTheme.displaySmall.copyWith(
                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                        ),
                      ),
                      const SizedBox(height: AppTheme.spacingXS),
                      Text(
                        '10% of requested amount',
                        style: AppTheme.bodySmall.copyWith(
                          color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: AppTheme.spacingLG),

                // Payslip Upload
                Text(
                  'Payslip Upload',
                  style: AppTheme.headlineMedium.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                ),
                const SizedBox(height: AppTheme.spacingSM),
                GestureDetector(
                  onTap: _pickPayslip,
                  child: ModernCard(
                    padding: const EdgeInsets.all(AppTheme.spacingMD),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(AppTheme.spacingSM),
                          decoration: BoxDecoration(
                            color: AppTheme.primaryColor.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(AppTheme.radiusSM),
                          ),
                          child: Icon(
                            Icons.picture_as_pdf_rounded,
                            color: AppTheme.primaryColor,
                            size: 24,
                          ),
                        ),
                        const SizedBox(width: AppTheme.spacingMD),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                _payslipFile?.path.split('/').last ??
                                    'Tap to upload payslip (PDF only)',
                                style: AppTheme.bodyMedium.copyWith(
                                  color: _payslipFile != null
                                      ? (isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight)
                                      : (isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight),
                                ),
                              ),
                              if (_payslipFile != null)
                                Text(
                                  'PDF file selected',
                                  style: AppTheme.bodySmall.copyWith(
                                    color: AppTheme.success,
                                  ),
                                ),
                            ],
                          ),
                        ),
                        Icon(
                          Icons.chevron_right_rounded,
                          color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: AppTheme.spacingLG),

                // Salary Validation Section
                if (_salaryValidation != null)
                  ModernCard(
                    padding: const EdgeInsets.all(AppTheme.spacingMD),
                    backgroundColor: _salaryValidation!.canAfford
                        ? AppTheme.success.withOpacity(0.1)
                        : AppTheme.error.withOpacity(0.1),
                    child: Row(
                      children: [
                        Icon(
                          _salaryValidation!.canAfford
                              ? Icons.check_circle_rounded
                              : Icons.cancel_rounded,
                          color: _salaryValidation!.canAfford
                              ? AppTheme.success
                              : AppTheme.error,
                          size: 24,
                        ),
                        const SizedBox(width: AppTheme.spacingMD),
                        Expanded(
                          child: Text(
                            _salaryValidation!.canAfford
                                ? 'Salary validation passed'
                                : 'Salary validation failed',
                            style: AppTheme.headlineMedium.copyWith(
                              color: _salaryValidation!.canAfford
                                  ? AppTheme.success
                                  : AppTheme.error,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                if (_salaryValidation != null) const SizedBox(height: AppTheme.spacingMD),

                // Validate Salary Button
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: _isValidatingSalary ? null : _validateSalary,
                    icon: _isValidatingSalary
                        ? SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              valueColor: AlwaysStoppedAnimation<Color>(
                                AppTheme.primaryColor,
                              ),
                            ),
                          )
                        : Icon(
                            Icons.verified_rounded,
                            color: AppTheme.primaryColor,
                          ),
                    label: Text(
                      'Validate Salary Capacity',
                      style: AppTheme.labelLarge.copyWith(
                        color: AppTheme.primaryColor,
                      ),
                    ),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: AppTheme.spacingMD),
                      side: BorderSide(color: AppTheme.primaryColor),
                    ),
                  ),
                ),
                const SizedBox(height: AppTheme.spacingLG),

                // Guarantors Section
                Text(
                  'Guarantors',
                  style: AppTheme.headlineLarge.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                ),
                const SizedBox(height: AppTheme.spacingMD),
                _buildGuarantorCard(context, _guarantor1, () => _selectGuarantor(1), 1),
                const SizedBox(height: AppTheme.spacingMD),
                _buildGuarantorCard(context, _guarantor2, () => _selectGuarantor(2), 2),
                const SizedBox(height: AppTheme.spacingXL),

                // Submit Button
                PrimaryButton(
                  text: 'Send to Guarantors',
                  isLoading: _isLoading,
                  onPressed: _submitLoanRequest,
                  width: double.infinity,
                  icon: Icons.send_rounded,
                ),
                const SizedBox(height: AppTheme.spacingXL),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildGuarantorCard(BuildContext context,
      MemberSearchResult? guarantor, VoidCallback onTap, int number) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return ModernCard(
      padding: const EdgeInsets.all(AppTheme.spacingMD),
      onTap: onTap,
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: AppTheme.primaryColor.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                number.toString(),
                style: AppTheme.headlineMedium.copyWith(
                  color: AppTheme.primaryColor,
                ),
              ),
            ),
          ),
          const SizedBox(width: AppTheme.spacingMD),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  guarantor?.fullName ?? 'Tap to select guarantor',
                  style: AppTheme.bodyLarge.copyWith(
                    color: guarantor != null
                        ? (isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight)
                        : (isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight),
                  ),
                ),
                if (guarantor != null)
                  Text(
                    guarantor.coopId,
                    style: AppTheme.bodySmall.copyWith(
                      color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                    ),
                  ),
              ],
            ),
          ),
          Icon(
            Icons.chevron_right_rounded,
                          color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
          ),
        ],
      ),
    );
  }
}
