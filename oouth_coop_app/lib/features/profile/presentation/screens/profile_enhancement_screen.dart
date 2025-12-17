// lib/features/profile/presentation/screens/profile_enhancement_screen.dart
import 'package:flutter/material.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/main_layout.dart';
import '../../data/services/profile_service.dart';
import '../../data/models/profile_model.dart';
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../features/profile/presentation/screens/personal_info_screen.dart';
import '../../../../features/profile/presentation/screens/change_password_screen.dart';
import '../../../../features/profile/presentation/screens/emergency_contact_screen.dart';

class ProfileEnhancementScreen extends StatefulWidget {
  const ProfileEnhancementScreen({Key? key}) : super(key: key);

  @override
  State<ProfileEnhancementScreen> createState() =>
      _ProfileEnhancementScreenState();
}

class _ProfileEnhancementScreenState extends State<ProfileEnhancementScreen> {
  bool _isLoading = false;
  Profile? _profile;

  @override
  void initState() {
    super.initState();
    _loadProfileData();
  }

  Future<void> _loadProfileData() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final userData = json.decode(prefs.getString('user_data') ?? '{}');
      setState(() {
        _profile = Profile.fromJson(userData);
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading profile: $e')),
        );
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _loadEmergencyContact() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      final userData = json.decode(prefs.getString('user_data') ?? '{}');
      setState(() {
        _profile = Profile.fromJson(userData);
      });
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading emergency contact: $e')),
        );
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Widget _buildCompletionIndicator() {
    if (_profile == null) return const SizedBox.shrink();
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? AppTheme.cardDark : AppTheme.cardLight,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Profile Completion',
                style: AppTheme.headlineMedium.copyWith(
                  color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                ),
              ),
              Text(
                '${_profile!.completionPercentage.toStringAsFixed(0)}%',
                style: AppTheme.headlineMedium.copyWith(
                  color: AppTheme.primaryColor,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          LinearProgressIndicator(
            value: _profile!.completionPercentage / 100,
            backgroundColor: isDark ? AppTheme.borderDark : AppTheme.borderLight,
            valueColor: AlwaysStoppedAnimation<Color>(
              _profile!.completionPercentage == 100
                  ? AppTheme.success
                  : AppTheme.warning,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMenuCard(
      IconData icon, String title, String subtitle, VoidCallback onTap) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Card(
      color: isDark ? AppTheme.cardDark : AppTheme.cardLight,
      child: ListTile(
        leading: Icon(icon, color: AppTheme.primaryColor),
        title: Text(
          title,
          style: AppTheme.bodyLarge.copyWith(
            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
            fontWeight: FontWeight.bold,
          ),
        ),
        subtitle: Text(
          subtitle,
          style: AppTheme.bodySmall.copyWith(
            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
          ),
        ),
        trailing: Icon(Icons.chevron_right, color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight),
        onTap: onTap,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return MainLayout(
      currentIndex: 4,
      body: Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        appBar: AppBar(
          backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
          elevation: 0,
          leading: IconButton(
            icon: Icon(Icons.arrow_back, color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight),
            onPressed: () => Navigator.pop(context),
          ),
          title: Text(
            'Profile Settings',
            style: AppTheme.headlineLarge.copyWith(
              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
            ),
          ),
        ),
        body: SafeArea(
          child: _isLoading
              ? Center(
                  child: CircularProgressIndicator(
                    color: AppTheme.primaryColor,
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    _buildCompletionIndicator(),
                    const SizedBox(height: 24),
                    _buildMenuCard(
                      Icons.person_outline,
                      'Personal Information',
                      'Update your personal details',
                      () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const PersonalInfoScreen(),
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    _buildMenuCard(
                      Icons.lock_outline,
                      'Change Password',
                      'Update your security credentials',
                      () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const ChangePasswordScreen(),
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    _buildMenuCard(
                      Icons.contact_phone_outlined,
                      'Emergency Contact',
                      'Manage your emergency contacts',
                      () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => EmergencyContactScreen(
                            onUpdate: _loadEmergencyContact,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
        ),
      ),
    );
  }
}
