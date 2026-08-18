<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header("Location: ./");
    exit;
}

require_once('DBConnection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Login - Donut Pasal</title>
    <link rel="stylesheet" href="./Font-Awesome-master/css/all.min.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <script src="./js/jquery-3.6.0.min.js"></script>
    <script src="./js/popper.min.js"></script>
    <script src="./js/bootstrap.min.js"></script>
    <script src="./Font-Awesome-master/js/all.min.js"></script>
    <script src="./js/script.js"></script>
    <style>
    html, body {
        height: 100%;
        font-size: 16px;
    }
    body {
        background-image: url('images/login.jpg') !important;
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center center;
        backdrop-filter: brightness(0.75);
    }
    h1#sys_title {
        font-size: 3.5rem;
        text-shadow: 3px 3px 15px rgba(0, 0, 0, 0.7);
        letter-spacing: 1px;
    }
    .card {
        border: none;
        backdrop-filter: blur(10px);
        background-color: rgba(255, 255, 255, 0.92);
    }
    @media screen and (max-width: 768px) {
        h1#sys_title {
            font-size: 2.3rem;
            padding: 1rem 0;
        }
        .card.col-md-4.offset-md-4 {
            width: 90%;
            margin: 0 auto;
        }
    }
    </style>
</head>
<body>
   <div class="h-100 d-flex justify-content-center align-items-center">
       <div class="w-100 px-3">
        <h1 class="py-3 text-center text-white fw-bold px-4" id="sys_title"><i class="fa fa-cookie-bite me-2 text-warning"></i>DONUT PASAL</h1>
    
        <div class="card my-3 col-md-4 offset-md-4 shadow-lg rounded-4 overflow-hidden">
            <div class="card-body p-4 p-sm-5">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-dark">Welcome Back</h4>
                    <p class="text-muted small">Please sign in to your account</p>
                </div>
                <form action="" id="login-form">
                    <div class="mb-3">
                        <label for="username" class="form-label small fw-semibold text-muted">Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa fa-user text-muted"></i></span>
                            <input type="text" id="username" autofocus name="username" class="form-control rounded-end-3 bg-light border-0 py-2" placeholder="Enter username" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-muted">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa fa-lock text-muted"></i></span>
                            <input type="password" id="password" name="password" class="form-control bg-light border-0 py-2" placeholder="Enter password" required>
                            <button class="btn btn-light border-0 bg-light text-muted px-3" type="button" id="togglePassword" title="Show/Hide Password">
                                <i class="fa fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 shadow-sm fw-semibold"><i class="fa fa-sign-in-alt me-2"></i>Log In</button>
                    </div>
                    <div class="text-center pt-2 border-top">
                        <a href="Forget.php" class="text-decoration-none text-muted small hover-primary">Forgot Password?</a>
                    </div>
                </form>
            </div>
        </div>
       </div>
   </div>

<script>
    $(function(){
        // Show/Hide Password Feature Integration
        $('#togglePassword').click(function(){
            var passwordInput = $('#password');
            var toggleIcon = $('#toggleIcon');
            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordInput.attr('type', 'password');
                toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        $('#login-form').submit(function(e){
            e.preventDefault();
            $('.pop_msg').remove();
            var _this = $(this);
            var _btn = _this.find('button[type="submit"]');
            var _el = $('<div>').addClass('pop_msg alert alert-dismissible fade show mb-3');

            _btn.attr('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Logging in...');

            $.ajax({
                url: './Actions.php?a=login',
                method: 'POST',
                data: _this.serialize(),
                dataType: 'JSON',
                error: function(err){
                    console.log(err);
                    _el.addClass('alert-danger').text("An error occurred. Please try again.");
                    _this.prepend(_el);
                    _btn.attr('disabled', false).html('<i class="fa fa-sign-in-alt me-2"></i>Log In');
                },
                success: function(resp){
                    if(resp && resp.status == 'success'){
                        _el.addClass('alert-success').text(resp.msg || 'Login successful. Redirecting...');
                        setTimeout(() => {
                            location.replace('./');
                        }, 1000);
                    } else {
                        _el.addClass('alert-danger').text(resp && resp.msg ? resp.msg : "Invalid username or password.");
                        _btn.attr('disabled', false).html('<i class="fa fa-sign-in-alt me-2"></i>Log In');
                    }
                    _this.prepend(_el);
                }
            });
        });
    });
</script>
</body>
</html>