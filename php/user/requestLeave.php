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
$gender = htmlspecialchars($employee['gender']);
$address = htmlspecialchars($employee['address']);
$status = htmlspecialchars($employee['status']);

// Get avatar initials (unchanged)
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

// Get leave balance from database (add this query)
$leave_query = "SELECT leave_balance FROM employees WHERE employee_number = '$employee_id'";
$leave_result = mysqli_query($connection, $leave_query);
$leave_balance = 15; // default fallback
if ($leave_result && mysqli_num_rows($leave_result) > 0) {
    $leave_data = mysqli_fetch_assoc($leave_result);
    $leave_balance = intval($leave_data['leave_balance']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Request Form</title>
    <link rel="stylesheet" href="../../css/user/requestLeave.css">
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
        <p style="font-size: 11px; opacity: 0.8;">Leave Balance: <span id="leaveBalanceDisplay"><?php echo $leave_balance; ?> days</span></p>
      </div>
      <div class="avatar" id="userAvatar"><?php echo $avatar_initials; ?></div>
    </div>
  </header>

  <div class="form-container">
    <div class="form-header">
      <img src="logo 2.png" alt="Logo">
      <div>
        <h1>LEAVE REQUEST FORM</h1>
        <p>Note: Leave requests must be submitted at least five (5) working days before the intended leave date.</p>
      </div>
    </div>

    <!-- Leave Balance Info -->
    <div class="leave-balance">
      Your current leave balance: <strong id="currentLeaveBalance"><?php echo $leave_balance; ?></strong> days
      <div style="font-size: 11px; margin-top: 5px;">
        Remaining after this request: <strong id="remainingBalance"><?php echo $leave_balance; ?></strong> days
      </div>
    </div>

    <div class="section-title">EMPLOYEE INFORMATION</div>
    <form id="leaveRequestForm">
      <table>
        <tr>
          <td colspan="3">
            <label>Employee Name:</label>
            <input type="text" id="employeeName" class="readonly" readonly value="<?php echo $employee_name; ?>">
            <div class="system-info">Auto-filled from your profile</div>
          </td>
          <td>
            <label>Employee ID:</label>
            <input type="text" id="employeeId" class="readonly" readonly value="<?php echo $employee_id_display; ?>">
            <div class="system-info">Auto-filled from your profile</div>
          </td>
          <td>
            <label>Date Filed:</label>
            <input type="text" id="dateFiled" class="readonly" readonly value="<?php echo $current_date; ?>">
            <div class="system-info">System-generated</div>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <label>Position/Designation:</label>
            <input type="text" id="employeePosition" class="readonly" readonly value="<?php echo $position; ?>">
            <div class="system-info">Auto-filled from your profile</div>
          </td>
          <td colspan="2">
            <label>Department/College:</label>
            <input type="text" id="employeeDepartment" class="readonly" readonly value="<?php echo $department; ?>">
            <div class="system-info">Auto-filled from your profile</div>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <label>Contact Number:</label>
            <input type="text" id="contactNumber" placeholder="For emergency contact" value="<?php echo $phone; ?>">
          </td>
          <td colspan="2">
            <label>Date Hired:</label>
            <input type="text" id="dateHired" class="readonly" readonly value="<?php echo $date_hired; ?>">
            <div class="system-info">Auto-filled from your profile</div>
          </td>
        </tr>
      </table>

      <div class="section-title">LEAVE DETAILS</div>
      <table>
        <tr>
          <td colspan="5">
            <label>Type of Leave:</label>
            <div class="radio-group" id="leaveTypeGroup">
              <label><input type="radio" name="leaveType" value="vacation" required> Vacation Leave</label>
              <label><input type="radio" name="leaveType" value="sick"> Sick Leave</label>
              <label><input type="radio" name="leaveType" value="maternity"> Maternity Leave</label>
              <label><input type="radio" name="leaveType" value="paternity"> Paternity Leave</label>
              <label><input type="radio" name="leaveType" value="bereavement"> Bereavement Leave</label>
              <label><input type="radio" name="leaveType" value="other"> Other</label>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <label>Leave Start Date:</label>
            <input type="date" id="leaveStartDate" onchange="calculateLeaveDays()" required>
            <div class="system-info">Must be at least 5 working days from today</div>
          </td>
          <td colspan="3">
            <label>Leave End Date:</label>
            <input type="date" id="leaveEndDate" onchange="calculateLeaveDays()" required>
            <div class="system-info">Must be after start date</div>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <label>Number of Days:</label>
            <input type="text" id="numberOfDays" class="readonly" readonly value="0">
            <div class="system-info">Calculated automatically</div>
          </td>
          <td colspan="3">
            <label>Emergency Contact During Leave:</label>
            <input type="text" id="emergencyContact" placeholder="Name and contact number" required>
          </td>
        </tr>
        <tr>
          <td colspan="5">
            <label>Reason for Leave:</label>
            <textarea id="leaveReason" rows="3" placeholder="Please provide detailed reason for leave..." required></textarea>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <label>Prepared by:</label>
            <input type="text" id="preparedBy" class="readonly" readonly value="<?php echo $employee_name; ?>">
            <div class="system-info">Auto-filled - your name</div>
          </td>
          <td colspan="2">
            <label>Submit Date:</label>
            <input type="text" id="submitDate" class="readonly" readonly value="<?php echo $submit_date; ?>">
            <div class="system-info">System-generated</div>
          </td>
        </tr>
      </table>

      <!-- Hidden input for employee ID -->
      <input type="hidden" id="hiddenEmployeeId" value="<?php echo $employee_id_display; ?>">

      <!-- Validation Message -->
      <div id="validationMessage" style="display: none; background-color: #ffebee; border: 1px solid #ffcdd2; color: #c62828; padding: 10px; border-radius: 5px; margin: 15px 0; font-size: 14px;"></div>

      <button type="button" class="submit-btn" id="submitBtn" onclick="submitLeaveRequest()" disabled>SUBMIT LEAVE REQUEST</button>
    </form>
  </div>

  <footer>
    © 2025 Dalubhasaang Politekniko ng Lungsod ng Baliwag. All Rights Reserved.
  </footer>  
</body>
<script src="../../js/user/requestLeave.js"></script>
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
// Initialize form with PHP values
document.addEventListener('DOMContentLoaded', function() {
    // Set min date for leave start (5 working days from now)
    const today = new Date();
    let minDate = new Date(today);
    let workDays = 0;
    
    while (workDays < 5) {
        minDate.setDate(minDate.getDate() + 1);
        // Skip weekends (0 = Sunday, 6 = Saturday)
        if (minDate.getDay() !== 0 && minDate.getDay() !== 6) {
            workDays++;
        }
    }
    
    const formattedMinDate = minDate.toISOString().split('T')[0];
    document.getElementById('leaveStartDate').min = formattedMinDate;
    document.getElementById('leaveStartDate').value = '';
    document.getElementById('leaveEndDate').min = formattedMinDate;
    document.getElementById('leaveEndDate').value = '';
    
    // Initialize leave balance
    window.currentLeaveBalance = <?php echo $leave_balance; ?>;
    document.getElementById('currentLeaveBalance').textContent = window.currentLeaveBalance;
    document.getElementById('remainingBalance').textContent = window.currentLeaveBalance;
});
</script>
</html>