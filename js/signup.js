function trySignup() {
    let name = $("#txtName").val();
    let email = $("#txtEmail").val();
    let pw = $("#txtPassword").val();
    let cpw = $("#txtConfirmPassword").val();

    if (name.trim() && email.trim() && pw.trim() && cpw.trim()) {
        if (pw !== cpw) {
            $("#errormessage").text("Passwords do not match!");
            $("#diverror").addClass("applyerrordiv");
            return;
        }

        $.ajax({
            url: "/ecommerce_project/ajaxhandler/signupAjax.php",
            type: "POST",
            dataType: "json",
            data: { name, email, password: pw },

            beforeSend: function () {
                $("#diverror").removeClass("applyerrordiv");
                $("#lockscreen").addClass("applylockscreen");
            },

            success: function (rv) {
                $("#lockscreen").removeClass("applylockscreen");

                if (rv.status === "OK") {
                    window.location.href = "login.php";
                } else {
                    $("#errormessage").text(rv.status);
                    $("#diverror").addClass("applyerrordiv");
                }
            },

            error: function (xhr) {
                alert("Error: " + xhr.responseText);
                $("#lockscreen").removeClass("applylockscreen");
            }
        });

    } else {
        $("#errormessage").text("All fields are required!");
        $("#diverror").addClass("applyerrordiv");
    }
}

$(function () {
    $("input").on("keyup", function () {
        $("#diverror").removeClass("applyerrordiv");

        let allFilled = $("#txtName").val().trim() && $("#txtEmail").val().trim() &&
                        $("#txtPassword").val().trim() && $("#txtConfirmPassword").val().trim();

        if (allFilled) {
            $("#btnSignup").removeClass("inactivecolor").addClass("activecolor");
        } else {
            $("#btnSignup").removeClass("activecolor").addClass("inactivecolor");
        }
    });

    $("#btnSignup").click(function () {
        trySignup();
    });
});
