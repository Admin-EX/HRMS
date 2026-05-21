<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include("../database/connection.php");
session_start();

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['loggedUser'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $employee_number = $_SESSION['loggedUser'];
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required.']);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit();
    }

    $full_name = trim($first_name . ' ' . $last_name);

    // Check if email exists for another user
    $check_query = "SELECT employee_number FROM employees WHERE email = ? AND employee_number != ?";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bind_param("ss", $email, $employee_number);
    $check_stmt->execute();

    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already in use by another account.']);
        exit();
    }

    // Update profile
    $update_query = "UPDATE employees SET full_name = ?, email = ?, phone = ? WHERE employee_number = ?";
    $update_stmt = $connection->prepare($update_query);
    $update_stmt->bind_param("ssss", $full_name, $email, $phone, $employee_number);

    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Profile updated successfully!',
            'full_name' => $full_name
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
    }

    $check_stmt->close();
    $update_stmt->close();
    $connection->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>