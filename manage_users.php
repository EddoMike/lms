<?php
session_start();
require 'includes/db_connect.php';

// Restrict to admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log("Unauthorized access attempt to manage_users.php by user_id: " . ($_SESSION['user_id'] ?? 'unknown'));
    header("Location: login.php");
    exit;
}

$username = $_SESSION['name'] ?? 'Admin';
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
        $error_message = "Invalid phone number format (use +255123456789).";
    } elseif ($role !== 'staff') {
        $error_message = "Invalid role selected.";
    } else {
        try {
            // Check if email or phone exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$email, $phone]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = "Email or phone number already registered.";
            } else {
                // Insert staff
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $hashed_password, $role]);
                $success_message = "Staff account created successfully.";
                error_log("Staff created by admin: $email");
            }
        } catch (PDOException $e) {
            error_log("Staff creation error: " . $e->getMessage());
            $error_message = "Failed to create staff account. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="manage-users-body" data-theme="light">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <header class="manage-users-header">
            <h2>Manage Users</h2>
            <div class="header-controls">
                <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
        </header>
        <div class="form-container">
            <h3>Create Staff Account</h3>
            <?php if (!empty($success_message)): ?>
                <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="+255123456789" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <input type="hidden" name="role" value="staff">
                <button type="submit" class="btn">Create Staff</button>
            </form>
        </div>
    </div>
    <script src="js/scripts.js"></script>
    <script>
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const body = document.body;
            body.dataset.theme = body.dataset.theme === 'light' ? 'dark' : 'light';
        });
    </script>
</body>
</html>