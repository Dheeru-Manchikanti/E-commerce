<?php
session_start();

// Checkout page
require_once 'includes/init.php';

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Initialize checkout variables
$errors = [];
$customer = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'country' => ''
];

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$savedAddresses = [];

// If user is logged in, get their information and addresses
if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    

    $db->query("SELECT * FROM users WHERE id = :id");
    $db->bind(':id', $userId);
    $user = $db->single();
    
    if ($user) {
        $customer['first_name'] = !empty($user['first_name']) ? $user['first_name'] : 'Will';
        $customer['last_name'] = !empty($user['last_name']) ? $user['last_name'] : 'Smith';
        $customer['email'] = !empty($user['email']) ? $user['email'] : $_SESSION['user_email'] ?? '';
        $customer['phone'] = !empty($user['phone']) ? $user['phone'] : '9100638734';
        
        // Get saved addresses
        $db->query("SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC");
        $db->bind(':user_id', $userId);
        $savedAddresses = $db->resultSet();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['use_saved_address']) && !empty($_POST['saved_address_id']) && $isLoggedIn) {
        $savedAddressId = (int)$_POST['saved_address_id'];
        $db->query("SELECT * FROM user_addresses WHERE id = :id AND user_id = :user_id");
        $db->bind(':id', $savedAddressId);
        $db->bind(':user_id', $userId);
        $selectedAddress = $db->single();
        
        if ($selectedAddress) {
            // Use saved address
            $customer['address'] = $selectedAddress['address'];
            $customer['city'] = $selectedAddress['city'];
            $customer['state'] = $selectedAddress['state'];
            $customer['postal_code'] = $selectedAddress['postal_code'];
            $customer['country'] = $selectedAddress['country'];
        }
    }
    
    // Validate customer information
    $customer['first_name'] = sanitize($_POST['first_name']);
    if (empty($customer['first_name'])) {
        $errors['first_name'] = 'First name is required';
    }
    
    $customer['last_name'] = sanitize($_POST['last_name']);
    if (empty($customer['last_name'])) {
        $errors['last_name'] = 'Last name is required';
    }
    
    $customer['email'] = sanitize($_POST['email']);
    if (empty($customer['email']) || !filter_var($customer['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid email is required';
    }
    
    $customer['phone'] = sanitize($_POST['phone']);
    if (empty($customer['phone'])) {
        $errors['phone'] = 'Phone number is required';
    }
    
    $customer['address'] = sanitize($_POST['address']);
    if (empty($customer['address'])) {
        $errors['address'] = 'Address is required';
    }
    
    $customer['city'] = sanitize($_POST['city']);
    if (empty($customer['city'])) {
        $errors['city'] = 'City is required';
    }
    
    $customer['state'] = sanitize($_POST['state']);
    if (empty($customer['state'])) {
        $errors['state'] = 'State/Province is required';
    }
    
    $customer['postal_code'] = sanitize($_POST['postal_code']);
    if (empty($customer['postal_code'])) {
        $errors['postal_code'] = 'Postal code is required';
    }
    
    $customer['country'] = sanitize($_POST['country']);
    if (empty($customer['country'])) {
        $errors['country'] = 'Country is required';
    }
    
    $payment_method = sanitize($_POST['payment_method']);
    if (empty($payment_method) || !in_array($payment_method, ['credit_card', 'paypal', 'bank_transfer'])) {
        $errors['payment_method'] = 'Valid payment method is required';
    }
    
    // If no errors, process order
    if (empty($errors)) {
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Check if customer exists
            $sql = "SELECT * FROM customers WHERE email = :email";
            $db->query($sql);
            $db->bind(':email', $customer['email']);
            $existingCustomer = $db->single();
            
            if ($existingCustomer) {
                // Use existing customer
                $customerId = $existingCustomer['id'];
                
                // Update customer information
                $sql = "UPDATE customers SET 
                        first_name = :first_name, 
                        last_name = :last_name, 
                        phone = :phone, 
                        address = :address, 
                        city = :city, 
                        state = :state, 
                        postal_code = :postal_code, 
                        country = :country 
                        WHERE id = :id";
                $db->query($sql);
                $db->bind(':first_name', $customer['first_name']);
                $db->bind(':last_name', $customer['last_name']);
                $db->bind(':phone', $customer['phone']);
                $db->bind(':address', $customer['address']);
                $db->bind(':city', $customer['city']);
                $db->bind(':state', $customer['state']);
                $db->bind(':postal_code', $customer['postal_code']);
                $db->bind(':country', $customer['country']);
                $db->bind(':id', $customerId);
                $db->execute();
            } else {
                // Insert new customer
                $sql = "INSERT INTO customers (first_name, last_name, email, phone, address, city, state, postal_code, country)
                        VALUES (:first_name, :last_name, :email, :phone, :address, :city, :state, :postal_code, :country)";
                $db->query($sql);
                $db->bind(':first_name', $customer['first_name']);
                $db->bind(':last_name', $customer['last_name']);
                $db->bind(':email', $customer['email']);
                $db->bind(':phone', $customer['phone']);
                $db->bind(':address', $customer['address']);
                $db->bind(':city', $customer['city']);
                $db->bind(':state', $customer['state']);
                $db->bind(':postal_code', $customer['postal_code']);
                $db->bind(':country', $customer['country']);
                $db->execute();
                
                $customerId = $db->lastInsertId('customers_id_seq');
            }
            
            // Prepare order data
            $orderNumber = generateOrderNumber();
            $shippingAddress = $customer['address'] . ', ' . $customer['city'] . ', ' . $customer['state'] . ' ' . $customer['postal_code'] . ', ' . $customer['country'];
            $billingAddress = $shippingAddress; // Using same address for billing in this example
            
            // Calculate totals
            $totalAmount = 0;
            $productIds = array_keys($_SESSION['cart']);
            
            if (!empty($productIds)) {
                // Create placeholders for SQL query
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                
                // Get products from database
                $sql = "SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'";
                $db->query($sql);
                
                // Bind product IDs
                $paramIndex = 1;
                foreach ($productIds as $id) {
                    $db->bind($paramIndex, $id);
                    $paramIndex++;
                }
                
                $products = $db->resultSet();
                
                // Organize products by ID for easy access
                $productsById = [];
                foreach ($products as $product) {
                    $productsById[$product['id']] = $product;
                }
                
                // Calculate total amount
                foreach ($productIds as $productId) {
                    if (isset($productsById[$productId]) && isset($_SESSION['cart'][$productId])) {
                        $product = $productsById[$productId];
                        $quantity = $_SESSION['cart'][$productId]['quantity'];
                        $price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
                        $totalAmount += ($price * $quantity);
                    }
                }
                
                // Add shipping cost if applicable
                if ($totalAmount < 50) {
                    $totalAmount += 5.99;
                }
                
                // Create order - associate with user account if logged in
                $sql = "INSERT INTO orders (customer_id, user_id, order_number, total_amount, status, shipping_address, billing_address, payment_method)
                        VALUES (:customer_id, :user_id, :order_number, :total_amount, 'pending', :shipping_address, :billing_address, :payment_method)";
                $db->query($sql);
                $db->bind(':customer_id', $customerId);
                $db->bind(':user_id', $isLoggedIn ? $userId : null);
                $db->bind(':order_number', $orderNumber);
                $db->bind(':total_amount', $totalAmount);
                $db->bind(':shipping_address', $shippingAddress);
                $db->bind(':billing_address', $billingAddress);
                $db->bind(':payment_method', $payment_method);
                $db->execute();
                
                $orderId = $db->lastInsertId('orders_id_seq');
                
                // Create order items and update inventory
                foreach ($productIds as $productId) {
                    if (isset($productsById[$productId]) && isset($_SESSION['cart'][$productId])) {
                        $product = $productsById[$productId];
                        $quantity = $_SESSION['cart'][$productId]['quantity'];
                        $price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
                        
                        // Check inventory
                        if ($quantity > $product['stock_quantity']) {
                            throw new Exception('Not enough stock for ' . $product['name']);
                        }
                        
                        // Add order item
                        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                                VALUES (:order_id, :product_id, :quantity, :price)";
                        $db->query($sql);
                        $db->bind(':order_id', $orderId);
                        $db->bind(':product_id', $productId);
                        $db->bind(':quantity', $quantity);
                        $db->bind(':price', $price);
                        $db->execute();
                        
                        // Update inventory
                        $newStock = $product['stock_quantity'] - $quantity;
                        $sql = "UPDATE products SET stock_quantity = :stock WHERE id = :id";
                        $db->query($sql);
                        $db->bind(':stock', $newStock);
                        $db->bind(':id', $productId);
                        $db->execute();
                    }
                }
                
                // Commit transaction
                $db->commit();
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                // Store order ID in session for confirmation page
                $_SESSION['last_order_id'] = $orderId;
                $_SESSION['last_order_number'] = $orderNumber;
                
                // If user is logged in and requested to save address
                if ($isLoggedIn && isset($_POST['save_address'])) {
                    // Check if this address already exists
                    $db->query("SELECT id FROM user_addresses WHERE 
                                user_id = :user_id AND 
                                address = :address AND 
                                city = :city AND 
                                state = :state AND 
                                postal_code = :postal_code AND 
                                country = :country");
                    $db->bind(':user_id', $userId);
                    $db->bind(':address', $customer['address']);
                    $db->bind(':city', $customer['city']);
                    $db->bind(':state', $customer['state']);
                    $db->bind(':postal_code', $customer['postal_code']);
                    $db->bind(':country', $customer['country']);
                    $existingAddress = $db->single();
                    
                    if (!$existingAddress) {
                        // Count user addresses to determine if this is the first one (default)
                        $db->query("SELECT COUNT(*) as address_count FROM user_addresses WHERE user_id = :user_id");
                        $db->bind(':user_id', $userId);
                        $addressCount = $db->single()['address_count'];
                        $isDefault = ($addressCount == 0) ? 1 : 0;
                        
                        // Save the address
                        $db->query("INSERT INTO user_addresses 
                                    (user_id, address_type, address, city, state, postal_code, country, is_default) 
                                    VALUES 
                                    (:user_id, 'both', :address, :city, :state, :postal_code, :country, :is_default)");
                        $db->bind(':user_id', $userId);
                        $db->bind(':address', $customer['address']);
                        $db->bind(':city', $customer['city']);
                        $db->bind(':state', $customer['state']);
                        $db->bind(':postal_code', $customer['postal_code']);
                        $db->bind(':country', $customer['country']);
                        $db->bind(':is_default', $isDefault);
                        $db->execute();
                    }
                }
                
                // Redirect to confirmation page
                header('Location: order-confirmation.php');
                exit;
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $errors['general'] = 'Order processing failed: ' . $e->getMessage();
        }
    }
}

// Get cart products for display
$cartProducts = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
    // Create placeholders for SQL query
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    // Get products details from database
    $sql = "SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'";
    $db->query($sql);
    
    // Bind product IDs
    $paramIndex = 1;
    foreach ($productIds as $id) {
        $db->bind($paramIndex, $id);
        $paramIndex++;
    }
    
    $results = $db->resultSet();
    
    // Organize products and calculate totals
    foreach ($results as $product) {
        $productId = $product['id'];
        
        if (isset($_SESSION['cart'][$productId])) {
            $quantity = $_SESSION['cart'][$productId]['quantity'];
            $price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
            $itemTotal = $price * $quantity;
            
            // Add to cart products array
            $cartProducts[] = [
                'id' => $productId,
                'name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'total' => $itemTotal
            ];
            
            // Update total
            $subtotal += $itemTotal;
        }
    }
}

// Calculate shipping
$shipping = 0;
if ($subtotal > 0 && $subtotal < 50) {
    $shipping = 5.99;
}

// Calculate total
$total = $subtotal + $shipping;

// Set page title
$pageTitle = 'Checkout';
$currentPage = 'checkout';

// Include header
include 'includes/public_header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mt-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="cart.php">Shopping Cart</a></li>
        <li class="breadcrumb-item active" aria-current="page">Checkout</li>
    </ol>
</nav>

<!-- Checkout Section -->
<section class="checkout-section mb-5">
    <div class="container">
        <h1 class="mb-4">Checkout</h1>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger">
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>
        
        <form action="checkout.php" method="post">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Customer Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Customer Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($isLoggedIn && !empty($savedAddresses)): ?>
                            <!-- Saved Addresses Selection for logged-in users -->
                            <div class="mb-4">
                                <label class="form-label">Your Saved Addresses</label>
                                <select class="form-select" id="saved_address_id" name="saved_address_id">
                                    <option value="">-- Select an address --</option>
                                    <?php foreach ($savedAddresses as $address): ?>
                                        <option value="<?php echo $address['id']; ?>">
                                            <?php echo htmlspecialchars($address['address'] . ', ' . $address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code'] . ', ' . $address['country']); ?>
                                            <?php echo $address['is_default'] ? ' (Default)' : ''; ?>
                                            <?php echo $address['address_type'] !== 'both' ? ' (' . ucfirst($address['address_type']) . ')' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="d-grid mt-2">
                                    <button type="submit" name="use_saved_address" class="btn btn-outline-primary btn-sm">
                                        Use This Address
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <p class="small text-muted">Or fill in a new address below:</p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Name -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name*</label>
                                    <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo $customer['first_name']; ?>" required>
                                    <?php if (isset($errors['first_name'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['first_name']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name*</label>
                                    <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo $customer['last_name']; ?>" required>
                                    <?php if (isset($errors['last_name'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['last_name']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Contact -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email*</label>
                                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo $customer['email']; ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['email']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone*</label>
                                    <input type="tel" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo $customer['phone']; ?>" required>
                                    <?php if (isset($errors['phone'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['phone']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Address -->
                            <div class="mb-3">
                                <label for="address" class="form-label">Address*</label>
                                <input type="text" class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" id="address" name="address" value="<?php echo $customer['address']; ?>" required>
                                <?php if (isset($errors['address'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo $errors['address']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="city" class="form-label">City*</label>
                                    <input type="text" class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>" id="city" name="city" value="<?php echo $customer['city']; ?>" required>
                                    <?php if (isset($errors['city'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['city']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="state" class="form-label">State/Province*</label>
                                    <input type="text" class="form-control <?php echo isset($errors['state']) ? 'is-invalid' : ''; ?>" id="state" name="state" value="<?php echo $customer['state']; ?>" required>
                                    <?php if (isset($errors['state'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['state']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="postal_code" class="form-label">Postal/ZIP Code*</label>
                                    <input type="text" class="form-control <?php echo isset($errors['postal_code']) ? 'is-invalid' : ''; ?>" id="postal_code" name="postal_code" value="<?php echo $customer['postal_code']; ?>" required>
                                    <?php if (isset($errors['postal_code'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['postal_code']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="country" class="form-label">Country*</label>
                                    <input type="text" class="form-control <?php echo isset($errors['country']) ? 'is-invalid' : ''; ?>" id="country" name="country" value="<?php echo $customer['country']; ?>" required>
                                    <?php if (isset($errors['country'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo $errors['country']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isLoggedIn): ?>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="save_address" name="save_address" value="1">
                                        <label class="form-check-label" for="save_address">
                                            Save this address to my account
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Saved Addresses -->
                    <?php if ($isLoggedIn && !empty($savedAddresses)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Saved Addresses</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="saved_address" class="form-label">Select a saved address</label>
                                    <select class="form-select" id="saved_address" name="saved_address_id">
                                        <option value="">-- Select an address --</option>
                                        <?php foreach ($savedAddresses as $address): ?>
                                            <option value="<?php echo $address['id']; ?>" <?php echo (isset($customer['address']) && $customer['address'] == $address['address']) ? 'selected' : ''; ?>>
                                                <?php echo $address['address'] . ', ' . $address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code'] . ', ' . $address['country']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="use_saved_address" id="use_saved_address" value="1">
                                    <label class="form-check-label" for="use_saved_address">
                                        Use this address for shipping
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Payment Method -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" checked>
                                <label class="form-check-label" for="credit_card">
                                    <i class="fab fa-cc-visa me-2"></i>
                                    <i class="fab fa-cc-mastercard me-2"></i>
                                    <i class="fab fa-cc-amex me-2"></i>
                                    Credit Card
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                <label class="form-check-label" for="paypal">
                                    <i class="fab fa-paypal me-2"></i>
                                    PayPal
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="bank_transfer" value="bank_transfer">
                                <label class="form-check-label" for="bank_transfer">
                                    <i class="fas fa-university me-2"></i>
                                    Bank Transfer
                                </label>
                            </div>
                            
                            <?php if (isset($errors['payment_method'])): ?>
                                <div class="text-danger mt-2">
                                    <?php echo $errors['payment_method']; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="alert alert-info mt-3">
                                <small>
                                    <i class="fas fa-info-circle me-2"></i>
                                    This is a demo store. No real payments will be processed.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Order Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cartProducts as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo $item['quantity']; ?> x <?php echo formatPrice($item['price']); ?>
                                        </small>
                                    </div>
                                    <span><?php echo formatPrice($item['total']); ?></span>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal</span>
                                <span><?php echo formatPrice($subtotal); ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <span>Shipping</span>
                                <?php if ($shipping > 0): ?>
                                    <span><?php echo formatPrice($shipping); ?></span>
                                <?php else: ?>
                                    <span class="text-success">Free</span>
                                <?php endif; ?>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total</strong>
                                <strong><?php echo formatPrice($total); ?></strong>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="place_order" class="btn btn-primary">
                                    <i class="fas fa-check me-2"></i> Place Order
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Back to Cart Button -->
                    <div class="d-grid">
                        <a href="cart.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
