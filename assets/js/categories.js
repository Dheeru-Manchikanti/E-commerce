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

    return true;
  });

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
        const result = JSON.parse(response);
        if (result.status === "success") {
          $("#editCategoryModal").modal("hide");
          alert("Category updated successfully");
          window.location.reload();
        } else {
          alert("Error: " + result.message);
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
      const categoryId = $(this).data("id");

      $.ajax({
        url: "../api/categories.php?action=delete",
        type: "POST",
        data: { id: categoryId },
        success: function (response) {
          const result = JSON.parse(response);
          if (result.status === "success") {
            alert("Category deleted successfully");
            window.location.reload();
          } else {
            alert("Error: " + result.message);
          }
        },
        error: function () {
          alert("Error deleting category");
        },
      });
    }
  });
});
