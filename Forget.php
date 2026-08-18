<?php
require_once('DBConnection.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Auto-include PHPMailer libraries safely
if (file_exists('phpmailer/src/Exception.php')) {
    require_once 'phpmailer/src/Exception.php';
    require_once 'phpmailer/src/PHPMailer.php';
    require_once 'phpmailer/src/SMTP.php';
}

$msg = '';
$alert_type = '';

if (isset($_POST['pwdrst'])) {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please provide a valid email address.";
        $alert_type = "danger";
    } else {
        // Check if email exists in the database securely using Prepared Statements
        $stmt = $conn->prepare("SELECT user_id FROM user_list WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            // Generate temporary secure password
            $raw_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
            $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);

            // Update user's password in database securely
            $update_stmt = $conn->prepare("UPDATE user_list SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $hashed_password, $email);
            
            if ($update_stmt->execute()) {
                $mail = new PHPMailer(true);

                try {
                    // Configure SMTP settings (Use environment variables or define fallbacks)
                    $smtp_host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
                    $smtp_user = defined('SMTP_USER') ? SMTP_USER : 'karkisujan590@gmail.com';
                    $smtp_pass = defined('SMTP_PASS') ? SMTP_PASS : 'axiy ktkl nvde voaj';
                    $smtp_port = defined('SMTP_PORT') ? SMTP_PORT : 587;

                    $mail->isSMTP();
                    $mail->Host = $smtp_host;
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtp_user;
                    $mail->Password = $smtp_pass;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = $smtp_port;
                    
                    // Set email content
                    $mail->setFrom($smtp_user, "Bakery Management System Admin");
                    $mail->addAddress($email);
                    $mail->isHTML(false);
                    $mail->Subject = "Password Reset - Bakery Management System";
                    $mail->Body = "Hello,\n\nYour password has been successfully reset.\nYour new temporary password is: $raw_password\n\nPlease log in and update your password immediately.";

                    $mail->send();
                    $msg = "Password reset email sent successfully. Please check your inbox.";
                    $alert_type = "success";
                } catch (Exception $e) {
                    $msg = "Failed to send email. Error: " . $mail->ErrorInfo;
                    $alert_type = "danger";
                }
            } else {
                $msg = "An error occurred while updating your password. Please try again.";
                $alert_type = "danger";
            }
            $update_stmt->close();
        } else {
            $msg = "No account found with that email address.";
            $alert_type = "danger";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | Forgot Password - Donut Pasal</title>
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
                    <h4 class="fw-bold text-dark">Forgot Password</h4>
                    <p class="text-muted small">Enter your email to reset your password</p>
                </div>
                <form id="validate_form" method="post" action="">  
                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($alert_type); ?> alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
                            <i class="fa fa-info-circle me-2"></i><?php echo htmlspecialchars($msg); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-4">
                        <label for="email" class="form-label small fw-semibold text-muted">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa fa-envelope text-muted"></i></span>
                            <input type="email" name="email" id="email" placeholder="Enter Email" required class="form-control bg-light border-0 py-2" />
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" id="login" name="pwdrst" class="btn btn-primary rounded-pill py-2 shadow-sm fw-semibold"><i class="fa fa-key me-2"></i>Reset Password</button>
                    </div>
                    
                    <div class="text-center pt-2 border-top">
                        <span class="text-muted small">Remember your password?</span> <a href="login.php" class="text-decoration-none fw-semibold text-primary">Log In</a>
                    </div>
                </form>
            </div>
        </div>
       </div>
   </div>
</body>
</html>