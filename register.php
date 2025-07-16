<?php
session_start();
require 'includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    // Validate role
    if (!in_array($role, ['customer', 'staff'])) {
        $error = "Invalid role selected.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $password, $role]);
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <h2>Register for LMS</h2>
            <p>Create your account to manage laundry services</p>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST" action="" class="login-form" onsubmit="return validateRegisterForm()">
                <div class="input-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="Enter your full name">
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="Enter your phone number (optional)">
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Create a password">
                </div>
                <div class="input-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="" disabled selected>Select your role</option>
                        <option value="customer">Customer</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                <button type="submit" class="login-button">Register</button>
            </form>
            <p class="register-link">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>