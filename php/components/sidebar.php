<?php
// sidebar.php - Reusable sidebar component
// Get admin info from session
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'Super Admin';

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="brand">
        <div class="brand-box">BT</div>
        <span>BTech HRMS</span>
    </div>

    <nav class="menu">
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="employee.php" class="<?php echo ($current_page == 'employee.php') ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Employees
        </a>
        <a href="records.php" class="<?php echo ($current_page == 'records.php') ? 'active' : ''; ?>">
            <i class="fas fa-folder-open"></i> Records
        </a>
        <a href="leave.php" class="<?php echo ($current_page == 'leave.php') ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i> Requests
        </a>
        <a href="analytics.php" class="<?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> Analytics
        </a>
                <a href="logout.php" class="<?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> Logout
        </a>
    </nav>

    <div class="admin">
        <div class="avatar">AD</div>
        <div>
            <strong><?php echo htmlspecialchars($admin_name); ?></strong>
            <small><?php echo htmlspecialchars($admin_role); ?></small>
        </div>
    </div>
</aside>