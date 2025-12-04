<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JIPMER Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light">

<!-- Fixed Header -->
<?php include 'header/login_header.php'; ?>

<!-- Login Form Section -->
<div class="d-flex justify-content-center align-items-center">
    <div class="card shadow p-4 rounded-4" style="width: 100%; max-width: 400px;">
        <h2 class="card-title text-center mb-3 fw-bold">Login</h2>
        <p class="text-center text-muted mb-4">Enter your username and password</p>

        <div id="errorMsg" class="text-danger mb-3"></div>

        <form id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-semibold" style="padding: 12px; font-size: 16px;">Login</button>
        </form>
    </div>
</div>

<!-- jQuery AJAX for login -->
<script>
$(document).ready(function(){
    $("#loginForm").submit(function(e){
        e.preventDefault();
        var username = $("#username").val();
        var password = $("#password").val();

        $.ajax({
            url: "login_ajax.php",
            type: "POST",
            data: { username: username, password: password },
            success: function(response){
                if(response.trim() === 'success'){
                    window.location.href = "dashboard.php";
                } else {
                    $("#errorMsg").html(response);
                }
            },
            error: function(xhr, status, error){
                $("#errorMsg").html("Server error. Please try again.");
            }
        });
    });
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
