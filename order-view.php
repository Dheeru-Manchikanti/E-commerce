<?php
session_start();

require_once 'includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$userId = $_SESSION['user_id'];

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Order ID is required.', 'danger');
    header('Location: account.php#orders');
    exit();
}

$orderId = (int)$_GET['id'];

// Get order data
$db->query("SELECT o.*, c.first_name, c.last_name, c.email, c.phone
           FROM orders o 
           JOIN customers c ON o.customer_id = c.id
           WHERE o.id = :id AND o.user_id = :user_id");
$db->bind(':id', $orderId);
$db->bind(':user_id', $userId);
$order = $db->single();

if (!$order) {
    setFlashMessage('error', 'Order not found or you do not have permission to view it.', 'danger');
    header('Location: account.php#orders');
    exit();
}

// Get order items
$db->query("SELECT oi.*, p.name as product_name, p.sku, p.image_main
           FROM order_items oi
           JOIN products p ON oi.product_id = p.id
           WHERE oi.order_id = :order_id");
$db->bind(':order_id', $orderId);
$orderItems = $db->resultSet();

// Set page title
$pageTitle = 'Order #' . $order['order_number'];

// Include header
include('includes/public_header.php');
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="account.php">My Account</a></li>
            <li class="breadcrumb-item"><a href="account.php#orders">Orders</a></li>
            <li class="breadcrumb-item active" aria-current="page">Order #<?php echo htmlspecialchars($order['order_number']); ?></li>
        </ol>
    </nav>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h5 class="m-0 font-weight-bold">
                Order #<?php echo htmlspecialchars($order['order_number']); ?>
                <span class="badge <?php 
                    switch($order['status']) {
                        case 'pending':
                            echo 'bg-warning';
                            break;
                        case 'processing':
                            echo 'bg-info';
                            break;
                        case 'shipped':
                            echo 'bg-primary';
                            break;
                        case 'delivered':
                            echo 'bg-success';
                            break;
                        case 'cancelled':
                            echo 'bg-danger';
                            break;
                        default:
                            echo 'bg-secondary';
                    }
                ?>">
                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                </span>
            </h5>
            <div>
                <small class="text-muted">
                    Order Date: <?php echo date('F j, Y h:i A', strtotime($order['created_at'])); ?>
                </small>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Shipping Address</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Billing Address</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Order Items</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th width="10%">Price</th>
                                    <th width="10%">Quantity</th>
                                    <th width="15%">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $subtotal = 0; ?>
                                <?php foreach ($orderItems as $item): ?>
                                    <?php $itemTotal = $item['price'] * $item['quantity']; ?>
                                    <?php $subtotal += $itemTotal; ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['image_main']): ?>
                                                    <img src="uploads/<?php echo $item['image_main']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="text-center text-muted me-3" style="width: 50px; height: 50px; line-height: 50px; border: 1px solid #ddd;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                                    </a>
                                                    <?php if ($item['sku']): ?>
                                                        <div class="small text-muted">SKU: <?php echo htmlspecialchars($item['sku']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo formatPrice($item['price']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatPrice($itemTotal); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Subtotal:</th>
                                    <td><?php echo formatPrice($subtotal); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Shipping:</th>
                                    <td><?php echo formatPrice($order['total_amount'] - $subtotal); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($order['notes'])): ?>
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Order Notes</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="account.php#orders" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>
</div>

<?php include('includes/public_footer.php'); ?>
