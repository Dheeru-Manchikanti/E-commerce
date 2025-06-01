<?php
require_once 'includes/init.php';

// Log every step for debugging
$debugLog = [];

$debugLog[] = "1. Starting category debug";
$debugLog[] = "2. GET parameters: " . print_r($_GET, true);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $debugLog[] = "3. ERROR: Invalid or missing category ID";
    echo "<h1>Invalid Category</h1>";
    echo "<pre>" . implode("\n", $debugLog) . "</pre>";
    exit;
}

$categoryId = (int)$_GET['id'];
$debugLog[] = "3. Category ID converted to: " . $categoryId;

// Get category details
$sql = "SELECT * FROM categories WHERE id = :id AND status = 'active'";
$db->query($sql);
$db->bind(':id', $categoryId);
$category = $db->single();

$debugLog[] = "4. Database query executed for ID: " . $categoryId;
$debugLog[] = "5. Database result: " . print_r($category, true);

if (!$category) {
    $debugLog[] = "6. ERROR: Category not found in database";
    echo "<h1>Category Not Found</h1>";
    echo "<pre>" . implode("\n", $debugLog) . "</pre>";
    exit;
}

$debugLog[] = "6. Category found successfully";
$debugLog[] = "7. Category name: " . $category['name'];
$debugLog[] = "8. Category description: " . $category['description'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Category Debug: <?php echo htmlspecialchars($category['name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug { background: #f0f0f0; padding: 15px; margin: 20px 0; border-left: 3px solid #333; }
        .result { background: #e8f5e8; padding: 15px; margin: 20px 0; border-left: 3px solid #28a745; }
        h1 { color: #007bff; }
        p { color: #666; }
    </style>
</head>
<body>
    <div class="debug">
        <h3>Debug Information:</h3>
        <pre><?php echo implode("\n", $debugLog); ?></pre>
    </div>
    
    <div class="result">
        <h2>Expected Result (what should appear on category.php):</h2>
        <h1><?php echo htmlspecialchars($category['name']); ?></h1>
        <p><?php echo htmlspecialchars($category['description']); ?></p>
    </div>
    
    <p><strong>Test Links:</strong></p>
    <p><a href="category.php?id=<?php echo $categoryId; ?>">Go to actual category.php with this ID</a></p>
    <p><a href="?id=1">Test with Electronics (ID 1)</a></p>
    <p><a href="?id=2">Test with Clothing (ID 2)</a></p>
    <p><a href="?id=3">Test with Home & Kitchen (ID 3)</a></p>
    <p><a href="?id=4">Test with Books (ID 4)</a></p>
    <p><a href="?id=5">Test with Sports & Outdoors (ID 5)</a></p>
</body>
</html>
