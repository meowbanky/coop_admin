import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter/foundation.dart' show kIsWeb;

/// Modern Design System for OOUTH Cooperative App
/// Primary Color: #19e680 (Bright Green)
class AppTheme {
  // Helper function to safely get Manrope font with fallback
  static TextStyle _manrope({
    required double fontSize,
    required FontWeight fontWeight,
    double? letterSpacing,
    double? height,
  }) {
    // On web, use the preloaded font from CSS, otherwise use GoogleFonts
    if (kIsWeb) {
      return TextStyle(
        fontFamily: 'Manrope',
        fontSize: fontSize,
        fontWeight: fontWeight,
        letterSpacing: letterSpacing,
        height: height,
        fontFamilyFallback: const ['sans-serif'],
      );
    } else {
      return GoogleFonts.manrope(
        fontSize: fontSize,
        fontWeight: fontWeight,
        letterSpacing: letterSpacing,
        height: height,
      );
    }
  }

  // Brand Colors
  static const Color primaryColor = Color(0xFF19e680); // Bright Green
  static const Color primaryDark = Color(0xFF14c96b);
  static const Color primaryLight = Color(0xFF4df0a0);

  // Background Colors
  static const Color backgroundLight = Color(0xFFf6f8f7);
  static const Color backgroundDark = Color(0xFF111814);

  // Card Colors
  static const Color cardLight = Color(0xFFFFFFFF);
  static const Color cardDark = Color(0xFF1c2621);

  // Text Colors
  static const Color textPrimaryLight = Color(0xFF111814);
  static const Color textPrimaryDark = Color(0xFFFFFFFF);
  static const Color textSecondaryLight = Color(0xFF64748b);
  static const Color textSecondaryDark = Color(0xFF9db8ab);

  // Semantic Colors
  static const Color success = Color(0xFF19e680);
  static const Color warning = Color(0xFFf59e0b);
  static const Color error = Color(0xFFef4444);
  static const Color info = Color(0xFF3b82f6);

  // Border Colors
  static const Color borderLight = Color(0xFFe2e8f0);
  static const Color borderDark = Color(0xFF293830);

  // Muted Colors
  static const Color mutedLight = Color(0xFF94a3b8);
  static const Color mutedDark = Color(0xFF64748b);

  // Spacing
  static const double spacingXS = 4.0;
  static const double spacingSM = 8.0;
  static const double spacingMD = 16.0;
  static const double spacingLG = 24.0;
  static const double spacingXL = 32.0;

  // Border Radius
  static const double radiusSM = 8.0;
  static const double radiusMD = 12.0;
  static const double radiusLG = 16.0;
  static const double radiusXL = 20.0;
  static const double radiusFull = 9999.0;

  // Animation Durations
  static const Duration animationFast = Duration(milliseconds: 200);
  static const Duration animationMedium = Duration(milliseconds: 300);
  static const Duration animationSlow = Duration(milliseconds: 500);

  // Text Styles
  static TextStyle get displayLarge => _manrope(
        fontSize: 32,
        fontWeight: FontWeight.w800,
        letterSpacing: -0.015,
        height: 1.2,
      );

  static TextStyle get displayMedium => _manrope(
        fontSize: 24,
        fontWeight: FontWeight.w700,
        letterSpacing: -0.015,
        height: 1.2,
      );

  static TextStyle get displaySmall => _manrope(
        fontSize: 20,
        fontWeight: FontWeight.w700,
        letterSpacing: -0.015,
        height: 1.2,
      );

  static TextStyle get headlineLarge => _manrope(
        fontSize: 18,
        fontWeight: FontWeight.w700,
        letterSpacing: -0.015,
        height: 1.3,
      );

  static TextStyle get headlineMedium => _manrope(
        fontSize: 16,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.015,
        height: 1.4,
      );

  static TextStyle get bodyLarge => _manrope(
        fontSize: 16,
        fontWeight: FontWeight.w400,
        letterSpacing: 0,
        height: 1.5,
      );

  static TextStyle get bodyMedium => _manrope(
        fontSize: 14,
        fontWeight: FontWeight.w400,
        letterSpacing: 0,
        height: 1.5,
      );

  static TextStyle get bodySmall => _manrope(
        fontSize: 12,
        fontWeight: FontWeight.w400,
        letterSpacing: 0,
        height: 1.4,
      );

  static TextStyle get labelLarge => _manrope(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        letterSpacing: 0.015,
        height: 1.4,
      );

  static TextStyle get labelMedium => _manrope(
        fontSize: 12,
        fontWeight: FontWeight.w500,
        letterSpacing: 0.015,
        height: 1.4,
      );

  static TextStyle get labelSmall => _manrope(
        fontSize: 11,
        fontWeight: FontWeight.w500,
        letterSpacing: 0.015,
        height: 1.4,
      );

  // Light Theme
  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      primaryColor: primaryColor,
      scaffoldBackgroundColor: backgroundLight,
      colorScheme: ColorScheme.light(
        primary: primaryColor,
        secondary: primaryDark,
        surface: cardLight,
        error: error,
        onPrimary: textPrimaryDark,
        onSecondary: textPrimaryDark,
        onSurface: textPrimaryLight,
        onError: Colors.white,
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: backgroundLight,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: textPrimaryLight),
        titleTextStyle: headlineLarge.copyWith(color: textPrimaryLight),
      ),
      cardTheme: CardThemeData(
        color: cardLight,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMD),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: textPrimaryDark,
          elevation: 0,
          padding: const EdgeInsets.symmetric(
              horizontal: spacingLG, vertical: spacingMD),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMD),
          ),
          textStyle: labelLarge.copyWith(color: textPrimaryDark),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: textPrimaryLight,
          side: const BorderSide(color: borderLight),
          padding: const EdgeInsets.symmetric(
              horizontal: spacingLG, vertical: spacingMD),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMD),
          ),
          textStyle: labelLarge,
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: primaryColor,
          padding: const EdgeInsets.symmetric(
              horizontal: spacingMD, vertical: spacingSM),
          textStyle: labelLarge.copyWith(color: primaryColor),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: cardLight,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMD),
          borderSide: const BorderSide(color: borderLight),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMD),
          borderSide: const BorderSide(color: borderLight),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMD),
          borderSide: const BorderSide(color: primaryColor, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMD),
          borderSide: const BorderSide(color: error),
        ),
        contentPadding: const EdgeInsets.symmetric(
            horizontal: spacingMD, vertical: spacingMD),
        hintStyle: bodyMedium.copyWith(color: mutedLight),
      ),
      textTheme: TextTheme(
        displayLarge: displayLarge.copyWith(color: textPrimaryLight),
        displayMedium: displayMedium.copyWith(color: textPrimaryLight),
        displaySmall: displaySmall.copyWith(color: textPrimaryLight),
        headlineLarge: headlineLarge.copyWith(color: textPrimaryLight),
        headlineMedium: headlineMedium.copyWith(color: textPrimaryLight),
        bodyLarge: bodyLarge.copyWith(color: textPrimaryLight),
        bodyMedium: bodyMedium.copyWith(color: textSecondaryLight),
        bodySmall: bodySmall.copyWith(color: textSecondaryLight),
        labelLarge: labelLarge.copyWith(color: textPrimaryLight),
        labelMedium: labelMedium.copyWith(color: textSecondaryLight),
      ),
    );
  }

  // Dark Theme
  static ThemeData get darkTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      primaryColor: primaryColor,
      scaffoldBackgroundColor: backgroundDark,
      colorScheme: ColorScheme.dark(
        primary: primaryColor,
        secondary: primaryLight,
        surface: cardDark,
        error: error,
        onPrimary: textPrimaryDark,
        onSecondary: textPrimaryDark,
        onSurface: textPrimaryDark,
        onError: Colors.white,
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: backgroundDark,
        elevation: 0,
        centerTitle: true,
        iconTheme: const IconThemeData(color: textPrimaryDark),
        titleTextStyle: headlineLarge.copyWith(color: textPrimaryDark),
      ),
      cardTheme: CardThemeData(
        color: cardDark,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusMD),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: textPrimaryDark,
          elevation: 0,
          padding: const EdgeInsets.symmetric(
              horizontal: spacingLG, vertical: spacingMD),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMD),
          ),
          textStyle: labelLarge.copyWith(color: textPrimaryDark),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: textPrimaryDark,
          side: const BorderSide(color: borderDark),
          padding: const EdgeInsets.symmetric(
              horizontal: spacingLG, vertical: spacingMD),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusMD),
          ),
          textStyle: labelLarge,
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: primaryColor,
          padding: const EdgeInsets.symmetric(
              horizontal: spacingMD, vertical: spacingSM),
          textStyle: labelLarge.copyWith(color: primaryColor),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: cardDark,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMD),
          borderSide: const BorderSide(color: borderDark),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMD),
          borderSide: const BorderSide(color: borderDark),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMD),
          borderSide: const BorderSide(color: primaryColor, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusMD),
          borderSide: const BorderSide(color: error),
        ),
        contentPadding: const EdgeInsets.symmetric(
            horizontal: spacingMD, vertical: spacingMD),
        hintStyle: bodyMedium.copyWith(color: mutedDark),
      ),
      textTheme: TextTheme(
        displayLarge: displayLarge.copyWith(color: textPrimaryDark),
        displayMedium: displayMedium.copyWith(color: textPrimaryDark),
        displaySmall: displaySmall.copyWith(color: textPrimaryDark),
        headlineLarge: headlineLarge.copyWith(color: textPrimaryDark),
        headlineMedium: headlineMedium.copyWith(color: textPrimaryDark),
        bodyLarge: bodyLarge.copyWith(color: textPrimaryDark),
        bodyMedium: bodyMedium.copyWith(color: textSecondaryDark),
        bodySmall: bodySmall.copyWith(color: textSecondaryDark),
        labelLarge: labelLarge.copyWith(color: textPrimaryDark),
        labelMedium: labelMedium.copyWith(color: textSecondaryDark),
      ),
    );
  }
}
