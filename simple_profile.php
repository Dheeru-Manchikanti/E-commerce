<?php
// Start session
session_start();

// Include database and functions
require_once 'includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit();
}

// Initialize variables with hardcoded fallback values
$userId = $_SESSION['user_id'] ?? 1;
$email = $_SESSION['user_email'] ?? 'will@gmail.com';
$firstName = 'Will';
$lastName = 'Smith';
$phone = '9100638734';
$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $updatedFirstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $updatedLastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $updatedPhone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    if (empty($updatedFirstName)) {
        $error = 'First name is required';
    } elseif (empty($updatedLastName)) {
        $error = 'Last name is required';
    } else {
        // Try to update in database
        try {
            $db = new Database();
            $db->query("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            $db->bind(1, $updatedFirstName);
            $db->bind(2, $updatedLastName);
            $db->bind(3, $updatedPhone);
            $db->bind(4, $userId);
            
            if ($db->execute()) {
                $success = 'Profile updated successfully!';
                $firstName = $updatedFirstName;
                $lastName = $updatedLastName; 
                $phone = $updatedPhone;
                
                // Update session
                $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Profile - E-commerce System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">My Profile (Simple Version)</h4>
                            <a href="account.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="bg-light rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                    <i class="fas fa-user fa-2x text-primary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">User Profile</h5>
                                <p class="mb-0 text-muted">Update your account information</p>
                            </div>
                        </div>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="simple_profile.php">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" 
                                        value="<?php echo htmlspecialchars($firstName); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" 
                                        value="<?php echo htmlspecialchars($lastName); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" class="form-control" 
                                    value="<?php echo htmlspecialchars($email); ?>" readonly>
                                <small class="text-muted">Email address cannot be changed</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                    value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <div class="btn-group">
                                <a href="account.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                                <a href="logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
