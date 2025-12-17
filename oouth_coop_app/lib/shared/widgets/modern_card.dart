import 'package:flutter/material.dart';
import '../../config/theme/app_theme.dart';

/// Modern Card Widget with consistent styling
class ModernCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry? padding;
  final EdgeInsetsGeometry? margin;
  final Color? backgroundColor;
  final VoidCallback? onTap;
  final BorderRadius? borderRadius;
  final BoxShadow? shadow;

  const ModernCard({
    super.key,
    required this.child,
    this.padding,
    this.margin,
    this.backgroundColor,
    this.onTap,
    this.borderRadius,
    this.shadow,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = backgroundColor ?? 
        (isDark ? AppTheme.cardDark : AppTheme.cardLight);
    
    Widget card = Container(
      padding: padding ?? const EdgeInsets.all(AppTheme.spacingLG),
      margin: margin ?? const EdgeInsets.all(AppTheme.spacingMD),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: borderRadius ?? BorderRadius.circular(AppTheme.radiusMD),
        boxShadow: shadow != null
            ? [shadow!]
            : [
                BoxShadow(
                  color: Colors.black.withOpacity(isDark ? 0.3 : 0.05),
                  blurRadius: 8,
                  offset: const Offset(0, 2),
                ),
              ],
      ),
      child: child,
    );

    if (onTap != null) {
      return InkWell(
        onTap: onTap,
        borderRadius: borderRadius ?? BorderRadius.circular(AppTheme.radiusMD),
        child: card,
      );
    }

    return card;
  }
}

