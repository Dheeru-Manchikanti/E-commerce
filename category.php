<?php
// Category page - display products by category
require_once 'includes/init.php';

// Check if category ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$categoryId = (int)$_GET['id'];

// Get category details
$sql = "SELECT * FROM categories WHERE id = :id AND status = 'active'";
$db->query($sql);
$db->bind(':id', $categoryId);
$category = $db->single();

// If category not found, redirect to home
if (!$category) {
    header('Location: index.php');
    exit;
}

// Get parent category if this is a subcategory
$parentCategory = null;
if (!empty($category['parent_id'])) {
    $sql = "SELECT * FROM categories WHERE id = :id";
    $db->query($sql);
    $db->bind(':id', $category['parent_id']);
    $parentCategory = $db->single();
}

// Get subcategories if any
$sql = "SELECT * FROM categories WHERE parent_id = :parent_id AND status = 'active'";
$db->query($sql);
$db->bind(':parent_id', $categoryId);
$subcategories = $db->resultSet();

// Set up pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Count total products in this category and its subcategories
$productCountSql = "SELECT COUNT(*) as total FROM products p
                   JOIN product_categories pc ON p.id = pc.product_id
                   WHERE p.status = 'active' AND pc.category_id = :category_id";
$db->query($productCountSql);
$db->bind(':category_id', $categoryId);
$totalCount = $db->single()['total'];

// Get products for this category with pagination
$sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as additional_image 
        FROM products p 
        JOIN product_categories pc ON p.id = pc.product_id 
        WHERE p.status = 'active' AND pc.category_id = :category_id 
        ORDER BY p.created_at DESC 
        LIMIT :offset, :limit";
$db->query($sql);
$db->bind(':category_id', $categoryId);
$db->bind(':offset', $offset, PDO::PARAM_INT);
$db->bind(':limit', $perPage, PDO::PARAM_INT);
$products = $db->resultSet();

// Generate pagination data
$pagination = paginate($totalCount, $perPage, $page, "category.php?id=$categoryId&page=(:num)");

// Set page title
$pageTitle = $category['name'];
$currentPage = 'categories';

// Include header
include 'includes/public_header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mt-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <?php if ($parentCategory): ?>
            <li class="breadcrumb-item">
                <a href="category.php?id=<?php echo $parentCategory['id']; ?>">
                    <?php echo htmlspecialchars($parentCategory['name']); ?>
                </a>
            </li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($category['name']); ?></li>
    </ol>
</nav>

<!-- Category Header -->
<section class="mb-4">
    <div class="container">
        <div class="card bg-light">
            <div class="card-body">
                <h1 class="mb-2"><?php echo htmlspecialchars($category['name']); ?></h1>
                <?php if (!empty($category['description'])): ?>
                    <p class="lead"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Subcategories (if any) -->
<?php if (!empty($subcategories)): ?>
<section class="mb-4">
    <div class="container">
        <h2 class="mb-3">Subcategories</h2>
        <div class="row">
            <?php foreach ($subcategories as $subcat): ?>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($subcat['name']); ?></h5>
                            <a href="category.php?id=<?php echo $subcat['id']; ?>" class="btn btn-outline-primary btn-sm">View Products</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Products Section -->
<section class="mb-5">
    <div class="container">
        <h2 class="mb-3">Products</h2>
        
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                No products found in this category.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($products as $product): ?>
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
            
            <!-- Pagination -->
            <?php if ($pagination['totalPages'] > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['links']['prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $pagination['links']['prev']; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <a class="page-link" href="#" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php foreach ($pagination['links']['pages'] as $pageNum => $url): ?>
                            <li class="page-item <?php echo $pageNum == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo $url; ?>"><?php echo $pageNum; ?></a>
                            </li>
                        <?php endforeach; ?>
                        
                        <?php if ($pagination['links']['next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo $pagination['links']['next']; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <a class="page-link" href="#" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/public_footer.php'; ?>
