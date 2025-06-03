<?php
// Bulk Image upload is yet to be implemented


session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/init.php';
$db->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$parentCategories = $db->resultSet();

$allCategories = [];
foreach ($parentCategories as $parent) {
    $allCategories[] = $parent;
    $db->query("SELECT * FROM categories WHERE parent_id = :parent_id ORDER BY name");
    $db->bind(':parent_id', $parent['id']);
    $children = $db->resultSet();
    
    foreach ($children as $child) {
        $child['name'] = '— ' . $child['name'];
        $allCategories[] = $child;
    }
}

$formErrors = [];
$formSuccess = '';
$processedCount = 0;
$failedCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $formErrors[] = 'Invalid form submission.';
    } else {
        // Process bulk image upload
        if (isset($_POST['bulk_upload']) && isset($_FILES['images'])) {
            $uploadMode = $_POST['upload_mode'] ?? 'all'; // all, category, specific
            $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
            $productIds = isset($_POST['product_ids']) ? explode(',', $_POST['product_ids']) : [];
            $asMainImage = isset($_POST['as_main_image']);
            $addAsAdditional = isset($_POST['add_as_additional']);
            
            // Validate inputs
            if ($uploadMode === 'category' && empty($categoryId)) {
                $formErrors[] = 'Please select a category.';
            } elseif ($uploadMode === 'specific' && empty($productIds)) {
                $formErrors[] = 'Please enter product IDs.';
            }
            
            if (!$asMainImage && !$addAsAdditional) {
                $formErrors[] = 'Please select at least one option: "Set as main image" or "Add as additional image".';
            }
            
            // If no errors, proceed with upload
            if (empty($formErrors)) {
                // Get the list of products based on the selected mode
                $products = [];
                
                if ($uploadMode === 'all') {
                    $db->query("SELECT id FROM products ORDER BY id");
                    $products = $db->resultSet();
                } elseif ($uploadMode === 'category') {
                    $db->query("SELECT DISTINCT p.id 
                                FROM products p 
                                JOIN product_categories pc ON p.id = pc.product_id 
                                WHERE pc.category_id = :category_id");
                    $db->bind(':category_id', $categoryId);
                    $products = $db->resultSet();
                } elseif ($uploadMode === 'specific') {
                    foreach ($productIds as $id) {
                        $id = (int)trim($id);
                        if ($id > 0) {
                            $products[] = ['id' => $id];
                        }
                    }
                }
                
                // Check if we have products and images
                if (empty($products)) {
                    $formErrors[] = 'No products found with the selected criteria.';
                } elseif (count($_FILES['images']['name']) === 0) {
                    $formErrors[] = 'Please select at least one image to upload.';
                } else {
                    try {
                        // Display debug information
                        error_log('Starting bulk image upload process');
                        error_log('UPLOADS_DIR: ' . UPLOADS_DIR);
                        error_log('UPLOADS_DIR exists: ' . (file_exists(UPLOADS_DIR) ? 'Yes' : 'No'));
                        error_log('UPLOADS_DIR writable: ' . (is_writable(UPLOADS_DIR) ? 'Yes' : 'No'));
                        
                        $db->beginTransaction();
                        
                        // Create an array of uploaded images
                        $uploadedImages = [];
                        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                            error_log('Processing image ' . ($i+1) . ': ' . $_FILES['images']['name'][$i]);
                            
                            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                                $file = [
                                    'name' => $_FILES['images']['name'][$i],
                                    'type' => $_FILES['images']['type'][$i],
                                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                                    'error' => $_FILES['images']['error'][$i],
                                    'size' => $_FILES['images']['size'][$i]
                                ];
                                
                                error_log('File details: ' . json_encode($file));
                                
                                $imagePath = uploadFile($file, UPLOADS_DIR);
                                if ($imagePath) {
                                    $uploadedImages[] = $imagePath;
                                    error_log('Successfully uploaded: ' . $imagePath);
                                } else {
                                    error_log('Failed to upload image: ' . $_FILES['images']['name'][$i]);
                                }
                            } else {
                                error_log('Upload error for image ' . ($i+1) . ': ' . $_FILES['images']['error'][$i]);
                            }
                        }
                        
                        if (empty($uploadedImages)) {
                            throw new Exception('Failed to upload any images.');
                        }
                        
                        // Distribute images among products
                        foreach ($products as $productIndex => $product) {
                            $productId = $product['id'];
                            
                            // Calculate which image index to use for this product
                            $imageIndex = $productIndex % count($uploadedImages);
                            $imagePath = $uploadedImages[$imageIndex];
                            
                            // Check if the product exists
                            $db->query("SELECT id FROM products WHERE id = :id");
                            $db->bind(':id', $productId);
                            if ($db->rowCount() === 0) {
                                continue; // Skip this product if it doesn't exist
                            }
                            
                            // Update the main image if requested
                            if ($asMainImage) {
                                // Get the current main image
                                $db->query("SELECT image_main FROM products WHERE id = :id");
                                $db->bind(':id', $productId);
                                $currentImage = $db->single();
                                
                                // Update the main image
                                $db->query("UPDATE products SET image_main = :image_main WHERE id = :id");
                                $db->bind(':image_main', $imagePath);
                                $db->bind(':id', $productId);
                                $db->execute();
                                
                                $processedCount++;
                            }
                            
                            // Add as additional image if requested
                            if ($addAsAdditional) {
                                // Get current highest sort order
                                $db->query("SELECT MAX(sort_order) as max_order FROM product_images WHERE product_id = :product_id");
                                $db->bind(':product_id', $productId);
                                $maxOrderResult = $db->single();
                                $sortOrder = $maxOrderResult['max_order'] ? $maxOrderResult['max_order'] + 1 : 1;
                                
                                $db->query("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (:product_id, :image_path, :sort_order)");
                                $db->bind(':product_id', $productId);
                                $db->bind(':image_path', $imagePath);
                                $db->bind(':sort_order', $sortOrder);
                                $db->execute();
                                
                                $processedCount++;
                            }
                        }
                        
                        $db->commit();
                        $formSuccess = "Images uploaded successfully! Updated {$processedCount} product records.";
                    } catch (Exception $e) {
                        if ($db->inTransaction()) {
                            $db->rollBack();
                        }
                        $formErrors[] = 'Error processing bulk upload: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Page title
$pageTitle = 'Bulk Image Upload';

// Additional JS
$additionalJS = [
    '../assets/js/products.js'
];
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bulk Image Upload</h1>
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
    
    <!-- <?php if (error_reporting() > 0): // Only show in debug mode ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Upload Directory Information</h6>
            </div>
            <div class="card-body">
                <p><strong>Upload Directory:</strong> <?php echo UPLOADS_DIR; ?></p>
                <p><strong>Upload URL:</strong> <?php echo UPLOADS_URL; ?></p>
                <p><strong>Directory exists:</strong> <?php echo file_exists(UPLOADS_DIR) ? 'Yes' : 'No'; ?></p>
                <p><strong>Directory writable:</strong> <?php echo is_writable(UPLOADS_DIR) ? 'Yes' : 'No'; ?></p>
                <p><strong>Directory permissions:</strong> <?php echo file_exists(UPLOADS_DIR) ? substr(sprintf('%o', fileperms(UPLOADS_DIR)), -4) : 'N/A'; ?></p>
                <p><strong>PHP user:</strong> <?php echo function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown (Windows or posix extension not available)'; ?></p>
                <p><strong>PHP version:</strong> <?php echo PHP_VERSION; ?></p>
                <p><strong>upload_max_filesize:</strong> <?php echo ini_get('upload_max_filesize'); ?></p>
                <p><strong>post_max_size:</strong> <?php echo ini_get('post_max_size'); ?></p>
            </div>
        </div>
    <?php endif; ?> -->
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Upload Images to Multiple Products</h6>
        </div>
        <div class="card-body">
            <!-- <div class="alert alert-info">
                <p><strong>Note:</strong> This tool allows you to upload images to multiple products at once. The images will be distributed evenly among the selected products.</p>
                <p>Image Sources: <a href="https://unsplash.com/collections/8172554/e-commerce-products" target="_blank">Unsplash</a>, 
                <a href="https://www.pexels.com/search/product/" target="_blank">Pexels</a>, 
                <a href="https://pixabay.com/images/search/product/" target="_blank">Pixabay</a>, or 
                <a href="https://placehold.co/" target="_blank">Product Placeholder</a>.</p>
            </div> -->
            
            <form method="post" action="bulk-image-upload.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-4">
                    <label class="form-label">Upload Mode</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="upload_mode" id="mode_all" value="all" checked>
                        <label class="form-check-label" for="mode_all">
                            Apply to All Products
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="upload_mode" id="mode_category" value="category">
                        <label class="form-check-label" for="mode_category">
                            Apply to Specific Category
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="upload_mode" id="mode_specific" value="specific">
                        <label class="form-check-label" for="mode_specific">
                            Apply to Specific Products (by ID)
                        </label>
                    </div>
                </div>
                

                <div class="mb-4" id="category_section" style="display: none;">
                    <label for="category_id" class="form-label">Select Category</label>
                    <select class="form-control" id="category_id" name="category_id">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($allCategories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                

                <div class="mb-4" id="products_section" style="display: none;">
                    <label for="product_ids" class="form-label">Product IDs</label>
                    <input type="text" class="form-control" id="product_ids" name="product_ids" placeholder="Enter product IDs separated by commas (e.g., 1,2,3)">
                </div>
                

                <div class="mb-4">
                    <label class="form-label">Image Options</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="as_main_image" id="as_main_image" checked>
                        <label class="form-check-label" for="as_main_image">
                            Set as main product image (replaces current main image)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="add_as_additional" id="add_as_additional">
                        <label class="form-check-label" for="add_as_additional">
                            Add as additional product image (keeps current images)
                        </label>
                    </div>
                </div>
                

                <div class="mb-4">
                    <label for="images" class="form-label">Select Images</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/gif">
                    <small class="text-muted">You can select multiple files. Supported formats: JPG, PNG, GIF. Max size: 2MB per file.</small>
                    <div class="mt-2" id="imagePreviewContainer"></div>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary" name="bulk_upload">Upload Images</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- <script>
document.addEventListener('DOMContentLoaded', function() {

    const modeAll = document.getElementById('mode_all');
    const modeCategory = document.getElementById('mode_category');
    const modeSpecific = document.getElementById('mode_specific');
    const categorySection = document.getElementById('category_section');
    const productsSection = document.getElementById('products_section');
    
    function updateSections() {
        if (modeCategory.checked) {
            categorySection.style.display = 'block';
            productsSection.style.display = 'none';
        } else if (modeSpecific.checked) {
            categorySection.style.display = 'none';
            productsSection.style.display = 'block';
        } else {
            categorySection.style.display = 'none';
            productsSection.style.display = 'none';
        }
    }
    
    modeAll.addEventListener('change', updateSections);
    modeCategory.addEventListener('change', updateSections);
    modeSpecific.addEventListener('change', updateSections);
    
    // Image preview
    const imagesInput = document.getElementById('images');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    
    imagesInput.addEventListener('change', function() {
        imagePreviewContainer.innerHTML = '';
        
        if (this.files.length > 0) {
            const previewRow = document.createElement('div');
            previewRow.className = 'row';
            
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                
                if (!file.type.match('image.*')) {
                    continue;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewCol = document.createElement('div');
                    previewCol.className = 'col-md-2 mt-2';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.maxHeight = '150px';
                    
                    previewCol.appendChild(img);
                    previewRow.appendChild(previewCol);
                };
                
                reader.readAsDataURL(file);
            }
            
            imagePreviewContainer.appendChild(previewRow);
        }
    });
    
    updateSections();
});
</script> -->

<?php include 'includes/footer.php'; ?>
