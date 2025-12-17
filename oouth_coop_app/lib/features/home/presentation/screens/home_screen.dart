import 'dart:io';
import 'package:flutter/material.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as p;
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../services/wallet_service.dart';
import '../../../../utils/currency_formatter.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../notifications/data/services/notification_service.dart';
import '../../../../shared/widgets/main_layout.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';
import '../../../../shared/widgets/progress_indicator_custom.dart';
import '../../../../features/wallet/data/models/wallet_data.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen>
    with SingleTickerProviderStateMixin {
  final WalletService _walletService = WalletService();
  WalletData? _walletData;
  String _firstName = 'Member';
  bool _isLoading = true;
  File? _profileImage;
  final NotificationService _notificationService = NotificationService();
  int _unreadCount = 0;
  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _loadFirstName();
    _loadWalletData();
    _loadProfileImage();
    _loadUnreadCount();
    _animationController = AnimationController(
      duration: AppTheme.animationMedium,
      vsync: this,
    );
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeOut),
    );
    _animationController.forward();
  }

  Future<void> _loadFirstName() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final firstName = prefs.getString('FirstName') ?? 'Member';
      if (mounted) {
        setState(() {
          _firstName = firstName;
        });
      }
    } catch (e) {
      debugPrint('Error loading first name: $e');
    }
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  Future<void> _loadUnreadCount() async {
    try {
      final count = await _notificationService.getUnreadCount();
      if (mounted) {
        setState(() {
          _unreadCount = count;
        });
      }
    } catch (e) {
      debugPrint('Error loading unread count: $e');
    }
  }

  Future<void> _loadWalletData({bool refresh = false}) async {
    try {
      final data = await _walletService.getWalletData(refresh: refresh);
      if (mounted) {
        setState(() {
          _walletData = data;
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

  Future<void> _loadProfileImage() async {
    if (kIsWeb) return;
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

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    if (_isLoading) {
      return Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    final totalBalance = _walletData?.totalBalance ?? 0;
    final unpaidLoan = _walletData?.unpaidLoan ?? 0;
    final availableFunds = totalBalance - unpaidLoan;

    return MainLayout(
      currentIndex: 0,
      body: Scaffold(
        backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
        body: FadeTransition(
          opacity: _fadeAnimation,
          child: SafeArea(
            child: RefreshIndicator(
              onRefresh: () => _loadWalletData(refresh: true),
              color: AppTheme.primaryColor,
              child: ListView(
                padding: EdgeInsets.zero,
                children: [
                  // Top App Bar
                  Padding(
                    padding: const EdgeInsets.all(AppTheme.spacingMD),
                    child: Row(
                      children: [
                        // Profile Picture
                        GestureDetector(
                          onTap: () {
                            // Navigate to profile
                            Navigator.pushNamed(context, '/account');
                          },
                          child: CircleAvatar(
                            radius: 24,
                            backgroundImage:
                                _profileImage != null ? FileImage(_profileImage!) : null,
                            backgroundColor: AppTheme.primaryColor.withOpacity(0.1),
                            child: _profileImage == null
                                ? Icon(
                                    Icons.person_rounded,
                                    color: AppTheme.primaryColor,
                                    size: 24,
                                  )
                                : null,
                          ),
                        ),
                        const SizedBox(width: AppTheme.spacingMD),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Hello, $_firstName',
                                style: AppTheme.headlineLarge.copyWith(
                                  color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                ),
                              ),
                              Text(
                                'Welcome back',
                                style: AppTheme.bodyMedium.copyWith(
                                  color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                ),
                              ),
                            ],
                          ),
                        ),
                        // Notifications
                        Stack(
                          children: [
                            IconButton(
                              icon: Icon(
                                Icons.notifications_outlined,
                                color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                size: 28,
                              ),
                              onPressed: () {
                                Navigator.pushNamed(context, '/notifications');
                              },
                            ),
                            if (_unreadCount > 0)
                              Positioned(
                                right: 8,
                                top: 8,
                                child: Container(
                                  padding: const EdgeInsets.all(4),
                                  decoration: const BoxDecoration(
                                    color: AppTheme.error,
                                    shape: BoxShape.circle,
                                  ),
                                  constraints: const BoxConstraints(
                                    minWidth: 16,
                                    minHeight: 16,
                                  ),
                                  child: Text(
                                    _unreadCount > 9 ? '9+' : _unreadCount.toString(),
                                    style: AppTheme.labelSmall.copyWith(
                                      color: Colors.white,
                                      fontSize: 10,
                                    ),
                                    textAlign: TextAlign.center,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  // Available Funds Card
                  ModernCard(
                    margin: const EdgeInsets.all(AppTheme.spacingMD),
                    padding: const EdgeInsets.all(AppTheme.spacingLG),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Available Funds',
                          style: AppTheme.bodyMedium.copyWith(
                            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                          ),
                        ),
                        const SizedBox(height: AppTheme.spacingSM),
                        Text(
                          formatCurrency(availableFunds > 0 ? availableFunds : 0),
                          style: AppTheme.displayMedium.copyWith(
                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                            fontSize: 36,
                          ),
                        ),
                        const SizedBox(height: AppTheme.spacingSM),
                        Text(
                          'Total amount you can withdraw or use for a new loan.',
                          style: AppTheme.bodyMedium.copyWith(
                            color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Quick Action Button
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: AppTheme.spacingMD),
                    child: SizedBox(
                      width: double.infinity,
                      child: OutlinedButton(
                        onPressed: () {
                          Navigator.pushNamed(context, '/loan-request-form');
                        },
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: AppTheme.spacingMD),
                          side: BorderSide(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              'Request Loan',
                              style: AppTheme.labelLarge.copyWith(
                                color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(height: AppTheme.spacingLG),

                  // Overview Section
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: AppTheme.spacingMD),
                    child: Text(
                      'Overview',
                      style: AppTheme.headlineLarge.copyWith(
                        color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                      ),
                    ),
                  ),

                  const SizedBox(height: AppTheme.spacingMD),

                  // Total Contributions Card
                  ModernCard(
                    margin: const EdgeInsets.all(AppTheme.spacingMD),
                    padding: const EdgeInsets.all(AppTheme.spacingLG),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Total Contributions',
                                    style: AppTheme.bodyMedium.copyWith(
                                      color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                    ),
                                  ),
                                  const SizedBox(height: AppTheme.spacingXS),
                                  Text(
                                    formatCurrency(totalBalance),
                                    style: AppTheme.displaySmall.copyWith(
                                      color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                    ),
                                  ),
                                  const SizedBox(height: AppTheme.spacingSM),
                                  Text(
                                    'Last contribution on ${DateTime.now().toString().split(' ')[0]}',
                                    style: AppTheme.bodySmall.copyWith(
                                      color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            // Circular Progress Indicator
                            SizedBox(
                              width: 80,
                              height: 80,
                              child: Stack(
                                alignment: Alignment.center,
                                children: [
                                  CircularProgressIndicator(
                                    value: 0.8,
                                    strokeWidth: 6,
                                    backgroundColor: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                                    valueColor: AlwaysStoppedAnimation<Color>(
                                      AppTheme.primaryColor,
                                    ),
                                  ),
                                  Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Text(
                                        '80%',
                                        style: AppTheme.headlineMedium.copyWith(
                                          color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                        ),
                                      ),
                                      Text(
                                        'Goal',
                                        style: AppTheme.bodySmall.copyWith(
                                          color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: AppTheme.spacingMD),
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton.icon(
                            onPressed: () {
                              Navigator.pushReplacementNamed(context, '/products');
                            },
                            icon: Icon(
                              Icons.arrow_forward_rounded,
                              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                              size: 18,
                            ),
                            label: Text(
                              'View History',
                              style: AppTheme.labelMedium.copyWith(
                                color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                              ),
                            ),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: AppTheme.spacingMD),
                              side: BorderSide(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Loan Status Card (if there's an unpaid loan)
                  if (unpaidLoan > 0)
                    ModernCard(
                      margin: const EdgeInsets.all(AppTheme.spacingMD),
                      padding: const EdgeInsets.all(AppTheme.spacingLG),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Current Loan',
                            style: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingXS),
                          Text(
                            formatCurrency(unpaidLoan),
                            style: AppTheme.displaySmall.copyWith(
                              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingMD),
                          ProgressIndicatorCustom(
                            progress: 0.45,
                            label: null,
                          ),
                          const SizedBox(height: AppTheme.spacingSM),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                '\$1,125 Paid',
                                style: AppTheme.bodySmall.copyWith(
                                  color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                ),
                              ),
                              Text(
                                '\$1,375 Remaining',
                                style: AppTheme.bodySmall.copyWith(
                                  color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: AppTheme.spacingMD),
                          SizedBox(
                            width: double.infinity,
                            child: OutlinedButton.icon(
                              onPressed: () {
                                Navigator.pushNamed(context, '/loan-tracker');
                              },
                              icon: Icon(
                                Icons.payment_rounded,
                                color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                size: 18,
                              ),
                              label: Text(
                                'Make a Payment',
                                style: AppTheme.labelMedium.copyWith(
                                  color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                                ),
                              ),
                              style: OutlinedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(vertical: AppTheme.spacingMD),
                                side: BorderSide(color: isDark ? AppTheme.borderDark : AppTheme.borderLight),
                              ),
                            ),
                          ),
                        ],
                      ),
                    )
                  else
                    // No Active Loans Card
                    ModernCard(
                      margin: const EdgeInsets.all(AppTheme.spacingMD),
                      padding: const EdgeInsets.all(AppTheme.spacingLG),
                      child: Column(
                        children: [
                          Container(
                            width: 64,
                            height: 64,
                            decoration: BoxDecoration(
                              color: AppTheme.primaryColor.withOpacity(0.1),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              Icons.savings_rounded,
                              color: AppTheme.primaryColor,
                              size: 32,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingMD),
                          Text(
                            'No Active Loans',
                            style: AppTheme.headlineLarge.copyWith(
                              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                            ),
                          ),
                          const SizedBox(height: AppTheme.spacingSM),
                          Text(
                            'You are eligible to apply for a new loan. Get the funds you need today.',
                            style: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: AppTheme.spacingMD),
                          SizedBox(
                            width: double.infinity,
                            child: PrimaryButton(
                              text: 'Request a Loan Now',
                              onPressed: () {
                                Navigator.pushNamed(context, '/loan-request-form');
                              },
                            ),
                          ),
                        ],
                      ),
                    ),

                  const SizedBox(height: 100), // Space for bottom nav
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
