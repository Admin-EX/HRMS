<?php
session_start();
include "../database/connection.php";

header('Content-Type: application/json');

$employeeNumber = $_POST['employeeNumber'] ?? '';
$password       = $_POST['password'] ?? '';

if (empty($employeeNumber) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required"
    ]);
    exit;
}
$sql = "SELECT * FROM users WHERE employee_number = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, "s", $employeeNumber);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    if (($user['status'] ?? 'active') !== 'active') {
        echo json_encode([
            "status" => "error",
            "message" => "Account is inactive. Please contact your administrator."
        ]);
        mysqli_close($connection);
        exit;
    }

    $storedPassword = $user['password'] ?? '';
    $isPasswordValid = false;

    // Support modern password_hash() entries, with fallback for legacy MD5 passwords.
    if ($storedPassword !== '' && password_verify($password, $storedPassword)) {
        $isPasswordValid = true;
    } elseif (md5($password) === $storedPassword) {
        $isPasswordValid = true;
    }

    if ($isPasswordValid) {
        $role = strtolower(trim($user['role'] ?? 'employee'));
        $_SESSION['role'] = $role;
        $_SESSION['loggedUser'] = $employeeNumber;

        $redirect = 'dashboard.php';

        if ($role === 'admin' || $role === 'super_admin') {
            $admin_name = $employeeNumber;
            $empStmt = mysqli_prepare($connection, "SELECT full_name FROM employees WHERE employee_number = ? LIMIT 1");
            if ($empStmt) {
                mysqli_stmt_bind_param($empStmt, "s", $employeeNumber);
                mysqli_stmt_execute($empStmt);
                $empRes = mysqli_stmt_get_result($empStmt);
                if ($empRow = mysqli_fetch_assoc($empRes)) {
                    $admin_name = trim($empRow['full_name'] ?: $employeeNumber);
                }
                mysqli_stmt_close($empStmt);
            }

            $_SESSION['admin_name'] = $admin_name;
            $_SESSION['admin_role'] = ($role === 'super_admin') ? 'Super Admin' : 'Admin';
            $redirect = '../admin/dashboard.php';
        }

        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "role" => $role,
            "redirect" => $redirect
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid Employee ID or Password"
        ]);
    }

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid Employee ID or Password"
    ]);
}
mysqli_close($connection);