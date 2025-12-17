import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'dart:convert';

class AppUpdateService {
  static const String UPDATE_URL =
      'https://www.emmaggi.com/coop_admin/version.json';

  static Future<void> checkForUpdate(BuildContext context) async {
    try {
      // Skip update check for web
      if (kIsWeb) {
        return;
      }

      final PackageInfo packageInfo = await PackageInfo.fromPlatform();
      final response = await http.get(Uri.parse(UPDATE_URL));

      if (response.statusCode == 200) {
        final updateInfo = json.decode(response.body);
        final serverVersion = updateInfo['version'];

        if (_shouldUpdate(packageInfo.version, serverVersion)) {
          _showUpdateDialog(
              context,
              updateInfo['version'],
              updateInfo['changelog'] ?? 'Bug fixes and improvements',
              updateInfo['force_update'] ?? false,
              updateInfo['download_url'] ??
                  'https://www.emmaggi.com/coop_admin/download/app-release.apk');
        }
      }
    } catch (e) {
      debugPrint('Update check failed: $e');
    }
  }

  static bool _shouldUpdate(String currentVersion, String serverVersion) {
    List<int> current = currentVersion.split('.').map(int.parse).toList();
    List<int> server = serverVersion.split('.').map(int.parse).toList();

    for (int i = 0; i < current.length && i < server.length; i++) {
      if (server[i] > current[i]) return true;
      if (server[i] < current[i]) return false;
    }
    return server.length > current.length;
  }

  static void _showUpdateDialog(
    BuildContext context,
    String newVersion,
    String changelog,
    bool forceUpdate,
    String downloadUrl,
  ) {
    showDialog(
      context: context,
      barrierDismissible: !forceUpdate,
      builder: (context) => WillPopScope(
        onWillPop: () async => !forceUpdate,
        child: AlertDialog(
          title: const Text('New Update Available'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('A new version ($newVersion) is available.'),
              const SizedBox(height: 8),
              const Text('What\'s new:'),
              const SizedBox(height: 4),
              Text(changelog),
            ],
          ),
          actions: [
            if (!forceUpdate)
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Later'),
              ),
            ElevatedButton(
              onPressed: () async {
                final Uri url = Uri.parse(downloadUrl);
                if (await canLaunchUrl(url)) {
                  await launchUrl(
                    url,
                    mode: LaunchMode.externalApplication,
                  );
                }
                if (!forceUpdate) {
                  Navigator.pop(context);
                }
              },
              child: const Text('Update Now'),
            ),
          ],
        ),
      ),
    );
  }

  // Platform-specific refresh method
  static void refreshApp(BuildContext context, bool forceUpdate) {
    if (!forceUpdate) {
      Navigator.pop(context);
    }
    // For mobile, we just close the dialog as the user will be redirected to the store
  }
}
