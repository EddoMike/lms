<?php
session_start();
require 'includes/db_connect.php';
include 'includes/SMS.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("Unauthorized access attempt to order_tracking.php: No session user_id");
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$username = $_SESSION['name'] ?? 'User';

// Fetch orders
try {
    $query = $role === 'customer' ? "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC" : "SELECT * FROM orders ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $params = $role === 'customer' ? [$user_id] : [];
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Orders fetched (initial render): " . count($orders));
} catch (PDOException $e) {
    error_log("Database error fetching orders: " . $e->getMessage());
    $orders = [];
}

// Fetch payments and map them to orders
try {
    $stmt = $pdo->prepare("SELECT * FROM payments");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $payment_map = [];
    foreach ($payments as $payment) {
        $payment_map[$payment['order_id']] = $payment['payment_status'] ?? 'N/A';
    }
    error_log("Payments fetched: " . count($payments));
} catch (PDOException $e) {
    error_log("Database error fetching payments: " . $e->getMessage());
    $payment_map = [];
}

// Handle order confirmation (staff/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['staff', 'admin']) && isset($_POST['confirm_order'])) {
    try {
        $order_id = intval($_POST['order_id']);
        $new_status = $_POST['status'];
        
        // Fetch order details to get user_id and check attended_by
        $stmt = $pdo->prepare("SELECT attended_by, user_id FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Order not found");
        }

        // Update order status
        if ($new_status === 'in_progress' && $order['attended_by'] === null && $order['user_id'] !== $user_id) {
            // Assign the current staff member as the attendant for customer-placed orders
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, attended_by = ? WHERE order_id = ?");
            $stmt->execute([$new_status, $user_id, $order_id]);
            error_log("Order $order_id status updated to $new_status and assigned to staff $user_id by user $user_id");
        } else {
            // Update status only
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$new_status, $order_id]);
            error_log("Order $order_id status updated to $new_status by user $user_id");
        }
        header("Location: order_tracking.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error updating order status: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("General error updating order status: " . $e->getMessage());
    }
}

// Handle send SMS action (staff/admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['staff', 'admin']) && isset($_POST['send_sms'])) {
    try {
        $order_id = intval($_POST['order_id']);
        
        // Fetch customer details (name and phone number)
        $stmt = $pdo->prepare("SELECT u.name, u.phone FROM users u JOIN orders o ON u.user_id = o.user_id WHERE o.order_id = ?");
        $stmt->execute([$order_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer && !empty($customer['phone'])) {
            $user_name = ucfirst($customer['name']);
            $phone_number = $customer['phone'];
            $username = "StevenBodyJr";
            $password = "5@m3@5Y0UR5";
            $senderId = "EasyTextAPI";
            $message = "✔ LAUNDRY MANAGEMENT SYSTEM !\n✔\nDear $user_name,\n\nYour order (ID: $order_id) has been completed. Please collect your items at your earliest convenience. Thank you for choosing us!\n\nBest regards,\nLaundry Team";
            
            // Send SMS
            $response = sendSms($username, $password, $senderId, $phone_number, $message);
            if ($response) {
                error_log("SMS sent successfully for order $order_id to $phone_number");
            } else {
                error_log("Failed to send SMS for order $order_id to $phone_number");
            }
        } else {
            error_log("No valid phone number found for order $order_id");
        }
        header("Location: order_tracking.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error sending SMS for order $order_id: " . $e->getMessage());
    }
}

// Handle payment status update (staff)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'staff' && isset($_POST['update_payment_status'])) {
    try {
        $order_id = intval($_POST['order_id']);
        $new_payment_status = $_POST['payment_status'];

        // Check if payment exists for this order
        $stmt = $pdo->prepare("SELECT payment_id FROM payments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $existing_payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_payment) {
            $stmt = $pdo->prepare("UPDATE payments SET payment_status = ? WHERE order_id = ?");
            $stmt->execute([$new_payment_status, $order_id]);
            error_log("Payment status updated for order $order_id to $new_payment_status by user $user_id");
        } else {
            $error = "No payment record found for order $order_id.";
        }

        // Return a JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => $existing_payment !== false, 'order_id' => $order_id]);
        exit;
    } catch (PDOException $e) {
        error_log("Error updating payment status: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to update payment status']);
        exit;
    }
}

// Handle payment recording (staff)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'staff' && isset($_POST['record_payment'])) {
    try {
        $order_id = intval($_POST['order_id']);
        $amount = floatval($_POST['payment_amount']);
        $payment_method = $_POST['payment_method'];
        $payment_status = $_POST['payment_status'];
        
        $stmt = $pdo->prepare("INSERT INTO payments (order_id, amount, payment_method, payment_status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $amount, $payment_method, $payment_status]);
        error_log("Payment recorded for order $order_id by user $user_id");
        header("Location: order_tracking.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error recording payment: " . $e->getMessage());
    }
}

// Handle deletion request submission (customer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'customer' && isset($_POST['submit_deletion_request'])) {
    try {
        $order_id = intval($_POST['order_id']);
        $reason = trim($_POST['reason']);

        if (empty($reason)) {
            $error = "Please provide a reason for the deletion request.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO order_deletion_requests (order_id, user_id, reason) VALUES (?, ?, ?)");
            $stmt->execute([$order_id, $user_id, $reason]);
            error_log("Deletion request submitted for order $order_id by user $user_id");
            header("Location: order_tracking.php?success=deletion_request_submitted");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error submitting deletion request: " . $e->getMessage());
    }
}

// Handle deletion request approval/rejection (staff)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['staff', 'admin']) && isset($_POST['handle_deletion_request'])) {
    try {
        $request_id = intval($_POST['request_id']);
        $action = $_POST['action'];

        if ($action === 'approve') {
            $stmt = $pdo->prepare("SELECT order_id FROM order_deletion_requests WHERE request_id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($request) {
                $order_id = $request['order_id'];
                $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $stmt = $pdo->prepare("DELETE FROM payments WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $stmt = $pdo->prepare("UPDATE order_deletion_requests SET status = 'approved' WHERE request_id = ?");
                $stmt->execute([$request_id]);
                error_log("Deletion request $request_id approved for order $order_id by user $user_id");
            }
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE order_deletion_requests SET status = 'rejected' WHERE request_id = ?");
            $stmt->execute([$request_id]);
            error_log("Deletion request $request_id rejected by user $user_id");
        }
        header("Location: order_tracking.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error handling deletion request: " . $e->getMessage());
    }
}

// Fetch deletion requests for staff
$deletion_requests = [];
if (in_array($role, ['staff', 'admin'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM order_deletion_requests WHERE status = 'pending' ORDER BY created_at DESC");
        $stmt->execute();
        $deletion_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error fetching deletion requests: " . $e->getMessage());
    }
}

// AJAX response
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        error_log("AJAX - Session expired or invalid for user_id: " . ($_SESSION['user_id'] ?? 'unknown'));
        http_response_code(401);
        echo json_encode(['error' => 'Session expired']);
        exit;
    }
    $role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    try {
        $query = $role === 'customer' ? "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC" : "SELECT * FROM orders ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($role === 'customer' ? [$user_id] : []);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Merge payment status and attended_by name
        foreach ($orders as &$order) {
            // Add payment status
            $order['payment_status'] = $payment_map[$order['order_id']] ?? 'N/A';
            
            // Handle attended_by safely
            $attended_by_name = 'Not Assigned';
            if (isset($order['attended_by']) && !is_null($order['attended_by'])) {
                $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
                $stmt->execute([$order['attended_by']]);
                $attended_by_user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($attended_by_user) {
                    $attended_by_name = $attended_by_user['name'];
                }
            }
            $order['attended_by_name'] = $attended_by_name;
        }
        error_log("AJAX - Orders fetched: " . count($orders));
        echo json_encode($orders);
    } catch (PDOException $e) {
        error_log("AJAX error fetching orders: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch orders']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="dashboard-body" data-theme="light">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <header class="dashboard-header">
            <h2>Track Orders</h2>
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
                <h3><i class="fas fa-truck"></i> Your Orders</h3>
                <?php if (isset($_GET['success']) && $_GET['success'] === 'deletion_request_submitted'): ?>
                    <p class="success">Deletion request submitted successfully!</p>
                <?php endif; ?>
                <div id="order-list">
                    <table class="sortable-table">
                        <thead>
                            <tr>
                                <th data-sort="order_id">Order ID <i class="fas fa-sort"></i></th>
                                <th data-sort="tag_number">Tag Number <i class="fas fa-sort"></i></th>
                                <th data-sort="items">Items <i class="fas fa-sort"></i></th>
                                <th data-sort="status">Status <i class="fas fa-sort"></i></th>
                                <th data-sort="drop_off_date">Drop-off Date <i class="fas fa-sort"></i></th>
                                <th data-sort="total_amount">Amount <i class="fas fa-sort"></i></th>
                                <th data-sort="payment_status">Payment Status <i class="fas fa-sort"></i></th>
                                <th data-sort="attended_by">Attended By <i class="fas fa-sort"></i></th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="9">No orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <?php
                                    // Check if attended_by exists and is not null before querying
                                    $attended_by_name = 'Not Assigned';
                                    if (isset($order['attended_by']) && !is_null($order['attended_by'])) {
                                        $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
                                        $stmt->execute([$order['attended_by']]);
                                        $attended_by_user = $stmt->fetch(PDO::FETCH_ASSOC);
                                        if ($attended_by_user) {
                                            $attended_by_name = $attended_by_user['name'];
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['tag_number']); ?></td>
                                        <td><?php echo htmlspecialchars($order['items']); ?></td>
                                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($order['drop_off_date'])); ?></td>
                                        <td><?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                        <td><?php echo htmlspecialchars($payment_map[$order['order_id']] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($attended_by_name); ?></td>
                                        <td>
                                            <a href="receipt.php?order_id=<?php echo $order['order_id']; ?>" class="action-button">View Receipt</a>
                                            <?php if (in_array($role, ['staff', 'admin'])): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <select name="status" onchange="this.form.submit()" class="status-select">
                                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="in_progress" <?php echo $order['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                        <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                    </select>
                                                    <input type="hidden" name="confirm_order" value="1">
                                                </form>
                                               
                                                <form method="POST" action="order_tracking.php" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <button type="submit" name="send_sms" class="action-button">Send SMS</button>
                                                </form>
                                                
                                            <?php endif; ?>
                                            <?php if ($role === 'staff'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return updatePaymentStatus(event, <?php echo $order['order_id']; ?>)">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <select name="payment_status" class="status-select" onchange="this.form.submit()">
                                                        <option value="pending" <?php echo ($payment_map[$order['order_id']] ?? 'N/A') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="completed" <?php echo ($payment_map[$order['order_id']] ?? 'N/A') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="failed" <?php echo ($payment_map[$order['order_id']] ?? 'N/A') === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                    </select>
                                                    <input type="hidden" name="update_payment_status" value="1">
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($role === 'customer'): ?>
                                                <button class="action-button" onclick="openDeletionModal(<?php echo $order['order_id']; ?>)">Request Deletion</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (in_array($role, ['staff', 'admin']) && !empty($deletion_requests)): ?>
                <div class="card">
                    <h3><i class="fas fa-trash"></i> Deletion Requests</h3>
                    <table class="sortable-table">
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Reason</th>
                                <th>Requested At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deletion_requests as $request): ?>
                                <?php
                                $stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
                                $stmt->execute([$request['user_id']]);
                                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                                $customer_name = $customer['name'] ?? 'Unknown';
                                ?>
                                <tr>
                                    <td><?php echo $request['request_id']; ?></td>
                                    <td><?php echo $request['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer_name); ?></td>
                                    <td><?php echo htmlspecialchars($request['reason']); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <button type="submit" name="handle_deletion_request" value="approve" class="action-button">Approve</button>
                                            <button type="submit" name="handle_deletion_request" value="reject" class="action-button">Reject</button>
                                            <input type="hidden" name="action" value="">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Deletion Request Modal -->
    <div id="deletion-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('deletion-modal')">×</span>
            <h3>Request Order Deletion</h3>
            <form method="POST" id="deletion-form">
                <input type="hidden" name="order_id" id="deletion-order-id">
                <div class="input-group">
                    <label for="reason">Reason for Deletion</label>
                    <textarea id="reason" name="reason" required placeholder="Enter your reason"></textarea>
                </div>
                <button type="submit" name="submit_deletion_request" class="login-button">Submit Request</button>
            </form>
        </div>
    </div>
    <!-- Payment Modal -->
    <div id="payment-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('payment-modal')">×</span>
            <h3>Record Payment</h3>
            <form method="POST" id="payment-form">
                <input type="hidden" name="order_id" id="payment-order-id">
                <div class="input-group">
                    <label for="payment_amount">Amount (TZS)</label>
                    <input type="number" id="payment_amount" name="payment_amount" step="0.01" required readonly>
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
                <div class="input-group">
                    <label for="payment_status">Payment Status</label>
                    <select id="payment_status" name="payment_status" required>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <button type="submit" name="record_payment" class="login-button">Submit Payment</button>
            </form>
        </div>
    </div>
    <script src="js/scripts.js"></script>
    <script>
        // Modal Functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }

        function openPaymentModal(orderId, amount) {
            document.getElementById('payment-order-id').value = orderId;
            document.getElementById('payment_amount').value = amount.toFixed(2);
            openModal('payment-modal');
        }

        function openDeletionModal(orderId) {
            document.getElementById('deletion-order-id').value = orderId;
            openModal('deletion-modal');
        }

        // Handle payment status update via AJAX
        function updatePaymentStatus(event, orderId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Payment status updated for order:', data.order_id);
                    fetchOrders(); // Refresh table immediately
                } else {
                    console.error('Error updating payment status:', data.error);
                    alert('Failed to update payment status. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error submitting payment status:', error);
                alert('An error occurred. Please try again.');
            });

            return false; // Prevent default form submission
        }

        // Fetch orders periodically
        function fetchOrders() {
            // Using a relative path instead of absolute path to avoid 404 errors
            fetch('order_tracking.php?ajax=1', { credentials: 'same-origin' })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Fetched orders:', data); // Debug
                    if (data.error) {
                        console.error('Server error:', data.error);
                        return;
                    }
                    const orderList = document.getElementById('order-list');
                    if (orderList) {
                        const tbody = document.querySelector('tbody');
                        if (tbody) {
                            if (data.length > 0) {
                                tbody.innerHTML = '';
                                data.forEach(order => {
                                    const row = document.createElement('tr');
                                    row.innerHTML = `
                                        <td>${order.order_id}</td>
                                        <td>${order.tag_number}</td>
                                        <td>${order.items}</td>
                                        <td>${order.status}</td>
                                        <td>${new Date(order.drop_off_date).toLocaleString()}</td>
                                        <td>${(order.total_amount || 0).toFixed(2)}</td>
                                        <td>${order.payment_status || 'N/A'}</td>
                                        <td>${order.attended_by_name}</td>
                                        <td>
                                            <a href="receipt.php?order_id=${order.order_id}" class="action-button">View Receipt</a>
                                            <?php if (in_array('<?php echo $role; ?>', ['staff', 'admin'])): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="${order.order_id}">
                                                    <select name="status" onchange="this.form.submit()" class="status-select">
                                                        <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                                                        <option value="in_progress" ${order.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                                        <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Completed</option>
                                                        <option value="delivered" ${order.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                                                    </select>
                                                    <input type="hidden" name="confirm_order" value="1">
                                                </form>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="order_id" value="${order.order_id}">
                                                    <button type="submit" name="send_sms" class="action-button">Send SMS</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ('<?php echo $role; ?>' === 'staff'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return updatePaymentStatus(event, ${order.order_id})">
                                                    <input type="hidden" name="order_id" value="${order.order_id}">
                                                    <select name="payment_status" class="status-select" onchange="this.form.submit()">
                                                        <option value="pending" ${order.payment_status === 'pending' ? 'selected' : ''}>Pending</option>
                                                        <option value="completed" ${order.payment_status === 'completed' ? 'selected' : ''}>Completed</option>
                                                        <option value="failed" ${order.payment_status === 'failed' ? 'selected' : ''}>Failed</option>
                                                    </select>
                                                    <input type="hidden" name="update_payment_status" value="1">
                                                </form>
                                            <?php endif; ?>
                                            <?php if ('<?php echo $role; ?>' === 'customer'): ?>
                                                <button class="action-button" onclick="openDeletionModal(${order.order_id})">Request Deletion</button>
                                            <?php endif; ?>
                                        </td>
                                    `;
                                    tbody.appendChild(row);
                                });
                            } else {
                                tbody.innerHTML = '<tr><td colspan="9">No orders found.</td></tr>';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching orders:', error);
                });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            fetchOrders();
            setInterval(fetchOrders, 30000); // Refresh every 30 seconds
        });
    </script>
</body>
</html>