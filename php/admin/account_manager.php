<?php
session_start();
include __DIR__ . '/../../database/connection.php';

if (empty($_SESSION['loggedUser']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Account Manager';
$page_description = 'Manage HRMS user accounts and roles.';
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target = $_POST['employee_number'] ?? '';
    if ($action === 'update_role' && in_array($_POST['new_role'] ?? '', ['employee', 'admin', 'super_admin'], true)) {
        $newRole = $_POST['new_role'];
        if ($target !== $_SESSION['loggedUser']) {
            $stmt = mysqli_prepare($connection, "UPDATE users SET role = ? WHERE employee_number = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $newRole, $target);
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Role updated successfully.';
                } else {
                    $messageType = 'error';
                    $message = 'Unable to update the role.';
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $messageType = 'error';
            $message = 'You cannot change your own role here.';
        }
    } elseif ($action === 'set_inactive' && $target !== $_SESSION['loggedUser']) {
        $stmt = mysqli_prepare($connection, "UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE employee_number = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $target);
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Account status updated successfully.';
            } else {
                $messageType = 'error';
                $message = 'Unable to update the account status.';
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        if ($action !== '') {
            $messageType = 'error';
            $message = 'Invalid account action.';
        }
    }
}

$result = mysqli_query($connection, "SELECT employee_number, email, role, status FROM users ORDER BY FIELD(role, 'super_admin', 'admin', 'employee'), employee_number");
$accounts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $accounts[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Manager</title>
    <link rel="stylesheet" href="../../css/admin/leave.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .manager-panel { padding: 24px; }
        .account-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .account-table th, .account-table td { padding: 14px 12px; border-bottom: 1px solid #e6e9f0; text-align: left; }
        .account-table th { background: #1f3c88; color: #fff; font-weight: 700; }
        .role-badge { padding: 6px 10px; border-radius: 999px; font-size: 13px; display: inline-block; }
        .role-employee { background:#e8f1ff;color:#1d4ed8; }
        .role-admin { background:#eaf8f0;color:#117a42; }
        .role-super_admin { background:#fff4e5;color:#b45309; }
        .status-badge { padding: 6px 10px; border-radius: 999px; font-size: 13px; display: inline-block; }
        .status-active { background:#e8f8f0;color:#116a3a; }
        .status-inactive { background:#fff1f0;color:#a12d2d; }
        .action-buttons { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .action-buttons form { margin: 0; }
        .action-buttons select { padding: 8px 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; }
        .account-header { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
        .account-header h2 { margin:0; }
        .message { margin-top: 14px; padding: 14px 18px; border-radius: 10px; }
        .message.success { background: #eaf8f0; color: #1f5f3b; border: 1px solid #c7e7d1; }
        .message.error { background: #fdecea; color: #872426; border: 1px solid #f2c1c1; }
        .account-card { margin: 0 24px 30px; border-radius: 10px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden; }
        .account-card header { padding: 24px; border-bottom: 1px solid #eef2f7; }
    </style>
</head>
<body>
    <div class="app">
        <?php include(__DIR__ . '/../components/sidebar.php'); ?>
        <main class="main">
            <?php include(__DIR__ . '/../components/topbar.php'); ?>
            <div class="content-header">
                <h1>Account Manager</h1>
                <p>Manage user roles for the HRMS system.</p>
            </div>
            <div class="manager-panel">
                <div class="account-card">
                    <header>
                        <div class="account-header">
                            <div>
                                <h2>All Accounts</h2>
                                <p>Only the super admin can view and manage these users.</p>
                            </div>
                        </div>
                    </header>
                    <div class="table-container">
                        <table class="account-table">
                            <thead>
                                <tr>
                                    <th>Employee Number</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                        <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($account['employee_number']); ?></td>
                                <td><?php echo htmlspecialchars($account['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo htmlspecialchars($account['role']); ?>">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $account['role']))); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($account['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($account['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($account['employee_number'] !== $_SESSION['loggedUser']): ?>
                                        <div class="action-buttons">
                                            <form method="POST">
                                                <input type="hidden" name="employee_number" value="<?php echo htmlspecialchars($account['employee_number']); ?>">
                                                <input type="hidden" name="action" value="update_role">
                                                <select name="new_role">
                                                    <option value="employee"<?php echo $account['role'] === 'employee' ? ' selected' : ''; ?>>Employee</option>
                                                    <option value="admin"<?php echo $account['role'] === 'admin' ? ' selected' : ''; ?>>Admin</option>
                                                    <option value="super_admin"<?php echo $account['role'] === 'super_admin' ? ' selected' : ''; ?>>Super Admin</option>
                                                </select>
                                                <button type="submit" class="view-btn">Update</button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('Change this account to inactive?');">
                                                <input type="hidden" name="employee_number" value="<?php echo htmlspecialchars($account['employee_number']); ?>">
                                                <input type="hidden" name="action" value="set_inactive">
                                                <button type="submit" class="deny-btn"><?php echo $account['status'] === 'active' ? 'Set Inactive' : 'Activate'; ?></button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#555; font-size:0.95rem;">Current user</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($accounts)): ?>
                            <tr><td colspan="5">No accounts found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        const accountToastMessage = <?php echo json_encode($message); ?>;
        const accountToastType = <?php echo json_encode($messageType); ?>;

        function showTopLeftToast(message, duration = 3000) {
            if (!message) return;
            let container = document.getElementById('topRightToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'topRightToastContainer';
                Object.assign(container.style, {
                    position: 'fixed',
                    right: '20px',
                    top: '20px',
                    zIndex: 99999,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: '10px',
                    alignItems: 'flex-end'
                });
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.textContent = message;
            Object.assign(toast.style, {
                background: accountToastType === 'error' ? '#c82333' : '#218838',
                color: '#fff',
                padding: '10px 14px',
                borderRadius: '8px',
                boxShadow: '0 8px 24px rgba(0,0,0,0.15)',
                opacity: '0',
                transform: 'translateY(-6px)',
                transition: 'opacity 220ms ease, transform 220ms ease',
                maxWidth: '360px',
                fontSize: '14px'
            });

            container.appendChild(toast);
            requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-6px)';
                setTimeout(() => { if (toast.parentElement) toast.parentElement.removeChild(toast); }, 300);
            }, duration);
        }

        if (accountToastMessage) {
            showTopLeftToast(accountToastMessage, 4000);
        }
    </script>
</body>
</html>
