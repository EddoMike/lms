<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Welcome to the Laundry Management System</h2>
        <p>Streamline your laundry operations with our automated system.</p>
        <div>
            <a href="login.php" class="button">Login</a>
            <a href="register.php" class="button">Register</a>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="js/scripts.js"></script>
</body>
</html>