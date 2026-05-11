function showAlert(type, message) {
  const $box = $("#alertBox");
  $box.removeClass("d-none alert-success alert-danger alert-warning");
  $box.addClass(type === "success" ? "alert-success" : type === "warning" ? "alert-warning" : "alert-danger");
  $box.text(message);
}

function setLoading(isLoading) {
  const $save = $("#saveBtn");
  const $refresh = $("#refreshBtn");
  $save.prop("disabled", isLoading);
  $refresh.prop("disabled", isLoading);
  $save.html(isLoading ? '<i class="bi bi-hourglass-split me-2"></i>Saving...' : '<i class="bi bi-check-circle me-2"></i>Save Changes');
}

function getTokenOrRedirect() {
  const token = localStorage.getItem("auth_token");
  if (!token) {
    window.location.href = "./login.html";
    return null;
  }
  return token;
}

function loadProfile() {
  const token = getTokenOrRedirect();
  if (!token) return;

  $("#whoAmI").text("Loading...");
  $.ajax({
    url: "./php/profile.php",
    method: "POST",
    contentType: "application/json",
    dataType: "json",
    data: JSON.stringify({ action: "get", token }),
  })
    .done(function (res) {
      if (!res || !res.ok) {
        const msg = (res && res.error) || "Failed to load profile.";
        showAlert("danger", msg);
        if (msg.toLowerCase().includes("unauthorized") || msg.toLowerCase().includes("expired")) {
          localStorage.removeItem("auth_token");
          window.location.href = "./login.html";
        }
        return;
      }

      $("#fullName").val(res.name || "");
      $("#email").val(res.email || "");
      $("#age").val(res.profile && res.profile.age != null ? res.profile.age : "");
      $("#dob").val((res.profile && res.profile.dob) || "");
      $("#contact").val((res.profile && res.profile.contact) || "");
      $("#address").val((res.profile && res.profile.address) || "");

      $("#headerName").text(res.name || "User");
      $("#whoAmI").text(res.email || "Loading...");
      $("#navEmail").text(res.email || "User");
      $("#alertBox").addClass("d-none");
    })
    .fail(function () {
      showAlert("danger", "Failed to load profile (network/server error).");
    });
}

function saveProfile() {
  const token = getTokenOrRedirect();
  if (!token) return;

  const payload = {
    action: "update",
    token,
    name: $("#fullName").val().trim(),
    age: $("#age").val(),
    dob: $("#dob").val(),
    contact: $("#contact").val().trim(),
    address: $("#address").val().trim(),
  };

  if (!payload.name) {
    showAlert("warning", "Full name is required.");
    return;
  }

  setLoading(true);
  $.ajax({
    url: "./php/profile.php",
    method: "POST",
    contentType: "application/json",
    dataType: "json",
    data: JSON.stringify(payload),
  })
    .done(function (res) {
      if (res && res.ok) {
        localStorage.setItem("user_name", payload.name);
        showAlert("success", "Profile updated successfully.");
        loadProfile();
        return;
      }
      showAlert("danger", (res && res.error) || "Failed to update profile.");
    })
    .fail(function (xhr) {
      const msg = (xhr.responseJSON && xhr.responseJSON.error) || "Failed to update profile (network/server error).";
      showAlert("danger", msg);
    })
    .always(function () {
      setLoading(false);
    });
}

function logout() {
  const token = localStorage.getItem("auth_token");
  if (!token) {
    window.location.href = "./login.html";
    return;
  }

  $.ajax({
    url: "./php/profile.php",
    method: "POST",
    contentType: "application/json",
    dataType: "json",
    data: JSON.stringify({ action: "logout", token }),
  }).always(function () {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user_email");
    localStorage.removeItem("user_name");
    window.location.href = "./login.html";
  });
}

$(document).ready(function () {
  if (!localStorage.getItem("auth_token")) {
    window.location.href = "./login.html";
    return;
  }

  $("#refreshBtn").on("click", loadProfile);
  $("#saveBtn").on("click", saveProfile);
  $("#logoutBtn").on("click", logout);

  loadProfile();
});

