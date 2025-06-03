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
3. Import the database.sql/ecommerce.sql file into your MySQL server
4. Configure database settings in includes/config.php
5. Access the site at http://localhost/Ecommerce/
6. Access the admin panel at http://localhost/Ecommerce/admin/
   - Username: admin
   - Password: admin123

## User Authentication

The system includes a complete user authentication system with:

- User registration and login
- Profile management via the profile.php page
- Order history tracking
- Address management for shipping and billing
- Integration with checkout process

## Requirements

- PHP 7.4+
- MySQL 5.7+
- XAMPP or similar PHP development environment
- Writable `uploads` directory (chmod 777 recommended for development)

## Product Image Management

### Bulk Image Upload (Yet to be Implemented)

The system includes a bulk image upload feature that allows you to:

1. Upload multiple images at once
2. Apply images to all products, products in a specific category, or specific products by ID
3. Set uploaded images as the main product image or add them as additional images
4. Distribute images evenly across selected products

To use the bulk image upload feature:(currently unavailable)

1. Log in to the admin panel
2. Navigate to "Products"
3. Click the "Bulk Image Upload" button
4. Choose your upload options:
   - Upload Mode: Apply to all products, products in a specific category, or specific products by ID
   - Image Options: Set as main image or add as additional image
5. Select multiple images to upload
6. Click "Upload Images"
