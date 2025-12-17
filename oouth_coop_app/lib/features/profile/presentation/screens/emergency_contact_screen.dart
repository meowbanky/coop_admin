import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/main_layout.dart';

class EmergencyContactScreen extends StatefulWidget {
  final VoidCallback onUpdate;

  const EmergencyContactScreen({Key? key, required this.onUpdate})
      : super(key: key);

  @override
  State<EmergencyContactScreen> createState() => _EmergencyContactScreenState();
}

class _EmergencyContactScreenState extends State<EmergencyContactScreen> {
  final TextEditingController _firstNameController = TextEditingController();
  final TextEditingController _middleNameController = TextEditingController();
  final TextEditingController _lastNameController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadEmergencyContact();
  }

  Future<void> _loadEmergencyContact() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      setState(() {
        _firstNameController.text = prefs.getString('nok_first_name') ?? '';
        _middleNameController.text = prefs.getString('nok_middle_name') ?? '';
        _lastNameController.text = prefs.getString('nok_last_name') ?? '';
        _phoneController.text = prefs.getString('nok_tel') ?? '';
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

  Future<void> _saveEmergencyContact() async {
    setState(() => _isLoading = true);
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('nok_first_name', _firstNameController.text);
      await prefs.setString('nok_middle_name', _middleNameController.text);
      await prefs.setString('nok_last_name', _lastNameController.text);
      await prefs.setString('nok_tel', _phoneController.text);

      // Call the callback to notify the parent widget
      widget.onUpdate();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Emergency contact updated successfully')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error updating emergency contact: $e')),
        );
      }
    } finally {
      setState(() => _isLoading = false);
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
          title: Text(
            'Emergency Contact',
            style: AppTheme.headlineLarge.copyWith(
              color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
            ),
          ),
          backgroundColor: isDark ? AppTheme.backgroundDark : AppTheme.backgroundLight,
          elevation: 0,
        ),
        body: _isLoading
            ? Center(
                child: CircularProgressIndicator(
                  color: AppTheme.primaryColor,
                ),
              )
            : Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  children: [
                    // First Name TextField
                    Builder(
                      builder: (context) {
                        final isDark = Theme.of(context).brightness == Brightness.dark;
                        return TextField(
                          controller: _firstNameController,
                          style: AppTheme.bodyLarge.copyWith(
                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                          ),
                          decoration: InputDecoration(
                            labelText: 'First Name',
                            labelStyle: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                            filled: true,
                            fillColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                            enabledBorder: OutlineInputBorder(
                              borderSide: BorderSide(
                                color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                              ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderSide: const BorderSide(color: AppTheme.primaryColor, width: 2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 16),

                    // Middle Name TextField
                    Builder(
                      builder: (context) {
                        final isDark = Theme.of(context).brightness == Brightness.dark;
                        return TextField(
                          controller: _middleNameController,
                          style: AppTheme.bodyLarge.copyWith(
                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                          ),
                          decoration: InputDecoration(
                            labelText: 'Middle Name',
                            labelStyle: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                            filled: true,
                            fillColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                            enabledBorder: OutlineInputBorder(
                              borderSide: BorderSide(
                                color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                              ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderSide: const BorderSide(color: AppTheme.primaryColor, width: 2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 16),

                    // Last Name TextField
                    Builder(
                      builder: (context) {
                        final isDark = Theme.of(context).brightness == Brightness.dark;
                        return TextField(
                          controller: _lastNameController,
                          style: AppTheme.bodyLarge.copyWith(
                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                          ),
                          decoration: InputDecoration(
                            labelText: 'Last Name',
                            labelStyle: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                            filled: true,
                            fillColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                            enabledBorder: OutlineInputBorder(
                              borderSide: BorderSide(
                                color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                              ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderSide: const BorderSide(color: AppTheme.primaryColor, width: 2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 16),

                    // Phone Number TextField
                    Builder(
                      builder: (context) {
                        final isDark = Theme.of(context).brightness == Brightness.dark;
                        return TextField(
                          controller: _phoneController,
                          style: AppTheme.bodyLarge.copyWith(
                            color: isDark ? AppTheme.textPrimaryDark : AppTheme.textPrimaryLight,
                          ),
                          decoration: InputDecoration(
                            labelText: 'Phone Number',
                            labelStyle: AppTheme.bodyMedium.copyWith(
                              color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight,
                            ),
                            filled: true,
                            fillColor: isDark ? AppTheme.cardDark : AppTheme.cardLight,
                            enabledBorder: OutlineInputBorder(
                              borderSide: BorderSide(
                                color: isDark ? AppTheme.borderDark : AppTheme.borderLight,
                              ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderSide: const BorderSide(color: AppTheme.primaryColor, width: 2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 20),

                    // Save Button
                    Builder(
                      builder: (context) {
                        final isDark = Theme.of(context).brightness == Brightness.dark;
                        return SizedBox(
                          width: double.infinity, // Makes the button fill the width
                          child: ElevatedButton(
                            onPressed: _saveEmergencyContact,
                            style: ElevatedButton.styleFrom(
                              backgroundColor: isDark ? AppTheme.cardDark : Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(8),
                              ),
                            ),
                            child: Text(
                              'Save',
                              style: TextStyle(
                                color: AppTheme.primaryColor,
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}
