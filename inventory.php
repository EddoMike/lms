<?php
session_start();
require 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['name'] ?? 'User';

// Fetch inventory
$stmt = $pdo->prepare("SELECT * FROM inventory");
$stmt->execute();
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add item (basic form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = $_POST['item_name'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $reorder_level = $_POST['reorder_level'];

    $stmt = $pdo->prepare("INSERT INTO inventory (item_name, quantity, unit, reorder_level) VALUES (?, ?, ?, ?)");
    $stmt->execute([$item_name, $quantity, $unit, $reorder_level]);
    header("Location: inventory.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="dashboard-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <header class="dashboard-header">
            <h2>Inventory Management</h2>
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars($username); ?></span>
            </div>
        </header>
        <div class="dashboard-container">
            <div class="card">
                <h3><i class="fas fa-boxes"></i> Inventory Items</h3>
                <table class="sortable-table">
                    <thead>
                        <tr>
                            <th data-sort="item_name">Item Name <i class="fas fa-sort"></i></th>
                            <th data-sort="quantity">Quantity <i class="fas fa-sort"></i></th>
                            <th data-sort="unit">Unit <i class="fas fa-sort"></i></th>
                            <th data-sort="reorder_level">Reorder Level <i class="fas fa-sort"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td class="<?php echo $item['quantity'] <= $item['reorder_level'] ? 'warning' : ''; ?>">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                <td><?php echo $item['reorder_level']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h3><i class="fas fa-plus"></i> Add New Item</h3>
                <form method="POST" action="" class="inventory-form">
                    <div class="input-group">
                        <label for="item_name">Item Name</label>
                        <input type="text" id="item_name" name="item_name" required placeholder="e.g., Detergent">
                    </div>
                    <div class="input-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" required min="0" placeholder="e.g., 100">
                    </div>
                    <div class="input-group">
                        <label for="unit">Unit</label>
                        <input type="text" id="unit" name="unit" required placeholder="e.g., Liters">
                    </div>
                    <div class="input-group">
                        <label for="reorder_level">Reorder Level</label>
                        <input type="number" id="reorder_level" name="reorder_level" required min="0" placeholder="e.g., 10">
                    </div>
                    <button type="submit" class="login-button">Add Item</button>
                </form>
            </div>
        </div>
    </div>
    <script src="js/scripts.js"></script>
</body>
</html>