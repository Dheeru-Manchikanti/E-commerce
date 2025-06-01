<?php
// Order confirmation page
require_once 'includes/init.php';

// Check if order ID is available in session
if (!isset($_SESSION['last_order_id']) || !isset($_SESSION['last_order_number'])) {
    header('Location: index.php');
    exit;
}

$orderId = $_SESSION['last_order_id'];
$orderNumber = $_SESSION['last_order_number'];

// Get order details
$sql = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone 
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.id = :order_id";
$db->query($sql);
$db->bind(':order_id', $orderId);
$order = $db->single();

if (!$order) {
    // If order not found, redirect to home
    unset($_SESSION['last_order_id']);
    unset($_SESSION['last_order_number']);
    header('Location: index.php');
    exit;
}

// Get order items
$sql = "SELECT oi.*, p.name, p.image_main
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = :order_id";
$db->query($sql);
$db->bind(':order_id', $orderId);
$orderItems = $db->resultSet();

// Calculate totals
$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Shipping cost (simplified approach)
$shipping = 0;
if ($subtotal < 50) {
    $shipping = 5.99;
}

// Set page title
$pageTitle = 'Order Confirmation';

// Include header
include 'includes/public_header.php';

// Clear session order data after displaying the page
// This prevents refreshing the page from showing the same order again
unset($_SESSION['last_order_id']);
unset($_SESSION['last_order_number']);
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mt-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Order Confirmation</li>
    </ol>
</nav>

<!-- Order Confirmation Section -->
<section class="mb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Success Message -->
                <div class="alert alert-success text-center mb-4">
                    <h3><i class="fas fa-check-circle me-2"></i> Thank You for Your Order!</h3>
                    <p class="mb-0">Your order has been placed successfully.</p>
                </div>
                
                <!-- Order Details -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order Details</h5>
                        <span class="badge bg-primary"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                                <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6>Shipping Address</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6>Billing Address</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Product</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col" class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <?php if (!empty($item['image_main'])): ?>
                                                            <img src="uploads/<?php echo htmlspecialchars($item['image_main']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail" style="width: 50px;">
                                                        <?php else: ?>
                                                            <img src="assets/images/placeholder.jpg" alt="Placeholder" class="img-thumbnail" style="width: 50px;">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo formatPrice($item['price']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="text-end"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end">Subtotal</td>
                                        <td class="text-end"><?php echo formatPrice($subtotal); ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end">Shipping</td>
                                        <td class="text-end">
                                            <?php if ($shipping > 0): ?>
                                                <?php echo formatPrice($shipping); ?>
                                            <?php else: ?>
                                                <span class="text-success">Free</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end">Total</th>
                                        <th class="text-end"><?php echo formatPrice($order['total_amount']); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Next Steps -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">What's Next?</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                A confirmation email has been sent to <strong><?php echo htmlspecialchars($order['email']); ?></strong>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-box me-2 text-primary"></i>
                                Your order will be processed and shipped soon.
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-truck me-2 text-primary"></i>
                                You will receive a notification when your order ships.
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Back to Shopping Button -->
                <div class="text-center">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
