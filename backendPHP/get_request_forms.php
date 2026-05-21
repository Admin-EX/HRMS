<?php
include("../database/connection.php");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $sql = "SELECT `id`, `employee_number`, `type`, `reason`, `prepared_by`, `submit_date`,`status` 
            FROM `request_form` 
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