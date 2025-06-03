$(document).ready(function () {
  const csrfToken = $('meta[name="csrf-token"]').attr("content");

  $(document).on("click", ".add-to-cart", function (e) {
    e.preventDefault();
    const productId = $(this).data("id");
    const quantity = $("#quantity").length ? $("#quantity").val() : 1;

    $.ajax({
      url: "api/cart.php?action=add",
      type: "POST",
      headers: {
        "X-CSRF-Token": csrfToken,
      },
      data: {
        product_id: productId,
        quantity: quantity,
        csrf_token: csrfToken,
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          $("#cartCount").text(response.cartCount);

          const alert = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <div class="mt-2">
                                <a href="cart.php" class="btn btn-sm btn-outline-success">View Cart</a>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" data-bs-dismiss="alert">Continue Shopping</button>
                            </div>
                        </div>
                    `;
          $("#cartAlerts").html(alert);

          // Scroll to alerts
          $("html, body").animate(
            {
              scrollTop: $("#cartAlerts").offset().top - 100,
            },
            500
          );

          setTimeout(function () {
            $(".alert").alert("close");
          }, 5000);
        } else {
          // Show error message
          const alert = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
          $("#cartAlerts").html(alert);

          // Scroll to alerts
          $("html, body").animate(
            {
              scrollTop: $("#cartAlerts").offset().top - 100,
            },
            500
          );
        }
      },
      error: function () {
        // Show general error message
        const alert = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Error adding product to cart. Please try again.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
        $("#cartAlerts").html(alert);
      },
    });
  });

  // Update cart item quantity
  $(".update-cart-item").on("change", function () {
    const itemId = $(this).data("id");
    const quantity = $(this).val();

    if (quantity < 1) {
      alert("Quantity must be at least 1");
      $(this).val(1);
      return;
    }

    $.ajax({
      url: "api/cart.php?action=update",
      type: "POST",
      data: {
        item_id: itemId,
        quantity: quantity,
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          // Update cart count and totals
          $("#cartCount").text(response.cartCount);
          $("#itemTotal_" + itemId).text(
            "₹" + parseFloat(response.itemTotal).toFixed(2)
          );
          $("#cartSubtotal").text(
            "₹" + parseFloat(response.subtotal).toFixed(2)
          );
          $("#cartTotal").text("₹" + parseFloat(response.total).toFixed(2));

          // Show success message
          const alert = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
          $("#cartAlerts").html(alert);

          // Auto-dismiss after 3 seconds
          setTimeout(function () {
            $(".alert").alert("close");
          }, 3000);
        } else {
          alert("Error: " + response.message);
        }
      },
      error: function () {
        alert("Error updating cart item");
      },
    });
  });

  // Remove item from cart
  $(".remove-cart-item").on("click", function (e) {
    e.preventDefault();
    if (confirm("Are you sure you want to remove this item from your cart?")) {
      const itemId = $(this).data("id");

      $.ajax({
        url: "api/cart.php?action=remove",
        type: "POST",
        data: {
          item_id: itemId,
        },
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            // Update cart count
            $("#cartCount").text(response.cartCount);

            // Remove item from DOM
            $("#cartItem_" + itemId).fadeOut(300, function () {
              $(this).remove();

              // Update totals
              $("#cartSubtotal").text(
                "₹" + parseFloat(response.subtotal).toFixed(2)
              );
              $("#cartTotal").text("₹" + parseFloat(response.total).toFixed(2));

              // If cart is empty, show message
              if (response.cartCount === 0) {
                $("#cartTable").remove();
                $("#cartSummary").remove();
                $("#cartContainer").html(
                  '<div class="alert alert-info">Your cart is empty. <a href="index.php">Continue shopping</a></div>'
                );
              }
            });

            // Show success message
            const alert = `
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                ${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
            $("#cartAlerts").html(alert);

            // Auto-dismiss after 3 seconds
            setTimeout(function () {
              $(".alert").alert("close");
            }, 3000);
          } else {
            alert("Error: " + response.message);
          }
        },
        error: function () {
          alert("Error removing item from cart");
        },
      });
    }
  });

  // Checkout form validation
  $("#checkoutForm").on("submit", function (e) {
    // Basic form validation
    const email = $("#email").val();
    const firstName = $("#first_name").val();
    const lastName = $("#last_name").val();
    const address = $("#address").val();
    const city = $("#city").val();
    const postalCode = $("#postal_code").val();
    const country = $("#country").val();

    // Validate email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      e.preventDefault();
      alert("Please enter a valid email address");
      $("#email").focus();
      return false;
    }

    // Check required fields
    if (
      !firstName ||
      !lastName ||
      !address ||
      !city ||
      !postalCode ||
      !country
    ) {
      e.preventDefault();
      alert("Please fill out all required fields");
      return false;
    }

    return true;
  });

  // Same as shipping address checkbox
  $("#same_as_shipping").on("change", function () {
    if ($(this).is(":checked")) {
      // Copy shipping address to billing address
      $("#billing_address").val($("#address").val());
      $("#billing_city").val($("#city").val());
      $("#billing_state").val($("#state").val());
      $("#billing_postal_code").val($("#postal_code").val());
      $("#billing_country").val($("#country").val());

      // Disable billing address fields
      $("#billingAddressFields input, #billingAddressFields select").prop(
        "disabled",
        true
      );
    } else {
      // Enable billing address fields
      $("#billingAddressFields input, #billingAddressFields select").prop(
        "disabled",
        false
      );
    }
  });

  // Product image gallery
  $(".product-thumbnail").on("click", function () {
    const mainImage = $("#mainProductImage");
    const newSrc = $(this).data("src");

    mainImage.fadeOut(300, function () {
      mainImage.attr("src", newSrc).fadeIn(300);
    });
  });

  // Live search with autocomplete
  $("#searchInput").on("keyup", function () {
    const query = $(this).val();

    if (query.length < 3) {
      $("#searchResults").empty().hide();
      return;
    }

    $.ajax({
      url: "api/search.php?action=live_search",
      type: "GET",
      data: { query: query },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const results = response.data;
          const resultsContainer = $("#searchResults");
          resultsContainer.empty();

          if (results.length > 0) {
            results.forEach(function (product) {
              const resultItem = `
                                <a href="product.php?id=${
                                  product.id
                                }" class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        <img src="uploads/${
                                          product.image_main || "no-image.jpg"
                                        }" alt="${
                product.name
              }" class="img-thumbnail mr-3" style="width: 50px; height: 50px; object-fit: cover;">
                                        <div class="ml-3">
                                            <h6 class="mb-0">${
                                              product.name
                                            }</h6>
                                            <small>${formatPrice(
                                              product.price
                                            )}</small>
                                        </div>
                                    </div>
                                </a>
                            `;
              resultsContainer.append(resultItem);
            });

            resultsContainer.show();
          } else {
            resultsContainer.append(
              '<div class="list-group-item">No products found</div>'
            );
            resultsContainer.show();
          }
        }
      },
    });
  });

  // Hide search results when clicking outside
  $(document).on("click", function (e) {
    if (!$(e.target).closest("#searchContainer").length) {
      $("#searchResults").hide();
    }
  });

  // Helper function to format price
  function formatPrice(price) {
    return "₹" + parseFloat(price).toFixed(2);
  }
});
