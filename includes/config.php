<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce');

// Site configuration
define('SITE_URL', 'http://localhost/Ecommerce');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/Ecommerce/uploads/');
define('UPLOADS_URL', SITE_URL . '/uploads/');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
