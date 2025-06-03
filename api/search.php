<?php
// Search API
require_once '../includes/init.php';


header('Content-Type: application/json');


$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';

if (empty($query) || strlen($query) < 2) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Search query is too short'
    ]);
    exit;
}


$searchTerms = '%' . $query . '%';

$sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as additional_image 
        FROM products p 
        WHERE p.status = 'active' AND 
        (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?) 
        ORDER BY p.name ASC 
        LIMIT 10";
$db->query($sql);
$db->bind(1, $searchTerms);
$db->bind(2, $searchTerms);
$db->bind(3, $searchTerms);
$products = $db->resultSet();


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


echo json_encode([
    'status' => 'success',
    'products' => $results
]);
