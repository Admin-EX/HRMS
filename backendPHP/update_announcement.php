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
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $announcement_date = $_POST['announcement_date'] ?? date('Y-m-d');
    $priority = $_POST['priority'] ?? 'Normal';

    if ($id <= 0 || empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Ensure table exists (include optional end_date for activity display)
    $createSql = "CREATE TABLE IF NOT EXISTS `announcements` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT NOT NULL,
        `announcement_date` DATE NOT NULL,
        `end_date` DATE DEFAULT NULL,
        `priority` VARCHAR(20) DEFAULT 'Normal',
        `status` VARCHAR(20) DEFAULT 'Active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $connection->query($createSql);
    // Ensure legacy tables get the optional end_date column (safe noop if already present)
    try {
        $connection->query("ALTER TABLE announcements ADD COLUMN IF NOT EXISTS `end_date` DATE DEFAULT NULL");
    } catch (Throwable $e) {
        // ignore - some MySQL versions may not support IF NOT EXISTS on ADD COLUMN
        @mysqli_query($connection, "ALTER TABLE announcements ADD COLUMN `end_date` DATE DEFAULT NULL");
    }
    $end_date = $_POST['end_date'] ?? null;
    if ($end_date === '') $end_date = null;

    $sql = "UPDATE announcements SET title = ?, content = ?, announcement_date = ?, end_date = ?, priority = ? WHERE id = ?";
    $stmt = $connection->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $connection->error]);
        exit;
    }
    $stmt->bind_param('sssssi', $title, $content, $announcement_date, $end_date, $priority, $id);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'DB execute error: ' . $stmt->error]);
        exit;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Announcement updated', 'announcement' => [
        'id' => $id,
        'title' => $title,
        'content' => $content,
        'announcement_date' => $announcement_date,
        'priority' => $priority
    ]]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($connection)) $connection->close();
}
