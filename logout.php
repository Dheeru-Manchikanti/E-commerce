<?php
session_start();

// Clear user session variables
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);

header('Location: index.php');
exit();
?>
