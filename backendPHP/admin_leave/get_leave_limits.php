<?php
header('Content-Type: application/json');

// Return leave limits configuration
// In the future, you can store this in a database table
$leaveLimits = [
    "Sick Leave" => ["maxDays" => 10, "description" => "Per year"],
    "Vacation Leave" => ["maxDays" => 15, "description" => "Per year"],
    "Emergency Leave" => ["maxDays" => 5, "description" => "Per year"],
    "Maternity Leave" => ["maxDays" => 105, "description" => "Per pregnancy"],
    "Paternity Leave" => ["maxDays" => 7, "description" => "Per child"]
];

echo json_encode($leaveLimits);
?>