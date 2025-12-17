import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../../../../config/theme/app_theme.dart';
import '../../data/services/auth_service.dart';
import 'package:local_auth/local_auth.dart';
import 'package:flutter/services.dart';
import '../../../../utils/snackbar_helper.dart';
import '../../../../shared/widgets/primary_button.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen>
    with SingleTickerProviderStateMixin {
  final TextEditingController _usernameController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final _authService = AuthService();
  final LocalAuthentication _localAuth = LocalAuthentication();
  final _formKey = GlobalKey<FormState>();
  bool _rememberMe = true;
  bool _isPasswordVisible = false;
  bool _isLoading = false;
  String _errorMessage = '';
  bool _canUseBiometrics = false;
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _checkBiometrics();
    _loadSavedCredentials();
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
    _usernameController.dispose();
    _passwordController.dispose();
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _loadSavedCredentials() async {
    final prefs = await SharedPreferences.getInstance();
    final savedUsername = prefs.getString('saved_username');
    final savedPassword = prefs.getString('saved_password');
    final rememberMe = prefs.getBool('remember_me') ?? true;

    setState(() {
      _rememberMe = rememberMe;
    });

    if (savedUsername != null && savedPassword != null && rememberMe) {
      setState(() {
        _usernameController.text = savedUsername;
        _passwordController.text = savedPassword;
      });
    }
  }

  Future<void> _checkBiometrics() async {
    try {
      bool canUseBiometrics = await _localAuth.canCheckBiometrics;
      if (!canUseBiometrics) {
        setState(() {
          _canUseBiometrics = false;
        });
        return;
      }

      final prefs = await SharedPreferences.getInstance();
      bool isBiometricEnabled = prefs.getBool('biometric_enabled') ?? false;

      setState(() {
        _canUseBiometrics = canUseBiometrics && isBiometricEnabled;
      });
    } catch (e) {
      debugPrint('Error checking biometrics: $e');
      setState(() {
        _canUseBiometrics = false;
      });
    }
  }

  Future<void> _handleBiometricAuth() async {
    try {
      final canCheckBiometrics = await _localAuth.canCheckBiometrics;
      final biometricTypes = await _localAuth.getAvailableBiometrics();

      if (!canCheckBiometrics || biometricTypes.isEmpty) {
        _showError('Biometric authentication not available');
        return;
      }

      final prefs = await SharedPreferences.getInstance();
      final savedUsername = prefs.getString('saved_username');
      final savedPassword = prefs.getString('saved_password');

      if (savedUsername == null || savedPassword == null) {
        _showError('Please login with credentials first');
        return;
      }

      final bool didAuthenticate = await _localAuth.authenticate(
        localizedReason: 'Authenticate to login',
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: false,
          useErrorDialogs: true,
        ),
      );

      if (didAuthenticate) {
        _usernameController.text = savedUsername;
        _passwordController.text = savedPassword;
        await _handleLogin();
      }
    } on PlatformException catch (e) {
      debugPrint('Biometric error: ${e.code} - ${e.message}');
      _showError('Biometric error: ${e.message}');
    } catch (e) {
      debugPrint('General error: $e');
      _showError('Authentication failed');
    }
  }

  void _showError(String message) {
    setState(() {
      _errorMessage = message;
    });
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      final result = await _authService.login(
        _usernameController.text,
        _passwordController.text,
      );

      if (result['success']) {
        SnackBarHelper.showCustomSnackBar(
          context: context,
          message: 'Login successful!',
          isError: false,
        );
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', result['token']);
        await prefs.setString('CoopID', result['user']['CoopID']);
        await prefs.setString('user_data', json.encode(result['user']));
        await prefs.setString('last_username', _usernameController.text);
        await prefs.setString('last_password', _passwordController.text);

        if (_rememberMe) {
          await prefs.setString('saved_username', _usernameController.text);
          await prefs.setString('saved_password', _passwordController.text);
          await prefs.setBool('remember_me', true);
        } else {
          await prefs.remove('saved_username');
          await prefs.remove('saved_password');
          await prefs.setBool('remember_me', false);
        }

        if (mounted) {
          Navigator.pushNamedAndRemoveUntil(
            context,
            '/home',
            (route) => false,
          );
        }
      } else {
        SnackBarHelper.showCustomSnackBar(
          context: context,
          message: result['message'] ?? 'Login failed',
          isError: true,
        );
      }
    } catch (e) {
      _showError('An error occurred. Please try again.');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
      body: FadeTransition(
        opacity: _fadeAnimation,
        child: SafeArea(
        child: SingleChildScrollView(
            padding: const EdgeInsets.all(AppTheme.spacingLG),
            child: Form(
              key: _formKey,
            child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                  const SizedBox(height: AppTheme.spacingXL),
                  
                  // Logo/Icon
                Center(
                    child: Container(
                      width: 80,
                      height: 80,
                      decoration: BoxDecoration(
                        color: AppTheme.primaryColor.withOpacity(0.1),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        Icons.account_balance_rounded,
                        size: 40,
                        color: AppTheme.primaryColor,
                      ),
                    ),
                  ),
                  
                  const SizedBox(height: AppTheme.spacingLG),
                  
                  // Title
                  Text(
                    'Welcome Back',
                    style: AppTheme.displayMedium.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                  ),
                    textAlign: TextAlign.center,
                ),
                  
                  const SizedBox(height: AppTheme.spacingSM),
                  
                Text(
                    'Sign in to access your account',
                    style: AppTheme.bodyMedium.copyWith(
                      color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  
                  const SizedBox(height: AppTheme.spacingXL),
                  
                  // Username Field
                  TextFormField(
                  controller: _usernameController,
                    style: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                  decoration: InputDecoration(
                      labelText: 'Member ID / Email',
                      labelStyle: AppTheme.bodyMedium.copyWith(
                        color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                      ),
                      prefixIcon: Icon(
                        Icons.person_outline_rounded,
                        color: AppTheme.primaryColor,
                    ),
                    errorText: _errorMessage.isNotEmpty ? _errorMessage : null,
                    ),
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Please enter your CoopID or Mobile Number';
                      }
                      return null;
                    },
                ),
                  
                  const SizedBox(height: AppTheme.spacingMD),
                  
                  // Password Field
                  TextFormField(
                  controller: _passwordController,
                  obscureText: !_isPasswordVisible,
                    style: AppTheme.bodyLarge.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                  decoration: InputDecoration(
                      labelText: 'Password',
                      labelStyle: AppTheme.bodyMedium.copyWith(
                        color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                      ),
                      prefixIcon: Icon(
                        Icons.lock_outline_rounded,
                        color: AppTheme.primaryColor,
                    ),
                    suffixIcon: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                          if (_canUseBiometrics)
                          IconButton(
                              icon: Icon(
                                Icons.fingerprint_rounded,
                                color: AppTheme.primaryColor,
                            ),
                            onPressed: _handleBiometricAuth,
                          ),
                        IconButton(
                          icon: Icon(
                            _isPasswordVisible
                                  ? Icons.visibility_off_rounded
                                  : Icons.visibility_rounded,
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                          ),
                          onPressed: () {
                            setState(() {
                              _isPasswordVisible = !_isPasswordVisible;
                            });
                          },
                        ),
                      ],
                    ),
                  ),
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Please enter your password';
                      }
                      return null;
                    },
                ),
                  
                  const SizedBox(height: AppTheme.spacingSM),
                  
                  // Remember Me & Forgot Password
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Checkbox(
                  value: _rememberMe,
                  onChanged: (bool? value) {
                    setState(() {
                      _rememberMe = value ?? false;
                    });
                  },
                            activeColor: AppTheme.primaryColor,
                          ),
                          Text(
                            'Remember Me',
                            style: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                          ),
                        ],
                ),
                    TextButton(
                      onPressed: () {
                          Navigator.pushNamed(context, '/forgot-password');
                      },
                      child: Text(
                          'Forgot Password?',
                          style: AppTheme.labelMedium.copyWith(
                            color: AppTheme.primaryColor,
                          ),
                        ),
                      ),
                    ],
                  ),
                  
                  const SizedBox(height: AppTheme.spacingLG),
                  
                  // Login Button
                  PrimaryButton(
                    text: 'Log In',
                    isLoading: _isLoading,
                    onPressed: _handleLogin,
                    width: double.infinity,
                  ),
                  
                  const SizedBox(height: AppTheme.spacingMD),
                  
                  // Sign Up Link
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        "Don't have an account? ",
                        style: AppTheme.bodyMedium.copyWith(
                          color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                      ),
                    ),
                    TextButton(
                      onPressed: () {
                          Navigator.pushNamed(context, '/signup');
                      },
                      child: Text(
                          'Sign Up',
                          style: AppTheme.labelMedium.copyWith(
                            color: AppTheme.primaryColor,
                            fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
