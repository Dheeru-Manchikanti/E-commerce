/**
 * Orders management JavaScript file
 */
$(document).ready(function () {
  // DataTable initialization for orders list
  if ($("#ordersTable").length) {
    $("#ordersTable").DataTable({
      order: [[0, "desc"]],
      pageLength: 10,
    });
  }

  // View order details
  $(".view-order").on("click", function (e) {
    e.preventDefault();
    const orderId = $(this).data("id");

    // AJAX call to get order details
    $.ajax({
      url: "../api/orders.php?action=get&id=" + orderId,
      type: "GET",
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const order = response.data;

          // Fill the modal with order data
          $("#viewOrderModal").find("#order_id").text(order.id);
          $("#viewOrderModal").find("#order_number").text(order.order_number);
          $("#viewOrderModal").find("#order_date").text(order.created_at);
          $("#viewOrderModal").find("#order_status").text(order.status);
          $("#viewOrderModal")
            .find("#order_total")
            .text("₹" + parseFloat(order.total_amount).toFixed(2));

          // Customer details
          $("#viewOrderModal")
            .find("#customer_name")
            .text(order.customer.first_name + " " + order.customer.last_name);
          $("#viewOrderModal")
            .find("#customer_email")
            .text(order.customer.email);
          $("#viewOrderModal")
            .find("#customer_phone")
            .text(order.customer.phone || "N/A");

          // Addresses
          $("#viewOrderModal")
            .find("#shipping_address")
            .text(order.shipping_address);
          $("#viewOrderModal")
            .find("#billing_address")
            .text(order.billing_address);

          // Order items
          const itemsContainer = $("#orderItemsContainer");
          itemsContainer.empty();

          order.items.forEach(function (item) {
            const itemRow = `
                            <tr>
                                <td>${item.product_name}</td>
                                <td>${item.quantity}</td>
                                <td>$${parseFloat(item.price).toFixed(2)}</td>
                                <td>$${(item.quantity * item.price).toFixed(
                                  2
                                )}</td>
                            </tr>
                        `;
            itemsContainer.append(itemRow);
          });

          // Status dropdown
          $("#update_status").val(order.status);
          $("#update_order_id").val(order.id);

          // Show the modal
          $("#viewOrderModal").modal("show");
        } else {
          alert("Error: " + response.message);
        }
      },
      error: function () {
        alert("Error fetching order details");
      },
    });
  });

  // Update order status
  $("#updateOrderStatus").on("click", function () {
    const orderId = $("#update_order_id").val();
    const status = $("#update_status").val();

    $.ajax({
      url: "../api/orders.php?action=update_status",
      type: "POST",
      data: {
        id: orderId,
        status: status,
      },
      success: function (response) {
        const result = JSON.parse(response);
        if (result.status === "success") {
          $("#viewOrderModal").modal("hide");
          alert("Order status updated successfully");
          window.location.reload();
        } else {
          alert("Error: " + result.message);
        }
      },
      error: function () {
        alert("Error updating order status");
      },
    });
  });

  // Export orders to CSV
  $("#exportOrders").on("click", function (e) {
    e.preventDefault();

    // Get filter values
    const startDate = $("#startDate").val();
    const endDate = $("#endDate").val();
    const status = $("#filterStatus").val();

    // Build query string
    let queryString = "?action=export";
    if (startDate) queryString += "&start_date=" + startDate;
    if (endDate) queryString += "&end_date=" + endDate;
    if (status) queryString += "&status=" + status;

    // Redirect to export endpoint
    window.location.href = "../api/orders.php" + queryString;
  });
});
