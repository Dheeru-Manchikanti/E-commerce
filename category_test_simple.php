<?php
require_once 'includes/init.php';

// Simple test for category data
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $categoryId = (int)$_GET['id'];
    
    $sql = "SELECT * FROM categories WHERE id = :id AND status = 'active'";
    $db->query($sql);
    $db->bind(':id', $categoryId);
    $category = $db->single();
    
    if ($category) {
        echo "<h1>Category Test Page</h1>";
        echo "<p><strong>Category ID:</strong> " . $categoryId . "</p>";
        echo "<p><strong>Category Name from Database:</strong> " . htmlspecialchars($category['name']) . "</p>";
        echo "<p><strong>Category Description from Database:</strong> " . htmlspecialchars($category['description']) . "</p>";
        
        echo "<hr>";
        echo "<h2>This should match the category.php display:</h2>";
        echo "<h1>" . htmlspecialchars($category['name']) . "</h1>";
        echo "<p>" . htmlspecialchars($category['description']) . "</p>";
        
        echo "<hr>";
        echo "<p><a href='category.php?id=" . $categoryId . "'>Go to actual category page</a></p>";
    } else {
        echo "<p>Category not found!</p>";
    }
} else {
    echo "<h1>Category Test</h1>";
    echo "<p>Select a category to test:</p>";
    
    $sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY name";
    $db->query($sql);
    $categories = $db->resultSet();
    
    foreach ($categories as $cat) {
        echo "<p><a href='?id=" . $cat['id'] . "'>" . htmlspecialchars($cat['name']) . " (ID: " . $cat['id'] . ")</a></p>";
    }
}
?>
