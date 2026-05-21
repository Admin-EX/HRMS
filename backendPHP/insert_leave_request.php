<?php
header('Content-Type: application/json');
session_start();
require_once '../database/connection.php';

// Check if connection exists from included file
if (!isset($connection)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['employeeId']) || empty($data['leaveType']) || 
    empty($data['leaveStartDate']) || empty($data['leaveEndDate']) || 
    empty($data['numberOfDays']) || empty($data['leaveReason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Sanitize inputs
$employeeNumber = mysqli_real_escape_string($connection, $data['employeeId']);
$leaveType = mysqli_real_escape_string($connection, $data['leaveType']);
$startDate = mysqli_real_escape_string($connection, $data['leaveStartDate']);
$endDate = mysqli_real_escape_string($connection, $data['leaveEndDate']);
$numberOfDays = intval($data['numberOfDays']);
$emergencyContact = mysqli_real_escape_string($connection, $data['emergencyContact'] ?? '');
$leaveReason = mysqli_real_escape_string($connection, $data['leaveReason']);
$status = 'pending';

// Begin transaction
mysqli_begin_transaction($connection);

try {
    // Insert leave request
    $sql = "INSERT INTO leave_requests (
        employee_number, 
        type, 
        start_date, 
        end_date, 
        days, 
        emergency_contact, 
        reason, 
        status, 
        date_created
    ) VALUES (
        '$employeeNumber',
        '$leaveType',
        '$startDate',
        '$endDate',
        $numberOfDays,
        '$emergencyContact',
        '$leaveReason',
        '$status',
        NOW()
    )";
    
    if (!mysqli_query($connection, $sql)) {
        throw new Exception('Failed to insert leave request: ' . mysqli_error($connection));
    }
    
    $requestId = mysqli_insert_id($connection);
    
    // Update employee leave balance
    $updateBalance = "UPDATE employees 
                      SET leave_balance = leave_balance - $numberOfDays 
                      WHERE employee_number = '$employeeNumber'";
    
    if (!mysqli_query($connection, $updateBalance)) {
        throw new Exception('Failed to update leave balance: ' . mysqli_error($connection));
    }
    
    // Commit transaction
    mysqli_commit($connection);
    
    // Optional: Send email notification
    // sendEmailNotification($data);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Leave request submitted successfully',
        'requestId' => $requestId,
        'remainingBalance' => $data['remainingBalance']
    ]);
        $offset_id = mysqli_insert_id($connection);
                $activitysql = "INSERT INTO `activity_log`(`employee_number`, `title`, `content`, `url`, `read_status`) 
                VALUES ('$employeeNumber', 'Leave Document', 'You have successfully submit $leaveType request, wait for HR to review it and will notify you ', '/activity.php', 'unread')";
$query = mysqli_query($connection, $activitysql);
} catch(Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($connection);
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

// Close connection
mysqli_close($connection);
?>