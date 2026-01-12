# 🚀 OOUTH Salary API Integration

## Overview

This integration allows you to fetch deduction/allowance data from the OOUTH Salary API and upload it directly to your database **without needing Excel files**.

## 🎯 Features

- ✅ **Period Selection** - Dropdown list of all payroll periods from API
- ✅ **Real-time Data Fetch** - Fetch staff data directly from OOUTH Salary API
- ✅ **Data Preview** - View staff ID, name, and amounts in a searchable table
- ✅ **Pagination** - Handle large datasets with 50 records per page
- ✅ **Search & Filter** - Find specific staff quickly
- ✅ **CSV Export** - Download data for offline use
- ✅ **JSON Upload** - Upload data to database via JSON (no Excel needed)
- ✅ **Progress Tracking** - Visual feedback during upload
- ✅ **Error Handling** - Comprehensive error messages and rollback on failure

## 📋 Setup Instructions

### Step 1: Get API Credentials

Contact OOUTH Salary System Administrator:

- **Email:** api-support@oouth.com
- **Provide:** Organization name, contact email, phone, resource needed

You will receive:

- API Key (e.g., `oouth_005_deduc_48_a1b2c3dxxxxxxx`)
- API Secret (64-character string) **⚠️ Keep this secret!**
- Organization ID (e.g., `005`)
- Resource Access (deduction or allowance ID)

### Step 2: Configure API Settings

1. Navigate to `config/` directory
2. Copy `api_config.php.example` to `api_config.php`:
   ```bash
   cp config/api_config.php.example config/api_config.php
   ```
3. Edit `config/api_config.php` and update:
   ```php
   define('OOUTH_API_KEY', 'oouth_005_deduc_48_ed7dee3cxxxxxxx');
   define('OOUTH_API_SECRET', '4e85095ce0bfdf69ce4aa231d809d5915xxxxxxxxxxxx');
   define('OOUTH_ORGANIZATION_ID', '005'); // e.g., 005
   define('OOUTH_RESOURCE_TYPE', 'deduction'); // or 'allowance'
   define('OOUTH_RESOURCE_ID', '48'); // e.g., 48 for Pension
   define('OOUTH_RESOURCE_NAME', 'PENSION');
   ```

### Step 3: Access the API Upload Page

1. Log in to your admin dashboard
2. Click on **"API Upload"** card (purple icon with cloud download)
3. Or navigate directly to: `https://emmaggi.com/coop_admin/api_upload.php`

## 🎨 How to Use

### Workflow:

1. **Select Period**

   - Choose a payroll period from the dropdown
   - Periods are automatically loaded from the API

2. **Fetch Data**

   - Click "Fetch Data from API" button
   - Wait for data to load (shows progress indicator)
   - Data appears in the table below

3. **Review Data**

   - Use search box to filter staff
   - Check staff ID, names, and amounts
   - Use pagination to browse through records
   - Export to CSV if needed

4. **Upload to Database**

   - Click "Upload to Database" button
   - Confirm the upload action
   - Watch progress bar
   - See success/error message

5. **Clear** (Optional)
   - Click "Clear Data" to reset and start over

## 🗂️ File Structure

```
coop_admin/
├── api_upload.php                      # Main API upload interface
├── classes/
│   └── OOUTHSalaryAPIClient.php       # API client wrapper
├── api/
│   ├── fetch_api_data.php             # Endpoint for fetching API data
│   └── upload_json_data.php           # Endpoint for uploading JSON data
├── config/
│   ├── api_config.php                 # Your actual config (gitignored)
│   └── api_config.php.example         # Config template
└── API_INTEGRATION_README.md          # This file
```

## 🔐 Security Notes

- ✅ `config/api_config.php` is automatically excluded from git
- ✅ Never commit your API secret to version control
- ✅ JWT tokens auto-refresh every 15 minutes
- ✅ Database transactions rollback on errors
- ✅ Admin-only access control
- ⚠️ Set `OOUTH_API_DEBUG` to `false` in production

## 🛠️ API Client Methods

The `OOUTHSalaryAPIClient` class provides:

```php
$client = new OOUTHSalaryAPIClient();

// Authenticate and get JWT token
$client->authenticate();

// Get all payroll periods
$client->getPeriods($page, $limit);

// Get active period
$client->getActivePeriod();

// Get deduction data
$client->getDeductions($deductionId, $periodId);

// Get allowance data
$client->getAllowances($allowanceId, $periodId);

// Get configured resource data
$client->getResourceData($periodId);
```

## 📊 Database Schema

The JSON upload endpoint updates these tables (adjust based on your schema):

- **Deductions:** `tbldeductions` table
  - Fields: `CoopID`, `Period`, `DeductionID`, `DeductionAmount`
- **Allowances:** `tblallowances` table
  - Fields: `CoopID`, `Period`, `AllowanceID`, `AllowanceAmount`

Upload log table (optional, will be created if doesn't exist):

```sql
CREATE TABLE tblapi_upload_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Period INT,
    ResourceType VARCHAR(20),
    ResourceID VARCHAR(50),
    ResourceName VARCHAR(100),
    RecordsProcessed INT,
    RecordsSuccess INT,
    RecordsError INT,
    UploadedBy VARCHAR(50),
    UploadedAt DATETIME,
    Source VARCHAR(20)
);
```

## 🐛 Troubleshooting

### Problem: "Authentication failed"

**Solution:** Check your API key and secret in `config/api_config.php`

### Problem: "Failed to fetch periods"

**Solution:**

- Check internet connection
- Verify API base URL is correct
- Contact OOUTH admin to verify API key status

### Problem: "Upload failed: Too many errors"

**Solution:**

- Check database table names match your schema
- Verify period ID exists in database
- Check resource ID matches your configuration

### Problem: "Unauthorized" error

**Solution:** Make sure you're logged in as Admin

## 📞 Support

For API-related issues:

- **Email:** api-support@oouth.edu.ng
- **Documentation:** Full API guide included in the integration package

For system issues:

- Contact your system administrator

## 🎯 Advantages over Excel Upload

| Feature       | Excel Upload          | API Upload             |
| ------------- | --------------------- | ---------------------- |
| Data Source   | Manual file           | Automated API          |
| Setup Time    | Upload file each time | One-time configuration |
| Error Prone   | Manual data entry     | Automated, validated   |
| Real-time     | No                    | Yes                    |
| Efficiency    | Medium                | High                   |
| Data Accuracy | Depends on file       | Direct from source     |

## 📝 Version History

- **v1.0.0** (2024-10-08) - Initial release
  - Period selection
  - Data fetching from API
  - JSON-based upload
  - Search and export features

---

**Built with ❤️ for OOUTH COOP Admin System**
