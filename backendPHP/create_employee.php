<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
require_once __DIR__ . '/../database/connection.php'; // Include your database connection file

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Function to generate random password
function generateRandomPassword($length = 8) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    $charactersLength = strlen($characters);
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $password;
}

try {
    // Get form data
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
    
    // Validate required fields
    if (empty($employee_number) || empty($full_name) || empty($employee_type) || 
        empty($department) || empty($position) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    // Check if employee number already exists
    $check_stmt = $connection->prepare("SELECT id FROM employees WHERE employee_number = ?");
    $check_stmt->bind_param("s", $employee_number);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Employee number already exists']);
        exit;
    }
    $check_stmt->close();
    
    // Generate random password (8 characters)
    $plain_password = generateRandomPassword(8);
    
    // Hash password with MD5 (Note: Consider using password_hash() instead of MD5)
    $hashed_password = md5($plain_password);
    
    // Calculate years of service
    $hire_date = new DateTime($date_hired);
    $current_date = new DateTime();
    $years_service = $hire_date->diff($current_date)->y;
    
    // Map employee_type to type field
    $type = $employee_type; // Assuming 'TP' or 'NTP'
    
    // Prepare SQL statement for employees table
    $sql = "INSERT INTO employees (
        employee_number, 
        full_name, 
        employee_type, 
        department, 
        position, 
        credentials, 
        gender, 
        address, 
        phone, 
        email, 
        status, 
        educational_attainment, 
        school, 
        created_at, 
        date_hired, 
        type, 
        years_service, 
        employment_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
    
    $stmt = $connection->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $connection->error]);
        exit;
    }
    
    // Fix: Correct parameter order - employment_status should be in status position
    // and status should be in employment_status position
    $stmt->bind_param(
        "sssssssssssssssss",
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
        $employment_status,         // Goes to employment_status field
        $educational_attainment,
        $school,
        $date_hired,
        $type,
        $years_service,
        $status                    // Goes to status field
    );
    
    // Start transaction
    $connection->begin_transaction();
    
    try {
        // Insert into employees table
        if (!$stmt->execute()) {
            throw new Exception('Failed to create employee: ' . $stmt->error);
        }
        
        $employee_id = $connection->insert_id;
        $stmt->close();
        
        // Create user account
        $employee_role = "employee";
        $user_status = "active";
        
        // Hash password for users table (using password_hash for better security)
        $hashedPassword = md5($plain_password);
        
        $sqlUser = "INSERT INTO `users` 
                   (`employee_number`, `email`, `password`, `role`, `status`, `created_at`) 
                   VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmtUser = $connection->prepare($sqlUser);
        
        if (!$stmtUser) {
            throw new Exception('Failed to prepare user statement: ' . $connection->error);
        }
        
        $stmtUser->bind_param(
            "sssss",
            $employee_number,
            $email,
            $hashedPassword,  // Use the password_hash version
            $employee_role,
            $user_status
        );
        
        if (!$stmtUser->execute()) {
            throw new Exception('Failed to create user account: ' . $stmtUser->error);
        }
        
        $stmtUser->close();
        
        // Commit transaction
        $connection->commit();
        
        // Log activity (optional - uncomment if you have a logging function)
        // logActivity($connection, 'Employee Created', "New employee added: $full_name (ID: $employee_id)");
        
        echo json_encode([
            'success' => true,
            'message' => 'Employee created successfully',
            'employee_id' => $employee_id,
            'employee_number' => $employee_number,
            'password' => $plain_password, // Send plain password for display (only once)
            'full_name' => $full_name,
            'email' => $email
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $connection->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($connection)) {
        $connection->close();
    }
}
?>