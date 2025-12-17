# Google Maps API Key - Quick Start Checklist

## ✅ Step-by-Step Checklist

### 1. Create Google Cloud Project

- [ ] Go to https://console.cloud.google.com/
- [ ] Click project dropdown → "New Project"
- [ ] Name: `OOUTH Coop Maps`
- [ ] Click "Create"
- [ ] Select the new project

### 2. Enable Billing

- [ ] Go to "Billing" in sidebar
- [ ] Link/create billing account
- [ ] Link to project
- [ ] **Note**: $200/month free credit available

### 3. Enable APIs

Go to "APIs & Services" → "Library" and enable:

- [ ] Maps JavaScript API
- [ ] Maps SDK for Android
- [ ] Maps SDK for iOS
- [ ] Geocoding API (optional)

### 4. Create API Key

- [ ] Go to "APIs & Services" → "Credentials"
- [ ] Click "Create Credentials" → "API Key"
- [ ] **COPY THE KEY** (you'll need it!)

### 5. Restrict API Key

**For Web (Admin Panel):**

- [ ] Click on API key to edit
- [ ] Application restrictions: "HTTP referrers"
- [ ] Add: `https://www.emmaggi.com/*`
- [ ] Add: `http://localhost:*` (for testing)
- [ ] API restrictions: Select "Maps JavaScript API"
- [ ] Click "Save"

**For Android:**

- [ ] Create new API key OR edit existing
- [ ] Application restrictions: "Android apps"
- [ ] Package name: `com.emmaggi.oouthcoop`
- [ ] Get SHA-1 (see Step 6)
- [ ] Add SHA-1 fingerprint
- [ ] API restrictions: "Maps SDK for Android"
- [ ] Click "Save"

**For iOS:**

- [ ] Create new API key OR edit existing
- [ ] Application restrictions: "iOS apps"
- [ ] Bundle ID: `com.emmaggi.oouthcoop`
- [ ] API restrictions: "Maps SDK for iOS"
- [ ] Click "Save"

### 6. Get SHA-1 Fingerprint (Android)

```bash
cd oouth_coop_app/android
./gradlew signingReport
```

- [ ] Copy SHA-1 from output
- [ ] Add to Android API key restrictions

### 7. Update Code Files

Replace `YOUR_GOOGLE_MAPS_API_KEY_HERE` with your actual key:

- [ ] `coop_admin/event-management.php` (line 162)
- [ ] `coop_admin/event-details.php` (line 250)
- [ ] `oouth_coop_app/android/app/src/main/AndroidManifest.xml` (line ~48)
- [ ] `oouth_coop_app/ios/Runner/AppDelegate.swift` (line 9)

### 8. Test

- [ ] Admin panel: Create event, verify map loads
- [ ] Mobile app: Run `flutter pub get`
- [ ] Mobile app: Test event check-in

## 🎯 Your API Key Locations

After creating your key, update these 4 files:

1. **Admin Panel - Event Management**

   - File: `coop_admin/event-management.php`
   - Line: ~162
   - Find: `AIzaSyCAsPADcUzQSE6T1jglEEBdmNjpGKWdO_Y`
   - Replace with: Your new API key

2. **Admin Panel - Event Details**

   - File: `coop_admin/event-details.php`
   - Line: ~250
   - Find: `AIzaSyCAsPADcUzQSE6T1jglEEBdmNjpGKWdO_Y`
   - Replace with: Your new API key

3. **Android App**

   - File: `oouth_coop_app/android/app/src/main/AndroidManifest.xml`
   - Line: ~48
   - Find: `YOUR_GOOGLE_MAPS_API_KEY_HERE`
   - Replace with: Your new API key

4. **iOS App**
   - File: `oouth_coop_app/ios/Runner/AppDelegate.swift`
   - Line: ~9
   - Find: `YOUR_GOOGLE_MAPS_API_KEY_HERE`
   - Replace with: Your new API key

## 📝 Notes

- You can use the same API key for all platforms if you set up multiple restrictions
- Or create separate keys for better security
- $200/month free credit = ~28,500 map loads free
- Always restrict your API keys!

## 🆘 Need Help?

See detailed guide: `GOOGLE_MAPS_SETUP.md`
