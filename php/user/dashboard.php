<?php
include("../../database/connection.php");
session_start();
if (empty($_SESSION['loggedUser'])) {
    header("Location: ../../index.html");
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['loggedUser'])) {
  header("Location: ../../login.php");
  exit();
}

$employee_id = $_SESSION['loggedUser'];

// Initialize variables
$spend_days = 0;
$pending_count = 0;
$employee_data = null;

// Helper function to get initials
function getInitials($name)
{
  $words = explode(' ', trim($name));
  $initials = '';
  foreach ($words as $word) {
    if (!empty($word)) {
      $initials .= strtoupper(substr($word, 0, 1));
    }
  }
  return substr($initials, 0, 2);
}

// Helper function for time ago
function timeAgo($datetime)
{
  $date = new DateTime($datetime);
  $now = new DateTime();
  $diff = $now->diff($date);

  if ($diff->d == 0 && $diff->h < 24) {
    if ($diff->h == 0) {
      if ($diff->i == 0) {
        return 'Just now';
      }
      return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    }
    return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
  } elseif ($diff->d == 1) {
    return '1 day ago';
  } elseif ($diff->d < 7) {
    return $diff->d . ' days ago';
  } elseif ($diff->d < 30) {
    $weeks = floor($diff->d / 7);
    return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
  } else {
    return $date->format('M j, Y');
  }
}

// Fetch employee profile data
$employee_query = "SELECT `id`, `employee_number`, `full_name`, `employee_type`, `department`, 
                          `position`, `credentials`, `gender`, `address`, `phone`, `email`, 
                          `status`, `educational_attainment`, `school`, `created_at`, 
                          `date_hired`, `type`, `years_service`, `employment_status`, 
                          `leave_balance` 
                   FROM `employees` 
                   WHERE `employee_number` = ?";
$stmt_emp = $connection->prepare($employee_query);
$stmt_emp->bind_param("s", $employee_id);
$stmt_emp->execute();
$emp_result = $stmt_emp->get_result();

if ($emp_result->num_rows > 0) {
  $employee_data = $emp_result->fetch_assoc();

  // Extract name parts for display
  $full_name = $employee_data['full_name'] ?? 'User';
  $name_parts = explode(' ', $full_name);
  $first_name = $name_parts[0] ?? 'User';
  $initials = getInitials($full_name);

  $employee_email = $employee_data['email'] ?? 'Not set';
  $employee_phone = $employee_data['phone'] ?? 'Not set';
  $employee_type = $employee_data['employee_type'] ?? 'Not set';
  $employee_department = $employee_data['department'] ?? 'Not set';
  $employee_position = $employee_data['position'] ?? 'Not set';
  $educational_attainment = $employee_data['educational_attainment'] ?? 'Not set';
  $date_hired = $employee_data['date_hired'] ?? 'Not set';
  $employment_status = $employee_data['employment_status'] ?? 'Not set';

  // Format date hired if it exists
  if ($date_hired && $date_hired != 'Not set') {
    $date_hired_formatted = date('F j, Y', strtotime($date_hired));
  } else {
    $date_hired_formatted = 'Not set';
  }

} else {
  // Fallback values if no employee record found
  $full_name = "User";
  $first_name = "User";
  $initials = "US";
  $employee_email = "Not available";
  $employee_phone = "Not available";
  $employee_type = "Not available";
  $employee_department = "Not available";
  $employee_position = "Not available";
  $educational_attainment = "Not available";
  $date_hired_formatted = "Not available";
  $employment_status = "Not available";
}
$stmt_emp->close();

// Query for pending leave requests count
$pending_query = "SELECT COUNT(*) as pending_count FROM leave_requests 
                  WHERE employee_number = ? AND status = 'pending'";
$stmt_pending = $connection->prepare($pending_query);
$stmt_pending->bind_param("s", $employee_id);
$stmt_pending->execute();
$pending_result = $stmt_pending->get_result();
if ($pending_row = $pending_result->fetch_assoc()) {
  $pending_count = $pending_row['pending_count'];
}
$stmt_pending->close();

// Query for total leave days used (all approved leaves)
$days_query = "SELECT COALESCE(SUM(days), 0) as total_days FROM leave_requests 
               WHERE employee_number = ? AND LOWER(status) = 'approved'";
$stmt_days = $connection->prepare($days_query);
$stmt_days->bind_param("s", $employee_id);
$stmt_days->execute();
$days_result = $stmt_days->get_result();
if ($days_row = $days_result->fetch_assoc()) {
  $spend_days = $days_row['total_days'];
}
$stmt_days->close();

// Use leave_balance from employees table if available, otherwise default to 15
if ($employee_data && isset($employee_data['leave_balance']) && is_numeric($employee_data['leave_balance'])) {
  $total_leave_days = $employee_data['leave_balance'];
} else {
  $total_leave_days = 15; // Default fallback
}

$remaining_days = $total_leave_days - $spend_days;

// Make sure remaining days is not negative
if ($remaining_days < 0) {
  $remaining_days = 0;
}

// Count unread notifications
$unread_query = "SELECT COUNT(*) as unread_count FROM activity_log 
                 WHERE employee_number = ? AND read_status = 'unread'";
$stmt_unread = $connection->prepare($unread_query);
$stmt_unread->bind_param("s", $employee_id);
$stmt_unread->execute();
$unread_result = $stmt_unread->get_result();
$unread_row = $unread_result->fetch_assoc();
$unread_count = $unread_row['unread_count'] ?? 0;
$stmt_unread->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard</title>
  <link rel="stylesheet" href="../../css/user/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <div class="top-bar">
    <div class="top-left">
      <img src="logo 2.png" alt="Logo">
    </div>
    <div class="top-right">
      <div class="notif" id="notifBtn" data-count="<?php echo $unread_count; ?>">
        <i class="fas fa-bell"></i>
        <?php if ($unread_count > 0): ?>
          <span class="notif-badge"><?php echo $unread_count; ?></span>
        <?php endif; ?>
      </div>

      <div class="user-name"><?php echo htmlspecialchars($full_name); ?></div>

      <!-- Notification Dropdown -->
      <div class="notif-dropdown" id="notifDropdown">
        <?php
        $recent_notif_query = "SELECT id, title, content,url, date, read_status 
                               FROM activity_log 
                               WHERE employee_number = ? 
                               ORDER BY date DESC 
                               LIMIT 5";

        $stmt_recent = $connection->prepare($recent_notif_query);
        $stmt_recent->bind_param("s", $employee_id);
        $stmt_recent->execute();
        $recent_notif_result = $stmt_recent->get_result();

        if ($recent_notif_result->num_rows > 0) {
          while ($notif = $recent_notif_result->fetch_assoc()) {
            $notif_time = timeAgo($notif['date']);
            $is_unread = ($notif['read_status'] == 'unread');
            $unread_class = $is_unread ? 'unread' : '';

            $notif_icon = 'fas fa-bell';
            if (strpos($notif['title'], 'Document') !== false) {
              $notif_icon = 'fas fa-file-upload';
            } elseif (strpos($notif['title'], 'Calendar') !== false) {
              $notif_icon = 'fas fa-calendar-alt';
            } elseif (strpos($notif['title'], 'Leave') !== false) {
              $notif_icon = 'fas fa-plane-departure';
            }
            ?>
            <a href=".<?php echo $notif['url'] ?>">

              <div class="notif-item <?php echo $unread_class; ?>" data-notif-id="<?php echo $notif['id']; ?>">
                <i class="<?php echo $notif_icon; ?>"></i>
                <div class="notif-details-dropdown">
                  <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                  <p><?php echo htmlspecialchars(substr($notif['content'], 0, 60)) . '...'; ?></p>
                  <span class="notif-time"><?php echo $notif_time; ?></span>
                </div>
                <?php if ($is_unread): ?>
                  <span class="unread-dot"></span>
                <?php endif; ?>
              </div>
            </a>

            <?php
          }
        } else {
          ?>
          <div class="notif-item" id="defaultNotif">
            <i class="fas fa-check-circle"></i>
            <div class="notif-details-dropdown">
              <strong>No new notifications</strong>
              <p>You're all caught up!</p>
            </div>
          </div>
          <?php
        }
        $stmt_recent->close();
        ?>

        <div class="view-all" id="viewAllNotif">View All Notifications</div>
      </div>
    </div>
  </div>

  <!-- Notification Modal -->
  <div class="notif-modal" id="notifModal">
    <div class="notif-content">
      <h3>All Notifications</h3>

      <?php
      $all_notif_query = "SELECT id, title, content, date, read_status 
                               FROM activity_log 
                               WHERE employee_number = ? 
                               ORDER BY date DESC 
                               LIMIT 20";

      $stmt_all = $connection->prepare($all_notif_query);
      $stmt_all->bind_param("s", $employee_id);
      $stmt_all->execute();
      $all_notif_result = $stmt_all->get_result();

      if ($all_notif_result->num_rows > 0) {
        while ($notif = $all_notif_result->fetch_assoc()) {
          $timeAgo_display = timeAgo($notif['date']);

          $icon = 'fas fa-bell';
          if (strpos($notif['title'], 'Document') !== false) {
            $icon = 'fas fa-file-upload';
          } elseif (strpos($notif['title'], 'Calendar') !== false) {
            $icon = 'fas fa-calendar-alt';
          } elseif (strpos($notif['title'], 'Leave') !== false) {
            $icon = 'fas fa-plane-departure';
          }

          $unread_class = ($notif['read_status'] == 'unread') ? 'unread' : '';
          ?>

          <div class="notif-item <?php echo $unread_class; ?>" data-notif-id="<?php echo $notif['id']; ?>">
            <i class="<?php echo $icon; ?>"></i>
            <div class="notif-details-dropdown">
              <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
              <p><?php echo htmlspecialchars($notif['content']); ?></p>
              <span class="notif-time"><?php echo $timeAgo_display; ?></span>
            </div>
            <?php if ($notif['read_status'] == 'unread'): ?>
              <span class="unread-dot"></span>
            <?php endif; ?>
          </div>

          <?php
        }
      }
      $stmt_all->close();
      ?>

      <button class="close-modal" id="closeNotifModal">Close</button>
    </div>
  </div>

  <!-- Image Upload Modal -->
  <div class="image-modal" id="imageModal">
    <div class="image-modal-content">
      <h3>Upload Profile Picture</h3>
      <div class="image-preview" id="imagePreview">
        <span>No image selected</span>
        <img id="previewImg" alt="Preview" style="display:none;">
      </div>
      <input type="file" id="imageInput" accept="image/*" style="margin-bottom: 20px;">
      <div class="modal-buttons">
        <button class="save-btn" id="saveImageBtn">Save</button>
        <button class="cancel-btn" id="cancelImageBtn">Cancel</button>
      </div>
    </div>
  </div>

  <!-- OTP Verification Modal -->
  <div class="otp-modal" id="otpModal">
    <div class="otp-content">
      <h3><i class="fas fa-shield-alt"></i> OTP Verification</h3>
      <p style="text-align: center; color: #666; margin-bottom: 15px;">
        For security, please verify your identity to change password
      </p>

      <div class="otp-method-selector">
        <button class="otp-method-btn active" data-method="email">
          <i class="fas fa-envelope"></i> Send via Email
        </button>
        <button class="otp-method-btn" data-method="sms">
          <i class="fas fa-mobile-alt"></i> Send via SMS
        </button>
      </div>

      <div class="otp-destination" id="otpDestination">
        OTP will be sent to: <strong><?php echo htmlspecialchars($employee_email); ?></strong>
      </div>

      <div class="otp-input-container">
        <input type="text" maxlength="1" class="otp-input" data-index="1">
        <input type="text" maxlength="1" class="otp-input" data-index="2">
        <input type="text" maxlength="1" class="otp-input" data-index="3">
        <input type="text" maxlength="1" class="otp-input" data-index="4">
        <input type="text" maxlength="1" class="otp-input" data-index="5">
        <input type="text" maxlength="1" class="otp-input" data-index="6">
      </div>

      <div class="resend-otp">
        Didn't receive the code?
        <a href="#" id="resendOtpLink">Resend OTP</a>
        <span id="otpTimer" class="otp-timer"></span>
      </div>

      <div class="otp-actions">
        <button class="cancel-otp-btn" id="cancelOtpBtn">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button class="verify-otp-btn" id="verifyOtpBtn">
          <i class="fas fa-check"></i> Verify OTP
        </button>
      </div>
    </div>
  </div>

  <!-- Masters Program Status Modal -->
  <div class="masters-modal" id="mastersModal">
    <div class="masters-modal-content">
      <h3><i class="fas fa-graduation-cap"></i> Masters Program Status</h3>

      <div class="masters-form-group">
        <label>Masters Program Status:</label>
        <select id="mastersStatusSelect">
          <option value="not_applicable">Not Applicable</option>
          <option value="in_progress">In Progress</option>
          <option value="requirements_submitted">Requirements Submitted</option>
          <option value="completed">Completed</option>
        </select>
      </div>

      <div id="mastersProgramDetails" style="display:none;">
        <div class="masters-form-group">
          <label>Masters Program:</label>
          <input type="text" id="mastersProgramInput" placeholder="e.g., Master of Science in Computer Science">
        </div>

        <div class="masters-form-group">
          <label>Start Date:</label>
          <input type="date" id="mastersStartDate">
        </div>

        <div class="masters-form-group">
          <label>Expected Completion Date:</label>
          <input type="date" id="mastersEndDate">
        </div>

        <div class="masters-form-group">
          <label>Requirements Submitted Date:</label>
          <input type="date" id="requirementsSubmittedDate">
        </div>
      </div>

      <div class="notification-status" id="notificationStatusBox">
        <h4><i class="fas fa-bell"></i> Notification Status</h4>
        <p id="notificationStatusText">Masters program notifications are currently ACTIVE.</p>
      </div>

      <div class="masters-modal-buttons">
        <button class="cancel-btn" id="cancelMastersBtn">Cancel</button>
        <button class="save-btn" id="saveMastersBtn">Save Changes</button>
      </div>
    </div>
  </div>

  <div class="main-container">
    <div class="sidebar">
      <div>
        <div class="profile-card">
          <div class="profile-circle" id="sidebarProfileCircle">
            <img id="sidebarProfileImg" alt="Profile Picture" style="display:none;">
            <span><?php echo $initials; ?></span>
            <div class="upload-overlay">
              <div>Click to<br>change photo</div>
            </div>
          </div>
          <h3><?php echo htmlspecialchars($full_name); ?></h3>
          <p><?php echo htmlspecialchars($employee_position); ?></p>
          <div id="sidebarMastersBadge" class="masters-badge 
            <?php
            if (
              strpos(strtolower($educational_attainment), 'master') !== false ||
              strpos(strtolower($educational_attainment), 'pursuing') !== false
            ) {
              echo 'masters-in-progress';
            } else {
              echo 'masters-not-applicable';
            }
            ?>">
            <?php echo htmlspecialchars(ucfirst($educational_attainment)); ?>
          </div>
        </div>
        <ul class="menu">
          <li class="active"><a href="dashboard.php"
              style="text-decoration:none; color:inherit; display:block;">Dashboard</a></li>
          <li><a href="activity.php" style="text-decoration:none; color:inherit; display:block;">Activity</a></li>
          <li><a href="calendar.php" style="text-decoration:none; color:inherit; display:block;">Calendar</a></li>
          <li><a href="settings.php" style="text-decoration:none; color:inherit; display:block;">Settings</a></li>
          <li><a href="logout.php" style="text-decoration:none; color:inherit; display:block;">Logout</a>
          </li>
        </ul>
      </div>
      <div class="bottom-logo">
        <img src="logo 2.png" alt="Logo" />
      </div>
    </div>

    <div class="main-content">
      <header>
        <h2>Welcome Back, <?php echo htmlspecialchars($first_name); ?>! 👋</h2>
        <p>Here's what's happening to your account today.</p>
      </header>

      <!-- Masters Program Banner (Only shows if in progress) -->
      <?php if (
        strpos(strtolower($educational_attainment), 'master') !== false ||
        strpos(strtolower($educational_attainment), 'pursuing') !== false
      ): ?>
        <div class="masters-banner" id="mastersBanner">
          <div>
            <h3><i class="fas fa-graduation-cap"></i> Masters Program Reminder</h3>
            <p>Your masters program is still in progress. Don't forget to submit your requirements!</p>
          </div>
          <button class="btn" id="updateMastersBtn">Update Status</button>
        </div>
      <?php endif; ?>

      <div class="cards">
        <div class="card">
          <i class="fas fa-calendar-check"></i>
          <h3><?php echo $remaining_days; ?></h3>
          <p>Leave Days Remaining</p>
        </div>
        <div class="card">
          <i class="fas fa-envelope-open-text"></i>
          <h3><?php echo $pending_count; ?></h3>
          <p>Pending Leave Requests</p>
        </div>
        <div class="card">
          <i class="fas fa-folder-open"></i>
          <h3>2</h3>
          <p>Missing Documents</p>
        </div>
        <div class="card masters-card">
          <i class="fas fa-graduation-cap"></i>
          <h3><?php echo htmlspecialchars(ucfirst($educational_attainment)); ?></h3>
          <p>Status</p>
        </div>
      </div>

      <div class="info-section">
        <div class="profile">
          <a class="edit-btn" id="editProfileBtn" href="account_settings.php">Edit</a>
          <h3>My Profile</h3>
          <hr style="margin: 8px 0 15px 0; border: none; border-top: 1px solid #ddd;">

          <div class="profile-header">
            <div class="avatar" id="mainAvatar">
              <img id="mainAvatarImg" alt="Profile Picture" style="display:none;">
              <span><?php echo $initials; ?></span>
            </div>
            <div>
              <h4><?php echo htmlspecialchars($full_name); ?></h4>
              <p><?php echo htmlspecialchars($employee_position); ?> -
                <?php echo htmlspecialchars($employee_department); ?>
              </p>
              <p class="emp-id">Employee ID: <?php echo htmlspecialchars($employee_id); ?></p>
              <p class="leave-days-info">Leave Days Used: <?php echo $spend_days; ?> days</p>
              <div id="profileMastersBadge" class="masters-badge 
                <?php
                if (
                  strpos(strtolower($educational_attainment), 'master') !== false ||
                  strpos(strtolower($educational_attainment), 'pursuing') !== false
                ) {
                  echo "masters-in-progress";
                } else {
                  echo "masters-not-applicable";
                }
                ?>">
                <?php echo htmlspecialchars($educational_attainment); ?>
              </div>
            </div>
          </div>

          <div class="info-grid">
            <div class="info-box">
              <div class="info-label">Email Address</div>
              <div class="info-value" contenteditable="false" id="profileEmail">
                <?php echo htmlspecialchars($employee_email); ?>
              </div>
            </div>
            <div class="info-box">
              <div class="info-label">Contact Number</div>
              <div class="info-value" contenteditable="false" id="profileContact">
                <?php echo htmlspecialchars($employee_phone); ?>
              </div>
            </div>
            <div class="info-box">
              <div class="info-label">Employment Type</div>
              <div class="info-value" contenteditable="false"><?php echo htmlspecialchars($employee_type); ?></div>
            </div>
            <div class="info-box">
              <div class="info-label">Educational Attainment</div>
              <div class="info-value" contenteditable="false"><?php echo htmlspecialchars($educational_attainment); ?>
              </div>
            </div>
            <div class="info-box">
              <div class="info-label">Leave Days Used</div>
              <div class="info-value"><?php echo $spend_days; ?> days</div>
            </div>
            <div class="info-box">
              <div class="info-label">Leave Days Remaining</div>
              <div class="info-value"><?php echo $remaining_days; ?> days (Out of <?php echo $total_leave_days; ?>)
              </div>
            </div>
            <div class="info-box">
              <div class="info-label">Employment Status</div>
              <div class="info-value" contenteditable="false"><?php echo htmlspecialchars($employment_status); ?></div>
            </div>
            <div class="info-box">
              <div class="info-label">Date Hired</div>
              <div class="info-value" contenteditable="false"><?php echo htmlspecialchars($date_hired_formatted); ?>
              </div>
            </div>
          </div>
        </div>

        <div class="history">
          <h3>Recent Activity <a href="activity.php"><span class="view">View All →</span></a></h3>

          <?php
          // Query to get recent activity for the user, limited to 4 items
          $activity_query = "SELECT * FROM activity_log 
                            WHERE employee_number = ? 
                            ORDER BY date DESC 
                            LIMIT 4";

          $stmt_activity = $connection->prepare($activity_query);
          $stmt_activity->bind_param("s", $employee_id);
          $stmt_activity->execute();
          $activity_result = $stmt_activity->get_result();

          // Check if there are results
          if ($activity_result->num_rows > 0) {
            while ($row = $activity_result->fetch_assoc()) {
              // Determine icon based on title
              $icon = 'fas fa-bell';
              if (strpos($row['title'], 'Document') !== false) {
                $icon = 'fas fa-file-upload';
              } elseif (strpos($row['title'], 'Calendar') !== false) {
                $icon = 'fas fa-calendar-alt';
              } elseif (strpos($row['title'], 'Leave') !== false) {
                $icon = 'fas fa-plane-departure';
              } elseif (strpos($row['title'], 'HR') !== false || strpos($row['title'], 'hired') !== false) {
                $icon = 'fas fa-user-tie';
              }

              // Calculate time ago
              $timeAgo = timeAgo($row['date']);

              echo '<div class="history-item">';
              echo '<strong><i class="' . $icon . '"></i> ' . htmlspecialchars($row['title']) . '</strong>';
              echo '<p>' . htmlspecialchars($row['content']) . '</p>';
              echo '<span>' . $timeAgo . '</span>';
              echo '</div>';
            }
          } else {
            echo '<div class="history-item">';
            echo '<p>No recent activity</p>';
            echo '</div>';
          }

          $stmt_activity->close();
          $connection->close();
          ?>

        </div>
      </div>
    </div>
  </div>

  <script src="../../js/user/dashboard.js"></script>
  <?php include __DIR__ . '/logout_confirm.php'; ?>
</body>

</html>