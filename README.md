# Advanced Library Management System with Barcode Integration

A comprehensive Library Management System (LMS) developed in PHP and MySQL, complete with powerful barcode integration features. This system automates and simplifies all aspects of library operations, making it perfect for educational institutions and libraries.

## Features

### 🎯 Core Features
- **Professional Admin Dashboard** - Data-rich overview with statistics and interactive charts
- **Complete Book Management** - Add, edit, delete books with multiple authors support
- **Automatic Barcode Generation** - Unique barcodes for each book copy
- **Member Management** - Handle students and teachers with detailed profiles
- **Transaction System** - Barcode-based borrowing and returning with due date management
- **Fine Calculation** - Automatic penalty calculation for overdue books
- **Comprehensive Reporting** - Generate detailed reports with print-friendly format

### 📊 Dashboard Features
- Real-time statistics cards (Total Books, Members, Books on Loan, Overdue Books)
- Interactive charts showing book distribution by category
- Member type breakdown (Students vs Teachers)
- Quick access to all major functions

### 📚 Book Management
- Add books with multiple authors
- Automatic barcode generation for each copy
- Print barcode labels for physical books
- Track book status (New, Good, Damaged, Lost)
- Advanced search and filtering
- Pagination for large datasets

### 👥 Member Management
- Register students and teachers
- Track borrowing history
- Member status management (Active, Inactive, Suspended)
- Search and filter members
- View current borrowed books

### 🔄 Transaction System
- Barcode-based quick transactions
- Automatic due date calculation
- Fine calculation for overdue books
- Real-time availability updates
- Transaction history tracking

### 📈 Reporting System
- Borrowed books reports (date range)
- Returned books reports (date range)
- Overdue books reports with fine calculation
- Individual member transaction history
- Print-friendly report formats

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 8.2+
- **Database**: MySQL 5.7+
- **Barcode Generation**: Custom PHP Barcode Generator
- **Charts**: Chart.js
- **Icons**: Font Awesome 6

## Installation Guide

### Prerequisites
- XAMPP, WAMP, or similar local server environment
- MySQL database
- PHP 8.0 or higher
- Modern web browser

### Step-by-Step Installation

#### 1. Download and Setup
1. Download the project files
2. Extract the zip file
3. Copy the `Advanced Library Management System` folder to your server's web directory:
   - XAMPP: `C:\xampp\htdocs\`
   - WAMP: `C:\wamp64\www\`

#### 2. Database Setup
1. Start Apache and MySQL services from XAMPP/WAMP control panel
2. Open your browser and navigate to `http://localhost/phpmyadmin/`
3. Create a new database named `project_library`
4. Select the new database
5. Go to the **Import** tab
6. Choose the `database.sql` file from the project folder
7. Click **Go** to import the database schema and sample data

#### 3. Configure Database Connection
1. Open the file `include/dbcon.php` in a text editor
2. Update the database connection details if needed:
   ```php
   $host = 'localhost';
   $user = 'root';
   $password = '';
   $dbname = 'project_library';
   ```

#### 4. Run the Application
1. Open your web browser
2. Navigate to: `http://localhost/Advanced Library Management System/`
3. Login with default credentials:
   - **Username**: `admin`
   - **Password**: `admin123`

## Project Structure

```
Advanced Library Management System/
├── admin/                          # Admin panel files
│   ├── includes/                   # Header, sidebar, and other includes
│   ├── dashboard.php              # Main dashboard
│   ├── books.php                  # Book management
│   ├── add_book.php               # Add new book
│   ├── print_barcodes.php         # Print barcode labels
│   ├── members.php                # Member management
│   ├── add_member.php             # Add new member
│   ├── transactions.php           # Borrow/return transactions
│   ├── reports.php                # Reports and analytics
│   └── login_process.php          # Login handler
├── assets/                        # Static assets
│   ├── css/                       # Stylesheets
│   ├── js/                        # JavaScript files
│   └── images/                    # Images
├── barcodes/                      # Generated barcode images
├── include/                       # Core PHP files
│   ├── dbcon.php                 # Database connection
│   └── barcode_generator.php      # Barcode generation class
├── reports/                       # Generated reports
├── composer.json                  # PHP dependencies
├── database.sql                   # Database schema and sample data
├── index.php                      # Login page
└── README.md                      # This file
```

## Database Schema

The system uses the following main tables:

- **admin_users** - Administrator accounts
- **books** - Book information and metadata
- **book_authors** - Multiple authors per book
- **book_copies** - Individual book copies with barcodes
- **members** - Member information (students/teachers)
- **transactions** - Borrow/return transactions
- **categories** - Book categories
- **settings** - System configuration

## Default Login Credentials

- **Username**: `admin`
- **Password**: `admin123`

## Barcode System

The system automatically generates unique barcodes for each book copy in the format:
`LMS-BOOK-XXX-YYY` where:
- `XXX` is the 3-digit book ID
- `YYY` is the 3-digit copy number

Barcodes can be printed directly from the system and applied to physical books for quick scanning during transactions.

## Fine Calculation

The system automatically calculates fines for overdue books based on:
- Number of days overdue
- Fine rate per day (configurable in settings)
- Member type (different loan periods for students vs teachers)

## Browser Compatibility

This system is compatible with all modern web browsers:
- Google Chrome (recommended)
- Mozilla Firefox
- Microsoft Edge
- Safari

## Security Features

- Session-based authentication
- SQL injection protection
- XSS prevention
- Input validation and sanitization
- Secure password handling

## Customization

### Changing Fine Rates
1. Login as admin
2. Go to Settings → System Settings
3. Update the fine_per_day value

### Adding New Categories
1. Go to Categories in the admin panel
2. Click "Add New Category"
3. Enter category details

### Customizing Barcode Format
Edit the `barcode_generator.php` file to modify the barcode generation logic.

## Troubleshooting

### Common Issues

**1. Database Connection Error**
- Check MySQL service is running
- Verify database credentials in `include/dbcon.php`
- Ensure database `project_library` exists

**2. Barcode Not Displaying**
- Check GD library is enabled in PHP
- Verify write permissions for `barcodes/` folder

**3. Login Issues**
- Clear browser cookies
- Check session configuration in php.ini
- Verify admin user exists in database

### Error Logs
Check PHP error logs for detailed error messages:
- XAMPP: `C:\xampp\apache\logs\error.log`
- WAMP: `C:\wamp64\logs\apache_error.log`

## Support

For support and issues:
1. Check this README file
2. Review error logs
3. Verify all installation steps
4. Test with sample data

## License

This project is provided as-is for educational purposes. Feel free to modify and distribute according to your needs.

---

**Note**: This system is designed as a final-year project demonstration and includes all the essential features of a modern library management system with barcode integration.
