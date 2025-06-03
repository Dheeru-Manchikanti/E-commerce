<?php
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
$first_name = '';
$last_name = '';
$phone = '';
$password = '';
$confirm_password = '';
$error = '';
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $email = sanitize($_POST['email']);
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate form data
        $errors = [];
        
        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Check if email already exists
        $db->query("SELECT id FROM users WHERE email = :email");
        $db->bind(':email', $email);
        $existingUser = $db->single();
        
        if ($existingUser) {
            $errors[] = 'Email address is already registered. Please use a different email or login.';
        }
        
        // Validate name
        if (empty($first_name)) {
            $errors[] = 'Please enter your first name.';
        }
        
        if (empty($last_name)) {
            $errors[] = 'Please enter your last name.';
        }
        
        // Validate password
        if (empty($password)) {
            $errors[] = 'Please enter a password.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        // Validate password confirmation
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Insert user into database
            $db->query("INSERT INTO users (email, password, first_name, last_name, phone, verification_token) 
                       VALUES (:email, :password, :first_name, :last_name, :phone, :verification_token)");
            $db->bind(':email', $email);
            $db->bind(':password', $hashedPassword);
            $db->bind(':first_name', $first_name);
            $db->bind(':last_name', $last_name);
            $db->bind(':phone', $phone);
            $db->bind(':verification_token', $verificationToken);
            
            $result = $db->execute();
            
            if ($result) {
                $userId = $db->lastInsertId();
                
                // In a production environment, send verification email here
                // For now, we'll just mark the user as verified
                $db->query("UPDATE users SET email_verified = 1 WHERE id = :id");
                $db->bind(':id', $userId);
                $db->execute();
                
                // Set success message
                $success = 'Account created successfully! You can now <a href="login.php">login</a> to your account.';
                
                // Clear form fields
                $email = '';
                $first_name = '';
                $last_name = '';
                $phone = '';
            } else {
                $error = 'Error creating account. Please try again.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Set page title
$pageTitle = 'Register';

// Include header
include('includes/public_header.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Create Account</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php else: ?>
                        <form method="post" action="register.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                                <small class="text-muted">Password must be at least 8 characters long.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Create Account</button>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/public_footer.php'); ?>
