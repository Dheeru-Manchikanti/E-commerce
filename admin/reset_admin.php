<?php
// Include database and functions
require_once '../includes/init.php';

// Force reset admin password (emergency script)
$username = 'admin';
$password = 'admin123';
$email = 'admin@example.com';

// Create proper bcrypt hash (with known working algorithm)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// First check if admin exists
$db->query("SELECT id FROM admin_users WHERE username = :username");
$db->bind(':username', $username);
$admin = $db->single();

if ($admin) {
    // Admin exists, update password
    $db->query("UPDATE admin_users SET password = :password WHERE id = :id");
    $db->bind(':password', $hashedPassword);
    $db->bind(':id', $admin['id']);
    $result = $db->execute();
    
    if ($result) {
        echo "<h2>Success!</h2>";
        echo "<p>Admin password has been reset.</p>";
        echo "<p>Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong></p>";
        
        // Check if password verification works with new hash
        echo "<p>Verifying password hash: ";
        echo password_verify('admin123', $hashedPassword) ? "WORKS" : "FAILS";
        echo "</p>";
        
        // Display the hash details
        echo "<p>New Password Hash: " . substr($hashedPassword, 0, 10) . "...</p>";
        echo "<p>Hash Length: " . strlen($hashedPassword) . "</p>";
        
        // Display info about algorithm
        $info = password_get_info($hashedPassword);
        echo "<p>Algorithm: " . ($info['algoName'] ?? 'unknown') . "</p>";
        
        echo "<p><a href='login.php'>Go to login page</a></p>";
    } else {
        echo "<h2>Error</h2>";
        echo "<p>Failed to update the admin password.</p>";
    }
} else {
    // Admin doesn't exist, create one
    $db->query("INSERT INTO admin_users (username, password, email) VALUES (:username, :password, :email)");
    $db->bind(':username', $username);
    $db->bind(':password', $hashedPassword);
    $db->bind(':email', $email);
    
    $success = $db->execute();
    
    if ($success) {
        echo "<h2>Success!</h2>";
        echo "<p>Admin user created successfully!</p>";
        echo "<p>Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong></p>";
        echo "<p><a href='login.php'>Go to login page</a></p>";
    } else {
        echo "<h2>Error</h2>";
        echo "<p>Failed to create admin user.</p>";
    }
}
?>
