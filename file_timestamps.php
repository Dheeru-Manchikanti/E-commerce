<?php
// Start session
session_start();

// Include database and functions
require_once 'includes/init.php';

// Set header for plain text output
header('Content-Type: text/plain');

echo "FILE TIMESTAMP TEST\n";
echo "==================\n\n";

$files = [
    'account.php',
    'login.php',
    'register.php',
    'includes/database.php',
    'includes/functions.php',
    'includes/init.php',
    'includes/public_header.php',
    'includes/public_footer.php',
    'sql/user_tables.sql'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo $file . ": Last modified " . date("Y-m-d H:i:s", filemtime($path)) . "\n";
        echo "  - Size: " . filesize($path) . " bytes\n";
        
        // For PHP files, show first few lines
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($path);
            echo "  - First 100 characters: " . substr(trim($content), 0, 100) . "...\n";
        }
    } else {
        echo $file . ": File not found\n";
    }
    echo "\n";
}

// Also show PHP info
echo "PHP VERSION: " . phpversion() . "\n";
echo "SERVER SOFTWARE: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "DOCUMENT ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SCRIPT FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";

// Check for opcache
if (function_exists('opcache_get_status')) {
    $opcache_status = opcache_get_status();
    echo "OPCACHE ENABLED: " . ($opcache_status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
} else {
    echo "OPCACHE: Not available\n";
}
?>
