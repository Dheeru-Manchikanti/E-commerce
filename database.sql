-- Database: `ecommerce`
CREATE DATABASE IF NOT EXISTS `ecommerce` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ecommerce`;

-- Table structure for table `admin_users`
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `categories`
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `products`
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(50) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `image_main` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `product_images`
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `product_categories`
CREATE TABLE `product_categories` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `customers`
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `orders`
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `billing_address` text NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for table `order_items`
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample admin user (password: admin123)
INSERT INTO `admin_users` (`username`, `password`, `email`) VALUES
('admin', '$2y$10$1q8UzCXqZVK/i0OgyIMQpuZ4KGQr5gTY.DIS9ZXNnvuIPCbU3J7Qm', 'admin@example.com');

-- Insert sample categories
INSERT INTO `categories` (`name`, `description`, `parent_id`, `status`) VALUES
('Electronics', 'Electronic devices and gadgets', NULL, 'active'),
('Clothing', 'Fashion items and apparel', NULL, 'active'),
('Home & Kitchen', 'Home essentials and kitchen appliances', NULL, 'active'),
('Books', 'Books of various genres', NULL, 'active'),
('Sports & Outdoors', 'Sports equipment and outdoor gear', NULL, 'active'),
('Smartphones', 'Mobile phones and accessories', 1, 'active'),
('Laptops', 'Portable computers', 1, 'active'),
('Men''s Clothing', 'Clothing items for men', 2, 'active'),
('Women''s Clothing', 'Clothing items for women', 2, 'active');

-- Insert sample products
INSERT INTO `products` (`name`, `description`, `price`, `sale_price`, `stock_quantity`, `sku`, `featured`, `status`, `image_main`) VALUES
('iPhone 13 Pro', 'Latest Apple smartphone with advanced features', 999.99, 949.99, 50, 'IP13PRO-001', 1, 'active', 'iphone13pro.jpg'),
('Samsung Galaxy S21', 'Flagship Android smartphone with excellent camera', 899.99, 849.99, 45, 'SGS21-002', 1, 'active', 'galaxys21.jpg'),
('Dell XPS 13', 'Ultrabook with 4K display and Intel Core i7', 1299.99, 1199.99, 30, 'DXPS13-003', 1, 'active', 'dellxps13.jpg'),
('MacBook Air M1', 'Thin and light laptop with Apple Silicon', 999.99, 949.99, 40, 'MBA-M1-004', 1, 'active', 'macbookair.jpg'),
('Men''s Casual Shirt', 'Comfortable cotton shirt for daily wear', 29.99, 24.99, 100, 'MCS-005', 0, 'active', 'mencasualshirt.jpg'),
('Women''s Summer Dress', 'Light and colorful summer dress', 39.99, 34.99, 80, 'WSD-006', 0, 'active', 'womendress.jpg'),
('Smart LED TV 55"', '4K Ultra HD Smart LED Television', 599.99, 549.99, 25, 'TV55-007', 1, 'active', 'smarttv.jpg'),
('Wireless Headphones', 'Noise-cancelling Bluetooth headphones', 149.99, 129.99, 60, 'WH-008', 0, 'active', 'headphones.jpg'),
('Coffee Maker', 'Programmable drip coffee maker', 79.99, 69.99, 35, 'CM-009', 0, 'active', 'coffeemaker.jpg'),
('Fitness Tracker', 'Smart fitness band with heart rate monitor', 59.99, 49.99, 70, 'FT-010', 0, 'active', 'fitnesstracker.jpg'),
('The Great Gatsby', 'Classic novel by F. Scott Fitzgerald', 12.99, 9.99, 150, 'BOOK-011', 0, 'active', 'greatgatsby.jpg'),
('Yoga Mat', 'Non-slip exercise mat for yoga', 24.99, 19.99, 90, 'YM-012', 0, 'active', 'yogamat.jpg'),
('Kitchen Blender', 'High-speed blender for smoothies and more', 89.99, 79.99, 40, 'KB-013', 0, 'active', 'blender.jpg'),
('Hiking Backpack', 'Durable backpack for outdoor adventures', 69.99, 59.99, 55, 'HB-014', 0, 'active', 'hikingbackpack.jpg'),
('Gaming Mouse', 'Ergonomic mouse with customizable buttons', 49.99, 39.99, 65, 'GM-015', 0, 'active', 'gamingmouse.jpg'),
('Air Purifier', 'HEPA filter air purifier for home', 129.99, 109.99, 30, 'AP-016', 0, 'active', 'airpurifier.jpg'),
('Digital Camera', 'Professional DSLR camera with accessories', 799.99, 749.99, 20, 'DC-017', 1, 'active', 'camera.jpg'),
('Men''s Running Shoes', 'Comfortable shoes for jogging and running', 79.99, 69.99, 75, 'MRS-018', 0, 'active', 'runningshoes.jpg'),
('Women''s Handbag', 'Stylish leather handbag with multiple compartments', 59.99, 49.99, 60, 'WHB-019', 0, 'active', 'handbag.jpg'),
('Bluetooth Speaker', 'Portable wireless speaker with rich sound', 69.99, 59.99, 50, 'BS-020', 0, 'active', 'bluetoothspeaker.jpg');

-- Link products to categories
INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(1, 6), -- iPhone to Smartphones
(2, 6), -- Samsung to Smartphones
(3, 7), -- Dell XPS to Laptops
(4, 7), -- MacBook to Laptops
(5, 8), -- Men's Shirt to Men's Clothing
(6, 9), -- Women's Dress to Women's Clothing
(7, 1), -- TV to Electronics
(8, 1), -- Headphones to Electronics
(9, 3), -- Coffee Maker to Home & Kitchen
(10, 5), -- Fitness Tracker to Sports & Outdoors
(11, 4), -- Book to Books
(12, 5), -- Yoga Mat to Sports & Outdoors
(13, 3), -- Blender to Home & Kitchen
(14, 5), -- Backpack to Sports & Outdoors
(15, 1), -- Gaming Mouse to Electronics
(16, 3), -- Air Purifier to Home & Kitchen
(17, 1), -- Camera to Electronics
(18, 8), -- Men's Shoes to Men's Clothing
(19, 9), -- Handbag to Women's Clothing
(20, 1); -- Speaker to Electronics
