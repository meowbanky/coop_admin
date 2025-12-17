import 'package:flutter/material.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/primary_button.dart';
import 'package:lottie/lottie.dart';

class WelcomeScreen extends StatefulWidget {
  const WelcomeScreen({super.key});

  @override
  State<WelcomeScreen> createState() => _WelcomeScreenState();
}

class _WelcomeScreenState extends State<WelcomeScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      duration: AppTheme.animationSlow,
      vsync: this,
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _animationController,
        curve: const Interval(0.0, 0.6, curve: Curves.easeOut),
      ),
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.3),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(
        parent: _animationController,
        curve: const Interval(0.2, 1.0, curve: Curves.easeOutCubic),
      ),
    );

    _animationController.forward();
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
      body: SafeArea(
        child: FadeTransition(
          opacity: _fadeAnimation,
          child: SlideTransition(
            position: _slideAnimation,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppTheme.spacingLG),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Illustration with animation
                  Expanded(
                    flex: 3,
                    child: Center(
                      child: Hero(
                        tag: 'welcome_illustration',
                        child: Builder(
                          builder: (context) {
                            try {
                              return Lottie.asset(
                                'assets/lottie/animation.json',
                                width: 280,
                                height: 280,
                                fit: BoxFit.contain,
                                repeat: true,
                                animate: true,
                                errorBuilder: (context, error, stackTrace) {
                                  debugPrint('Lottie animation error: $error');
                                  return Icon(
                                    Icons.account_balance_wallet,
                                    size: 120,
                                    color: AppTheme.primaryColor,
                                  );
                                },
                              );
                            } catch (e) {
                              debugPrint('Error loading Lottie: $e');
                              return Icon(
                                Icons.account_balance_wallet,
                                size: 120,
                                color: AppTheme.primaryColor,
                              );
                            }
                          },
                        ),
                      ),
                    ),
                  ),

                  // Welcome Text
                  Expanded(
                    flex: 2,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'Welcome to\nOOUTH Cooperative',
                          style: AppTheme.displayLarge.copyWith(
                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                            height: 1.2,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: AppTheme.spacingMD),
                        Text(
                          'Your financial community for savings, loans, and growth together.',
                          style: AppTheme.bodyLarge.copyWith(
                            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            height: 1.5,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),

                  // Get Started Button
                  Expanded(
                    flex: 1,
                    child: Center(
                      child: PrimaryButton(
                        text: 'Get Started',
                        icon: Icons.arrow_forward_rounded,
                        width: double.infinity,
                        onPressed: () {
                          Navigator.pushNamed(context, '/login');
                        },
                      ),
                    ),
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
