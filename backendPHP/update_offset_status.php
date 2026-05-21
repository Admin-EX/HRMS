<?php
include("../database/connection.php");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
session_start();

// ── Helper: insert into activity_log ─────────────────────────────────────────
function logActivity($connection, $employee_number, $title, $content, $url = '') {
    $stmt = $connection->prepare(
        "INSERT INTO `activity_log` (`employee_number`, `title`, `content`, `url`, `read_status`, `date`)
         VALUES (?, ?, ?, ?, 'unread', NOW())"
    );
    $stmt->bind_param("ssss", $employee_number, $title, $content, $url);
    $stmt->execute();
    $stmt->close();
}

try {
    $id       = $_POST['offset_id'] ?? '';
    $status   = $_POST['status']    ?? '';
    $reason   = $_POST['reason']    ?? '';
    $admin_id = $_SESSION["loggedUser"]  ?? 'admin'; // ✅ pass from JS

    // Validate required fields
    if (empty($id) || empty($status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: offset_id and status']);
        exit;
    }

    // Allowed status values
    $allowed_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status. Allowed: pending, approved, rejected']);
        exit;
    }

    // ── 1. Get offset details before updating ─────────────────────────────────
    $fetch = $connection->prepare(
        "SELECT `employee_number`, `subject_code`, `subject_description` 
         FROM `offset` WHERE `id` = ?"
    );
    $fetch->bind_param("i", $id);
    $fetch->execute();
    $fetch->bind_result($employee_number, $subject_code, $subject_description);
    if (!$fetch->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        exit;
    }
    $fetch->close();

    // ── 2. Update offset status ───────────────────────────────────────────────
    $stmt = $connection->prepare("UPDATE `offset` SET `status` = ? WHERE `id` = ?");
    $stmt->bind_param("si", $status, $id);

    if (!$stmt->execute()) {
        throw new Exception("Update failed: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Record not found or status unchanged']);
        exit;
    }
    $stmt->close();

    $status_label = ucfirst($status); // Approved / Rejected / Pending

    // ── 3. Log for EMPLOYEE ───────────────────────────────────────────────────
    $emp_title   = "Offset Request {$status_label}";
    $emp_content = "Your offset request #{$id} for {$subject_code} - {$subject_description} "
                 . "has been {$status}"
                 . ($reason ? ". Reason: {$reason}" : ".");
    $emp_url     = "/activity.php"; // adjust to your actual URL

    logActivity($connection, $employee_number, $emp_title, $emp_content, $emp_url);

    // ── 4. Log for ADMIN ──────────────────────────────────────────────────────
    $admin_title   = "Offset Request #{$id} {$status_label}";
    $admin_content = "You {$status} the offset request #{$id} "
                   . "({$subject_code} - {$subject_description}) "
                   . "from employee {$employee_number}"
                   . ($reason ? ". Reason: {$reason}" : ".");
    $admin_url     = "/leave.php"; // adjust to your actual URL

    logActivity($connection, $admin_id, $admin_title, $admin_content, $admin_url);

    // ── 5. Respond ────────────────────────────────────────────────────────────
    echo json_encode([
        'success' => true,
        'message' => "Offset #{$id} has been {$status}.",
        'id'      => $id,
        'status'  => $status
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$connection->close();
?>