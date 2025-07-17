<?php
/**
 * Profile Management Page for Laundry Management System
 * Allows users to view and update their profile information
 * Includes functionality to upload and manage profile pictures
 * Images are stored in assets/images directory
 */
session_start();
require 'includes/db_connect.php';

// Set timezone to East African Time (Arusha, Tanzania)
date_default_timezone_set('Africa/Dar_es_Salaam');

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['name'] ?? 'User';

// Initialize variables
$success = $error = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($phone)) {
        $error = "All fields are required";
    } else {
        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $error = "Email already in use by another account";
        } else {
            // Process profile picture upload if present
            $profile_picture = $user['profile_picture']; // Default to current picture
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $file_type = $_FILES['profile_picture']['type'];
                
                // Validate file type
                if (!in_array($file_type, $allowed_types)) {
                    $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
                } else {
                    // Create directory if it doesn't exist
                    $upload_dir = 'assets/images/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $file_name = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                        // Delete old profile picture if it's not the default
                        if ($user['profile_picture'] !== 'assets/images/default-profile.png' && 
                            file_exists($user['profile_picture']) && 
                            $user['profile_picture'] !== $target_file) {
                            unlink($user['profile_picture']);
                        }
                        
                        $profile_picture = $target_file;
                    } else {
                        $error = "Failed to upload profile picture";
                    }
                }
            }
            
            // If no errors, update user profile
            if (empty($error)) {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, profile_picture = ? WHERE user_id = ?");
                    $stmt->execute([$name, $email, $phone, $profile_picture, $user_id]);
                    
                    // Update session variables
                    $_SESSION['name'] = $name;
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $success = "Profile updated successfully";
                    
                    // Log the update
                    error_log("User ID: $user_id updated their profile");
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                    error_log("Profile update error: " . $e->getMessage());
                }
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            try {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $success = "Password changed successfully";
                error_log("User ID: $user_id changed their password");
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                error_log("Password change error: " . $e->getMessage());
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Additional styles for profile page */
        .profile-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px;
        }
        
        .profile-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            flex: 1;
            min-width: 300px;
        }
        
        /* Dark theme support for profile card */
        body[data-theme="dark"] .profile-card {
            background: #2a2a3d;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #28a745;
        }
        
        .profile-info h2 {
            margin: 0;
            color: #2d3436;
        }
        
        body[data-theme="dark"] .profile-info h2 {
            color: #e0e0e0;
        }
        
        .profile-info p {
            margin: 5px 0;
            color: #636e72;
        }
        
        body[data-theme="dark"] .profile-info p {
            color: #b0b0cc;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2d3436;
        }
        
        body[data-theme="dark"] .form-group label {
            color: #e0e0e0;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #dfe6e9;
            border-radius: 5px;
            background: #fff;
            color: #2d3436;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        body[data-theme="dark"] .form-group input {
            background: #33334d;
            border-color: #444464;
            color: #e0e0e0;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 6px rgba(40, 167, 69, 0.3);
        }
        
        .form-actions {
            margin-top: 20px;
        }
        
        .btn-update {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }
        
        .btn-update:hover {
            background: #219653;
            transform: translateY(-3px);
        }
        
        body[data-theme="dark"] .btn-update {
            background: #219653;
        }
        
        body[data-theme="dark"] .btn-update:hover {
            background: #1b7b41;
        }
        
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .file-upload {
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .file-upload input[type=file] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            cursor: pointer;
            display: block;
        }
        
        .file-upload-btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .file-upload-btn:hover {
            background: #0056b3;
        }
        
        body[data-theme="dark"] .file-upload-btn {
            background: #219653;
        }
        
        body[data-theme="dark"] .file-upload-btn:hover {
            background: #1b7b41;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #dfe6e9;
        }
        
        body[data-theme="dark"] .tabs {
            border-bottom-color: #444464;
        }
        
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            color: #636e72;
        }
        
        body[data-theme="dark"] .tab {
            color: #b0b0cc;
        }
        
        .tab.active {
            border-bottom: 2px solid #28a745;
            color: #28a745;
        }
        
        body[data-theme="dark"] .tab.active {
            border-bottom-color: #219653;
            color: #219653;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-picture {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body class="dashboard-body" data-theme="light">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <header class="dashboard-header">
            <h2>Profile Management</h2>
            <div class="header-controls">
                <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
                <div class="user-info">
                    <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'assets/images/default-profile.png'); ?>" alt="Profile" class="profile-picture" style="width: 30px; height: 30px;">
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
        </header>
        
        <div class="profile-container">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="width: 100%;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="width: 100%;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-card">
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'assets/images/default-profile.png'); ?>" alt="Profile Picture" class="profile-picture">
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p><?php echo htmlspecialchars($user['role']); ?></p>
                        <p>Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="tabs">
                    <div class="tab active" data-tab="profile">Profile Information</div>
                    <div class="tab" data-tab="password">Change Password</div>
                </div>
                
                <div id="profile-tab" class="tab-content active">
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_picture">Profile Picture</label>
                            <div class="file-upload">
                                <label for="profile_picture" class="file-upload-btn">
                                    <i class="fas fa-upload"></i> Choose File
                                </label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg, image/png, image/gif">
                            </div>
                            <small id="file-name" style="display: block; margin-top: 5px;">No file chosen</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn-update">Update Profile</button>
                        </div>
                    </form>
                </div>
                
                <div id="password-tab" class="tab-content">
                    <form action="profile.php" method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn-update">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // File upload preview
        document.getElementById('profile_picture').addEventListener('change', function() {
            const fileName = this.files[0]?.name || 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });
        
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                // Show the corresponding tab content
                document.getElementById(this.dataset.tab + '-tab').classList.add('active');
            });
        });
        
        // Theme toggle
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Update icon
            const icon = this.querySelector('i');
            if (newTheme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        });
        
        // Set theme on page load
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', savedTheme);
            
            const icon = document.querySelector('.theme-toggle i');
            if (savedTheme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        })();
    </script>
</body>
</html>
