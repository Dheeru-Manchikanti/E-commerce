USE ecommerce; DELETE FROM admin_users WHERE username='admin'; INSERT INTO admin_users (username, password, email) VALUES ('admin', 'y', 'admin@example.com');
