#!/bin/bash

# Function to increment version
increment_version() {
    local version=$1
    local position=$2
    
    IFS='.' read -ra VERSION_PARTS <<< "${version%+*}"
    local build_number="${version#*+}"
    
    # Increment build number
    build_number=$((build_number + 1))
    
    # Increment version based on position (1=major, 2=minor, 3=patch)
    case $position in
        1)
            VERSION_PARTS[0]=$((VERSION_PARTS[0] + 1))
            VERSION_PARTS[1]=0
            VERSION_PARTS[2]=0
            ;;
        2)
            VERSION_PARTS[1]=$((VERSION_PARTS[1] + 1))
            VERSION_PARTS[2]=0
            ;;
        3)
            VERSION_PARTS[2]=$((VERSION_PARTS[2] + 1))
            ;;
    esac
    
    echo "${VERSION_PARTS[0]}.${VERSION_PARTS[1]}.${VERSION_PARTS[2]}+${build_number}"
}

# Read current version from pubspec.yaml
CURRENT_VERSION=$(grep 'version:' pubspec.yaml | cut -d' ' -f2)

# Ask for version bump type
echo "Current version is $CURRENT_VERSION"
echo "Select version bump type:"
echo "1) Major (x.0.0)"
echo "2) Minor (0.x.0)"
echo "3) Patch (0.0.x)"
read -p "Enter choice (1-3): " BUMP_TYPE

# Get new version
NEW_VERSION=$(increment_version "$CURRENT_VERSION" "$BUMP_TYPE")

# Update pubspec.yaml
sed -i '' "s/version: .*/version: $NEW_VERSION/" pubspec.yaml

# Create version.json
cat > version.json << EOF
{
  "version": "${NEW_VERSION%+*}",
  "build_number": "${NEW_VERSION#*+}",
  "changelog": "- Bug fixes and improvements",
  "force_update": false,
  "download_url": "https://www.emmaggi.com/coop_admin/download.html"
}
EOF

# Build APK
echo "Building APK..."
flutter build apk --split-per-abi --release

# Move files to downloadribution folder
mkdir -p download
cp build/app/outputs/flutter-apk/app-arm64-v8a-release.apk download/oouth_coop_arm64-v8a_${NEW_VERSION%+*}.apk
cp build/app/outputs/flutter-apk/app-armeabi-v7a-release.apk download/oouth_coop_armeabi-v7a_${NEW_VERSION%+*}.apk
cp build/app/outputs/flutter-apk/app-x86_64-release.apk download/oouth_coop_x86_64_${NEW_VERSION%+*}.apk
cp version.json download/

echo "Done! New version is $NEW_VERSION"
echo "Files are in the download folder:"
ls -l download/