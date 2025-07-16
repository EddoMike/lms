<?php
session_start();
require 'includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    error_log("Unauthorized access attempt to manage_clothes.php by user: " . ($_SESSION['user_id'] ?? 'unknown'));
    header("Location: login.php");
    exit;
}

$username = $_SESSION['name'] ?? 'Admin';

// Handle adding a new clothing item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_clothing'])) {
    try {
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        
        // Validate input
        if (empty($name) || $price <= 0) {
            $error = "Please provide a valid name and price.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO clothing_items (name, price) VALUES (?, ?)");
            $stmt->execute([$name, $price]);
            error_log("Clothing item added: $name, Price: $price");
            header("Location: manage_clothes.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error adding clothing item: " . $e->getMessage());
        $error = "Failed to add clothing item. Please try again.";
    }
}

// Handle editing a clothing item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_clothing'])) {
    try {
        $item_id = intval($_POST['item_id']);
        $name = trim($_POST['name']);
        $price = floatval($_POST['price']);
        
        // Validate input
        if (empty($name) || $price <= 0) {
            $error = "Please provide a valid name and price.";
        } else {
            $stmt = $pdo->prepare("UPDATE clothing_items SET name = ?, price = ? WHERE item_id = ?");
            $stmt->execute([$name, $price, $item_id]);
            error_log("Clothing item edited: Item ID $item_id, Name: $name, Price: $price");
            header("Location: manage_clothes.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error editing clothing item: " . $e->getMessage());
        $error = "Failed to edit clothing item. Please try again.";
    }
}

// Handle deleting a clothing item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_clothing'])) {
    try {
        $item_id = intval($_POST['item_id']);
        $stmt = $pdo->prepare("DELETE FROM clothing_items WHERE item_id = ?");
        $stmt->execute([$item_id]);
        error_log("Clothing item deleted: Item ID $item_id");
        header("Location: manage_clothes.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting clothing item: " . $e->getMessage());
        $error = "Failed to delete clothing item. Please try again.";
    }
}

// Fetch all clothing items
try {
    $stmt = $pdo->prepare("SELECT * FROM clothing_items");
    $stmt->execute();
    $clothing_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Clothing items fetched: " . count($clothing_items));
} catch (PDOException $e) {
    error_log("Error fetching clothing items: " . $e->getMessage());
    $clothing_items = [];
    $error = "Failed to fetch clothing items.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clothes - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="dashboard-body" data-theme="light">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <header class="dashboard-header">
            <h2>Manage Clothes</h2>
            <div class="header-controls">
                <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
        </header>
        <div class="dashboard-container">
            <div class="card">
                <h3><i class="fas fa-tshirt"></i> Add New Clothing Item</h3>
                <?php if (isset($error)): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>
                <form method="POST" class="form">
                    <div class="input-group">
                        <label for="name">Clothing Name</label>
                        <input type="text" id="name" name="name" placeholder="e.g., Shirt" required>
                    </div>
                    <div class="input-group">
                        <label for="price">Price ($)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" placeholder="e.g., 5.00" required>
                    </div>
                    <button type="submit" name="add_clothing" class="login-button">Add Clothing</button>
                </form>
            </div>
            <div class="card">
                <h3><i class="fas fa-list"></i> Clothing Items</h3>
                <div id="clothing-list">
                    <table class="sortable-table">
                        <thead>
                            <tr>
                                <th data-sort="item_id">Item ID <i class="fas fa-sort"></i></th>
                                <th data-sort="name">Name <i class="fas fa-sort"></i></th>
                                <th data-sort="price">Price ($) <i class="fas fa-sort"></i></th>
                                <th data-sort="created_at">Added On <i class="fas fa-sort"></i></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clothing_items)): ?>
                                <tr><td colspan="5">No clothing items found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($clothing_items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['item_id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?></td>
                                        <td>
                                            <button class="action-button" onclick="openEditModal(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['price']; ?>)">Edit</button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                                <button type="submit" name="delete_clothing" class="action-button danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Clothing Modal -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('edit-modal')">×</span>
            <h3>Edit Clothing Item</h3>
            <form method="POST" id="edit-form">
                <input type="hidden" name="item_id" id="edit-item-id">
                <div class="input-group">
                    <label for="edit-name">Clothing Name</label>
                    <input type="text" id="edit-name" name="name" required>
                </div>
                <div class="input-group">
                    <label for="edit-price">Price ($)</label>
                    <input type="number" id="edit-price" name="price" step="0.01" min="0" required>
                </div>
                <button type="submit" name="edit_clothing" class="login-button">Update Clothing</button>
            </form>
        </div>
    </div>

    <script src="js/scripts.js"></script>
    <script>
        function openEditModal(itemId, name, price) {
            document.getElementById('edit-item-id').value = itemId;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-price').value = price.toFixed(2);
            openModal('edit-modal');
        }
    </script>
</body>
</html>