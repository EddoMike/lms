<?php
session_start();
require 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['customer', 'staff'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['name'] ?? 'User';
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch clothing items
$stmt = $pdo->prepare("SELECT * FROM clothing_items");
$stmt->execute();
$clothing_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = [];
    $total_amount = 0;
    foreach ($clothing_items as $item) {
        $quantity = isset($_POST['quantity_' . $item['item_id']]) ? (int)$_POST['quantity_' . $item['item_id']] : 0;
        if ($quantity > 0) {
            $items[] = $quantity . ' ' . $item['name'];
            $total_amount += $quantity * $item['price'];
        }
    }
    $items = implode(', ', $items);
    $drop_off_date = $_POST['drop_off_date'];
    $payment_method = $_POST['payment_method'];
    $status = 'pending';

    // Generate unique tag number
    $current_year = date('Y');
    $stmt = $pdo->query("SELECT tag_number FROM orders WHERE tag_number LIKE 'ORD-$current_year%' ORDER BY tag_number DESC LIMIT 1");
    $last_tag = $stmt->fetchColumn();
    $sequence = 0;
    if ($last_tag) {
        $parts = explode('-', $last_tag);
        $sequence = (int)$parts[2]; // Extract the numeric part (e.g., 0023)
    }
    $tag_number = sprintf("ORD-%s-%04d", $current_year, $sequence + 1);

    // Insert order with customer details for staff and auto-assign attended_by for staff
    $customer_name = $role === 'staff' ? trim($_POST['customer_name']) : null;
    $customer_phone = $role === 'staff' ? trim($_POST['customer_phone']) : null;
    $customer_email = $role === 'staff' ? trim($_POST['customer_email']) : null;
    $customer_address = $role === 'staff' ? trim($_POST['customer_address']) : null;
    $attended_by = $role === 'staff' ? $user_id : null;

    $stmt = $pdo->prepare("INSERT INTO orders (user_id, tag_number, items, drop_off_date, status, total_amount, customer_name, customer_phone, customer_email, customer_address, attended_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $tag_number, $items, $drop_off_date, $status, $total_amount, $customer_name, $customer_phone, $customer_email, $customer_address, $attended_by]);

    $order_id = $pdo->lastInsertId();

    // Insert payment
    $stmt = $pdo->prepare("INSERT INTO payments (order_id, amount, payment_method, payment_status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$order_id, $total_amount, $payment_method]);

    header("Location: order_tracking.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="dashboard-body" data-theme="light">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <header class="dashboard-header">
            <h2>Place Order</h2>
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
                <h3><i class="fas fa-plus"></i> New Order</h3>
                <form method="POST" action="" class="order-form" onsubmit="return validateOrderForm()">
                    <?php if ($role === 'staff'): ?>
                        <div class="input-group">
                            <label>Customer Details</label>
                            <table class="clothing-table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Name</td>
                                        <td><input type="text" name="customer_name" required placeholder="Enter customer name"></td>
                                    </tr>
                                    <tr>
                                        <td>Phone Number</td>
                                        <td><input type="text" name="customer_phone" required placeholder="Enter phone number"></td>
                                    </tr>
                                    <tr>
                                        <td>Email</td>
                                        <td><input type="email" name="customer_email" placeholder="Enter email (optional)"></td>
                                    </tr>
                                    <tr>
                                        <td>Address</td>
                                        <td><input type="text" name="customer_address" required placeholder="Enter customer address"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <div class="input-group">
                        <label>Clothing Items</label>
                        <table class="clothing-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price (TZS)</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clothing_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo number_format($item['price'], 0); ?></td>
                                        <td>
                                            <input type="number" name="quantity_<?php echo $item['item_id']; ?>" min="0" value="0" class="quantity-input" data-price="<?php echo $item['price']; ?>" onchange="updateTotal()">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="input-group">
                        <label>Total Amount (TZS)</label>
                        <input type="text" id="total_amount" readonly value="0">
                    </div>
                    <div class="input-group">
                        <label for="drop_off_date">Drop-off Date</label>
                        <input type="datetime-local" id="drop_off_date" name="drop_off_date" required>
                    </div>
                    <div class="input-group">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                    <button type="submit" class="login-button">Submit Order</button>
                </form>
            </div>
        </div>
    </div>
    <script src="js/scripts.js"></script>
    <script>
        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.quantity-input').forEach(input => {
                const quantity = parseInt(input.value) || 0;
                const price = parseFloat(input.getAttribute('data-price'));
                total += quantity * price;
            });
            document.getElementById('total_amount').value = total.toFixed(0);
        }

        function validateOrderForm() {
            const quantities = document.querySelectorAll('.quantity-input');
            let hasItems = false;
            quantities.forEach(input => {
                if (parseInt(input.value) > 0) hasItems = true;
            });
            if (!hasItems) {
                alert('Please select at least one clothing item.');
                return false;
            }
            const dropOffDate = document.getElementById('drop_off_date');
            if (!dropOffDate.value) {
                alert('Please select a drop-off date.');
                return false;
            }
            const paymentMethod = document.getElementById('payment_method');
            if (!paymentMethod.value) {
                alert('Please select a payment method.');
                return false;
            }
            if ('<?php echo $role; ?>' === 'staff') {
                const customerName = document.querySelector('input[name="customer_name"]');
                const customerPhone = document.querySelector('input[name="customer_phone"]');
                const customerAddress = document.querySelector('input[name="customer_address"]');
                if (!customerName.value || !customerPhone.value || !customerAddress.value) {
                    alert('Please fill in all required customer details (name, phone, and address).');
                    return false;
                }
            }
            return true;
        }
    </script>
</body>
</html>