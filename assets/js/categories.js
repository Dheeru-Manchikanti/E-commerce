/**
 * Categories management JavaScript file
 */
$(document).ready(function () {
  // DataTable initialization for categories list
  if ($("#categoriesTable").length) {
    $("#categoriesTable").DataTable({
      order: [[0, "asc"]],
      pageLength: 10,
    });
  }

  // Category form validation
  $("#categoryForm").on("submit", function (e) {
    const name = $("#name").val();

    if (!name || name.trim() === "") {
      e.preventDefault();
      alert("Category name is required");
      $("#name").focus();
      return false;
    }

    // Store form submission time to prevent duplicate submissions
    sessionStorage.setItem("categoryFormSubmitted", Date.now());

    return true;
  });

  // Prevent form resubmission on page reload
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }

  // Inline editing of category
  $(".edit-category").on("click", function (e) {
    e.preventDefault();
    const categoryId = $(this).data("id");

    // AJAX call to get category details
    $.ajax({
      url: "../api/categories.php?action=get&id=" + categoryId,
      type: "GET",
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const category = response.data;

          // Fill the modal with category data
          $("#editCategoryModal").find("#edit_id").val(category.id);
          $("#editCategoryModal").find("#edit_name").val(category.name);
          $("#editCategoryModal")
            .find("#edit_description")
            .val(category.description);
          $("#editCategoryModal")
            .find("#edit_parent_id")
            .val(category.parent_id);
          $("#editCategoryModal").find("#edit_status").val(category.status);

          // Show the modal
          $("#editCategoryModal").modal("show");
        } else {
          alert("Error: " + response.message);
        }
      },
      error: function () {
        alert("Error fetching category details");
      },
    });
  });

  // Save category changes
  $("#saveCategoryChanges").on("click", function () {
    const id = $("#edit_id").val();
    const name = $("#edit_name").val();
    const description = $("#edit_description").val();
    const parent_id = $("#edit_parent_id").val();
    const status = $("#edit_status").val();

    if (!name || name.trim() === "") {
      alert("Category name is required");
      $("#edit_name").focus();
      return;
    }

    $.ajax({
      url: "../api/categories.php?action=update",
      type: "POST",
      data: {
        id: id,
        name: name,
        description: description,
        parent_id: parent_id,
        status: status,
      },
      success: function (response) {
        console.log(response);

        if (response.status === "success") {
          $("#editCategoryModal").modal("hide");
          alert("Category updated successfully");

          // Instead of just reloading (which might resubmit forms),
          // redirect to the categories page with a clean URL
          window.location.href = "categories.php";
        } else {
          alert("Error: " + response.message);
        }
      },
      error: function () {
        alert("Error updating category");
      },
    });
  });

  // Delete category
  $(".delete-category").on("click", function (e) {
    e.preventDefault();
    if (
      confirm(
        "Are you sure you want to delete this category? This cannot be undone."
      )
    ) {
      // Extract category ID from href attribute
      const href = $(this).attr("href");
      let categoryId;

      if (href) {
        // Extract ID from URL format like "categories.php?action=delete&id=21"
        const match = href.match(/[?&]id=(\d+)/);
        if (match && match[1]) {
          categoryId = match[1];
        }
      } else {
        // Fallback to data-id if href is not available
        categoryId = $(this).data("id");
      }

      // Check if categoryId is defined
      if (!categoryId) {
        alert("Error: Category ID not found");
        return;
      }

      console.log("Deleting category ID:", categoryId);

      $.ajax({
        url: "../api/categories.php?action=delete",
        type: "POST",
        data: { id: categoryId },
        success: function (response) {
          //const result = JSON.parse(response);
          if (response.status === "success") {
            alert("Category deleted successfully");
            // Redirect to the categories page with a clean URL
            window.location.href = "categories.php";
          } else {
            alert("Error: " + response.message);
          }
        },
        error: function () {
          alert("Error deleting category");
        },
      });
    }
  });
});
