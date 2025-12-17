import 'dart:io';
import 'package:path_provider/path_provider.dart';
import 'package:flutter/material.dart';
import 'package:path/path.dart' as p;
import 'package:image_picker/image_picker.dart';
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:provider/provider.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/main_layout.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../config/routes/routes.dart';
import '../../../../features/profile/presentation/screens/personal_info_screen.dart';
import '../../../../features/profile/presentation/screens/profile_enhancement_screen.dart';
import '../../../../services/theme_service.dart';

class AccountScreen extends StatefulWidget {
  const AccountScreen({Key? key}) : super(key: key);

  @override
  State<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends State<AccountScreen>
    with SingleTickerProviderStateMixin {
  final ImagePicker _picker = ImagePicker();
  bool _isLoading = true;
  File? _profileImage;
  String? _email;
  String? _phoneNumber;
  String? _firstName;
  String? _lastName;
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _loadProfileImage();
    _loadUserData();
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

  String _getGreeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) {
      return 'Good Morning';
    } else if (hour < 17) {
      return 'Good Afternoon';
    } else {
      return 'Good Evening';
    }
  }

  Future<void> _loadUserData() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userData = prefs.getString('user_data');
      debugPrint('Retrieved user data: $userData');

      if (userData != null) {
        final user = json.decode(userData);
        setState(() {
          _email = user['EmailAddress'] ?? '';
          _phoneNumber = user['MobileNumber'] ?? '';
          _firstName = user['FirstName'] ?? '';
          _lastName = user['LastName'] ?? '';
          _isLoading = false;
        });
      }
    } catch (e) {
      debugPrint('Error loading user data: $e');
      setState(() => _isLoading = false);
    }
  }

  Future<void> _loadProfileImage() async {
    try {
      final directory = await getApplicationDocumentsDirectory();
      final imagePath = p.join(directory.path, 'profile_image.jpg');
      if (await File(imagePath).exists()) {
        if (mounted) {
          setState(() {
            _profileImage = File(imagePath);
          });
        }
      }
    } catch (e) {
      debugPrint('Error loading profile image: $e');
    }
  }

  Future<void> _pickImage() async {
    try {
      final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
      if (image != null && mounted) {
        final directory = await getApplicationDocumentsDirectory();
        final imagePath = p.join(directory.path, 'profile_image.jpg');
        final savedImage =
            await File(imagePath).writeAsBytes(await image.readAsBytes());
        setState(() {
          _profileImage = savedImage;
        });
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error picking image: ${e.toString()}')),
        );
      }
    }
  }

  Widget _buildMenuItem({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
    Color? iconColor,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return ModernCard(
      padding: const EdgeInsets.all(AppTheme.spacingMD),
      margin: const EdgeInsets.only(bottom: AppTheme.spacingSM),
      onTap: onTap,
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(AppTheme.spacingSM),
            decoration: BoxDecoration(
              color: (iconColor ?? AppTheme.primaryColor).withOpacity(0.1),
              borderRadius: BorderRadius.circular(AppTheme.radiusSM),
            ),
            child: Icon(
              icon,
              color: iconColor ?? AppTheme.primaryColor,
              size: 24,
            ),
          ),
          const SizedBox(width: AppTheme.spacingMD),
          Expanded(
            child: Text(
              title,
              style: AppTheme.bodyLarge.copyWith(
                color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
              ),
            ),
          ),
          Icon(
            Icons.chevron_right_rounded,
            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    if (_isLoading) {
      return Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        body: const Center(child: CircularProgressIndicator()),
      );
    }
    return MainLayout(
      currentIndex: 3,
      body: Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        body: FadeTransition(
          opacity: _fadeAnimation,
          child: SafeArea(
            child: SingleChildScrollView(
              child: Column(
                children: [
                  // Profile Header Card
                  ModernCard(
                    margin: const EdgeInsets.all(AppTheme.spacingMD),
                    padding: const EdgeInsets.all(AppTheme.spacingLG),
                    child: Column(
                      children: [
                        GestureDetector(
                          onTap: _pickImage,
                          child: Stack(
                            children: [
                              CircleAvatar(
                                radius: 50,
                                backgroundColor:
                                    AppTheme.primaryColor.withOpacity(0.1),
                                backgroundImage: _profileImage != null
                                    ? FileImage(_profileImage!)
                                    : null,
                                child: _profileImage == null
                                    ? Icon(
                                        Icons.person_rounded,
                                        size: 50,
                                        color: AppTheme.primaryColor,
                                      )
                                    : null,
                              ),
                              Positioned(
                                bottom: 0,
                                right: 0,
                                child: Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: AppTheme.primaryColor,
                                    shape: BoxShape.circle,
                                    border: Border.all(
                                      color: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
                                      width: 3,
                                    ),
                                  ),
                                  child: Icon(
                                    Icons.camera_alt_rounded,
                                    size: 18,
                                    color: AppTheme.textPrimaryDark,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: AppTheme.spacingMD),
                        Text(
                          _getGreeting(),
                          style: AppTheme.bodyMedium.copyWith(
                            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                          ),
                        ),
                        const SizedBox(height: AppTheme.spacingXS),
                        if (_firstName != null && _lastName != null)
                          Text(
                            '$_firstName $_lastName',
                            style: AppTheme.displaySmall.copyWith(
                              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                            ),
                          ),
                        const SizedBox(height: AppTheme.spacingMD),
                        if (_email != null)
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.email_outlined,
                                size: 16,
                                color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                              ),
                              const SizedBox(width: AppTheme.spacingXS),
                              Text(
                                _email!,
                                style: AppTheme.bodySmall.copyWith(
                                  color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                ),
                              ),
                            ],
                          ),
                        if (_phoneNumber != null) ...[
                          const SizedBox(height: AppTheme.spacingXS),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.phone_outlined,
                                size: 16,
                                color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                              ),
                              const SizedBox(width: AppTheme.spacingXS),
                              Text(
                                _phoneNumber!,
                                style: AppTheme.bodySmall.copyWith(
                                  color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ],
                    ),
                  ),

                  // Menu Items
                  Padding(
                    padding: const EdgeInsets.symmetric(
                        horizontal: AppTheme.spacingMD),
                    child: Column(
                      children: [
                        _buildMenuItem(
                          icon: Icons.person_outline_rounded,
                          title: 'Personal Information',
                          onTap: () async {
                            final result = await Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) =>
                                    const PersonalInfoScreen(),
                              ),
                            );
                            if (result == true) {
                              setState(() => _isLoading = true);
                              await _loadUserData();
                              setState(() => _isLoading = false);
                            }
                          },
                        ),
                        _buildMenuItem(
                          icon: Icons.lock_outline_rounded,
                          title: 'Change Password',
                          onTap: () =>
                              AppRoutes.navigateToChangePassword(context),
                        ),
                        _buildMenuItem(
                          icon: Icons.contact_emergency_rounded,
                          title: 'Emergency Contact',
                          onTap: () async {
                            await AppRoutes.navigateToEmergencyContact(
                              context,
                              () {}, // Callback
                            );
                          },
                        ),
                        _buildMenuItem(
                          icon: Icons.notifications_outlined,
                          title: 'Notification Settings',
                          onTap: () => AppRoutes.navigateToSettings(context),
                        ),
                        Consumer<ThemeService>(
                          builder: (context, themeService, _) {
                            final isDark = Theme.of(context).brightness == Brightness.dark;
                            return ModernCard(
                              padding: const EdgeInsets.all(AppTheme.spacingMD),
                              margin: const EdgeInsets.only(bottom: AppTheme.spacingSM),
                              child: Row(
                                children: [
                                  Container(
                                    padding: const EdgeInsets.all(AppTheme.spacingSM),
                                    decoration: BoxDecoration(
                                      color: AppTheme.primaryColor.withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(AppTheme.radiusSM),
                                    ),
                                    child: Icon(
                                      themeService.isDarkMode 
                                          ? Icons.dark_mode_rounded 
                                          : Icons.light_mode_rounded,
                                      color: AppTheme.primaryColor,
                                      size: 24,
                                    ),
                                  ),
                                  const SizedBox(width: AppTheme.spacingMD),
                                  Expanded(
                                    child: Text(
                                      'Dark Mode',
                                      style: AppTheme.bodyLarge.copyWith(
                                        color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                      ),
                                    ),
                                  ),
                                  Switch(
                                    value: themeService.isDarkMode,
                                    onChanged: (value) {
                                      themeService.toggleTheme();
                                    },
                                    activeColor: AppTheme.primaryColor,
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                        _buildMenuItem(
                          icon: Icons.support_outlined,
                          title: 'Support',
                          onTap: () => AppRoutes.navigateToSupport(context),
                        ),
                        _buildMenuItem(
                          icon: Icons.manage_accounts_rounded,
                          title: 'Profile Management',
                          onTap: () => Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) =>
                                  const ProfileEnhancementScreen(),
                            ),
                          ),
                        ),
                        _buildMenuItem(
                          icon: Icons.account_balance_rounded,
                          title: 'Bank Account',
                          onTap: () => AppRoutes.navigateToBankAccount(context),
                        ),
                        const SizedBox(height: AppTheme.spacingMD),
                        _buildMenuItem(
                          icon: Icons.logout_rounded,
                          title: 'Logout',
                          iconColor: AppTheme.error,
                          onTap: () => AppRoutes.navigateToLogin(context),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: AppTheme.spacingXL),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
