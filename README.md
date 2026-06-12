# MCCAT

## Overview

MCCAT is a web-based food ordering and sales monitoring system developed to streamline food ordering operations and improve customer experience. The system allows users to browse menu items, place orders, and interact with the platform through a user-friendly interface.

The upgraded version integrates both Online Transaction Processing (OLTP) and Online Analytical Processing (OLAP) functionalities, enabling efficient transaction management and sales analytics.

---

## Features

### Customer Module
- User Registration
- User Login
- Browse Food Menu
- Place Orders
- Contact and Inquiry Submission

### Administrative Module
- Dashboard Access
- Transaction Monitoring
- Sales Analytics
- ETL Processing
- OLTP and OLAP Reporting

### API Module
- Food Data Retrieval
- Order Processing

---

## Technologies Used

| Technology | Purpose |
|------------|----------|
| PHP | Server-side processing |
| HTML | Website structure |
| CSS | User interface styling |
| JavaScript | Client-side functionality |
| MySQL | Database management |
| GitHub | Version control and collaboration |

---

## Project Structure

```text
ITEC-65---MCCAT
│
├── admin/
├── api/
├── backups/
├── components/
├── css/
├── data/
├── images/
├── js/
├── scripts/
│
├── about.html
├── contact.html
├── index.php
├── login.php
├── logout.php
├── menu.php
├── order.php
├── signup.php
│
├── ordering_system.sql
├── ordering_system_complete.sql
└── README.md
```

---

## System Architecture

```text
User
↓
Web Browser
↓
PHP Application Layer
(index.php, login.php, menu.php, order.php)
↓
API Layer
(foods.php, place-order.php)
↓
MySQL Database
(ordering_system)
↓
OLAP Data Warehouse
(ordering_dw)
```

---

## Database Components

### OLTP Tables
- users
- foods
- orders
- order_items
- audit_logs

### OLAP Tables
- daily_sales
- product_daily_sales
- orders_by_hour
- category_performance

---

## Installation Guide

1. Install XAMPP.
2. Start Apache and MySQL.
3. Import `ordering_system_complete.sql` into MySQL.
4. Place the MCCAT project folder inside the `htdocs` directory.
5. Open a web browser and access the project through localhost.
6. Access administrative features through the admin module.

---

## Documentation

The complete system documentation is included in the project documentation files and contains:

- Executive Summary
- System Overview
- System Architecture and Design
- Database Schema
- Development Process
- Installation Guide
- User Guide
- Testing and Quality Assurance

---

## Development Team

- Miguel Lumactod
- Charlie Oras
- Gervin Signo
- Joshua Limpioso

---

## Course Information

Open-Source Technologies

Academic Year 2025–2026
