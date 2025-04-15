const API_BASE_URL = "compost_platform/api";

function initializeTheme() {
  // Check for saved theme preference or use the system preference
  const savedTheme = localStorage.getItem('theme') ||
    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

  // Apply the theme
  if (savedTheme === 'dark') {
    document.documentElement.classList.add('dark');
    toggleThemeIcons(true);
  } else {
    document.documentElement.classList.remove('dark');
    toggleThemeIcons(false);
  }
}

function toggleThemeIcons(isDark) {
  const sunIcon = document.getElementById('sun-icon');
  const moonIcon = document.getElementById('moon-icon');

  if (isDark) {
    sunIcon.classList.remove('hidden');
    moonIcon.classList.add('hidden');
  } else {
    sunIcon.classList.add('hidden');
    moonIcon.classList.remove('hidden');
  }
}

function toggleTheme() {
  const isDark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
  toggleThemeIcons(isDark);
}

$(document).ready(function () {
  // Load header and footer
  $("#header").load(
    "/compost_platform/frontend/components/header.html",
    function() {
      checkAuth();
      initializeTheme();

      // Add event listener to theme toggle button
      $(document).on('click', '#theme-toggle', toggleTheme);
    }
  );
  $("#footer").load("/compost_platform/frontend/components/footer.html");

  // Check authentication status
  function checkAuth() {
    $.ajax({
      url: "http://localhost/compost_platform/api/auth/check.php",
      method: "GET",
      xhrFields: { withCredentials: true },
      success: function (response) {
        if (response.status === "success") {
          $("#auth-links").addClass("hidden");
          $("#user-links").removeClass("hidden");
          $("#dashboard-link").attr(
            "href",
            response.data.role === "admin"
              ? "http://localhost/compost_platform/frontend/pages/admin/dashboard.html"
              : "http://localhost/compost_platform/frontend/pages/provider/dashboard.html"
          );
          $("#profile-link").removeClass("hidden");
        }
      },
      error: function () {
        $("#auth-links").removeClass("hidden");
        $("#user-links").addClass("hidden");
      },
    });
  }

  // Register form
  $("#register-form").submit(function (e) {
    e.preventDefault();
    const data = {
      username: $("#username").val(),
      email: $("#email").val(),
      password: $("#password").val(),
      role: $("#role").val(),
    };
    $.ajax({
      url: "http://localhost/compost_platform/api/auth/register.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify(data),
      xhrFields: { withCredentials: true },
      success: function (response) {
        showMessage(response.status, response.message, "#register-messages");
        if (response.status === "success") {
          setTimeout(
            () =>
              (window.location.href =
                "/compost_platform/frontend/pages/auth/login.html"),
            2000
          );
        }
      },
      error: function (xhr) {
        showMessage(
          "error",
          xhr.responseJSON ? xhr.responseJSON.message : "Server error",
          "#register-messages"
        );
      },
    });
  });

  // Login form
  $("#login-form").submit(function (e) {
    e.preventDefault();
    const data = {
      email: $("#email").val(),
      password: $("#password").val(),
    };
    $.ajax({
      url: "http://localhost/compost_platform/api/auth/login.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify(data),
      xhrFields: { withCredentials: true },
      success: function (response) {
        showMessage(response.status, response.message, "#login-messages");
        if (response.status === "success") {
          setTimeout(
            () =>
              (window.location.href =
                response.data.role === "admin"
                  ? "http://localhost/compost_platform/frontend/pages/admin/dashboard.html"
                  : "http://localhost/compost_platform/frontend/pages/index.html"),
            2000
          );
        }
      },
      error: function () {
        showMessage("error", "Server error", "#login-messages");
      },
    });
  });

  // Profile form
  $("#profile-form").submit(function (e) {
    e.preventDefault();
    const data = {
      email: $("#email").val(),
      password: $("#password").val(),
      company_name: $("#company_name").val(),
      location: $("#location").val(),
      contact_number: $("#contact_number").val(),
      description: $("#description").val(),
    };
    const url =
      $("#role").val() === "provider"
        ? "http://localhost/compost_platform/api/provider/update.php"
        : "http://localhost/compost_platform/api/user/update.php";
    $.ajax({
      url: url,
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify(data),
      xhrFields: { withCredentials: true },
      success: function (response) {
        showMessage(response.status, response.message, "#profile-messages");
      },
      error: function () {
        showMessage("error", "Server error", "#profile-messages");
      },
    });
  });

  // Load profile
  if (window.location.pathname.includes("profile.html")) {
    $.ajax({
      url: "http://localhost/compost_platform/api/user/profile.php",
      method: "GET",
      xhrFields: { withCredentials: true },
      success: function (response) {
        if (response.status === "success") {
          $("#username").val(response.data.username);
          $("#email").val(response.data.email);
          $("#role").val(response.data.role);
          if (response.data.provider) {
            $("#company_name").val(response.data.provider.company_name);
            $("#location").val(response.data.provider.location);
            $("#contact_number").val(response.data.provider.contact_number);
            $("#description").val(response.data.provider.description);
            $("#provider-fields").removeClass("hidden");
          }
        }
      },
    });
  }

  // Services filter
  $("#filter-btn").click(function () {
    const location = $("#location-filter").val();
    const serviceType = $("#service-type-filter").val();
    loadServices("http://localhost/compost_platform/api/service/filter.php", {
      location,
      service_type: serviceType,
    });
  });

  // Load all services
  if (window.location.pathname.includes("services.html")) {
    loadServices("http://localhost/compost_platform/api/service/list.php", {
      limit: 9,
      offset: 0,
    });
  }

  function loadServices(url, params) {
    $.ajax({
      url: url,
      method: "GET",
      data: params,
      xhrFields: { withCredentials: true },
      success: function (response) {
        $("#services-list").empty();
        console.log(response);

        const services = url.includes("filter")
          ? response.data
          : response.data.services;
        if (response.status === "success" && services.length > 0) {
          services.forEach(function (service) {
            $("#services-list").append(`
                          <div class="card-shadow p-6 bg-white rounded-lg">
                              <h3 class="text-xl font-semibold text-green-600">${
                                service.title
                              }</h3>
                              <p class="text-gray-600">${
                                service.description || "No description"
                              }</p>
                              <p class="text-gray-800 font-semibold">Provider: ${
                                service.company_name
                              }</p>
                              <p class="text-gray-600">Location: ${
                                service.location
                              }</p>
                              <p class="text-gray-600">Price: $${
                                service.price
                              }</p>
                              <button class="btn-primary mt-4 request-service" data-id="${
                                service.id
                              }">Request Service</button>
                          </div>
                      `);
          });
          $("#no-services").addClass("hidden");
        } else {
          $("#no-services").removeClass("hidden");
        }
      },
      error: function () {
        $("#no-services").text("Error loading services").removeClass("hidden");
      },
    });
  }

  // Request service
  $(document).on("click", ".request-service", function () {
    const serviceId = $(this).data("id");
    $.ajax({
      url: "http://localhost/compost_platform/api/service/request.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({ service_id: serviceId }),
      xhrFields: { withCredentials: true },
      success: function (response) {
        showMessage(response.status, response.message, "#services-messages");
      },
      error: function () {
        showMessage(
          "error",
          "Please log in to request a service",
          "#services-messages"
        );
      },
    });
  });

  // Create service
  $("#create-service-form").submit(function (e) {
    e.preventDefault();
    const data = {
      service_type: $("#service_type").val(),
      title: $("#title").val(),
      description: $("#description").val(),
      location: $("#location").val(),
      price: parseFloat($("#price").val()),
    };
    $.ajax({
      url: "http://localhost/compost_platform/api/service/create.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify(data),
      xhrFields: { withCredentials: true },
      success: function (response) {
        showMessage(
          response.status,
          response.message,
          "#create-service-messages"
        );
        if (response.status === "success") {
          setTimeout(() => (window.location.href = "dashboard.html"), 2000);
        }
      },
      error: function () {
        showMessage("error", "Server error", "#create-service-messages");
      },
    });
  });

  // Edit service
  $("#edit-service-form").submit(function (e) {
    e.preventDefault();
    const data = {
      service_id: $("#service_id").val(),
      service_type: $("#service_type").val(),
      title: $("#title").val(),
      description: $("#description").val(),
      location: $("#location").val(),
      price: parseFloat($("#price").val()),
    };
    $.ajax({
      url: "http://localhost/compost_platform/api/provider/service-update.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify(data),
      xhrFields: { withCredentials: true },
      success: function (response) {
        showMessage(
          response.status,
          response.message,
          "#edit-service-messages"
        );
        if (response.status === "success") {
          setTimeout(() => (window.location.href = "dashboard.html"), 2000);
        }
      },
      error: function () {
        showMessage("error", "Server error", "#edit-service-messages");
      },
    });
  });

  // Load edit service
  if (window.location.pathname.includes("edit-service.html")) {
    const urlParams = new URLSearchParams(window.location.search);
    const serviceId = urlParams.get("id");
    $("#service_id").val(serviceId);
    $.ajax({
      url: "http://localhost/compost_platform/api/provider/services.php",
      method: "GET",
      xhrFields: { withCredentials: true },
      success: function (response) {
        if (response.status === "success") {
          const service = response.data.find((s) => s.id == serviceId);
          if (service) {
            $("#service_type").val(service.service_type);
            $("#title").val(service.title);
            $("#description").val(service.description);
            $("#location").val(service.location);
            $("#price").val(service.price);
          }
        }
      },
    });
  }

  // Provider dashboard
  if (window.location.pathname.includes("provider/dashboard.html")) {
    $.ajax({
      url: "http://localhost/compost_platform/api/provider/dashboard.php",
      method: "GET",
      xhrFields: { withCredentials: true },
      success: function (response) {
        if (response.status === "success") {
          $("#profile-info").html(`
                      <h3 class="text-2xl font-bold">${
                        response.data.profile.company_name
                      }</h3>
                      <p>Location: ${response.data.profile.location}</p>
                      <p>Contact: ${
                        response.data.profile.contact_number || "N/A"
                      }</p>
                      <p>${
                        response.data.profile.description || "No description"
                      }</p>
                  `);
          response.data.services.forEach(function (service) {
            $("#services-list").append(`
                          <div class="card-shadow p-4 bg-white rounded-lg flex justify-between items-center">
                              <div>
                                  <h4 class="text-lg font-semibold">${service.title}</h4>
                                  <p>${service.location} - $${service.price}</p>
                              </div>
                              <div>
                                  <a href="edit-service.html?id=${service.id}" class="btn-primary mr-2">Edit</a>
                                  <button class="btn-secondary delete-service" data-id="${service.id}">Delete</button>
                              </div>
                          </div>
                      `);
          });
          response.data.requests.forEach(function (request) {
            $("#requests-list").append(`
                          <div class="card-shadow p-4 bg-white rounded-lg">
                              <p><strong>Service:</strong> ${request.title}</p>
                              <p><strong>User:</strong> ${request.username}</p>
                              <p><strong>Status:</strong> ${request.status}</p>
                          </div>
                      `);
          });
        }
      },
    });
  }

  // Delete service
  $(document).on("click", ".delete-service", function () {
    if (!confirm("Are you sure you want to delete this service?")) return;
    const serviceId = $(this).data("id");
    $.ajax({
      url: "http://localhost/compost_platform/api/service/delete.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({ service_id: serviceId }),
      xhrFields: { withCredentials: true },
      success: function (response) {
        showMessage(response.status, response.message, "#provider-messages");
        if (response.status === "success") {
          location.reload();
        }
      },
    });
  });

  // Admin dashboard
  if (window.location.pathname.includes("/compost_platform/frontend/pages/admin/dashboard.html")) {
    console.log("AJAX started");
    $.ajax({
        url: "http://localhost/compost_platform/api/admin/dashboard.php",
        method: "GET",
        xhrFields: { withCredentials: true },
        success: function (response) {
            console.log("Response:", response);
            if (response.status === "success") {
                if (response.message.pending_providers && response.message.pending_providers.length > 0) {
                    $("#providers-list").empty(); // Clear existing content
                    response.message.pending_providers.forEach(function (provider) {
                        $("#providers-list").append(`
                            <div class="card-shadow p-4 bg-white rounded-lg flex justify-between items-center">
                                <div>
                                    <p><strong>${provider.company_name || provider.username}</strong></p>
                                    <p>${provider.email} - ${provider.location || "N/A"}</p>
                                </div>
                                <button class="btn-primary validate-provider" data-id="${provider.id}">Validate</button>
                            </div>
                        `);
                    });
                } else {
                    $("#providers-list").html("<p>No pending providers.</p>");
                }
                if (response.message.pending_services && response.message.pending_services.length > 0) {
                    $("#services-list").empty(); // Clear existing content
                    response.message.pending_services.forEach(function (service) {
                        $("#services-list").append(`
                            <div class="card-shadow p-4 bg-white rounded-lg flex justify-between items-center">
                                <div>
                                    <p><strong>${service.title}</strong></p>
                                    <p>${service.location}</p>
                                </div>
                                <div>
                                    <button class="btn-primary approve-service mr-2" data-id="${service.id}">Approve</button>
                                    <button class="btn-secondary remove-service" data-id="${service.id}">Remove</button>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    $("#services-list").html("<p>No pending services.</p>");
                }
            } else {
                $("#providers-list").html("<p>Error loading providers.</p>");
                $("#services-list").html("<p>Error loading services.</p>");
            }
        },
        error: function (xhr, status, error) {
            console.log("AJAX error:", status, error, xhr.responseText);
            $("#providers-list").html("<p>Error loading providers.</p>");
            $("#services-list").html("<p>Error loading services.</p>");
        }
    });
}

  // Validate provider
  $(document).on("click", ".validate-provider", function () {
    const userId = $(this).data("id");
    $.ajax({
      url: "http://localhost/compost_platform/api/admin/validate_provider.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({ user_id: userId }),
      xhrFields: { withCredentials: true },
      success: function (response) {
        showMessage(response.status, response.message, "#admin-messages");
        if (response.status === "success") {
          location.reload();
        }
      },
    });
  });

  // Approve/Remove service
  $(document).on("click", ".approve-service, .remove-service", function () {
    const serviceId = $(this).data("id");
    const action = $(this).hasClass("approve-service") ? "approve" : "remove";
    $.ajax({
      url: "http://localhost/compost_platform/api/admin/manage_services.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({ service_id: serviceId, action: action }),
      xhrFields: { withCredentials: true },
      success: function (response) {
        showMessage(response.status, response.message, "#admin-messages");
        if (response.status === "success") {
          location.reload();
        }
      },
    });
  });

  // Logout
  $(document).on("click", "#logout", function (e) {
    e.preventDefault();
    console.log("Logout clicked");
    $.ajax({
      url: "http://localhost/compost_platform/api/auth/logout.php",
      method: "POST", // <-- POST
      xhrFields: { withCredentials: true },
      success: function (response) {
        window.location.href =
          "http://localhost/compost_platform/frontend/pages/index.html";
      },
      error: function (xhr, status, error) {
        alert(
          "Logout failed: " +
            (xhr.responseJSON ? xhr.responseJSON.message : "Server error")
        );
      },
    });
  });

  // Utility to show messages
  function showMessage(status, message, container) {
    $(container)
      .html(
        `
          <div class="alert ${
            status === "success" ? "alert-success" : "alert-error"
          }">
              ${message}
          </div>
      `
      )
      .show();
    setTimeout(() => $(container).empty(), 5000);
  }
});
