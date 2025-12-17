import 'package:flutter/material.dart';
import '../../config/theme/app_theme.dart';

/// Status Badge Widget for displaying status indicators
class StatusBadge extends StatelessWidget {
  final String text;
  final StatusType type;
  final double? fontSize;

  const StatusBadge({
    super.key,
    required this.text,
    required this.type,
    this.fontSize,
  });

  @override
  Widget build(BuildContext context) {
    Color backgroundColor;
    Color textColor;

    switch (type) {
      case StatusType.success:
        backgroundColor = AppTheme.success.withOpacity(0.2);
        textColor = AppTheme.success;
        break;
      case StatusType.warning:
        backgroundColor = AppTheme.warning.withOpacity(0.2);
        textColor = AppTheme.warning;
        break;
      case StatusType.error:
        backgroundColor = AppTheme.error.withOpacity(0.2);
        textColor = AppTheme.error;
        break;
      case StatusType.info:
        backgroundColor = AppTheme.info.withOpacity(0.2);
        textColor = AppTheme.info;
        break;
      case StatusType.pending:
        backgroundColor = AppTheme.warning.withOpacity(0.2);
        textColor = AppTheme.warning;
        break;
      case StatusType.active:
        backgroundColor = AppTheme.success.withOpacity(0.2);
        textColor = AppTheme.success;
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: AppTheme.spacingMD,
        vertical: AppTheme.spacingXS,
      ),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(AppTheme.radiusFull),
      ),
      child: Text(
        text,
        style: AppTheme.labelMedium.copyWith(
          color: textColor,
          fontSize: fontSize ?? 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

enum StatusType {
  success,
  warning,
  error,
  info,
  pending,
  active,
}

