$(document).ready(function () {
  console.log("Categories.js loaded");

  if ($("#categoriesTable").length) {
    console.log("Initializing DataTable");
    var table = $("#categoriesTable").DataTable({
      order: [[0, "asc"]],
      pageLength: 10,
    });

    table.on("page.dt", function () {
      console.log(
        "DataTable page changed - event delegation should still work"
      );
    });

    console.log("Initial edit buttons found:", $(".edit-category").length);
    console.log("Initial delete buttons found:", $(".delete-category").length);
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

  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }

  if ($(".alert-danger").length > 0) {
    $("#addCategoryModal").modal("show");
  }

  $(document).on("click", "button", function (e) {
    if ($(this).hasClass("edit-category")) {
      console.log("Global click handler detected edit-category button click");
    }
    if ($(this).hasClass("delete-category")) {
      console.log("Global click handler detected delete-category button click");
    }
  });

  setTimeout(function () {
    console.log(
      "After timeout - edit buttons found:",
      $(".edit-category").length
    );
    console.log(
      "After timeout - delete buttons found:",
      $(".delete-category").length
    );

    // Test clicking the first edit button programmatically
    if ($(".edit-category").length > 0) {
      console.log(
        "First edit button data-id:",
        $(".edit-category").first().data("id")
      );
    }
  }, 1000);

  // Edit category event handler with event delegation
  $(document).on("click", ".edit-category", function (e) {
    e.preventDefault();
    console.log("Edit category event triggered!");

    const categoryId = $(this).data("id");
    const buttonElement = $(this);

    console.log("Edit button clicked! Category ID:", categoryId);
    console.log("Button element:", buttonElement);
    console.log("Button data attributes:", buttonElement.data());

    if (!categoryId) {
      console.error("No category ID found on button:", buttonElement);
      alert("Error: Category ID not found");
      return;
    }

    // AJAX call to get category details
    $.ajax({
      url: "../api/categories.php?action=get&id=" + categoryId,
      type: "GET",
      dataType: "json",
      success: function (response) {
        console.log("Get category response:", response);
        if (response && response.status === "success" && response.data) {
          const category = response.data;

          // Fill the modal with category data
          $("#edit_id").val(category.id);
          $("#edit_name").val(category.name || "");
          $("#edit_description").val(category.description || "");
          $("#edit_parent_id").val(category.parent_id || "");
          $("#edit_status").val(category.status || "active");

          // Show the modal
          $("#editCategoryModal").modal("show");
        } else {
          alert("Error: " + (response.message || "Invalid response"));
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
          readyState: xhr.readyState,
          statusText: xhr.statusText,
        });
        alert("Error fetching category details: " + error);
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

    // Show loading state
    $(this).prop("disabled", true).text("Saving...");

    $.ajax({
      url: "../api/categories.php?action=update",
      type: "POST",
      dataType: "json",
      data: {
        id: id,
        name: name,
        description: description,
        parent_id: parent_id,
        status: status,
      },
      success: function (response) {
        console.log("Update response:", response);

        if (response && response.status === "success") {
          $("#editCategoryModal").modal("hide");

          // Redirect to categories page with success parameter
          window.location.href = "categories.php?update=success";
        } else {
          alert("Error: " + (response.message || "Update failed"));
          $("#saveCategoryChanges")
            .prop("disabled", false)
            .text("Save Changes");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", {
          status: status,
          error: error,
          responseText: xhr.responseText,
        });
        alert("Error updating category: " + error);
        $("#saveCategoryChanges").prop("disabled", false).text("Save Changes");
      },
    });
  });

  // Delete category event handler with event delegation
  $(document).on("click", ".delete-category", function (e) {
    e.preventDefault();
    console.log("Delete category event triggered!");

    const categoryId = $(this).data("id");
    const buttonElement = $(this);

    console.log("Delete button clicked! Category ID:", categoryId);
    console.log("Button element:", buttonElement);
    console.log("Button data attributes:", buttonElement.data());

    if (!categoryId) {
      console.error("No category ID found on button:", buttonElement);
      alert("Error: Category ID not found");
      return;
    }

    if (
      confirm(
        "Are you sure you want to delete this category? This cannot be undone."
      )
    ) {
      const $row = $(this).closest("tr");

      console.log("Proceeding with deletion for category ID:", categoryId);

      // Show immediate visual feedback
      $row.fadeOut(300);

      $.ajax({
        url: "../api/categories.php?action=delete",
        type: "POST",
        dataType: "json",
        data: { id: categoryId },
        success: function (response) {
          console.log("Delete response:", response);
          if (response && response.status === "success") {
            // Redirect to categories page with success parameter
            window.location.href = "categories.php?deletion=success";
          } else {
            // Restore row visibility on error
            $row.fadeIn(300);
            alert("Error: " + (response.message || "Delete failed"));
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", {
            status: status,
            error: error,
            responseText: xhr.responseText,
          });
          $row.fadeIn(300);
          alert("Error deleting category: " + error);
        },
      });
    }
  });
});
