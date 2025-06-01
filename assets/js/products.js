/**
 * Products management JavaScript file
 */
$(document).ready(function () {
  // DataTable initialization for products list
  if ($("#productsTable").length) {
    $("#productsTable").DataTable({
      order: [[0, "desc"]],
      pageLength: 10,
    });
  }

  // Product image preview
  $("#product_image").on("change", function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        $("#imagePreview").attr("src", e.target.result).show();
      };
      reader.readAsDataURL(file);
    }
  });

  // Handle bulk action checkbox
  $("#selectAll").on("change", function () {
    $(".product-checkbox").prop("checked", $(this).prop("checked"));
    updateBulkActionButton();
  });

  $(".product-checkbox").on("change", function () {
    updateBulkActionButton();
    // If any checkbox is unchecked, uncheck "select all" as well
    if (!$(this).prop("checked")) {
      $("#selectAll").prop("checked", false);
    }
  });

  // Update bulk action button state
  function updateBulkActionButton() {
    const checkedCount = $(".product-checkbox:checked").length;
    if (checkedCount > 0) {
      $("#bulkActionButton").prop("disabled", false);
      $("#selectedCount").text(checkedCount);
    } else {
      $("#bulkActionButton").prop("disabled", true);
      $("#selectedCount").text("0");
    }
  }

  // Product form validation
  $("#productForm").on("submit", function (e) {
    const name = $("#name").val();
    const price = $("#price").val();

    if (!name || name.trim() === "") {
      e.preventDefault();
      alert("Product name is required");
      $("#name").focus();
      return false;
    }

    if (!price || isNaN(parseFloat(price)) || parseFloat(price) <= 0) {
      e.preventDefault();
      alert("Valid price is required");
      $("#price").focus();
      return false;
    }

    return true;
  });

  // Inline editing of product
  $(".edit-product-inline").on("click", function (e) {
    e.preventDefault();
    const productId = $(this).data("id");

    // AJAX call to get product details
    $.ajax({
      url: "../api/products.php?action=get&id=" + productId,
      type: "GET",
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const product = response.data;

          // Fill the modal with product data
          $("#editProductModal").find("#edit_id").val(product.id);
          $("#editProductModal").find("#edit_name").val(product.name);
          $("#editProductModal")
            .find("#edit_description")
            .val(product.description);
          $("#editProductModal").find("#edit_price").val(product.price);
          $("#editProductModal")
            .find("#edit_sale_price")
            .val(product.sale_price);
          $("#editProductModal")
            .find("#edit_stock_quantity")
            .val(product.stock_quantity);
          $("#editProductModal").find("#edit_sku").val(product.sku);
          $("#editProductModal").find("#edit_status").val(product.status);
          $("#editProductModal")
            .find("#edit_featured")
            .prop("checked", product.featured == 1);

          if (product.image_main) {
            $("#editProductModal")
              .find("#currentImage")
              .attr("src", "../uploads/" + product.image_main)
              .show();
          } else {
            $("#editProductModal").find("#currentImage").hide();
          }

          // Show categories
          if (product.categories) {
            product.categories.forEach(function (categoryId) {
              $("#editProductModal")
                .find("#edit_category_" + categoryId)
                .prop("checked", true);
            });
          }

          // Show the modal
          $("#editProductModal").modal("show");
        } else {
          alert("Error: " + response.message);
        }
      },
      error: function () {
        alert("Error fetching product details");
      },
    });
  });

  // Save product changes
  $("#saveProductChanges").on("click", function () {
    const form = $("#editProductForm")[0];
    const formData = new FormData(form);

    $.ajax({
      url: "../api/products.php?action=update",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      success: function (response) {
        const result = JSON.parse(response);
        if (result.status === "success") {
          $("#editProductModal").modal("hide");
          alert("Product updated successfully");
          window.location.reload();
        } else {
          alert("Error: " + result.message);
        }
      },
      error: function () {
        alert("Error updating product");
      },
    });
  });

  // Delete product
  $(".delete-product").on("click", function (e) {
    e.preventDefault();
    if (
      confirm(
        "Are you sure you want to delete this product? This cannot be undone."
      )
    ) {
      const productId = $(this).data("id");

      $.ajax({
        url: "../api/products.php?action=delete",
        type: "POST",
        data: { id: productId },
        success: function (response) {
          const result = JSON.parse(response);
          if (result.status === "success") {
            alert("Product deleted successfully");
            window.location.reload();
          } else {
            alert("Error: " + result.message);
          }
        },
        error: function () {
          alert("Error deleting product");
        },
      });
    }
  });

  // Handle bulk actions
  $("#bulkActionForm").on("submit", function (e) {
    e.preventDefault();
    const action = $("#bulkAction").val();
    if (!action) {
      alert("Please select an action");
      return;
    }

    const selectedIds = [];
    $(".product-checkbox:checked").each(function () {
      selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
      alert("No products selected");
      return;
    }

    // Confirm bulk action
    if (
      confirm(
        "Are you sure you want to " +
          action +
          " " +
          selectedIds.length +
          " selected products?"
      )
    ) {
      $.ajax({
        url: "../api/products.php?action=bulk",
        type: "POST",
        data: {
          action: action,
          ids: selectedIds,
        },
        success: function (response) {
          const result = JSON.parse(response);
          if (result.status === "success") {
            alert("Bulk action completed successfully");
            window.location.reload();
          } else {
            alert("Error: " + result.message);
          }
        },
        error: function () {
          alert("Error performing bulk action");
        },
      });
    }
  });
});
