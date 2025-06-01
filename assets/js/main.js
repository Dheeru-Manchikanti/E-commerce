/**
 * Main application JavaScript file
 */
$(document).ready(function () {
  // Admin: Toggle sidebar
  $("#sidebarToggle").on("click", function (e) {
    e.preventDefault();
    $("#sidebar").toggleClass("collapsed");
    $("#content-wrapper").toggleClass("expanded");
  });

  // Initialize tooltips and popovers
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  var popoverTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]')
  );
  var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });

  // Dismiss alerts automatically after 5 seconds
  $(".alert").not(".alert-permanent").delay(5000).fadeOut(500);

  // Handle CSRF token for AJAX requests
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    },
  });

  // Handle form validation
  $(".needs-validation").each(function () {
    const form = $(this);
    form.on("submit", function (event) {
      if (form[0].checkValidity() === false) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.addClass("was-validated");
    });
  });

  // Confirmation dialog for delete actions
  $(".confirm-action").on("click", function (e) {
    e.preventDefault();
    const target = $(this).data("target");

    if (
      confirm(
        "Are you sure you want to perform this action? This cannot be undone."
      )
    ) {
      if ($(this).is("a")) {
        window.location.href = $(this).attr("href");
      } else if (target) {
        $(target).submit();
      }
    }
  });

  // Back to top button
  $(window).scroll(function () {
    if ($(this).scrollTop() > 300) {
      $(".back-to-top").fadeIn();
    } else {
      $(".back-to-top").fadeOut();
    }
  });

  $(".back-to-top").click(function () {
    $("html, body").animate({ scrollTop: 0 }, 800);
    return false;
  });

  // Product image zoom effect
  $(".product-image-zoom")
    .on("mousemove", function (e) {
      const image = $(this);
      const offsetX = ((e.pageX - image.offset().left) / image.width()) * 100;
      const offsetY = ((e.pageY - image.offset().top) / image.height()) * 100;

      image.css("transform-origin", `${offsetX}% ${offsetY}%`);
    })
    .on("mouseenter", function () {
      $(this).css("transform", "scale(1.5)");
    })
    .on("mouseleave", function () {
      $(this).css("transform", "scale(1)");
    });

  // Add animation to product cards
  $(".product-card").hover(
    function () {
      $(this).addClass("shadow-lg").css("transform", "translateY(-5px)");
    },
    function () {
      $(this).removeClass("shadow-lg").css("transform", "translateY(0)");
    }
  );
});
