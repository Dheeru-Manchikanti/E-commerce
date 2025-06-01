<?php
// Check PHP sessions
echo "<h1>PHP Session Diagnostic</h1>";

echo "<h2>Session Settings</h2>";
echo "<pre>";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "</pre>";

echo "<h2>Session Test</h2>";

// Start session
session_start();

// Set a test value
$_SESSION['test_value'] = 'This is a test value: ' . time();

echo "<p>Session started and test value set.</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Test value stored: " . $_SESSION['test_value'] . "</p>";

// Write session and start a new one to verify persistence
session_write_close();
session_start();

echo "<p>Session restarted to test persistence.</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Retrieved test value: " . ($_SESSION['test_value'] ?? 'NOT FOUND') . "</p>";

// Check if cookies are working
echo "<h2>Cookie Test</h2>";
echo "<p>Session cookie should be set in your browser with name: " . session_name() . "</p>";

echo "<h2>Next Steps</h2>";
echo "<p><a href='reset_admin.php'>Reset Admin Password</a></p>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
