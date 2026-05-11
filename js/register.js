function showAlert(type, message) {
  const $box = $("#alertBox");
  $box.removeClass("d-none alert-success alert-danger alert-warning");
  $box.addClass(type === "success" ? "alert-success" : type === "warning" ? "alert-warning" : "alert-danger");
  $box.text(message);
}

function setLoading(isLoading) {
  const $btn = $("#registerBtn");
  $btn.prop("disabled", isLoading);
  $btn.text(isLoading ? "Creating account..." : "Create account");
}

$(document).ready(function () {
  $("#registerBtn").on("click", function () {
    const name = $("#name").val().trim();
    const email = $("#email").val().trim();
    const password = $("#password").val();

    if (!name || !email || !password) {
      showAlert("warning", "Please fill all fields.");
      return;
    }
    if (password.length < 8) {
      showAlert("warning", "Password must be at least 8 characters.");
      return;
    }

    setLoading(true);
    $.ajax({
      url: "./php/register.php",
      method: "POST",
      contentType: "application/json",
      dataType: "json",
      data: JSON.stringify({ name, email, password }),
    })
      .done(function (res) {
        if (res && res.ok) {
          showAlert("success", "Registration successful. Redirecting to login...");
          setTimeout(function () {
            window.location.href = "./login.html";
          }, 800);
          return;
        }
        showAlert("danger", (res && res.error) || "Registration failed.");
      })
      .fail(function (xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.error) || "Registration failed (network/server error).";
        showAlert("danger", msg);
      })
      .always(function () {
        setLoading(false);
      });
  });
});

