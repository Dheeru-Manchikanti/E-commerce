<?php
// Start session
session_start();

// Include database and functions
require_once 'includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page with return URL
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$userId = $_SESSION['user_id'];

// Check if ID is provided for editing
$addressId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $addressId > 0;

$address = [
    'id' => 0,
    'address_type' => 'both',
    'address' => '',
    'city' => '',
    'state' => '',
    'postal_code' => '',
    'country' => '',
    'is_default' => 0
];

if ($isEdit) {
    // Get address data for editing
    $db->query("SELECT * FROM user_addresses WHERE id = :id AND user_id = :user_id");
    $db->bind(':id', $addressId);
    $db->bind(':user_id', $userId);
    $addressData = $db->single();
    
    if (!$addressData) {
        setFlashMessage('error', 'Address not found or you do not have permission to edit it.', 'danger');
        header('Location: account.php#addresses');
        exit();
    }
    
    $address = $addressData;
}

$error = '';
$success = '';

// Process address form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $address_type = sanitize($_POST['address_type']);
        $street_address = sanitize($_POST['address']);
        $city = sanitize($_POST['city']);
        $state = sanitize($_POST['state']);
        $postal_code = sanitize($_POST['postal_code']);
        $country = sanitize($_POST['country']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // Validate form data
        $errors = [];
        
        if (empty($street_address)) {
            $errors[] = 'Street address is required.';
        }
        
        if (empty($city)) {
            $errors[] = 'City is required.';
        }
        
        if (empty($state)) {
            $errors[] = 'State/Province is required.';
        }
        
        if (empty($postal_code)) {
            $errors[] = 'Postal code is required.';
        }
        
        if (empty($country)) {
            $errors[] = 'Country is required.';
        }
        
        if (!in_array($address_type, ['shipping', 'billing', 'both'])) {
            $errors[] = 'Invalid address type.';
        }
        
        if (empty($errors)) {
            // If this is the default address, unset any other default addresses
            if ($is_default) {
                $db->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id");
                $db->bind(':user_id', $userId);
                $db->execute();
            }
            
            if ($isEdit) {
                // Update existing address
                $db->query("UPDATE user_addresses SET 
                           address_type = :address_type, 
                           address = :address, 
                           city = :city, 
                           state = :state, 
                           postal_code = :postal_code, 
                           country = :country, 
                           is_default = :is_default 
                           WHERE id = :id AND user_id = :user_id");
                $db->bind(':id', $addressId);
            } else {
                // Insert new address
                $db->query("INSERT INTO user_addresses 
                           (user_id, address_type, address, city, state, postal_code, country, is_default) 
                           VALUES 
                           (:user_id, :address_type, :address, :city, :state, :postal_code, :country, :is_default)");
            }
            
            $db->bind(':user_id', $userId);
            $db->bind(':address_type', $address_type);
            $db->bind(':address', $street_address);
            $db->bind(':city', $city);
            $db->bind(':state', $state);
            $db->bind(':postal_code', $postal_code);
            $db->bind(':country', $country);
            $db->bind(':is_default', $is_default);
            
            $result = $db->execute();
            
            if ($result) {
                setFlashMessage('success', 'Address ' . ($isEdit ? 'updated' : 'added') . ' successfully.', 'success');
                header('Location: account.php#addresses');
                exit();
            } else {
                $error = 'Failed to ' . ($isEdit ? 'update' : 'add') . ' address. Please try again.';
            }
        } else {
            $error = implode('<br>', $errors);
            
            // Preserve form data
            $address['address_type'] = $address_type;
            $address['address'] = $street_address;
            $address['city'] = $city;
            $address['state'] = $state;
            $address['postal_code'] = $postal_code;
            $address['country'] = $country;
            $address['is_default'] = $is_default;
        }
    }
}

// Process deletion
if (isset($_POST['delete']) && $isEdit) {
    // Validate CSRF token
    if (!isset($_POST['delete_csrf_token']) || !verifyCSRFToken($_POST['delete_csrf_token'])) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $db->query("DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id");
        $db->bind(':id', $addressId);
        $db->bind(':user_id', $userId);
        
        $result = $db->execute();
        
        if ($result) {
            setFlashMessage('success', 'Address deleted successfully.', 'success');
            header('Location: account.php#addresses');
            exit();
        } else {
            $error = 'Failed to delete address. Please try again.';
        }
    }
}

// Set page title
$pageTitle = ($isEdit ? 'Edit' : 'Add') . ' Address';

// Include header
include('includes/public_header.php');
?>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="account.php">My Account</a></li>
            <li class="breadcrumb-item"><a href="account.php#addresses">Addresses</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $isEdit ? 'Edit' : 'Add'; ?> Address</li>
        </ol>
    </nav>
    
    <div class="card shadow">
        <div class="card-header">
            <h5 class="mb-0"><?php echo $isEdit ? 'Edit' : 'Add'; ?> Address</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo $isEdit ? 'address-edit.php?id=' . $addressId : 'address-edit.php'; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label for="address_type" class="form-label">Address Type *</label>
                    <select id="address_type" name="address_type" class="form-select" required>
                        <option value="both" <?php echo $address['address_type'] === 'both' ? 'selected' : ''; ?>>Both Shipping & Billing</option>
                        <option value="shipping" <?php echo $address['address_type'] === 'shipping' ? 'selected' : ''; ?>>Shipping Only</option>
                        <option value="billing" <?php echo $address['address_type'] === 'billing' ? 'selected' : ''; ?>>Billing Only</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Street Address *</label>
                    <textarea id="address" name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($address['address']); ?></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="city" class="form-label">City *</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?php echo htmlspecialchars($address['city']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="state" class="form-label">State / Province *</label>
                        <input type="text" id="state" name="state" class="form-control" value="<?php echo htmlspecialchars($address['state']); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="postal_code" class="form-label">Postal Code *</label>
                        <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($address['postal_code']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="country" class="form-label">Country *</label>
                        <input type="text" id="country" name="country" class="form-control" value="<?php echo htmlspecialchars($address['country']); ?>" required>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_default" name="is_default" <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_default">Set as default address</label>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Update' : 'Add'; ?> Address</button>
                    
                    <a href="account.php#addresses" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            
            <?php if ($isEdit): ?>
                <hr class="my-4">
                
                <form method="post" action="address-edit.php?id=<?php echo $addressId; ?>" class="mt-3" onsubmit="return confirm('Are you sure you want to delete this address?');">
                    <input type="hidden" name="delete_csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" name="delete" class="btn btn-danger">Delete Address</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('includes/public_footer.php'); ?>
