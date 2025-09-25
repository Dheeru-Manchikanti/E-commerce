<?php

// Include necessary files
require_once '../includes/init.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Error handling function
function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get action
$action = isset($_GET['action']) ? $_GET['action'] : '';


switch ($action) {
    case 'get':
        // Get single category details
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid category ID'
            ]);
            exit;
        }

        $categoryId = (int)$_GET['id'];
        
        // Query to get category details
        $db->query("SELECT c.*, p.name as parent_name 
                  FROM categories c 
                  LEFT JOIN categories p ON c.parent_id = p.id 
                  WHERE c.id = :id");
        $db->bind(':id', $categoryId);
        $category = $db->single();
        
        if (!$category) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Category not found'
            ]);
            exit;
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $category
        ]);
        break;
        
    case 'list':
        // List all categories
        $db->query("SELECT c.*, p.name as parent_name 
                  FROM categories c 
                  LEFT JOIN categories p ON c.parent_id = p.id 
                  ORDER BY COALESCE(c.parent_id, c.id), c.name");
        $categories = $db->resultSet();
        
        echo json_encode([
            'status' => 'success',
            'data' => $categories
        ]);
        break;
        
    case 'update':
        // Update category
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            exit;
        }
        
        // Get and sanitize form data
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'active';
        
        // Validation
        if (empty($name)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Category name is required'
            ]);
            exit;
        }
        
        if ($id <= 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid category ID'
            ]);
            exit;
        }
        
        // Check if category exists
        $db->query("SELECT id FROM categories WHERE id = :id");
        $db->bind(':id', $id);
        $categoryExists = $db->single();
        if (!$categoryExists) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Category not found'
            ]);
            exit;
        }
        

        if ($parent_id == $id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'A category cannot be its own parent'
            ]);
            exit;
        }
        

        if ($parent_id !== null) {
            $db->query("SELECT id FROM categories WHERE id = :id");
            $db->bind(':id', $parent_id);
            $parentExists = $db->single();
            if (!$parentExists) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Selected parent category does not exist'
                ]);
                exit;
            }
        }
        
        try {
            // Update the category
            $sql = "UPDATE categories SET 
                   name = :name, 
                   description = :description, 
                   parent_id = " . ($parent_id ? ":parent_id" : "NULL") . ", 
                   status = :status, 
                   updated_at = NOW() 
                   WHERE id = :id";
            
            $db->query($sql);
            $db->bind(':name', $name);
            $db->bind(':description', $description);
            if ($parent_id) {
                $db->bind(':parent_id', $parent_id);
            }
            $db->bind(':status', $status);
            $db->bind(':id', $id);
            
            if ($db->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Category updated successfully'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to update category'
                ]);
            }
        } catch (Exception $e) {
            error_log('Category API Error (update): ' . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'delete':
        // Delete category
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid category ID'
            ]);
            exit;
        }
        
        $categoryId = (int)$_POST['id'];
        
        try {
            // Check if there are subcategories
            $db->query("SELECT COUNT(*) AS count FROM categories WHERE parent_id = :id");
            $db->bind(':id', $categoryId);
            $result = $db->single();
            
            if ($result['count'] > 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Cannot delete category with subcategories. Please delete or reassign subcategories first.'
                ]);
                exit;
            }
            
            // Check if there are products in this category
            $db->query("SELECT COUNT(*) AS count FROM product_categories WHERE category_id = :id");
            $db->bind(':id', $categoryId);
            $result = $db->single();
            
            if ($result['count'] > 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Cannot delete category with products. Please remove products from this category first.'
                ]);
                exit;
            }
            
            // Delete the category
            $db->query("DELETE FROM categories WHERE id = :id");
            $db->bind(':id', $categoryId);
            
            if ($db->execute()) {
                // Reset auto-increment to reuse deleted IDs
                resetAutoIncrementForReuse('categories');
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Category deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to delete category'
                ]);
            }
        } catch (Exception $e) {
            error_log('Category API Error (delete): ' . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'create':
        // Create a new category
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            exit;
        }
        
        // Get and sanitize form data
        $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'active';
        
        // Validation
        if (empty($name)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Category name is required'
            ]);
            exit;
        }
        
        // Check if parent category exists if specified
        if ($parent_id !== null) {
            $db->query("SELECT id FROM categories WHERE id = :id");
            $db->bind(':id', $parent_id);
            $parentExists = $db->single();
            if (!$parentExists) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Selected parent category does not exist'
                ]);
                exit;
            }
        }
        
        try {
            // Reset auto-increment to reuse deleted IDs
            resetAutoIncrementForReuse('categories');
            
            // Insert the category
            $sql = "INSERT INTO categories (name, description, parent_id, status, created_at, updated_at) 
                   VALUES (:name, :description, " . ($parent_id ? ":parent_id" : "NULL") . ", :status, NOW(), NOW())";
            
            $db->query($sql);
            $db->bind(':name', $name);
            $db->bind(':description', $description);
            if ($parent_id) {
                $db->bind(':parent_id', $parent_id);
            }
            $db->bind(':status', $status);
            
                                if ($db->execute()) {
                                    $newCategoryId = $db->lastInsertId('categories_id_seq');
                                    echo json_encode([
                                        'status' => 'success',
                                        'message' => 'Category created successfully',
                                        'id' => $newCategoryId
                                    ]);            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to create category'
                ]);
            }
        } catch (Exception $e) {
            error_log('Category API Error (create): ' . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
}
