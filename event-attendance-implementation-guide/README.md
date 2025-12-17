# Event Attendance Feature - Implementation Guide

This guide provides everything needed to implement a location-based event attendance system with device binding and admin override capabilities in a similar PHP/Flutter project.

## 📋 Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Prerequisites](#prerequisites)
4. [Database Setup](#database-setup)
5. [Backend API Implementation](#backend-api-implementation)
6. [Admin Panel Implementation](#admin-panel-implementation)
7. [Mobile App Implementation](#mobile-app-implementation)
8. [Configuration](#configuration)
9. [Testing](#testing)
10. [Security Features](#security-features)

## 🎯 Overview

This feature allows:
- **Admins** to create events with location-based geofencing and configurable grace periods
- **Users** to check in to events when they're within the geofence radius and time window
- **Device binding** to prevent device sharing during events
- **Admins** to manually check in users and reset device locks
- **Admins** to view attendance lists with device IDs and export to Excel
- **Location validation** using GPS coordinates and distance calculation

## ✨ Features

### Core Features
- ✅ Event CRUD operations (Create, Read, Update, Delete)
- ✅ Location-based check-in with geofencing
- ✅ Google Maps integration for location selection
- ✅ Attendance tracking and viewing
- ✅ Excel export for attendance reports
- ✅ One check-in per user per event
- ✅ Distance calculation and validation

### Enhanced Features
- ✅ **Configurable Grace Period** - Admins can set grace period (0-120 minutes) per event
- ✅ **Device Binding** - Prevents same device from checking in multiple users
- ✅ **Time Window Validation** - Only allows check-in during event + grace period
- ✅ **Admin Manual Check-in** - Admins can manually check in users with override
- ✅ **Device Lock Reset** - Admins can reset device locks for legitimate sharing
- ✅ **Member Search** - Autocomplete search for member selection in manual check-in
- ✅ **Enhanced Error Messages** - Specific messages for different validation failures
- ✅ **Admin Override Tracking** - Records which admin manually checked in users

## 🔧 Prerequisites

### Backend (PHP)
- PHP 7.4+ with PDO MySQL extension
- Composer for dependency management
- PhpSpreadsheet library (`composer require phpoffice/phpspreadsheet`)
- Google Maps API key
- Session-based authentication for admin panel
- JWT authentication for mobile API

### Mobile App (Flutter)
- Flutter SDK 3.0+
- `geolocator` package (^10.1.0)
- `google_maps_flutter` package (^2.5.0)
- `device_info_plus` package (^9.1.0)
- Location permissions configured

## 📊 Database Setup

### Step 1: Run Initial Migration

Execute the SQL file: `database/migrations/create_events_table.sql`

This creates two tables:
- `events` - Stores event information
- `event_attendance` - Stores user check-in records

### Step 2: Run Enhancement Migration

Execute the SQL file: `database/migrations/add_device_and_grace_period_to_events.sql`

This adds:
- `grace_period_minutes` to `events` table
- `device_id`, `admin_override`, `checked_in_by_admin` to `event_attendance` table
- Index on `(event_id, device_id)` for device lookups

### Step 3: Verify Tables

```sql
-- Check events table
DESCRIBE events;

-- Check event_attendance table
DESCRIBE event_attendance;

-- Verify grace_period_minutes column exists
SHOW COLUMNS FROM events LIKE 'grace_period_minutes';

-- Verify device_id column exists
SHOW COLUMNS FROM event_attendance LIKE 'device_id';
```

## 🔌 Backend API Implementation

### Required API Endpoints

#### Admin APIs (Session-based auth):

1. **Event Management**
   - `POST /api/admin/events.php` - Create event (includes grace_period_minutes)
   - `GET /api/admin/events.php` - List all events
   - `GET /api/admin/events.php/{id}` - Get event details with attendance
   - `PUT /api/admin/events.php/{id}` - Update event (can update grace_period_minutes)
   - `DELETE /api/admin/events.php/{id}` - Delete event

2. **Attendance Management**
   - `GET /api/admin/export-attendance.php?event_id={id}` - Export attendance to Excel
   - `POST /api/admin/search-members.php?q={search_term}` - Search members for manual check-in
   - `POST /api/admin/manual-checkin.php` - Manually check in a user
   - `POST /api/admin/reset-device-lock.php` - Reset device lock for an event

#### Mobile APIs (JWT auth):

1. **Event Listing**
   - `GET /api/events/list.php?filter={upcoming|active|past|all}` - List events

2. **Event Details**
   - `GET /api/events/details.php?id={id}` - Get event details

3. **Check-in**
   - `POST /api/events/checkin.php` - Check in to event (requires device_id)

### File Structure

```
coop_admin/
├── auth_api/
│   └── api/
│       ├── admin/
│       │   ├── events.php (CRUD operations)
│       │   ├── export-attendance.php (Excel export)
│       │   ├── search-members.php (Member search)
│       │   ├── manual-checkin.php (Admin override)
│       │   └── reset-device-lock.php (Device reset)
│       └── events/
│           ├── list.php (Mobile: list events)
│           ├── details.php (Mobile: event details)
│           └── checkin.php (Mobile: check-in with device binding)
├── event-management.php (Admin UI)
├── event-details.php (Admin: view attendance + manual check-in)
└── config/
    └── EnvConfig.php (Environment config helper)
```

### Check-in Validation Flow

1. **Time Window Check**
   - Current time must be >= start_time
   - Current time must be <= (end_time + grace_period_minutes)
   - Return specific error messages for too early/too late

2. **User Check**
   - Verify user hasn't already checked in (UNIQUE constraint)

3. **Device Check**
   - Verify device hasn't been used by another user for this event
   - Check device_id against existing attendance records

4. **Location Check**
   - Calculate distance using Haversine formula
   - Verify distance <= geofence_radius
   - Return distance if outside range

## 🖥️ Admin Panel Implementation

### Required Files

1. **event-management.php** - Main event management page
   - Event list table with grace period column
   - Create/Edit event modal with grace period slider
   - Google Maps integration
   - Attendance quick view modal with device_id display

2. **event-details.php** - Event details and attendance view
   - Event information display including grace period
   - Map showing event location
   - Full attendance list with device_id column
   - **Manual Check-in Modal**:
     - Member search field with autocomplete
     - Clear button (X) to clear search
     - Device ID field (optional)
     - Skip location validation checkbox
   - Reset device lock button for each device
   - Admin override indicators

3. **Navigation Updates**
   - Add "Event Management" to sidebar
   - Add menu item in header navigation

### Key Features

- Google Maps API key from `.env` file
- Location picker (map or current location)
- Geofence radius configuration (10-500m)
- Grace period configuration (0-120 minutes)
- Member search with autocomplete
- Device ID display in attendance tables
- Admin override badges
- Excel export button

## 📱 Mobile App Implementation

### Required Files

```
lib/
├── services/
│   └── device_id_service.dart (NEW - Device ID generation)
└── features/
    └── events/
        ├── data/
        │   ├── models/
        │   │   └── event_model.dart
        │   └── services/
        │       └── event_service.dart (enhanced with device_id)
        └── presentation/
            └── screens/
                ├── events_list_screen.dart
                └── event_details_screen.dart
```

### Device ID Service

**File:** `lib/services/device_id_service.dart`

- Generates unique device identifier
- **Android**: Uses Android ID + model + manufacturer
- **iOS**: Uses identifierForVendor + model
- **Web**: Generates and stores persistent UUID
- Combines multiple identifiers for reliability
- Caches in SharedPreferences

### Navigation Updates

- Add Events tab to bottom navigation (5th tab, index 3)
- Update `MainLayout` to include Events tab
- Add routes in `routes.dart`

### Key Features

- Events list with filters (Upcoming, Active, Past, All)
- Event details with map
- Location-based check-in with device_id
- Distance calculation and display
- One-time check-in per event
- Device binding enforcement
- Time window validation
- Enhanced error messages

## ⚙️ Configuration

### 1. Google Maps API Key

Add to your `.env` or `config.env` file:
```
GOOGLE_MAPS_API_KEY=your_api_key_here
```

### 2. Environment Config Helper

Add method to `EnvConfig.php`:
```php
public static function getGoogleMapsApiKey() {
    return self::get('GOOGLE_MAPS_API_KEY', '');
}
```

### 3. Mobile App Configuration

**pubspec.yaml:**
```yaml
dependencies:
  geolocator: ^10.1.0
  google_maps_flutter: ^2.5.0
  device_info_plus: ^9.1.0
```

**Android** (`android/app/src/main/AndroidManifest.xml`):
```xml
<meta-data
    android:name="com.google.android.geo.API_KEY"
    android:value="YOUR_API_KEY_HERE" />
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION"/>
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION"/>
```

**iOS** (`ios/Runner/AppDelegate.swift`):
```swift
import GoogleMaps

GMSServices.provideAPIKey("YOUR_API_KEY_HERE")
```

**iOS** (`ios/Runner/Info.plist`):
```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>Your location is required to check in to events and calculate distance from event locations.</string>
```

## 🧪 Testing

### Admin Panel Testing

1. Create an event with custom grace period
2. Verify event appears in list with grace period
3. Click attendance count to view attendance
4. Test manual check-in with member search
5. Test device lock reset
6. Export attendance to Excel
7. Edit event details including grace period
8. Delete event

### Mobile App Testing

1. View events list
2. Filter by status (upcoming/active/past)
3. Open event details
4. Check in when within geofence and time window
5. Verify check-in status updates
6. Try checking in twice (should fail)
7. Try checking in with same device for different user (should fail)
8. Try checking in before event starts (should fail)
9. Try checking in after grace period (should fail)
10. Verify device ID is generated correctly

## 🔒 Security Features

### 1. Device Binding
- Prevents device sharing during events
- One device can only check in one user per event
- Admin can reset device locks for legitimate scenarios

### 2. Time Window
- Prevents early check-ins (before event starts)
- Prevents late check-ins (after grace period)
- Configurable grace period per event (0-120 minutes)

### 3. One Per User
- Enforced by UNIQUE constraint on (event_id, user_coop_id)
- Prevents duplicate check-ins

### 4. Location Validation
- Prevents remote check-ins
- Geofence radius validation
- Distance calculation using Haversine formula

### 5. Admin Override
- Allows legitimate exceptions
- Tracks which admin manually checked in user
- Can skip location validation if needed
- Audit trail via admin_override flag

### 6. Member Validation
- Validates Coop ID exists before manual check-in
- Prevents foreign key constraint violations
- Member search ensures valid selection

## 📝 Implementation Checklist

### Database
- [ ] Run initial migration (create_events_table.sql)
- [ ] Run enhancement migration (add_device_and_grace_period_to_events.sql)
- [ ] Verify all columns created correctly
- [ ] Test foreign key constraints
- [ ] Test UNIQUE constraints

### Backend APIs
- [ ] Implement admin events CRUD API (with grace_period_minutes)
- [ ] Implement mobile events list API
- [ ] Implement mobile event details API
- [ ] Implement mobile check-in API (with device binding and time window)
- [ ] Implement Excel export API
- [ ] Implement member search API
- [ ] Implement manual check-in API
- [ ] Implement device lock reset API
- [ ] Add authentication checks
- [ ] Test all endpoints
- [ ] Test validation logic

### Admin Panel
- [ ] Create event-management.php (with grace period slider)
- [ ] Create event-details.php (with manual check-in and device reset)
- [ ] Add Google Maps integration
- [ ] Add member search autocomplete
- [ ] Add navigation menu items
- [ ] Implement Excel export button
- [ ] Add device_id display in attendance tables
- [ ] Add admin override indicators
- [ ] Test all admin features

### Mobile App
- [ ] Create device_id_service.dart
- [ ] Create event model (with grace_period_minutes)
- [ ] Create event service (with device_id)
- [ ] Create events list screen
- [ ] Create event details screen
- [ ] Add Events tab to navigation
- [ ] Configure location permissions
- [ ] Add Google Maps integration
- [ ] Test device ID generation
- [ ] Test check-in functionality
- [ ] Test device binding
- [ ] Test time window validation

### Configuration
- [ ] Add Google Maps API key to .env
- [ ] Update EnvConfig.php
- [ ] Configure Android manifest
- [ ] Configure iOS AppDelegate
- [ ] Add device_info_plus to pubspec.yaml
- [ ] Test API key loading

## 📚 Additional Resources

- [Google Maps Platform Documentation](https://developers.google.com/maps/documentation)
- [PhpSpreadsheet Documentation](https://phpspreadsheet.readthedocs.io/)
- [Flutter Geolocator Package](https://pub.dev/packages/geolocator)
- [Flutter Google Maps Package](https://pub.dev/packages/google_maps_flutter)
- [Flutter Device Info Plus Package](https://pub.dev/packages/device_info_plus)

## 🐛 Troubleshooting

### Common Issues

1. **Maps not loading**: Check API key configuration
2. **Check-in fails**: Verify location permissions
3. **Distance calculation wrong**: Check Haversine formula implementation
4. **Excel export fails**: Verify PhpSpreadsheet installation
5. **CORS errors**: Check API CORS headers
6. **Device ID not generating**: Check device_info_plus package installation
7. **Foreign key constraint violation**: Ensure Coop ID exists before manual check-in
8. **Device binding not working**: Verify device_id is being sent in check-in request

## 📄 License

This implementation guide is provided as-is for use in similar projects.
