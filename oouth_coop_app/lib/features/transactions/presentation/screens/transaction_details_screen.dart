import 'package:flutter/material.dart';
import '../../../../shared/widgets/custom_app_bar.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../config/theme/app_theme.dart';

class TransactionDetailsScreen extends StatelessWidget {
  const TransactionDetailsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
      appBar: const CustomAppBar(title: 'Transaction Details'),
      body: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: 10, // Replace with actual transaction count
        itemBuilder: (context, index) {
          return ModernCard(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(16),
            child: ListTile(
              contentPadding: EdgeInsets.zero,
              title: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Transaction #${1000 + index}',
                    style: AppTheme.headlineMedium.copyWith(
                      color: AppTheme.primaryColor,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Date: ${DateTime.now().subtract(Duration(days: index)).toString().split(' ')[0]}',
                    style: AppTheme.bodySmall.copyWith(
                      color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                    ),
                  ),
                ],
              ),
              subtitle: Padding(
                padding: const EdgeInsets.only(top: 8.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Amount: \$${(100 * (index + 1)).toStringAsFixed(2)}',
                      style: AppTheme.bodyMedium.copyWith(
                        color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Status: ${index % 2 == 0 ? 'Completed' : 'Pending'}',
                      style: AppTheme.bodySmall.copyWith(
                        color: index % 2 == 0 ? AppTheme.success : AppTheme.warning,
                      ),
                    ),
                  ],
                ),
              ),
              trailing: Icon(
                Icons.chevron_right,
                color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
              ),
              onTap: () {
                // Navigate to transaction detail
              },
            ),
          );
        },
      ),
    );
  }
}
