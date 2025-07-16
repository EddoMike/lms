<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? 'customer';
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>Laundry System</h3>
        <button class="sidebar-toggle" id="sidebar-toggle"><i class="fas fa-bars"></i></button>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>
            
            <?php if ($role === 'customer'): ?>
                <li><a href="order_form.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'order_form.php' ? 'active' : ''; ?>"><i class="fas fa-plus"></i> Place Order</a></li>
                <li><a href="order_tracking.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'order_tracking.php' ? 'active' : ''; ?>"><i class="fas fa-truck"></i> Track Orders</a></li>
            <?php elseif ($role === 'staff'): ?>
                <li><a href="order_form.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'order_form.php' ? 'active' : ''; ?>"><i class="fas fa-plus"></i> Create Order</a></li>
                <li><a href="order_tracking.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'order_tracking.php' ? 'active' : ''; ?>"><i class="fas fa-truck"></i> Track Orders</a></li>
                <li><a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'inventory.php' ? 'active' : ''; ?>"><i class="fas fa-boxes"></i> Inventory</a></li>
            <?php elseif ($role === 'admin'): ?>
                <li><a href="order_tracking.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'order_tracking.php' ? 'active' : ''; ?>"><i class="fas fa-truck"></i> Track Orders</a></li>
                <li><a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'inventory.php' ? 'active' : ''; ?>"><i class="fas fa-boxes"></i> Inventory</a></li>
                <li><a href="manage_clothes.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'manage_clothes.php' ? 'active' : ''; ?>"><i class="fas fa-tshirt"></i> Manage Clothes</a></li>
            <?php endif; ?>
            
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
</aside>

<script>
    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    });
</script>