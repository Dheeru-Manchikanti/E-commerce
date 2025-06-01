<?php
require_once 'includes/init.php';

echo "<h2>Category Database Debug</h2>";

// Check what categories exist
$sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY id";
$db->query($sql);
$categories = $db->resultSet();

echo "<h3>All Active Categories:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Description</th><th>Parent ID</th></tr>";
foreach ($categories as $cat) {
    echo "<tr>";
    echo "<td>" . $cat['id'] . "</td>";
    echo "<td>" . htmlspecialchars($cat['name']) . "</td>";
    echo "<td>" . htmlspecialchars($cat['description']) . "</td>";
    echo "<td>" . ($cat['parent_id'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test specific category queries
if (isset($_GET['test_id'])) {
    $testId = (int)$_GET['test_id'];
    echo "<h3>Testing Category ID: $testId</h3>";
    
    $sql = "SELECT * FROM categories WHERE id = :id AND status = 'active'";
    $db->query($sql);
    $db->bind(':id', $testId);
    $category = $db->single();
    
    if ($category) {
        echo "<p><strong>Name:</strong> " . htmlspecialchars($category['name']) . "</p>";
        echo "<p><strong>Description:</strong> " . htmlspecialchars($category['description']) . "</p>";
    } else {
        echo "<p style='color: red;'>Category not found or inactive!</p>";
    }
}

echo "<h3>Test Links:</h3>";
foreach ($categories as $cat) {
    echo "<p><a href='?test_id=" . $cat['id'] . "'>Test Category: " . htmlspecialchars($cat['name']) . "</a> | ";
    echo "<a href='category.php?id=" . $cat['id'] . "'>View Category Page</a></p>";
}
?>
