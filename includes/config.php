<?php

$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    // Parse the PostgreSQL URL provided by Render
    $dbopts = parse_url($dbUrl);
    define('DB_HOST', $dbopts['host']);
    define('DB_PORT', $dbopts['port']);
    define('DB_USER', $dbopts['user']);
    define('DB_PASS', $dbopts['pass']);
    define('DB_NAME', ltrim($dbopts['path'], '/'));
    define('DB_DRIVER', 'pgsql');
} else {
    // Fallback for local development or other environments
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: '3306'); // Default MySQL port
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'ecommerce');
    define('DB_DRIVER', 'mysql');
}

// Site configuration
define('SITE_URL', getenv('SITE_URL'));
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOADS_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('UPLOADS_URL', SITE_URL . '/uploads/');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
