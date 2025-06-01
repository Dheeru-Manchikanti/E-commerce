<?php
require_once 'includes/init.php';

// Get cart count if session exists
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userFullName = '';

if ($isLoggedIn) {
    // Get user name for display
    $userId = $_SESSION['user_id'];
    $db->query("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = :id");
    $db->bind(':id', $userId);
    $user = $db->single();
    if ($user) {
        $userFullName = $user['full_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>E-commerce Store</title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-store">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <strong>E-commerce Store</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo !isset($currentPage) || $currentPage === 'home' ? 'active' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo isset($currentPage) && $currentPage === 'categories' ? 'active' : ''; ?>" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                            <?php
                            // Get all parent categories
                            $sql = "SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY name";
                            $db->query($sql);
                            $categories = $db->resultSet();
                            
                            foreach ($categories as $category) {
                                echo '<li><a class="dropdown-item" href="category.php?id=' . $category['id'] . '">' . htmlspecialchars($category['name']) . '</a></li>';
                            }
                            ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($currentPage) && $currentPage === 'products' ? 'active' : ''; ?>" href="products.php">All Products</a>
                    </li>
                </ul>
                <!-- Search Form -->
                <form class="d-flex me-3" action="search.php" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Search products..." aria-label="Search" required>
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                <!-- User Account & Cart Links -->
                <div class="d-flex align-items-center">
                    <?php if ($isLoggedIn): ?>
                        <!-- Logged in user menu -->
                        <div class="dropdown me-3">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($userFullName); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                                <li><a class="dropdown-item" href="account.php"><i class="fas fa-user me-2"></i> My Account</a></li>
                                <li><a class="dropdown-item" href="simple_profile.php"><i class="fas fa-id-card me-2"></i> My Profile</a></li>
                                <li><a class="dropdown-item" href="account.php?tab=orders"><i class="fas fa-shopping-bag me-2"></i> My Orders</a></li>
                                <li><a class="dropdown-item" href="account.php?tab=addresses"><i class="fas fa-map-marker-alt me-2"></i> My Addresses</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Login/Register links -->
                        <div class="btn-group me-3">
                            <a href="login.php" class="btn btn-outline-secondary"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                            <a href="register.php" class="btn btn-outline-secondary"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Cart Link -->
                    <a href="cart.php" class="btn btn-outline-primary position-relative">
                        <i class="fas fa-shopping-cart"></i> Cart
                        <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $cartCount; ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container mt-4" id="main-content">
        <div id="cartAlerts">
            <?php displayFlashMessage(); ?>
        </div
