<?php
session_start();
require 'includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fetch order (including customer_name directly from orders table)
$query = $role === 'customer' ? "SELECT * FROM orders WHERE order_id = ? AND user_id = ?" : "SELECT * FROM orders WHERE order_id = ?";
$stmt = $pdo->prepare($query);
$params = $role === 'customer' ? [$order_id, $_SESSION['user_id']] : [$order_id];
$stmt->execute($params);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found or access denied.");
}

// Use customer_name from orders table if available (staff-entered), otherwise fall back to users table
$customer_name = $order['customer_name'] ?? null;
if (empty($customer_name)) {
    // Fallback to users table if customer_name is not set (e.g., for customer-placed orders)
    $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
    $stmt->execute([$order['user_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    $customer_name = $customer['name'] ?? 'Unknown';
}

// Fetch payment
$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
$stmt->execute([$order_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body class="dashboard-body" data-theme="light">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <header class="dashboard-header">
            <h2>Order Receipt</h2>
            <div class="header-controls">
                <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
        </header>
        <div class="dashboard-container">
            <div class="card" id="receipt-content">
                <h3><i class="fas fa-receipt"></i> Receipt for Order #<?php echo $order['order_id']; ?></h3>
                <div class="receipt-details">
                    <p><strong>Laundry Management System</strong></p>
                    <p><strong>Tag Number:</strong> <?php echo htmlspecialchars($order['tag_number']); ?></p>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($customer_name); ?></p>
                    <p><strong>Items:</strong> <?php echo htmlspecialchars($order['items']); ?></p>
                    <p><strong>Drop-off Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['drop_off_date'])); ?></p>
                    <?php if ($order['pickup_date']): ?>
                        <p><strong>Pickup Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['pickup_date'])); ?></p>
                    <?php endif; ?>
                    <p><strong>Total Amount:</strong> TZS <?php echo number_format($order['total_amount'], 0); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                    <?php if ($payment): ?>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?></p>
                        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($payment['payment_status']); ?></p>
                    <?php endif; ?>
                    <p><strong>Issued on:</strong> <?php echo date('Y-m-d H:i'); ?></p>
                </div>
                <button class="action-button" onclick="downloadReceipt()">Download PDF</button>
            </div>
        </div>
    </div>
    <script src="js/scripts.js"></script>
    <script>
        function downloadReceipt() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Header
            doc.setFontSize(18);
            doc.setFont("helvetica", "bold");
            doc.text("Laundry Management System", 20, 20);
            doc.setFontSize(12);
            doc.setFont("helvetica", "normal");
            doc.text("Dar es Salaam, Tanzania", 20, 30);
            doc.text("Phone: +255 123 456 789", 20, 35);
            
            // Title
            doc.setFontSize(16);
            doc.setFont("helvetica", "bold");
            doc.text(`Receipt for Order #${<?php echo $order['order_id']; ?>}`, 20, 50);
            
            // Details
            doc.setFontSize(12);
            doc.setFont("helvetica", "normal");
            let y = 60;
            doc.text(`Tag Number: <?php echo htmlspecialchars($order['tag_number']); ?>`, 20, y);
            y += 10;
            doc.text(`Customer: <?php echo htmlspecialchars($customer_name); ?>`, 20, y);
            y += 10;
            doc.text(`Items: <?php echo htmlspecialchars($order['items']); ?>`, 20, y);
            y += 10;
            doc.text(`Drop-off Date: <?php echo date('Y-m-d H:i', strtotime($order['drop_off_date'])); ?>`, 20, y);
            y += 10;
            <?php if ($order['pickup_date']): ?>
                doc.text(`Pickup Date: <?php echo date('Y-m-d H:i', strtotime($order['pickup_date'])); ?>`, 20, y);
                y += 10;
            <?php endif; ?>
            doc.text(`Total Amount: TZS <?php echo number_format($order['total_amount'], 0); ?>`, 20, y);
            y += 10;
            doc.text(`Status: <?php echo htmlspecialchars($order['status']); ?>`, 20, y);
            y += 10;
            <?php if ($payment): ?>
                doc.text(`Payment Method: <?php echo htmlspecialchars($payment['payment_method']); ?>`, 20, y);
                y += 10;
                doc.text(`Payment Status: <?php echo htmlspecialchars($payment['payment_status']); ?>`, 20, y);
                y += 10;
            <?php endif; ?>
            doc.text(`Issued on: <?php echo date('Y-m-d H:i'); ?>`, 20, y);
            
            // Footer
            doc.setFontSize(10);
            doc.setFont("helvetica", "italic");
            doc.text("Thank you for choosing our services!", 20, y + 20);
            
            doc.save(`receipt_order_${<?php echo $order['order_id']; ?>}.pdf`);
        }
    </script>
</body>
</html>