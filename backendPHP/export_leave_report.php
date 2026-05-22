<?php
include "../database/connection.php";
session_start();
error_reporting(0);

$status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$leave_limits = [
    'Sick Leave' => 10,
    'Vacation Leave' => 15,
    'Emergency Leave' => 5,
    'Maternity Leave' => 105,
    'Paternity Leave' => 7
];

$where = "WHERE 1=1";
if ($status !== 'all' && $status !== '') {
    $escaped_status = $connection->real_escape_string($status);
    $where .= " AND lr.status = '$escaped_status'";
}
if ($search !== '') {
    $escaped_search = $connection->real_escape_string($search);
    $where .= " AND (e.employee_number LIKE '%$escaped_search%' OR e.full_name LIKE '%$escaped_search%' OR e.department LIKE '%$escaped_search%' OR lr.type LIKE '%$escaped_search%' OR lr.reason LIKE '%$escaped_search%')";
}

$sql = "SELECT lr.*, e.full_name, e.department, e.position, e.email, e.phone
        FROM leave_requests lr
        LEFT JOIN employees e ON lr.employee_number = e.employee_number
        $where
        ORDER BY lr.date_created DESC";

$result = mysqli_query($connection, $sql);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Leave_Report_' . date('Y-m-d_His') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .title { font-size: 16pt; font-weight: bold; text-align: center; }
        .subtitle { font-size: 10pt; text-align: center; color: #666666; }
        .header { background-color: #4472C4; color: white; font-weight: bold; text-align: center; }
        .stats { background-color: #F2F2F2; font-weight: bold; }
        .data { border: 1px solid #D0D0D0; }
        .status-pending { background-color: #FFF3CD; color: #856404; font-weight: bold; }
        .status-approved { background-color: #D4EDDA; color: #155724; font-weight: bold; }
        .status-rejected { background-color: #F8D7DA; color: #721C24; font-weight: bold; }
        .status-auto_rejected { background-color: #E2E3E5; color: #41464B; font-weight: bold; }
        .cell-label { background-color: #F8F8F8; font-weight: bold; }
    </style>
</head>
<body>
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td colspan="12" class="title">BTech HRMS - Leave Requests Report</td>
        </tr>
        <tr>
            <td colspan="12" class="subtitle">Generated on: <?php echo date('F d, Y h:i A'); ?></td>
        </tr>
        <tr><td colspan="12">&nbsp;</td></tr>
        <tr class="header">
            <td>Request ID</td>
            <td>Employee Number</td>
            <td>Employee Name</td>
            <td>Department</td>
            <td>Leave Type</td>
            <td>Start Date</td>
            <td>End Date</td>
            <td>Days</td>
            <td>Leave Balance</td>
            <td>Wet Signature</td>
            <td>Status</td>
            <td>Submitted Date</td>
        </tr>
        <?php
        $usageCache = [];
        function getLeaveBalance($connection, $leave_limits, &$usageCache, $employee_number, $type) {
            if (!isset($leave_limits[$type])) {
                return '';
            }
            $key = $employee_number . '||' . $type;
            if (isset($usageCache[$key])) {
                return $usageCache[$key];
            }
            $limit = $leave_limits[$type];
            $sql = "SELECT COALESCE(SUM(days), 0) AS used_days FROM leave_requests WHERE employee_number = '" . $connection->real_escape_string($employee_number) . "' AND type = '" . $connection->real_escape_string($type) . "' AND status = 'approved' AND YEAR(date_created) = YEAR(CURDATE())";
            $res = mysqli_query($connection, $sql);
            $row = $res ? mysqli_fetch_assoc($res) : null;
            $used = $row ? (int)$row['used_days'] : 0;
            $balance = max(0, $limit - $used) . '/' . $limit;
            $usageCache[$key] = $balance;
            return $balance;
        }

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $balance = getLeaveBalance($connection, $leave_limits, $usageCache, $row['employee_number'], $row['type']);
                $signature = !empty($row['signature']) ? htmlspecialchars($row['signature']) : 'Verified';
                $statusClass = 'status-' . strtolower(str_replace(' ', '_', $row['status'] ?? 'pending'));
                echo '<tr class="data">';
                echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['employee_number']) . '</td>';
                echo '<td>' . htmlspecialchars($row['full_name'] ?? 'Unknown') . '</td>';
                echo '<td>' . htmlspecialchars($row['department'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['type'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['start_date']) . '</td>';
                echo '<td>' . htmlspecialchars($row['end_date']) . '</td>';
                echo '<td>' . htmlspecialchars($row['days']) . '</td>';
                echo '<td>' . htmlspecialchars($balance) . '</td>';
                echo '<td>' . htmlspecialchars($signature) . '</td>';
                echo '<td class="' . $statusClass . '">' . htmlspecialchars(ucfirst($row['status'] ?? 'Pending')) . '</td>';
                echo '<td>' . htmlspecialchars($row['date_created']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="12" align="center">No leave records found</td></tr>';
        }
        ?>
        <tr><td colspan="12">&nbsp;</td></tr>
        <tr>
            <td colspan="12" class="subtitle">Filters: Status: <?php echo htmlspecialchars(ucfirst($status)); ?><?php echo $search !== '' ? ' | Search: ' . htmlspecialchars($search) : ''; ?></td>
        </tr>
        <tr>
            <td colspan="12" class="subtitle">© <?php echo date('Y'); ?> BTech HRMS - Human Resource Management System</td>
        </tr>
    </table>
</body>
</html>
