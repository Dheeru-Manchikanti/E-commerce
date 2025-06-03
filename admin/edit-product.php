<?php
// Check if user is logged in
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database and functions
require_once '../includes/init.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'Product ID is required.', 'danger');
    header('Location: products.php');
    exit();
}

$productId = (int)$_GET['id'];

// Get product data
$db->query("SELECT * FROM products WHERE id = :id");
$db->bind(':id', $productId);
$product = $db->single();

if (!$product) {
    setFlashMessage('error', 'Product not found.', 'danger');
    header('Location: products.php');
    exit();
}

// Get product categories
$db->query("SELECT category_id FROM product_categories WHERE product_id = :product_id");
$db->bind(':product_id', $productId);
$productCategoriesResult = $db->resultSet();
$productCategories = [];
foreach ($productCategoriesResult as $category) {
    $productCategories[] = $category['category_id'];
}

// Get all categories for form
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

// Get product images
$db->query("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order");
$db->bind(':product_id', $productId);
$productImages = $db->resultSet();

// Process form submission
$formErrors = [];
$formSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $formErrors[] = 'Invalid form submission.';
    } else if (!isset($_POST['product_id']) || (int)$_POST['product_id'] !== $productId) {
        $formErrors[] = 'Invalid product ID.';
    } else {
        // Get and sanitize form data
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        $sale_price = !empty($_POST['sale_price']) ? filter_var($_POST['sale_price'], FILTER_VALIDATE_FLOAT) : null;
        $stock_quantity = filter_var($_POST['stock_quantity'], FILTER_VALIDATE_INT);
        $sku = !empty($_POST['sku']) ? sanitize($_POST['sku']) : null;
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
        
        // Check if SKU exists and is not this product's SKU
        if (!empty($sku) && $sku !== $product['sku']) {
            $db->query("SELECT id FROM products WHERE sku = :sku AND id != :id");
            $db->bind(':sku', $sku);
            $db->bind(':id', $productId);
            if ($db->rowCount() > 0) {
                $formErrors[] = 'SKU already exists. Please use a unique SKU.';
            }
        }
        
        // Process image upload
        $image_main = $product['image_main']; // Keep existing image by default
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $new_image = uploadFile($_FILES['image'], UPLOADS_DIR, ['image/jpeg', 'image/png', 'image/gif']);
            if ($new_image === false) {
                $formErrors[] = 'Failed to upload image. Please ensure it is a valid image file (JPG, PNG, or GIF) and less than 2MB.';
            } else {
                $image_main = $new_image;
                // Delete old image if exists
                if ($product['image_main'] && file_exists(UPLOADS_DIR . $product['image_main'])) {
                    unlink(UPLOADS_DIR . $product['image_main']);
                }
            }
        }
        
        // Process additional images
        $newImages = [];
        if (isset($_FILES['additional_images'])) {
            $fileCount = count($_FILES['additional_images']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['additional_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['additional_images']['name'][$i],
                        'type' => $_FILES['additional_images']['type'][$i],
                        'tmp_name' => $_FILES['additional_images']['tmp_name'][$i],
                        'error' => $_FILES['additional_images']['error'][$i],
                        'size' => $_FILES['additional_images']['size'][$i]
                    ];
                    
                    $additionalImage = uploadFile($file, UPLOADS_DIR, ['image/jpeg', 'image/png', 'image/gif']);
                    if ($additionalImage !== false) {
                        $newImages[] = $additionalImage;
                    }
                }
            }
        }
        
        // If no errors, update product in database
        if (empty($formErrors)) {
            try {
                // Start transaction
                $db->beginTransaction();
                
                // Update product
                $db->query("UPDATE products SET 
                           name = :name, 
                           description = :description, 
                           price = :price, 
                           sale_price = :sale_price, 
                           stock_quantity = :stock_quantity, 
                           sku = :sku, 
                           featured = :featured, 
                           status = :status, 
                           image_main = :image_main,
                           updated_at = NOW()
                           WHERE id = :id");
                $db->bind(':name', $name);
                $db->bind(':description', $description);
                $db->bind(':price', $price);
                $db->bind(':sale_price', $sale_price);
                $db->bind(':stock_quantity', $stock_quantity);
                $db->bind(':sku', $sku);
                $db->bind(':featured', $featured);
                $db->bind(':status', $status);
                $db->bind(':image_main', $image_main);
                $db->bind(':id', $productId);
                
                if (!$db->execute()) {
                    throw new Exception("Failed to update product");
                }
                
                // Update categories
                // First, delete existing categories
                $db->query("DELETE FROM product_categories WHERE product_id = :product_id");
                $db->bind(':product_id', $productId);
                
                if (!$db->execute()) {
                    throw new Exception("Failed to delete existing product categories");
                }
                
                // Then add new categories
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
                    
                    if (!$db->execute()) {
                        throw new Exception("Failed to add product categories");
                    }
                }
                
                // Add new additional images
                if (!empty($newImages)) {
                    // Get current highest sort order
                    $db->query("SELECT MAX(sort_order) as max_order FROM product_images WHERE product_id = :product_id");
                    $db->bind(':product_id', $productId);
                    $maxOrderResult = $db->single();
                    $sortOrder = $maxOrderResult['max_order'] ? $maxOrderResult['max_order'] + 1 : 1;
                    
                    foreach ($newImages as $image) {
                        $db->query("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (:product_id, :image_path, :sort_order)");
                        $db->bind(':product_id', $productId);
                        $db->bind(':image_path', $image);
                        $db->bind(':sort_order', $sortOrder);
                        $db->execute();
                        $sortOrder++;
                    }
                }
                
                $db->commit();
                
                $formSuccess = 'Product updated successfully.';
                
                // Refresh product data
                $db->query("SELECT * FROM products WHERE id = :id");
                $db->bind(':id', $productId);
                $product = $db->single();
                
                // Refresh product categories
                $db->query("SELECT category_id FROM product_categories WHERE product_id = :product_id");
                $db->bind(':product_id', $productId);
                $productCategoriesResult = $db->resultSet();
                $productCategories = [];
                foreach ($productCategoriesResult as $category) {
                    $productCategories[] = $category['category_id'];
                }
                
                // Refresh product images
                $db->query("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order");
                $db->bind(':product_id', $productId);
                $productImages = $db->resultSet();
            } catch (Exception $e) {
                $db->rollBack();
                $formErrors[] = 'Error updating product: ' . $e->getMessage();
                // Add detailed error information for debugging
                error_log('Product Update Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
        }
    }
}

// Process image deletion
if (isset($_GET['delete_image']) && isset($_GET['image_id'])) {
    $imageId = (int)$_GET['image_id'];
    
    // Get image data
    $db->query("SELECT * FROM product_images WHERE id = :id AND product_id = :product_id");
    $db->bind(':id', $imageId);
    $db->bind(':product_id', $productId);
    $image = $db->single();
    
    if ($image) {
        // Delete image file
        if (file_exists(UPLOADS_DIR . $image['image_path'])) {
            unlink(UPLOADS_DIR . $image['image_path']);
        }
        
        // Delete from database
        $db->query("DELETE FROM product_images WHERE id = :id");
        $db->bind(':id', $imageId);
        $db->execute();
        
        setFlashMessage('success', 'Image deleted successfully.', 'success');
        header('Location: edit-product.php?id=' . $productId);
        exit();
    }
}

// Page title
$pageTitle = 'Edit Product';

// Additional JS
$additionalJS = [
    '../assets/js/products.js'
];
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Product</h1>
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
    
    <?php if (!empty($formSuccess)): ?>
        <div class="alert alert-success"><?php echo $formSuccess; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($formErrors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($formErrors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Product Details</h6>
        </div>
        <div class="card-body">
            <form method="post" action="edit-product.php?id=<?php echo $productId; ?>" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo htmlspecialchars(desanitize($product['name'])); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" id="sku" name="sku" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Regular Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required value="<?php echo $product['price']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="sale_price" class="form-label">Sale Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="sale_price" name="sale_price" step="0.01" min="0" value="<?php echo $product['sale_price'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" required value="<?php echo $product['stock_quantity']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="featured" name="featured" <?php echo $product['featured'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featured">
                                    Featured Product
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Main Product Image</label>
                        <div class="text-center mb-3">
                            <?php if ($product['image_main']): ?>
                                <img src="<?php echo UPLOADS_URL . $product['image_main']; ?>" alt="<?php echo htmlspecialchars(desanitize($product['name'])); ?>" class="img-thumbnail mb-2" style="max-width: 200px; max-height: 200px;">
                            <?php else: ?>
                                <div class="text-center text-muted p-4 border mb-2">
                                    <i class="fas fa-image fa-4x"></i>
                                    <p class="mt-2">No image</p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-2">
                                <input type="file" class="form-control" id="image" name="image">
                                <small class="text-muted">Upload a new image to replace the current one</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="6"><?php echo htmlspecialchars(desanitize($product['description'] ?? '')); ?></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Product Categories</label>
                    <div class="row">
                        <?php foreach ($allCategories as $category): ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="category_<?php echo $category['id']; ?>" <?php echo in_array($category['id'], $productCategories) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="category_<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Additional Images</label>
                    <div class="row">
                        <?php if (count($productImages) > 0): ?>
                            <?php foreach ($productImages as $image): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <img src="<?php echo UPLOADS_URL . $image['image_path']; ?>" alt="Product Image" class="card-img-top" style="height: 150px; object-fit: contain;">
                                        <div class="card-body p-2 text-center">
                                            <a href="edit-product.php?id=<?php echo $productId; ?>&delete_image=1&image_id=<?php echo $image['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this image?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">No additional images for this product.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3">
                        <label for="additional_images" class="form-label">Add More Images</label>
                        <input type="file" class="form-control" id="additional_images" name="additional_images[]" multiple>
                        <small class="text-muted">You can select multiple files. Supported formats: JPG, PNG, GIF. Max size: 2MB per file.</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary" name="update_product">Update Product</button>
                    <a href="products.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
