<?php
session_start();
require_once '../database/connection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['loggedUser'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login first.'
    ]);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

// Get employee ID from session
$employee_id = $_SESSION['loggedUser'];

// Verify employee exists and get employee type - USE PREPARED STATEMENT
$verify_query = "SELECT employee_type, employment_status FROM employees WHERE employee_number = ?";
$stmt = mysqli_prepare($connection, $verify_query);
mysqli_stmt_bind_param($stmt, "s", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Employee not found in database.'
    ]);
    exit();
}

$employee = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Check if employee is Teaching Staff
if ($employee['employee_type'] !== 'TP') {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Teaching Staff can submit offset forms.'
    ]);
    exit();
}

// Check if employee is active - FIXED: Should check 'status' not 'employment_status'
if ($employee['employment_status'] !== 'Active') {
    echo json_encode([
        'success' => false,
        'message' => 'Only active employees can submit offset forms.'
    ]);
    exit();
}

// Validate required fields
$required_fields = [
    'employee_number', 'subject_code', 'subject_description', 'academic_term',
    'schedule_section', 'original_sched_date', 'original_sched_time', 
    'offset_sched_date', 'offset_sched_time', 'reason', 'prepaired_by', 'submit_date'
];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields: ' . $field
        ]);
        exit();
    }
}

// Prepare insert statement to prevent SQL injection
$insert_query = "INSERT INTO offset (
    employee_number,
    subject_code,
    subject_description,
    academic_term,
    schedule_section,
    original_sched_date,
    original_sched_time,
    offset_sched_date,
    offset_sched_time,
    reason,
    prepaired_by,
    submit_date
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($connection, $insert_query);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . mysqli_error($connection)
    ]);
    exit();
}

// Bind parameters
mysqli_stmt_bind_param($stmt, "ssssssssssss",
    $_POST['employee_number'],
    $_POST['subject_code'],
    $_POST['subject_description'],
    $_POST['academic_term'],
    $_POST['schedule_section'],
    $_POST['original_sched_date'],
    $_POST['original_sched_time'],
    $_POST['offset_sched_date'],
    $_POST['offset_sched_time'],
    $_POST['reason'],
    $_POST['prepaired_by'],
    $_POST['submit_date']
);
$employee_number = $_POST['employee_number'];
$date =   $_POST['offset_sched_date'];
$time =    $_POST['offset_sched_time'];
if (mysqli_stmt_execute($stmt)) {
    $offset_id = mysqli_insert_id($connection);
                $activitysql = "INSERT INTO `activity_log`(`employee_number`, `title`, `content`, `url`, `read_status`) 
                VALUES ('$employee_number', 'Offset Document', 'You have successfully change your schedule date to $date at $time ', '/activity.php', 'unread')";
$query = mysqli_query($connection, $activitysql);
    echo json_encode([
        'success' => true,
        'message' => 'Offset form submitted successfully!',
        'offset_id' => $offset_id,
        'subject_code' => $_POST['subject_code']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit offset form. Please try again.',
        'error' => mysqli_stmt_error($stmt)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($connection);
?>