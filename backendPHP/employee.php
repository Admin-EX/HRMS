<?php
include "../database/connection.php";
header('Content-Type: application/json');

$sql = "SELECT 
    employee_number AS id,
    full_name AS name,
    employee_type AS type,
    department,
    position,
    credentials,
    gender,
    address,
    phone,
    email,
    status,
    employment_status
FROM employees";

$result = mysqli_query($connection, $sql);

$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = $row;
}

echo json_encode($employees);
