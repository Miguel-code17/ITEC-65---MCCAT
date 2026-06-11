# MCCAT Ordering System - Installation & Setup Guide

## Prerequisites

### System Requirements
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Required PHP Extensions
- mysqli (for MySQL)
- json (for API responses)
- session (for user sessions)
- filter (for input validation)

## Installation Steps

### 1. Download & Extract

```bash
# Clone or download the project
git clone <repository-url> /path/to/xampp/htdocs/ITEC-65---MCCAT

# Navigate to project directory
cd /path/to/xampp/htdocs/ITEC-65---MCCAT
```

### 2. Database Setup

#### Option A: Using phpMyAdmin

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" to create a new database
3. Name: `ordering_system`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"
6. Select the database and click "Import"
7. Upload `ordering_system_complete.sql`
8. Click "Go" to execute the SQL script

#### Option B: Using Command Line

```bash
# Connect to MySQL
mysql -u root -p

# Create database
CREATE DATABASE ordering_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ordering_system;

# Import schema
SOURCE /path/to/ordering_system_complete.sql;
```

### 3. Configuration

#### Update Database Connection
Edit `connection.php`:

```php
<?php
$host = 'localhost';
$user = 'root';
$password = ''; // Add your password if set
$db = "ordering_system";

$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_error($conn));
}
?>
```

#### Set File Permissions
```bash
# Make scripts directory writable
chmod 755 scripts/
chmod 755 admin/
chmod 755 api/

# Create logs directory
mkdir -p logs/
chmod 755 logs/
```

### 4. Populate Initial Data

#### Add Sample Food Items

```sql
INSERT INTO foods (name, description, price, category, is_available) VALUES
('Fried Chicken', 'Crispy fried chicken with savory seasoning', 195.00, 'Main Course', 1),
('Spaghetti Carbonara', 'Classic Italian pasta with bacon and cream sauce', 245.00, 'Main Course', 1),
('Lumpia Shanghai', 'Golden-fried spring rolls with meat filling', 125.00, 'Appetizers', 1),
('Iced Tea', 'Refreshing iced tea', 45.00, 'Beverages', 1),
('Leche Flan', 'Traditional Filipino dessert', 95.00, 'Desserts', 1);
```

#### Create Admin User

```sql
INSERT INTO users (fullname, email, phone, password, is_admin) VALUES
('Admin User', 'admin@mccat.local', '09123456789', '$2y$12$...', 1);
```

Generate bcrypt hash:
```php
<?php
echo password_hash('admin@123', PASSWORD_BCRYPT, ['cost' => 12]);
?>
```

### 5. Run ETL Process

Initialize the data warehouse:

```bash
# Via CLI
php scripts/olap_etl.php

# Via Web Browser
http://localhost/ITEC-65---MCCAT/scripts/olap_etl.php?run_etl=1
```

### 6. Start Using the System

1. Open browser: `http://localhost/ITEC-65---MCCAT/index.php`
2. For customer functions: Browse menu → Add to cart → Checkout
3. For admin functions: `http://localhost/ITEC-65---MCCAT/admin/olap_dashboard.php`

## Application Structure

```
ITEC-65---MCCAT/
├── admin/                          # Admin panel
│   ├── olap_dashboard.php          # Interactive analytics dashboard
│   ├── olap_export.php             # Report export functionality
│   ├── oltp.php                    # OLTP order management
│   └── oltp_order.php              # Order details
├── api/                            # REST API endpoints
│   └── place-order.php             # Order placement API
├── components/                     # Reusable HTML components
│   ├── navbar.html / navbar.php    # Navigation bar
│   └── footer.html                 # Footer
├── css/                            # Stylesheets
│   ├── style.css                   # Main styles
│   ├── navbar.css                  # Navigation styles
│   ├── forms.css                   # Form styles
│   └── animations.css              # Animations
├── data/                           # Static data files
│   └── foods.json                  # Menu items
├── js/                             # JavaScript files
│   ├── menu.js                     # Menu functionality
│   ├── order.js                    # Order management
│   ├── login.js                    # Login validation
│   ├── signup.js                   # Signup validation
│   └── validation.js               # Form validation
├── scripts/                        # Server-side scripts
│   ├── olap_etl.php                # ETL pipeline
│   ├── business_intelligence.php   # BI & recommendations
│   └── sample_data.php             # Sample data generator
├── security/                       # Security utilities
│   └── utils.php                   # Security functions
├── backups/                        # Database backups
│   └── *.sql.bak                   # Backup files
├── index.php                       # Homepage
├── menu.php                        # Menu page
├── order.php                       # Order placement page
├── login.php                       # Login page
├── signup.php                      # Registration page
├── logout.php                      # Logout handler
├── contact.html                    # Contact page
├── connection.php                  # Database connection
├── ordering_system.sql             # Original schema
├── ordering_system_complete.sql    # Enhanced schema
└── README.md                       # Project overview
```

## User Roles & Access

### Customer
- Browse menu
- Create account
- Place orders
- View order history
- Update profile

### Admin
- View analytics dashboard
- Export reports
- View all orders
- Manage menu items
- View customer data
- Generate recommendations

## Database Backup & Recovery

### Creating Backup

```bash
# Full database backup
mysqldump -u root -p ordering_system > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup specific table
mysqldump -u root -p ordering_system orders > orders_backup.sql
```

### Restoring from Backup

```bash
# Restore entire database
mysql -u root -p ordering_system < backup_file.sql

# Restore specific table
mysql -u root -p ordering_system < orders_backup.sql
```

## Troubleshooting

### Database Connection Error
**Problem**: "Database connection failed"

**Solution**:
1. Check MySQL is running: `sudo service mysql status`
2. Verify credentials in `connection.php`
3. Ensure database exists: `SHOW DATABASES;`

### ETL Script Fails
**Problem**: "ETL process failed"

**Solution**:
1. Check database tables exist
2. Verify permissions: `SELECT * FROM daily_sales;`
3. Review ETL log: `cat scripts/etl.log`
4. Run manually: `php scripts/olap_etl.php`

### Charts Not Displaying
**Problem**: Empty dashboard charts

**Solution**:
1. Run ETL: `php scripts/olap_etl.php`
2. Create sample orders to generate data
3. Clear browser cache
4. Check Console for JS errors (F12)

### Session Timeout Issues
**Problem**: User logged out unexpectedly

**Solution**:
1. Check session timeout setting: 3600 seconds (1 hour)
2. Verify browser accepts cookies
3. Check HTTPS redirect settings

## Performance Tuning

### Database Optimization

```sql
-- Analyze tables for query optimization
ANALYZE TABLE orders;
ANALYZE TABLE order_items;
ANALYZE TABLE daily_sales;

-- Check table sizes
SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB'
FROM information_schema.tables
WHERE table_schema = 'ordering_system';
```

### PHP Performance

Edit `php.ini`:
```
memory_limit = 256M
upload_max_filesize = 50M
max_execution_time = 300
```

## Security Checklist

- [ ] Change default admin password
- [ ] Update database credentials
- [ ] Enable HTTPS/SSL
- [ ] Set proper file permissions
- [ ] Disable debug mode in production
- [ ] Enable CORS headers if needed
- [ ] Backup database regularly
- [ ] Monitor activity logs
- [ ] Keep PHP and MySQL updated

## Support & Troubleshooting

For issues or questions:
1. Check TECHNICAL_DOCUMENTATION.md
2. Review database schema
3. Check application logs: `scripts/etl.log`
4. Enable debug mode for detailed errors

## Next Steps

1. Run sample data generator: `php scripts/sample_data.php`
2. Access dashboard: `http://localhost/ITEC-65---MCCAT/admin/olap_dashboard.php`
3. Review recommendations: `/scripts/business_intelligence.php?action=recommendations`
4. Export reports: Click export buttons on dashboard
