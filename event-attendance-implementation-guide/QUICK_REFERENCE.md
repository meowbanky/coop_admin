# Quick Reference Guide

## 📋 File Checklist

### Database
- [ ] `database/migrations/create_events_table.sql` (Initial tables)
- [ ] `database/migrations/add_device_and_grace_period_to_events.sql` (Enhancements)

### Backend APIs
- [ ] `auth_api/api/admin/events.php` (CRUD with grace_period_minutes)
- [ ] `auth_api/api/admin/export-attendance.php` (Excel export)
- [ ] `auth_api/api/admin/search-members.php` (Member search for manual check-in)
- [ ] `auth_api/api/admin/manual-checkin.php` (Admin override)
- [ ] `auth_api/api/admin/reset-device-lock.php` (Device reset)
- [ ] `auth_api/api/events/list.php` (Mobile: list)
- [ ] `auth_api/api/events/details.php` (Mobile: details)
- [ ] `auth_api/api/events/checkin.php` (Mobile: check-in with device binding)

### Admin Panel
- [ ] `event-management.php` (Main page with grace period slider)
- [ ] `event-details.php` (Details page with manual check-in and device reset)
- [ ] `sidebar.php` (Add menu item)
- [ ] `includes/header.php` (Add menu item)
- [ ] `config/EnvConfig.php` (Add getGoogleMapsApiKey method)
- [ ] `config.env` (Add GOOGLE_MAPS_API_KEY)

### Mobile App
- [ ] `lib/services/device_id_service.dart` (Device ID generation)
- [ ] `lib/features/events/data/models/event_model.dart` (With grace_period_minutes)
- [ ] `lib/features/events/data/services/event_service.dart` (With device_id)
- [ ] `lib/features/events/presentation/screens/events_list_screen.dart`
- [ ] `lib/features/events/presentation/screens/event_details_screen.dart`
- [ ] `lib/shared/widgets/main_layout.dart` (Add Events tab)
- [ ] `lib/config/routes/routes.dart` (Add route)
- [ ] `pubspec.yaml` (Add packages: geolocator, google_maps_flutter, device_info_plus)
- [ ] `android/app/src/main/AndroidManifest.xml` (API key + permissions)
- [ ] `ios/Runner/AppDelegate.swift` (Google Maps setup)
- [ ] `ios/Runner/Info.plist` (Location permissions)

## 🔑 Key Configuration Values

### Environment Variables
```env
GOOGLE_MAPS_API_KEY=your_api_key_here
```

### Composer Dependencies
```json
{
    "phpoffice/phpspreadsheet": "^1.29"
}
```

### Flutter Dependencies
```yaml
dependencies:
  geolocator: ^10.1.0
  google_maps_flutter: ^2.5.0
  device_info_plus: ^9.1.0
```

## 📐 Database Schema Summary

### events table
- Primary key: `id`
- Required: `title`, `start_time`, `end_time`, `location_lat`, `location_lng`, `geofence_radius`, `grace_period_minutes`, `created_by`
- Optional: `description`
- Default: `geofence_radius` = 50, `grace_period_minutes` = 20

### event_attendance table
- Primary key: `id`
- Foreign keys: `event_id` → events.id, `user_coop_id` → users table
- Unique constraint: `(event_id, user_coop_id)` - one check-in per user per event
- Required: All fields except `check_in_time` (has default), `device_id`, `admin_override`, `checked_in_by_admin` (optional)
- Index: `(event_id, device_id)` for device lookup

## 🔌 API Endpoints Summary

### Admin (Session Auth)
- `POST /api/admin/events.php` - Create (with grace_period_minutes)
- `GET /api/admin/events.php` - List
- `GET /api/admin/events.php/{id}` - Details (with device_id and admin_override)
- `PUT /api/admin/events.php/{id}` - Update (can update grace_period_minutes)
- `DELETE /api/admin/events.php/{id}` - Delete
- `GET /api/admin/export-attendance.php?event_id={id}` - Export Excel
- `GET /api/admin/search-members.php?q={search}` - Search members
- `POST /api/admin/manual-checkin.php` - Manual check-in
- `POST /api/admin/reset-device-lock.php` - Reset device lock

### Mobile (JWT Auth)
- `GET /api/events/list.php?filter={filter}` - List events (with grace_period_minutes)
- `GET /api/events/details.php?id={id}` - Event details (with grace_period_minutes)
- `POST /api/events/checkin.php` - Check in (requires device_id)

## 🧮 Distance Calculation (Haversine Formula)

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

```dart
double calculateDistance(double lat1, double lon1, double lat2, double lon2) {
  return Geolocator.distanceBetween(lat1, lon1, lat2, lon2);
}
```

## 🔐 Check-in Validation Flow

1. **Time Window Check**
   - Current time >= start_time
   - Current time <= (end_time + grace_period_minutes)
   - Error messages: "Check-in is only available during the event..." or "Check-in period has ended..."

2. **User Check**
   - Verify user hasn't already checked in
   - Error: "You have already checked in to this event"

3. **Device Check**
   - Verify device hasn't been used by another user
   - Error: "This device has already been used to check in another user..."

4. **Location Check**
   - Calculate distance using Haversine formula
   - Verify distance <= geofence_radius
   - Error: "You are too far from the event location" (with distance)

## 🎨 UI Components

### Admin Panel
- Event list table with status badges and grace period
- Create/Edit modal with map picker and grace period slider (0-120 min)
- Attendance modal with export button, device_id column, admin override badges
- Event details page with map
- Manual check-in modal with member search autocomplete
- Device lock reset functionality

### Mobile App
- Events list with filter tabs
- Event cards with status indicators
- Event details with map and check-in button
- Distance display when outside geofence
- Device ID generation and transmission

## 🔒 Security Checklist

- [ ] All APIs require authentication
- [ ] Input validation on all endpoints
- [ ] SQL injection prevention (prepared statements)
- [ ] API key stored in .env (not in code)
- [ ] CORS headers configured
- [ ] Location permissions requested properly
- [ ] Error messages don't expose sensitive info
- [ ] Device binding enforced
- [ ] Time window validation enforced
- [ ] Coop ID validation before manual check-in
- [ ] Foreign key constraints enforced

## 🐛 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Maps not loading | Check API key in .env and code |
| Check-in fails | Verify location permissions |
| Distance wrong | Check coordinate order (lat, lng) |
| Excel export fails | Verify PhpSpreadsheet installed |
| CORS errors | Check API CORS headers |
| Duplicate check-in | Verify UNIQUE constraint exists |
| Device binding not working | Verify device_id is sent in request |
| Foreign key violation | Validate Coop ID exists before manual check-in |
| Time window error | Check grace_period_minutes is set correctly |

## 📱 Testing Checklist

### Admin Panel
- [ ] Create event with location and grace period
- [ ] Edit event details including grace period
- [ ] Delete event
- [ ] View attendance list with device_id
- [ ] Export to Excel
- [ ] Map displays correctly
- [ ] Manual check-in with member search
- [ ] Reset device lock
- [ ] Verify admin override badges

### Mobile App
- [ ] Events list loads
- [ ] Filters work correctly
- [ ] Event details display with grace period
- [ ] Map shows event location
- [ ] Check-in when within range and time window
- [ ] Check-in fails when outside range
- [ ] Check-in fails when too early
- [ ] Check-in fails when too late (after grace period)
- [ ] Can't check in twice
- [ ] Can't use same device for different user
- [ ] Distance displays correctly
- [ ] Device ID generates correctly

## 🎯 Key Features Summary

### Core Features
- ✅ Event CRUD operations
- ✅ Location-based check-in with geofencing
- ✅ Google Maps integration
- ✅ Attendance tracking
- ✅ Excel export

### Enhanced Features
- ✅ Configurable grace period (0-120 minutes)
- ✅ Device binding (prevents device sharing)
- ✅ Time window validation
- ✅ Admin manual check-in
- ✅ Device lock reset
- ✅ Member search autocomplete
- ✅ Enhanced error messages
- ✅ Admin override tracking

## 📝 Error Messages Reference

- **Too Early**: "Check-in is only available during the event. Event starts at [time]"
- **Too Late**: "Check-in period has ended. The grace period expired at [time]"
- **Already Checked In**: "You have already checked in to this event"
- **Device Used**: "This device has already been used to check in another user for this event"
- **Outside Range**: "You are too far from the event location" (with distance)
- **Invalid Coop ID**: "Invalid Coop ID. Member not found in the system."
