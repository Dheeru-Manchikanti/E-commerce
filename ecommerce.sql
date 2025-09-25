-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 03, 2025 at 02:09 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `created_at`, `last_login`) VALUES
(2, 'admin', '$2y$10$80bJRXDa3/OusYWekDCj7eT0jdZEuGKrs/dlfvUnTINRJU8F92Dmy', 'admin@example.com', '2025-05-31 19:03:23', '2025-06-03 11:24:29');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `parent_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Electronicss', 'Electronic devices and gadgets', NULL, 'active', '2025-05-31 18:53:52', '2025-06-02 17:45:17'),
(2, 'Clothing', 'Fashion items and apparel', NULL, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(3, 'Home & Kitchen', 'Home essentials and kitchen appliances', NULL, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(4, 'Bookss', 'Books of various genres', NULL, 'active', '2025-05-31 18:53:52', '2025-06-02 14:10:42'),
(5, 'Sports & Outdoors', 'Sports equipment and outdoor gear', NULL, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(6, 'Smartphones', 'Mobile phones and accessories', 1, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(7, 'Laptops', 'Portable computers', 1, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(8, 'Men\'s Clothing', 'Clothing items for men', 2, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(9, 'Women\'s Clothing', 'Clothing items for women', 2, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `email`, `first_name`, `last_name`, `phone`, `address`, `city`, `state`, `postal_code`, `country`, `created_at`) VALUES
(1, 'jhon@got.com', 'Jhon', 'Snow', '9100639585', 'North Wall', 'North', 'Ice', '522002', 'India', '2025-05-31 19:13:41'),
(2, 'will@gmail.com', 'Will', 'Smith', '9100638734', 'Dummy', 'Dummy', 'Dummy', '12345', 'Dummy', '2025-06-01 04:08:30');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `billing_address` text NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `user_id`, `order_number`, `total_amount`, `status`, `shipping_address`, `billing_address`, `payment_method`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'ORD-20250531-NFDOY', 949.99, 'pending', 'North Wall, North, Ice 522002, India', 'North Wall, North, Ice 522002, India', 'credit_card', NULL, '2025-05-31 19:13:41', '2025-05-31 19:13:41'),
(2, 2, 1, 'ORD-20250601-A74AL', 1799.98, 'pending', 'Dummy, Dummy, Dummy 12345, Dummy', 'Dummy, Dummy, Dummy 12345, Dummy', 'credit_card', NULL, '2025-06-01 04:08:30', '2025-06-01 04:08:30');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 949.99),
(2, 2, 2, 1, 849.99),
(3, 2, 1, 1, 949.99);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `sale_price`, `stock_quantity`, `sku`, `featured`, `status`, `image_main`, `created_at`, `updated_at`) VALUES
(1, 'iPhone 13 Pro', 'Latest Apple smartphone with advanced features', 999.99, 949.99, 48, 'IP13PRO-001', 1, 'active', '1748770835_iPhone_13_Pro.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:40:35'),
(2, 'Samsung Galaxy S21', 'Flagship Android smartphone with excellent camera', 899.99, 849.99, 44, 'SGS21-002', 1, 'active', '1748770824_Samsung_Galaxy_S21.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:40:24'),
(3, 'Dell XPS 13', 'Ultrabook with 4K display and Intel Core i7', 1299.99, 1199.99, 30, 'DXPS13-003', 1, 'active', '1748770805_Dell_XPS_13.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:40:05'),
(4, 'MacBook Air M1', 'Thin and light laptop with Apple Silicon', 999.99, 949.99, 40, 'MBA-M1-004', 1, 'active', '1748770776_MacBook_Air_M1.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:39:36'),
(5, 'Men&#039;s Casual Shirt', 'Comfortable cotton shirt for daily wear', 29.99, 24.99, 100, 'MCS-005', 0, 'active', '1748771055_Men_s_Casual_Shirt.jpg', '2025-05-31 18:53:52', '2025-06-01 09:44:15'),
(6, 'Women&#039;s Summer Dress', 'Light and colorful summer dress', 39.99, 34.99, 80, 'WSD-006', 0, 'active', '1748770669_Women_s_Summer_Dress.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:37:49'),
(7, 'Smart LED TV 55&quot;', '4K Ultra HD Smart LED Television', 599.99, 549.99, 25, 'TV55-007', 1, 'active', '1748770655_Smart_LED_TV_55.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:37:35'),
(8, 'Wireless Headphones', 'Noise-cancelling Bluetooth headphones', 149.99, 129.99, 60, 'WH-008', 0, 'active', '1748770639_Wireless_Headphones.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:37:19'),
(9, 'Coffee Maker', 'Programmable drip coffee maker', 79.99, 69.99, 35, 'CM-009', 0, 'active', '1748770624_Coffee_Maker.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:37:04'),
(10, 'Fitness Tracker', 'Smart fitness band with heart rate monitor', 59.99, 49.99, 70, 'FT-010', 0, 'active', '1748770598_Fitness_Tracker.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:36:38'),
(11, 'The Great Gatsby', 'Classic novel by F. Scott Fitzgerald', 12.99, 9.99, 150, 'BOOK-011', 0, 'active', '1748770583_The_Great_Gatsby.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:36:23'),
(12, 'Yoga Mat', 'Non-slip exercise mat for yoga', 24.99, 19.99, 90, 'YM-012', 0, 'active', '1748770565_Yoga_Mat.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:36:05'),
(13, 'Kitchen Blender', 'High-speed blender for smoothies and more', 89.99, 79.99, 40, 'KB-013', 0, 'active', '1748770552_Kitchen_Blender.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:35:52'),
(14, 'Hiking Backpack', 'Durable backpack for outdoor adventures', 69.99, 59.99, 55, 'HB-014', 0, 'active', '1748770536_Hiking_Backpack.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:35:36'),
(15, 'Gaming Mouse', 'Ergonomic mouse with customizable buttons', 49.99, 39.99, 65, 'GM-015', 0, 'active', '1748770490_Gaming_Mouse.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:35:22'),
(16, 'Air Purifier', 'HEPA filter air purifier for home', 129.99, 109.99, 30, 'AP-016', 0, 'active', '1748770476_Air_Purifier.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:34:36'),
(17, 'Digital Camera', 'Professional DSLR camera with accessories', 799.99, 749.99, 20, 'DC-017', 1, 'active', '1748770451_Digital_Camera.jpeg', '2025-05-31 18:53:52', '2025-06-03 11:26:46'),
(18, 'Men&#039;s Running Shoes', 'Comfortable shoes for jogging and running', 79.99, 69.99, 75, 'MRS-018', 0, 'active', '1748769644_Men_s_Running_Shoes.jpeg', '2025-05-31 18:53:52', '2025-06-03 11:26:46'),
(19, 'Women&#039;s Handbag', 'Stylish leather handbag with multiple compartments', 2000.00, 600.00, 60, 'WHB-019', 0, 'active', '1748769513_Women_s_Handbag.jpeg', '2025-05-31 18:53:52', '2025-06-03 11:26:46'),
(20, 'Bluetooth Speaker', 'Portable wireless speaker with rich sound', 1500.00, 950.00, 50, 'BS-020', 0, 'active', '1748770463_Bluetooth_Speaker.jpeg', '2025-05-31 18:53:52', '2025-06-03 11:26:46');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(1, 6),
(2, 6),
(3, 7),
(4, 7),
(5, 8),
(6, 9),
(7, 1),
(8, 1),
(9, 3),
(10, 5),
(11, 4),
(12, 5),
(13, 3),
(14, 5),
(15, 1),
(16, 3),
(17, 1),
(18, 8),
(19, 9),
(20, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `first_name`, `last_name`, `phone`, `is_active`, `created_at`, `last_login`, `email_verified`, `verification_token`) VALUES
(1, 'will@gmail.com', '$2y$10$3c0OAeJGB90h8ibyX1qpPOykEz7DmObX77pdL3sUYZwTNhx.hkvLy', 'Will', 'Smith', '9100638734', 1, '2025-06-01 03:10:21', '2025-06-03 11:30:48', 1, '4ebef56bb937c359900c49a16524c46d4f3731188739d48014e9ec3114d9ebd2'),
(2, 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', '555-0123', 1, '2025-06-01 12:12:17', NULL, 1, NULL),
(3, 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', '555-0456', 1, '2025-06-01 12:12:17', NULL, 1, NULL),
(4, 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', '555-0789', 1, '2025-06-01 12:12:17', NULL, 1, NULL),
(6, 'james@gmail.com', '$2y$10$uMa5G.MPEYhXjOlF3ek75OoXF.qwVlrB/LS8VMvHTLk.czUvk0c0y', 'James', 'Bond', '9394225163', 1, '2025-06-01 16:45:18', '2025-06-01 16:45:36', 1, '08e7bf66f1657088bffa78626e3fd0f78690bf70c62b166fec643d136c83b9bd'),
(7, 'Harvey@gmail.com', '$2y$10$D/Lnef2z0RPPvFb3CIq.KuIK3eMxcYsizBx4xGEmPZ.weRz9tcz0e', 'Harvey', 'Spector', '9394331542', 1, '2025-06-03 11:33:25', '2025-06-03 11:33:47', 1, 'adcacf379fff845d79fc97407edfbc7ba475c70202f5b4814fd6b836b34245e4');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('shipping','billing','both') NOT NULL DEFAULT 'both',
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `country` varchar(100) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `address_type`, `address`, `city`, `state`, `postal_code`, `country`, `is_default`) VALUES
(1, 1, 'both', '123 Main Street', 'New York', 'NY', '10001', 'USA', 1),
(2, 2, 'both', '456 Oak Avenue', 'Los Angeles', 'CA', '90210', 'USA', 1),
(3, 3, 'both', '789 Pine Road', 'Chicago', 'IL', '60601', 'USA', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`product_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
