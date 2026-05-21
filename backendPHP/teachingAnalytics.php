<?php
include "../database/connection.php";
header('Content-Type: application/json');

$analytics = [
    "doctorate" => 0,
    "takingDoctorate" => 0,
    "masters" => 0,
    "takingMasters" => 0,
    "bachelor" => 0,
    "total" => 0,
    "schools" => []
];

// Get educational attainment counts
$sql = "
    SELECT educational_attainment, COUNT(*) AS total_count
    FROM employees
    GROUP BY educational_attainment
";

$result = mysqli_query($connection, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $attainment = strtolower(trim($row['educational_attainment']));
    
    switch ($attainment) {
        case 'doctorate':
            $analytics['doctorate'] = (int)$row['total_count'];
            break;
        case 'taking doctorate':
        case 'takingdoctorate':
            $analytics['takingDoctorate'] = (int)$row['total_count'];
            break;
        case 'master':
        case 'masters':
            $analytics['masters'] = (int)$row['total_count'];
            break;
        case 'taking masters':
        case 'takingmasters':
            $analytics['takingMasters'] = (int)$row['total_count'];
            break;
        case 'bachelor':
        case 'bachelors':
            $analytics['bachelor'] = (int)$row['total_count'];
            break;
    }
    
    $analytics['total'] += (int)$row['total_count'];
}

// Get school breakdown by educational attainment
$schoolSql = "
    SELECT 
        educational_attainment,
        school,
        COUNT(*) AS count
    FROM employees
    WHERE school IS NOT NULL AND school != ''
    GROUP BY educational_attainment, school
    ORDER BY educational_attainment, count DESC
";

$schoolResult = mysqli_query($connection, $schoolSql);

while ($row = mysqli_fetch_assoc($schoolResult)) {
    $attainment = strtolower(trim($row['educational_attainment']));
    $school = trim($row['school']);
    $count = (int)$row['count'];
    
    // Normalize keys to match JavaScript expectations
    $keyMap = [
        'doctorate' => 'Doctorate',
        'taking doctorate' => 'takingDoctorate',
        'takingdoctorate' => 'takingDoctorate',
        'masters' => 'Masters',
        'master' => 'Masters',
        'taking masters' => 'takingMasters',
        'takingmasters' => 'takingMasters',
        'bachelor' => 'Bachelors',
        'bachelors' => 'Bachelors'
    ];
    
    $key = isset($keyMap[$attainment]) ? $keyMap[$attainment] : $attainment;
    
    if (!isset($analytics['schools'][$key])) {
        $analytics['schools'][$key] = [];
    }
    
    $analytics['schools'][$key][] = [
        'name' => $school,
        'count' => $count
    ];
}

echo json_encode($analytics);
?>