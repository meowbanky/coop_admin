import 'package:flutter/material.dart';
import 'package:timeago/timeago.dart' as timeago;
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../data/models/notification_model.dart';
import '../../data/services/notification_service.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen>
    with SingleTickerProviderStateMixin {
  final NotificationService _notificationService = NotificationService();
  bool _isLoading = true;
  List<NotificationModel> _notifications = [];
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _loadNotifications();
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

  Future<void> _loadNotifications() async {
    try {
      final notifications = await _notificationService.getNotifications();
      if (mounted) {
        setState(() {
          _notifications = notifications;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: ${e.toString()}')),
        );
      }
    }
  }

  Future<void> _markAsRead(int notificationId) async {
    try {
      await _notificationService.markAsRead(notificationId);
      await _loadNotifications();
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: ${e.toString()}')),
        );
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
          'Notifications',
          style: AppTheme.headlineLarge.copyWith(color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight),
        ),
      ),
      body: FadeTransition(
        opacity: _fadeAnimation,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _notifications.isEmpty
                ? Center(
                    child: ModernCard(
                      padding: const EdgeInsets.all(AppTheme.spacingXL),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            width: 80,
                            height: 80,
                            decoration: BoxDecoration(
                              color: AppTheme.primaryColor.withOpacity(0.1),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              Icons.notifications_none_rounded,
                              color: AppTheme.primaryColor,
                              size: 40,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingLG),
                          Text(
                            'No Notifications',
                            style: AppTheme.headlineLarge.copyWith(
                              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingSM),
                          Text(
                            'You\'re all caught up!',
                            style: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                          ),
                        ],
                      ),
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: _loadNotifications,
                    color: AppTheme.primaryColor,
                    child: ListView.builder(
                      padding: const EdgeInsets.all(AppTheme.spacingMD),
                      itemCount: _notifications.length,
                      itemBuilder: (context, index) {
                        final notification = _notifications[index];
                        final isUnread = notification.status == 'unread';

                        return ModernCard(
                          margin: const EdgeInsets.only(bottom: AppTheme.spacingMD),
                          padding: const EdgeInsets.all(AppTheme.spacingMD),
                          backgroundColor: isUnread
                              ? AppTheme.primaryColor.withOpacity(0.05)
                              : null,
                          onTap: () => _markAsRead(notification.id),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                width: 48,
                                height: 48,
                                decoration: BoxDecoration(
                                  color: isUnread
                                      ? AppTheme.primaryColor.withOpacity(0.1)
                                      : (isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight).withOpacity(0.1),
                                  shape: BoxShape.circle,
                                ),
                                child: Icon(
                                  Icons.notifications_rounded,
                                  color: isUnread
                                      ? AppTheme.primaryColor
                                      : (isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight),
                                  size: 24,
                                ),
                              ),
                              const SizedBox(width: AppTheme.spacingMD),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Expanded(
                                          child: Text(
                                            notification.title,
                                            style: AppTheme.headlineMedium.copyWith(
                                              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                              fontWeight: isUnread
                                                  ? FontWeight.w700
                                                  : FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                        if (isUnread)
                                          Container(
                                            width: 8,
                                            height: 8,
                                            decoration: BoxDecoration(
                                              color: AppTheme.primaryColor,
                                              shape: BoxShape.circle,
                                            ),
                                          ),
                                      ],
                                    ),
                                    const SizedBox(height: AppTheme.spacingXS),
                                    Text(
                                      notification.message,
                                      style: AppTheme.bodyMedium.copyWith(
                                        color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                      ),
                                    ),
                                    const SizedBox(height: AppTheme.spacingXS),
                                    Text(
                                      timeago.format(notification.createdAt),
                                      style: AppTheme.bodySmall.copyWith(
                                        color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
      ),
    );
  }
}
