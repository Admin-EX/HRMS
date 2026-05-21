<?php
include "../../database/connection.php";
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$requestId = mysqli_real_escape_string($connection, $_POST['request_id']);
$status = 'approved';
$employeeId = mysqli_real_escape_string($connection, $_POST['employee_id']);
$leaveType = mysqli_real_escape_string($connection, $_POST['leave_type']);
$days = (int)$_POST['days'];
$overrideReason = isset($_POST['override_reason']) ? mysqli_real_escape_string($connection, $_POST['override_reason']) : '';

// Update the leave request status
$sql = "UPDATE leave_requests 
        SET status = '$status',
            approved_date = NOW()";

if (!empty($overrideReason)) {
    $sql .= ", override_reason = '$overrideReason'";
}

$sql .= " WHERE id = '$requestId'";

$result = mysqli_query($connection, $sql);

if ($result) {
    // You might want to update a separate leave_balance table here
    // to track used leave days more accurately
    
    echo json_encode([
        'success' => true,
        'message' => 'Leave request approved successfully'
    ]);
        $admin_id = $_SESSION['loggedUser'];
    $activitysql = "INSERT INTO `activity_log`(`employee_number`, `title`, `content`, `url`, `read_status`) 
                VALUES ('$employeeId', '$status Leave Document', 'your application for leave has been $status ', '/activity.php', 'unread')";
                $query = mysqli_query($connection, $activitysql);
    $activitysql1 = "INSERT INTO `activity_log`(`employee_number`, `title`, `content`, `url`, `read_status`) 
                VALUES ('$admin_id', '$status Leave Document', 'you have been $status application of Employee $employeeId ', '/leave.php', 'unread')";
    $query1 = mysqli_query($connection, $activitysql1);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error approving leave request: ' . mysqli_error($connection)
    ]);
}

mysqli_close($connection);
?>