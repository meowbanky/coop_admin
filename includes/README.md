# Header and Footer System

This directory contains the reusable header and footer components for the OOUTH COOP Management System.

## Files

### `header.php`

- **Purpose**: Common header component used across all pages
- **Features**:
  - Session management and authentication checks
  - Responsive navigation with logo and branding
  - User information display
  - Back to dashboard button (when not on home page)
  - Logout functionality
  - Common CSS and JavaScript libraries
  - Utility functions for common operations

### `footer.php`

- **Purpose**: Common footer component used across all pages
- **Features**:
  - Company information and branding
  - Quick navigation links
  - Contact information
  - System status indicator
  - Common JavaScript utilities
  - Global utility functions

## Usage

### Basic Implementation

```php
<?php
ini_set('max_execution_time','300');
require_once('Connections/coop.php');
include_once('classes/model.php');

// Set page title
$pageTitle = 'OOUTH COOP - Your Page Title';

// Include header
include 'includes/header.php';
?>

<!-- Your page content here -->

<?php include 'includes/footer.php'; ?>
```

### Page Title Variable

Set the `$pageTitle` variable before including the header to customize the page title:

```php
$pageTitle = 'OOUTH COOP - Employee Management';
```

## Features

### Header Features

- **Authentication**: Automatically checks session and redirects if not logged in
- **User Info**: Displays current user name and role
- **Navigation**: Back to dashboard button (hidden on home page)
- **Logout**: Secure logout functionality
- **Responsive**: Mobile-friendly design
- **Branding**: Consistent OOUTH COOP branding

### Footer Features

- **Company Info**: OOUTH COOP branding and description
- **Quick Links**: Navigation to main sections
- **Contact Info**: Phone, email, and address
- **System Status**: Online indicator
- **Version Info**: System version display

### Global Utilities

The footer includes global JavaScript utilities accessible via `window.CoopUtils`:

- `showLoading(element)` - Show loading spinner
- `hideLoading(element)` - Hide loading spinner
- `showSuccess(message, title)` - Show success notification
- `showError(message, title)` - Show error notification
- `showConfirm(message, title, callback)` - Show confirmation dialog
- `formatCurrency(amount)` - Format currency (NGN)
- `formatDate(date)` - Format date
- `debounce(func, wait)` - Debounce function for search
- `ajax(url, options)` - AJAX helper function

## Updated Files

The following files have been updated to use the new header/footer system:

1. `home.php` - Dashboard
2. `employee.php` - Employee Management
3. `masterReportModern.php` - Master Report System
4. `procesCommodity.php` - Commodity Processing
5. `update_deduction.php` - Deduction Management
6. `enquiry.php` - Enquiry Management
7. `payperiods.php` - Pay Periods
8. `print_member.php` - Member Contributions
9. `payprocess.php` - Deductions Processing
10. `users.php` - User Management
11. `upload.php` - File Upload

## Benefits

### Maintenance

- **Single Source of Truth**: All header/footer changes in one place
- **Consistent Design**: Uniform look and feel across all pages
- **Easy Updates**: Change once, apply everywhere
- **Reduced Duplication**: No more repeated HTML/CSS/JS

### Development

- **Faster Development**: New pages just need to include header/footer
- **Consistent Structure**: Standardized page layout
- **Common Utilities**: Shared JavaScript functions
- **Responsive Design**: Mobile-friendly out of the box

### User Experience

- **Consistent Navigation**: Same header/footer on all pages
- **Professional Look**: Modern, clean design
- **Better Performance**: Optimized CSS and JavaScript
- **Accessibility**: Proper semantic HTML structure

## Customization

### Adding New CSS

Add custom styles in the `<style>` section of `header.php` or create a separate CSS file and include it.

### Adding New JavaScript

Add custom JavaScript in the `<script>` section of `footer.php` or create a separate JS file and include it.

### Modifying Navigation

Update the header navigation in `header.php` to add new menu items or modify existing ones.

### Updating Footer Links

Modify the quick links section in `footer.php` to add or update navigation links.

## Backup Files

All original files have been backed up with `.backup.YYYY-MM-DD-HH-MM-SS` extensions before modification. These can be restored if needed.

## Notes

- All files maintain their original functionality
- Authentication and session management is handled by the header
- The system is fully responsive and mobile-friendly
- All logout links have been updated to use the proper `logout.php` file
