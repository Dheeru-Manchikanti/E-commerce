<?php
// Include database and functions
require_once '../includes/init.php';

// Create admin user if it doesn't exist
$db->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
$result = $db->single();

if ($result['count'] == 0) {
    // Admin doesn't exist, create one
    $username = 'admin';
    $password = 'admin123';
    $email = 'admin@example.com';
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $db->query("INSERT INTO admin_users (username, password, email) VALUES (:username, :password, :email)");
    $db->bind(':username', $username);
    $db->bind(':password', $hashedPassword);
    $db->bind(':email', $email);
    
    $success = $db->execute();
    
    if ($success) {
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<a href='login.php'>Go to login page</a>";
    } else {
        echo "Failed to create admin user.";
    }
} else {
    // Admin exists, show debug info
    $db->query("SELECT id, username, email, password FROM admin_users WHERE username = 'admin'");
    $admin = $db->single();
    
    echo "Admin user already exists:<br>";
    echo "ID: " . $admin['id'] . "<br>";
    echo "Username: " . $admin['username'] . "<br>";
    echo "Email: " . $admin['email'] . "<br>";
    echo "Password Length: " . strlen($admin['password']) . "<br>";
    echo "Password Hash Prefix: " . substr($admin['password'], 0, 10) . "...<br>";
    
    // Test password verification
    $testPassword = 'admin123';
    echo "<br>Testing password verification with 'admin123': ";
    echo password_verify($testPassword, $admin['password']) ? "SUCCESS" : "FAILED";
    
    // Fix password if verification fails
    if (!password_verify($testPassword, $admin['password'])) {
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        $db->query("UPDATE admin_users SET password = :password WHERE id = :id");
        $db->bind(':password', $newHash);
        $db->bind(':id', $admin['id']);
        $result = $db->execute();
        echo "<br><br><strong>Password has been reset. Please try logging in again.</strong>";
    }
    
    echo "<br><br><a href='login.php'>Go to login page</a>";
}
?>
