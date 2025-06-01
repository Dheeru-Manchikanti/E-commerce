<?php
// Check if user is logged in
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database and functions
require_once '../includes/init.php';

// Get products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Count total products
$db->query("SELECT COUNT(*) as total FROM products");
$totalResult = $db->single();
$totalProducts = $totalResult['total'];

// Get products for current page
$db->query("SELECT p.*, 
           (SELECT GROUP_CONCAT(c.name SEPARATOR ', ') 
            FROM categories c 
            JOIN product_categories pc ON c.id = pc.category_id 
            WHERE pc.product_id = p.id) as category_names 
           FROM products p 
           ORDER BY p.id DESC 
           LIMIT :offset, :limit");
$db->bind(':offset', $offset);
$db->bind(':limit', $itemsPerPage);
$products = $db->resultSet();

// Get all categories for filter and add form
$db->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$parentCategories = $db->resultSet();

$allCategories = [];
foreach ($parentCategories as $parent) {
    $allCategories[] = $parent;
    
    // Get child categories
    $db->query("SELECT * FROM categories WHERE parent_id = :parent_id ORDER BY name");
    $db->bind(':parent_id', $parent['id']);
    $children = $db->resultSet();
    
    foreach ($children as $child) {
        $child['name'] = '— ' . $child['name']; // Add indentation to show hierarchy
        $allCategories[] = $child;
    }
}

// Process add product form
$formErrors = [];
$formSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $formErrors[] = 'Invalid form submission.';
    } else {
        // Get and sanitize form data
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        $sale_price = !empty($_POST['sale_price']) ? filter_var($_POST['sale_price'], FILTER_VALIDATE_FLOAT) : null;
        $stock_quantity = filter_var($_POST['stock_quantity'], FILTER_VALIDATE_INT);
        $sku = sanitize($_POST['sku']);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $status = sanitize($_POST['status']);
        $categories = isset($_POST['categories']) ? $_POST['categories'] : [];
        
        // Validation
        if (empty($name)) {
            $formErrors[] = 'Product name is required.';
        }
        
        if ($price === false || $price <= 0) {
            $formErrors[] = 'Price must be a positive number.';
        }
        
        if ($sale_price !== null && ($sale_price <= 0 || $sale_price >= $price)) {
            $formErrors[] = 'Sale price must be a positive number less than regular price.';
        }
        
        if ($stock_quantity === false || $stock_quantity < 0) {
            $formErrors[] = 'Stock quantity must be a non-negative number.';
        }
        
        // Check if SKU exists
        if (!empty($sku)) {
            $db->query("SELECT id FROM products WHERE sku = :sku");
            $db->bind(':sku', $sku);
            if ($db->rowCount() > 0) {
                $formErrors[] = 'SKU already exists. Please use a unique SKU.';
            }
        }
        
        // Process image upload
        $image_main = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $image_main = uploadFile($_FILES['image'], UPLOADS_DIR, ['image/jpeg', 'image/png', 'image/gif']);
            if ($image_main === false) {
                $formErrors[] = 'Failed to upload image. Please ensure it is a valid image file (JPG, PNG, or GIF) and less than 2MB.';
            }
        }
        
        // If no errors, add product to database
        if (empty($formErrors)) {
            try {
                // Start transaction
                $db->beginTransaction();
                
                // Insert product
                $db->query("INSERT INTO products (name, description, price, sale_price, stock_quantity, sku, featured, status, image_main) 
                           VALUES (:name, :description, :price, :sale_price, :stock_quantity, :sku, :featured, :status, :image_main)");
                $db->bind(':name', $name);
                $db->bind(':description', $description);
                $db->bind(':price', $price);
                $db->bind(':sale_price', $sale_price);
                $db->bind(':stock_quantity', $stock_quantity);
                $db->bind(':sku', $sku);
                $db->bind(':featured', $featured);
                $db->bind(':status', $status);
                $db->bind(':image_main', $image_main);
                $db->execute();
                
                $productId = $db->lastInsertId();
                
                // Add product categories
                if (!empty($categories)) {
                    $values = [];
                    $params = [];
                    $i = 0;
                    
                    foreach ($categories as $categoryId) {
                        $values[] = "(:product_id" . $i . ", :category_id" . $i . ")";
                        $params[':product_id' . $i] = $productId;
                        $params[':category_id' . $i] = $categoryId;
                        $i++;
                    }
                    
                    $sql = "INSERT INTO product_categories (product_id, category_id) VALUES " . implode(', ', $values);
                    $db->query($sql);
                    
                    foreach ($params as $param => $value) {
                        $db->bind($param, $value);
                    }
                    
                    $db->execute();
                }
                
                $db->commit();
                
                $formSuccess = 'Product added successfully.';
                
                // Clear form data
                $name = $description = $sku = '';
                $price = $sale_price = $stock_quantity = '';
                $featured = 0;
                $status = 'active';
                $categories = [];
            } catch (Exception $e) {
                $db->rollBack();
                $formErrors[] = 'Error adding product: ' . $e->getMessage();
            }
        }
    }
}

// Generate pagination
$pagination = paginate($totalProducts, $itemsPerPage, $page, '?page=(:num)');

// Page title
$pageTitle = 'Products';

// Additional JS
$additionalJS = [
    '../assets/js/products.js'
];
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
        <div>
            <a href="bulk-image-upload.php" class="btn btn-success me-2">
                <i class="fas fa-images"></i> Bulk Image Upload
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Add New Product
            </button>
        </div>
    </div>
    
    <?php if (!empty($formSuccess)): ?>
        <div class="alert alert-success"><?php echo $formSuccess; ?></div>
    <?php endif; ?>
    
    <!-- Products Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Filter Products</h6>
            <button class="btn btn-sm btn-outline-secondary" type="button" id="toggleFilter">
                <i class="fas fa-filter"></i> Toggle Filter
            </button>
        </div>
        <div class="card-body" id="filterContainer" style="display: none;">
            <form method="get" action="products.php">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="name">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Search by name">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="category">Category</label>
                        <select class="form-control" id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($allCategories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="stock">Stock Status</label>
                        <select class="form-control" id="stock" name="stock">
                            <option value="">All</option>
                            <option value="in_stock">In Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                            <option value="low_stock">Low Stock (≤ 5)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Products List</h6>
            <div>
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    Total Products: <strong><?php echo $totalProducts; ?></strong>
                </span>
            </div>
        </div>
        <div class="card-body">
            <form id="bulkActionForm">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <select class="form-select" id="bulkAction" name="bulk_action">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" id="bulkActionButton" class="btn btn-sm btn-secondary" disabled>
                            Apply (<span id="selectedCount">0</span>)
                        </button>
                    </div>
                    <div class="pagination">
                        <?php if ($pagination['links']['prev']): ?>
                            <a href="<?php echo $pagination['links']['prev']; ?>" class="btn btn-sm btn-outline-primary">&laquo; Previous</a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>&laquo; Previous</button>
                        <?php endif; ?>
                        
                        <?php foreach ($pagination['links']['pages'] as $pageNum => $url): ?>
                            <?php if ($pageNum == $page): ?>
                                <a href="<?php echo $url; ?>" class="btn btn-sm btn-primary"><?php echo $pageNum; ?></a>
                            <?php else: ?>
                                <a href="<?php echo $url; ?>" class="btn btn-sm btn-outline-primary"><?php echo $pageNum; ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if ($pagination['links']['next']): ?>
                            <a href="<?php echo $pagination['links']['next']; ?>" class="btn btn-sm btn-outline-primary">Next &raquo;</a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>Next &raquo;</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="productsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="20">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th width="60">ID</th>
                                <th width="80">Image</th>
                                <th>Name</th>
                                <th>Categories</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="product-checkbox" name="products[]" value="<?php echo $product['id']; ?>">
                                        </td>
                                        <td><?php echo $product['id']; ?></td>
                                        <td>
                                            <?php if ($product['image_main']): ?>
                                                <img src="<?php echo UPLOADS_URL . $product['image_main']; ?>" alt="<?php echo htmlspecialchars(desanitize($product['name'])); ?>" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                            <?php else: ?>
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-image fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars(desanitize($product['name'])); ?>
                                            <?php if ($product['featured']): ?>
                                                <span class="badge bg-warning ms-1">Featured</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(desanitize($product['category_names'] ?? '')); ?></td>
                                        <td>
                                            <?php if ($product['sale_price']): ?>
                                                <span class="text-danger"><?php echo formatPrice($product['sale_price']); ?></span>
                                                <del class="text-muted small"><?php echo formatPrice($product['price']); ?></del>
                                            <?php else: ?>
                                                <?php echo formatPrice($product['price']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($product['stock_quantity'] <= 0): ?>
                                                <span class="badge bg-danger">Out of Stock</span>
                                            <?php elseif ($product['stock_quantity'] <= 5): ?>
                                                <span class="badge bg-warning">Low: <?php echo $product['stock_quantity']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $product['stock_quantity']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($product['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-info" title="Edit Product">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger delete-product" data-id="<?php echo $product['id']; ?>" title="Delete Product">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Bottom pagination -->
                <div class="mt-3 d-flex justify-content-end">
                    <div class="pagination">
                        <?php if ($pagination['links']['prev']): ?>
                            <a href="<?php echo $pagination['links']['prev']; ?>" class="btn btn-sm btn-outline-primary">&laquo; Previous</a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>&laquo; Previous</button>
                        <?php endif; ?>
                        
                        <?php foreach ($pagination['links']['pages'] as $pageNum => $url): ?>
                            <?php if ($pageNum == $page): ?>
                                <a href="<?php echo $url; ?>" class="btn btn-sm btn-primary"><?php echo $pageNum; ?></a>
                            <?php else: ?>
                                <a href="<?php echo $url; ?>" class="btn btn-sm btn-outline-primary"><?php echo $pageNum; ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if ($pagination['links']['next']): ?>
                            <a href="<?php echo $pagination['links']['next']; ?>" class="btn btn-sm btn-outline-primary">Next &raquo;</a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>Next &raquo;</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="products.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="modal-body">
                    <?php if (!empty($formErrors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($formErrors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars(desanitize($name)) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="sku" class="form-label">SKU</label>
                            <input type="text" class="form-control" id="sku" name="sku" value="<?php echo isset($sku) ? htmlspecialchars($sku) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Regular Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required value="<?php echo isset($price) ? htmlspecialchars($price) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="sale_price" class="form-label">Sale Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="sale_price" name="sale_price" step="0.01" min="0" value="<?php echo isset($sale_price) ? htmlspecialchars($sale_price) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" required value="<?php echo isset($stock_quantity) ? htmlspecialchars($stock_quantity) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" <?php echo (!isset($status) || $status === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($status) && $status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($description) ? htmlspecialchars(desanitize($description)) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image">
                        <small class="text-muted">Supported formats: JPG, PNG, GIF. Max size: 2MB.</small>
                        <div class="mt-2">
                            <img id="imagePreview" src="#" alt="Image Preview" style="max-width: 200px; max-height: 200px; display: none;">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="featured" name="featured" <?php echo (isset($featured) && $featured) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="featured">
                                Featured Product
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Product Categories</label>
                        <div class="row">
                            <?php foreach ($allCategories as $category): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="category_<?php echo $category['id']; ?>" <?php echo (isset($categories) && in_array($category['id'], $categories)) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category_<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="add_product">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>



<?php include 'includes/footer.php'; ?>
