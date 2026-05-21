<?php
// submit_leave.php
session_start();
require_once '../database/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get user from session
        $employee_id = $_SESSION['loggedUser'] ?? $_POST['loggedUser'] ?? null;
        
        if (!$employee_id) {
            throw new Exception('User not authenticated. Please log in.');
        }
        
        // First, get employee data from database
        $employee_query = "SELECT 
            employee_number, 
            full_name, 
            position, 
            department, 
            email, 
            phone, 
            leave_balance 
        FROM employees 
        WHERE employee_number = '$employee_id'";
        
        $employee_result = mysqli_query($connection, $employee_query);
        
        if (!$employee_result || mysqli_num_rows($employee_result) === 0) {
            throw new Exception('Employee data not found.');
        }
        
        $employee_data = mysqli_fetch_assoc($employee_result);
        $employee_name = $employee_data['full_name'];
        $current_balance = $employee_data['leave_balance'] ?? 0;
        
        // Get form data
        $leave_type = mysqli_real_escape_string($connection, $_POST['leave_type'] ?? '');
        $start_date = mysqli_real_escape_string($connection, $_POST['start_date'] ?? '');
        $end_date = mysqli_real_escape_string($connection, $_POST['end_date'] ?? '');
        $days = intval($_POST['days'] ?? 0);
        $reason = mysqli_real_escape_string($connection, $_POST['reason'] ?? '');
        $emergency_contact = mysqli_real_escape_string($connection, $_POST['emergency_contact'] ?? '');
        $contact_number = mysqli_real_escape_string($connection, $_POST['contact_number'] ?? $employee_data['phone'] ?? '');
        
        // Validate required fields
        if (empty($leave_type)) {
            throw new Exception('Please select a Type of Leave.');
        }
        
        if (empty($start_date)) {
            throw new Exception('Please select Leave Start Date.');
        }
        
        if (empty($end_date)) {
            throw new Exception('Please select Leave End Date.');
        }
        
        if ($days <= 0) {
            throw new Exception('Please select valid leave dates.');
        }
        
        if (empty($reason)) {
            throw new Exception('Please provide a Reason for Leave.');
        }
        
        // Validate date order
        if (strtotime($end_date) < strtotime($start_date)) {
            throw new Exception('End date must be after start date.');
        }
        
        // Check if dates are in the future (at least 1 day from today)
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        
        if (strtotime($start_date) < strtotime($tomorrow)) {
            throw new Exception('Leave must start at least 1 day from today.');
        }
        
        // Check leave balance
        if ($days > $current_balance) {
            throw new Exception("Insufficient leave balance. Requested: $days days, Available: $current_balance days");
        }
        
        // Start transaction
        mysqli_begin_transaction($connection);
        
        try {
            // Insert leave request
            $insert_query = "INSERT INTO leave_requests (
                employee_number, 
                type, 
                start_date, 
                end_date, 
                days, 
                reason, 
                emergency_contact, 
                contact_number,
                status,
                date_created
            ) VALUES (
                '$employee_id',
                '$leave_type',
                '$start_date',
                '$end_date',
                $days,
                '$reason',
                '$emergency_contact',
                '$contact_number',
                'Pending',
                NOW()
            )";
            
            $insert_result = mysqli_query($connection, $insert_query);
            
            if (!$insert_result) {
                throw new Exception('Failed to submit leave request: ' . mysqli_error($connection));
            }
            
            $request_id = mysqli_insert_id($connection);
            
            // Update employee leave balance
            $new_balance = $current_balance - $days;
            $update_query = "UPDATE employees SET leave_balance = $new_balance WHERE employee_number = '$employee_id'";
            $update_result = mysqli_query($connection, $update_query);
            
            if (!$update_result) {
                throw new Exception('Failed to update leave balance: ' . mysqli_error($connection));
            }
            
            // Insert into leave_balance_history for tracking
            $history_query = "INSERT INTO leave_balance_history (
                employee_number,
                transaction_type,
                leave_request_id,
                days_used,
                previous_balance,
                new_balance,
                transaction_date
            ) VALUES (
                '$employee_id',
                'LEAVE_REQUEST',
                $request_id,
                $days,
                $current_balance,
                $new_balance,
                NOW()
            )";
            
            mysqli_query($connection, $history_query);
            
            // Commit transaction
            mysqli_commit($connection);
            
            // Get formatted dates for response
            $formatted_start = date('M d, Y', strtotime($start_date));
            $formatted_end = date('M d, Y', strtotime($end_date));
            
            // Prepare email notification (optional)
            $email_sent = sendLeaveNotification($employee_data, [
                'request_id' => $request_id,
                'leave_type' => $leave_type,
                'start_date' => $formatted_start,
                'end_date' => $formatted_end,
                'days' => $days,
                'reason' => $reason,
                'new_balance' => $new_balance
            ]);
            
            // Send success response
            echo json_encode([
                'success' => true,
                'message' => 'Leave request submitted successfully!',
                'data' => [
                    'request_id' => $request_id,
                    'employee_id' => $employee_id,
                    'employee_name' => $employee_name,
                    'leave_type' => getLeaveTypeLabel($leave_type),
                    'start_date' => $formatted_start,
                    'end_date' => $formatted_end,
                    'days' => $days,
                    'remaining_balance' => $new_balance,
                    'status' => 'Pending',
                    'email_sent' => $email_sent
                ]
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($connection);
            throw $e;
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

// Helper function to get leave type label
function getLeaveTypeLabel($type) {
    $labels = [
        'vacation' => 'Vacation Leave',
        'sick' => 'Sick Leave',
        'maternity' => 'Maternity Leave',
        'paternity' => 'Paternity Leave',
        'bereavement' => 'Bereavement Leave',
        'emergency' => 'Emergency Leave',
        'other' => 'Other Leave'
    ];
    
    return $labels[$type] ?? ucfirst($type);
}

// Function to send email notification (optional)
function sendLeaveNotification($employee, $leave_data) {
    // This is a placeholder - implement your email sending logic here
    
    /*
    $to = $employee['email'];
    $subject = "Leave Request Submitted - Request #" . $leave_data['request_id'];
    
    $message = "
    <html>
    <head>
        <title>Leave Request Confirmation</title>
    </head>
    <body>
        <h2>Leave Request Submitted Successfully</h2>
        <p>Dear " . $employee['full_name'] . ",</p>
        <p>Your leave request has been submitted successfully.</p>
        
        <h3>Request Details:</h3>
        <table border='1' cellpadding='10'>
            <tr><td><strong>Request ID:</strong></td><td>" . $leave_data['request_id'] . "</td></tr>
            <tr><td><strong>Employee:</strong></td><td>" . $employee['full_name'] . " (" . $employee['employee_number'] . ")</td></tr>
            <tr><td><strong>Leave Type:</strong></td><td>" . $leave_data['leave_type'] . "</td></tr>
            <tr><td><strong>Duration:</strong></td><td>" . $leave_data['days'] . " day(s)</td></tr>
            <tr><td><strong>From:</strong></td><td>" . $leave_data['start_date'] . "</td></tr>
            <tr><td><strong>To:</strong></td><td>" . $leave_data['end_date'] . "</td></tr>
            <tr><td><strong>Reason:</strong></td><td>" . $leave_data['reason'] . "</td></tr>
            <tr><td><strong>Remaining Balance:</strong></td><td>" . $leave_data['new_balance'] . " days</td></tr>
            <tr><td><strong>Status:</strong></td><td>Pending Approval</td></tr>
        </table>
        
        <p>Your request will be reviewed by the HR department. You will receive another email once it's approved or denied.</p>
        
        <p>Thank you,<br>
        HR Department</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: hr@yourcompany.com" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
    */
    
    return false; // Return false for now since email is not implemented
}
?>