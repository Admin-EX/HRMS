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
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
        exit();
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
        exit();
    }

    if ($current_password === $new_password) {
        echo json_encode(['success' => false, 'message' => 'New password must be different from current password.']);
        exit();
    }

    // Validate password strength
    if (strlen($new_password) < 8 || 
        !preg_match('/[A-Z]/', $new_password) || 
        !preg_match('/[a-z]/', $new_password) || 
        !preg_match('/[0-9]/', $new_password) || 
        !preg_match('/[^A-Za-z0-9]/', $new_password)) {
        echo json_encode(['success' => false, 'message' => 'Password does not meet requirements.']);
        exit();
    }

    // Get current password from database
    $query = "SELECT password FROM users WHERE employee_number = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $employee_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found.']);
        exit();
    }

    $employee = $result->fetch_assoc();
    $stored_password = $employee['password'];

    // Verify current password (MD5)
    $current_password_md5 = md5($current_password);

    if ($current_password_md5 !== $stored_password) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit();
    }

    // Hash new password
    $new_password_md5 = md5($new_password);

    // Update password
    $update_query = "UPDATE users SET password = ? WHERE employee_number = ?";
    $update_stmt = $connection->prepare($update_query);
    $update_stmt->bind_param("ss", $new_password_md5, $employee_number);

    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
    }

    $stmt->close();
    $update_stmt->close();
    $connection->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>