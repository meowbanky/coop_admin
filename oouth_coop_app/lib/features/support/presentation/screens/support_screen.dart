import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../config/routes/routes.dart';
import '../../../../shared/widgets/main_layout.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';

class SupportScreen extends StatefulWidget {
  const SupportScreen({super.key});

  @override
  State<SupportScreen> createState() => _SupportScreenState();
}

class _SupportScreenState extends State<SupportScreen>
    with SingleTickerProviderStateMixin {
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
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _launchWhatsApp(BuildContext context) async {
    const phone = "+2347039394218";
    const message = "Hello, I need assistance with OOUTH COOP App.";
    final encodedMessage = Uri.encodeComponent(message);

    final url = Uri.parse('whatsapp://send?phone=$phone&text=$encodedMessage');

    final connectivity = await Connectivity().checkConnectivity();
    if (connectivity == ConnectivityResult.none) {
      _showError(context, 'No internet connection. Please check your network.');
      return;
    }

    if (await launchUrl(url, mode: LaunchMode.externalApplication)) {
      return;
    }

    final webUrl = Uri.parse('https://wa.me/$phone?text=$encodedMessage');
    if (await launchUrl(webUrl, mode: LaunchMode.externalApplication)) {
      return;
    }

    if (context.mounted) {
      await _showInstallDialog(context);
    }
  }

  Future<void> _showInstallDialog(BuildContext context) async {
    return showDialog(
      context: context,
      builder: (context) {
        final isDark = Theme.of(context).brightness == Brightness.dark;
        return AlertDialog(
          backgroundColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
          title: Text(
            'WhatsApp Not Found',
            style: AppTheme.headlineMedium.copyWith(
              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
            ),
          ),
          content: Text(
            'Would you like to install WhatsApp to continue?',
            style: AppTheme.bodyMedium.copyWith(
              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text(
                'Cancel',
                style: AppTheme.labelMedium.copyWith(
                  color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                ),
              ),
            ),
            TextButton(
              onPressed: () {
                Navigator.pop(context);
                _launchAppStore(context);
              },
              child: Text(
                'Install',
                style: AppTheme.labelMedium.copyWith(color: AppTheme.primaryColor),
              ),
            ),
          ],
        );
      },
    );
  }

  Future<void> _launchAppStore(BuildContext context) async {
    const appStoreUrl = 'https://apps.apple.com/app/id310633997';
    const playStoreUrl =
        'https://play.google.com/store/apps/details?id=com.whatsapp';

    final url = Uri.parse(Theme.of(context).platform == TargetPlatform.iOS
        ? appStoreUrl
        : playStoreUrl);

    if (await canLaunchUrl(url)) {
      await launchUrl(url);
    } else if (context.mounted) {
      _showError(context, 'Could not open app store');
    }
  }

  void _showError(BuildContext context, String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: AppTheme.error,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return MainLayout(
      currentIndex: 3,
      body: Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        appBar: AppBar(
          backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
          elevation: 0,
          leading: IconButton(
            icon: Icon(
              Icons.arrow_back_rounded,
              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
            ),
            onPressed: () {
              if (Navigator.canPop(context)) {
                Navigator.pop(context);
              } else {
                Navigator.pushReplacementNamed(context, AppRoutes.account);
              }
            },
          ),
          title: Text(
            'Support',
            style: AppTheme.headlineLarge.copyWith(
              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
            ),
          ),
        ),
        body: FadeTransition(
          opacity: _fadeAnimation,
          child: SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(AppTheme.spacingLG),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const SizedBox(height: AppTheme.spacingXL),
                  // Icon
                  Container(
                    width: 120,
                    height: 120,
                    decoration: BoxDecoration(
                      color: const Color(0xFF25D366).withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      FontAwesomeIcons.whatsapp,
                      size: 60,
                      color: Color(0xFF25D366),
                    ),
                  ),
                  const SizedBox(height: AppTheme.spacingXL),
                  Text(
                    '24/7 WhatsApp Support',
                    style: AppTheme.displayMedium.copyWith(
                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: AppTheme.spacingMD),
                  Text(
                    'Get instant help from our support team\n'
                    'Average response time: 15 minutes',
                    style: AppTheme.bodyMedium.copyWith(
                      color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: AppTheme.spacingXL),
                  ModernCard(
                    padding: const EdgeInsets.all(AppTheme.spacingLG),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            Icon(
                              Icons.info_outline_rounded,
                              color: AppTheme.primaryColor,
                              size: 20,
                            ),
                            const SizedBox(width: AppTheme.spacingSM),
                            Expanded(
                              child: Text(
                                'Contact Information',
                                style: AppTheme.headlineMedium.copyWith(
                                  color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: AppTheme.spacingMD),
                        _buildContactRow(
                          Icons.phone_rounded,
                          'Phone',
                          '+234 703 939 4218',
                        ),
                        const SizedBox(height: AppTheme.spacingSM),
                        _buildContactRow(
                          Icons.access_time_rounded,
                          'Response Time',
                          '15 minutes average',
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: AppTheme.spacingLG),
                  PrimaryButton(
                    text: 'Start WhatsApp Chat',
                    icon: FontAwesomeIcons.whatsapp,
                    onPressed: () => _launchWhatsApp(context),
                    width: double.infinity,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildContactRow(IconData icon, String label, String value) {
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
