<?php

session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit();
}


require_once '../includes/init.php';


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;


$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$filterDateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$filterDateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$filterOrderId = isset($_GET['order_id']) ? sanitize($_GET['order_id']) : '';


$conditions = [];
$params = [];

if (!empty($filterStatus)) {
    $conditions[] = "o.status = :status";
    $params[':status'] = $filterStatus;
}

if (!empty($filterDateFrom)) {
    $conditions[] = "DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $filterDateFrom;
}

if (!empty($filterDateTo)) {
    $conditions[] = "DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $filterDateTo;
}

if (!empty($filterOrderId)) {
    $conditions[] = "(o.id = :order_id OR o.order_number LIKE :order_number)";
    $params[':order_id'] = $filterOrderId;
    $params[':order_number'] = '%' . $filterOrderId . '%';
}


$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Count total orders with filters
$countQuery = "SELECT COUNT(*) as total FROM orders o $whereClause";
$db->query($countQuery);
foreach ($params as $param => $value) {
    $db->bind($param, $value);
}
$totalResult = $db->single();
$totalOrders = $totalResult['total'];

// Get orders for current page with filters
$ordersQuery = "SELECT o.*, c.first_name, c.last_name, c.email 
               FROM orders o 
               JOIN customers c ON o.customer_id = c.id 
               $whereClause
               ORDER BY o.created_at DESC 
               LIMIT :offset, :limit";
$db->query($ordersQuery);
foreach ($params as $param => $value) {
    $db->bind($param, $value);
}
$db->bind(':offset', $offset);
$db->bind(':limit', $itemsPerPage);
$orders = $db->resultSet();

// Generate pagination
$pagination = paginate($totalOrders, $itemsPerPage, $page, '?page=(:num)' . 
                      ($filterStatus ? '&status=' . urlencode($filterStatus) : '') . 
                      ($filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : '') . 
                      ($filterDateTo ? '&date_to=' . urlencode($filterDateTo) : '') . 
                      ($filterOrderId ? '&order_id=' . urlencode($filterOrderId) : ''));


$pageTitle = 'Orders';


$additionalJS = [
    '../assets/js/orders.js'
];
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Orders</h1>
        <a href="#" class="btn btn-success" id="exportOrders">
            <i class="fas fa-file-export"></i> Export to CSV
        </a>
    </div>
    
    <!-- Orders Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Filter Orders</h6>
            <button class="btn btn-sm btn-outline-secondary" type="button" id="toggleFilter">
                <i class="fas fa-filter"></i> Toggle Filter
            </button>
        </div>
        <div class="card-body" id="filterContainer" style="<?php echo !empty($filterStatus) || !empty($filterDateFrom) || !empty($filterDateTo) || !empty($filterOrderId) ? '' : 'display: none;'; ?>">
            <form method="get" action="orders.php">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="status">Order Status</label>
                        <select class="form-control" id="filterStatus" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $filterStatus === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $filterStatus === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $filterStatus === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_from">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $filterDateFrom; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="date_to">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $filterDateTo; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="order_id">Order #</label>
                        <input type="text" class="form-control" id="order_id" name="order_id" placeholder="Order ID or Number" value="<?php echo $filterOrderId; ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="orders.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Orders List</h6>
            <div>
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    Total Orders: <strong><?php echo $totalOrders; ?></strong>
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3 d-flex justify-content-end">
                <div class="pagination">
                    <?php if ($pagination['links']['prev']): ?>
                        <a href="<?php echo $pagination['links']['prev']; ?>" class="btn btn-sm btn-outline-primary">&laquo; Previous</a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" disabled>&laquo; Previous</button>
                    <?php endif; ?>
                    
                    <?php foreach ($pagination['links']['pages'] as $pageNum => $url): ?>
                        <?php if ($pageNum == $page): ?>
                            <a href="<?php echo $url; ?>" class="btn btn-sm btn-primary"><?php echo $pageNum; ?></a>
                        <?php else: ?>
                            <a href="<?php echo $url; ?>" class="btn btn-sm btn-outline-primary"><?php echo $pageNum; ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <?php if ($pagination['links']['next']): ?>
                        <a href="<?php echo $pagination['links']['next']; ?>" class="btn btn-sm btn-outline-primary">Next &raquo;</a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" disabled>Next &raquo;</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered" id="ordersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                        <div class="small text-muted">ID: <?php echo $order['id']; ?></div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                        <div class="small text-muted"><?php echo htmlspecialchars($order['email']); ?></div>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                        <div class="small text-muted"><?php echo date('h:i A', strtotime($order['created_at'])); ?></div>
                                    </td>
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
                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <button type="button" class="btn btn-sm btn-primary view-order" style = "display:none;" data-id="<?php echo $order['id']; ?>">
                                                <i class="fas fa-edit"></i> Quick View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Bottom pagination -->
            <div class="mt-3 d-flex justify-content-end">
                <div class="pagination">
                    <?php if ($pagination['links']['prev']): ?>
                        <a href="<?php echo $pagination['links']['prev']; ?>" class="btn btn-sm btn-outline-primary">&laquo; Previous</a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" disabled>&laquo; Previous</button>
                    <?php endif; ?>
                    
                    <?php foreach ($pagination['links']['pages'] as $pageNum => $url): ?>
                        <?php if ($pageNum == $page): ?>
                            <a href="<?php echo $url; ?>" class="btn btn-sm btn-primary"><?php echo $pageNum; ?></a>
                        <?php else: ?>
                            <a href="<?php echo $url; ?>" class="btn btn-sm btn-outline-primary"><?php echo $pageNum; ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <?php if ($pagination['links']['next']): ?>
                        <a href="<?php echo $pagination['links']['next']; ?>" class="btn btn-sm btn-outline-primary">Next &raquo;</a>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary" disabled>Next &raquo;</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1" aria-labelledby="viewOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewOrderModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Order Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Order ID:</th>
                                <td><span id="order_id"></span></td>
                            </tr>
                            <tr>
                                <th>Order Number:</th>
                                <td><span id="order_number"></span></td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td><span id="order_date"></span></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td><span id="order_status"></span></td>
                            </tr>
                            <tr>
                                <th>Total:</th>
                                <td><span id="order_total"></span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>Name:</th>
                                <td><span id="customer_name"></span></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><span id="customer_email"></span></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><span id="customer_phone"></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>Shipping Address</h6>
                        <div class="p-3 border rounded" id="shipping_address"></div>
                    </div>
                    <div class="col-md-6">
                        <h6>Billing Address</h6>
                        <div class="p-3 border rounded" id="billing_address"></div>
                    </div>
                </div>
                
                <h6>Order Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="orderItemsContainer">
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    <h6>Update Order Status</h6>
                    <div class="row">
                        <div class="col-md-8">
                            <input type="hidden" id="update_order_id">
                            <select class="form-control" id="update_status">
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" id="updateOrderStatus" class="btn btn-primary">Update Status</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="viewDetailedOrder" class="btn btn-info">View Full Details</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
