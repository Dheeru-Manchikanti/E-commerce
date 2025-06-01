<?php
require_once 'includes/init.php';

// Get cart count if session exists
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
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
                <!-- Cart Link -->
                <div class="d-flex">
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
        </div>
