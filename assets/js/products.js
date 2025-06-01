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

  // Inline editing code removed - now using only the direct edit page

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
