<?php
// Search API endpoint
require_once '../includes/init.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get search query
$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Search query is too short'
    ]);
    exit;
}

// Search products
$searchTerms = '%' . $query . '%';

$sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as additional_image 
        FROM products p 
        WHERE p.status = 'active' AND 
        (p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search) 
        ORDER BY p.name ASC 
        LIMIT 10";
$db->query($sql);
$db->bind(':search', $searchTerms);
$products = $db->resultSet();

// Format results
$results = [];
foreach ($products as $product) {
    $results[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'sale_price' => $product['sale_price'],
        'image_main' => $product['image_main'] ?: $product['additional_image'],
        'url' => 'product.php?id=' . $product['id']
    ];
}

// Return JSON response
echo json_encode([
    'status' => 'success',
    'products' => $results
]);
