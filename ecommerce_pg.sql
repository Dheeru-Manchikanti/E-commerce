-- PostgreSQL version of the ecommerce database

-- Drop existing types and tables to make the script re-runnable
DROP TABLE IF EXISTS "user_addresses" CASCADE;
DROP TABLE IF EXISTS "product_images" CASCADE;
DROP TABLE IF EXISTS "product_categories" CASCADE;
DROP TABLE IF EXISTS "order_items" CASCADE;
DROP TABLE IF EXISTS "orders" CASCADE;
DROP TABLE IF EXISTS "categories" CASCADE;
DROP TABLE IF EXISTS "products" CASCADE;
DROP TABLE IF EXISTS "users" CASCADE;
DROP TABLE IF EXISTS "customers" CASCADE;
DROP TABLE IF EXISTS "admin_users" CASCADE;

DROP TYPE IF EXISTS "category_status";
DROP TYPE IF EXISTS "order_status";
DROP TYPE IF EXISTS "product_status";
DROP TYPE IF EXISTS "address_type";

-- Create ENUM types
CREATE TYPE "category_status" AS ENUM ('active', 'inactive');
CREATE TYPE "order_status" AS ENUM ('pending', 'processing', 'shipped', 'delivered', 'cancelled');
CREATE TYPE "product_status" AS ENUM ('active', 'inactive');
CREATE TYPE "address_type" AS ENUM ('shipping', 'billing', 'both');

-- Function to update timestamp on row update
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = now();
   RETURN NEW;
END;
$$ language 'plpgsql';


-- Table structure for table "admin_users"
CREATE TABLE "admin_users" (
  "id" SERIAL PRIMARY KEY,
  "username" VARCHAR(50) NOT NULL UNIQUE,
  "password" VARCHAR(255) NOT NULL,
  "email" VARCHAR(100) NOT NULL UNIQUE,
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "last_login" TIMESTAMP NULL DEFAULT NULL
);

INSERT INTO "admin_users" ("id", "username", "password", "email", "created_at", "last_login") VALUES
(2, 'admin', '$2y$10$80bJRXDa3/OusYWekDCj7eT0jdZEuGKrs/dlfvUnTINRJU8F92Dmy', 'admin@example.com', '2025-05-31 19:03:23', '2025-06-03 11:24:29');

-- Table structure for table "categories"
CREATE TABLE "categories" (
  "id" SERIAL PRIMARY KEY,
  "name" VARCHAR(100) NOT NULL,
  "description" TEXT DEFAULT NULL,
  "parent_id" INTEGER DEFAULT NULL,
  "status" "category_status" NOT NULL DEFAULT 'active',
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER update_categories_updated_at BEFORE UPDATE ON "categories" FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();

INSERT INTO "categories" ("id", "name", "description", "parent_id", "status", "created_at", "updated_at") VALUES
(1, 'Electronicss', 'Electronic devices and gadgets', NULL, 'active', '2025-05-31 18:53:52', '2025-06-02 17:45:17'),
(2, 'Clothing', 'Fashion items and apparel', NULL, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(3, 'Home & Kitchen', 'Home essentials and kitchen appliances', NULL, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(4, 'Bookss', 'Books of various genres', NULL, 'active', '2025-06-02 14:10:42', '2025-06-02 14:10:42'),
(5, 'Sports & Outdoors', 'Sports equipment and outdoor gear', NULL, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(6, 'Smartphones', 'Mobile phones and accessories', 1, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(7, 'Laptops', 'Portable computers', 1, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(8, 'Men''s Clothing', 'Clothing items for men', 2, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52'),
(9, 'Women''s Clothing', 'Clothing items for women', 2, 'active', '2025-05-31 18:53:52', '2025-05-31 18:53:52');

-- Table structure for table "customers"
CREATE TABLE "customers" (
  "id" SERIAL PRIMARY KEY,
  "email" VARCHAR(100) NOT NULL UNIQUE,
  "first_name" VARCHAR(50) NOT NULL,
  "last_name" VARCHAR(50) NOT NULL,
  "phone" VARCHAR(20) DEFAULT NULL,
  "address" VARCHAR(255) DEFAULT NULL,
  "city" VARCHAR(100) DEFAULT NULL,
  "state" VARCHAR(100) DEFAULT NULL,
  "postal_code" VARCHAR(20) DEFAULT NULL,
  "country" VARCHAR(100) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO "customers" ("id", "email", "first_name", "last_name", "phone", "address", "city", "state", "postal_code", "country", "created_at") VALUES
(1, 'jhon@got.com', 'Jhon', 'Snow', '9100639585', 'North Wall', 'North', 'Ice', '522002', 'India', '2025-05-31 19:13:41'),
(2, 'will@gmail.com', 'Will', 'Smith', '9100638734', 'Dummy', 'Dummy', 'Dummy', '12345', 'Dummy', '2025-06-01 04:08:30');

-- Table structure for table "users"
CREATE TABLE "users" (
  "id" SERIAL PRIMARY KEY,
  "email" VARCHAR(100) NOT NULL UNIQUE,
  "password" VARCHAR(255) NOT NULL,
  "first_name" VARCHAR(50) NOT NULL,
  "last_name" VARCHAR(50) NOT NULL,
  "phone" VARCHAR(20) DEFAULT NULL,
  "is_active" BOOLEAN NOT NULL DEFAULT TRUE,
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "last_login" TIMESTAMP NULL DEFAULT NULL,
  "email_verified" BOOLEAN NOT NULL DEFAULT FALSE,
  "verification_token" VARCHAR(100) DEFAULT NULL
);

INSERT INTO "users" ("id", "email", "password", "first_name", "last_name", "phone", "is_active", "created_at", "last_login", "email_verified", "verification_token") VALUES
(1, 'will@gmail.com', '$2y$10$3c0OAeJGB90h8ibyX1qpPOykEz7DmObX77pdL3sUYZwTNhx.hkvLy', 'Will', 'Smith', '9100638734', TRUE, '2025-06-01 03:10:21', '2025-06-03 11:30:48', TRUE, '4ebef56bb937c359900c49a16524c46d4f3731188739d48014e9ec3114d9ebd2'),
(2, 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', '555-0123', TRUE, '2025-06-01 12:12:17', NULL, TRUE, NULL),
(3, 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', '555-0456', TRUE, '2025-06-01 12:12:17', NULL, TRUE, NULL),
(4, 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test', 'User', '555-0789', TRUE, '2025-06-01 12:12:17', NULL, TRUE, NULL),
(6, 'james@gmail.com', '$2y$10$uMa5G.MPEYhXjOlF3ek75OoXF.qwVlrB/LS8VMvHTLk.czUvk0c0y', 'James', 'Bond', '9394225163', TRUE, '2025-06-01 16:45:18', '2025-06-01 16:45:36', TRUE, '08e7bf66f1657088bffa78626e3fd0f78690bf70c62b166fec643d136c83b9bd'),
(7, 'Harvey@gmail.com', '$2y$10$D/Lnef2z0RPPvFb3CIq.KuIK3eMxcYsizBx4xGEmPZ.weRz9tcz0e', 'Harvey', 'Spector', '9394331542', TRUE, '2025-06-03 11:33:25', '2025-06-03 11:33:47', TRUE, 'adcacf379fff845d79fc97407edfbc7ba475c70202f5b4814fd6b836b34245e4');

-- Table structure for table "orders"
CREATE TABLE "orders" (
  "id" SERIAL PRIMARY KEY,
  "customer_id" INTEGER NOT NULL,
  "user_id" INTEGER DEFAULT NULL,
  "order_number" VARCHAR(20) NOT NULL UNIQUE,
  "total_amount" DECIMAL(10,2) NOT NULL,
  "status" "order_status" NOT NULL DEFAULT 'pending',
  "shipping_address" TEXT NOT NULL,
  "billing_address" TEXT NOT NULL,
  "payment_method" VARCHAR(50) NOT NULL,
  "notes" TEXT DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER update_orders_updated_at BEFORE UPDATE ON "orders" FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();

INSERT INTO "orders" ("id", "customer_id", "user_id", "order_number", "total_amount", "status", "shipping_address", "billing_address", "payment_method", "notes", "created_at", "updated_at") VALUES
(1, 1, NULL, 'ORD-20250531-NFDOY', 949.99, 'pending', 'North Wall, North, Ice 522002, India', 'North Wall, North, Ice 522002, India', 'credit_card', NULL, '2025-05-31 19:13:41', '2025-05-31 19:13:41'),
(2, 2, 1, 'ORD-20250601-A74AL', 1799.98, 'pending', 'Dummy, Dummy, Dummy 12345, Dummy', 'Dummy, Dummy, Dummy 12345, Dummy', 'credit_card', NULL, '2025-06-01 04:08:30', '2025-06-01 04:08:30');

-- Table structure for table "products"
CREATE TABLE "products" (
  "id" SERIAL PRIMARY KEY,
  "name" VARCHAR(255) NOT NULL,
  "description" TEXT DEFAULT NULL,
  "price" DECIMAL(10,2) NOT NULL,
  "sale_price" DECIMAL(10,2) DEFAULT NULL,
  "stock_quantity" INTEGER NOT NULL DEFAULT 0,
  "sku" VARCHAR(50) DEFAULT NULL UNIQUE,
  "featured" BOOLEAN NOT NULL DEFAULT FALSE,
  "status" "product_status" NOT NULL DEFAULT 'active',
  "image_main" VARCHAR(255) DEFAULT NULL,
  "created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER update_products_updated_at BEFORE UPDATE ON "products" FOR EACH ROW EXECUTE PROCEDURE update_updated_at_column();

INSERT INTO "products" ("id", "name", "description", "price", "sale_price", "stock_quantity", "sku", "featured", "status", "image_main", "created_at", "updated_at") VALUES
(1, 'iPhone 13 Pro', 'Latest Apple smartphone with advanced features', 999.99, 949.99, 48, 'IP13PRO-001', TRUE, 'active', '1748770835_iPhone_13_Pro.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:40:35'),
(2, 'Samsung Galaxy S21', 'Flagship Android smartphone with excellent camera', 899.99, 849.99, 44, 'SGS21-002', TRUE, 'active', '1748770824_Samsung_Galaxy_S21.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:40:24'),
(3, 'Dell XPS 13', 'Ultrabook with 4K display and Intel Core i7', 1299.99, 1199.99, 30, 'DXPS13-003', TRUE, 'active', '1748770805_Dell_XPS_13.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:40:05'),
(4, 'MacBook Air M1', 'Thin and light laptop with Apple Silicon', 999.99, 949.99, 40, 'MBA-M1-004', TRUE, 'active', '1748770776_MacBook_Air_M1.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:39:36'),
(5, 'Men''s Casual Shirt', 'Comfortable cotton shirt for daily wear', 29.99, 24.99, 100, 'MCS-005', FALSE, 'active', '1748771055_Men_s_Casual_Shirt.jpg', '2025-05-31 18:53:52', '2025-06-01 09:44:15'),
(6, 'Women''s Summer Dress', 'Light and colorful summer dress', 39.99, 34.99, 80, 'WSD-006', FALSE, 'active', '1748770669_Women_s_Summer_Dress.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:37:49'),
(7, 'Smart LED TV 55"', '4K Ultra HD Smart LED Television', 599.99, 549.99, 25, 'TV55-007', TRUE, 'active', '1748770655_Smart_LED_TV_55.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:37:35'),
(8, 'Wireless Headphones', 'Noise-cancelling Bluetooth headphones', 149.99, 129.99, 60, 'WH-008', FALSE, 'active', '1748770639_Wireless_Headphones.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:37:19'),
(9, 'Coffee Maker', 'Programmable drip coffee maker', 79.99, 69.99, 35, 'CM-009', FALSE, 'active', '1748770624_Coffee_Maker.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:37:04'),
(10, 'Fitness Tracker', 'Smart fitness band with heart rate monitor', 59.99, 49.99, 70, 'FT-010', FALSE, 'active', '1748770598_Fitness_Tracker.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:36:38'),
(11, 'The Great Gatsby', 'Classic novel by F. Scott Fitzgerald', 12.99, 9.99, 150, 'BOOK-011', FALSE, 'active', '1748770583_The_Great_Gatsby.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:36:23'),
(12, 'Yoga Mat', 'Non-slip exercise mat for yoga', 24.99, 19.99, 90, 'YM-012', FALSE, 'active', '1748770565_Yoga_Mat.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:36:05'),
(13, 'Kitchen Blender', 'High-speed blender for smoothies and more', 89.99, 79.99, 40, 'KB-013', FALSE, 'active', '1748770552_Kitchen_Blender.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:35:52'),
(14, 'Hiking Backpack', 'Durable backpack for outdoor adventures', 69.99, 59.99, 55, 'HB-014', FALSE, 'active', '1748770536_Hiking_Backpack.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:35:36'),
(15, 'Gaming Mouse', 'Ergonomic mouse with customizable buttons', 49.99, 39.99, 65, 'GM-015', FALSE, 'active', '1748770490_Gaming_Mouse.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:35:22'),
(16, 'Air Purifier', 'HEPA filter air purifier for home', 129.99, 109.99, 30, 'AP-016', FALSE, 'active', '1748770476_Air_Purifier.jpeg', '2025-05-31 18:53:52', '2025-06-01 09:34:36'),
(17, 'Digital Camera', 'Professional DSLR camera with accessories', 799.99, 749.99, 20, 'DC-017', TRUE, 'active', '1748770451_Digital_Camera.jpeg', '2025-05-31 18:53:52', '2025-06-03 11:26:46'),
(18, 'Men''s Running Shoes', 'Comfortable shoes for jogging and running', 79.99, 69.99, 75, 'MRS-018', FALSE, 'active', '1748769644_Men_s_Running_Shoes.jpeg', '2025-05-31 18:53:52', '2025-06-03 11:26:46'),
(19, 'Women''s Handbag', 'Stylish leather handbag with multiple compartments', 2000.00, 600.00, 60, 'WHB-019', FALSE, 'active', '1748769513_Women_s_Handbag.jpeg', '2025-05-31 18:53:52', '2025-06-03 11:26:46'),
(20, 'Bluetooth Speaker', 'Portable wireless speaker with rich sound', 1500.00, 950.00, 50, 'BS-020', FALSE, 'active', '1748770463_Bluetooth_Speaker.jpeg', '2025-05-31 18:53:52', '2025-06-03 11:26:46');

-- Table structure for table "order_items"
CREATE TABLE "order_items" (
  "id" SERIAL PRIMARY KEY,
  "order_id" INTEGER NOT NULL,
  "product_id" INTEGER NOT NULL,
  "quantity" INTEGER NOT NULL,
  "price" DECIMAL(10,2) NOT NULL
);

INSERT INTO "order_items" ("id", "order_id", "product_id", "quantity", "price") VALUES
(1, 1, 1, 1, 949.99),
(2, 2, 2, 1, 849.99),
(3, 2, 1, 1, 949.99);

-- Table structure for table "product_categories"
CREATE TABLE "product_categories" (
  "product_id" INTEGER NOT NULL,
  "category_id" INTEGER NOT NULL,
  PRIMARY KEY ("product_id", "category_id")
);

INSERT INTO "product_categories" ("product_id", "category_id") VALUES
(1, 6), (2, 6), (3, 7), (4, 7), (5, 8), (6, 9), (7, 1), (8, 1), (9, 3), (10, 5), (11, 4), (12, 5), (13, 3), (14, 5), (15, 1), (16, 3), (17, 1), (18, 8), (19, 9), (20, 1);

-- Table structure for table "product_images"
CREATE TABLE "product_images" (
  "id" SERIAL PRIMARY KEY,
  "product_id" INTEGER NOT NULL,
  "image_path" VARCHAR(255) NOT NULL,
  "sort_order" INTEGER NOT NULL DEFAULT 0
);

-- Table structure for table "user_addresses"
CREATE TABLE "user_addresses" (
  "id" SERIAL PRIMARY KEY,
  "user_id" INTEGER NOT NULL,
  "address_type" "address_type" NOT NULL DEFAULT 'both',
  "address" VARCHAR(255) NOT NULL,
  "city" VARCHAR(100) NOT NULL,
  "state" VARCHAR(100) NOT NULL,
  "postal_code" VARCHAR(20) NOT NULL,
  "country" VARCHAR(100) NOT NULL,
  "is_default" BOOLEAN NOT NULL DEFAULT FALSE
);

INSERT INTO "user_addresses" ("id", "user_id", "address_type", "address", "city", "state", "postal_code", "country", "is_default") VALUES
(1, 1, 'both', '123 Main Street', 'New York', 'NY', '10001', 'USA', TRUE),
(2, 2, 'both', '456 Oak Avenue', 'Los Angeles', 'CA', '90210', 'USA', TRUE),
(3, 3, 'both', '789 Pine Road', 'Chicago', 'IL', '60601', 'USA', TRUE);

-- Foreign Key Constraints
ALTER TABLE "categories" ADD FOREIGN KEY ("parent_id") REFERENCES "categories" ("id") ON DELETE SET NULL;
ALTER TABLE "orders" ADD FOREIGN KEY ("customer_id") REFERENCES "customers" ("id");
ALTER TABLE "orders" ADD FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE SET NULL;
ALTER TABLE "order_items" ADD FOREIGN KEY ("order_id") REFERENCES "orders" ("id") ON DELETE CASCADE;
ALTER TABLE "order_items" ADD FOREIGN KEY ("product_id") REFERENCES "products" ("id");
ALTER TABLE "product_categories" ADD FOREIGN KEY ("product_id") REFERENCES "products" ("id") ON DELETE CASCADE;
ALTER TABLE "product_categories" ADD FOREIGN KEY ("category_id") REFERENCES "categories" ("id") ON DELETE CASCADE;
ALTER TABLE "product_images" ADD FOREIGN KEY ("product_id") REFERENCES "products" ("id") ON DELETE CASCADE;
ALTER TABLE "user_addresses" ADD FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE;

-- Set sequence values
SELECT setval('admin_users_id_seq', 4, false);
SELECT setval('categories_id_seq', 10, false);
SELECT setval('customers_id_seq', 3, false);
SELECT setval('orders_id_seq', 3, false);
SELECT setval('order_items_id_seq', 4, false);
SELECT setval('products_id_seq', 21, false);
SELECT setval('users_id_seq', 8, false);
SELECT setval('user_addresses_id_seq', 4, false);
