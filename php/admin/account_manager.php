<?php
session_start();
include __DIR__ . '/../../database/connection.php';

if (empty($_SESSION['loggedUser']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Account Manager';
$page_description = 'Manage HRMS user accounts and roles.';

// read flash message (POST-Redirect-Get)
$message = '';
$messageType = 'success';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

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
                    $_SESSION['flash_message'] = 'Role updated successfully.';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $_SESSION['flash_message'] = 'Unable to update the role.';
                    $_SESSION['flash_type'] = 'error';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $_SESSION['flash_message'] = 'You cannot change your own role here.';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    } elseif ($action === 'set_inactive' && $target !== $_SESSION['loggedUser']) {
        $stmt = mysqli_prepare($connection, "UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE employee_number = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $target);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_message'] = 'Account status updated successfully.';
                $_SESSION['flash_type'] = 'success';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $_SESSION['flash_message'] = 'Unable to update the account status.';
                $_SESSION['flash_type'] = 'error';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        if ($action !== '') {
            $_SESSION['flash_message'] = 'Invalid account action.';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
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
        .account-table { width: 100%; border-collapse: collapse; margin-top: 20px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.04); }
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

        /* Button styles specific to account manager */
        .view-btn { padding: 8px 12px; border-radius: 8px; background: #3498db; color: #fff; border: none; cursor: pointer; font-weight: 600; }
        .view-btn:hover { background: #2980b9; }

        .deny-btn { padding: 8px 12px; border-radius: 8px; background: #e74c3c; color: #fff; border: none; cursor: pointer; font-weight: 600; }
        .deny-btn:hover { background: #c0392b; }

        /* Ensure action buttons don't stretch */
        .action-buttons button { display: inline-block; }

        /* Center specific columns (Role, Status, Actions) */
        .account-table th:nth-child(3),
        .account-table th:nth-child(4),
        .account-table th:nth-child(5) {
            text-align: center;
        }

        .account-table td:nth-child(3),
        .account-table td:nth-child(4),
        .account-table td:nth-child(5) {
            text-align: center;
            vertical-align: middle;
        }

        /* Center action buttons inside their cell */
        .action-buttons { justify-content: center; }
        /* Modal styles */
        .confirm-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: none; align-items: center; justify-content: center; z-index: 120000; }
        .confirm-modal { background: #fff; border-radius: 10px; padding: 20px 22px; max-width: 420px; width: 100%; box-shadow: 0 12px 40px rgba(0,0,0,0.18); }
        .confirm-modal h3 { margin: 0 0 8px 0; font-size: 18px; }
        .confirm-modal p { margin: 0 0 16px 0; color: #444; }
        .confirm-modal .controls { display:flex; gap:10px; justify-content:flex-end; }
        .confirm-modal .btn { padding:8px 12px; border-radius:8px; cursor:pointer; border: none; font-weight:600; }
        .confirm-modal .btn.cancel { background:#e6eef7; color:#1f3c88; }
        .confirm-modal .btn.confirm { background:#e74c3c; color:#fff; }
    </style>
</head>
<body>
    <div class="app">
        <?php include(__DIR__ . '/../components/sidebar.php'); ?>
        <main class="main">
            <?php include(__DIR__ . '/../components/topbar.php'); ?>

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
                    <div class="search-filters-container">
                        <div class="search-filters-wrapper">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input id="accountSearchInput" type="text" placeholder="Search by employee number, email, or role...">
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="accountTable" class="account-table">
                            <thead>
                                <tr>
                                    <th>Employee Number</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="accountTableBody">
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination" id="accountPagination" style="display:flex; justify-content:space-between; align-items:center; padding:12px 24px; background:white; margin: 0 24px 30px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                        <div class="pagination-info" id="accountPaginationInfo">Showing 0 to 0 of 0 results</div>
                        <div class="pagination-controls">
                            <button class="pagination-btn" id="accountPrevBtn" disabled>Previous</button>
                            <button class="pagination-btn" id="accountNextBtn" disabled>Next</button>
                        </div>
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

        // ---------------------------
        // Client-side search & pagination
        // ---------------------------
        const accountsData = <?php echo json_encode($accounts); ?>;
        const perPage = 10;
        let currentPage = 1;
        let filtered = accountsData.slice();

        function renderTable() {
            const tbody = document.getElementById('accountTableBody');
            tbody.innerHTML = '';
            const start = (currentPage - 1) * perPage;
            const pageItems = filtered.slice(start, start + perPage);
            for (const acc of pageItems) {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${escapeHtml(acc.employee_number)}</td>
                    <td>${escapeHtml(acc.email)}</td>
                    <td><span class="role-badge role-${escapeHtml(acc.role)}">${escapeHtml(capitalize(acc.role.replace('_',' ')))}</span></td>
                    <td><span class="status-badge status-${escapeHtml(acc.status)}">${escapeHtml(capitalize(acc.status))}</span></td>
                    <td>${renderActions(acc)}</td>
                `;
                tbody.appendChild(tr);
            }
            updatePaginationInfo();
            wireForms();
        }

        function renderActions(acc) {
            const me = <?php echo json_encode($_SESSION['loggedUser'] ?? ''); ?>;
            if (acc.employee_number === me) return `<span style="color:#555; font-size:0.95rem;">Current user</span>`;
            // role form
            const selectedEmployee = escapeHtml(acc.employee_number);
            const roleOptions = ['employee','admin','super_admin'].map(r => `<option value="${r}" ${acc.role===r? 'selected':''}>${capitalize(r.replace('_',' '))}</option>`).join('');
            const actionHtml = `
                <div class="action-buttons">
                    <form method="POST">
                        <input type="hidden" name="employee_number" value="${selectedEmployee}">
                        <input type="hidden" name="action" value="update_role">
                        <select name="new_role">${roleOptions}</select>
                        <button type="submit" class="view-btn">Update</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Change this account to inactive?');">
                        <input type="hidden" name="employee_number" value="${selectedEmployee}">
                        <input type="hidden" name="action" value="set_inactive">
                        <button type="submit" class="deny-btn">${acc.status === 'active' ? 'Set Inactive' : 'Activate'}</button>
                    </form>
                </div>
            `;
            return actionHtml;
        }

        function updatePaginationInfo() {
            const total = filtered.length;
            const start = total === 0 ? 0 : (currentPage - 1) * perPage + 1;
            const end = Math.min(currentPage * perPage, total);
            document.getElementById('accountPaginationInfo').textContent = `Showing ${start} to ${end} of ${total} results`;
            const prev = document.getElementById('accountPrevBtn');
            const next = document.getElementById('accountNextBtn');
            prev.disabled = currentPage <= 1;
            next.disabled = currentPage >= Math.ceil(total / perPage) || total === 0;
        }

        function applySearch(term) {
            term = term.trim().toLowerCase();
            if (term === '') filtered = accountsData.slice();
            else filtered = accountsData.filter(a => (a.employee_number||'').toLowerCase().includes(term) || (a.email||'').toLowerCase().includes(term) || (a.role||'').toLowerCase().includes(term));
            currentPage = 1;
            renderTable();
        }

        document.getElementById('accountPrevBtn').addEventListener('click', ()=>{ if (currentPage>1){ currentPage--; renderTable(); } });
        document.getElementById('accountNextBtn').addEventListener('click', ()=>{ const pages = Math.ceil(filtered.length / perPage); if (currentPage<pages){ currentPage++; renderTable(); } });

        document.getElementById('accountSearchInput').addEventListener('input', (e)=>{ applySearch(e.target.value); });

        // helpers
        function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]; }); }
        function capitalize(s){ return String(s).replace(/(^|\s)\S/g, t=>t.toUpperCase()); }

        // After rendering, wire up forms to submit normally (no-op here) - optional enhancements could use AJAX
        function wireForms(){ /* no-op: forms will submit to server */ }

        // initial render
        renderTable();

        // Confirmation modal handling
        // create modal element
        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'confirm-modal-overlay';
        modalOverlay.innerHTML = `
            <div class="confirm-modal" role="dialog" aria-modal="true">
                <h3 id="confirmModalTitle">Confirm action</h3>
                <p id="confirmModalMessage">Are you sure?</p>
                <div class="controls">
                    <button type="button" class="btn cancel" id="confirmCancel">Cancel</button>
                    <button type="button" class="btn confirm" id="confirmOk">Confirm</button>
                </div>
            </div>
        `;
        document.body.appendChild(modalOverlay);

        let pendingForm = null;

        // delegate clicks on deny buttons
        document.addEventListener('click', function(e){
            const tgt = e.target;
            if (tgt.classList && tgt.classList.contains('deny-btn')) {
                // find the form
                const form = tgt.closest('form');
                if (!form) return;
                e.preventDefault();
                pendingForm = form;
                // get employee id and action to show context
                const empInput = form.querySelector('input[name="employee_number"]');
                const emp = empInput ? empInput.value : '';
                const btnText = tgt.textContent.trim();
                const msg = btnText === 'Set Inactive' ? `Change account ${emp} to inactive?` : `Activate account ${emp}?`;
                document.getElementById('confirmModalMessage').textContent = msg;
                modalOverlay.style.display = 'flex';
                // focus cancel for accessibility
                document.getElementById('confirmCancel').focus();
            }
        });

        // modal controls
        document.getElementById('confirmCancel').addEventListener('click', ()=>{ pendingForm = null; modalOverlay.style.display = 'none'; });
        document.getElementById('confirmOk').addEventListener('click', ()=>{
            if (pendingForm) {
                // submit stored form
                pendingForm.submit();
            }
        });

        // close on overlay click
        modalOverlay.addEventListener('click', function(e){ if (e.target === modalOverlay) { pendingForm = null; modalOverlay.style.display = 'none'; } });

        // close on Esc
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && modalOverlay.style.display === 'flex') { pendingForm = null; modalOverlay.style.display = 'none'; } });
    </script>
</body>
</html>
