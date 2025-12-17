import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../../../../config/env.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/main_layout.dart';

class NotificationScreen extends StatefulWidget {
  static final String baseUrl = Env.apiBaseUrl;

  const NotificationScreen({Key? key}) : super(key: key);

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  List<dynamic> _notifications = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchNotifications();
  }

  Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token');
  }

  Future<String?> _getCoopId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('user_data')?.let((data) {
      final userData = json.decode(data);
      return userData['CoopID'];
    });
  }

  Future<void> _fetchNotifications() async {
    try {
      final token = await _getToken();
      final coopId = await _getCoopId();

      if (token == null || coopId == null) {
        throw Exception('Authentication required');
      }

      final response = await http.get(
        Uri.parse(
            '${NotificationScreen.baseUrl}/auth/notifications.php?coop_id=$coopId'),
        headers: {
          'Authorization': 'Bearer $token',
        },
      );

      debugPrint('Response status: ${response.statusCode}');
      debugPrint('Response body: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _notifications = data['data'];
            _isLoading = false;
          });
        } else {
          throw Exception(data['message'] ?? 'Failed to load notifications');
        }
      } else {
        throw Exception('Server error: ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('Error fetching notifications: $e');
      setState(() {
        _isLoading = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: ${e.toString()}')),
        );
      }
    }
  }

  Future<void> _markAsRead(int notificationId) async {
    try {
      final token = await _getToken();

      if (token == null) {
        throw Exception('Authentication required');
      }

      final response = await http.put(
        Uri.parse(
            '${NotificationScreen.baseUrl}/auth/notifications.php/$notificationId/read'),
        headers: {
          'Authorization': 'Bearer $token',
          'Content-Type': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            final index =
                _notifications.indexWhere((n) => n['id'] == notificationId);
            if (index != -1) {
              _notifications[index]['status'] = 'read';
            }
          });
        } else {
          throw Exception(
              data['message'] ?? 'Failed to mark notification as read');
        }
      } else {
        throw Exception('Server error: ${response.statusCode}');
      }
    } catch (e) {
      debugPrint('Error marking notification as read: $e');
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
    return MainLayout(
      currentIndex: 4, // Adjust the index as needed
      body: Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        appBar: AppBar(
          backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
          elevation: 0,
          title: Text(
            'Notifications',
            style: AppTheme.headlineLarge.copyWith(
              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
            ),
          ),
        ),
        body: _isLoading
            ? Center(
                child: CircularProgressIndicator(
                  color: AppTheme.primaryColor,
                ),
              )
            : _notifications.isEmpty
                ? Center(
                    child: Text(
                      'No notifications found',
                      style: AppTheme.bodyLarge.copyWith(
                        color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                      ),
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: _fetchNotifications,
                    color: AppTheme.primaryColor,
                    child: ListView.builder(
                      itemCount: _notifications.length,
                      padding: const EdgeInsets.all(16),
                      itemBuilder: (context, index) {
                        final notification = _notifications[index];
                        final DateTime createdAt =
                            DateTime.parse(notification['created_at']);

                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          decoration: BoxDecoration(
                            color: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: ListTile(
                            onTap: () {
                              if (notification['status'] == 'unread') {
                                _markAsRead(notification['id']);
                              }
                            },
                            title: Text(
                              notification['title'],
                              style: AppTheme.bodyLarge.copyWith(
                                color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                fontWeight: notification['status'] == 'unread'
                                    ? FontWeight.bold
                                    : FontWeight.normal,
                              ),
                            ),
                            subtitle: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const SizedBox(height: 4),
                                Text(
                                  notification['message'],
                                  style: AppTheme.bodyMedium.copyWith(
                                    color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  _formatDate(createdAt),
                                  style: AppTheme.bodySmall.copyWith(
                                    color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                  ),
                                ),
                              ],
                            ),
                            leading: CircleAvatar(
                              backgroundColor:
                                  notification['status'] == 'unread'
                                      ? AppTheme.primaryColor
                                      : (isDark ? AppTheme.borderDark : AppTheme.borderLight),
                              child: Icon(
                                Icons.notifications,
                                color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                size: 20,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
      ),
    );
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    final difference = now.difference(date);

    if (difference.inDays > 7) {
      return '${date.day}/${date.month}/${date.year}';
    } else if (difference.inDays > 0) {
      return '${difference.inDays}d ago';
    } else if (difference.inHours > 0) {
      return '${difference.inHours}h ago';
    } else if (difference.inMinutes > 0) {
      return '${difference.inMinutes}m ago';
    } else {
      return 'Just now';
    }
  }
}

extension NullSafetyExtension<T> on T? {
  R? let<R>(R Function(T) block) => this != null ? block(this!) : null;
}
