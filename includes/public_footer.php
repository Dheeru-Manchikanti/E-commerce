    </div><!-- End of main-content -->

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>E-commerce Store</h5>
                    <p>Your one-stop shop for quality products at affordable prices.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="cart.php" class="text-white">Shopping Cart</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt me-2"></i> 123 Store Street, City Name</p>
                        <p><i class="fas fa-phone me-2"></i> (123) 456-7890</p>
                        <p><i class="fas fa-envelope me-2"></i> info@ecommerce-store.com</p>
                    </address>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?php echo date('Y'); ?> E-commerce Store. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a></li>
                        <li class="list-inline-item"><a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a></li>
                        <li class="list-inline-item"><a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JS -->
    <script src="assets/js/main.js"></script>
    
    <!-- Cart JS -->
    <script src="assets/js/cart.js"></script>
    
    <!-- Additional JS -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Page specific scripts -->
    <?php if (isset($pageScript)): ?>
        <script>
            <?php echo $pageScript; ?>
        </script>
    <?php endif; ?>
</body>
</html>
