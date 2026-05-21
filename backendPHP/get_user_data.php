<?php
// get_user_data.php
session_start();
require_once '../database/connection.php';

header('Content-Type: application/json');

$employee_id = $_SESSION['employee_id'] ?? $_GET['employee_id'] ?? null;

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$query = "SELECT 
    employee_number as id,
    full_name as name,
    employee_type as type,
    position,
    department,
    email,
    phone as contact,
    date_hired,
    leave_balance,
    gender,
    address,
    status
FROM employees 
WHERE employee_number = '$employee_id'";

$result = mysqli_query($connection, $query);

if ($result && $row = mysqli_fetch_assoc($result)) {
    // Parse first and last name
    $name_parts = explode(' ', $row['name']);
    $first_name = $name_parts[0] ?? '';
    $last_name = end($name_parts) ?? '';
    $middle_name = count($name_parts) > 2 ? implode(' ', array_slice($name_parts, 1, -1)) : '';
    
    $response = [
        'success' => true,
        'data' => [
            'id' => $row['id'],
            'name' => $row['name'],
            'firstName' => $first_name,
            'lastName' => $last_name,
            'middleName' => $middle_name,
            'position' => $row['position'],
            'department' => $row['department'],
            'email' => $row['email'],
            'contact' => $row['contact'],
            'type' => $row['type'],
            'dateHired' => $row['date_hired'],
            'leaveBalance' => $row['leave_balance'] ?? 0,
            'gender' => $row['gender'],
            'address' => $row['address'],
            'status' => $row['status']
        ]
    ];
} else {
    $response = [
        'success' => false,
        'message' => 'User data not found'
    ];
}

echo json_encode($response);
?>