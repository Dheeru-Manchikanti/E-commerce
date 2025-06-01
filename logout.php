<?php
// Start session
session_start();

// Clear user session variables
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);

// Optional: Destroy the session entirely
// Note: This may also destroy the shopping cart if it's stored in the session
// session_destroy();

// Redirect to home page
header('Location: index.php');
exit();
?>
