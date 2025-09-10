# Cooperative Management System

A comprehensive PHP-based cooperative management system built for OOUTH Cooperative Society. This system provides modern UI/UX with Tailwind CSS and includes features for member management, payroll processing, loan management, and financial reporting.

## 🚀 Features

### Core Functionality
- **Member Management**: Complete CRUD operations for cooperative members
- **Payroll Processing**: Monthly deduction processing with progress tracking
- **Loan Management**: Loan application, processing, and repayment tracking
- **Financial Reporting**: Comprehensive reports and analytics
- **User Management**: Admin and user role management
- **File Upload**: Document management system
- **Settings Management**: Configurable system settings

### Modern UI/UX
- **Responsive Design**: Mobile-first approach with Tailwind CSS
- **Modern Components**: Cards, modals, progress bars, and interactive elements
- **Consistent Styling**: Unified header/footer system across all pages
- **User-Friendly Interface**: Intuitive navigation and user experience

### Technical Features
- **PHP 8.1+ Compatible**: Updated to work with modern PHP versions
- **MySQL Database**: Robust database design with proper relationships
- **AJAX Integration**: Asynchronous data loading and updates
- **Excel Export**: Data export functionality for reports
- **Session Management**: Secure user authentication and session handling
- **Error Handling**: Comprehensive error handling and logging

## 📁 Project Structure

```
coop_admin/
├── api/                    # REST API endpoints
│   ├── users.php
│   ├── employee.php
│   └── searchStaff.php
├── classes/               # Core classes and models
│   ├── controller.php
│   ├── model.php
│   ├── class.db.php
│   └── class.userservices.php
├── includes/              # Reusable components
│   ├── header.php
│   └── footer.php
├── js/                    # JavaScript files
│   └── loan-processor.js
├── views/                 # View components
│   └── loan-processor.php
├── archive/               # Archived/unused files
├── home.php              # Main dashboard
├── users.php             # User management
├── employee.php          # Employee management
├── print_member.php      # Member contributions
├── upload.php            # File upload system
├── payprocess.php        # Payroll processing
└── README.md
```

## 🛠️ Installation

### Prerequisites
- PHP 8.1 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependencies)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/coop_admin.git
   cd coop_admin
   ```

2. **Configure Database**
   - Create a MySQL database
   - Update database credentials in `classes/class.db.php`
   - Import the database schema

3. **Install Dependencies**
   ```bash
   composer install
   ```

4. **Configure Environment**
   - Copy `.env.example` to `.env`
   - Update environment variables

5. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 644 *.php
   ```

6. **Access the Application**
   - Open your browser
   - Navigate to `http://yourdomain.com/coop_admin`
   - Login with admin credentials

## 🔧 Configuration

### Database Configuration
Update the database connection in `classes/class.db.php`:

```php
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_username';
$password = 'your_password';
```

### Email Configuration
Configure SMTP settings in `classes/controller.php` for email notifications.

### File Upload Settings
Update upload directory permissions and file size limits in `upload.php`.

## 📊 Key Features

### Member Management
- Add, edit, and manage cooperative members
- Track member contributions and savings
- Generate member reports
- Export data to Excel

### Payroll Processing
- Monthly deduction processing
- Progress tracking with real-time updates
- Bulk processing capabilities
- Error handling and logging

### Loan Management
- Loan application processing
- Repayment tracking
- Interest calculations
- Loan status management

### Financial Reporting
- Master reports
- Member contribution reports
- Period-based filtering
- Excel export functionality

## 🎨 UI/UX Features

### Modern Design
- Clean, professional interface
- Responsive design for all devices
- Consistent color scheme and typography
- Interactive elements and animations

### User Experience
- Intuitive navigation
- Quick access dashboard
- Search and filter capabilities
- Real-time updates and notifications

## 🔒 Security Features

- Password hashing with bcrypt
- SQL injection prevention
- XSS protection
- Session management
- Input validation and sanitization

## 📱 Responsive Design

The system is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- Various screen sizes

## 🚀 Performance

- Optimized database queries
- Efficient file handling
- Minimal resource usage
- Fast page loading times

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

- **Development Team** - OOUTH Cooperative Society
- **UI/UX Design** - Modern responsive design implementation

## 📞 Support

For support and questions:
- Email: support@oouthcoop.com
- Documentation: [Project Wiki](https://github.com/yourusername/coop_admin/wiki)

## 🔄 Version History

### v2.0.0 (Current)
- Modern UI/UX with Tailwind CSS
- PHP 8.1+ compatibility
- Improved error handling
- Enhanced security features
- Responsive design
- API integration

### v1.0.0 (Legacy)
- Basic cooperative management
- Traditional PHP/HTML interface
- Core functionality implementation

## 🎯 Roadmap

- [ ] Mobile app integration
- [ ] Advanced reporting features
- [ ] Multi-language support
- [ ] API documentation
- [ ] Automated testing
- [ ] Performance optimization

---

**Note**: This system is specifically designed for OOUTH Cooperative Society. Please ensure proper configuration and testing before deployment in production environments.
