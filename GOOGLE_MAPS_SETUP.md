# Google Maps API Key Setup Guide

## Step 1: Create a New Google Cloud Project

1. **Go to Google Cloud Console**

   - Visit: https://console.cloud.google.com/
   - Sign in with your Google account

2. **Create a New Project**

   - Click on the project dropdown at the top (next to "Google Cloud")
   - Click "New Project"
   - Enter project details:
     - **Project name**: `OOUTH Coop Maps` (or any name you prefer)
     - **Organization**: (Leave as default if you don't have one)
     - **Location**: (Leave as default)
   - Click "Create"
   - Wait for the project to be created (takes a few seconds)

3. **Select Your New Project**
   - Click the project dropdown again
   - Select your newly created project

## Step 2: Enable Billing (Required for Maps API)

1. **Set up Billing Account**

   - In the left sidebar, click "Billing"
   - Click "Link a billing account"
   - If you don't have one, click "Create billing account"
   - Fill in your payment information
   - **Note**: Google provides $200/month free credit, so you likely won't be charged unless you exceed this

2. **Link Billing to Project**
   - Select your billing account
   - Click "Set account"

## Step 3: Enable Required APIs

1. **Open API Library**

   - In the left sidebar, click "APIs & Services" → "Library"
   - Or visit: https://console.cloud.google.com/apis/library

2. **Enable Maps JavaScript API** (For Admin Panel/Web)

   - Search for "Maps JavaScript API"
   - Click on it
   - Click "Enable" button
   - Wait for it to enable

3. **Enable Maps SDK for Android** (For Android App)

   - Search for "Maps SDK for Android"
   - Click on it
   - Click "Enable" button

4. **Enable Maps SDK for iOS** (For iOS App)

   - Search for "Maps SDK for iOS"
   - Click on it
   - Click "Enable" button

5. **Enable Geocoding API** (Optional but recommended)
   - Search for "Geocoding API"
   - Click on it
   - Click "Enable" button

## Step 4: Create API Key

1. **Go to Credentials**

   - In the left sidebar, click "APIs & Services" → "Credentials"
   - Or visit: https://console.cloud.google.com/apis/credentials

2. **Create API Key**

   - Click "Create Credentials" → "API Key"
   - Your API key will be generated immediately
   - **Copy this key** - you'll need it in the next steps
   - It will look like: `AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX`

3. **Restrict the API Key** (IMPORTANT for security)

   **For Web/Admin Panel:**

   - Click on your API key to edit it
   - Under "Application restrictions", select "HTTP referrers (web sites)"
   - Click "Add an item"
   - Add your domain(s):
     - `https://www.emmaggi.com/*`
     - `https://emmaggi.com/*`
     - `http://localhost:*` (for local testing)
   - Under "API restrictions", select "Restrict key"
   - Check only:
     - Maps JavaScript API
     - Geocoding API (if enabled)
   - Click "Save"

   **For Android App:**

   - Create a separate API key OR use the same one with multiple restrictions
   - Under "Application restrictions", select "Android apps"
   - Click "Add an item"
   - Enter:
     - **Package name**: `com.emmaggi.oouthcoop`
     - **SHA-1 certificate fingerprint**: (Get this in Step 5)
   - Under "API restrictions", select "Restrict key"
   - Check only:
     - Maps SDK for Android
     - Geocoding API (if enabled)
   - Click "Save"

   **For iOS App:**

   - Create a separate API key OR use the same one with multiple restrictions
   - Under "Application restrictions", select "iOS apps"
   - Click "Add an item"
   - Enter:
     - **Bundle identifier**: `com.emmaggi.oouthcoop` (check your iOS project)
   - Under "API restrictions", select "Restrict key"
   - Check only:
     - Maps SDK for iOS
     - Geocoding API (if enabled)
   - Click "Save"

## Step 5: Get SHA-1 Fingerprint (For Android)

1. **Open Terminal/Command Prompt**

   - Navigate to your Android project directory

2. **Get Debug SHA-1** (For testing)

   ```bash
   cd oouth_coop_app/android
   ./gradlew signingReport
   ```

   - Look for `SHA1:` in the output under `Variant: debug`
   - Copy the SHA-1 value (looks like: `AA:BB:CC:DD:...`)

3. **Get Release SHA-1** (For production)

   - If you have a keystore file, use:

   ```bash
   keytool -list -v -keystore /path/to/your/keystore.jks -alias your-key-alias
   ```

   - Enter your keystore password
   - Copy the SHA-1 value

4. **Add SHA-1 to API Key Restrictions**
   - Go back to Google Cloud Console → Credentials
   - Edit your Android API key
   - Add both debug and release SHA-1 fingerprints

## Step 6: Update Your Code Files

Replace `YOUR_GOOGLE_MAPS_API_KEY_HERE` with your actual API key in these files:

### Admin Panel Files:

1. **coop_admin/event-management.php** (Line 162)

   ```javascript
   <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY_HERE&libraries=places"></script>
   ```

2. **coop_admin/event-details.php** (Line 250)
   ```javascript
   <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY_HERE"></script>
   ```

### Mobile App Files:

3. **oouth_coop_app/android/app/src/main/AndroidManifest.xml** (Line ~48)

   ```xml
   <meta-data
       android:name="com.google.android.geo.API_KEY"
       android:value="YOUR_API_KEY_HERE" />
   ```

4. **oouth_coop_app/ios/Runner/AppDelegate.swift** (Line 9)
   ```swift
   GMSServices.provideAPIKey("YOUR_API_KEY_HERE")
   ```

## Step 7: Test Your Setup

### Test Admin Panel:

1. Open `event-management.php` in your browser
2. Click "Create Event"
3. Click "Select on Map" or "Use My Location"
4. Map should load without errors

### Test Mobile App:

1. Run `flutter pub get` in your app directory
2. Build and run the app
3. Navigate to Events tab
4. Open an event and check if map loads
5. Try checking in to an event

## Troubleshooting

### Common Errors:

1. **"This API key is not authorized"**

   - Check if you've enabled the correct APIs
   - Verify API key restrictions match your domain/app

2. **"RefererNotAllowedMapError"**

   - Add your domain to HTTP referrer restrictions
   - Include both `www` and non-`www` versions

3. **"API key not valid"**

   - Double-check you copied the entire key
   - Ensure no extra spaces or characters

4. **Maps not loading on mobile**
   - Verify SHA-1 fingerprint is correct
   - Check bundle ID matches exactly
   - Ensure Maps SDK APIs are enabled

## Cost Information

- **Free Tier**: $200/month credit
- **Maps JavaScript API**: $7 per 1,000 loads
- **Maps SDK (Android/iOS)**: $7 per 1,000 loads
- **Geocoding API**: $5 per 1,000 requests

With $200 credit, you get approximately:

- ~28,500 map loads per month FREE
- ~40,000 geocoding requests per month FREE

## Security Best Practices

1. ✅ Always restrict your API keys
2. ✅ Use separate keys for web, Android, and iOS
3. ✅ Monitor usage in Google Cloud Console
4. ✅ Set up billing alerts
5. ✅ Rotate keys periodically
6. ✅ Never commit API keys to public repositories

## Quick Links

- Google Cloud Console: https://console.cloud.google.com/
- API Library: https://console.cloud.google.com/apis/library
- Credentials: https://console.cloud.google.com/apis/credentials
- Billing: https://console.cloud.google.com/billing
- Maps Platform Pricing: https://mapsplatform.google.com/pricing/
