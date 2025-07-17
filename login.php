<?php
/**
 * Login page for the Laundry Management System
 * Handles user authentication and password reset via SMS
 * Includes phone-based password reset functionality
 * Displays success messages from password reset process
 */
session_start();
require 'includes/db_connect.php';
require 'includes/sendNotifications.php'; // Already contains sendSms function

// Check for success message from password reset
$success = null;
if (isset($_SESSION['login_message'])) {
    $success = $_SESSION['login_message'];
    unset($_SESSION['login_message']); // Clear the message after displaying it
}

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

// Handle password reset request - redirect to forgot_password.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    // Get the phone number from the form
    $phone = trim($_POST['phone']);
    
    // Validate phone number format (basic validation)
    if (empty($phone)) {
        $error = "Please enter your phone number.";
        error_log("Empty phone number submitted for password reset");
    } else {
        // Redirect to forgot_password.php with the phone number as a parameter
        // This uses the existing phone-based reset functionality
        header("Location: forgot_password.php?phone=" . urlencode($phone));
        exit;
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
                <p>Enter your phone number to receive a temporary password via SMS</p>
                <form method="POST" action="" class="login-form">
                    <div class="input-group">
                        <label for="reset_phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" id="reset_phone" name="phone" required placeholder="Enter your phone number">
                    </div>
                    <button type="submit" name="reset_password" class="login-button">Send Temporary Password</button>
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