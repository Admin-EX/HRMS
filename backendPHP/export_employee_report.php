<?php
include "../database/connection.php";
session_start();
error_reporting(0);

$employee_type = isset($_GET['employee_type']) ? trim($_GET['employee_type']) : 'all';
$credential = isset($_GET['credential']) ? trim($_GET['credential']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "WHERE 1=1";
if ($employee_type !== 'all' && $employee_type !== '') {
    $escaped_type = $connection->real_escape_string($employee_type);
    $where .= " AND employee_type = '$escaped_type'";
}
if ($credential !== '') {
    $escaped_credential = $connection->real_escape_string($credential);
    $where .= " AND credentials = '$escaped_credential'";
}
if ($search !== '') {
    $escaped_search = $connection->real_escape_string($search);
    $where .= " AND (employee_number LIKE '%$escaped_search%' OR full_name LIKE '%$escaped_search%' OR department LIKE '%$escaped_search%' OR position LIKE '%$escaped_search%' OR email LIKE '%$escaped_search%' OR phone LIKE '%$escaped_search%')";
}

$sql = "SELECT employee_number, full_name, employee_type, department, position, credentials, gender, email, phone, status, employment_status, date_hired FROM employees $where ORDER BY full_name ASC";
$result = mysqli_query($connection, $sql);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Employee_Report_' . date('Y-m-d_His') . '.xls"');
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
        .data { border: 1px solid #D0D0D0; }
        .stats { background-color: #F2F2F2; font-weight: bold; }
        .cell-label { background-color: #F8F8F8; font-weight: bold; }
    </style>
</head>
<body>
    <table border="1" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td colspan="12" class="title">BTech HRMS - Employee Report</td>
        </tr>
        <tr>
            <td colspan="12" class="subtitle">Generated on: <?php echo date('F d, Y h:i A'); ?></td>
        </tr>
        <tr><td colspan="12">&nbsp;</td></tr>
        <tr class="header">
            <td>Employee Number</td>
            <td>Full Name</td>
            <td>Employee Type</td>
            <td>Department</td>
            <td>Position</td>
            <td>Credentials</td>
            <td>Gender</td>
            <td>Email</td>
            <td>Phone</td>
            <td>Status</td>
            <td>Employment Status</td>
            <td>Date Hired</td>
        </tr>
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<tr class="data">';
                echo '<td>' . htmlspecialchars($row['employee_number']) . '</td>';
                echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['employee_type']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                echo '<td>' . htmlspecialchars($row['position']) . '</td>';
                echo '<td>' . htmlspecialchars($row['credentials']) . '</td>';
                echo '<td>' . htmlspecialchars($row['gender']) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                echo '<td>' . htmlspecialchars($row['phone']) . '</td>';
                echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                echo '<td>' . htmlspecialchars($row['employment_status']) . '</td>';
                echo '<td>' . htmlspecialchars($row['date_hired']) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="12" align="center">No employee records found</td></tr>';
        }
        ?>
        <tr><td colspan="12">&nbsp;</td></tr>
        <tr>
            <td colspan="12" class="subtitle">
                Filters: Employee Type: <?php echo htmlspecialchars(ucfirst($employee_type)); ?>
                <?php if ($credential !== '') echo ' | Credential: ' . htmlspecialchars($credential); ?>
                <?php if ($search !== '') echo ' | Search: ' . htmlspecialchars($search); ?>
            </td>
        </tr>
        <tr>
            <td colspan="12" class="subtitle">© <?php echo date('Y'); ?> BTech HRMS - Human Resource Management System</td>
        </tr>
    </table>
</body>
</html>
