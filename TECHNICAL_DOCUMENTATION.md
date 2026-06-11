# MCCAT Ordering System - Technical Documentation

## System Architecture

### Overview
MCCAT is a comprehensive Online Transaction Processing (OLTP) and Online Analytical Processing (OLAP) system designed for managing food orders with advanced business intelligence capabilities.

```
┌─────────────────────────────────────────────────────────────────┐
│                        User Interface Layer                      │
│        (HTML/CSS/JavaScript - Frontend Components)              │
└────────────────┬────────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────────┐
│                    Application Layer (PHP)                       │
│  ├─ User Management (login, signup, profile)                   │
│  ├─ Order Management (place, view, update)                     │
│  ├─ Menu Management (browse, search)                           │
│  └─ API Endpoints (RESTful services)                           │
└────────────────┬────────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────────┐
│                    Business Logic Layer                          │
│  ├─ Validation & Security                                       │
│  ├─ Transaction Management                                      │
│  ├─ ETL Processes                                               │
│  └─ Decision-Making Engine                                      │
└────────────────┬────────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────────┐
│                   Data & Analytics Layer                         │
│  ├─ OLTP Database (orders, users, items)                        │
│  ├─ OLAP Data Warehouse (daily_sales, products, trends)        │
│  ├─ Analytics Engine (reporting, forecasting)                  │
│  └─ Business Intelligence (recommendations)                    │
└────────────────┬────────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────────┐
│              Database Layer (MySQL/MariaDB)                      │
│  ├─ Transactional Tables                                        │
│  ├─ Analytical Tables                                           │
│  └─ Audit Logs                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Database Schema

### OLTP Tables (Transactional)

#### users
- **id** (PK): User identifier
- **fullname**: User's full name
- **email** (UNIQUE): Email address
- **phone**: Phone number
- **password**: Bcrypt hashed password
- **is_admin**: Admin flag
- **created_at, updated_at**: Timestamps

#### foods
- **id** (PK): Food item identifier
- **name**: Food name
- **description**: Item description
- **price**: Unit price (decimal)
- **category**: Food category
- **is_available**: Availability status
- **created_at, updated_at**: Timestamps

#### orders
- **id** (PK): Order identifier
- **user_id** (FK): User reference
- **customer_name**: Customer name
- **email**: Customer email
- **phone**: Customer phone
- **address**: Delivery address
- **subtotal, delivery_fee, tax_amount, grand_total**: Pricing
- **status**: Order status (pending, completed, cancelled)
- **payment_method**: Payment type
- **created_at, updated_at, completed_at**: Timestamps

#### order_items
- **id** (PK): Item identifier
- **order_id** (FK): Order reference
- **food_id** (FK): Food reference
- **food_name**: Item name snapshot
- **unit_price**: Price snapshot
- **quantity**: Order quantity
- **line_total**: Item total

#### activity_log
- **id** (PK): Log identifier
- **user_id** (FK): User reference
- **action**: Action type
- **entity_type**: Entity being acted upon
- **entity_id**: Entity identifier
- **description**: Action description
- **created_at**: Timestamp

### OLAP Tables (Data Warehouse)

#### daily_sales
- **sales_date** (PK): Date
- **total_orders**: Count of orders
- **total_revenue**: Sum of revenues
- **total_items**: Total items sold
- **avg_order_value**: Average order amount
- **completed_orders**: Completed count
- **pending_orders**: Pending count

#### product_daily_sales
- **sales_date, food_id** (PK): Composite key
- **food_name**: Product name
- **category**: Food category
- **units_sold**: Quantity sold
- **revenue**: Total revenue
- **avg_price**: Average price

#### orders_by_hour
- **hour_start** (PK): Hour timestamp
- **order_count**: Orders in hour
- **revenue**: Revenue in hour
- **avg_order_value**: Average order value

#### monthly_summary
- **year_month** (PK): Year-month
- **total_orders**: Monthly orders
- **total_revenue**: Monthly revenue
- **total_customers**: Unique customers
- **avg_order_value**: Average order value
- **top_product**: Best seller
- **top_product_revenue**: Top product revenue

#### category_performance
- **category** (PK): Food category
- **total_revenue**: Category revenue
- **total_units**: Units sold
- **order_count**: Number of orders
- **avg_revenue**: Average revenue per item

#### customer_analytics
- **user_id** (PK): User identifier
- **total_orders**: Lifetime orders
- **total_spent**: Total spending
- **avg_order_value**: Average order value
- **first_order_date**: First purchase date
- **last_order_date**: Most recent purchase
- **preferred_category**: Most ordered category
- **lifetime_value**: Customer value

## Key Features

### Component 1: OLTP & Transaction Management
- ✓ Normalized database schema (3NF)
- ✓ ACID compliance with transactions
- ✓ Foreign key constraints with CASCADE rules
- ✓ Full CRUD operations
- ✓ Input validation & SQL injection prevention
- ✓ Audit logging of all activities

### Component 2: OLAP & Decision-Making Logic
- ✓ Advanced multidimensional queries
- ✓ Data aggregation with GROUP BY
- ✓ Window functions for trend analysis
- ✓ Decision-making engine with business rules
- ✓ Recommendations based on analytics
- ✓ Forecasting using moving averages

### Component 3: Analytics, Reporting & Visualization
- ✓ Interactive dashboard with Chart.js
- ✓ Real-time KPI cards
- ✓ Multiple chart types (line, bar, doughnut)
- ✓ PDF report generation
- ✓ CSV export functionality
- ✓ Excel export support
- ✓ Date range filtering

### Component 4: Project Documentation
- ✓ System architecture documentation
- ✓ Database ERD and schema descriptions
- ✓ API endpoint documentation
- ✓ Installation and setup guide
- ✓ User manual with examples
- ✓ Security best practices

### Component 5: Code Quality, Security & Demo
- ✓ Clean, modular code structure
- ✓ Input validation and sanitization
- ✓ SQL injection prevention (prepared statements)
- ✓ Password hashing with bcrypt
- ✓ CSRF token protection
- ✓ Security headers (CSP, X-Frame-Options, etc.)
- ✓ Rate limiting implementation
- ✓ Session management with timeouts

## ETL Process

The ETL (Extract-Transform-Load) pipeline runs daily to populate data warehouse tables:

1. **Extract**: Read transactional data from OLTP tables
2. **Transform**: Aggregate, calculate metrics, apply business logic
3. **Load**: Insert/update OLAP tables with processed data

### Running ETL

**Via CLI:**
```bash
php scripts/olap_etl.php
```

**Via Web:**
```
http://localhost/ITEC-65---MCCAT/scripts/olap_etl.php?run_etl=1
```

### ETL Tables Populated
- daily_sales
- product_daily_sales
- orders_by_hour
- category_performance

## API Endpoints

### Business Intelligence API
```
/scripts/business_intelligence.php?action=ACTION&params=VALUE
```

**Actions:**
- `recommendations` - Get business recommendations
- `metrics` - Get performance metrics (default: 30 days)
- `top_products` - Get top performing products
- `peak_hours` - Get peak sales hours
- `forecast` - Forecast next day revenue
- `revenue_analysis` - Detailed revenue analysis

**Examples:**
```
/scripts/business_intelligence.php?action=recommendations
/scripts/business_intelligence.php?action=metrics&days=60
/scripts/business_intelligence.php?action=top_products&limit=5
```

## Security Features

### Input Validation
- Email validation using filter_var
- Phone number validation (PH format)
- Price and quantity validation
- Filename sanitization

### Data Protection
- Prepared statements for all queries
- Parameter binding to prevent SQL injection
- Password hashing with bcrypt (cost: 12)
- CSRF token validation
- Rate limiting on sensitive operations

### Session Management
- Secure session cookies (HttpOnly, Secure, SameSite)
- Session timeout after 1 hour
- Session regeneration on login
- Activity tracking

### Security Headers
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block
- Strict-Transport-Security
- Content-Security-Policy

## Performance Optimization

### Database Indexes
- Indexes on frequently queried columns (created_at, status, category)
- Composite indexes on common filter combinations
- Primary keys and foreign key constraints

### Caching
- Session-based rate limiting
- Query result caching (in-memory)
- Materialized views (daily_sales, product_daily_sales)

## Future Enhancements

1. **Payment Integration**: Integrate with payment gateways
2. **Mobile App**: Develop native mobile applications
3. **Notification System**: Email/SMS notifications
4. **Inventory Management**: Stock tracking and alerts
5. **Customer Portal**: Self-service customer account management
6. **Advanced Analytics**: Machine learning for demand forecasting
7. **Multi-location Support**: Support for multiple restaurant locations
