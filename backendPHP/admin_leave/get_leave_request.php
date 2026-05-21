<?php
include "../../database/connection.php";
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Simple query first - get leave requests
    $sql = "SELECT 
        id,
        employee_number,
        type,
        start_date,
        end_date,
        days,
        emergency_contact,
        reason,
        status,
        date_created
    FROM leave_requests
    ORDER BY date_created DESC";

    $result = mysqli_query($connection, $sql);

    if (!$result) {
        throw new Exception(mysqli_error($connection));
    }

    $requests = [];
    $employeeIds = [];

    while ($row = mysqli_fetch_assoc($result)) {
        // Get employee details
        $empId = $row['employee_number'];
        $employeeIds[] = $empId;
        
        // Get employee info from employees table
        $empSql = "SELECT full_name, department FROM employees WHERE employee_number = '$empId' LIMIT 1";
        $empResult = mysqli_query($connection, $empSql);
        $empData = mysqli_fetch_assoc($empResult);
        
        $employeeName = $empData ? $empData['full_name'] : 'Unknown';
        $department = $empData ? $empData['department'] : 'N/A';
        
        // Generate avatar
        $nameParts = explode(' ', $employeeName);
        $avatar = '';
        if (count($nameParts) >= 2) {
            $avatar = strtoupper($nameParts[0][0] . $nameParts[1][0]);
        } else {
            $avatar = strtoupper(substr($employeeName, 0, 2));
        }
        
        $requests[] = [
            'id' => $row['id'],
            'employee' => [
                'id' => $empId,
                'name' => $employeeName,
                'department' => $department,
                'avatar' => $avatar
            ],
            'leaveType' => $row['type'],
            'dateRange' => $row['start_date'] . ' to ' . $row['end_date'],
            'startDate' => $row['start_date'],
            'endDate' => $row['end_date'],
            'days' => (int)$row['days'],
            'reason' => $row['reason'],
            'contactDuringLeave' => $row['emergency_contact'],
            'status' => $row['status'],
            'submittedDate' => $row['date_created'],
            'signature' => 'verified',
            'checkLimit' => true,
            'attachments' => [],
            'autoRejected' => ($row['status'] === 'auto_rejected')
        ];
    }

    // Calculate leave usage
    $leaveUsage = [];
    $limits = [
        'Sick Leave' => 10,
        'Vacation Leave' => 15,
        'Emergency Leave' => 5,
        'Maternity Leave' => 105,
        'Paternity Leave' => 7
    ];

    foreach (array_unique($employeeIds) as $empId) {
        $leaveUsage[$empId] = [];
        
        foreach ($limits as $leaveType => $limit) {
            // Count approved days for this employee and leave type this year
            $usageSql = "SELECT COALESCE(SUM(days), 0) as used_days 
                        FROM leave_requests 
                        WHERE employee_number = '$empId' 
                        AND type = '$leaveType' 
                        AND status = 'approved'
                        AND YEAR(date_created) = YEAR(CURDATE())";
            
            $usageResult = mysqli_query($connection, $usageSql);
            $usageData = mysqli_fetch_assoc($usageResult);
            $used = (int)$usageData['used_days'];
            
            $leaveUsage[$empId][$leaveType] = [
                'used' => $used,
                'remaining' => max(0, $limit - $used)
            ];
        }
    }

    mysqli_close($connection);

    echo json_encode([
        'requests' => $requests,
        'leaveUsage' => $leaveUsage
    ]);

} catch (Exception $e) {
    mysqli_close($connection);
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'requests' => [],
        'leaveUsage' => []
    ]);
}