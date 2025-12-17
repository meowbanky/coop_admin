// lib/features/auth/presentation/screens/forgot_password_screen.dart
import 'package:flutter/material.dart';
import 'dart:async';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';
import '../../data/services/auth_service.dart';
import '../../data/models/user_search.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen>
    with SingleTickerProviderStateMixin {
  final TextEditingController _searchController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _otpController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _confirmPasswordController =
      TextEditingController();
  final _formKey = GlobalKey<FormState>();

  final _authService = AuthService();
  bool _isLoading = false;
  String _errorMessage = '';
  List<UserSearch> _searchResults = [];
  bool _otpSent = false;
  bool _otpVerified = false;
  Timer? _debounceTimer;
  bool _isPasswordVisible = false;
  bool _isConfirmPasswordVisible = false;
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
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
    _searchController.dispose();
    _emailController.dispose();
    _otpController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _debounceTimer?.cancel();
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _searchUsers(String query) async {
    final trimmedQuery = query.trim();
    if (trimmedQuery.length < 3) {
      setState(() {
        _searchResults = [];
        _isLoading = false;
        _errorMessage = '';
      });
      return;
    }

    _debounceTimer?.cancel();
    _debounceTimer = Timer(const Duration(milliseconds: 300), () async {
      if (!mounted) return;
      
      setState(() {
        _isLoading = true;
        _errorMessage = '';
      });
      
      try {
        final results = await _authService.searchUsers(trimmedQuery);
        if (mounted) {
          debugPrint('Updating UI with ${results.length} results');
          setState(() {
            _searchResults = results;
            _isLoading = false;
            if (results.isEmpty && trimmedQuery.length >= 3) {
              _errorMessage = 'No users found. Please try a different search term.';
            } else {
              _errorMessage = '';
            }
          });
        }
      } catch (e) {
        debugPrint('Search error: $e');
        if (mounted) {
          setState(() {
            _searchResults = [];
            _isLoading = false;
            _errorMessage = 'Error searching users. Please try again.';
          });
        }
      }
    });
  }

  Future<void> _requestOTP() async {
    if (_emailController.text.isEmpty) {
      setState(() => _errorMessage = 'Please select a user first');
      return;
    }

    setState(() => _isLoading = true);
    final result = await _authService.requestOTP(_emailController.text);
    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _otpSent = true;
          _errorMessage = '';
        } else {
          _errorMessage = result['message'];
        }
      });
    }
  }

  Future<void> _verifyOTP() async {
    if (_otpController.text.length != 6) {
      setState(() => _errorMessage = 'Please enter a valid 6-digit OTP');
      return;
    }

    setState(() => _isLoading = true);
    final result = await _authService.verifyOTP(
      _emailController.text,
      _otpController.text,
    );

    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _otpVerified = true;
          _errorMessage = '';
        } else {
          _errorMessage = result['message'];
        }
      });
    }
  }

  Future<void> _resetPassword() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    final result = await _authService.resetPassword(
      _emailController.text,
      _otpController.text,
      _passwordController.text,
    );

    if (mounted) {
      if (result['success']) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('Password reset successfully'),
            backgroundColor: AppTheme.success,
          ),
        );
        Navigator.pop(context);
      } else {
        setState(() {
          _isLoading = false;
          _errorMessage = result['message'];
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
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
          'Reset Password',
          style: AppTheme.headlineLarge.copyWith(color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight),
        ),
      ),
      body: FadeTransition(
        opacity: _fadeAnimation,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppTheme.spacingLG),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header Icon
                Center(
                  child: Container(
                    width: 100,
                    height: 100,
                    decoration: BoxDecoration(
                      color: AppTheme.primaryColor.withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      Icons.lock_reset_rounded,
                      color: AppTheme.primaryColor,
                      size: 50,
                    ),
                  ),
                ),
                const SizedBox(height: AppTheme.spacingXL),
                  Text(
                  'Reset Your Password',
                  style: AppTheme.displayMedium.copyWith(
                    color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: AppTheme.spacingSM),
                Text(
                  'Enter your details to reset your password',
                  style: AppTheme.bodyMedium.copyWith(
                    color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: AppTheme.spacingXL),
                if (!_otpSent) ...[
                  TextFormField(
                    controller: _searchController,
                    style: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                    onChanged: _searchUsers,
                    decoration: InputDecoration(
                      labelText: 'Search by name...',
                      prefixIcon: Icon(Icons.search_rounded,
                          color: AppTheme.primaryColor),
                    ),
                  ),
                  if (_isLoading)
                    const Padding(
                      padding: EdgeInsets.all(AppTheme.spacingMD),
                      child: Center(child: CircularProgressIndicator()),
                    ),
                  if (!_isLoading && _searchResults.isNotEmpty)
                    ModernCard(
                      margin: const EdgeInsets.only(top: AppTheme.spacingMD),
                      padding: EdgeInsets.zero,
                      child: ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: _searchResults.length,
                        itemBuilder: (context, index) {
                          final user = _searchResults[index];
                          return ListTile(
                            leading: Icon(
                              Icons.person_rounded,
                              color: AppTheme.primaryColor,
                            ),
                            title: Text(
                              user.fullName,
                              style: AppTheme.bodyMedium.copyWith(
                                color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                              ),
                            ),
                            subtitle: user.coopId.isNotEmpty
                                ? Text(
                                    'ID: ${user.coopId}',
                                    style: AppTheme.bodySmall.copyWith(
                                      color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                    ),
                                  )
                                : null,
                            onTap: () {
                              setState(() {
                                _emailController.text = user.email;
                                _searchResults = [];
                                _searchController.clear();
                                _errorMessage = '';
                              });
                            },
                          );
                        },
                      ),
                    ),
                  const SizedBox(height: AppTheme.spacingMD),
                  TextFormField(
                    controller: _emailController,
                    readOnly: true,
                    style: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                    decoration: InputDecoration(
                      labelText: 'Email address',
                      prefixIcon: Icon(Icons.email_outlined,
                          color: AppTheme.primaryColor),
                    ),
                  ),
                  const SizedBox(height: AppTheme.spacingLG),
                  PrimaryButton(
                    text: 'Send OTP',
                    isLoading: _isLoading,
                    icon: Icons.send_rounded,
                    onPressed: _emailController.text.isNotEmpty ? _requestOTP : null,
                    width: double.infinity,
                  ),
                ],
                if (_otpSent && !_otpVerified) ...[
                  TextFormField(
                    controller: _otpController,
                    keyboardType: TextInputType.number,
                    maxLength: 6,
                    style: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                    decoration: InputDecoration(
                      labelText: 'Enter OTP',
                      prefixIcon: Icon(Icons.lock_outlined,
                          color: AppTheme.primaryColor),
                    ),
                    validator: (value) {
                      if (value == null || value.length != 6) {
                        return 'Please enter a valid 6-digit OTP';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: AppTheme.spacingLG),
                  PrimaryButton(
                    text: 'Verify OTP',
                    isLoading: _isLoading,
                    icon: Icons.verified_rounded,
                    onPressed: _verifyOTP,
                    width: double.infinity,
                  ),
                ],
                if (_otpVerified) ...[
                  TextFormField(
                    controller: _passwordController,
                    obscureText: !_isPasswordVisible,
                    style: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                    decoration: InputDecoration(
                      labelText: 'New Password',
                      prefixIcon: Icon(Icons.lock_outline_rounded,
                          color: AppTheme.primaryColor),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _isPasswordVisible
                              ? Icons.visibility_off_rounded
                              : Icons.visibility_rounded,
                          color: AppTheme.textSecondaryLight,
                        ),
                        onPressed: () {
                          setState(() {
                            _isPasswordVisible = !_isPasswordVisible;
                          });
                        },
                      ),
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please enter a new password';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: AppTheme.spacingMD),
                  TextFormField(
                    controller: _confirmPasswordController,
                    obscureText: !_isConfirmPasswordVisible,
                    style: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                    decoration: InputDecoration(
                      labelText: 'Confirm New Password',
                      prefixIcon: Icon(Icons.lock_outline_rounded,
                          color: AppTheme.primaryColor),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _isConfirmPasswordVisible
                              ? Icons.visibility_off_rounded
                              : Icons.visibility_rounded,
                          color: AppTheme.textSecondaryLight,
                        ),
                        onPressed: () {
                          setState(() {
                            _isConfirmPasswordVisible =
                                !_isConfirmPasswordVisible;
                          });
                        },
                      ),
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please confirm your password';
                      }
                      if (value != _passwordController.text) {
                        return 'Passwords do not match';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: AppTheme.spacingLG),
                  PrimaryButton(
                    text: 'Reset Password',
                    isLoading: _isLoading,
                    icon: Icons.check_circle_rounded,
                    onPressed: _resetPassword,
                    width: double.infinity,
                  ),
                ],
                if (_errorMessage.isNotEmpty) ...[
                  const SizedBox(height: AppTheme.spacingMD),
                  ModernCard(
                    padding: const EdgeInsets.all(AppTheme.spacingMD),
                    backgroundColor: AppTheme.error.withOpacity(0.1),
                    child: Row(
                      children: [
                        Icon(Icons.error_outline_rounded, color: AppTheme.error),
                        const SizedBox(width: AppTheme.spacingMD),
                        Expanded(
                          child: Text(
                            _errorMessage,
                            style: AppTheme.bodyMedium.copyWith(
                              color: AppTheme.error,
                            ),
                          ),
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
    );
  }
}

