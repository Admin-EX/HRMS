<?php
include("../database/connection.php");
session_start();
// Get filter parameters
$employeeType = isset($_GET['employee_type']) ? $_GET['employee_type'] : 'all';
$reviewStatus = isset($_GET['review_status']) ? $_GET['review_status'] : 'all';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$offset = ($page - 1) * $perPage;

// Build the query
$query = "SELECT 
            e.employee_number,
            e.full_name,
            e.employee_type,
            e.department,
            e.position,
            e.email,
            e.phone,
            COUNT(DISTINCT ed.id) as total_docs,
            SUM(CASE WHEN ed.status IN ('approved', 'pending', 'rejected', 'for_revision', 'missing') THEN 1 ELSE 0 END) as submitted_docs,
            SUM(CASE WHEN ed.status = 'pending' THEN 1 ELSE 0 END) as pending_docs,
            SUM(CASE WHEN ed.status = 'approved' THEN 1 ELSE 0 END) as approved_docs,
            SUM(CASE WHEN ed.status = 'rejected' THEN 1 ELSE 0 END) as rejected_docs,
            SUM(CASE WHEN ed.status = 'for_revision' THEN 1 ELSE 0 END) as revision_docs,
            SUM(CASE WHEN ed.status = 'missing' THEN 1 ELSE 0 END) as missing_docs,
            CASE 
                WHEN SUM(CASE WHEN ed.status = 'pending' THEN 1 ELSE 0 END) > 0 THEN 'pending'
                WHEN SUM(CASE WHEN ed.status = 'for_revision' THEN 1 ELSE 0 END) > 0 THEN 'for_revision'
                WHEN SUM(CASE WHEN ed.status = 'rejected' THEN 1 ELSE 0 END) > 0 THEN 'rejected'
                ELSE 'approved'
            END as overall_status
          FROM employees e
          LEFT JOIN employee_documents ed ON e.employee_number = ed.employee_number
          WHERE 1=1";

// Add filters
if ($employeeType !== 'all') {
    if ($employeeType === 'tp') {
        $query .= " AND e.employee_type = 'Teaching Personnel'";
    } elseif ($employeeType === 'ntp') {
        $query .= " AND e.employee_type = 'Non-Teaching Personnel'";
    }
}

if (!empty($searchTerm)) {
    $searchTerm = $connection->real_escape_string($searchTerm);
    $query .= " AND (e.full_name LIKE '%$searchTerm%' 
                OR e.employee_number LIKE '%$searchTerm%' 
                OR e.department LIKE '%$searchTerm%')";
}

$query .= " GROUP BY e.employee_number, e.full_name, e.employee_type, e.department, e.position, e.email, e.phone";

// Add status filter after grouping
if ($reviewStatus !== 'all') {
    $query .= " HAVING overall_status = '$reviewStatus'";
}

// Count total records
$countQuery = "SELECT COUNT(*) as total FROM ($query) as counted";
$countResult = $connection->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];

// Add pagination
$query .= " ORDER BY e.employee_number LIMIT $offset, $perPage";

// Execute query
$result = $connection->query($query);

$employees = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Determine required docs count (typically 5)
        $requiredDocs = 5;
        
        $employees[] = [
            'employee_number' => $row['employee_number'],
            'full_name' => $row['full_name'],
            'employee_type' => $row['employee_type'],
            'department' => $row['department'] ?? 'N/A',
            'position' => $row['position'] ?? 'N/A',
            'email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? '',
            'documents' => $row['submitted_docs'] . '/' . $requiredDocs,
            'total_docs' => (int)$row['total_docs'],
            'submitted_docs' => (int)$row['submitted_docs'],
            'pending_docs' => (int)$row['pending_docs'],
            'approved_docs' => (int)$row['approved_docs'],
            'rejected_docs' => (int)$row['rejected_docs'],
            'revision_docs' => (int)$row['revision_docs'],
            'missing_docs' => (int)$row['missing_docs'],
            'review_status' => $row['overall_status']
        ];
    }
}

// Get statistics
$statsQuery = "SELECT 
                COUNT(DISTINCT e.employee_number) as total_employees,
                SUM(CASE WHEN ed.status = 'pending' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN ed.status = 'approved' THEN 1 ELSE 0 END) as approved_docs,
                SUM(CASE WHEN ed.status = 'rejected' THEN 1 ELSE 0 END) as rejected_docs,
                SUM(CASE WHEN ed.status = 'for_revision' THEN 1 ELSE 0 END) as for_revision
               FROM employees e
               LEFT JOIN employee_documents ed ON e.employee_number = ed.employee_number";

$statsResult = $connection->query($statsQuery);
$stats = $statsResult->fetch_assoc();

$response = [
    'success' => true,
    'data' => $employees,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $perPage,
        'total_records' => $totalRecords,
        'total_pages' => ceil($totalRecords / $perPage),
        'from' => $offset + 1,
        'to' => min($offset + $perPage, $totalRecords)
    ],
    'statistics' => [
        'total_employees' => (int)$stats['total_employees'],
        'pending_approval' => (int)$stats['pending_approval'],
        'approved_docs' => (int)$stats['approved_docs'],
        'rejected_docs' => (int)$stats['rejected_docs'],
        'for_revision' => (int)$stats['for_revision']
    ]
];

header('Content-Type: application/json');
echo json_encode($response);

$connection->close();
?>