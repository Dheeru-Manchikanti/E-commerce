# E-commerce System

A PHP-based e-commerce system with admin panel and storefront.

## Features

- Product catalog with categories
- Shopping cart functionality
- Checkout process
- User registration and login system
- User account management (orders, addresses, profile)
- Admin dashboard for managing products, orders, and categories
- Order management system

## Setup Instructions

1. Clone or download this repository
2. Place it in your XAMPP htdocs folder
3. Import the database.sql file into your MySQL server
4. Import the sql/user_tables.sql file to add user authentication functionality
5. Configure database settings in includes/config.php
6. Access the site at http://localhost/Ecommerce/
7. Access the admin panel at http://localhost/Ecommerce/admin/
   - Username: admin
   - Password: admin123

## User Authentication

The system includes a complete user authentication system with:
- User registration and login
- Profile management via the simple_profile.php page
- Order history tracking 
- Address management for shipping and billing
- Integration with checkout process

## Requirements

- PHP 7.4+
- MySQL 5.7+
- XAMPP or similar PHP development environment
