import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'dart:io';

class DeviceIdService {
  static const String _deviceIdKey = 'app_device_id';
  static String? _cachedDeviceId;

  /// Get unique device identifier
  /// Combines multiple identifiers for better reliability
  static Future<String> getDeviceId() async {
    if (_cachedDeviceId != null) {
      return _cachedDeviceId!;
    }

    try {
      // Check if we have a stored device ID
      final prefs = await SharedPreferences.getInstance();
      String? storedId = prefs.getString(_deviceIdKey);

      if (storedId != null && storedId.isNotEmpty) {
        _cachedDeviceId = storedId;
        return storedId;
      }

      // Generate new device ID based on platform
      String deviceId;

      if (kIsWeb) {
        // Web: Generate and store persistent UUID
        deviceId = await _getWebDeviceId(prefs);
      } else if (Platform.isAndroid) {
        // Android: Use Android ID
        deviceId = await _getAndroidDeviceId();
      } else if (Platform.isIOS) {
        // iOS: Use identifierForVendor
        deviceId = await _getIOSDeviceId();
      } else {
        // Fallback: Generate UUID
        deviceId = _generateUUID();
      }

      // Store for future use
      await prefs.setString(_deviceIdKey, deviceId);
      _cachedDeviceId = deviceId;

      return deviceId;
    } catch (e) {
      debugPrint('Error getting device ID: $e');
      // Fallback to generated UUID
      return _generateUUID();
    }
  }

  /// Get Android device ID
  static Future<String> _getAndroidDeviceId() async {
    try {
      final deviceInfo = DeviceInfoPlugin();
      final androidInfo = await deviceInfo.androidInfo;
      
      // Use Android ID (most reliable)
      String androidId = androidInfo.id;
      
      // Combine with other identifiers for better reliability
      String model = androidInfo.model.replaceAll(' ', '_');
      String manufacturer = androidInfo.manufacturer.replaceAll(' ', '_');
      
      // Create composite ID
      return 'android_${manufacturer}_${model}_${androidId}';
    } catch (e) {
      debugPrint('Error getting Android device ID: $e');
      return _generateUUID();
    }
  }

  /// Get iOS device ID
  static Future<String> _getIOSDeviceId() async {
    try {
      final deviceInfo = DeviceInfoPlugin();
      final iosInfo = await deviceInfo.iosInfo;
      
      // Use identifierForVendor (UUID per vendor)
      String identifier = iosInfo.identifierForVendor ?? '';
      
      if (identifier.isEmpty) {
        return _generateUUID();
      }
      
      // Combine with model for better reliability
      String model = iosInfo.model.replaceAll(' ', '_');
      
      return 'ios_${model}_${identifier}';
    } catch (e) {
      debugPrint('Error getting iOS device ID: $e');
      return _generateUUID();
    }
  }

  /// Get or generate web device ID
  static Future<String> _getWebDeviceId(SharedPreferences prefs) async {
    // Check if we have a stored ID
    String? storedId = prefs.getString(_deviceIdKey);
    
    if (storedId != null && storedId.isNotEmpty) {
      return storedId;
    }
    
    // Generate new UUID for web
    String newId = 'web_${_generateUUID()}';
    await prefs.setString(_deviceIdKey, newId);
    
    return newId;
  }

  /// Generate UUID v4
  static String _generateUUID() {
    // Simple UUID v4 generator
    final random = DateTime.now().millisecondsSinceEpoch.toString();
    final random2 = (1000000 + (DateTime.now().microsecondsSinceEpoch % 1000000)).toString();
    return '${random}_${random2}_${DateTime.now().microsecondsSinceEpoch}';
  }

  /// Reset device ID (for testing or device change)
  static Future<void> resetDeviceId() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_deviceIdKey);
    _cachedDeviceId = null;
  }
}

