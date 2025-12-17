// lib/features/auth/presentation/screens/signup_screen.dart
import 'package:flutter/material.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';
import '../../data/services/auth_service.dart';
import '../../data/models/user_search.dart';
import '../../../../utils/snackbar_helper.dart';
import 'dart:async';

class SignupScreen extends StatefulWidget {
  const SignupScreen({super.key});

  @override
  State<SignupScreen> createState() => _SignupScreenState();
}

class _SignupScreenState extends State<SignupScreen>
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
  UserSearch? _selectedUser;
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

  Future<void> _checkUserEmail(UserSearch user) async {
    setState(() => _isLoading = true);
    final result = await _authService.checkUserEmail(user.coopId);

    if (mounted) {
      if (result['hasEmail']) {
        SnackBarHelper.showCustomSnackBar(
          context: context,
          message: 'Account already exists. Redirecting to password reset...',
          isError: false,
        );
        Navigator.pushReplacementNamed(context, '/forgot-password');
      } else {
        setState(() {
          _selectedUser = user;
          _searchResults = [];
          _searchController.clear();
        });
      }
      setState(() => _isLoading = false);
    }
  }

  Future<void> _sendOTP() async {
    if (_emailController.text.isEmpty) {
      setState(() => _errorMessage = 'Please enter your email address');
      return;
    }

    setState(() => _isLoading = true);
    final result = await _authService.sendSignupOTP(_emailController.text);

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
    final result = await _authService.verifySignupOTP(
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

  Future<void> _createAccount() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    final result = await _authService.createAccount(
      _selectedUser!.coopId,
      _emailController.text,
      _otpController.text,
      _passwordController.text,
    );

    if (mounted) {
      if (result['success']) {
        SnackBarHelper.showCustomSnackBar(
          context: context,
          message: 'Account created successfully! Please login.',
          isError: false,
        );
        Navigator.pushReplacementNamed(context, '/login');
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
          'Create Account',
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
                if (_selectedUser == null) ...[
                  Text(
                    'Search for your account',
                    style: AppTheme.headlineMedium.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                  ),
                  const SizedBox(height: AppTheme.spacingMD),
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
                            onTap: () => _checkUserEmail(user),
                          );
                        },
                      ),
                    ),
                ],
                if (_selectedUser != null && !_otpSent) ...[
                  ModernCard(
                    padding: const EdgeInsets.all(AppTheme.spacingMD),
                    child: Row(
                      children: [
                        Icon(Icons.person_rounded, color: AppTheme.primaryColor),
                        const SizedBox(width: AppTheme.spacingMD),
                        Expanded(
                          child: Text(
                            _selectedUser!.fullName,
                            style: AppTheme.headlineMedium.copyWith(
                              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: AppTheme.spacingLG),
                  TextFormField(
                    controller: _emailController,
                    keyboardType: TextInputType.emailAddress,
                    style: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                    decoration: InputDecoration(
                      labelText: 'Enter your email address',
                      prefixIcon: Icon(Icons.email_outlined,
                          color: AppTheme.primaryColor),
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please enter your email';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: AppTheme.spacingLG),
                  PrimaryButton(
                    text: 'Send OTP',
                    isLoading: _isLoading,
                    icon: Icons.send_rounded,
                    onPressed: _sendOTP,
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
                      labelText: 'Create Password',
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
                        return 'Please enter a password';
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
                      labelText: 'Confirm Password',
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
                    text: 'Create Account',
                    isLoading: _isLoading,
                    icon: Icons.person_add_rounded,
                    onPressed: _createAccount,
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
