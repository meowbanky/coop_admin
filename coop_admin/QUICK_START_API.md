# ⚡ Quick Start - API Upload Feature

## 🚀 Get Started in 3 Steps

### Step 1: Configure (5 minutes)

```bash
# 1. Copy the example config
cp config/api_config.php.example config/api_config.php

# 2. Edit with your credentials
nano config/api_config.php
```

Update these lines:
```php
define('OOUTH_API_KEY', 'your_api_key_here');
define('OOUTH_API_SECRET', 'your_secret_here');
define('OOUTH_RESOURCE_ID', '48'); // Your resource ID
```

### Step 2: Access (1 minute)

1. Log in to admin dashboard
2. Click **"API Upload"** card (purple icon)
3. You'll see the API upload interface

### Step 3: Use (2 minutes)

1. **Select Period** from dropdown
2. Click **"Fetch Data from API"**
3. Review the data in table
4. Click **"Upload to Database"**
5. ✅ Done!

## 🎯 What You Get

- ✅ No more manual Excel uploads
- ✅ Real-time data from OOUTH API
- ✅ Automatic validation
- ✅ One-click upload to database
- ✅ Search, filter, export features

## 🔑 Need API Credentials?

Contact: **api-support@oouth.edu.ng**

Provide:
- Organization name
- Contact email
- Which deduction/allowance you need

You'll receive your API key within 24 hours.

## 📱 Interface Preview

```
┌─────────────────────────────────────┐
│  🎯 API Data Upload                 │
│  Fetch and import from OOUTH API    │
├─────────────────────────────────────┤
│  📅 Period: [October 2024 ▼]        │
│  ⬇️  [Fetch Data from API]          │
│  ⬆️  [Upload to Database]           │
│  🗑️  [Clear Data]                   │
├─────────────────────────────────────┤
│  📊 Staff Data                       │
│  ┌─────┬──────────┬────────┬────────┐│
│  │ #   │ Staff ID │ Name   │ Amount ││
│  ├─────┼──────────┼────────┼────────┤│
│  │ 1   │ 900      │ Sala...│ ₦5,000 ││
│  │ 2   │ 1200     │ Ogun...│ ₦4,500 ││
│  └─────┴──────────┴────────┴────────┘│
│  [🔍 Search] [📤 Export CSV]         │
└─────────────────────────────────────┘
```

## 🆘 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| Can't see "API Upload" card | Make sure you're logged in as Admin |
| "Authentication failed" | Check your API key/secret in config |
| "No periods found" | Verify API connection, contact support |
| Upload fails | Check database connection and table names |

## 📖 Full Documentation

See `API_INTEGRATION_README.md` for complete documentation.

## 💡 Pro Tips

1. **Test First**: Use a closed period for testing
2. **Export Data**: Always export to CSV before uploading
3. **Double Check**: Review the data table before uploading
4. **Keep Records**: Export and save CSV files for audit trail

---

**Ready to go! 🎉**

Questions? Check the full README or contact support.

