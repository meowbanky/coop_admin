import 'dart:ui';
import 'package:flutter/material.dart';
import '../../config/theme/app_theme.dart';

class MainLayout extends StatelessWidget {
  final Widget body;
  final int currentIndex;

  const MainLayout({
    super.key,
    required this.body,
    required this.currentIndex,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor:
          isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
      body: body,
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: isDark
              ? AppTheme.backgroundDark.withOpacity(0.8)
              : AppTheme.backgroundLight.withOpacity(0.8),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(isDark ? 0.3 : 0.05),
              blurRadius: 20,
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: ClipRRect(
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
            child: Container(
              height: 80,
              padding:
                  const EdgeInsets.symmetric(horizontal: AppTheme.spacingSM),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  _buildNavItem(
                    context: context,
                    icon: Icons.dashboard_rounded,
                    label: 'Dashboard',
                    index: 0,
                    isSelected: currentIndex == 0,
                  ),
                  _buildNavItem(
                    context: context,
                    icon: Icons.history_rounded,
                    label: 'History',
                    index: 1,
                    isSelected: currentIndex == 1,
                  ),
                  _buildNavItem(
                    context: context,
                    icon: Icons.account_balance_wallet_rounded,
                    label: 'Loans',
                    index: 2,
                    isSelected: currentIndex == 2,
                  ),
                  _buildNavItem(
                    context: context,
                    icon: Icons.event_rounded,
                    label: 'Events',
                    index: 3,
                    isSelected: currentIndex == 3,
                  ),
                  _buildNavItem(
                    context: context,
                    icon: Icons.person_rounded,
                    label: 'Profile',
                    index: 4,
                    isSelected: currentIndex == 4,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildNavItem({
    required BuildContext context,
    required IconData icon,
    required String label,
    required int index,
    required bool isSelected,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final color = isSelected
        ? AppTheme.primaryColor
        : (isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight);

    return Expanded(
      child: InkWell(
        onTap: () => _handleNavigation(context, index),
        borderRadius: BorderRadius.circular(AppTheme.radiusSM),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedContainer(
              duration: AppTheme.animationFast,
              curve: Curves.easeInOut,
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: isSelected
                    ? AppTheme.primaryColor.withOpacity(0.1)
                    : Colors.transparent,
                borderRadius: BorderRadius.circular(AppTheme.radiusSM),
              ),
              child: Icon(
                icon,
                color: color,
                size: 24,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: AppTheme.labelSmall.copyWith(
                color: color,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _handleNavigation(BuildContext context, int index) {
    switch (index) {
      case 0:
        if (currentIndex != 0) {
          Navigator.pushReplacementNamed(context, '/home');
        }
        break;
      case 1:
        if (currentIndex != 1) {
          Navigator.pushReplacementNamed(context, '/products');
        }
        break;
      case 2:
        if (currentIndex != 2) {
          Navigator.pushReplacementNamed(context, '/loan-tracker');
        }
        break;
      case 3:
        if (currentIndex != 3) {
          Navigator.pushReplacementNamed(context, '/events');
        }
        break;
      case 4:
        if (currentIndex != 4) {
          Navigator.pushReplacementNamed(context, '/account');
        }
        break;
    }
  }
}
