<?php
// Product detail page
require_once 'includes/init.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$productId = (int)$_GET['id'];

// Get product details
$sql = "SELECT * FROM products WHERE id = :id AND status = 'active'";
$db->query($sql);
$db->bind(':id', $productId);
$product = $db->single();

// If product not found, redirect to home
if (!$product) {
    header('Location: index.php');
    exit;
}

// Get product images
$sql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order";
$db->query($sql);
$db->bind(':product_id', $productId);
$productImages = $db->resultSet();

// Get product categories
$sql = "SELECT c.id, c.name FROM categories c 
        JOIN product_categories pc ON c.id = pc.category_id 
        WHERE pc.product_id = :product_id";
$db->query($sql);
$db->bind(':product_id', $productId);
$productCategories = $db->resultSet();

// Get related products based on categories
$relatedProductsIds = [];
if (!empty($productCategories)) {
    $categoryIds = array_column($productCategories, 'id');
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    
    $sql = "SELECT DISTINCT p.* FROM products p 
            JOIN product_categories pc ON p.id = pc.product_id 
            WHERE p.id != ? AND pc.category_id IN ($placeholders) 
            AND p.status = 'active' 
            LIMIT 4";
    
    $db->query($sql);
    $db->bind(1, $productId);
    
    $paramIndex = 2;
    foreach ($categoryIds as $catId) {
        $db->bind($paramIndex, $catId);
        $paramIndex++;
    }
    
    $relatedProducts = $db->resultSet();
}

// Set page title
$pageTitle = $product['name'];
$currentPage = 'product';


include 'includes/public_header.php';
?>


<nav aria-label="breadcrumb" class="mt-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <?php if (!empty($productCategories)): ?>
            <li class="breadcrumb-item">
                <a href="category.php?id=<?php echo $productCategories[0]['id']; ?>">
                    <?php echo htmlspecialchars($productCategories[0]['name']); ?>
                </a>
            </li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
    </ol>
</nav>

<!-- Product Details -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <!-- Product Images -->
            <div class="col-md-6">
                <div class="product-image-gallery">
                    <?php if (!empty($product['image_main'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($product['image_main']); ?>" class="img-fluid mb-3" id="mainProductImage" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                        <img src="assets/images/placeholder.jpg" class="img-fluid mb-3" id="mainProductImage" alt="Placeholder Image">
                    <?php endif; ?>
                    
                    <?php if (!empty($productImages)): ?>
                        <div class="row">
                            <?php foreach ($productImages as $image): ?>
                                <div class="col-3 mb-3">
                                    <img src="uploads/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         class="img-fluid thumbnail" 
                                         alt="Product Thumbnail"
                                         onclick="changeMainImage('uploads/<?php echo htmlspecialchars($image['image_path']); ?>')">
                                </div>
                            <?php endforeach; ?>
                            <?php if (!empty($product['image_main'])): ?>
                                <div class="col-3 mb-3">
                                    <img src="uploads/<?php echo htmlspecialchars($product['image_main']); ?>" 
                                         class="img-fluid thumbnail active" 
                                         alt="Product Thumbnail"
                                         onclick="changeMainImage('uploads/<?php echo htmlspecialchars($product['image_main']); ?>')">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="mb-3">
                    <?php if (!empty($product['sale_price'])): ?>
                        <span class="product-price sale-price h3"><?php echo formatPrice($product['sale_price']); ?></span>
                        <span class="original-price h5"><?php echo formatPrice($product['price']); ?></span>
                        <span class="badge bg-danger ms-2">Sale</span>
                    <?php else: ?>
                        <span class="product-price h3"><?php echo formatPrice($product['price']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <span class="<?php echo $product['stock_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $product['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                    <?php if ($product['stock_quantity'] > 0 && $product['stock_quantity'] < 10): ?>
                        <small class="text-danger ms-2">Only <?php echo $product['stock_quantity']; ?> left!</small>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($product['description'])): ?>
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($productCategories)): ?>
                    <div class="mb-3">
                        <h5>Categories</h5>
                        <?php foreach ($productCategories as $category): ?>
                            <a href="category.php?id=<?php echo $category['id']; ?>" class="badge bg-secondary text-decoration-none">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($product['stock_quantity'] > 0): ?>
                    <form class="mb-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-auto">
                                <label for="quantity" class="col-form-label">Quantity</label>
                            </div>
                            <div class="col-auto">
                                <input type="number" id="quantity" class="form-control" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary add-to-cart" data-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
                
                <?php if (!empty($product['sku'])): ?>
                    <div class="mb-3">
                        <small class="text-muted">SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<?php if (!empty($relatedProducts)): ?>
<section class="mb-5">
    <div class="container">
        <h3 class="mb-4">Related Products</h3>
        <div class="row">
            <?php foreach ($relatedProducts as $relatedProduct): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="product-card card h-100">
                        <a href="product.php?id=<?php echo $relatedProduct['id']; ?>">
                            <?php if (!empty($relatedProduct['image_main'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($relatedProduct['image_main']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>">
                            <?php else: ?>
                                <img src="assets/images/placeholder.jpg" class="card-img-top" alt="Placeholder Image">
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <h5 class="product-title">
                                <a href="product.php?id=<?php echo $relatedProduct['id']; ?>">
                                    <?php echo htmlspecialchars($relatedProduct['name']); ?>
                                </a>
                            </h5>
                            <div class="mb-2">
                                <?php if (!empty($relatedProduct['sale_price'])): ?>
                                    <span class="product-price sale-price"><?php echo formatPrice($relatedProduct['sale_price']); ?></span>
                                    <span class="original-price"><?php echo formatPrice($relatedProduct['price']); ?></span>
                                <?php else: ?>
                                    <span class="product-price"><?php echo formatPrice($relatedProduct['price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-sm add-to-cart" data-id="<?php echo $relatedProduct['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
    function changeMainImage(imgSrc) {
        document.getElementById('mainProductImage').src = imgSrc;
        document.querySelectorAll('.thumbnail').forEach(function(thumb) {
            thumb.classList.remove('active');
            if (thumb.src === imgSrc) {
                thumb.classList.add('active');
            }
        });
    }
</script>

<?php include 'includes/public_footer.php'; ?>
