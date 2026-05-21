<?php
include("../database/connection.php");
session_start();

// Check if admin is logged in
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: ../../admin_login.php");
//     exit();
// }

// Get filter parameters
$employee_type = $_GET['employee_type'] ?? 'all';
$review_status = $_GET['review_status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build the query
$query = "SELECT 
            e.employee_number,
            full_name,
            e.employee_type,
            e.department,
            e.position,
            e.phone,
            e.email,
            COUNT(DISTINCT ed.id) as total_documents,
            SUM(CASE WHEN ed.status = 'pending' THEN 1 ELSE 0 END) as pending_docs,
            SUM(CASE WHEN ed.status = 'approved' THEN 1 ELSE 0 END) as approved_docs,
            SUM(CASE WHEN ed.status = 'rejected' THEN 1 ELSE 0 END) as rejected_docs,
            SUM(CASE WHEN ed.status = 'for_revision' THEN 1 ELSE 0 END) as for_revision_docs,
            e.date_hired as registration_date
          FROM employees e
          LEFT JOIN employee_documents ed ON e.employee_number = ed.employee_number
          WHERE 1=1";

// Apply filters
if ($employee_type !== 'all') {
    $query .= " AND e.employee_type = '" . $connection->real_escape_string($employee_type) . "'";
}

if ($review_status !== 'all') {
    $query .= " AND ed.status = '" . $connection->real_escape_string($review_status) . "'";
}

if (!empty($search)) {
    $search_term = $connection->real_escape_string($search);
    $query .= " AND (e.employee_number LIKE '%$search_term%' 
                OR e.first_name LIKE '%$search_term%' 
                OR e.last_name LIKE '%$search_term%'
                OR e.department LIKE '%$search_term%')";
}

$query .= " GROUP BY e.employee_number ORDER BY e.employee_number ASC";

$result = $connection->query($query);

// Get statistics
$stats_query = "SELECT 
                COUNT(DISTINCT e.employee_number) as total_employees,
                SUM(CASE WHEN ed.status = 'pending' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN ed.status = 'approved' THEN 1 ELSE 0 END) as approved_docs,
                SUM(CASE WHEN ed.status = 'rejected' THEN 1 ELSE 0 END) as rejected_docs,
                SUM(CASE WHEN ed.status = 'for_revision' THEN 1 ELSE 0 END) as for_revision
                FROM employees e
                LEFT JOIN employee_documents ed ON e.employee_number = ed.employee_number";
$stats_result = $connection->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="Employee_Document_Review_Report_' . date('Y-m-d_His') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .header {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
        }
        .subtitle {
            font-size: 10pt;
            text-align: center;
            color: #666666;
        }
        .stats {
            background-color: #F2F2F2;
            font-weight: bold;
        }
        .data {
            border: 1px solid #D0D0D0;
        }
        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
            font-weight: bold;
        }
        .status-approved {
            background-color: #D4EDDA;
            color: #155724;
            font-weight: bold;
        }
        .status-rejected {
            background-color: #F8D7DA;
            color: #721C24;
            font-weight: bold;
        }
        .status-revision {
            background-color: #D1ECF1;
            color: #0C5460;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <table border="1" cellpadding="5" cellspacing="0">
        <!-- Title Row -->
        <tr>
            <td colspan="13" class="title">
                BTech HRMS - Employee Document Review Report
            </td>
        </tr>
        <tr>
            <td colspan="13" class="subtitle">
                Generated on: <?php echo date('F d, Y h:i A'); ?>
            </td>
        </tr>
        <tr>
            <td colspan="13">&nbsp;</td>
        </tr>

        <!-- Statistics Section -->
        <tr class="stats">
            <td colspan="2">Total Employees</td>
            <td colspan="2">Pending Approval</td>
            <td colspan="2">Approved Documents</td>
            <td colspan="2">Rejected Documents</td>
            <td colspan="2">For Revision</td>
            <td colspan="3">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2" align="center"><?php echo $stats['total_employees']; ?></td>
            <td colspan="2" align="center"><?php echo $stats['pending_approval']; ?></td>
            <td colspan="2" align="center"><?php echo $stats['approved_docs']; ?></td>
            <td colspan="2" align="center"><?php echo $stats['rejected_docs']; ?></td>
            <td colspan="2" align="center"><?php echo $stats['for_revision']; ?></td>
            <td colspan="3">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="13">&nbsp;</td>
        </tr>

        <!-- Table Headers -->
        <tr class="header">
            <td>EMPLOYEE ID</td>
            <td>FULL NAME</td>
            <td>EMPLOYEE TYPE</td>
            <td>DEPARTMENT</td>
            <td>POSITION</td>
            <td>CONTACT NUMBER</td>
            <td>EMAIL</td>
            <td>TOTAL DOCUMENTS</td>
            <td>PENDING</td>
            <td>APPROVED</td>
            <td>REJECTED</td>
            <td>FOR REVISION</td>
            <td>REGISTRATION DATE</td>
        </tr>

        <!-- Data Rows -->
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<tr class="data">';
                echo '<td>' . htmlspecialchars($row['employee_number']) . '</td>';
                echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['employee_type']) . '</td>';
                echo '<td>' . htmlspecialchars($row['department']) . '</td>';
                echo '<td>' . htmlspecialchars($row['position'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['contact_number'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['email'] ?? 'N/A') . '</td>';
                echo '<td align="center">' . $row['total_documents'] . '</td>';
                echo '<td align="center" class="status-pending">' . $row['pending_docs'] . '</td>';
                echo '<td align="center" class="status-approved">' . $row['approved_docs'] . '</td>';
                echo '<td align="center" class="status-rejected">' . $row['rejected_docs'] . '</td>';
                echo '<td align="center" class="status-revision">' . $row['for_revision_docs'] . '</td>';
                echo '<td>' . date('M d, Y', strtotime($row['registration_date'])) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="13" align="center">No records found</td></tr>';
        }
        ?>

        <!-- Footer -->
        <tr>
            <td colspan="13">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="13" class="subtitle">
                Report Filters: 
                Employee Type: <?php echo ucfirst($employee_type); ?> | 
                Review Status: <?php echo ucfirst($review_status); ?>
                <?php if (!empty($search)) echo " | Search: " . htmlspecialchars($search); ?>
            </td>
        </tr>
        <tr>
            <td colspan="13" class="subtitle">
                © <?php echo date('Y'); ?> BTech HRMS - Human Resource Management System
            </td>
        </tr>
    </table>
</body>
</html>