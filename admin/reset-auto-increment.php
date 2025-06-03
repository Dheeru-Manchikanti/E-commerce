<?php
/**
 * Auto-increment ID reuse utility
 * This script helps reuse deleted IDs by resetting auto-increment values
 */

require_once '../includes/init.php';

/**
 * Reset auto-increment to reuse deleted IDs for a given table
 * @param string $table - Table name (products or categories)
 * @return array - Result with status and message
 */
function resetAutoIncrement($table) {
    global $db;
    
    try {
        // Validate table name for security
        $allowedTables = ['products', 'categories'];
        if (!in_array($table, $allowedTables)) {
            return ['status' => 'error', 'message' => 'Invalid table name'];
        }
        
        // Get all existing IDs
        $db->query("SELECT id FROM {$table} ORDER BY id");
        $existingIds = $db->resultSet();
        
        if (empty($existingIds)) {
            // No records, reset to 1
            $nextId = 1;
        } else {
            // Find the first gap in the sequence
            $nextId = 1;
            foreach ($existingIds as $row) {
                if ($row['id'] == $nextId) {
                    $nextId++;
                } else {
                    // Found a gap, use this ID
                    break;
                }
            }
        }
        
        // Reset auto-increment (cannot use parameter binding with ALTER TABLE)
        $sql = "ALTER TABLE {$table} AUTO_INCREMENT = " . intval($nextId);
        $db->query($sql);
        $db->execute();
        
        return [
            'status' => 'success', 
            'message' => "Auto-increment for {$table} reset to {$nextId}",
            'next_id' => $nextId
        ];
        
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get auto-increment status for a table
 * @param string $table - Table name
 * @return array - Current auto-increment info
 */
function getAutoIncrementStatus($table) {
    global $db;
    
    try {
        // Get current auto-increment value
        $db->query("SHOW TABLE STATUS LIKE :table");
        $db->bind(':table', $table);
        $status = $db->single();
        
        // Get existing IDs
        $db->query("SELECT id FROM {$table} ORDER BY id");
        $existingIds = $db->resultSet();
        
        // Find gaps
        $gaps = [];
        if (!empty($existingIds)) {
            $expectedId = 1;
            foreach ($existingIds as $row) {
                while ($expectedId < $row['id']) {
                    $gaps[] = $expectedId;
                    $expectedId++;
                }
                $expectedId = $row['id'] + 1;
            }
        }
        
        return [
            'table' => $table,
            'current_auto_increment' => $status['Auto_increment'],
            'total_records' => count($existingIds),
            'gaps' => $gaps,
            'next_available_id' => !empty($gaps) ? min($gaps) : (empty($existingIds) ? 1 : max(array_column($existingIds, 'id')) + 1)
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'reset':
            $table = $_POST['table'] ?? '';
            $result = resetAutoIncrement($table);
            echo json_encode($result);
            break;
            
        case 'status':
            $table = $_GET['table'] ?? '';
            $result = getAutoIncrementStatus($table);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
    exit;
}

// If not an AJAX request, show the interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto-Increment ID Reuse Utility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h2 class="text-center mb-4">Auto-Increment ID Reuse Utility</h2>
                <p class="text-muted text-center">This tool helps you reuse deleted IDs by resetting auto-increment values</p>
                
                <!-- Products Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Products Table</h5>
                    </div>
                    <div class="card-body">
                        <div id="products-status"></div>
                        <button class="btn btn-primary" onclick="resetAutoIncrement('products')">
                            Reset Products Auto-Increment
                        </button>
                        <button class="btn btn-outline-secondary" onclick="getStatus('products')">
                            Check Status
                        </button>
                    </div>
                </div>
                
                <!-- Categories Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Categories Table</h5>
                    </div>
                    <div class="card-body">
                        <div id="categories-status"></div>
                        <button class="btn btn-primary" onclick="resetAutoIncrement('categories')">
                            Reset Categories Auto-Increment
                        </button>
                        <button class="btn btn-outline-secondary" onclick="getStatus('categories')">
                            Check Status
                        </button>
                    </div>
                </div>
                
                <!-- Results -->
                <div id="results" class="mt-4"></div>
                
                <div class="text-center mt-4">
                    <a href="../admin/products.php" class="btn btn-secondary">Back to Products</a>
                    <a href="../admin/categories.php" class="btn btn-secondary">Back to Categories</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load initial status
        $(document).ready(function() {
            getStatus('products');
            getStatus('categories');
        });
        
        function getStatus(table) {
            $.get('?action=status&table=' + table, function(data) {
                let html = '';
                if (data.error) {
                    html = '<div class="alert alert-danger">Error: ' + data.error + '</div>';
                } else {
                    html = `
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Current Auto-Increment:</strong> ${data.current_auto_increment}<br>
                                <strong>Total Records:</strong> ${data.total_records}<br>
                                <strong>Next Available ID:</strong> ${data.next_available_id}
                            </div>
                            <div class="col-md-6">
                                <strong>Available IDs (gaps):</strong><br>
                                ${data.gaps.length > 0 ? data.gaps.join(', ') : 'None'}
                            </div>
                        </div>
                    `;
                }
                $('#' + table + '-status').html(html);
            });
        }
        
        function resetAutoIncrement(table) {
            if (confirm('Are you sure you want to reset the auto-increment for ' + table + '? This will make deleted IDs available for reuse.')) {
                $.post('?action=reset', {table: table}, function(data) {
                    let alertClass = data.status === 'success' ? 'alert-success' : 'alert-danger';
                    $('#results').html(`<div class="alert ${alertClass}">${data.message}</div>`);
                    
                    if (data.status === 'success') {
                        // Refresh status
                        getStatus(table);
                    }
                });
            }
        }
    </script>
</body>
</html>
