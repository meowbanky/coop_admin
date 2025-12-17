import 'package:flutter/material.dart';
import 'package:onesignal_flutter/onesignal_flutter.dart';
import 'package:local_auth/local_auth.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/modern_card.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});
  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen>
    with SingleTickerProviderStateMixin {
  bool _notificationsEnabled = true;
  bool _biometricEnabled = false;
  final LocalAuthentication _localAuth = LocalAuthentication();
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _checkNotificationStatus();
    _checkBiometricStatus();
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

  Future<void> _checkNotificationStatus() async {
    final deviceState = await OneSignal.User.pushSubscription;
    setState(() {
      _notificationsEnabled = deviceState.optedIn ?? false;
    });
  }

  Future<void> _toggleNotifications(bool value) async {
    try {
      if (value) {
        final pushSubscription = await OneSignal.User.pushSubscription;
        final bool hasPermission = pushSubscription.optedIn ?? false;

        if (hasPermission) {
          await OneSignal.login(OneSignal.User.pushSubscription.id ?? '');
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: const Text('Notifications enabled'),
              backgroundColor: AppTheme.success,
            ),
          );
        } else {
          bool permissionGranted =
              await OneSignal.Notifications.requestPermission(true);

          if (permissionGranted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Notifications enabled successfully'),
                backgroundColor: AppTheme.success,
              ),
            );
          } else {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Please enable notifications in device settings'),
                backgroundColor: AppTheme.warning,
              ),
            );
            setState(() {
              _notificationsEnabled = false;
            });
            return;
          }
        }
      } else {
        await OneSignal.Notifications.clearAll();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Notifications disabled'),
            backgroundColor: AppTheme.info,
          ),
        );
      }

      setState(() {
        _notificationsEnabled = value;
      });
    } catch (e) {
      print('Error toggling notifications: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Failed to update notification settings'),
          backgroundColor: AppTheme.error,
        ),
      );
      setState(() {
        _notificationsEnabled = !value;
      });
    }
  }

  Future<void> _checkBiometricStatus() async {
    try {
      final canCheckBiometrics = await _localAuth.canCheckBiometrics;
      if (!canCheckBiometrics) {
        setState(() {
          _biometricEnabled = false;
        });
        return;
      }

      final availableBiometrics = await _localAuth.getAvailableBiometrics();
      if (availableBiometrics.isEmpty) {
        setState(() {
          _biometricEnabled = false;
        });
        return;
      }

      final bool isEnabled = await _getBiometricPreference();
      setState(() {
        _biometricEnabled = isEnabled;
      });
    } catch (e) {
      print('Error checking biometric status: $e');
      setState(() {
        _biometricEnabled = false;
      });
    }
  }

  Future<void> _toggleBiometric(bool value) async {
    try {
      if (value) {
        final canCheckBiometrics = await _localAuth.canCheckBiometrics;
        final canAuthenticate = await _localAuth.isDeviceSupported();

        if (!canCheckBiometrics || !canAuthenticate) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text(
                  'Biometric authentication not available on this device'),
              backgroundColor: AppTheme.warning,
            ),
          );
          setState(() {
            _biometricEnabled = false;
          });
          return;
        }

        final authenticated = await _localAuth.authenticate(
          localizedReason: 'Authenticate to enable biometric login',
          options: const AuthenticationOptions(
            stickyAuth: true,
            biometricOnly: true,
          ),
        );

        if (authenticated) {
          await _saveBiometricPreference(true);
          setState(() {
            _biometricEnabled = true;
          });
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Biometric authentication enabled'),
              backgroundColor: AppTheme.success,
            ),
          );
        } else {
          setState(() {
            _biometricEnabled = false;
          });
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Biometric authentication failed'),
              backgroundColor: AppTheme.error,
            ),
          );
        }
      } else {
        await _saveBiometricPreference(false);
        setState(() {
          _biometricEnabled = false;
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Biometric authentication disabled'),
            backgroundColor: AppTheme.info,
          ),
        );
      }
    } catch (e) {
      print('Error toggling biometric: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Failed to update biometric settings'),
          backgroundColor: AppTheme.error,
        ),
      );
      setState(() {
        _biometricEnabled = !value;
      });
    }
  }

  Future<bool> _getBiometricPreference() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool('biometric_enabled') ?? false;
  }

  Future<void> _saveBiometricPreference(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('biometric_enabled', value);
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
          icon: Icon(
            Icons.arrow_back_rounded,
            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          'Settings',
          style: AppTheme.headlineLarge.copyWith(
            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
          ),
        ),
      ),
      body: FadeTransition(
        opacity: _fadeAnimation,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppTheme.spacingMD),
          child: Column(
            children: [
              ModernCard(
                padding: const EdgeInsets.all(AppTheme.spacingLG),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Preferences',
                      style: AppTheme.headlineLarge.copyWith(
                        color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                      ),
                    ),
                    const SizedBox(height: AppTheme.spacingLG),
                    _buildSwitchTile(
                      context: context,
                      icon: Icons.notifications_outlined,
                      title: 'Push Notifications',
                      subtitle: 'Receive push notifications',
                      value: _notificationsEnabled,
                      onChanged: _toggleNotifications,
                    ),
                    const SizedBox(height: AppTheme.spacingMD),
                    Divider(
                      color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                    ),
                    const SizedBox(height: AppTheme.spacingMD),
                    _buildSwitchTile(
                      context: context,
                      icon: Icons.fingerprint_rounded,
                      title: 'Biometric Authentication',
                      subtitle: 'Use fingerprint or face ID to login',
                      value: _biometricEnabled,
                      onChanged: _toggleBiometric,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSwitchTile({
    required BuildContext context,
    required IconData icon,
    required String title,
    required String subtitle,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(AppTheme.spacingSM),
          decoration: BoxDecoration(
            color: AppTheme.primaryColor.withOpacity(0.1),
            borderRadius: BorderRadius.circular(AppTheme.radiusSM),
          ),
          child: Icon(
            icon,
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
                title,
                style: AppTheme.headlineMedium.copyWith(
                  color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                ),
              ),
              const SizedBox(height: AppTheme.spacingXS),
              Text(
                subtitle,
                style: AppTheme.bodySmall.copyWith(
                  color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                ),
              ),
            ],
          ),
        ),
        Switch(
          value: value,
          onChanged: onChanged,
          activeColor: AppTheme.primaryColor,
        ),
      ],
    );
  }
}
