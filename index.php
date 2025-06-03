<?php
// Home page - main storefront
$pageTitle = 'Home';
$currentPage = 'home';

require_once 'includes/init.php';

// Get featured products
$sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as additional_image 
        FROM products p 
        WHERE p.status = 'active' AND p.featured = 1 
        ORDER BY p.created_at DESC 
        LIMIT 8";
$db->query($sql);
$featuredProducts = $db->resultSet();


$sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as additional_image 
        FROM products p 
        WHERE p.status = 'active' 
        ORDER BY p.created_at DESC 
        LIMIT 8";
$db->query($sql);
$latestProducts = $db->resultSet();


$sql = "SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active'";
$db->query($sql);
$parentCategories = $db->resultSet();


include 'includes/public_header.php';
?>


<section class="hero-section mb-5">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h1>Welcome to Our E-commerce Store</h1>
                <p class="lead">Discover amazing products at competitive prices</p>
                <a href="products.php" class="btn btn-primary btn-lg">Shop Now</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Featured Products</h2>
                <div class="row">
                    <?php foreach ($featuredProducts as $product): ?>
                        <div class="col-md-3 col-sm-6">
                            <div class="product-card card h-100">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <?php if (!empty($product['image_main'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($product['image_main']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php elseif (!empty($product['additional_image'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($product['additional_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <img src="assets/images/placeholder.jpg" class="card-img-top" alt="Placeholder Image">
                                    <?php endif; ?>
                                </a>
                                <div class="card-body">
                                    <h5 class="product-title">
                                        <a href="product.php?id=<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h5>
                                    <div class="mb-2">
                                        <?php if (!empty($product['sale_price'])): ?>
                                            <span class="product-price sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                                            <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                                        <?php else: ?>
                                            <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary btn-sm add-to-cart" data-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="mb-5 bg-light py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Shop by Category</h2>
                <div class="row">
                    <?php foreach ($parentCategories as $category): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <h3 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                                    <?php if (!empty($category['description'])): ?>
                                        <p class="card-text"><?php echo htmlspecialchars($category['description']); ?></p>
                                    <?php endif; ?>
                                    <a href="category.php?id=<?php echo $category['id']; ?>" class="btn btn-outline-primary">View Products</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Products -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Latest Products</h2>
                <div class="row">
                    <?php foreach ($latestProducts as $product): ?>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="product-card card h-100">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <?php if (!empty($product['image_main'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($product['image_main']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php elseif (!empty($product['additional_image'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($product['additional_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <img src="assets/images/placeholder.jpg" class="card-img-top" alt="Placeholder Image">
                                    <?php endif; ?>
                                </a>
                                <div class="card-body">
                                    <h5 class="product-title">
                                        <a href="product.php?id=<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h5>
                                    <div class="mb-2">
                                        <?php if (!empty($product['sale_price'])): ?>
                                            <span class="product-price sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                                            <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                                        <?php else: ?>
                                            <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary btn-sm add-to-cart" data-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-4">
                    <a href="products.php" class="btn btn-outline-primary">View All Products</a>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="mb-5">
    <div class="container">
        <div class="card bg-primary text-white">
            <div class="card-body p-4 text-center">
                <h3>Special Offer!</h3>
                <p class="lead">Get 10% off your first order. Use code: WELCOME10</p>
                <a href="products.php" class="btn btn-light">Shop Now</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
