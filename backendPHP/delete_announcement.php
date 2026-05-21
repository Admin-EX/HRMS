<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid id']);
        exit;
    }

    // Prefer soft-delete by setting status to 'Archived'
    $sql = "UPDATE announcements SET status = 'Archived' WHERE id = ?";
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $connection->error]);
        exit;
    }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'DB execute error: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Announcement deleted']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($connection)) $connection->close();
}
