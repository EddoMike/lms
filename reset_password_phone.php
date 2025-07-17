<?php
/**
 * This file handles the password change functionality after a user has logged in with a temporary password
 * It allows users to set a new permanent password after receiving a temporary one via SMS
 * The user must be logged in to access this page
 */
session_start();
require 'includes/db_connect.php';

// Initialize error and success variables
$error = null;
$success = null;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Handle password change request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Get form data
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];
    
    // Validate passwords
    if ($new_password !== $confirm_password) {
        // Error if passwords don't match
        $error = "New passwords do not match.";
        error_log("Password mismatch during change for user ID: $user_id");
    } elseif (strlen($new_password) < 6) {
        // Error if password is too short
        $error = "New password must be at least 6 characters long.";
        error_log("Password too short during change for user ID: $user_id");
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($current_password, $user['password'])) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Update password in database
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Set success message
            $success = "Your password has been changed successfully.";
            error_log("Password changed successfully for user ID: $user_id");
        } else {
            // Error if current password is incorrect
            $error = "Current password is incorrect.";
            error_log("Incorrect current password during change for user ID: $user_id");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <h2>Change Password</h2>
            <p>Enter your current temporary password and set a new permanent password</p>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
                <p><a href="dashboard.php">Back to Dashboard</a></p>
            <?php else: ?>
                <form method="POST" action="" class="login-form">
                    <div class="input-group">
                        <label for="current_password"><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" id="current_password" name="current_password" required placeholder="Enter current password">
                    </div>
                    <div class="input-group">
                        <label for="new_password"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Enter new password">
                    </div>
                    <div class="input-group">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm new password">
                    </div>
                    <button type="submit" name="change_password" class="login-button">Change Password</button>
                </form>
                <p><a href="dashboard.php">Back to Dashboard</a></p>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>
