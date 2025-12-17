// File: lib/features/profile/presentation/screens/bank_account_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/main_layout.dart';
import '../../data/services/profile_service.dart';

class BankAccountScreen extends StatefulWidget {
  const BankAccountScreen({Key? key}) : super(key: key);

  @override
  State<BankAccountScreen> createState() => _BankAccountScreenState();
}

class _BankAccountScreenState extends State<BankAccountScreen> {
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = true;
  String? _selectedBank;
  final TextEditingController _accountNumberController =
      TextEditingController();
  List<Map<String, dynamic>> _banks = [];
  Map<String, dynamic>? _currentBankDetails;

  final ProfileService _profileService = ProfileService();

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      await Future.wait([
        _loadBanks(),
        _loadCurrentBankDetails(),
      ]);
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _loadBanks() async {
    try {
      final result = await _profileService.getBanksList();
      if (result['success']) {
        if (mounted) {
          setState(() {
            _banks = List<Map<String, dynamic>>.from(result['data']);
          });
        }
      } else {
        _showError(result['message'] ?? 'Failed to load banks');
      }
    } catch (e) {
      _showError('Error loading banks: $e');
    }
  }

  Future<void> _loadCurrentBankDetails() async {
    try {
      final result = await _profileService.getBankAccount();
      if (result['success']) {
        if (mounted) {
          setState(() {
            _currentBankDetails = result['data'];
            _selectedBank = _currentBankDetails?['bank_code'];
            _accountNumberController.text =
                _currentBankDetails?['AccountNo'] ?? '';
          });
        }
      } else {
        _showError(result['message'] ?? 'Failed to load bank details');
      }
    } catch (e) {
      _showError('Error loading bank details: $e');
    }
  }

  Future<void> _saveBankDetails() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    try {
      final result = await _profileService.updateBankAccount({
        'bank_sort_code': _selectedBank,
        'account_number': _accountNumberController.text,
      });

      if (result['success']) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Bank details updated successfully')),
        );
        Navigator.pop(context, true);
      } else {
        _showError(result['message'] ?? 'Failed to update bank details');
      }
    } catch (e) {
      _showError('Error saving bank details: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    if (_isLoading) {
      return Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        body: Center(
          child: CircularProgressIndicator(
            color: AppTheme.primaryColor,
          ),
        ),
      );
    }

    return MainLayout(
      currentIndex: 4,
      body: Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        appBar: AppBar(
          backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
          title: Text(
            'Bank Account Details',
            style: AppTheme.headlineLarge.copyWith(
              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
            ),
          ),
          elevation: 0,
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Select Bank',
                  style: AppTheme.headlineMedium.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                ),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  value: _selectedBank,
                  decoration: InputDecoration(
                    filled: true,
                    fillColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: BorderSide(
                        color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                      ),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: BorderSide(
                        color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                      ),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: const BorderSide(
                        color: AppTheme.primaryColor,
                        width: 2,
                      ),
                    ),
                  ),
                  dropdownColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                  style: AppTheme.bodyMedium.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                  items: _banks.map((bank) {
                    return DropdownMenuItem<String>(
                      value: bank['bank_code'],
                      child: Text(
                        bank['Bank_Name'],
                        style: AppTheme.bodyMedium.copyWith(
                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                        ),
                      ),
                    );
                  }).toList(),
                  onChanged: (value) {
                    setState(() => _selectedBank = value);
                  },
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please select a bank';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 20),
                Text(
                  'Account Number',
                  style: AppTheme.headlineMedium.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                ),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _accountNumberController,
                  style: AppTheme.bodyLarge.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                  decoration: InputDecoration(
                    filled: true,
                    fillColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: BorderSide(
                        color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                      ),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: BorderSide(
                        color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                      ),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: const BorderSide(color: AppTheme.primaryColor, width: 2),
                    ),
                  ),
                  keyboardType: TextInputType.number,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(10),
                  ],
                  validator: (value) {
                    if (value == null || value.isEmpty) {
                      return 'Please enter account number';
                    }
                    if (value.length != 10) {
                      return 'Account number must be 10 digits';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 30),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _saveBankDetails,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isDark ? AppTheme.cardDark : Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 15),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    child: Text(
                      _currentBankDetails != null ? 'Update' : 'Save',
                      style: TextStyle(
                        color: AppTheme.primaryColor,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
