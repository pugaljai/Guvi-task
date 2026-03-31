/**
 * login.js
 * Handles all front-end logic for the Login page.
 *
 * RULES FOLLOWED:
 *  - jQuery AJAX used for backend communication (NO form submission)
 *  - JS is in a separate file (not inside HTML)
 *  - Session token saved in localStorage (NOT PHP session / cookies)
 *  - On success, redirects to profile.html
 */

$(document).ready(function () {

  // ─── 0. Auto-redirect if already logged in ────────────────────────────────
  // If a token already exists in localStorage, no need to login again
  const existingToken = localStorage.getItem("session_token");
if (existingToken) {
  // Verify the token is still alive in Redis before redirecting
  $.ajax({
    url: "https://d29xajtybawg3y.cloudfront.net/php/get_profile.php",
    type: "POST",
    dataType: "json",
    data: { session_token: existingToken },
    success: function (response) {
      if (response.status === "success" || response.status === "no_profile") {
        // Token is valid in Redis → already logged in → go to profile
        window.location.href = "profile.html";
      } else {
        // Token is expired or invalid → clear it and stay on login page
        localStorage.removeItem("session_token");
        localStorage.removeItem("username");
      }
    },
    error: function () {
      // Network error → clear token and stay on login page
      localStorage.removeItem("session_token");
      localStorage.removeItem("username");
    }
  });
  return;
}

  // ─── 0b. Pre-fill email if "Remember Me" was used last time ──────────────
  const savedEmail = localStorage.getItem("remembered_email");
  if (savedEmail) {
    $("#email").val(savedEmail);
    $("#rememberMe").prop("checked", true);
  }

  // ─────────────────────────────────────────────────────────────────────────
  // HELPER FUNCTIONS
  // ─────────────────────────────────────────────────────────────────────────

  // Show the alert box at the top of the form
  function showAlert(message, type) {
    // type: "success" | "danger" | "warning"
    const alertBox = $("#alertBox");
    alertBox
      .removeClass("d-none alert-success alert-danger alert-warning")
      .addClass("alert-" + type)
      .text(message);
  }

  // Hide the alert box
  function hideAlert() {
    $("#alertBox").addClass("d-none");
  }

  // Show an error message under a specific field
  function showFieldError(fieldId, errorId, message) {
    $("#" + fieldId).addClass("is-invalid");
    $("#" + errorId).text(message);
  }

  // Clear all field errors and alerts
  function clearErrors() {
    $(".custom-input").removeClass("is-invalid is-valid");
    $(".invalid-feedback").text("");
    hideAlert();
  }

  // Validate the form fields before sending AJAX request
  function validateForm(email, password) {
    let valid = true;

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.trim() === "") {
      showFieldError("email", "emailError", "Email address is required.");
      valid = false;
    } else if (!emailRegex.test(email.trim())) {
      showFieldError("email", "emailError", "Please enter a valid email address.");
      valid = false;
    }

    // Password validation
    if (password === "") {
      showFieldError("password", "passwordError", "Password is required.");
      valid = false;
    }

    return valid;
  }

  // Show/hide loading spinner on the login button
  function setLoading(isLoading) {
    if (isLoading) {
      $("#btnText").text("Logging in...");
      $("#btnSpinner").removeClass("d-none");
      $("#loginBtn").prop("disabled", true);
    } else {
      $("#btnText").text("Login");
      $("#btnSpinner").addClass("d-none");
      $("#loginBtn").prop("disabled", false);
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // SHOW / HIDE PASSWORD TOGGLE
  // ─────────────────────────────────────────────────────────────────────────
  $("#togglePassword").on("click", function () {
    const pwdInput = $("#password");
    const isHidden = pwdInput.attr("type") === "password";

    if (isHidden) {
      // Show the password
      pwdInput.attr("type", "text");
      $("#eyeShow").addClass("d-none");
      $("#eyeHide").removeClass("d-none");
    } else {
      // Hide the password
      pwdInput.attr("type", "password");
      $("#eyeShow").removeClass("d-none");
      $("#eyeHide").addClass("d-none");
    }
  });

  // ─────────────────────────────────────────────────────────────────────────
  // MAIN: Login button click handler
  // NOTE: Using button click — NOT form submit — as per project rules
  // ─────────────────────────────────────────────────────────────────────────
  $("#loginBtn").on("click", function () {

    clearErrors();

    // Read values from inputs
    const email    = $("#email").val();
    const password = $("#password").val();
    const remember = $("#rememberMe").is(":checked");

    // Step 1: Front-end validation
    if (!validateForm(email, password)) {
      return; // Stop if invalid
    }

    // Step 2: Show loading spinner
    setLoading(true);

    // Step 3: Send credentials to backend via jQuery AJAX
    // Strictly NO <form> submission — only $.ajax()
    $.ajax({
     url: "https://d29xajtybawg3y.cloudfront.net/php/login.php",
      type: "POST",             // HTTP method
      dataType: "json",         // Expect JSON response
      data: {
        email:    email.trim(),
        password: password
      },

      // ── On AJAX success ────────────────────────────────────────────────
      success: function (response) {
        setLoading(false);

        if (response.status === "success") {

          // ── STORE SESSION IN LOCALSTORAGE (not PHP session / cookies) ──
          // The token was generated by PHP and stored in Redis.
          // We save it here in the browser so every future request can
          // send it to verify the user's identity via Redis.
          localStorage.setItem("session_token", response.token);
          localStorage.setItem("username", response.username);

          // Handle "Remember Me"
          if (remember) {
            // Save email so we can pre-fill it next time
            localStorage.setItem("remembered_email", email.trim());
          } else {
            // Clear any previously saved email
            localStorage.removeItem("remembered_email");
          }

          // Show success message briefly before redirecting
          showAlert("Login successful! Redirecting...", "success");
          $(".custom-input").removeClass("is-invalid").addClass("is-valid");

          // Redirect to profile page after short delay
          setTimeout(function () {
            window.location.href = "profile.html";
          }, 1500);

        } else {
          // PHP returned an error (wrong password, user not found, etc.)
          showAlert(response.message, "danger");

          // Shake the form card to give visual feedback on failure
          $(".form-card").addClass("shake");
          setTimeout(function () {
            $(".form-card").removeClass("shake");
          }, 500);
        }
      },

      // ── On AJAX network/server error ───────────────────────────────────
      error: function (xhr, status, error) {
        setLoading(false);
        showAlert("Something went wrong. Please try again later.", "danger");
        console.error("AJAX Error:", status, error);
      }

    }); // END $.ajax

  }); // END loginBtn click

  // ─────────────────────────────────────────────────────────────────────────
  // LIVE VALIDATION — remove error highlight as user types
  // ─────────────────────────────────────────────────────────────────────────
  $(".custom-input").on("input", function () {
    $(this).removeClass("is-invalid");
  });

  // Allow pressing Enter key to trigger login
  $(document).on("keypress", function (e) {
    if (e.which === 13) { // 13 = Enter key
      $("#loginBtn").trigger("click");
    }
  });

}); // END document.ready
