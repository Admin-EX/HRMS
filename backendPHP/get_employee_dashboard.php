<?php
include "../database/connection.php";
header('Content-Type: application/json');

// ── TIMESTAMPDIFF calculates real years from date_hired to today ──────────────
$sql = "SELECT `id`, `employee_number`, `full_name`, `employee_type`,
               `department`, `position`, `credentials`, `gender`,
               `address`, `phone`, `email`, `status`,
               `educational_attainment`, `school`, `created_at`,
               `date_hired`, `type`, `leave_balance`,
               `employment_status`,
               TIMESTAMPDIFF(YEAR, `date_hired`, CURDATE()) AS years_service
        FROM `employees`
        WHERE `date_hired` IS NOT NULL AND `date_hired` != '0000-00-00'";

$result = mysqli_query($connection, $sql);

if (!$result) {
    echo json_encode(['error' => 'Query failed: ' . mysqli_error($connection)]);
    exit;
}

function buildInitials($fullName) {
    $parts    = explode(' ', trim($fullName));
    $initials = '';
    foreach ($parts as $p) {
        if ($p === '') continue;
        $initials .= strtoupper(substr($p, 0, 1));
        if (strlen($initials) >= 2) break;
    }
    return $initials;
}

$categories = [
    'permanent'           => [],
    'contractual'         => [],
    'cos'                 => [],
    'years30plus'         => [],
    'years20to29'         => [],
    'years10to19'         => [],
    'below10'             => [],
    'bachelorsNTP'        => [],
    'vocationalNTP'       => [],
    'highSchoolNTP'       => [],
    'supportStaffNTP'     => [],
    'graduateDegrees'     => [],
    'civilServiceEligible'=> [],
    'all'                 => [],
];

while ($row = mysqli_fetch_assoc($result)) {

    $empStatus = strtolower(trim($row['status'] ?? ''));
    $edu       = strtolower(trim($row['educational_attainment'] ?? ''));
    $empType   = strtolower(trim($row['employee_type'] ?? ''));
    $creds     = strtolower(trim($row['credentials'] ?? ''));
    $yrs       = (int)($row['years_service'] ?? 0);  // now from TIMESTAMPDIFF

    $emp = [
        'id'          => $row['employee_number'],
        'name'        => $row['full_name'],
        'initials'    => buildInitials($row['full_name']),
        'position'    => $row['position'],
        'department'  => $row['department'],
        'yearsService'=> $yrs,
        'dateHired'   => $row['date_hired'],
        'degree'      => $row['educational_attainment'] ?? '',
        'credentials' => $row['credentials'] ?? '',
        'school'      => $row['school'] ?? '',
        'status'      => $row['status'] ?? '',
        'leaveBalance'=> $row['leave_balance'] ?? 0,
        'email'       => $row['email'] ?? '',
        'phone'       => $row['phone'] ?? '',
    ];

    $categories['all'][] = $emp;

    // ── Employment Status (from `status` column) ───────────────────────────
    switch ($empStatus) {
        case 'permanent':
            $categories['permanent'][] = $emp;
            break;
        case 'contractual':
        case 'contract':
            $categories['contractual'][] = $emp;
            break;
        case 'cos':
        case 'contract of service':
            $categories['cos'][] = $emp;
            break;
    }

    // ── Years of Service (now accurate from date_hired) ────────────────────
    if ($yrs >= 30) {
        $categories['years30plus'][] = $emp;
    } elseif ($yrs >= 20) {
        $categories['years20to29'][] = $emp;
    } elseif ($yrs >= 10) {
        $categories['years10to19'][] = $emp;
    } else {
        $categories['below10'][] = $emp;
    }

    // ── Non-Teaching Personnel by education ───────────────────────────────
    if ($empType === 'ntp') {
        switch (true) {
            case strpos($edu, 'bachelor') !== false:
                $categories['bachelorsNTP'][] = $emp;
                break;
            case strpos($edu, 'vocational') !== false:
            case strpos($edu, 'technical') !== false:
                $categories['vocationalNTP'][] = $emp;
                break;
            case strpos($edu, 'high school') !== false:
            case strpos($edu, 'senior high') !== false:
                $categories['highSchoolNTP'][] = $emp;
                break;
            default:
                $categories['supportStaffNTP'][] = $emp;
                break;
        }
    }

    // ── Graduate Degrees (completed only, exclude "taking ...") ───────────
    if (strpos($edu, 'taking') === false) {
        if (
            strpos($edu, 'master')    !== false ||
            strpos($edu, 'doctorate') !== false ||
            strpos($edu, 'phd')       !== false
        ) {
            $categories['graduateDegrees'][] = $emp;
        }
    }

    // ── Civil Service Eligible ─────────────────────────────────────────────
    if (
        strpos($creds, 'civil service') !== false ||
        strpos($creds, 'cse')           !== false ||
        strpos($creds, 'prc')           !== false
    ) {
        $categories['civilServiceEligible'][] = $emp;
    }
}

$counts = [];
foreach ($categories as $key => $list) {
    $counts[$key] = count($list);
}

mysqli_free_result($result);

echo json_encode([
    'success'    => true,
    'categories' => $categories,
    'counts'     => $counts,
]);
?>