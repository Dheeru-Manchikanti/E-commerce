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
      const $row = $(this).closest("tr");

      $.ajax({
        url: "../api/products.php?action=delete",
        type: "POST",
        data: { id: productId },
        success: function (response) {
          try {
            const result =
              typeof response === "string" ? JSON.parse(response) : response;
            if (result.status === "success") {
              // Remove the row from the table immediately for instant feedback
              $row.fadeOut(300, function () {
                $(this).remove();
                // Then redirect to show success message
                window.location.href = "products.php?deletion=success";
              });
            } else {
              alert("Error: " + result.message);
            }
          } catch (e) {
            console.error("Error parsing response:", e, response);
            alert("Error processing server response");
          }
        },
        error: function (xhr) {
          console.error("AJAX Error:", xhr.responseText);
          alert(
            "Error deleting product. Check the browser console for details."
          );
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
      const $selectedRows = $(".product-checkbox:checked").closest("tr");

      $.ajax({
        url: "../api/products.php?action=bulk",
        type: "POST",
        data: {
          action: action,
          ids: selectedIds,
        },
        success: function (response) {
          const result =
            typeof response === "string" ? JSON.parse(response) : response;
          if (result.status === "success") {
            // Apply immediate visual feedback based on action
            if (action === "delete") {
              // Fade out deleted rows
              $selectedRows.fadeOut(300, function () {
                // Redirect after animation completes
                window.location.href =
                  "products.php?bulk=success&action=" + action;
              });
            } else {
              // For activate/deactivate, update status visually then redirect
              $selectedRows.each(function () {
                const $statusBadge = $(this).find(".badge");
                if (action === "activate") {
                  $statusBadge
                    .removeClass("bg-danger")
                    .addClass("bg-success")
                    .text("Active");
                } else if (action === "deactivate") {
                  $statusBadge
                    .removeClass("bg-success")
                    .addClass("bg-danger")
                    .text("Inactive");
                }
              });
              // Short delay to show the status change, then redirect
              setTimeout(function () {
                window.location.href =
                  "products.php?bulk=success&action=" + action;
              }, 500);
            }
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
