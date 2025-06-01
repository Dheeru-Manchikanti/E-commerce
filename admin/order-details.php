<?php
// Check if user is logged in
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database and functions
require_once '../includes/init.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Order ID is required.', 'danger');
    header('Location: orders.php');
    exit();
}

$orderId = (int)$_GET['id'];

// Get order data
$db->query("SELECT o.*, c.first_name, c.last_name, c.email, c.phone, c.address, c.city, c.state, c.postal_code, c.country
           FROM orders o 
           JOIN customers c ON o.customer_id = c.id
           WHERE o.id = :id");
$db->bind(':id', $orderId);
$order = $db->single();

if (!$order) {
    setFlashMessage('error', 'Order not found.', 'danger');
    header('Location: orders.php');
    exit();
}

// Get order items
$db->query("SELECT oi.*, p.name as product_name, p.sku, p.image_main
           FROM order_items oi
           JOIN products p ON oi.product_id = p.id
           WHERE oi.order_id = :order_id");
$db->bind(':order_id', $orderId);
$orderItems = $db->resultSet();

// Process update status form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('error', 'Invalid form submission.', 'danger');
    } else {
        $newStatus = sanitize($_POST['status']);
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (in_array($newStatus, $validStatuses)) {
            $db->query("UPDATE orders SET status = :status WHERE id = :id");
            $db->bind(':status', $newStatus);
            $db->bind(':id', $orderId);
            $result = $db->execute();
            
            if ($result) {
                setFlashMessage('success', 'Order status updated successfully.', 'success');
                
                // Refresh order data
                $db->query("SELECT o.*, c.first_name, c.last_name, c.email, c.phone, c.address, c.city, c.state, c.postal_code, c.country
                           FROM orders o 
                           JOIN customers c ON o.customer_id = c.id
                           WHERE o.id = :id");
                $db->bind(':id', $orderId);
                $order = $db->single();
            } else {
                setFlashMessage('error', 'Failed to update order status.', 'danger');
            }
        } else {
            setFlashMessage('error', 'Invalid status value.', 'danger');
        }
    }
}

// Page title
$pageTitle = 'Order Details';

// Additional JS
$additionalJS = [
    '../assets/js/orders.js'
];
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Order Details</h1>
        <div>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <a href="javascript:window.print();" class="btn btn-info ml-2">
                <i class="fas fa-print"></i> Print Order
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
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
            </h6>
            <div>
                <small class="text-muted">
                    Order Date: <?php echo date('F j, Y h:i A', strtotime($order['created_at'])); ?>
                </small>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Customer Information</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></p>
                            <p class="mb-0"><strong>Customer ID:</strong> <?php echo $order['customer_id']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">Shipping Address</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="mb-0">Billing Address</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Items</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
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
                                                    <img src="<?php echo UPLOADS_URL . $item['image_main']; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="text-center text-muted me-3" style="width: 50px; height: 50px; line-height: 50px; border: 1px solid #ddd;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <a href="../admin/edit-product.php?id=<?php echo $item['product_id']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                                    </a>
                                                    <div class="small text-muted">ID: <?php echo $item['product_id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatPrice($item['price']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatPrice($itemTotal); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Subtotal:</th>
                                    <td><?php echo formatPrice($subtotal); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Shipping:</th>
                                    <td><?php echo formatPrice($order['total_amount'] - $subtotal); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Total:</th>
                                    <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Notes</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($order['notes'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No notes for this order.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Status</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="order-details.php?id=<?php echo $orderId; ?>" class="row g-3 align-items-center">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="col-md-4">
                            <select class="form-control" name="status">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                        </div>
                        <div class="col-md-5">
                            <p class="mb-0">
                                Current Status: 
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
                                <br>
                                <small class="text-muted">Last Updated: <?php echo date('F j, Y h:i A', strtotime($order['updated_at'])); ?></small>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
