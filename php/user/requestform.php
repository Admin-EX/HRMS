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
    <title>Request Form</title>
    <link rel="stylesheet" href="../../css/user/requestform.css">
    <style>
        /* Additional CSS for PHP-generated content */
        .readonly {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        .system-info {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
            font-style: italic;
        }
        
        .profile {
            text-align: right;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .back-btn {
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .back-btn:hover {
            color: #2980b9;
        }
        
        footer {
            text-align: center;
            padding: 20px;
            background: #2c3e50;
            color: white;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    
  <header>
    <div class="left">
      <a href="./activity.php" class="back-btn">← BACK</a>
    </div>
    <div class="right">
      <div class="profile">
        <h3 id="userName"><?php echo $employee_name; ?></h3>
        <p id="userType"><?php echo $employee_type; ?> Staff</p>
      </div>
      <div class="avatar" id="userAvatar"><?php echo $avatar_initials; ?></div>
    </div>
  </header>

  <div class="form-container">
    <div class="form-header">
      <img src="logo 2.png" alt="Logo">
      <div>
        <h1>REQUEST FORM</h1>
        <p>Note: The request document will be released within 3 working days.</p>
      </div>
    </div>

    <div class="section-title">INFORMATION</div>
    <table>
      <tr>
        <td colspan="3">
          <label>Employee/Faculty Name:</label>
          <input type="text" id="employeeName" class="readonly" readonly 
                 value="<?php echo $employee_name; ?>">
          <div class="system-info">Auto-filled from your profile</div>
        </td>
        <td>
          <label>Employee ID:</label>
          <input type="text" id="employeeId" class="readonly" readonly 
                 value="<?php echo $employee_id_display; ?>">
          <div class="system-info">Auto-filled from your profile</div>
        </td>
        <td>
          <label>Date Filed:</label>
          <input type="text" id="dateFiled" class="readonly" readonly 
                 value="<?php echo $current_datetime; ?>">
          <div class="system-info">System-generated</div>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <label>Position/Designation:</label>
          <input type="text" id="employeePosition" class="readonly" readonly 
                 value="<?php echo $position; ?>">
          <div class="system-info">Auto-filled from your profile</div>
        </td>
        <td colspan="2">
          <label>College/Office:</label>
   <input type="text" id="employeeDepartment" class="readonly" readonly 
                 value="<?php echo $department; ?>">
          <div class="system-info">Auto-filled from your profile</div>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <label>Cellphone Number:</label>
          <input type="text" id="contactNumber" placeholder="For updates" 
                 value="<?php echo $phone; ?>">
          <div class="system-info">Auto-filled from your profile</div>
        </td>
        <td colspan="2">
          <label>Date Hired:</label>
          <input type="text" id="dateHired" class="readonly" readonly 
                 value="<?php echo $date_hired; ?>">
          <div class="system-info">Auto-filled from your profile</div>
        </td>
      </tr>
    </table>

    <div class="section-title">REQUEST DETAILS</div>
    <table>
      <tr>
        <td colspan="5">
          <label>Request Type:</label>
          <select id="requestType">
            <option value="">-- Select Request Type --</option>
            <option value="coe">Certificate of Employment (COE)</option>
            <option value="service">Service Record</option>
            <option value="certification">Certification</option>
            <option value="clearance">Clearance</option>
            <option value="other">Other Documents</option>
          </select>
        </td>
      </tr>
      <tr>
        <td colspan="5">
          <p style="font-weight:bold; font-style:italic; margin-top:10px;">REASON FOR REQUEST:</p>
          <div class="checkbox-group">
            <label><input type="checkbox" name="reason" value="employment"> Employment</label>
            <label><input type="checkbox" name="reason" value="licensing"> Licensing</label>
            <label><input type="checkbox" name="reason" value="credit-card"> Credit Card Application</label>
            <label><input type="checkbox" name="reason" value="visa"> Visa Application</label>
            <label><input type="checkbox" name="reason" value="loan"> Loan</label>
            <label><input type="checkbox" name="reason" value="others"> Others</label>
          </div>
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <label>Prepared by:</label>
          <input type="text" id="preparedBy" class="readonly" readonly 
                 value="<?php echo $employee_name; ?>">
          <div class="system-info">Auto-filled - your name</div>
        </td>
        <td colspan="2">
          <label>Submit Date:</label>
          <input type="text" id="submitDate" class="readonly" readonly 
                 value="<?php echo $submit_date; ?>">
          <div class="system-info">System-generated</div>
        </td>
      </tr>
    </table>

    <button class="submit-btn" onclick="submitRequestForm()">SUBMIT REQUEST</button>
  </div>

  <footer>
    © <?php echo date('Y'); ?> Dalubhasaang Politekniko ng Lungsod ng Baliwag. All Rights Reserved.
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
  <script src="../../js/user/requestForm.js"></script>
</body>
</html>