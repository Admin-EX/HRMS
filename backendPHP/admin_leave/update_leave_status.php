<?php
include "../../database/connection.php";
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
session_start();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$requestId = mysqli_real_escape_string($connection, $_POST['request_id']);
$status = mysqli_real_escape_string($connection, $_POST['status']);
$reason = isset($_POST['reason']) ? mysqli_real_escape_string($connection, $_POST['reason']) : '';

// Update the leave request status
$sql = "UPDATE leave_requests 
        SET status = '$status'";

// Add reason to a notes field if you have one, or create one
if (!empty($reason)) {
    $sql .= ", denial_reason = '$reason'";
}

$sql .= " WHERE id = '$requestId'";

$result = mysqli_query($connection, $sql);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Leave status updated successfully'
    ]);

    $admin_id = $_SESSION['loggedUser'];
    $activitysql = "INSERT INTO `activity_log`(`employee_number`, `title`, `content`, `url`, `read_status`) 
                VALUES ('$employeeId', '$status Leave Document', 'your application for leave has been $status ', '/activity.php', 'unread')";
                    $query = mysqli_query($connection, $activitysql);
    $activitysql1 = "INSERT INTO `activity_log`(`employee_number`, `title`, `content`, `url`, `read_status`) 
                VALUES ('$admin_id', '$status Leave Document', 'you have been $status application of Employee $employeeId ', 'https://hrms.fun/php/admin/request.php', 'unread')";
    $query1 = mysqli_query($connection, $activitysql1);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating leave status: ' . mysqli_error($connection)
    ]);
}

mysqli_close($connection);
?>