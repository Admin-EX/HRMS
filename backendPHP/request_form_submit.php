<?php
header('Content-Type: application/json');
require_once('../database/connection.php');
// Check connection
if ($connection->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate data
if (!isset($data['employee_number']) || !isset($data['type']) || 
    !isset($data['reason']) || !isset($data['prepared_by']) || 
    !isset($data['submit_date'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

// Prepare SQL statement
$stmt = $connection->prepare("INSERT INTO request_form (employee_number, type, reason, prepared_by, submit_date) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", 
    $data['employee_number'],
    $data['type'],
    $data['reason'],
    $data['prepared_by'],
    $data['submit_date']
);

// Execute statement
if ($stmt->execute()) {
    $formid = $connection->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'Request submitted successfully',
        'formid' => $formid
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit request: ' . $connection->error
    ]);
}

$stmt->close();
$connection->close();
?>