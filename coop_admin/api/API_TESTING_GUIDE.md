# API Testing Guide

This guide explains how to test the OOUTH COOP API endpoints.

## API Endpoints

### 1. Check User (`check_user`)
Verifies if a member exists by phone number.

### 2. Get Balances (`get_balances`)
Retrieves member account balances.

---

## Testing Methods

### Method 1: Using cURL (Command Line)

#### Test Check User Endpoint:
```bash
curl -X GET "https://www.emmaggi.com/coop_admin/api/api.php?action=check_user&phone=2348012345678&apikey=YOUR_API_SECRET"
```

#### Test Get Balances Endpoint:
```bash
curl -X GET "https://www.emmaggi.com/coop_admin/api/api.php?action=get_balances&member_id=COOP001&apikey=YOUR_API_SECRET"
```

#### With Authorization Header:
```bash
curl -X GET \
  "https://www.emmaggi.com/coop_admin/api/api.php?action=check_user&phone=2348012345678" \
  -H "Authorization: Bearer YOUR_API_SECRET"
```

---

### Method 2: Using PHP Test Script

A test script is provided at `api/test_api.php`:

```bash
# Test check_user
php api/test_api.php check_user 2348012345678

# Test get_balances
php api/test_api.php get_balances COOP001
```

**Note:** Update `$API_BASE_URL` in `test_api.php` to match your server URL.

---

### Method 3: Using Browser (Limited)

You can test in browser, but authentication must be via `apikey` parameter:

```
https://www.emmaggi.com/coop_admin/api/api.php?action=check_user&phone=2348012345678&apikey=YOUR_API_SECRET
```

**Warning:** This exposes your API key in the URL. Use only for testing, not production.

---

### Method 4: Using Postman/Insomnia

#### Setup:
1. **Method:** GET
2. **URL:** `https://www.emmaggi.com/coop_admin/api/api.php`

#### For Check User:
- **Query Parameters:**
  - `action`: `check_user`
  - `phone`: `2348012345678`
  - `apikey`: `YOUR_API_SECRET`

#### For Get Balances:
- **Query Parameters:**
  - `action`: `get_balances`
  - `member_id`: `COOP001`
  - `apikey`: `YOUR_API_SECRET`

#### Alternative (Authorization Header):
- **Headers:**
  - `Authorization`: `Bearer YOUR_API_SECRET`
- **Query Parameters:**
  - `action`: `check_user`
  - `phone`: `2348012345678`

---

## Expected Responses

### Check User - Success:
```json
{
    "status": "success",
    "member_id": "COOP001",
    "name": "John Doe",
    "first_name": "John",
    "last_name": "Doe",
    "phone_matched": "08012345678",
    "email": "john.doe@example.com",
    "status": "Active"
}
```

### Check User - Not Found:
```json
{
    "status": "error",
    "message": "Member not found"
}
```

### Get Balances - Success:
```json
{
    "status": "success",
    "member_id": "COOP001",
    "member_name": "John Doe",
    "member_status": "Active",
    "data": {
        "savings_balance": "5000.00",
        "shares_balance": "10000.00",
        "loan_balance": "2500.00",
        "interest_paid": "500.00",
        "commodity_balance": "0.00",
        "dev_levy_total": "100.00",
        "entry_fee_total": "50.00",
        "stationery_total": "25.00",
        "currency": "NGN"
    },
    "raw_totals": {
        "total_savings": 5000,
        "total_shares": 10000,
        "total_loan_taken": 5000,
        "total_loan_repaid": 2500,
        "total_interest_paid": 500,
        "total_commodity": 0,
        "total_commodity_repaid": 0
    }
}
```

### Error Responses:

#### Unauthorized:
```json
{
    "status": "error",
    "message": "Unauthorized"
}
```

#### Invalid Action:
```json
{
    "status": "error",
    "message": "Invalid action"
}
```

#### Missing Parameter:
```json
{
    "status": "error",
    "message": "Phone number is required"
}
```

---

## Testing Checklist

- [ ] Test with valid API secret
- [ ] Test with invalid API secret (should return 401)
- [ ] Test check_user with valid phone number
- [ ] Test check_user with invalid phone number
- [ ] Test check_user with phone number in different formats (080, 23480, etc.)
- [ ] Test get_balances with valid CoopID
- [ ] Test get_balances with invalid CoopID
- [ ] Test with missing action parameter
- [ ] Test with missing required parameters
- [ ] Test with Authorization header instead of apikey parameter

---

## Security Notes

1. **Never commit API secrets** to version control
2. **Use HTTPS** in production
3. **Prefer Authorization header** over query parameter for API key
4. **Rotate API secrets** regularly
5. **Monitor API usage** for suspicious activity

---

## Troubleshooting

### Issue: "Unauthorized" error
- **Solution:** Check that API_SECRET in `.env` matches the one used in request

### Issue: "Database Connection Failed"
- **Solution:** Verify database credentials in `.env` file

### Issue: "Member not found"
- **Solution:** Verify the phone number or CoopID exists in `tblemployees` table

### Issue: "API_SECRET not configured"
- **Solution:** Ensure `.env` file exists and contains `API_SECRET=...`

---

## Example Test Data

To test the API, you'll need:
1. A valid CoopID from `tblemployees` table
2. A valid phone number from `tblemployees.MobileNumber`
3. The current API_SECRET from `.env` file

You can get test data by querying the database:
```sql
SELECT CoopID, FirstName, LastName, MobileNumber 
FROM tblemployees 
LIMIT 5;
```



