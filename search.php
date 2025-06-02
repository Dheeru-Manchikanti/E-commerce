<?php
// Search page
require_once 'includes/init.php';

// Check if search query is provided
if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    header('Location: index.php');
    exit;
}

$searchQuery = trim($_GET['q']);
$searchTerms = '%' . $searchQuery . '%';

// Set up pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Count total products matching the search
$productCountSql = "SELECT COUNT(*) as total FROM products 
                    WHERE status = 'active' AND 
                    (name LIKE ? OR description LIKE ? OR sku LIKE ?)";
$db->query($productCountSql);
$db->bind(1, $searchTerms);
$db->bind(2, $searchTerms);
$db->bind(3, $searchTerms);
$totalCount = $db->single()['total'];

// Get products matching the search with pagination
$sql = "SELECT p.*, (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as additional_image 
        FROM products p 
        WHERE p.status = 'active' AND 
        (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?) 
        ORDER BY p.name ASC 
        LIMIT ?, ?";
$db->query($sql);
$db->bind(1, $searchTerms);
$db->bind(2, $searchTerms);
$db->bind(3, $searchTerms);
$db->bind(4, $offset, PDO::PARAM_INT);
$db->bind(5, $perPage, PDO::PARAM_INT);
$products = $db->resultSet();

// Generate pagination data
$pagination = paginate($totalCount, $perPage, $page, "search.php?q=" . urlencode($searchQuery) . "&page=(:num)");

// Set page title
$pageTitle = 'Search Results: ' . htmlspecialchars($searchQuery);

// Include header
include 'includes/public_header.php';
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mt-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Search Results</li>
    </ol>
</nav>

<!-- Search Header -->
<section class="mb-4">
    <div class="container">
        <div class="card bg-light">
            <div class="card-body">
                <h1 class="mb-2">Search Results</h1>
                <p class="lead">Showing results for: <strong><?php echo htmlspecialchars($searchQuery); ?></strong></p>
                <p>Found <?php echo $totalCount; ?> product(s)</p>
                
                <!-- Search Form -->
                <form class="mt-3" action="search.php" method="GET">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($searchQuery); ?>" required>
                        <button class="btn btn-primary" type="submit">Search Again</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Products Section -->
<section class="mb-5">
    <div class="container">
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                No products found matching your search. Please try a different search term.
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
