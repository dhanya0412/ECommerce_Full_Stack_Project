function tryLogin() {
    let un = $("#txtUsername").val();
    let pw = $("#txtPassword").val();

    // Debug: Check input values
    console.log("Username:", un, "Password:", pw);

    if (un.trim() !== "" && pw.trim() !== "") {
        // Debug: Verify data before sending request
        console.log("Sending Data:", { cust_email: un, cust_password: pw, action: "verifyUser" });

        $.ajax({
            url: "/ecommerce_project/ajaxhandler/loginAjax.php", // Ensure correct path
            type: "POST",
            dataType: "json",
            data: { cust_email: un, cust_password: pw, action: "verifyUser" },

            beforeSend: function () {
                console.log("AJAX call is about to be made...");
                $("#diverror").removeClass("applyerrordiv");
                $("#lockscreen").addClass("applylockscreen");
            },

            success: function (rv) {
                console.log("AJAX Success Response:", rv); // Debug: Log response

                $("#lockscreen").removeClass("applylockscreen");

                if (rv['status'] === "ALL OK") {
                    console.log("Login Successful! Redirecting...");
                    document.location.replace("e_commerce.php");
                } else {
                    console.warn("Login Failed:", rv['status']); // Debug: Warn if login fails
                    $("#diverror").addClass("applyerrordiv");
                    $("#errormessage").text(rv['status']);
                }
            },

            error: function (xhr, status, error) {
                console.error("AJAX Error:", error); // Debug: Log AJAX error
                console.log("Full Response:", xhr.responseText); // Debug: Show full error
                alert("Oops something went wrong: " + xhr.responseText);
            },
        });
    } else {
        console.warn("Username or password is empty!"); // Debug: Log empty input
    }
}

// Do everything only when the document is loaded
$(function () {
    // Capture the keyup event
    $(document).on("keyup", "input", function () {
        $("#diverror").removeClass("applyerrordiv");

        let un = $("#txtUsername").val();
        let pw = $("#txtPassword").val();

        if (un.trim() !== "" && pw.trim() !== "") {
            $("#btnLogin").removeClass("inactivecolor").addClass("activecolor");
        } else {
            $("#btnLogin").removeClass("activecolor").addClass("inactivecolor");
        }
    });

    // Capture login button click event
    $(document).on("click", "#btnLogin", function () {
        console.log("Login button clicked"); // Debug: Ensure button is being clicked
        tryLogin();
    });
});
