<?php
// Start session
session_start();

// Include database and functions
require_once 'includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$userId = $_SESSION['user_id'];

// Get user data from database
try {
    $db->query("SELECT id, email, first_name, last_name, phone FROM users WHERE id = ?");
    $db->bind(1, $userId);
    $user = $db->single();
    
    if (!$user) {
        // If no user found, create empty array
        $user = [
            'id' => $userId,
            'email' => $_SESSION['user_email'] ?? '',
            'first_name' => '',
            'last_name' => '',
            'phone' => ''
        ];
    }
} catch (Exception $e) {
    // On error, use session data as fallback
    $user = [
        'id' => $userId,
        'email' => $_SESSION['user_email'] ?? '',
        'first_name' => '',
        'last_name' => '',
        'phone' => ''
    ];
}

// Add a dev-only debug feature that can be enabled when needed
$debugMode = false;
if ($debugMode) {
    echo '<div style="background: #f5f5f5; border: 1px solid #ddd; padding: 10px; margin: 10px 0; font-family: monospace;">';
    echo '<strong>DEBUG - User Data:</strong><br>';
    echo '<pre>' . print_r($user, true) . '</pre>';
    echo '<strong>DEBUG - Session Data:</strong><br>';
    echo '<pre>' . print_r($_SESSION, true) . '</pre>';
    echo '</div>';
}

// Get user addresses
$db->query("SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC");
$db->bind(':user_id', $userId);
$addresses = $db->resultSet();

// Get recent orders
$db->query("SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at
           FROM orders o
           WHERE o.user_id = :user_id
           ORDER BY o.created_at DESC 
           LIMIT 5");
$db->bind(':user_id', $userId);
$recentOrders = $db->resultSet();

// Process account update form
$updateSuccess = '';
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $updateError = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $phone = sanitize($_POST['phone'] ?? '');
        
        // Validate form data
        $errors = [];
        
        if (empty($first_name)) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($last_name)) {
            $errors[] = 'Last name is required.';
        }
        
        if (empty($errors)) {
            // Update user data
            $db->query("UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone WHERE id = :id");
            $db->bind(':first_name', $first_name);
            $db->bind(':last_name', $last_name);
            $db->bind(':phone', $phone);
            $db->bind(':id', $userId);
            
            $result = $db->execute();
            
            if ($result) {
                // Update session variable
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                
                // Update user variable for display
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;
                $user['phone'] = $phone;
                
                $updateSuccess = 'Profile updated successfully.';
            } else {
                $updateError = 'Failed to update profile. Please try again.';
            }
        } else {
            $updateError = implode('<br>', $errors);
        }
    }
}

// Process password update form
$passwordSuccess = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $passwordError = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate form data
        $errors = [];
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors[] = 'New password is required.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        }
        
        // Validate password confirmation
        if ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match.';
        }
        
        if (empty($errors)) {
            // Hash new password
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update user password
            $db->query("UPDATE users SET password = :password WHERE id = :id");
            $db->bind(':password', $hashedPassword);
            $db->bind(':id', $userId);
            
            $result = $db->execute();
            
            if ($result) {
                $passwordSuccess = 'Password updated successfully.';
            } else {
                $passwordError = 'Failed to update password. Please try again.';
            }
        } else {
            $passwordError = implode('<br>', $errors);
        }
    }
}

// Set page title
$pageTitle = 'My Account';

// Include header
include('includes/public_header.php');
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar / Navigation -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">My Account</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#dashboard" class="list-group-item list-group-item-action active" data-bs-toggle="list">Dashboard</a>
                    <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">My Orders</a>
                    <a href="#addresses" class="list-group-item list-group-item-action" data-bs-toggle="list">My Addresses</a>
                    <a href="profile.php" class="list-group-item list-group-item-action">My Profile <span class="badge bg-success">New</span></a>
                    <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">Change Password</a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- Dashboard Tab -->
                <div class="tab-pane fade show active" id="dashboard">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Dashboard</h5>
                        </div>
                        <div class="card-body">
                            <h4 style="color: #4285f4;">Hello, <?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?>!</h4>
                            <p>From your account dashboard you can view your recent orders, manage your shipping and billing addresses, and edit your password and account details.</p>
                            
                            <?php if (count($recentOrders) > 0): ?>
                                <h5 class="mt-4">Recent Orders</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Total</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                    <td>
                                                        <span class="badge <?php 
                                                            switch($order['status']) {
                                                                case 'pending': echo 'bg-warning'; break;
                                                                case 'processing': echo 'bg-info'; break;
                                                                case 'shipped': echo 'bg-primary'; break;
                                                                case 'delivered': echo 'bg-success'; break;
                                                                case 'cancelled': echo 'bg-danger'; break;
                                                                default: echo 'bg-secondary';
                                                            }
                                                        ?>">
                                                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                                                    <td>
                                                        <a href="order-view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end">
                                    <a href="#orders" class="btn btn-outline-primary" data-bs-toggle="list">View All Orders</a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mt-3">
                                    <p class="mb-0">You haven't placed any orders yet. <a href="products.php">Start shopping</a> to place your first order!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">My Orders</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($recentOrders) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th>Total</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                    <td>
                                                        <span class="badge <?php 
                                                            switch($order['status']) {
                                                                case 'pending': echo 'bg-warning'; break;
                                                                case 'processing': echo 'bg-info'; break;
                                                                case 'shipped': echo 'bg-primary'; break;
                                                                case 'delivered': echo 'bg-success'; break;
                                                                case 'cancelled': echo 'bg-danger'; break;
                                                                default: echo 'bg-secondary';
                                                            }
                                                        ?>">
                                                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                                                    <td>
                                                        <a href="order-view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <p class="mb-0">You haven't placed any orders yet. <a href="products.php">Start shopping</a> to place your first order!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Addresses Tab -->
                <div class="tab-pane fade" id="addresses">
                    <div class="card shadow mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">My Addresses</h5>
                            <a href="address-edit.php" class="btn btn-sm btn-primary">Add New Address</a>
                        </div>
                        <div class="card-body">
                            <?php if (count($addresses) > 0): ?>
                                <div class="row">
                                    <?php foreach ($addresses as $address): ?>
                                        <div class="col-lg-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-header d-flex justify-content-between">
                                                    <h6 class="mb-0">
                                                        <?php echo ucfirst(htmlspecialchars($address['address_type'])); ?> Address
                                                        <?php if ($address['is_default']): ?>
                                                            <span class="badge bg-primary ms-2">Default</span>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <div>
                                                        <a href="address-edit.php?id=<?php echo $address['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($address['address'])); ?><br>
                                                    <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                                    <?php echo htmlspecialchars($address['country']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <p class="mb-0">You haven't added any addresses yet. <a href="address-edit.php">Add an address</a> to make checkout faster!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Tab -->
                <div class="tab-pane fade" id="profile">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Account Details</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($updateSuccess): ?>
                                <div class="alert alert-success"><?php echo $updateSuccess; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($updateError): ?>
                                <div class="alert alert-danger"><?php echo $updateError; ?></div>
                            <?php endif; ?>
                            
                            <form method="post" action="account.php#profile">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name * (Current: <?php echo htmlspecialchars($user['first_name'] ?? ''); ?>)</label>
                                        <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" id="email" class="form-control" value="<?php echo !empty($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" readonly>
                                    <small class="text-muted">Email address cannot be changed.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>">
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="password">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($passwordSuccess): ?>
                                <div class="alert alert-success"><?php echo $passwordSuccess; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($passwordError): ?>
                                <div class="alert alert-danger"><?php echo $passwordError; ?></div>
                            <?php endif; ?>
                            
                            <form method="post" action="account.php#password">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password *</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password *</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                    <small class="text-muted">Password must be at least 8 characters long.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/public_footer.php'); ?>
