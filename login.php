<?php
session_start();
require 'includes/db_connect.php';
require 'includes/sendNotifications.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        error_log("User logged in: $email");
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid email or password";
        error_log("Login failed for email: $email");
    }
}

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate a unique token
        $token = bin2hex(random_bytes(32));
        $reset_link = "http://".$_SERVER['HTTP_HOST']."/reset_password.php?token=$token";

        // Store token in password_resets table
        try {
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?) ON DUPLICATE KEY UPDATE token = ?, created_at = NOW()");
            $stmt->execute([$email, $token, $token]);
            
            // Send reset email
            $subject = "Password Reset Request - Laundry Management System";
            $message = "Dear {$user['name']},<br><br>";
            $message .= "You have requested to reset your password. Click the link below to reset it:<br>";
            $message .= "<a href='$reset_link'>Reset Password</a><br><br>";
            $message .= "If you did not request this, please ignore this email.<br>";
            $message .= "This link will expire in 1 hour.<br><br>";
            $message .= "Best regards,<br>Laundry Management System";
            
            $email_response = json_decode(sendEmail($email, $subject, $message), true);
            if ($email_response['success']) {
                $success = "A password reset link has been sent to your email.";
                error_log("Password reset link sent to: $email");
            } else {
                $error = "Failed to send reset email. Please try again.";
                error_log("Failed to send reset email to $email: " . $email_response['message']);
            }
        } catch (PDOException $e) {
            $error = "Failed to process reset request. Please try again.";
            error_log("Error storing reset token for $email: " . $e->getMessage());
        }
    } else {
        $error = "No account found with that email.";
        error_log("Password reset attempt for non-existent email: $email");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <h2>LOGIN TO LMS</h2>
            <p>Access your laundry management dashboard</p>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>
            <!-- Login Form -->
            <form method="POST" action="" class="login-form">
                <div class="input-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                <div class="input-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" name="login" class="login-button">Sign In</button>
            </form>
            <p class="forgot-password"><a href="#" onclick="showResetForm()">Forgot Password?</a></p>
            <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
            
            <!-- Password Reset Form (Hidden by default) -->
            <div id="reset-form" style="display: none;">
                <h3>Reset Password</h3>
                <form method="POST" action="" class="login-form">
                    <div class="input-group">
                        <label for="reset_email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="reset_email" name="email" required placeholder="Enter your email">
                    </div>
                    <button type="submit" name="reset_password" class="login-button">Send Reset Link</button>
                </form>
                <p><a href="#" onclick="showLoginForm()">Back to Login</a></p>
            </div>
        </div>
    </div>
    <script src="js/scripts.js"></script>
    <script>
        function showResetForm() {
            document.querySelector('.login-form').style.display = 'none';
            document.querySelector('.register-link').style.display = 'none';
            document.querySelector('.forgot-password').style.display = 'none';
            document.getElementById('reset-form').style.display = 'block';
        }

        function showLoginForm() {
            document.querySelector('.login-form').style.display = 'block';
            document.querySelector('.register-link').style.display = 'block';
            document.querySelector('.forgot-password').style.display = 'block';
            document.getElementById('reset-form').style.display = 'none';
        }
    </script>
</body>
</html>