<?php

session_start();


if (isset($_SESSION['admin_user_id'])) {
    header('Location: index.php');
    exit();
}


require_once '../includes/init.php';


$username = '';
$password = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        
        // Validate form data
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            // Query database for user
            $db->query("SELECT * FROM admin_users WHERE username = :username");
            $db->bind(':username', $username);
            $user = $db->single();
            
            // Debug output - remove in production
            if ($user) {
                $error = "Found user with ID: " . $user['id'] . " - Verifying password...";
                if (password_verify($password, $user['password'])) {

                    $_SESSION['admin_user_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    

                    $db->query("UPDATE admin_users SET last_login = NOW() WHERE id = :id");
                    $db->bind(':id', $user['id']);
                    $db->execute();
                    

                    session_write_close();
                    session_start();
                    if (isset($_SESSION['admin_user_id'])) {

                        header('Location: index.php');
                        exit();
                    } else {
                        $error = "Session creation failed. Please check your PHP session configuration.";
                    }
                } else {
                    $error = 'Password verification failed.';
                }
            } else {
                $error = 'User not found with username: ' . $username;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - E-commerce System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        body {
            background-color: #f8f9fc;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-wrapper {
            width: 100%;
            max-width: 400px;
        }
        
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-radius: 1rem 1rem 0 0 !important;
            background-color: #4e73df;
            color: white;
            text-align: center;
            padding: 1.5rem;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            width: 100%;
            padding: 0.75rem;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Admin Login</h4>
                <p class="mb-0">E-commerce Management System</p>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
