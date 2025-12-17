import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:firebase_core/firebase_core.dart';
import 'package:provider/provider.dart';
import 'config/theme/app_theme.dart';
import 'config/routes/routes.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'services/notification_service.dart';
import 'services/app_update_service.dart'; // Add this import
import 'services/theme_service.dart';
import 'package:onesignal_flutter/onesignal_flutter.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  try {
    // Load environment variables
    await dotenv.load(fileName: ".env");
    debugPrint('Environment variables loaded successfully');
  } catch (e) {
    debugPrint('Warning: Could not load .env file: $e');
  }

  if (!kIsWeb) {
    try {
      // Initialize Firebase first
      await Firebase.initializeApp();
      debugPrint('Firebase initialized successfully');
    } catch (e) {
      debugPrint('Error initializing Firebase: $e');
      // Continue anyway - Firebase might not be critical for app startup
    }

    try {
      // Initialize OneSignal with proper configuration
      OneSignal.consentRequired(false);
      OneSignal.initialize("aeef154f-9807-4dff-b7a6-d215ac0c1281");
      await OneSignal.login("aeef154f-9807-4dff-b7a6-d215ac0c1281");
      await OneSignal.Notifications.requestPermission(true);
      debugPrint('OneSignal initialized successfully');

      OneSignal.User.pushSubscription.addObserver((state) {
        debugPrint(
            'Push subscription state changed: ${state.current.jsonRepresentation()}');
        final id = state.current.id;
        final token = state.current.token;
        debugPrint('Subscription ID: $id');
        debugPrint('Push Token: $token');
      });
    } catch (e) {
      debugPrint('Error initializing OneSignal: $e');
      // Continue anyway - OneSignal is not critical for app startup
    }
  } else {
    try {
      await Firebase.initializeApp(
        options: const FirebaseOptions(
            apiKey: "AIzaSyCAsPADcUzQSE6T1jglEEBdmNjpGKWdO_Y",
            authDomain: "oouth-coop.firebaseapp.com",
            projectId: "oouth-coop",
            storageBucket: "oouth-coop.appspot.com",
            messagingSenderId: "115486900431",
            appId: "1:974646288468:web:82cec30f5d2b6f00ef1f43",
            measurementId: "G-JFF7D9P3PH"),
      );
      debugPrint('Firebase (Web) initialized successfully');
    } catch (e) {
      debugPrint('Error initializing Firebase (Web): $e');
    }
  }

  try {
    final notificationService = NotificationService();
    await notificationService.initialize();
    debugPrint('Notification service initialized successfully');
  } catch (e) {
    debugPrint('Error initializing notification service: $e');
    // Continue anyway - notification service is not critical for app startup
  }

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => ThemeService(),
      child: Consumer<ThemeService>(
        builder: (context, themeService, _) {
          return MaterialApp(
            title: 'OOUTH Coop App',
            theme: AppTheme.lightTheme,
            darkTheme: AppTheme.darkTheme,
            themeMode: themeService.themeMode,
            initialRoute: AppRoutes.welcome,
            routes: AppRoutes.routes,
            debugShowCheckedModeBanner: false,
            builder: (context, child) {
              // Add this builder to check for updates after the app is built
              if (!kIsWeb) {
                WidgetsBinding.instance.addPostFrameCallback((_) {
                  AppUpdateService.checkForUpdate(context);
                });
              }
              return child!;
            },
          );
        },
      ),
    );
  }
}
