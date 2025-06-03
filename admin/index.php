<?php

session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit();
}


require_once '../includes/init.php';



$db->query("SELECT COUNT(*) as total_products FROM products");
$productsResult = $db->single();
$totalProducts = $productsResult['total_products'];


$db->query("SELECT COUNT(*) as active_products FROM products WHERE status = 'active'");
$activeProductsResult = $db->single();
$activeProducts = $activeProductsResult['active_products'];


$db->query("SELECT COUNT(*) as total_categories FROM categories");
$categoriesResult = $db->single();
$totalCategories = $categoriesResult['total_categories'];

// Orders count
$db->query("SELECT COUNT(*) as total_orders FROM orders");
$ordersResult = $db->single();
$totalOrders = $ordersResult['total_orders'];

// Pending orders count
$db->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
$pendingOrdersResult = $db->single();
$pendingOrders = $pendingOrdersResult['pending_orders'];

// Recent orders
$db->query("SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at, 
            c.first_name, c.last_name, c.email 
            FROM orders o 
            JOIN customers c ON o.customer_id = c.id 
            ORDER BY o.created_at DESC LIMIT 5");
$recentOrders = $db->resultSet();

// Low stock products
$db->query("SELECT id, name, stock_quantity FROM products WHERE stock_quantity <= 5 AND status = 'active' ORDER BY stock_quantity ASC LIMIT 5");
$lowStockProducts = $db->resultSet();

// Page title
$pageTitle = 'Dashboard';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>
    
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Products</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalProducts; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Products</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $activeProducts; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Categories</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalCategories; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pendingOrders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                    <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentOrders) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                            <td><?php echo formatPrice($order['total_amount']); ?></td>
                                            <td>
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
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No recent orders found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Low Stock Products -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Low Stock Products</h6>
                </div>
                <div class="card-body">
                    <?php if (count($lowStockProducts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $product): ?>
                                        <tr>
                                            <td>
                                                <a href="edit-product.php?id=<?php echo $product['id']; ?>">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $product['stock_quantity']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">All products have sufficient stock.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Status Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Status Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Orders</span>
                        <span class="font-weight-bold"><?php echo $totalOrders; ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Pending</span>
                        <span class="text-warning font-weight-bold"><?php echo $pendingOrders; ?></span>
                    </div>
                    <?php
                    // Get counts for other statuses
                    $db->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
                    $statusCounts = $db->resultSet();
                    $statusData = [];
                    foreach ($statusCounts as $status) {
                        if ($status['status'] !== 'pending') {
                            $statusData[$status['status']] = $status['count'];
                        }
                    }
                    
                    // Display other statuses
                    $statusColors = [
                        'processing' => 'text-info',
                        'shipped' => 'text-primary',
                        'delivered' => 'text-success',
                        'cancelled' => 'text-danger'
                    ];
                    
                    foreach ($statusColors as $status => $colorClass) {
                        $count = isset($statusData[$status]) ? $statusData[$status] : 0;
                        echo '<div class="d-flex justify-content-between mb-2">';
                        echo '<span>' . ucfirst($status) . '</span>';
                        echo '<span class="' . $colorClass . ' font-weight-bold">' . $count . '</span>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
