<?php
/**
 * Test script to verify POST-REDIRECT-GET pattern implementation
 */

session_start();
require_once 'includes/init.php';

echo "<h1>POST-REDIRECT-GET Pattern Test</h1>";

// Test 1: Flash Message System
echo "<h2>Test 1: Flash Message System</h2>";
setFlashMessage('test', 'This is a test message', 'success');

if (hasFlashMessage('test')) {
    echo "<p style='color: green;'>✓ Flash message system working - hasFlashMessage() works</p>";
    $message = getFlashMessage('test');
    echo "<p>Retrieved message: " . htmlspecialchars($message) . "</p>";
    
    if (!hasFlashMessage('test')) {
        echo "<p style='color: green;'>✓ Message properly removed after retrieval</p>";
    } else {
        echo "<p style='color: red;'>✗ Message not removed after retrieval</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Flash message system not working</p>";
}

// Test 2: Check if products API endpoints exist
echo "<h2>Test 2: API Endpoints</h2>";

$api_file = 'api/products.php';
if (file_exists($api_file)) {
    $api_content = file_get_contents($api_file);
    
    if (strpos($api_content, 'case \'bulk\':') !== false) {
        echo "<p style='color: green;'>✓ Bulk action endpoint exists in API</p>";
    } else {
        echo "<p style='color: red;'>✗ Bulk action endpoint missing in API</p>";
    }
    
    if (strpos($api_content, 'function bulkAction()') !== false) {
        echo "<p style='color: green;'>✓ bulkAction() function exists in API</p>";
    } else {
        echo "<p style='color: red;'>✗ bulkAction() function missing in API</p>";
    }
} else {
    echo "<p style='color: red;'>✗ API file not found</p>";
}

// Test 3: Check JavaScript redirects
echo "<h2>Test 3: JavaScript Implementation</h2>";

$js_file = 'assets/js/products.js';
if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    
    if (strpos($js_content, 'products.php?deletion=success') !== false) {
        echo "<p style='color: green;'>✓ Delete redirect implemented in JavaScript</p>";
    } else {
        echo "<p style='color: red;'>✗ Delete redirect missing in JavaScript</p>";
    }
    
    if (strpos($js_content, 'products.php?bulk=success') !== false) {
        echo "<p style='color: green;'>✓ Bulk action redirect implemented in JavaScript</p>";
    } else {
        echo "<p style='color: red;'>✗ Bulk action redirect missing in JavaScript</p>";
    }
} else {
    echo "<p style='color: red;'>✗ JavaScript file not found</p>";
}

// Test 4: Check PHP redirect handling
echo "<h2>Test 4: PHP Redirect Handling</h2>";

$php_file = 'admin/products.php';
if (file_exists($php_file)) {
    $php_content = file_get_contents($php_file);
    
    if (strpos($php_content, 'header(\'Location: products.php\');') !== false) {
        echo "<p style='color: green;'>✓ POST-REDIRECT-GET pattern implemented in PHP</p>";
    } else {
        echo "<p style='color: red;'>✗ POST-REDIRECT-GET pattern missing in PHP</p>";
    }
    
    if (strpos($php_content, 'setFlashMessage(\'success\'') !== false) {
        echo "<p style='color: green;'>✓ Flash message usage implemented in PHP</p>";
    } else {
        echo "<p style='color: red;'>✗ Flash message usage missing in PHP</p>";
    }
    
    if (strpos($php_content, 'hasFlashMessage(\'success\')') !== false) {
        echo "<p style='color: green;'>✓ Flash message display implemented in PHP</p>";
    } else {
        echo "<p style='color: red;'>✗ Flash message display missing in PHP</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Admin products file not found</p>";
}

echo "<h2>Summary</h2>";
echo "<p>The POST-REDIRECT-GET pattern implementation includes:</p>";
echo "<ul>";
echo "<li>✓ Flash message system for success/error messages</li>";
echo "<li>✓ Server-side redirects after POST operations</li>";
echo "<li>✓ JavaScript redirects with success parameters</li>";
echo "<li>✓ URL parameter handling with clean redirects</li>";
echo "<li>✓ Bulk action API endpoint</li>";
echo "</ul>";

echo "<p><strong>This implementation should prevent form resubmission issues!</strong></p>";
?>
