/**
 * register.js
 * Handles all front-end logic for the Register page.
 *
 * RULES FOLLOWED:
 *  - jQuery AJAX used for backend communication (NO form submission)
 *  - JS is in a separate file (not inside HTML)
 *  - On success, redirects to login.html
 */

$(document).ready(function () {

  // ─── Helper: Show alert box ───────────────────────────────────────────────
  function showAlert(message, type) {
    // type = "success" | "danger" | "warning"
    const alertBox = $("#alertBox");
    alertBox
      .removeClass("d-none alert-success alert-danger alert-warning")
      .addClass("alert-" + type)
      .text(message);
  }

  // ─── Helper: Hide alert box ───────────────────────────────────────────────
  function hideAlert() {
    $("#alertBox").addClass("d-none");
  }

  // ─── Helper: Show inline field error ─────────────────────────────────────
  function showFieldError(fieldId, errorId, message) {
    $("#" + fieldId).addClass("is-invalid");
    $("#" + errorId).text(message);
  }

  // ─── Helper: Clear all field errors ──────────────────────────────────────
  function clearErrors() {
    $(".custom-input").removeClass("is-invalid is-valid");
    $(".invalid-feedback").text("");
    hideAlert();
  }

  // ─── Helper: Validate the form before sending ─────────────────────────────
  function validateForm(username, email, password, confirmPassword) {
    let valid = true;

    // Username check
    if (username.trim() === "") {
      showFieldError("username", "usernameError", "Username is required.");
      valid = false;
    } else if (username.trim().length < 3) {
      showFieldError("username", "usernameError", "Username must be at least 3 characters.");
      valid = false;
    }

    // Email check — simple regex
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.trim() === "") {
      showFieldError("email", "emailError", "Email address is required.");
      valid = false;
    } else if (!emailRegex.test(email.trim())) {
      showFieldError("email", "emailError", "Please enter a valid email address.");
      valid = false;
    }

    // Password check
    if (password === "") {
      showFieldError("password", "passwordError", "Password is required.");
      valid = false;
    } else if (password.length < 6) {
      showFieldError("password", "passwordError", "Password must be at least 6 characters.");
      valid = false;
    }

    // Confirm password check
    if (confirmPassword === "") {
      showFieldError("confirmPassword", "confirmPasswordError", "Please confirm your password.");
      valid = false;
    } else if (password !== confirmPassword) {
      showFieldError("confirmPassword", "confirmPasswordError", "Passwords do not match.");
      valid = false;
    }

    return valid;
  }

  // ─── Helper: Show loading spinner on button ───────────────────────────────
  function setLoading(isLoading) {
    if (isLoading) {
      $("#btnText").text("Registering...");
      $("#btnSpinner").removeClass("d-none");
      $("#registerBtn").prop("disabled", true);
    } else {
      $("#btnText").text("Register");
      $("#btnSpinner").addClass("d-none");
      $("#registerBtn").prop("disabled", false);
    }
  }

  // ─── MAIN: Register button click ─────────────────────────────────────────
  // NOTE: We use a button click (NOT form submit) as required by the rules
  $("#registerBtn").on("click", function () {

    clearErrors();

    // Read values from input fields
    const username        = $("#username").val();
    const email           = $("#email").val();
    const password        = $("#password").val();
    const confirmPassword = $("#confirmPassword").val();

    // Step 1: Validate on the front-end first
    if (!validateForm(username, email, password, confirmPassword)) {
      return; // Stop here if validation fails
    }

    // Step 2: Show loading state
    setLoading(true);

    // Step 3: Send data to backend using jQuery AJAX
    // Strictly NO form submission — $.ajax() only
    $.ajax({
      url: "php/register.php",       // Backend PHP file
      type: "POST",                   // HTTP method
      dataType: "json",               // Expect JSON back from PHP
      data: {
        username: username.trim(),
        email: email.trim(),
        password: password
        // NOTE: confirmPassword is NOT sent — validation is front-end only
      },

      // ── On AJAX success ──────────────────────────────────────────────────
      success: function (response) {
        setLoading(false);

        if (response.status === "success") {
          // Show success message
          showAlert("Registration successful! Redirecting to login...", "success");
          $(".custom-input").removeClass("is-invalid").addClass("is-valid");

          setTimeout(function () {
          // Clear any old session so login.js doesn't auto-redirect to profile
         localStorage.removeItem("session_token");
         localStorage.removeItem("username");
         window.location.href = "login.html";
        }, 2000);

        } else {
          // PHP returned an error (e.g. duplicate email/username)
          showAlert(response.message, "danger");
        }
      },

      // ── On AJAX error (network/server issue) ─────────────────────────────
      error: function (xhr, status, error) {
        setLoading(false);
        showAlert("Something went wrong. Please try again later.", "danger");
        console.error("AJAX Error:", status, error);
      }

    }); // END $.ajax

  }); // END registerBtn click

  // ─── Live validation: remove error highlight as user types ───────────────
  $(".custom-input").on("input", function () {
    $(this).removeClass("is-invalid");
  });
  $("#loginHereBtn").on("click", function (e) {
  e.preventDefault(); // stop the link from navigating immediately
  localStorage.removeItem("session_token");
  localStorage.removeItem("username");
  window.location.href = "login.html"; // now go to login cleanly
});

}); // END document.ready
