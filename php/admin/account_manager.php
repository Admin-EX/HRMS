<?php
session_start();
include __DIR__ . '/../../database/connection.php';

if (empty($_SESSION['loggedUser']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Account Manager';
$page_description = 'Manage HRMS user accounts and roles.';

// Check if a super_admin already exists (to enforce single-superadmin rule)
$saRes = mysqli_query($connection, "SELECT COUNT(*) AS cnt FROM users WHERE role = 'super_admin'");
$saRow = $saRes ? mysqli_fetch_assoc($saRes) : null;
$superAdminCount = isset($saRow['cnt']) ? (int)$saRow['cnt'] : 0;

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
            // Server-side enforce single super_admin: if attempting to set role to super_admin,
            // disallow if another super_admin exists (excluding the target itself).
            if ($newRole === 'super_admin') {
                $countStmt = mysqli_prepare($connection, "SELECT COUNT(*) AS cnt FROM users WHERE role = 'super_admin' AND employee_number <> ?");
                if ($countStmt) {
                    mysqli_stmt_bind_param($countStmt, 's', $target);
                    mysqli_stmt_execute($countStmt);
                    mysqli_stmt_bind_result($countStmt, $existingSa);
                    mysqli_stmt_fetch($countStmt);
                    $existingSa = isset($existingSa) ? (int)$existingSa : 0;
                    mysqli_stmt_close($countStmt);
                    if ($existingSa > 0) {
                        $_SESSION['flash_message'] = 'A super admin already exists. Only one super admin is allowed.';
                        $_SESSION['flash_type'] = 'error';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
                }
            }

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
        // Approve or reject pending registrations
        if ($action === 'approve_registration') {
            $regId = isset($_POST['registration_id']) ? (int)$_POST['registration_id'] : 0;
            if ($regId <= 0) {
                $_SESSION['flash_message'] = 'Invalid registration selected.';
                $_SESSION['flash_type'] = 'error';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            // fetch pending registration
            $pstmt = mysqli_prepare($connection, 'SELECT email, password FROM pending_registrations WHERE id = ? LIMIT 1');
            if ($pstmt) {
                mysqli_stmt_bind_param($pstmt, 'i', $regId);
                mysqli_stmt_execute($pstmt);
                $pres = mysqli_stmt_get_result($pstmt);
                $prow = $pres ? mysqli_fetch_assoc($pres) : null;
                mysqli_stmt_close($pstmt);

                if (!$prow) {
                    $_SESSION['flash_message'] = 'Pending registration not found.';
                    $_SESSION['flash_type'] = 'error';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                $email = $prow['email'];
                $passwordHash = $prow['password'];

                // generate a unique employee number (EMP + 6 digits)
                $attempts = 0;
                $empNumber = '';
                while ($attempts < 6) {
                    $candidate = 'EMP' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $check = mysqli_prepare($connection, 'SELECT id FROM users WHERE employee_number = ? LIMIT 1');
                    if ($check) {
                        mysqli_stmt_bind_param($check, 's', $candidate);
                        mysqli_stmt_execute($check);
                        $cres = mysqli_stmt_get_result($check);
                        $exists = $cres && mysqli_fetch_assoc($cres);
                        mysqli_stmt_close($check);
                        if (!$exists) { $empNumber = $candidate; break; }
                    }
                    $attempts++;
                }

                if ($empNumber === '') {
                    $_SESSION['flash_message'] = 'Unable to generate employee number. Try again later.';
                    $_SESSION['flash_type'] = 'error';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                // insert into users and employees together
                mysqli_begin_transaction($connection);
                $ins = mysqli_prepare($connection, "INSERT INTO users (employee_number, email, password, role, status) VALUES (?, ?, ?, 'employee', 'active')");
                if ($ins) {
                    mysqli_stmt_bind_param($ins, 'sss', $empNumber, $email, $passwordHash);
                    if (mysqli_stmt_execute($ins)) {
                        mysqli_stmt_close($ins);

                        $empFullName = explode('@', $email)[0];
                        $empEmployeeType = 'TP';
                        $empDepartment = 'TBD';
                        $empPosition = 'Employee';
                        $empCredentials = '';
                        $empGender = null;
                        $empAddress = null;
                        $empPhone = null;
                        $empStatus = 'Active';
                        $empEducationalAttainment = 'N/A';
                        $empSchool = 'N/A';
                        $empDateHired = date('Y-m-d');
                        $empType = 'employee';
                        $empYearsService = 0;
                        $empEmploymentStatus = 'Active';
                        $empLeaveBalance = '0';

                        $employeeSql = "INSERT INTO employees (
                            employee_number,
                            full_name,
                            employee_type,
                            department,
                            position,
                            credentials,
                            gender,
                            address,
                            phone,
                            email,
                            status,
                            educational_attainment,
                            school,
                            created_at,
                            date_hired,
                            type,
                            years_service,
                            employment_status,
                            leave_balance
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";

                        $empStmt = mysqli_prepare($connection, $employeeSql);
                        if ($empStmt) {
                            mysqli_stmt_bind_param(
                                $empStmt,
                                'sssssssssssssssiss',
                                $empNumber,
                                $empFullName,
                                $empEmployeeType,
                                $empDepartment,
                                $empPosition,
                                $empCredentials,
                                $empGender,
                                $empAddress,
                                $empPhone,
                                $email,
                                $empStatus,
                                $empEducationalAttainment,
                                $empSchool,
                                $empDateHired,
                                $empType,
                                $empYearsService,
                                $empEmploymentStatus,
                                $empLeaveBalance
                            );

                            if (!mysqli_stmt_execute($empStmt)) {
                                mysqli_stmt_close($empStmt);
                                mysqli_rollback($connection);
                                $_SESSION['flash_message'] = 'Approved user created, but employee record failed: ' . mysqli_error($connection);
                                $_SESSION['flash_type'] = 'error';
                                header('Location: ' . $_SERVER['PHP_SELF']);
                                exit;
                            }
                            mysqli_stmt_close($empStmt);
                        } else {
                            mysqli_rollback($connection);
                            $_SESSION['flash_message'] = 'Unable to prepare employee record.';
                            $_SESSION['flash_type'] = 'error';
                            header('Location: ' . $_SERVER['PHP_SELF']);
                            exit;
                        }

                        // remove pending registration
                        $del = mysqli_prepare($connection, 'DELETE FROM pending_registrations WHERE id = ?');
                        if ($del) {
                            mysqli_stmt_bind_param($del, 'i', $regId);
                            mysqli_stmt_execute($del);
                            mysqli_stmt_close($del);
                        }

                        mysqli_commit($connection);

                        // Send notification email with employee id
                        $emailSent = false;
                        $autoload = __DIR__ . '/../../vendor/autoload.php';
                        if (file_exists($autoload)) {
                            require_once $autoload;
                            try {
                                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                                $mail->isSMTP();
                                $mail->Host = 'smtp.gmail.com';
                                $mail->SMTPAuth = true;
                                $mail->Username = 'mostdevil24@gmail.com';
                                $mail->Password = 'bkvx rpin tlfi svpl';
                                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                                $mail->Port = 587;
                                $mail->setFrom('mostdevil24@gmail.com', 'BTech HRMS');
                                $mail->addAddress($email);
                                $mail->Subject = 'BTech HRMS Account Approved';
                                $mail->Body = "Congratulations, you have been accepted. Your Employee Id: $empNumber";
                                $mail->send();
                                $emailSent = true;
                            } catch (Exception $e) {
                                $emailSent = false;
                            }
                        }

                        if ($emailSent) {
                            $_SESSION['flash_message'] = 'Registration approved. Employee number: ' . $empNumber . '. An email has been sent.';
                        } else {
                            $_SESSION['flash_message'] = 'Registration approved. Employee number: ' . $empNumber . '. Unable to send email.';
                        }
                        $_SESSION['flash_type'] = 'success';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        mysqli_stmt_close($ins);
                        mysqli_rollback($connection);
                        $_SESSION['flash_message'] = 'Unable to create user account.';
                        $_SESSION['flash_type'] = 'error';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
                } else {
                    mysqli_rollback($connection);
                    $_SESSION['flash_message'] = 'Database error. Please try again later.';
                    $_SESSION['flash_type'] = 'error';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
            } else {
                $_SESSION['flash_message'] = 'Database error. Please try again later.';
                $_SESSION['flash_type'] = 'error';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        } elseif ($action === 'reject_registration') {
            $regId = isset($_POST['registration_id']) ? (int)$_POST['registration_id'] : 0;
            if ($regId <= 0) {
                $_SESSION['flash_message'] = 'Invalid registration selected.';
                $_SESSION['flash_type'] = 'error';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            $del = mysqli_prepare($connection, 'DELETE FROM pending_registrations WHERE id = ?');
            if ($del) {
                mysqli_stmt_bind_param($del, 'i', $regId);
                if (mysqli_stmt_execute($del)) {
                    $_SESSION['flash_message'] = 'Registration rejected and removed.';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
                mysqli_stmt_close($del);
            }
            $_SESSION['flash_message'] = 'Unable to reject registration.';
            $_SESSION['flash_type'] = 'error';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            if ($action !== '') {
                $_SESSION['flash_message'] = 'Invalid account action.';
                $_SESSION['flash_type'] = 'error';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

$result = mysqli_query($connection, "SELECT employee_number, email, role, status FROM users ORDER BY FIELD(role, 'super_admin', 'admin', 'employee'), employee_number");
$accounts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $accounts[] = $row;
}

// Fetch pending registrations for admin approval
$pendingRegs = [];
$createPending = "CREATE TABLE IF NOT EXISTS pending_registrations (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
mysqli_query($connection, $createPending);

$pr = mysqli_query($connection, "SELECT id, email, created_at FROM pending_registrations ORDER BY created_at ASC");
if ($pr) {
    while ($r = mysqli_fetch_assoc($pr)) { $pendingRegs[] = $r; }
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

        .view-btn.loading,
        .deny-btn.loading {
            opacity: 0.75;
            cursor: wait;
        }

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
                            <div>
                                <button id="openPendingBtn" class="view-btn">Pending Registrations (<?php echo count($pendingRegs); ?>)</button>
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
    <!-- Pending Registrations Modal -->
    <div id="pendingModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:120000; justify-content:center; align-items:center;">
        <div style="background:#fff; width:760px; max-width:95%; border-radius:10px; padding:18px; box-shadow:0 18px 40px rgba(0,0,0,0.3);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <strong>Pending Registrations</strong>
                <button id="closePending" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
            </div>
            <div style="max-height:400px; overflow:auto;">
                <?php if (count($pendingRegs) === 0): ?>
                    <div style="padding:20px; text-align:center; color:#555;">No pending registrations.</div>
                <?php else: ?>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead><tr><th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Email</th><th style="padding:8px;border-bottom:1px solid #eee;">Requested</th><th style="padding:8px;border-bottom:1px solid #eee;">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($pendingRegs as $prg): ?>
                            <tr>
                                <td style="padding:10px;border-bottom:1px solid #f3f3f3;"><?php echo htmlspecialchars($prg['email']); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f3f3f3;"><?php echo htmlspecialchars($prg['created_at']); ?></td>
                                <td style="padding:10px;border-bottom:1px solid #f3f3f3;">
                                    <form method="POST" style="display:inline-block; margin-right:8px;">
                                        <input type="hidden" name="action" value="approve_registration">
                                        <input type="hidden" name="registration_id" value="<?php echo (int)$prg['id']; ?>">
                                        <button type="submit" class="view-btn">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Reject this registration?');">
                                        <input type="hidden" name="action" value="reject_registration">
                                        <input type="hidden" name="registration_id" value="<?php echo (int)$prg['id']; ?>">
                                        <button type="submit" class="deny-btn">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        const accountToastMessage = <?php echo json_encode($message); ?>;
        const accountToastType = <?php echo json_encode($messageType); ?>;
        // Allow selecting super_admin only if none exists yet
        const allowSuperAdmin = <?php echo json_encode($superAdminCount === 0); ?>;

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
            const rolesList = allowSuperAdmin ? ['employee','admin','super_admin'] : ['employee','admin'];
            const roleOptions = rolesList.map(r => `<option value="${r}" ${acc.role===r? 'selected':''}>${capitalize(r.replace('_',' '))}</option>`).join('');
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
        // Pending registrations modal handlers
        const openPendingBtn = document.getElementById('openPendingBtn');
        const pendingModal = document.getElementById('pendingModal');
        const closePending = document.getElementById('closePending');
        if (openPendingBtn && pendingModal) openPendingBtn.addEventListener('click', ()=>{ pendingModal.style.display='flex'; });
        if (closePending && pendingModal) closePending.addEventListener('click', ()=>{ pendingModal.style.display='none'; });
        if (pendingModal) pendingModal.addEventListener('click', (e)=>{ if (e.target === pendingModal) pendingModal.style.display='none'; });

        // Show loading state when approve/reject is clicked
        const pendingFormButtons = document.querySelectorAll('#pendingModal form button[type="submit"]');
        pendingFormButtons.forEach((btn) => {
            const form = btn.closest('form');
            if (!form) return;
            form.addEventListener('submit', () => {
                btn.dataset.originalText = btn.textContent;
                btn.textContent = 'Processing...';
                btn.disabled = true;
                btn.classList.add('loading');
            });
        });
    </script>
</body>
</html>
