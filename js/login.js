function showAlert(type, message) {
  const $box = $("#alertBox");
  $box.removeClass("d-none alert-success alert-danger alert-warning");
  $box.addClass(type === "success" ? "alert-success" : type === "warning" ? "alert-warning" : "alert-danger");
  $box.text(message);
}

function setLoading(isLoading) {
  const $btn = $("#loginBtn");
  $btn.prop("disabled", isLoading);
  $btn.text(isLoading ? "Logging in..." : "Login");
}

$(document).ready(function () {
  // If already logged-in, go to profile
  const token = localStorage.getItem("auth_token");
  if (token) {
    window.location.href = "./profile.html";
    return;
  }

  $("#loginBtn").on("click", function () {
    const email = $("#email").val().trim();
    const password = $("#password").val();

    if (!email || !password) {
      showAlert("warning", "Please enter email and password.");
      return;
    }

    setLoading(true);
    $.ajax({
      url: "./php/login.php",
      method: "POST",
      contentType: "application/json",
      dataType: "json",
      data: JSON.stringify({ email, password }),
    })
      .done(function (res) {
        if (res && res.ok && res.token) {
          localStorage.setItem("auth_token", res.token);
          localStorage.setItem("user_email", res.email || email);
          localStorage.setItem("user_name", res.name || "");
          window.location.href = "./profile.html";
          return;
        }
        showAlert("danger", (res && res.error) || "Login failed.");
      })
      .fail(function (xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.error) || "Login failed (network/server error).";
        showAlert("danger", msg);
      })
      .always(function () {
        setLoading(false);
      });
  });
});

