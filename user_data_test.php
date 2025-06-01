<?php
// Test file to check database connection and retrieve user data
// Start session
session_start();

// Include database and functions
require_once 'includes/init.php';

// Set header for plain text output
header('Content-Type: text/plain');

echo "USER DATA TEST\n";
echo "==============\n\n";

// Check if user session exists
echo "Session Data:\n";
echo "-------------\n";
var_dump($_SESSION);
echo "\n\n";

// If user ID exists in session, try to get user data
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    echo "User ID from session: " . $userId . "\n\n";
    
    echo "Testing Database Connection:\n";
    echo "--------------------------\n";
    
    try {
        // Test the database connection
        $db->query("SELECT 'Database connection successful' as test");
        $result = $db->single();
        echo $result['test'] . "\n\n";
        
        // Get user data with explicit field selection
        echo "User Data from Database:\n";
        echo "----------------------\n";
        
        $db->query("SELECT id, email, password, first_name, last_name, phone, is_active, created_at, 
                  last_login, email_verified
                  FROM users WHERE id = :id");
        $db->bind(':id', $userId);
        $user = $db->single();
        
        if ($user) {
            echo "User Found:\n";
            foreach ($user as $key => $value) {
                if ($key != 'password') { // Don't print password
                    echo "- $key: $value\n";
                } else {
                    echo "- $key: [REDACTED]\n";
                }
            }
        } else {
            echo "No user found with ID: $userId\n";
        }
    } catch (Exception $e) {
        echo "Database Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No user session found. Please log in first.\n";
}
?>
