import 'package:onesignal_flutter/onesignal_flutter.dart';
import 'package:permission_handler/permission_handler.dart';
import '../features/auth/data/services/auth_service.dart';
import '../config/env.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert' as convert;
import 'package:flutter/foundation.dart';

class NotificationService {
  final AuthService _authService = AuthService();

  Future<void> initialize() async {
    if (kIsWeb) {
      // Web platform initialization
      // OneSignal Web SDK is initialized in index.html
      // We can still setup device ID monitoring for web
      await setupDeviceId();
      return;
    }

    // Mobile platform initialization
    OneSignal.Debug.setLogLevel(OSLogLevel.verbose);
    OneSignal.initialize(Env.oneSignalAppId);
    await _requestNotificationPermission();
    await setupDeviceId();

    // Listen for device ID changes
    OneSignal.User.pushSubscription.addObserver((state) {
      _handleDeviceIdChange(state.current.id);
    });
  }

  Future<void> _requestNotificationPermission() async {
    if (kIsWeb) {
      // Web permissions are handled by the OneSignal Web SDK
      return;
    }

    try {
      if (!kIsWeb && defaultTargetPlatform == TargetPlatform.iOS) {
        await OneSignal.Notifications.requestPermission(true);
      } else {
        if (await Permission.notification.isDenied) {
          await Permission.notification.request();
        }
      }
    } catch (e) {
      debugPrint('Error requesting notification permission: $e');
    }
  }

  Future<void> setupDeviceId() async {
    try {
      String? pushToken;

      if (kIsWeb) {
        // For web, we need to get the ID from the OneSignal Web SDK
        // This would typically be handled through JavaScript interop
        // The actual implementation depends on your web setup
        return;
      } else {
        pushToken = OneSignal.User.pushSubscription.id;
      }

      if (pushToken != null) {
        await _handleDeviceIdChange(pushToken);
      }
    } catch (e) {
      debugPrint('Error setting up device ID: $e');
    }
  }

  Future<void> _handleDeviceIdChange(String? deviceId) async {
    if (deviceId == null) return;

    try {
      final prefs = await SharedPreferences.getInstance();
      final userData = prefs.getString('user_data');

      if (userData != null) {
        final user = convert.json.decode(userData);
        final coopId = user['CoopID'];

        final success = await _authService.storeOneSignalId(deviceId, coopId);
        if (success) {
          debugPrint('Device ID updated successfully: $deviceId');
        } else {
          debugPrint('Failed to update device ID');
        }
      }
    } catch (e) {
      debugPrint('Error handling device ID change: $e');
    }
  }
}
