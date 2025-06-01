<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once 'config.php';
require_once 'database.php';
require_once 'functions.php';

// Database connection
$db = new Database();

// Security headers
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

// Set default timezone
date_default_timezone_set('UTC');
