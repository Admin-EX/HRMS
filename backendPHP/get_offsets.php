<?php
include("../database/connection.php");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $sql = "SELECT `id`, `employee_number`, `subject_code`, `subject_description`, `academic_term`, 
                   `schedule_section`, `original_sched_date`, `original_sched_time`, 
                   `offset_sched_date`, `offset_sched_time`, `reason`, `prepaired_by`, 
                   `submit_date`, `status`
            FROM `offset` 
            WHERE 1 
            ORDER BY `submit_date` DESC";

    $result = $connection->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $connection->error);
    }

    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }

    echo json_encode($records);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$connection->close();
?>