# Code Examples & Snippets

Quick reference code snippets for implementing the event attendance feature with device binding and admin override capabilities.

## 🔐 Environment Configuration

### config/EnvConfig.php
```php
public static function getGoogleMapsApiKey() {
    return self::get('GOOGLE_MAPS_API_KEY', '');
}
```

### config.env
```env
GOOGLE_MAPS_API_KEY=your_api_key_here
```

## 📊 Database Queries

### Get Events with Attendance Count (Enhanced)
```php
$query = "SELECT 
    e.id,
    e.title,
    e.description,
    e.start_time,
    e.end_time,
    e.location_lat,
    e.location_lng,
    e.geofence_radius,
    e.grace_period_minutes,
    COUNT(DISTINCT ea.id) as attendance_count
FROM events e
LEFT JOIN event_attendance ea ON ea.event_id = e.id
GROUP BY e.id
ORDER BY e.start_time DESC";
```

### Get Event with Full Attendance List (Enhanced)
```php
$query = "SELECT 
    e.*,
    ea.id as attendance_id,
    ea.user_coop_id,
    ea.device_id,
    ea.admin_override,
    ea.checked_in_by_admin,
    CONCAT(u.FirstName, ' ', u.LastName) as user_name,
    ea.check_in_time,
    ea.distance_from_event,
    ea.status
FROM events e
LEFT JOIN event_attendance ea ON ea.event_id = e.id
LEFT JOIN tblemployees u ON u.CoopID = ea.user_coop_id
WHERE e.id = :event_id
ORDER BY ea.check_in_time DESC";
```

### Check-in Validation Query
```php
// Check time window
$now = date('Y-m-d H:i:s');
$endTimeWithGrace = new DateTime($event['end_time']);
$endTimeWithGrace->modify("+{$event['grace_period_minutes']} minutes");
$checkInDeadline = $endTimeWithGrace->format('Y-m-d H:i:s');

if ($now < $event['start_time']) {
    throw new Exception('Check-in is only available during the event...');
}

if ($now > $checkInDeadline) {
    throw new Exception('Check-in period has ended...');
}

// Check if user already checked in
$checkUserQuery = "SELECT id FROM event_attendance 
    WHERE event_id = :event_id AND user_coop_id = :user_coop_id";
    
// Check if device already used
$checkDeviceQuery = "SELECT user_coop_id FROM event_attendance 
    WHERE event_id = :event_id AND device_id = :device_id 
    AND user_coop_id != :user_coop_id LIMIT 1";
```

## 🔌 API Endpoint Examples

### Check-in API (Enhanced with Device Binding)
```php
// POST /api/events/checkin.php
$input = json_decode(file_get_contents('php://input'), true);

$eventId = intval($input['event_id'] ?? 0);
$userLat = floatval($input['latitude'] ?? 0);
$userLng = floatval($input['longitude'] ?? 0);
$deviceId = trim($input['device_id'] ?? '');

// Validate device ID
if (empty($deviceId)) {
    throw new Exception('Device ID is required', 400);
}

// Get event with grace period
$eventQuery = "SELECT 
    id, start_time, end_time, location_lat, location_lng, 
    geofence_radius, COALESCE(grace_period_minutes, 20) as grace_period_minutes
FROM events WHERE id = :event_id";

// Time window validation
$endTime = new DateTime($event['end_time']);
$endTime->modify("+{$event['grace_period_minutes']} minutes");
$checkInDeadline = $endTime->format('Y-m-d H:i:s');

// Device binding check
$deviceCheckQuery = "SELECT user_coop_id FROM event_attendance 
    WHERE event_id = :event_id AND device_id = :device_id 
    AND user_coop_id != :user_coop_id LIMIT 1";

// Insert check-in
$insertQuery = "INSERT INTO event_attendance 
    (event_id, user_coop_id, check_in_lat, check_in_lng, 
     distance_from_event, device_id, status, admin_override)
    VALUES (:event_id, :user_coop_id, :check_in_lat, :check_in_lng, 
            :distance_from_event, :device_id, 'present', 0)";
```

### Manual Check-in API
```php
// POST /api/admin/manual-checkin.php
$userCoopId = trim($input['user_coop_id'] ?? '');
$deviceId = trim($input['device_id'] ?? '') ?: 'admin-override-' . time() . '-' . rand(1000, 9999);
$skipLocationCheck = isset($input['skip_location_check']) && $input['skip_location_check'] === true;

// Validate Coop ID exists
$validateUserQuery = "SELECT CoopID FROM tblemployees WHERE CoopID = :coop_id LIMIT 1";
$validateStmt = $db->prepare($validateUserQuery);
$validateStmt->execute([':coop_id' => $userCoopId]);

if ($validateStmt->rowCount() === 0) {
    throw new Exception('Invalid Coop ID. Member not found in the system.', 400);
}

// Insert with admin override
$insertQuery = "INSERT INTO event_attendance 
    (event_id, user_coop_id, check_in_lat, check_in_lng, 
     distance_from_event, device_id, status, admin_override, checked_in_by_admin)
    VALUES (:event_id, :user_coop_id, :check_in_lat, :check_in_lng, 
            :distance_from_event, :device_id, 'present', 1, :admin_username)";
```

### Member Search API
```php
// GET /api/admin/search-members.php?q={search_term}
$searchTerm = trim($_GET['q'] ?? '');
$searchPattern = '%' . $searchTerm . '%';

$query = "SELECT 
    CoopID,
    FirstName,
    MiddleName,
    LastName,
    CONCAT(FirstName, ' ', COALESCE(MiddleName, ''), ' ', LastName) as FullName,
    EmailAddress,
    MobileNumber
FROM tblemployees
WHERE (CoopID LIKE :search 
    OR FirstName LIKE :search 
    OR LastName LIKE :search 
    OR MiddleName LIKE :search
    OR CONCAT(FirstName, ' ', COALESCE(MiddleName, ''), ' ', LastName) LIKE :search)
ORDER BY FirstName, LastName
LIMIT 20";
```

### Reset Device Lock API
```php
// POST /api/admin/reset-device-lock.php
$eventId = intval($input['event_id'] ?? 0);
$deviceId = trim($input['device_id'] ?? '');

// Delete attendance records for this device
$deleteQuery = "DELETE FROM event_attendance 
    WHERE event_id = :event_id AND device_id = :device_id";
$deleteStmt = $db->prepare($deleteQuery);
$deleteStmt->execute([
    ':event_id' => $eventId,
    ':device_id' => $deviceId
]);

$deletedCount = $deleteStmt->rowCount();
```

## 📱 Flutter Code Examples

### Device ID Service
```dart
// lib/services/device_id_service.dart
import 'package:device_info_plus/device_info_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:io';

class DeviceIdService {
  static const String _deviceIdKey = 'app_device_id';
  static String? _cachedDeviceId;

  static Future<String> getDeviceId() async {
    if (_cachedDeviceId != null) {
      return _cachedDeviceId!;
    }

    final prefs = await SharedPreferences.getInstance();
    String? storedId = prefs.getString(_deviceIdKey);

    if (storedId != null && storedId.isNotEmpty) {
      _cachedDeviceId = storedId;
      return storedId;
    }

    String deviceId;
    if (Platform.isAndroid) {
      final deviceInfo = DeviceInfoPlugin();
      final androidInfo = await deviceInfo.androidInfo;
      String model = androidInfo.model.replaceAll(' ', '_');
      String manufacturer = androidInfo.manufacturer.replaceAll(' ', '_');
      deviceId = 'android_${manufacturer}_${model}_${androidInfo.id}';
    } else if (Platform.isIOS) {
      final deviceInfo = DeviceInfoPlugin();
      final iosInfo = await deviceInfo.iosInfo;
      String identifier = iosInfo.identifierForVendor ?? '';
      String model = iosInfo.model.replaceAll(' ', '_');
      deviceId = 'ios_${model}_${identifier}';
    } else {
      deviceId = 'web_${DateTime.now().millisecondsSinceEpoch}';
    }

    await prefs.setString(_deviceIdKey, deviceId);
    _cachedDeviceId = deviceId;
    return deviceId;
  }
}
```

### Event Service (Enhanced)
```dart
// lib/features/events/data/services/event_service.dart
import '../../../../services/device_id_service.dart';

Future<Map<String, dynamic>> checkIn({
  required int eventId,
  required double latitude,
  required double longitude,
}) async {
  final token = await _getToken();
  final deviceId = await DeviceIdService.getDeviceId();

  final response = await http.post(
    Uri.parse('$baseUrl/events/checkin.php'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: json.encode({
      'event_id': eventId,
      'latitude': latitude,
      'longitude': longitude,
      'device_id': deviceId,
    }),
  );

  final result = json.decode(response.body);
  return result;
}
```

### Event Model (Enhanced)
```dart
// lib/features/events/data/models/event_model.dart
class EventModel {
  final int id;
  final String title;
  final String? description;
  final DateTime startTime;
  final DateTime endTime;
  final double locationLat;
  final double locationLng;
  final int geofenceRadius;
  final int gracePeriodMinutes; // NEW

  EventModel({
    required this.id,
    required this.title,
    this.description,
    required this.startTime,
    required this.endTime,
    required this.locationLat,
    required this.locationLng,
    required this.geofenceRadius,
    this.gracePeriodMinutes = 20, // Default
  });

  bool isWithinCheckInWindow() {
    final now = DateTime.now();
    final deadline = endTime.add(Duration(minutes: gracePeriodMinutes));
    return now.isAfter(startTime) && now.isBefore(deadline);
  }

  bool isTooEarly() {
    return DateTime.now().isBefore(startTime);
  }

  bool isTooLate() {
    final deadline = endTime.add(Duration(minutes: gracePeriodMinutes));
    return DateTime.now().isAfter(deadline);
  }
}
```

## 🖥️ Admin Panel Code Examples

### Event Form with Grace Period
```html
<!-- Grace Period Slider -->
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
        Grace Period (minutes after event ends) <span class="text-red-500">*</span>
    </label>
    <div class="flex items-center gap-4">
        <input type="range" id="event-grace-period" min="0" max="120" value="20" step="5" class="flex-1"
            oninput="document.getElementById('grace-value').textContent = this.value + ' min'">
        <span id="grace-value" class="text-lg font-semibold text-blue-600 w-24 text-right">20 min</span>
    </div>
    <p class="text-xs text-gray-500 mt-1">Users can check in up to this many minutes after the event ends</p>
</div>
```

### Member Search Autocomplete
```javascript
// Member search with autocomplete
function setupMemberSearch() {
    const searchInput = document.getElementById('manual-member-search');
    const resultsDiv = document.getElementById('member-search-results');
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            resultsDiv.style.display = 'none';
            return;
        }
        
        // Debounce search
        clearTimeout(memberSearchTimeout);
        memberSearchTimeout = setTimeout(() => {
            searchMembers(query);
        }, 300);
    });
}

async function searchMembers(query) {
    const response = await fetch(`${apiBaseUrl}/admin/search-members.php?q=${encodeURIComponent(query)}`, {
        credentials: 'include'
    });
    
    const result = await response.json();
    if (result.success && result.data.length > 0) {
        let html = '';
        result.data.forEach(member => {
            html += `
                <div class="px-4 py-2 hover:bg-blue-50 cursor-pointer member-result-item"
                     data-coop-id="${member.coop_id}"
                     data-name="${member.full_name}">
                    <div class="font-medium">${member.full_name}</div>
                    <div class="text-sm text-gray-500">Coop ID: ${member.coop_id}</div>
                </div>
            `;
        });
        resultsDiv.innerHTML = html;
        resultsDiv.style.display = 'block';
    }
}
```

### Manual Check-in Form
```javascript
// Manual check-in form submission
document.getElementById('manualCheckInForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const userCoopId = document.getElementById('manual-user-coop-id').value.trim();
    const deviceId = document.getElementById('manual-device-id').value.trim();
    const skipLocationCheck = document.getElementById('skip-location-check').checked;
    
    if (!userCoopId) {
        Swal.fire('Error', 'Please search and select a member', 'error');
        return;
    }
    
    const response = await fetch(`${apiBaseUrl}/admin/manual-checkin.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            event_id: eventId,
            user_coop_id: userCoopId,
            device_id: deviceId || undefined,
            skip_location_check: skipLocationCheck
        })
    });
    
    const result = await response.json();
    if (result.success) {
        Swal.fire('Success!', result.message, 'success');
        loadEventDetails();
    }
});
```

### Attendance Table with Device ID
```javascript
// Display attendance with device_id and admin override
attendance.forEach(record => {
    const isAdminOverride = record.admin_override === true || record.admin_override === 1;
    html += `<tr>
        <td>${escapeHtml(record.user_name || 'Unknown')}
            ${isAdminOverride ? '<span class="badge bg-purple-100">Admin</span>' : ''}
        </td>
        <td>${escapeHtml(record.user_coop_id)}</td>
        <td>${formatDateTime(record.check_in_time)}</td>
        <td>${record.distance_from_event.toFixed(2)}m</td>
        <td>
            <span class="font-mono text-xs">${escapeHtml(record.device_id || 'N/A')}</span>
            <button onclick="resetDeviceLock('${record.device_id}')" class="text-red-600">
                <i class="fas fa-unlock"></i>
            </button>
        </td>
        <td><span class="badge bg-green-100">${record.status}</span></td>
    </tr>`;
});
```

## 🧮 Distance Calculation

### PHP (Haversine Formula)
```php
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}
```

### Dart (Using Geolocator)
```dart
double calculateDistance(double lat1, double lon1, double lat2, double lon2) {
  return Geolocator.distanceBetween(lat1, lon1, lat2, lon2);
}
```

## 🔒 Error Handling Examples

### Check-in Error Messages
```php
// Too early
if ($now < $event['start_time']) {
    $startTime = new DateTime($event['start_time']);
    throw new Exception('Check-in is only available during the event. Event starts at ' . 
        $startTime->format('F j, Y g:i A'), 400);
}

// Too late
if ($now > $checkInDeadline) {
    $deadlineTime = new DateTime($checkInDeadline);
    throw new Exception('Check-in period has ended. The grace period expired at ' . 
        $deadlineTime->format('F j, Y g:i A'), 400);
}

// Already checked in
if ($existingStmt->rowCount() > 0) {
    throw new Exception('You have already checked in to this event', 400);
}

// Device already used
if ($deviceStmt->rowCount() > 0) {
    throw new Exception('This device has already been used to check in another user for this event', 400);
}

// Outside geofence
if ($distance > $geofenceRadius) {
    echo json_encode([
        'success' => false,
        'message' => 'You are too far from the event location',
        'distance' => round($distance, 2),
        'required_radius' => $geofenceRadius,
        'within_range' => false
    ]);
    exit();
}
```

## 📝 SQL Migration Examples

### Add Grace Period and Device Fields
```sql
-- Add grace_period_minutes to events table
ALTER TABLE events 
ADD COLUMN grace_period_minutes INT NOT NULL DEFAULT 20 
COMMENT 'Grace period in minutes after event ends for check-in (default 20 minutes)';

-- Add device_id to event_attendance table
ALTER TABLE event_attendance 
ADD COLUMN device_id VARCHAR(255) NULL 
COMMENT 'Device identifier used for check-in';

-- Add index for device lookup
ALTER TABLE event_attendance 
ADD INDEX idx_event_device (event_id, device_id);

-- Add admin_override flag
ALTER TABLE event_attendance 
ADD COLUMN admin_override TINYINT(1) DEFAULT 0 
COMMENT 'Flag indicating if check-in was done by admin override';

-- Add checked_in_by_admin field
ALTER TABLE event_attendance 
ADD COLUMN checked_in_by_admin VARCHAR(255) NULL 
COMMENT 'Admin username who manually checked in the user';
```

## 🎨 UI Component Examples

### Clear Button for Search Field
```html
<div class="relative">
    <input type="text" id="manual-member-search" 
        class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg">
    <button type="button" id="clear-member-search" onclick="clearMemberSearch()"
        class="hidden absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
        <i class="fas fa-times"></i>
    </button>
</div>
```

```javascript
function clearMemberSearch() {
    document.getElementById('manual-member-search').value = '';
    document.getElementById('manual-user-coop-id').value = '';
    document.getElementById('member-search-results').style.display = 'none';
    document.getElementById('clear-member-search').classList.add('hidden');
}
```
