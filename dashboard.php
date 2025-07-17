<?php
/**
 * Dashboard page for the Laundry Management System
 * Shows role-specific data and system statistics
 * Includes improved user profile icon with link to profile management
 */
session_start();
require 'includes/db_connect.php';

// Set timezone to East African Time (Arusha, Tanzania)
date_default_timezone_set('Africa/Dar_es_Salaam');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch role-specific data
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$username = $_SESSION['name'] ?? 'User';

// Fetch user profile picture
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);
$profile_picture = $user_data['profile_picture'] ?? 'assets/images/default-profile.png';

// Orders
$query = $role === 'customer' ? "SELECT * FROM orders WHERE user_id = ?" : "SELECT * FROM orders";
$stmt = $pdo->prepare($query);
$params = $role === 'customer' ? [$user_id] : [];
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Order status counts
$status_counts = ['pending' => 0, 'in_progress' => 0, 'completed' => 0, 'delivered' => 0];
$total_revenue = 0;
foreach ($orders as $order) {
    $status_counts[$order['status']]++;
    $total_revenue += $order['total_amount'] ?? 0;
}

// Recent orders (last 5)
$recent_query = $role === 'customer' 
    ? "SELECT * FROM orders WHERE user_id = ? ORDER BY drop_off_date DESC LIMIT 5"
    : "SELECT * FROM orders ORDER BY drop_off_date DESC LIMIT 5";
$stmt = $pdo->prepare($recent_query);
$stmt->execute($role === 'customer' ? [$user_id] : []);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inventory (staff/admin)
$inventory = [];
$low_inventory = 0;
if ($role === 'staff' || $role === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM inventory");
    $stmt->execute();
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($inventory as $item) {
        if ($item['quantity'] <= $item['reorder_level']) {
            $low_inventory++;
        }
    }
}

// Users (admin)
$users = [];
$user_roles = ['customer' => 0, 'staff' => 0, 'admin' => 0];
if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        $user_roles[$user['role']]++;
    }
}

// Revenue trend (last 7 days, admin/staff)
$revenue_trend = [];
if ($role === 'staff' || $role === 'admin') {
    $stmt = $pdo->prepare("SELECT DATE(drop_off_date) as date, SUM(total_amount) as revenue 
                           FROM orders 
                           WHERE drop_off_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                           GROUP BY DATE(drop_off_date)");
    $stmt->execute();
    $revenue_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Laundry Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="dashboard-body" data-theme="light">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <header class="dashboard-header">
            <h2>Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)</h2>
            <div class="header-controls">
                <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
                <!-- Improved user info section with profile picture and link to profile page -->
                <div class="user-info">
                    <a href="profile.php" title="Manage Profile" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                        <!-- Profile picture with fallback to default image -->
                        <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" 
                             style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                        <span style="margin-left: 8px;"><?php echo htmlspecialchars($username); ?></span>
                    </a>
                </div>
            </div>
        </header>
        <div class="dashboard-container">
            <div class="dashboard-grid">
                <!-- Order Summary -->
                <div class="card dashboard-card" data-tooltip="Order status overview">
                    <div class="card-header">
                        <h3><i class="fas fa-shopping-cart"></i> Order Summary</h3>
                        <i class="fas fa-chevron-down toggle-card" data-target="order-content"></i>
                    </div>
                    <div class="card-content" id="order-content">
                        <p class="metric"><?php echo count($orders); ?> <?php echo $role === 'customer' ? 'Your Orders' : 'Total Orders'; ?></p>
                        <canvas id="order-status-chart" height="200"></canvas>
                        <div class="status-metrics">
                            <span>Pending: <?php echo $status_counts['pending']; ?></span>
                            <span>In Progress: <?php echo $status_counts['in_progress']; ?></span>
                            <span>Completed: <?php echo $status_counts['completed']; ?></span>
                            <span>Delivered: <?php echo $status_counts['delivered']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Revenue (Staff/Admin) -->
                <?php if ($role === 'staff' || $role === 'admin'): ?>
                    <div class="card dashboard-card" data-tooltip="Revenue trends">
                        <div class="card-header">
                            <h3><i class="fas fa-dollar-sign"></i> Revenue</h3>
                            <i class="fas fa-chevron-down toggle-card" data-target="revenue-content"></i>
                        </div>
                        <div class="card-content" id="revenue-content">
                            <p class="metric">$<?php echo number_format($total_revenue, 2); ?> Total</p>
                            <canvas id="revenue-trend-chart" height="200"></canvas>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Inventory (Staff/Admin) -->
                <?php if ($role === 'staff' || $role === 'admin'): ?>
                    <div class="card dashboard-card" data-tooltip="Inventory status">
                        <div class="card-header">
                            <h3><i class="fas fa-boxes"></i> Inventory</h3>
                            <i class="fas fa-chevron-down toggle-card" data-target="inventory-content"></i>
                        </div>
                        <div class="card-content" id="inventory-content">
                            <p class="metric"><?php echo count($inventory); ?> Items</p>
                            <p class="warning"><?php echo $low_inventory; ?> Low Stock</p>
                            <table class="sortable-table">
                                <thead>
                                    <tr>
                                        <th data-sort="item_name">Item Name <i class="fas fa-sort"></i></th>
                                        <th data-sort="quantity">Quantity <i class="fas fa-sort"></i></th>
                                        <th data-sort="reorder_level">Reorder Level <i class="fas fa-sort"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($inventory, 0, 5) as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td class="<?php echo $item['quantity'] <= $item['reorder_level'] ? 'warning' : ''; ?>">
                                                <?php echo $item['quantity']; ?>
                                            </td>
                                            <td><?php echo $item['reorder_level']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <a href="inventory.php" class="action-button">View All</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Users (Admin) -->
                <?php if ($role === 'admin'): ?>
                    <div class="card dashboard-card" data-tooltip="User management">
                        <div class="card-header">
                            <h3><i class="fas fa-users"></i> Users</h3>
                            <i class="fas fa-chevron-down toggle-card" data-target="users-content"></i>
                        </div>
                        <div class="card-content" id="users-content">
                            <p class="metric"><?php echo count($users); ?> Registered Users</p>
                            <div class="status-metrics">
                                <span>Customers: <?php echo $user_roles['customer']; ?></span>
                                <span>Staff: <?php echo $user_roles['staff']; ?></span>
                                <span>Admins: <?php echo $user_roles['admin']; ?></span>
                            </div>
                            <table class="sortable-table">
                                <thead>
                                    <tr>
                                        <th data-sort="name">Name <i class="fas fa-sort"></i></th>
                                        <th data-sort="email">Email <i class="fas fa-sort"></i></th>
                                        <th data-sort="role">Role <i class="fas fa-sort"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($users, 0, 5) as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recent Orders -->
                <div class="card dashboard-card" data-tooltip="Recent order activity">
                    <div class="card-header">
                        <h3><i class="fas fa-clock"></i> Recent Orders</h3>
                        <i class="fas fa-chevron-down toggle-card" data-target="recent-orders-content"></i>
                    </div>
                    <div class="card-content" id="recent-orders-content">
                        <div class="filter-group">
                            <label for="status-filter">Filter by Status:</label>
                            <select id="status-filter">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>
                        <table class="sortable-table" id="recent-orders-table">
                            <thead>
                                <tr>
                                    <th data-sort="order_id">Order ID <i class="fas fa-sort"></i></th>
                                    <th data-sort="status">Status <i class="fas fa-sort"></i></th>
                                    <th data-sort="drop_off_date">Drop-off Date <i class="fas fa-sort"></i></th>
                                    <th data-sort="total_amount">Amount <i class="fas fa-sort"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr data-status="<?php echo $order['status']; ?>">
                                        <td><?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($order['drop_off_date'])); ?></td>
                                        <td><?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button class="action-button" onclick="exportTableToCSV('recent-orders-table', 'orders.csv')">Export to CSV</button>
                        <a href="order_tracking.php" class="action-button">View All Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Order Status Chart
        const statusCtx = document.getElementById('order-status-chart')?.getContext('2d');
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Pending', 'In Progress', 'Completed', 'Delivered'],
                    datasets: [{
                        data: [
                            <?php echo $status_counts['pending']; ?>,
                            <?php echo $status_counts['in_progress']; ?>,
                            <?php echo $status_counts['completed']; ?>,
                            <?php echo $status_counts['delivered']; ?>
                        ],
                        backgroundColor: ['#ffca28', '#29b6f6', '#4caf50', '#ab47bc']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        // Revenue Trend Chart
        const revenueCtx = document.getElementById('revenue-trend-chart')?.getContext('2d');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: [
                        <?php
                        $dates = [];
                        for ($i = 6; $i >= 0; $i--) {
                            $dates[] = "'" . date('Y-m-d', strtotime("-$i days")) . "'";
                        }
                        echo implode(',', $dates);
                        ?>
                    ],
                    datasets: [{
                        label: 'Revenue ($)',
                        data: [
                            <?php
                            $revenues = array_fill(0, 7, 0);
                            foreach ($revenue_trend as $trend) {
                                $index = 6 - floor((strtotime('today') - strtotime($trend['date'])) / 86400);
                                if ($index >= 0 && $index < 7) {
                                    $revenues[$index] = $trend['revenue'];
                                }
                            }
                            echo implode(',', $revenues);
                            ?>
                        ],
                        borderColor: '#28a745',
                        fill: false,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Revenue ($)' } },
                        x: { title: { display: true, text: 'Date' } }
                    }
                }
            });
        }
    </script>
    <script src="js/scripts.js"></script>
</body>
</html>