<?php
include("../database/connection.php");
session_start();
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$document_id = intval($input['document_id'] ?? 0);
$status      = trim($input['status'] ?? '');
$notes       = trim($input['notes'] ?? '');
$admin_name  = $_SESSION['admin_name'] ?? 'Admin';

// Validate
$allowed_statuses = ['approved', 'rejected', 'for_revision', 'pending'];
if (!$document_id || !in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid document ID or status']);
    exit;
}

// Update the document status
$stmt = $connection->prepare(
    "UPDATE employee_documents 
     SET status = ?, 
         notes = ?, 
         reviewed_by = ?, 
         review_date = NOW() 
     WHERE id = ?"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query preparation failed: ' . $connection->error]);
    exit;
}

$stmt->bind_param('sssi', $status, $notes, $admin_name, $document_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Optionally log to admin_notifications
        if ($connection->query("SHOW TABLES LIKE 'admin_notifications'")->num_rows > 0) {
            $action_label = ucfirst($status);
            $notif_msg = "Document ID $document_id has been $action_label by $admin_name.";
            $notif_stmt = $connection->prepare(
                "INSERT INTO admin_notifications (message, read_status, created_at) VALUES (?, 'unread', NOW())"
            );
            if ($notif_stmt) {
                $notif_stmt->bind_param('s', $notif_msg);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Document status updated to ' . $status,
            'document_id' => $document_id,
            'new_status' => $status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No document found with that ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
}

$stmt->close();
$connection->close();
?>