<?php
include __DIR__ . '/../database/connection.php';
header('Content-Type: application/json');

$sql = "SELECT 
    id AS db_id,
    employee_number AS id,
    employee_number,
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
    employment_status,
    educational_attainment,
    school,
    date_hired
FROM employees";

$result = mysqli_query($connection, $sql);

$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = $row;
}

echo json_encode($employees);
