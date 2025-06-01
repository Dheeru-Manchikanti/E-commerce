<?php
// Force a cache refresh by sending appropriate headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clear Cache - Ecommerce System</title>
    
    <!-- Force reload of CSS and JS -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            padding-top: 40px;
            background-color: #f8f9fa;
        }
        
        .refresh-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .refresh-icon {
            font-size: 4rem;
            color: #4e73df;
            animation: spin 2s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="refresh-container text-center">
            <i class="fas fa-sync-alt refresh-icon mb-4"></i>
            
            <h2>Browser Cache Cleared</h2>
            <p class="lead">We've updated some files in our system.</p>
            
            <hr>
            
            <div class="alert alert-info">
                <p><strong>If you're still seeing outdated content:</strong></p>
                <p>Try one of these methods to clear your browser cache:</p>
                <ul class="text-start">
                    <li>Press <kbd>Ctrl+F5</kbd> or <kbd>Cmd+Shift+R</kbd> to force refresh</li>
                    <li>Open your browser in incognito/private browsing mode</li>
                    <li>Clear your browser history from your browser settings</li>
                </ul>
            </div>
            
            <div class="mt-4">
                <a href="profile.php" class="btn btn-primary">Go to New Profile Page</a>
                <a href="account.php" class="btn btn-outline-secondary ms-2">Go to Dashboard</a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
