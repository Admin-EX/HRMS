<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../database/connection.php");

header('Content-Type: application/json');

// Log function for debugging
function logError($message, $data = null)
{
    $logFile = 'calendar_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    $logMessage .= "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    // Check authentication
    if (!isset($_SESSION['loggedUser'])) {
        logError('Authentication failed - No logged user in session');
        echo json_encode([
            'success' => false,
            'message' => 'Not authenticated',
            'error_code' => 'AUTH_FAILED'
        ]);
        exit();
    }

    $employee_id = $_SESSION['loggedUser'];
    $action = $_POST['action'] ?? '';

    logError("Request received", [
        'action' => $action,
        'employee_id' => $employee_id,
        'post_data' => $_POST
    ]);

    // Validate action
    if (empty($action)) {
        logError('No action specified');
        echo json_encode([
            'success' => false,
            'message' => 'No action specified',
            'error_code' => 'NO_ACTION'
        ]);
        exit();
    }

    // Get employee number
    $emp_query = "SELECT employee_number FROM employees WHERE employee_number = ?";
    $stmt = $connection->prepare($emp_query);

    if (!$stmt) {
        logError('Database prepare failed for employee query', [
            'error' => $connection->error
        ]);
        throw new Exception('Database error: ' . $connection->error);
    }

    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee_data = $result->fetch_assoc();
    $employee_number = $employee_data['employee_number'] ?? '';
    $stmt->close();

    if (empty($employee_number)) {
        logError('Employee not found', ['employee_id' => $employee_id]);
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found',
            'error_code' => 'EMPLOYEE_NOT_FOUND'
        ]);
        exit();
    }

    logError("Employee found", ['employee_number' => $employee_number]);

    switch ($action) {
        case 'create':
            $title = $_POST['title'] ?? '';
            $start_datetime = $_POST['start_datetime'] ?? '';
            $category = $_POST['category'] ?? 'others';
            $color = $_POST['color'] ?? '#6c757d';
            $details = $_POST['details'] ?? '';
            $time_display = $_POST['time_display'] ?? '';
            $is_imported = isset($_POST['is_imported']) ? (int) $_POST['is_imported'] : 0;

            // Validate required fields
            if (empty($title)) {
                logError('Validation failed - Title is required');
                echo json_encode([
                    'success' => false,
                    'message' => 'Title is required',
                    'error_code' => 'VALIDATION_FAILED'
                ]);
                exit();
            }

            if (empty($start_datetime)) {
                logError('Validation failed - Start datetime is required');
                echo json_encode([
                    'success' => false,
                    'message' => 'Start date/time is required',
                    'error_code' => 'VALIDATION_FAILED'
                ]);
                exit();
            }

            logError("Creating event", [
                'title' => $title,
                'start_datetime' => $start_datetime,
                'category' => $category,
                'employee_number' => $employee_number
            ]);

            $query = "INSERT INTO calendar_events (employee_id, title, start_datetime, category, color, details, time_display, is_imported, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $connection->prepare($query);

            if (!$stmt) {
                logError('Database prepare failed for INSERT', [
                    'error' => $connection->error,
                    'query' => $query
                ]);
                throw new Exception('Database error: ' . $connection->error);
            }

            $stmt->bind_param("sssssssi", $employee_number, $title, $start_datetime, $category, $color, $details, $time_display, $is_imported);

            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                logError("Event created successfully", [
                    'new_id' => $new_id,
                    'title' => $title
                ]);
                echo json_encode([
                    'success' => true,
                    'message' => 'Event created successfully',
                    'id' => $new_id,
                    'employee_id' => $employee_number
                ]);
$activitysql = "INSERT INTO `activity_log`(`employee_number`, `title`, `content`, `url`, `read_status`) 
                VALUES ('$employee_number', 'Calendar', 'You have successfully added calendar, $title', '/calendar.php', 'unread')";
$query = mysqli_query($connection, $activitysql);
            } else {
                logError('Event creation failed', [
                    'error' => $stmt->error,
                    'errno' => $stmt->errno
                ]);
                throw new Exception('Failed to create event: ' . $stmt->error);
            }
            $stmt->close();
            break;

        case 'update':
            $id = $_POST['id'] ?? '';
            $title = $_POST['title'] ?? '';
            $start_datetime = $_POST['start_datetime'] ?? '';
            $category = $_POST['category'] ?? 'others';
            $color = $_POST['color'] ?? '#6c757d';
            $details = $_POST['details'] ?? '';
            $time_display = $_POST['time_display'] ?? '';

            // Validate required fields
            if (empty($id)) {
                logError('Validation failed - Event ID is required');
                echo json_encode([
                    'success' => false,
                    'message' => 'Event ID is required',
                    'error_code' => 'VALIDATION_FAILED'
                ]);
                exit();
            }

            if (empty($title)) {
                logError('Validation failed - Title is required');
                echo json_encode([
                    'success' => false,
                    'message' => 'Title is required',
                    'error_code' => 'VALIDATION_FAILED'
                ]);
                exit();
            }

            logError("Updating event", [
                'id' => $id,
                'title' => $title,
                'start_datetime' => $start_datetime,
                'employee_number' => $employee_number
            ]);

            $query = "UPDATE calendar_events 
                      SET title = ?, start_datetime = ?, category = ?, color = ?, details = ?, time_display = ?, updated_at = NOW() 
                      WHERE id = ? AND employee_id = ?";
            $stmt = $connection->prepare($query);


            if (!$stmt) {
                logError('Database prepare failed for UPDATE', [
                    'error' => $connection->error,
                    'query' => $query
                ]);
                throw new Exception('Database error: ' . $connection->error);
            }

            $stmt->bind_param("ssssssss", $title, $start_datetime, $category, $color, $details, $time_display, $id, $employee_number);

            if ($stmt->execute()) {
                $affected_rows = $stmt->affected_rows;
                logError("Event updated", [
                    'id' => $id,
                    'affected_rows' => $affected_rows
                ]);

                if ($affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Event updated successfully'
                    ]);
                } else {
                    logError('No rows affected - Event may not exist or no changes made', ['id' => $id]);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Event not found or no changes made',
                        'error_code' => 'NO_ROWS_AFFECTED'
                    ]);
                }
            } else {
                logError('Event update failed', [
                    'error' => $stmt->error,
                    'errno' => $stmt->errno
                ]);
                throw new Exception('Failed to update event: ' . $stmt->error);
            }
            $stmt->close();
            break;

        case 'delete':
            $id = $_POST['id'] ?? '';

            if (empty($id)) {
                logError('Validation failed - Event ID is required for deletion');
                echo json_encode([
                    'success' => false,
                    'message' => 'Event ID is required',
                    'error_code' => 'VALIDATION_FAILED'
                ]);
                exit();
            }

            logError("Deleting event", [
                'id' => $id,
                'employee_number' => $employee_number
            ]);

            $query = "DELETE FROM calendar_events WHERE id = ? AND employee_id = ?";
            $stmt = $connection->prepare($query);

            if (!$stmt) {
                logError('Database prepare failed for DELETE', [
                    'error' => $connection->error,
                    'query' => $query
                ]);
                throw new Exception('Database error: ' . $connection->error);
            }

            $stmt->bind_param("ss", $id, $employee_number);

            if ($stmt->execute()) {
                $affected_rows = $stmt->affected_rows;
                logError("Event deleted", [
                    'id' => $id,
                    'affected_rows' => $affected_rows
                ]);

                if ($affected_rows > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Event deleted successfully'
                    ]);
                } else {
                    logError('No rows affected - Event may not exist', ['id' => $id]);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Event not found',
                        'error_code' => 'EVENT_NOT_FOUND'
                    ]);
                }
            } else {
                logError('Event deletion failed', [
                    'error' => $stmt->error,
                    'errno' => $stmt->errno
                ]);
                throw new Exception('Failed to delete event: ' . $stmt->error);
            }
            $stmt->close();
            break;

        default:
            logError('Invalid action provided', ['action' => $action]);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action: ' . $action,
                'error_code' => 'INVALID_ACTION'
            ]);
            break;
    }

} catch (Exception $e) {
    logError('Exception caught', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage(),
        'error_code' => 'EXCEPTION',
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} finally {
    if (isset($connection)) {
        $connection->close();
    }
}
?>