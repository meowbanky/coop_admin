# Event Attendance Feature - AI Implementation Prompt

Use this prompt with an AI assistant (like Claude, GPT-4, etc.) to implement the event attendance feature in your project.

---

## Prompt for AI Assistant

I need to implement a location-based event attendance system with device binding and admin override capabilities in my PHP/Flutter application. Here's what I need:

### Project Structure

- **Backend**: PHP with PDO MySQL, session-based admin auth, JWT for mobile API
- **Admin Panel**: PHP with Tailwind CSS, uses Google Maps
- **Mobile App**: Flutter/Dart with provider state management

### Database Requirements

Create two tables with enhanced features:

1. **events** table:

   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
   - title (VARCHAR(255), NOT NULL)
   - description (TEXT, NULL)
   - start_time (DATETIME, NOT NULL)
   - end_time (DATETIME, NOT NULL)
   - location_lat (DECIMAL(10,8), NOT NULL)
   - location_lng (DECIMAL(11,8), NOT NULL)
   - geofence_radius (INT, NOT NULL, DEFAULT 50) - in meters
   - grace_period_minutes (INT, NOT NULL, DEFAULT 20) - minutes after event ends for check-in
   - created_by (VARCHAR(255), NOT NULL)
   - created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
   - updated_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
   - Indexes on start_time, end_time, and location

2. **event_attendance** table:
   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
   - event_id (INT, NOT NULL, FOREIGN KEY to events.id)
   - user_coop_id (VARCHAR(50), NOT NULL, FOREIGN KEY to users table)
   - check_in_time (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
   - check_in_lat (DECIMAL(10,8), NOT NULL)
   - check_in_lng (DECIMAL(11,8), NOT NULL)
   - distance_from_event (DECIMAL(10,2), NOT NULL) - in meters
   - device_id (VARCHAR(255), NULL) - Device identifier for device binding
   - status (ENUM('present', 'late', 'absent'), DEFAULT 'present')
   - admin_override (TINYINT(1), DEFAULT 0) - Flag for admin manual check-in
   - checked_in_by_admin (VARCHAR(255), NULL) - Admin username who manually checked in
   - UNIQUE constraint on (event_id, user_coop_id) - one check-in per user per event
   - Indexes on event_id, user_coop_id, check_in_time, and (event_id, device_id)

### Backend API Endpoints Needed

#### Admin APIs (Session-based authentication):

1. **POST /api/admin/events.php**

   - Create new event
   - Validate: title required, location required, end_time > start_time, grace_period_minutes (0-120)
   - Return: event ID

2. **GET /api/admin/events.php**

   - List all events
   - Include attendance_count and grace_period_minutes for each event
   - Order by start_time DESC

3. **GET /api/admin/events.php/{id}**

   - Get event details
   - Include full attendance list with user names, device_id, admin_override flags
   - Return: event data + attendance array

4. **PUT /api/admin/events.php/{id}**

   - Update event (partial update supported)
   - Can update grace_period_minutes
   - Validate same as create
   - Return: updated event data

5. **DELETE /api/admin/events.php/{id}**

   - Delete event (cascade deletes attendance)
   - Return: success message

6. **GET /api/admin/export-attendance.php?event_id={id}**

   - Export attendance to Excel using PhpSpreadsheet
   - Include event info, user names, check-in times, distances, device_id, admin_override
   - Format with headers, alternating row colors
   - Download as .xlsx file

7. **POST /api/admin/search-members.php?q={search_term}**

   - Search members by name or Coop ID
   - Returns up to 20 matching members
   - Used for manual check-in member selection
   - Return: array of members with coop_id, full_name, etc.

8. **POST /api/admin/manual-checkin.php**

   - Body: { event_id, user_coop_id, device_id (optional), skip_location_check (optional) }
   - Validate Coop ID exists in users table
   - Check if user already checked in
   - Create attendance record with admin_override flag
   - Auto-generate device_id if not provided
   - Return: success message

9. **POST /api/admin/reset-device-lock.php**
   - Body: { event_id, device_id }
   - Remove check-in record(s) for a device
   - Free device for another user
   - Return: success message with count of deleted records

#### Mobile APIs (JWT authentication):

1. **GET /api/events/list.php?filter={upcoming|active|past|all}**

   - List events filtered by status
   - Include has_checked_in flag for current user
   - Calculate status based on current time vs event times
   - Return: array of events with grace_period_minutes

2. **GET /api/events/details.php?id={id}**

   - Get single event details
   - Include has_checked_in flag
   - Return: event object with grace_period_minutes

3. **POST /api/events/checkin.php**
   - Body: { event_id, latitude, longitude, device_id }
   - Validate time window: must be between start_time and (end_time + grace_period_minutes)
   - Validate user hasn't checked in already
   - Validate device hasn't been used by another user for this event
   - Calculate distance from event location using Haversine formula
   - Check if within geofence radius
   - If within range: create attendance record, return success
   - If outside range: return error with distance
   - Return: success/error with appropriate error messages:
     - Too early: "Check-in is only available during the event. Event starts at [time]"
     - Too late: "Check-in period has ended. The grace period expired at [time]"
     - Already checked in: "You have already checked in to this event"
     - Device used: "This device has already been used to check in another user for this event"
     - Outside range: "You are too far from the event location" (with distance)

### Admin Panel Pages Needed

1. **event-management.php**

   - Table showing all events with: title, start/end time, location, radius, grace period, attendance count, status
   - "Create Event" button
   - Edit/Delete actions for each event
   - Click attendance count to open modal with attendance list
   - Modal includes "Export to Excel" button
   - Attendance modal shows device_id and admin_override badges
   - Create/Edit modal with:
     - Title, description fields
     - Start/End datetime pickers
     - Location picker: "Use My Location" button OR "Select on Map" button
     - Google Maps integration for map selection
     - Geofence radius slider (10-500m)
     - Grace period slider (0-120 minutes)
   - Use Google Maps API key from .env file via EnvConfig

2. **event-details.php**

   - Show event information including grace period
   - Map displaying event location with geofence circle
   - Full attendance list table with device_id column
   - "Manual Check-in" button
   - Manual check-in modal with:
     - Member search field (autocomplete, searches by name or Coop ID)
     - Clear button (X) to clear search field
     - Device ID field (optional, auto-generates if empty)
     - Skip location validation checkbox
   - Reset device lock button next to each device_id in attendance table
   - Admin override indicators (badge showing "Admin" for manually checked-in users)
   - Back button to event-management.php

3. **Navigation Updates**
   - Add "Event Management" menu item to sidebar
   - Add to modern sidebar in header.php

### Mobile App Screens Needed

1. **Events List Screen** (events_list_screen.dart)

   - Filter tabs: Upcoming, Active, Past, All
   - Event cards showing: title, description, start/end time, status badge, check-in indicator
   - Pull-to-refresh
   - Tap card to open event details
   - Use MainLayout with currentIndex: 3

2. **Event Details Screen** (event_details_screen.dart)

   - Event title, description, times
   - Google Maps showing:
     - Event location marker
     - User location marker
     - Geofence circle
   - Distance display (if available)
   - "Check In" button (only if not checked in and event is active/within grace period)
   - Check-in status indicator if already checked in
   - Request location permission
   - Calculate distance using geolocator package
   - Show error if outside geofence with distance
   - Display appropriate error messages for time window violations

3. **Navigation Updates**
   - Add Events tab (5th tab, index 3) to MainLayout
   - Update navigation handler to route to /events
   - Add route in routes.dart
   - Update events_list_screen to use currentIndex: 3

### Service Layer

1. **DeviceIdService** (device_id_service.dart) - NEW

   - getDeviceId() - Returns unique device identifier
   - Android: Uses Android ID + model + manufacturer
   - iOS: Uses identifierForVendor + model
   - Web: Generates and stores persistent UUID
   - Combines multiple identifiers for reliability
   - Caches device ID in SharedPreferences

2. **EventService** (event_service.dart)

   - getEvents(filter: String) - fetch events list
   - getEventDetails(eventId: int) - fetch single event
   - checkIn(eventId: int, latitude: double, longitude: double) - check in to event
   - Must include device_id from DeviceIdService.getDeviceId()

3. **EventModel** (event_model.dart)
   - Model class with all event fields including grace_period_minutes
   - Helper methods: isActive, isUpcoming, isPast, isWithinCheckInWindow

### Configuration

1. **Environment Variables**

   - Add GOOGLE_MAPS_API_KEY to config.env
   - Add getGoogleMapsApiKey() method to EnvConfig.php

2. **Mobile App Configuration**
   - Add geolocator, google_maps_flutter, and device_info_plus to pubspec.yaml
   - Configure location permissions in AndroidManifest.xml and Info.plist
   - Add Google Maps API key to AndroidManifest.xml and AppDelegate.swift

### Key Implementation Details

- **Distance Calculation**: Use Haversine formula for accurate distance between coordinates
- **Geofencing**: Check if user distance <= geofence_radius before allowing check-in
- **Time Window**: Check-in allowed from start_time to (end_time + grace_period_minutes)
- **One Check-in Rule**: Enforce UNIQUE constraint on (event_id, user_coop_id)
- **Device Binding**: Prevent same device from checking in multiple users for same event
- **Device ID**: Generate unique device ID combining platform-specific identifiers
- **Admin Override**: Allow admins to manually check in users, bypassing location/time checks
- **Member Search**: Use autocomplete search for member selection in manual check-in
- **Status Calculation**: Compare current time with event start_time and (end_time + grace_period_minutes)
- **Error Handling**: Proper error messages for:
  - Too early check-in
  - Too late check-in (after grace period)
  - Already checked in
  - Device already used
  - Outside geofence
- **Security**: Validate all inputs, use prepared statements, authenticate all requests, validate Coop ID exists before manual check-in

### Files to Create/Modify

**Backend:**

- database/migrations/create_events_table.sql (initial tables)
- database/migrations/add_device_and_grace_period_to_events.sql (enhancements)
- auth_api/api/admin/events.php
- auth_api/api/admin/export-attendance.php
- auth_api/api/admin/search-members.php (NEW)
- auth_api/api/admin/manual-checkin.php (NEW)
- auth_api/api/admin/reset-device-lock.php (NEW)
- auth_api/api/events/list.php
- auth_api/api/events/details.php
- auth_api/api/events/checkin.php (enhanced with device binding and time window)
- event-management.php
- event-details.php (enhanced with manual check-in and device reset)
- config/EnvConfig.php (add getGoogleMapsApiKey method)
- config.env (add GOOGLE_MAPS_API_KEY)
- sidebar.php (add menu item)
- includes/header.php (add menu item)

**Mobile App:**

- lib/services/device_id_service.dart (NEW)
- lib/features/events/data/models/event_model.dart
- lib/features/events/data/services/event_service.dart (enhanced with device_id)
- lib/features/events/presentation/screens/events_list_screen.dart
- lib/features/events/presentation/screens/event_details_screen.dart
- lib/shared/widgets/main_layout.dart (add Events tab)
- lib/config/routes/routes.dart (add events route)
- pubspec.yaml (add packages: geolocator, google_maps_flutter, device_info_plus)
- android/app/src/main/AndroidManifest.xml (add API key and permissions)
- ios/Runner/AppDelegate.swift (add Google Maps import and API key)
- ios/Runner/Info.plist (add location permissions)

### Security Features

1. **Device Binding**: Prevents device sharing during events
2. **Time Window**: Prevents early/late check-ins (with configurable grace period)
3. **One Per User**: Prevents duplicate check-ins
4. **Location Validation**: Prevents remote check-ins
5. **Admin Override**: Allows legitimate exceptions with audit trail
6. **Device Reset**: Handles legitimate device sharing scenarios
7. **Member Validation**: Validates Coop ID exists before manual check-in

### Error Messages

- **Too Early**: "Check-in is only available during the event. Event starts at [time]"
- **Too Late**: "Check-in period has ended. The grace period expired at [time]"
- **Already Checked In**: "You have already checked in to this event"
- **Device Used**: "This device has already been used to check in another user for this event"
- **Outside Range**: "You are too far from the event location" (with distance)
- **Invalid Coop ID**: "Invalid Coop ID. Member not found in the system."

Please implement this feature following the structure and requirements above. Ensure all code follows best practices, includes proper error handling, and is well-documented.
