import 'package:flutter/material.dart';
import '../../config/theme/app_theme.dart';

/// Custom Progress Indicator Widget
class ProgressIndicatorCustom extends StatelessWidget {
  final double progress; // 0.0 to 1.0
  final double height;
  final Color? backgroundColor;
  final Color? progressColor;
  final String? label;
  final EdgeInsetsGeometry? padding;

  const ProgressIndicatorCustom({
    super.key,
    required this.progress,
    this.height = 8.0,
    this.backgroundColor,
    this.progressColor,
    this.label,
    this.padding,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = backgroundColor ??
        (isDark ? AppTheme.borderDark : AppTheme.borderLight);
    final progColor = progressColor ?? AppTheme.primaryColor;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (label != null) ...[
          Padding(
            padding: padding ?? EdgeInsets.zero,
            child: Text(
              label!,
              style: AppTheme.bodySmall.copyWith(
                color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
              ),
            ),
          ),
          const SizedBox(height: AppTheme.spacingXS),
        ],
        Container(
          height: height,
          decoration: BoxDecoration(
            color: bgColor,
            borderRadius: BorderRadius.circular(AppTheme.radiusFull),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(AppTheme.radiusFull),
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: 0.0, end: progress.clamp(0.0, 1.0)),
              duration: AppTheme.animationMedium,
              curve: Curves.easeOutCubic,
              builder: (context, value, child) {
                return LinearProgressIndicator(
                  value: value,
                  backgroundColor: Colors.transparent,
                  valueColor: AlwaysStoppedAnimation<Color>(progColor),
                  minHeight: height,
                );
              },
            ),
          ),
        ),
      ],
    );
  }
}

