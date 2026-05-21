<?php
/**
 * Fetch Employee Documents
 * This file retrieves all documents for a specific employee
 */

include("../database/connection.php");
session_start();

// Set JSON header
header('Content-Type: application/json');

// Check if admin is logged in (commented out for testing)
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
//     exit();
// }

// Define base URL for file access
// Adjust this path based on your server configuration
$base_url = 'https://hrms.fun/';
// $base_url = 'http://localhost/hrms/';

try {
    // Get employee number from request
    $employee_number = $_GET['employee_number'] ?? null;
    
    if (!$employee_number) {
        echo json_encode(['success' => false, 'message' => 'Employee number is required']);
        exit();
    }
    
    // Sanitize input
    $employee_number = $connection->real_escape_string($employee_number);
    
    // Fetch employee information
    $employee_query = "SELECT 
                        e.employee_number,
                        full_name,
                        e.employee_type,
                        e.department,
                        COUNT(ed.id) as total_documents
                      FROM employees e
                      LEFT JOIN employee_documents ed ON e.employee_number = ed.employee_number
                      WHERE e.employee_number = '$employee_number'
                      GROUP BY e.employee_number";
    
    $employee_result = $connection->query($employee_query);
    
    if (!$employee_result || $employee_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit();
    }
    
    $employee = $employee_result->fetch_assoc();
    
    // Fetch all documents for this employee
    $documents_query = "SELECT 
                            id,
                            employee_number,
                            document_type,
                            document_name,
                            file_path,
                            file_size,
                            file_type,
                            status,
                            upload_date,
                            expiry_date,
                            reviewed_by,
                            review_date,
                            notes,
                            created_at,
                            updated_at
                        FROM employee_documents
                        WHERE employee_number = '$employee_number'
                        ORDER BY 
                            CASE status
                                WHEN 'pending' THEN 1
                                WHEN 'for_revision' THEN 2
                                WHEN 'approved' THEN 3
                                WHEN 'rejected' THEN 4
                                ELSE 5
                            END,
                            upload_date DESC";
    
    $documents_result = $connection->query($documents_query);
    
    $documents = [];
    
    if ($documents_result && $documents_result->num_rows > 0) {
        while ($row = $documents_result->fetch_assoc()) {
            // Build the full file path for browser access
            $file_path = '';
            $file_exists = false;
            
            if (!empty($row['file_path'])) {
                // Add base URL to make it accessible from browser
                $file_path = $base_url . $row['file_path'];
                
                // Check if file actually exists on server
                $server_path = $_SERVER['DOCUMENT_ROOT'] . $base_url . $row['file_path'];
                $file_exists = file_exists($server_path);
            }
            
            // Format the data
            $documents[] = [
                'id' => $row['id'],
                'employee_number' => $row['employee_number'],
                'document_type' => $row['document_type'] ?? 'Unknown',
                'document_name' => $row['document_name'] ?? 'Unnamed Document',
                'file_path' => $file_path,
                'file_exists' => $file_exists, // Add this to help with debugging
                'file_size' => (int)$row['file_size'],
                'file_type' => $row['file_type'] ?? 'application/octet-stream',
                'status' => $row['status'] ?? 'pending',
                'upload_date' => $row['upload_date'],
                'expiry_date' => $row['expiry_date'],
                'reviewed_by' => $row['reviewed_by'],
                'review_date' => $row['review_date'],
                'notes' => $row['notes'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
    }
    
    // Get document statistics for this employee
    $stats_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                        SUM(CASE WHEN status = 'for_revision' THEN 1 ELSE 0 END) as for_revision
                    FROM employee_documents
                    WHERE employee_number = '$employee_number'";
    
    $stats_result = $connection->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    
    // Prepare response
    $response = [
        'success' => true,
        'employee' => $employee,
        'documents' => $documents,
        'statistics' => [
            'total' => (int)$stats['total'],
            'pending' => (int)$stats['pending'],
            'approved' => (int)$stats['approved'],
            'rejected' => (int)$stats['rejected'],
            'for_revision' => (int)$stats['for_revision']
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($connection)) {
        $connection->close();
    }
}
?>