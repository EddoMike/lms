<?php
session_start();
require 'includes/db_connect.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = $_GET['token'];
    // Verify token and check if it's not expired (1 hour)
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = "Invalid or expired reset token.";
        error_log("Invalid or expired reset token: $token");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
        error_log("Password mismatch during reset for token: $token");
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
        error_log("Password too short during reset for token: $token");
    } else {
        // Verify token again
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if ($reset) {
            $email = $reset['email'];
            // Update password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_password, $email]);

            // Delete the used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            $success = "Your password has been reset successfully. You can now log in.";
            error_log("Password reset successfully for email: $email");
        } else {
            $error = "Invalid or expired reset token.";
            error_log("Invalid or expired reset token during POST: $token");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <h2>Reset Password</h2>
            <p>Enter your new password below</p>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
                <p><a href="login.php">Back to Login</a></p>
            <?php else: ?>
                <form method="POST" action="" class="login-form">
                    <input type="hidden" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">
                    <div class="input-group">
                        <label for="password"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" id="password" name="password" required placeholder="Enter new password">
                    </div>
                    <div class="input-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm new password">
                    </div>
                    <button type="submit" name="reset_password" class="login-button">Reset Password</button>
                </form>
                <p><a href="login.php">Back to Login</a></p>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>