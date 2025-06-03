<?php
// Shopping cart page
require_once 'includes/init.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $productId = (int)$_GET['id'];
    
    if ($action === 'remove' && array_key_exists($productId, $_SESSION['cart'])) {
        // Remove product from cart
        unset($_SESSION['cart'][$productId]);
        setFlashMessage('cart_msg', 'Product removed from cart successfully!', 'success');
        header('Location: cart.php');
        exit;
    } elseif ($action === 'update' && isset($_POST['quantity']) && array_key_exists($productId, $_SESSION['cart'])) {
        // Update product quantity
        $quantity = (int)$_POST['quantity'];
        if ($quantity > 0) {
            $_SESSION['cart'][$productId]['quantity'] = $quantity;
            setFlashMessage('cart_msg', 'Cart updated successfully!', 'success');
        } elseif ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
            setFlashMessage('cart_msg', 'Product removed from cart!', 'success');
        }
        header('Location: cart.php');
        exit;
    }
}

// Initialize cart totals
$subtotal = 0;
$totalItems = 0;

// Get products in cart if cart is not empty
$cartProducts = [];
if (!empty($_SESSION['cart'])) {
    // Create placeholders for SQL query
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    // Get products details from database
    $sql = "SELECT * FROM products WHERE id IN ($placeholders) AND status = 'active'";
    $db->query($sql);
    

    $paramIndex = 1;
    foreach ($productIds as $id) {
        $db->bind($paramIndex, $id);
        $paramIndex++;
    }
    
    $results = $db->resultSet();
    

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
                'original_price' => $product['price'],
                'sale_price' => $product['sale_price'],
                'quantity' => $quantity,
                'image_main' => $product['image_main'],
                'stock_quantity' => $product['stock_quantity'],
                'total' => $itemTotal
            ];
            
            // Update totals
            $subtotal += $itemTotal;
            $totalItems += $quantity;
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
$pageTitle = 'Shopping Cart';
$currentPage = 'cart';

// Include header
include 'includes/public_header.php';
?>


<nav aria-label="breadcrumb" class="mt-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
    </ol>
</nav>

<!-- Cart Section -->
<section class="mb-5">
    <div class="container">
        <h1 class="mb-4">Your Shopping Cart</h1>
        
        <?php if (empty($cartProducts)): ?>
            <div class="alert alert-info">
                <p>Your shopping cart is empty.</p>
                <a href="products.php" class="btn btn-primary mt-3">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <!-- Cart Items -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col" width="50%">Product</th>
                                        <th scope="col" width="15%">Price</th>
                                        <th scope="col" width="15%">Quantity</th>
                                        <th scope="col" width="15%">Total</th>
                                        <th scope="col" width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartProducts as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0">
                                                        <?php if (!empty($item['image_main'])): ?>
                                                            <img src="uploads/<?php echo htmlspecialchars($item['image_main']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail" style="width: 80px;">
                                                        <?php else: ?>
                                                            <img src="assets/images/placeholder.jpg" alt="Placeholder" class="img-thumbnail" style="width: 80px;">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h5>
                                                            <a href="product.php?id=<?php echo $item['id']; ?>">
                                                                <?php echo htmlspecialchars($item['name']); ?>
                                                            </a>
                                                        </h5>
                                                        <?php if ($item['stock_quantity'] < $item['quantity']): ?>
                                                            <p class="text-danger">Only <?php echo $item['stock_quantity']; ?> available</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['sale_price'])): ?>
                                                    <span class="product-price sale-price"><?php echo formatPrice($item['price']); ?></span>
                                                    <br>
                                                    <small class="original-price"><?php echo formatPrice($item['original_price']); ?></small>
                                                <?php else: ?>
                                                    <span class="product-price"><?php echo formatPrice($item['price']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form action="cart.php?action=update&id=<?php echo $item['id']; ?>" method="post">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>">
                                                        <button class="btn btn-outline-secondary" type="submit">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td><?php echo formatPrice($item['total']); ?></td>
                                            <td>
                                                <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" class="text-danger" onclick="return confirm('Are you sure you want to remove this item?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Continue Shopping Button -->
                    <div class="d-flex justify-content-between mb-4">
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                        </a>
                        <a href="cart.php" class="btn btn-outline-secondary" onclick="updateAllQuantities()">
                            <i class="fas fa-sync-alt me-2"></i> Update Cart
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Order Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
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
                                <a href="checkout.php" class="btn btn-primary">
                                    Proceed to Checkout
                                </a>
                            </div>
                            <div class="mt-3">
                                <div class="alert alert-secondary small">
                                    <i class="fas fa-truck me-2"></i> Free shipping on orders over $50
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
