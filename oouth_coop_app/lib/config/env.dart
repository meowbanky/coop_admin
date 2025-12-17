import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:flutter/foundation.dart';

class Env {
  static String get apiBaseUrl {
    if (kIsWeb) {
      return 'https://www.emmaggi.com/coop_admin/auth_api/api';
    }
    return dotenv.env['API_BASE_URL'] ?? '';
  }

  static String get oneSignalAppId {
    if (kIsWeb) return '';
    return dotenv.env['ONESIGNAL_APP_ID'] ??
        '2ec0cda9-7643-471c-9b3f-f607768d243d';
  }

  static void printConfig() {
    debugPrint('API Base URL: $apiBaseUrl');
    debugPrint('Is Web: $kIsWeb');
  }
}
