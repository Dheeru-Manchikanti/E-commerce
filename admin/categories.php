<?php

session_start();
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/init.php';

if (isset($_GET['update']) && $_GET['update'] === 'success') {
    setFlashMessage('success', 'Category updated successfully.');
    header('Location: categories.php');
    exit();
}

if (isset($_GET['deletion']) && $_GET['deletion'] === 'success') {
    setFlashMessage('success', 'Category deleted successfully.');
    header('Location: categories.php');
    exit();
}


$db->query("SELECT c.*, p.name as parent_name 
           FROM categories c 
           LEFT JOIN categories p ON c.parent_id = p.id 
           ORDER BY COALESCE(c.parent_id, c.id), c.name");
$categories = $db->resultSet();


$db->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
$parentCategories = $db->resultSet();


$formErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {

    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $formErrors[] = 'Invalid form submission.';
    } else {

        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $status = sanitize($_POST['status']);
        

        if (empty($name)) {
            $formErrors[] = 'Category name is required.';
        }
        

        if ($parent_id !== null) {
            $db->query("SELECT id FROM categories WHERE id = :id");
            $db->bind(':id', $parent_id);
            if ($db->rowCount() === 0) {
                $formErrors[] = 'Selected parent category does not exist.';
            }
        }
        

        if (empty($formErrors)) {
            try {

                resetAutoIncrementForReuse('categories');
                
                $db->query("INSERT INTO categories (name, description, parent_id, status) 
                           VALUES (:name, :description, :parent_id, :status)");
                $db->bind(':name', $name);
                $db->bind(':description', $description);
                $db->bind(':parent_id', $parent_id);
                $db->bind(':status', $status);
                $db->execute();
                
                setFlashMessage('success', 'Category added successfully.');
                

                header('Location: categories.php');
                exit();
                

            } catch (Exception $e) {
                setFlashMessage('error', 'Error adding category: ' . $e->getMessage());
            }
        }
    }
}


if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $categoryId = (int)$_GET['id'];
    
    $db->query("SELECT COUNT(*) as product_count FROM product_categories WHERE category_id = :category_id");
    $db->bind(':category_id', $categoryId);
    $productCount = $db->single()['product_count'];
    

    $db->query("SELECT COUNT(*) as child_count FROM categories WHERE parent_id = :parent_id");
    $db->bind(':parent_id', $categoryId);
    $childCount = $db->single()['child_count'];
    
    if ($productCount > 0) {
        setFlashMessage('error', 'Cannot delete category: It has ' . $productCount . ' associated products.', 'danger');
    } elseif ($childCount > 0) {
        setFlashMessage('error', 'Cannot delete category: It has ' . $childCount . ' child categories.', 'danger');
    } else {
        try {
            $db->query("DELETE FROM categories WHERE id = :id");
            $db->bind(':id', $categoryId);
            $db->execute();
            
            setFlashMessage('success', 'Category deleted successfully.', 'success');
            

            $db->query("SELECT c.*, p.name as parent_name 
                       FROM categories c 
                       LEFT JOIN categories p ON c.parent_id = p.id 
                       ORDER BY COALESCE(c.parent_id, c.id), c.name");
            $categories = $db->resultSet();
            

            $db->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
            $parentCategories = $db->resultSet();
        } catch (Exception $e) {
            setFlashMessage('error', 'Error deleting category: ' . $e->getMessage(), 'danger');
        }
    }
    
    header('Location: categories.php');
    exit();
}


$pageTitle = 'Categories';

$additionalJS = [
    '../assets/js/categories.js'
];
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Product Categories</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fas fa-plus"></i> Add New Category
        </button>
    </div>
    
    <?php if (hasFlashMessage('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo getFlashMessage('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (hasFlashMessage('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo getFlashMessage('error'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Categories Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Categories List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="categoriesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Parent Category</th>
                            <th>Status</th>
                            <th>Products</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($categories) > 0): ?>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo $category['id']; ?></td>
                                    <td>
                                        <?php if ($category['parent_id']): ?>
                                            <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </td>
                                    <td>
                                        <?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '<span class="text-muted">None</span>'; ?>
                                    </td>
                                    <td>
                                        <?php if ($category['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Get product count for this category
                                        $db->query("SELECT COUNT(*) as count FROM product_categories WHERE category_id = :category_id");
                                        $db->bind(':category_id', $category['id']);
                                        $productCount = $db->single()['count'];
                                        echo $productCount;
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-info edit-category" data-id="<?php echo $category['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-category" data-id="<?php echo $category['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No categories found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="categories.php" id="categoryForm">
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
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="parent_id" class="form-label">Parent Category</label>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">None (Top Level)</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>" <?php echo (isset($parent_id) && $parent_id == $parent['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($parent['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select a parent category to create a subcategory</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active" <?php echo (!isset($status) || $status === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($status) && $status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="add_category">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="mb-3">
                    <label for="edit_name" class="form-label">Category Name *</label>
                    <input type="text" class="form-control" id="edit_name" name="name" required>
                </div>
                
                <div class="mb-3">
                    <label for="edit_parent_id" class="form-label">Parent Category</label>
                    <select class="form-control" id="edit_parent_id" name="parent_id">
                        <option value="">None (Top Level)</option>
                        <?php foreach ($parentCategories as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>">
                                <?php echo htmlspecialchars($parent['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Select a parent category to create a subcategory</small>
                </div>
                
                <div class="mb-3">
                    <label for="edit_description" class="form-label">Description</label>
                    <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="edit_status" class="form-label">Status</label>
                    <select class="form-control" id="edit_status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCategoryChanges">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
