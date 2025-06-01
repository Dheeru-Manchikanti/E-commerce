<?php
/**
 * Products API
 * 
 * This file handles API requests for products
 */

// Include necessary files
require_once '../includes/init.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle different actions
switch ($action) {
    case 'get':
        getProduct();
        break;
    case 'list':
        listProducts();
        break;
    case 'update':
        updateProduct();
        break;
    case 'delete':
        deleteProduct();
        break;
    default:
        sendResponse('error', 'Invalid action', 400);
}

/**
 * Get a single product by ID
 */
function getProduct() {
    global $db;
    
    // Check if ID is provided
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        sendResponse('error', 'Product ID is required', 400);
    }
    
    try {
        // Get product data
        $db->query("SELECT * FROM products WHERE id = :id");
        $db->bind(':id', $id);
        $product = $db->single();
        
        if (!$product) {
            sendResponse('error', 'Product not found', 404);
        }
        
        // Decode HTML entities in name and description - this prevents display issues
        $product['name'] = desanitize($product['name']);
        $product['description'] = desanitize($product['description']);
        
        // Get product categories
        $db->query("SELECT category_id FROM product_categories WHERE product_id = :product_id");
        $db->bind(':product_id', $id);
        $categories = $db->resultSet();
        $product['categories'] = [];
        foreach ($categories as $category) {
            $product['categories'][] = $category['category_id'];
        }
        
        // Get product images
        $db->query("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order");
        $db->bind(':product_id', $id);
        $product['additional_images'] = $db->resultSet();
        
        sendResponse('success', 'Product retrieved successfully', 200, $product);
    } catch (Exception $e) {
        sendResponse('error', 'Error retrieving product: ' . $e->getMessage(), 500);
    }
}

/**
 * List products with filtering and pagination
 */
function listProducts() {
    global $db;
    
    // Get parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    // Filters
    $name = isset($_GET['name']) ? trim($_GET['name']) : '';
    $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    
    try {
        // Base query
        $sql = "SELECT p.*, 
                (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') 
                FROM categories c 
                JOIN product_categories pc ON c.id = pc.category_id 
                WHERE pc.product_id = p.id) as category_names 
                FROM products p";
        
        $binds = [];
        $whereClauses = [];
        
        // Add filters if provided
        if (!empty($name)) {
            $whereClauses[] = "p.name LIKE :name";
            $binds[':name'] = "%$name%";
        }
        
        if ($category > 0) {
            $sql .= " JOIN product_categories pc2 ON p.id = pc2.product_id";
            $whereClauses[] = "pc2.category_id = :category";
            $binds[':category'] = $category;
        }
        
        if (!empty($status)) {
            $whereClauses[] = "p.status = :status";
            $binds[':status'] = $status;
        }
        
        // Add where clause if necessary
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        // Add sorting and pagination
        $sql .= " ORDER BY p.id DESC LIMIT :offset, :limit";
        $binds[':offset'] = $offset;
        $binds[':limit'] = $limit;
        
        // Execute query
        $db->query($sql);
        
        // Bind parameters
        foreach ($binds as $param => $value) {
            $db->bind($param, $value);
        }
        
        $products = $db->resultSet();
        
        // Process products
        foreach ($products as &$product) {
            $product['name'] = desanitize($product['name']);
            $product['description'] = desanitize($product['description']);
        }
        
        // Count total products (for pagination)
        $countSql = "SELECT COUNT(*) as total FROM products p";
        
        // Add joins and where clauses for counting
        if ($category > 0) {
            $countSql .= " JOIN product_categories pc2 ON p.id = pc2.product_id";
        }
        
        if (!empty($whereClauses)) {
            $countSql .= " WHERE " . implode(" AND ", $whereClauses);
        }
        
        $db->query($countSql);
        
        // Bind parameters again for count query
        foreach ($binds as $param => $value) {
            if ($param !== ':offset' && $param !== ':limit') {
                $db->bind($param, $value);
            }
        }
        
        $totalResult = $db->single();
        $totalProducts = $totalResult['total'];
        
        // Calculate pagination info
        $totalPages = ceil($totalProducts / $limit);
        
        // Prepare response
        $response = [
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_records' => $totalProducts,
                'total_pages' => $totalPages
            ]
        ];
        
        sendResponse('success', 'Products retrieved successfully', 200, $response);
    } catch (Exception $e) {
        sendResponse('error', 'Error retrieving products: ' . $e->getMessage(), 500);
    }
}

/**
 * Update product
 */
function updateProduct() {
    global $db;
    
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse('error', 'Invalid request method', 405);
    }
    
    // Get product data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        sendResponse('error', 'Invalid JSON data', 400);
    }
    
    // Check if ID is provided
    $id = isset($data['id']) ? (int)$data['id'] : 0;
    if ($id <= 0) {
        sendResponse('error', 'Product ID is required', 400);
    }
    
    try {
        // Check if product exists
        $db->query("SELECT id FROM products WHERE id = :id");
        $db->bind(':id', $id);
        if ($db->rowCount() === 0) {
            sendResponse('error', 'Product not found', 404);
        }
        
        // Start transaction
        $db->beginTransaction();
        
        // Update product
        $sql = "UPDATE products SET ";
        $fields = [];
        $binds = [':id' => $id];
        
        // Handle name
        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $binds[':name'] = sanitize($data['name']);
        }
        
        // Handle description
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $binds[':description'] = sanitize($data['description']);
        }
        
        // Handle price
        if (isset($data['price'])) {
            $fields[] = "price = :price";
            $binds[':price'] = (float)$data['price'];
        }
        
        // Handle sale price
        if (isset($data['sale_price'])) {
            $fields[] = "sale_price = :sale_price";
            $binds[':sale_price'] = !empty($data['sale_price']) ? (float)$data['sale_price'] : null;
        }
        
        // Handle stock quantity
        if (isset($data['stock_quantity'])) {
            $fields[] = "stock_quantity = :stock_quantity";
            $binds[':stock_quantity'] = (int)$data['stock_quantity'];
        }
        
        // Handle SKU
        if (isset($data['sku'])) {
            $fields[] = "sku = :sku";
            $binds[':sku'] = sanitize($data['sku']);
        }
        
        // Handle featured
        if (isset($data['featured'])) {
            $fields[] = "featured = :featured";
            $binds[':featured'] = $data['featured'] ? 1 : 0;
        }
        
        // Handle status
        if (isset($data['status'])) {
            $fields[] = "status = :status";
            $binds[':status'] = sanitize($data['status']);
        }
        
        // Only update if fields to update
        if (!empty($fields)) {
            $fields[] = "updated_at = NOW()";
            $sql .= implode(", ", $fields) . " WHERE id = :id";
            
            $db->query($sql);
            foreach ($binds as $param => $value) {
                $db->bind($param, $value);
            }
            $db->execute();
        }
        
        // Handle categories if provided
        if (isset($data['categories'])) {
            // Delete existing categories
            $db->query("DELETE FROM product_categories WHERE product_id = :product_id");
            $db->bind(':product_id', $id);
            $db->execute();
            
            // Add new categories
            if (!empty($data['categories'])) {
                $values = [];
                $categoryBinds = [];
                $i = 0;
                
                foreach ($data['categories'] as $categoryId) {
                    $values[] = "(:product_id" . $i . ", :category_id" . $i . ")";
                    $categoryBinds[':product_id' . $i] = $id;
                    $categoryBinds[':category_id' . $i] = $categoryId;
                    $i++;
                }
                
                $categorySql = "INSERT INTO product_categories (product_id, category_id) VALUES " . implode(', ', $values);
                $db->query($categorySql);
                
                foreach ($categoryBinds as $param => $value) {
                    $db->bind($param, $value);
                }
                
                $db->execute();
            }
        }
        
        $db->commit();
        
        // Get updated product
        $db->query("SELECT * FROM products WHERE id = :id");
        $db->bind(':id', $id);
        $updatedProduct = $db->single();
        
        // Decode HTML entities
        $updatedProduct['name'] = desanitize($updatedProduct['name']);
        $updatedProduct['description'] = desanitize($updatedProduct['description']);
        
        sendResponse('success', 'Product updated successfully', 200, $updatedProduct);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        sendResponse('error', 'Error updating product: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete product
 */
function deleteProduct() {
    global $db;
    
    // Check if ID is provided
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        sendResponse('error', 'Product ID is required', 400);
    }
    
    try {
        // Check if product exists
        $db->query("SELECT image_main FROM products WHERE id = :id");
        $db->bind(':id', $id);
        $product = $db->single();
        
        if (!$product) {
            sendResponse('error', 'Product not found', 404);
        }
        
        // Get additional images
        $db->query("SELECT image_path FROM product_images WHERE product_id = :product_id");
        $db->bind(':product_id', $id);
        $additionalImages = $db->resultSet();
        
        // Start transaction
        $db->beginTransaction();
        
        // Delete product
        $db->query("DELETE FROM products WHERE id = :id");
        $db->bind(':id', $id);
        $db->execute();
        
        $db->commit();
        
        // Delete image files (outside of transaction)
        if ($product['image_main'] && file_exists(UPLOADS_DIR . $product['image_main'])) {
            unlink(UPLOADS_DIR . $product['image_main']);
        }
        
        foreach ($additionalImages as $image) {
            if (file_exists(UPLOADS_DIR . $image['image_path'])) {
                unlink(UPLOADS_DIR . $image['image_path']);
            }
        }
        
        sendResponse('success', 'Product deleted successfully', 200);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        sendResponse('error', 'Error deleting product: ' . $e->getMessage(), 500);
    }
}

/**
 * Send JSON response
 * 
 * @param string $status - Response status (success or error)
 * @param string $message - Response message
 * @param int $code - HTTP status code
 * @param mixed $data - Response data
 */
function sendResponse($status, $message, $code = 200, $data = null) {
    http_response_code($code);
    
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}
