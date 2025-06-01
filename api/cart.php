<?php
// Cart API endpoint
require_once '../includes/init.php';

// Set headers for JSON response
header('Content-Type: application/json');

// CSRF protection for non-GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    // Check for CSRF token in header
    $headers = getallheaders();
    $token = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : null;
    
    // If token not in header, check POST data
    if (!$token && isset($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    }
    
    if (!verifyCSRFToken($token)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'CSRF token validation failed'
        ]);
        exit;
    }
}

// Initialize cart if not exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle different cart actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'add':
        // Add to cart
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid product ID'
                ]);
                exit;
            }
            
            $productId = (int)$_POST['product_id'];
            $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            
            if ($quantity <= 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Quantity must be greater than zero'
                ]);
                exit;
            }
            
            // Check if product exists and is active
            $sql = "SELECT * FROM products WHERE id = :id AND status = 'active'";
            $db->query($sql);
            $db->bind(':id', $productId);
            $product = $db->single();
            
            if (!$product) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product not found or is inactive'
                ]);
                exit;
            }
            
            // Check if product is in stock
            if ($quantity > $product['stock_quantity']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Not enough stock available. Only ' . $product['stock_quantity'] . ' items left.'
                ]);
                exit;
            }
            
            // Add or update product in cart
            if (isset($_SESSION['cart'][$productId])) {
                // Update quantity if product already in cart
                $_SESSION['cart'][$productId]['quantity'] += $quantity;
                
                // Make sure quantity doesn't exceed stock
                if ($_SESSION['cart'][$productId]['quantity'] > $product['stock_quantity']) {
                    $_SESSION['cart'][$productId]['quantity'] = $product['stock_quantity'];
                }
                
                $message = 'Cart updated successfully!';
            } else {
                // Add new product to cart
                $_SESSION['cart'][$productId] = [
                    'quantity' => $quantity
                ];
                
                $message = 'Product added to cart successfully!';
            }
            
            // Count total items in cart
            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'];
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => $message,
                'cartCount' => $cartCount
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }
        break;
        
    case 'update':
        // Update cart item quantity
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid product ID'
                ]);
                exit;
            }
            
            if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity']) || (int)$_POST['quantity'] < 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid quantity'
                ]);
                exit;
            }
            
            $productId = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            
            // Check if product exists in cart
            if (!isset($_SESSION['cart'][$productId])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product not found in cart'
                ]);
                exit;
            }
            
            // If quantity is 0, remove product from cart
            if ($quantity === 0) {
                unset($_SESSION['cart'][$productId]);
                $message = 'Product removed from cart';
            } else {
                // Check if product exists and is active
                $sql = "SELECT * FROM products WHERE id = :id AND status = 'active'";
                $db->query($sql);
                $db->bind(':id', $productId);
                $product = $db->single();
                
                if (!$product) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Product not found or is inactive'
                    ]);
                    exit;
                }
                
                // Check if product is in stock
                if ($quantity > $product['stock_quantity']) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Not enough stock available. Only ' . $product['stock_quantity'] . ' items left.'
                    ]);
                    exit;
                }
                
                // Update quantity
                $_SESSION['cart'][$productId]['quantity'] = $quantity;
                $message = 'Cart updated successfully';
            }
            
            // Count total items in cart
            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'];
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => $message,
                'cartCount' => $cartCount
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }
        break;
        
    case 'remove':
        // Remove item from cart
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid product ID'
                ]);
                exit;
            }
            
            $productId = (int)$_POST['product_id'];
            
            // Check if product exists in cart
            if (!isset($_SESSION['cart'][$productId])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Product not found in cart'
                ]);
                exit;
            }
            
            // Remove product from cart
            unset($_SESSION['cart'][$productId]);
            
            // Count total items in cart
            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'];
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Product removed from cart',
                'cartCount' => $cartCount
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }
        break;
        
    case 'clear':
        // Clear entire cart
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Empty the cart
            $_SESSION['cart'] = [];
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Cart cleared successfully',
                'cartCount' => 0
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
        }
        break;
        
    case 'get':
        // Get cart contents
        $cartItems = [];
        $subtotal = 0;
        $cartCount = 0;
        
        if (!empty($_SESSION['cart'])) {
            // Get product IDs from cart
            $productIds = array_keys($_SESSION['cart']);
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            
            // Get product details
            $sql = "SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'";
            $db->query($sql);
            
            // Bind product IDs
            $paramIndex = 1;
            foreach ($productIds as $id) {
                $db->bind($paramIndex, $id);
                $paramIndex++;
            }
            
            $products = $db->resultSet();
            
            // Process products
            foreach ($products as $product) {
                $productId = $product['id'];
                
                if (isset($_SESSION['cart'][$productId])) {
                    $quantity = $_SESSION['cart'][$productId]['quantity'];
                    $price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
                    $total = $price * $quantity;
                    
                    $cartItems[] = [
                        'id' => $productId,
                        'name' => $product['name'],
                        'price' => $price,
                        'original_price' => $product['price'],
                        'sale_price' => $product['sale_price'],
                        'quantity' => $quantity,
                        'image_main' => $product['image_main'],
                        'stock_quantity' => $product['stock_quantity'],
                        'total' => $total
                    ];
                    
                    $subtotal += $total;
                    $cartCount += $quantity;
                }
            }
        }
        
        // Calculate shipping (simplistic approach, would be more complex in a real system)
        $shipping = 0;
        if ($subtotal > 0 && $subtotal < 50) {
            $shipping = 5.99;
        }
        
        // Calculate total
        $total = $subtotal + $shipping;
        
        echo json_encode([
            'status' => 'success',
            'cartCount' => $cartCount,
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total,
            'formatted' => [
                'subtotal' => formatPrice($subtotal),
                'shipping' => $shipping > 0 ? formatPrice($shipping) : 'Free',
                'total' => formatPrice($total)
            ]
        ]);
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
}
