<?php
/**
 * Helper functions for the application
 */

/**
 * Sanitize user input
 * 
 * @param string $input - The input to sanitize
 * @return string - Sanitized input
 */
function sanitize($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Redirect to a specific page
 * 
 * @param string $url - The URL to redirect to
 */
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Generate a random string
 * 
 * @param int $length - Length of the string to generate
 * @return string - Random alphanumeric string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Generate a unique order number
 * 
 * @return string - Order number in format ORD-YYYYMMDD-XXXXX
 */
function generateOrderNumber() {
    $date = date('Ymd');
    $random = strtoupper(generateRandomString(5));
    return "ORD-{$date}-{$random}";
}

/**
 * Format price with currency symbol
 * 
 * @param float $price - Price to format
 * @return string - Formatted price
 */
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

/**
 * Generate CSRF token and store in session
 * 
 * @return string - CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token - Token to verify
 * @return bool - True if token is valid
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Display flash message
 * 
 * @param string $name - Name of the message
 * @param string $message - The message text
 * @param string $class - Bootstrap alert class
 */
function setFlashMessage($name, $message, $class = 'success') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = array();
    }
    $_SESSION['flash_messages'][$name] = array(
        'message' => $message,
        'class' => $class
    );
}

/**
 * Display flash message and remove it from session
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $name => $details) {
            echo '<div class="alert alert-' . $details['class'] . ' alert-dismissible fade show" role="alert">';
            echo $details['message'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
        unset($_SESSION['flash_messages']);
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool - True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['admin_user_id']);
}

/**
 * Upload file with validation
 * 
 * @param array $file - The $_FILES array element
 * @param string $destinationFolder - Folder to upload to
 * @param array $allowedTypes - Allowed MIME types
 * @param int $maxSize - Maximum file size in bytes
 * @return string|bool - Filename if successful, false if failed
 */
function uploadFile($file, $destinationFolder = UPLOADS_DIR, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 2097152) {
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Validate file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $fileType = $finfo->file($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        return false;
    }
    
    // Create a safe filename
    $filename = basename($file['name']);
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
    $filename = time() . '_' . $filename;
    
    // Create the destination folder if it doesn't exist
    if (!file_exists($destinationFolder)) {
        mkdir($destinationFolder, 0755, true);
    }
    
    // Move the uploaded file to the destination folder
    if (move_uploaded_file($file['tmp_name'], $destinationFolder . $filename)) {
        return $filename;
    }
    
    return false;
}

/**
 * Pagination helper
 * 
 * @param int $totalItems - Total number of items
 * @param int $itemsPerPage - Items per page
 * @param int $currentPage - Current page
 * @param string $urlPattern - URL pattern for pagination links
 * @return array - Pagination data
 */
function paginate($totalItems, $itemsPerPage = 10, $currentPage = 1, $urlPattern = '?page=(:num)') {
    // Calculate total pages
    $totalPages = ceil($totalItems / $itemsPerPage);
    
    // Ensure current page is valid
    if ($currentPage < 1) {
        $currentPage = 1;
    } elseif ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }
    
    // Calculate start and end items
    $startItem = ($currentPage - 1) * $itemsPerPage;
    $endItem = $startItem + $itemsPerPage;
    if ($endItem > $totalItems) {
        $endItem = $totalItems;
    }
    
    // Build pagination array
    $pagination = array(
        'currentPage' => $currentPage,
        'totalPages' => $totalPages,
        'itemsPerPage' => $itemsPerPage,
        'totalItems' => $totalItems,
        'startItem' => $startItem,
        'endItem' => $endItem
    );
    
    // Add pagination links
    $pagination['links'] = array();
    
    // Previous page link
    if ($currentPage > 1) {
        $pagination['links']['prev'] = str_replace('(:num)', $currentPage - 1, $urlPattern);
    } else {
        $pagination['links']['prev'] = null;
    }
    
    // Next page link
    if ($currentPage < $totalPages) {
        $pagination['links']['next'] = str_replace('(:num)', $currentPage + 1, $urlPattern);
    } else {
        $pagination['links']['next'] = null;
    }
    
    // Page number links
    $pagination['links']['pages'] = array();
    for ($i = 1; $i <= $totalPages; $i++) {
        $pagination['links']['pages'][$i] = str_replace('(:num)', $i, $urlPattern);
    }
    
    return $pagination;
}
