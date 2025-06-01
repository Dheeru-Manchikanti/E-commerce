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

// Direct database query with debugging
try {
    $db = new Database(); // Create a fresh instance to avoid any potential issues
    $db->query("SELECT id, email, first_name, last_name, phone FROM users WHERE id = ?");
    $db->bind(1, $userId);
    $user = $db->single();
    
    // Debug output
    error_log("User query for ID $userId returned: " . print_r($user, true));
    
    if (!$user) {
        error_log("No user found with ID $userId in the database");
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// If database retrieval fails, try to use session data as fallback
if (empty($user) || empty($user['first_name'])) {
    error_log("Using session data as fallback");
    $user = [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'first_name' => '',
        'last_name' => ''
    ];
    
    // Parse the name from session if available
    if (isset($_SESSION['user_name'])) {
        $nameParts = explode(' ', $_SESSION['user_name'], 2);
        if (count($nameParts) > 0) {
            $user['first_name'] = $nameParts[0];
            $user['last_name'] = $nameParts[1] ?? '';
        }
    }
}

// Initialize variables
$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Validate
    if (empty($first_name)) {
        $error = 'First name is required';
    } elseif (empty($last_name)) {
        $error = 'Last name is required';
    } else {
        // Update user
        $db->query("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
        $db->bind(1, $first_name);
        $db->bind(2, $last_name);
        $db->bind(3, $phone);
        $db->bind(4, $userId);
        
        if ($db->execute()) {
            $success = 'Profile updated successfully!';
            
            // Update the user variable to show the new values
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['phone'] = $phone;
            
            // Update session
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}

// Set page title
$pageTitle = 'My Profile';

// Include header
include('includes/public_header.php');
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="account.php" class="list-group-item list-group-item-action">Dashboard</a>
                <a href="profile.php" class="list-group-item list-group-item-action active">My Profile</a>
                <a href="order-view.php" class="list-group-item list-group-item-action">My Orders</a>
                <a href="address-edit.php" class="list-group-item list-group-item-action">My Addresses</a>
                <a href="logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Profile</h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Account Information</h5>
                        <p>Update your personal details below</p>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <p><strong>Debug Info:</strong></p>
                        <ul>
                            <li>User ID: <?php echo $userId; ?></li>
                            <li>Email: <?php echo isset($user['email']) ? $user['email'] : 'Not available'; ?></li>
                            <li>First Name: <?php echo isset($user['first_name']) ? $user['first_name'] : 'Not available'; ?></li>
                            <li>Last Name: <?php echo isset($user['last_name']) ? $user['last_name'] : 'Not available'; ?></li>
                            <li>Session user_name: <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Not set'; ?></li>
                            <li>Session user_email: <?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'Not set'; ?></li>
                        </ul>
                    </div>
                    
                    <!-- Show the raw data from database and session -->
                    <div class="alert alert-secondary small">
                        <p><strong>Raw Data:</strong></p>
                        <div style="overflow-x:auto;">
                            <pre><?php 
                            echo "User variable:\n";
                            print_r($user);
                            echo "\n\nSession:\n";
                            print_r($_SESSION);
                            ?></pre>
                        </div>
                    </div>
                    
                    <form method="post" action="profile.php">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                    value="<?php echo !empty($user['first_name']) ? htmlspecialchars($user['first_name']) : 'Will'; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                    value="<?php echo !empty($user['last_name']) ? htmlspecialchars($user['last_name']) : 'Smith'; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" class="form-control" 
                                value="<?php echo !empty($user['email']) ? htmlspecialchars($user['email']) : htmlspecialchars($_SESSION['user_email'] ?? 'will@gmail.com'); ?>" readonly>
                            <small class="text-muted">Email address cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                value="<?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '9100638734'; ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/public_footer.php'); ?>
