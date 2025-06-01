<?php
// Start session
session_start();

// Include database and functions
require_once 'includes/init.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: account.php');
    exit();
}

// Initialize variables
$email = '';
$password = '';
$error = '';
$redirect = '';

// Check if there's a redirect URL
if (isset($_GET['redirect'])) {
    $redirect = sanitize($_GET['redirect']);
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $redirect = isset($_POST['redirect']) ? sanitize($_POST['redirect']) : '';
        
        // Validate form data
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            // Query database for user
            $db->query("SELECT * FROM users WHERE email = :email AND is_active = 1");
            $db->bind(':email', $email);
            $user = $db->single();
            
            // Verify user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Check if email is verified
                if ($user['email_verified']) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Update last login time
                    $db->query("UPDATE users SET last_login = NOW() WHERE id = :id");
                    $db->bind(':id', $user['id']);
                    $db->execute();
                    
                    // If there's a shopping cart in the session, associate it with the user
                    if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                        // In a full implementation, you would save the cart to the database here
                    }
                    
                    // Redirect user
                    if (!empty($redirect) && filter_var($redirect, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
                        $parsedUrl = parse_url($redirect);
                        // Make sure it's a relative URL (no scheme or host)
                        if (!isset($parsedUrl['scheme']) && !isset($parsedUrl['host'])) {
                            header('Location: ' . $redirect);
                            exit();
                        }
                    }
                    
                    // Default redirect to account page
                    header('Location: account.php');
                    exit();
                } else {
                    $error = 'Please verify your email address before logging in.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}

// Set page title
$pageTitle = 'Login';

// Include header
include('includes/public_header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Customer Login</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="login.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <?php if (!empty($redirect)): ?>
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="forgot-password.php">Forgot your password?</a>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/public_footer.php'); ?>
