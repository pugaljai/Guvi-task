/**
 * profile.js
 * Handles all front-end logic for the Profile page.
 *
 * RULES FOLLOWED:
 *  - jQuery AJAX used for all backend calls (NO form submission)
 *  - Session verified using token from localStorage (NOT PHP session)
 *  - JS is in a completely separate file
 *
 * FLOW:
 *  1. On page load → check localStorage for session_token
 *  2. If no token → redirect to login.html
 *  3. If token exists → send AJAX request to get_profile.php
 *  4. Populate form with returned data
 *  5. On Save → send AJAX POST to update_profile.php
 */

$(document).ready(function () {

  // ─────────────────────────────────────────────────────────────────────────
  // STEP 1: Read session from localStorage
  // The token was saved here by login.js after successful login
  // ─────────────────────────────────────────────────────────────────────────
  const sessionToken = localStorage.getItem("session_token");
  const username     = localStorage.getItem("username");

  // If no token found → not logged in → redirect to login
  if (!sessionToken || !username) {
    window.location.href = "login.html";
    return;
  }

  // Set username in navbar and sidebar immediately (don't wait for AJAX)
  $("#navUsername").text(username);
  $("#sidebarUsername").text("@" + username);
  $("#usernameDisplay").val(username);

  // Set avatar initials from username
  const initials = username.charAt(0).toUpperCase();
  $("#navAvatar").text(initials);
  $("#avatarCircle").text(initials);

  // ─────────────────────────────────────────────────────────────────────────
  // HELPER FUNCTIONS
  // ─────────────────────────────────────────────────────────────────────────

  // Show the alert box
  function showAlert(message, type) {
    const alertBox = $("#alertBox");
    alertBox
      .removeClass("d-none alert-success alert-danger alert-warning")
      .addClass("alert-" + type)
      .html(message);

    // Auto-hide success alerts after 4 seconds
    if (type === "success") {
      setTimeout(function () {
        alertBox.addClass("d-none");
      }, 4000);
    }
  }

  // Hide the alert box
  function hideAlert() {
    $("#alertBox").addClass("d-none");
  }

  // Show/hide the loading overlay
  function setLoadingOverlay(show) {
    if (show) {
      $("#loadingOverlay").removeClass("d-none");
    } else {
      $("#loadingOverlay").addClass("d-none");
    }
  }

  // Show/hide the save button spinner
  function setSaveLoading(isLoading) {
    if (isLoading) {
      $("#saveBtnText").text("Saving...");
      $("#saveBtnSpinner").removeClass("d-none");
      $("#saveBtn").prop("disabled", true);
    } else {
      $("#saveBtnText").text("Save Profile");
      $("#saveBtnSpinner").addClass("d-none");
      $("#saveBtn").prop("disabled", false);
    }
  }

  // Update the sidebar quick-info chips and avatar from current form values
  function updateSidebar() {
    const fullName = $("#fullName").val().trim();
    const age      = $("#age").val().trim();
    const gender   = $("#gender").val();
    const contact  = $("#contact").val().trim();

    // Full name in sidebar
    $("#sidebarFullName").text(fullName || "—");

    // Avatar: use first letter of full name if available, else username
    const avatarChar = fullName
      ? fullName.charAt(0).toUpperCase()
      : username.charAt(0).toUpperCase();
    $("#avatarCircle").text(avatarChar);
    $("#navAvatar").text(avatarChar);

    // Chips
    $("#chipAgeVal").text("Age: " + (age || "—"));
    $("#chipGenderVal").text("Gender: " + (gender || "—"));
    $("#chipContactVal").text("Contact: " + (contact || "—"));

    // Completeness bar: count how many of 6 fields are filled
    const fields = [
      $("#fullName").val(),
      $("#age").val(),
      $("#dob").val(),
      $("#contact").val(),
      $("#gender").val(),
      $("#address").val(),
      $("#bio").val()
    ];
    const filled = fields.filter(f => f && f.trim() !== "").length;
    const pct    = Math.round((filled / fields.length) * 100);

    $("#completenessPct").text(pct + "%");
    $("#completenessBar")
      .css("width", pct + "%")
      .attr("aria-valuenow", pct);

    // Change bar color based on completion
    $("#completenessBar")
      .removeClass("bg-danger bg-warning bg-success")
      .addClass(pct < 40 ? "bg-danger" : pct < 80 ? "bg-warning" : "bg-success");
  }

  // Populate all form fields with data from MongoDB (returned by get_profile.php)
  function populateForm(data) {
    $("#fullName").val(data.full_name  || "");
    $("#age").val(data.age             || "");
    $("#dob").val(data.dob             || "");
    $("#contact").val(data.contact     || "");
    $("#gender").val(data.gender       || "");
    $("#address").val(data.address     || "");
    $("#bio").val(data.bio             || "");

    // Update sidebar to reflect loaded data
    updateSidebar();
  }

  // ─────────────────────────────────────────────────────────────────────────
  // STEP 2: Load profile data on page load
  // Sends session token to PHP → PHP verifies via Redis → fetches from MongoDB
  // ─────────────────────────────────────────────────────────────────────────
  function loadProfile() {
    setLoadingOverlay(true);
    hideAlert();

    $.ajax({
      url: "https://d29xajtybawg3y.cloudfront.net/php/get_profile.php",
      type: "POST",                // POST so token isn't in URL
      dataType: "json",
      data: {
        session_token: sessionToken  // Token from localStorage
      },

      success: function (response) {
        setLoadingOverlay(false);

        if (response.status === "success") {
          // Populate form with data from MongoDB
          populateForm(response.data);

        } else if (response.status === "no_profile") {
          // User exists but has no profile saved yet — that's fine
          // Just show empty form ready to fill
          updateSidebar();
          showAlert(
            "👋 Welcome! Your profile is empty. Fill in the details below and click <strong>Save Profile</strong>.",
            "warning"
          );

        } else if (response.status === "unauthorized") {
          // Token invalid or expired in Redis
          localStorage.removeItem("session_token");
          localStorage.removeItem("username");
          window.location.href = "login.html";

        } else {
          showAlert(response.message || "Failed to load profile.", "danger");
        }
      },

      error: function (xhr, status, error) {
        setLoadingOverlay(false);
        showAlert("Network error. Could not load your profile. Please refresh.", "danger");
        console.error("Load profile error:", status, error);
      }
    });
  }

  // Call load on page ready
  loadProfile();

  // ─────────────────────────────────────────────────────────────────────────
  // STEP 3: Save Profile — AJAX POST to update_profile.php
  // ─────────────────────────────────────────────────────────────────────────
  $("#saveBtn").on("click", function () {
    hideAlert();

    // Basic validation
    const contact = $("#contact").val().trim();
    if (contact !== "" && !/^\d{10}$/.test(contact)) {
      showAlert("Contact number must be exactly 10 digits.", "danger");
      $("#contact").addClass("is-invalid");
      return;
    }

    const age = $("#age").val().trim();
    if (age !== "" && (parseInt(age) < 1 || parseInt(age) > 120)) {
      showAlert("Please enter a valid age between 1 and 120.", "danger");
      $("#age").addClass("is-invalid");
      return;
    }

    setSaveLoading(true);

    // Collect all form values
    const profileData = {
      session_token : sessionToken,       // For Redis verification in PHP
      full_name     : $("#fullName").val().trim(),
      age           : $("#age").val().trim(),
      dob           : $("#dob").val(),
      contact       : contact,
      gender        : $("#gender").val(),
      address       : $("#address").val().trim(),
      bio           : $("#bio").val().trim()
    };

    // Send to backend via jQuery AJAX — NO form submission
    $.ajax({
      url: "https://d29xajtybawg3y.cloudfront.net/php/update_profile.php",
      type: "POST",
      dataType: "json",
      data: profileData,

      success: function (response) {
        setSaveLoading(false);

        if (response.status === "success") {
          showAlert("✅ Profile saved successfully!", "success");
          // Update sidebar to reflect new values
          updateSidebar();
          // Remove any validation highlights
          $(".custom-input").removeClass("is-invalid");

        } else if (response.status === "unauthorized") {
          localStorage.removeItem("session_token");
          localStorage.removeItem("username");
          window.location.href = "login.html";

        } else {
          showAlert(response.message || "Failed to save profile.", "danger");
        }
      },

      error: function (xhr, status, error) {
        setSaveLoading(false);
        showAlert("Network error. Could not save your profile. Please try again.", "danger");
        console.error("Save profile error:", status, error);
      }
    });

  }); // END saveBtn click

  // ─────────────────────────────────────────────────────────────────────────
  // STEP 4: Reset Changes — reload data from server
  // ─────────────────────────────────────────────────────────────────────────
  $("#resetBtn").on("click", function () {
    hideAlert();
    $(".custom-input").removeClass("is-invalid is-valid");
    loadProfile();   // Re-fetch from MongoDB
  });

  // ─────────────────────────────────────────────────────────────────────────
  // STEP 5: Logout button
  // ─────────────────────────────────────────────────────────────────────────
  $("#logoutBtn").on("click", function () {

    // Call logout.php to delete token from Redis
    $.ajax({
      url: "https://d29xajtybawg3y.cloudfront.net/php/logout.php",
      type: "POST",
      dataType: "json",
      data: {
        session_token: sessionToken
      },
      complete: function () {
        // Whether or not server responds — always clear localStorage and redirect
        localStorage.removeItem("session_token");
        localStorage.removeItem("username");
        window.location.href = "login.html";
      }
    });

  });

  // ─────────────────────────────────────────────────────────────────────────
  // Live sidebar updates as user types
  // ─────────────────────────────────────────────────────────────────────────
  $("#fullName, #age, #gender, #contact, #dob, #address, #bio").on("input change", function () {
    updateSidebar();
    $(this).removeClass("is-invalid");
  });

}); // END document.ready
