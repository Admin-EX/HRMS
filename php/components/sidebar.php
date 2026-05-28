<?php
// sidebar.php - Reusable sidebar component
// Get admin info from session
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'Super Admin';
$session_role = $_SESSION['role'] ?? '';

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
        <?php if ($session_role === 'super_admin'): ?>
            <a href="account_manager.php" class="<?php echo ($current_page == 'account_manager.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-cog"></i> Account Manager
            </a>
        <?php endif; ?>
        <a href="logout.php" id="logoutLink" class="<?php echo ($current_page == 'logout.php') ? 'active' : ''; ?>">
            <i class="fas fa-sign-out-alt"></i> Logout
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

<div id="logoutConfirmModal" class="logout-confirm-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; z-index:120000;">
    <div class="logout-confirm-modal" style="background:#fff; border-radius:12px; max-width:400px; width:90%; padding:22px; box-shadow:0 16px 40px rgba(0,0,0,0.18);">
        <h3 style="margin:0 0 12px; font-size:1.2rem;">Confirm logout</h3>
        <p style="margin:0 0 20px; color:#444;">Are you sure you want to log out?</p>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button id="cancelLogoutBtn" type="button" style="padding:10px 14px; border:none; border-radius:8px; background:#e0e7ff; color:#1f3c88; cursor:pointer; font-weight:600;">Cancel</button>
            <button id="confirmLogoutBtn" type="button" style="padding:10px 14px; border:none; border-radius:8px; background:#e74c3c; color:#fff; cursor:pointer; font-weight:600;">Log Out</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logoutLink = document.getElementById('logoutLink');
        const modal = document.getElementById('logoutConfirmModal');
        const cancelBtn = document.getElementById('cancelLogoutBtn');
        const confirmBtn = document.getElementById('confirmLogoutBtn');

        if (!logoutLink || !modal || !cancelBtn || !confirmBtn) return;

        logoutLink.addEventListener('click', function(event) {
            event.preventDefault();
            modal.style.display = 'flex';
        });

        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        confirmBtn.addEventListener('click', function() {
            window.location.href = logoutLink.href;
        });

        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        });
    });
</script>