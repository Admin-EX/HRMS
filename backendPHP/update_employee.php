<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../database/connection.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $employee_id = $_POST['employee_id'] ?? '';
    $original_employee_number = $_POST['original_employee_number'] ?? '';
    $employee_number = $_POST['employee_number'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $employee_type = $_POST['employee_type'] ?? '';
    $department = $_POST['department'] ?? '';
    $position = $_POST['position'] ?? '';
    $credentials = $_POST['credentials'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $educational_attainment = $_POST['educational_attainment'] ?? '';
    $school = $_POST['school'] ?? '';
    $date_hired = $_POST['date_hired'] ?? date('Y-m-d');
    $employment_status = $_POST['employment_status'] ?? '';

    if (empty($employee_id) || empty($employee_number) || empty($full_name) || empty($employee_type) || empty($department) || empty($position) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    $check_stmt = $connection->prepare("SELECT id FROM employees WHERE employee_number = ? AND id != ?");
    $check_stmt->bind_param("si", $employee_number, $employee_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Employee number already exists']);
        exit;
    }
    $check_stmt->close();

    $type = $employee_type;

    $sql = "UPDATE employees SET
        employee_number = ?,
        full_name = ?,
        employee_type = ?,
        department = ?,
        position = ?,
        credentials = ?,
        gender = ?,
        address = ?,
        phone = ?,
        email = ?,
        status = ?,
        educational_attainment = ?,
        school = ?,
        date_hired = ?,
        type = ?,
        employment_status = ?
        WHERE id = ?";

    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $connection->error]);
        exit;
    }

    $types = str_repeat('s', 16) . 'i';
    $stmt->bind_param(
        $types,
        $employee_number,
        $full_name,
        $employee_type,
        $department,
        $position,
        $credentials,
        $gender,
        $address,
        $phone,
        $email,
        $status,
        $educational_attainment,
        $school,
        $date_hired,
        $type,
        $employment_status,
        $employee_id
    );

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee: ' . $stmt->error]);
        exit;
    }

    $stmt->close();

    $user_stmt = $connection->prepare("UPDATE users SET employee_number = ?, email = ? WHERE employee_number = ?");
    if ($user_stmt) {
        $user_stmt->bind_param("sss", $employee_number, $email, $original_employee_number);
        $user_stmt->execute();
        $user_stmt->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Employee updated successfully'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($connection)) {
        $connection->close();
    }
}
?>
