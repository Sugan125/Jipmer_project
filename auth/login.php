<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JIPMER Department Login</title>
<!-- Local Bootstrap -->
<link href="../css/bootstrap.min.css" rel="stylesheet">
<!-- Custom CSS -->
<link href="../css/style.css" rel="stylesheet"> 
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="login-card shadow">
                <div class="login-header">
                    <img src="../images/logo.png" alt="JIPMER Logo">
                    <h5>Department Login</h5>
                    <p>Jawaharlal Institute of Postgraduate Medical Education and Research, Puducherry</p>
                </div>

                <div id="errorMsg"></div>

                <form id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Enter password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- jQuery + SweetAlert -->
<script src="../js/jquery-3.7.1.min.js"></script>
<!-- Local SweetAlert -->
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){
    $("#loginForm").submit(function(e){
        e.preventDefault();
        var username = $("#username").val();
        var password = $("#password").val();

        $.ajax({
            url: "login_ajax.php",
            type: "POST",
            data: {username: username, password: password},
            dataType: "json", // <-- parse response as JSON
            success: function(response){
                if(response.status === 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful',
                        showConfirmButton: false,
                        timer: 1200
                    }).then(() => {
                        window.location.href = response.redirect; 
                    });
                } else {
                    $("#errorMsg").html(response.message || "Invalid credentials");
                }
            },
            error: function(){
                $("#errorMsg").html("Server error. Please try again.");
            }
        });
    });
});

</script>

</body>
</html>
