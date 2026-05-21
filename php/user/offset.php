<?php
session_start();
require_once '../../database/connection.php';

// Check if user is logged in
if (!isset($_SESSION['loggedUser'])) {
    header('Location: login.php');
    exit();
}

// Get employee data from database
$employee_id = $_SESSION['loggedUser'];
$query = "SELECT 
    employee_number,
    full_name,
    employee_type,
    position,
    department,
    phone,
    date_hired,
    email,
    gender,
    address,
    status
FROM employees 
WHERE employee_number = '$employee_id'";

$result = mysqli_query($connection, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die('Employee data not found. Please contact HR.');
}

$employee = mysqli_fetch_assoc($result);

// Process employee data
$employee_name = htmlspecialchars($employee['full_name']);
$employee_id_display = htmlspecialchars($employee['employee_number']);
$employee_type = htmlspecialchars($employee['employee_type']);
$position = htmlspecialchars($employee['position']);
$department = htmlspecialchars($employee['department']);
$phone = htmlspecialchars($employee['phone']);
$date_hired = !empty($employee['date_hired']) ? date('F d, Y', strtotime($employee['date_hired'])) : 'Not specified';
$email = htmlspecialchars($employee['email']);

// Get avatar initials
$name_parts = explode(' ', $employee_name);
$avatar_initials = '';
if (count($name_parts) >= 2) {
    $avatar_initials = strtoupper(substr($name_parts[0], 0, 1) . substr(end($name_parts), 0, 1));
} else {
    $avatar_initials = strtoupper(substr($employee_name, 0, 2));
}

// Current date and time
$current_date = date('F d, Y');
$current_time = date('h:i A');
$current_datetime = $current_date . ' ' . $current_time;
$submit_date = $current_date;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offset Form - DPLB</title>
    <link rel="stylesheet" href="../../css/user/offset.css">
</head>
<body>
    
  <header>
    <div class="left">
      <a href="activity.php" class="back-btn">← BACK</a>
    </div>
    <div class="right">
      <div class="profile">
        <h3 id="userName"><?php echo $employee_name; ?></h3>
        <p id="userType"><?php echo $employee_type; ?></p>
      </div>
      <div class="avatar" id="userAvatar"><?php echo $avatar_initials; ?></div>
    </div>
  </header>

  <div class="form-container">
    <div class="form-header">
      <img src="logo 2.png" alt="Logo">
      <div>
        <h1>OFFSET FORM</h1>
        <p>Note: Offset forms are for Faculty members only. No offset compensation will be processed unless this form is completed and returned.</p>
      </div>
    </div>

    <!-- Faculty Access Check -->
    <div id="facultyCheck" class="faculty-indicator" style="display: none;">
      <strong>⚠️ ACCESS RESTRICTED</strong><br>
      <span id="accessMessage"></span>
    </div>

    <div class="section-title">EMPLOYEE INFORMATION</div>
    <table>
      <tr>
        <td colspan="3">
          <label>Employee Name:</label>
          <input type="text" id="employeeName" value="<?php echo $employee_name; ?>" class="readonly" readonly>
          <div class="system-info">Auto-filled from your profile</div>
        </td>
        <td>
          <label>Employee ID:</label>
          <input type="text" id="employeeId" value="<?php echo $employee_id_display; ?>" class="readonly" readonly>
          <div class="system-info">Auto-filled from your profile</div>
        </td>
        <td>
          <label>Date Filed:</label>
          <input type="text" id="dateFiled" value="<?php echo $current_date; ?>" class="readonly" readonly>
          <div class="system-info">System-generated</div>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <label>Position/Designation:</label>
          <input type="text" id="employeePosition" value="<?php echo $position; ?>" class="readonly" readonly>
          <div class="system-info">Auto-filled from your profile</div>
        </td>
        <td colspan="2">
          <label>Institute/Office:</label>
          <select id="employeeInstitute">
            <option value="">Select Institute/Office</option>
            <option value="college-eng">College of Engineering</option>
            <option value="college-edu">College of Education</option>
            <option value="college-cs">College of Computer Studies</option>
            <option value="college-ba">College of Business Administration</option>
            <option value="admin">Administration Office</option>
            <option value="finance">Finance Office</option>
          </select>
          <div class="system-info">Select from list - cannot type</div>
        </td>
      </tr>
    </table>

    <!-- Faculty-Specific Fields -->
    <div id="facultyFields">
      <div class="section-title">SUBJECT TO OFFSET (FACULTY ONLY)</div>
      <table>
        <tr>
          <td colspan="2">
            <label>Subject Code:</label>
            <input type="text" id="subjectCode" placeholder="e.g., MATH101, ENG201">
          </td>
          <td colspan="3">
            <label>Subject Description:</label>
            <input type="text" id="subjectDescription" placeholder="e.g., Calculus 1, Technical Writing">
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <label>Academic Term:</label>
            <select id="academicTerm">
              <option value="">Select Academic Term</option>
              <option value="1st-2024">1st Semester AY 2024-2025</option>
              <option value="2nd-2024">2nd Semester AY 2024-2025</option>
              <option value="summer-2025">Summer Term AY 2024-2025</option>
              <option value="1st-2025">1st Semester AY 2025-2026</option>
            </select>
          </td>
          <td colspan="2">
            <label>Schedule Section:</label>
            <input type="text" id="scheduleSection" placeholder="e.g., CS101-A, BSIT-3A">
          </td>
        </tr>
      </table>
    </div>

    <div class="section-title">OFFSET SCHEDULE</div>
    <table class="schedule-table">
      <tr>
        <th>Original Schedule (Date)</th>
        <th>Original Schedule (Time)</th>
        <th>Offset Schedule (Date)</th>
        <th>Offset Schedule (Time)</th>
      </tr>
      <tr>
        <td>
          <input type="date" id="originalDate" onchange="validateOffsetDates()">
        </td>
        <td>
          <input type="time" id="originalTime" onchange="validateOffsetDates()">
        </td>
        <td>
          <input type="date" id="offsetDate" onchange="validateOffsetDates()">
        </td>
        <td>
          <input type="time" id="offsetTime" onchange="validateOffsetDates()">
        </td>
      </tr>
    </table>

    <!-- Validation Message -->
    <div id="validationMessage" class="validation-message"></div>

    <div class="section-title">REASON FOR OFFSET</div>
    <table>
      <tr>
        <td colspan="5">
          <label>Detailed Reason:</label>
          <textarea id="offsetReason" rows="3" placeholder="Please provide detailed reason for the offset request..."></textarea>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <label>Prepared by:</label>
          <input type="text" id="preparedBy" value="<?php echo $employee_name; ?>" class="readonly" readonly>
          <div class="system-info">Auto-filled - your name</div>
        </td>
        <td colspan="2">
          <label>Submit Date:</label>
          <input type="text" id="submitDate" value="<?php echo $submit_date; ?>" class="readonly" readonly>
          <div class="system-info">System-generated</div>
        </td>
      </tr>
    </table>

    <button class="submit-btn" id="submitBtn" onclick="submitOffsetForm()" disabled>SUBMIT OFFSET FORM</button>
  </div>

  <footer>
    © 2025 Dalubhasaang Politekniko ng Lungsod ng Baliwag. All Rights Reserved.
  </footer>
  <script>
    const currentUser = {
        id: "<?php echo $employee_id_display; ?>",
        name: "<?php echo $employee_name; ?>",
        position: "<?php echo $position; ?>",
        department: "<?php echo $department; ?>",
        email: "<?php echo $email; ?>",
        contact: "<?php echo $phone; ?>",
        type: "<?php echo $employee_type; ?>",
        dateHired: "<?php echo $employee['date_hired']; ?>"
    };
  </script>
  <script src="../../js/user/offset.js"></script>
</body>
</html>