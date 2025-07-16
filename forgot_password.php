<?php
session_start();
require 'includes/db_connect.php';
require 'includes/sendNotifications.php';

$error = null;
$success = null;
$show_reset_form = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'request_code') {
        $phone = trim($_POST['phone']);
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate 6-digit reset code
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', time() + 15 * 60); // 15 minutes from now

            // Delete any existing reset codes for this user
            $stmt = $pdo->prepare("DELETE FROM reset_codes WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);

            // Insert new reset code
            $stmt = $pdo->prepare("INSERT INTO reset_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['user_id'], $code, $expires_at]);

            // Send SMS
            $username = "StevenBodyJr";
            $password = "5@m3@5Y0UR5";
            $senderId = "EasyTextAPI";
            $destination = $phone;
            $message = "Your password reset code is: $code. It expires in 15 minutes.";
            $response = sendSms($username, $password, $senderId, $destination, $message);
            $data = json_decode($response, true);

            if ($data['success']) {
                $success = "A reset code has been sent to your phone.";
                $show_reset_form = true;
            } else {
                $error = "Failed to send reset code. Please try again.";
            }
        } else {
            $error = "No account found with that phone number.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $code = trim($_POST['code']);
        $new_password = $_POST['new_password'];

        // Find the reset code
        $stmt = $pdo->prepare("SELECT * FROM reset_codes WHERE code = ? AND expires_at > NOW()");
        $stmt->execute([$code]);
        $reset = $stmt->fetch();

        if ($reset) {
            // Update user's password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $reset['user_id']]);

            // Delete the reset code
            $stmt = $pdo->prepare("DELETE FROM reset_codes WHERE id = ?");
            $stmt->execute([$reset['id']]);

            $success = "Your password has been reset successfully. You can now log in.";
            // Redirect to login page with success message
            $_SESSION['login_message'] = $success;
            header("Location: login.php");
            exit;
        } else {
            $error = "Invalid or expired reset code.";
            $show_reset_form = true; // Show the form again
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <h2>Forgot Password</h2>
            <p>Reset your password using your phone number</p>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (isset($success) && !$show_reset_form): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
                <p><a href="login.php">Back to Login</a></p>
            <?php endif; ?>
            <?php if ($show_reset_form): ?>
                <!-- Reset Password Form -->
                <form method="POST" action="" class="login-form">
                    <input type="hidden" name="action" value="reset_password">
                    <div class="input-group">
                        <label for="code"><i class="fas fa-key"></i> Reset Code</label>
                        <input type="text" id="code" name="code" required placeholder="Enter the 6-digit code">
                    </div>
                    <div class="input-group">
                        <label for="new_password"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Enter new password">
                    </div>
                    <button type="submit" class="login-button">Reset Password</button>
                </form>
            <?php else: ?>
                <!-- Phone Number Form -->
                <form method="POST" action="" class="login-form">
                    <input type="hidden" name="action" value="request_code">
                    <div class="input-group">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" id="phone" name="phone" required placeholder="Enter your phone number">
                    </div>
                    <button type="submit" class="login-button">Send Reset Code</button>
                </form>
            <?php endif; ?>
            <p><a href="login.php">Back to Login</a></p>
        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>