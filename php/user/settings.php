<?php
include("../../database/connection.php");
session_start();

if (empty($_SESSION['loggedUser'])) {
    header("Location: ../../index.html");
    exit;
}

// Function to get initials
function getInitials($name) {
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
function timeAgo($datetime) {
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

$employee_id = $_SESSION['loggedUser'];

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

  $full_name = $employee_data['full_name'] ?? 'User';
  $name_parts = explode(' ', $full_name);
  $first_name = $name_parts[0] ?? 'User';
  $last_name = count($name_parts) > 1 ? end($name_parts) : '';

  $initials = getInitials($full_name);
  $employee_number = $employee_data['employee_number'] ?? '';
  $employee_email = $employee_data['email'] ?? '';
  $employee_phone = $employee_data['phone'] ?? '';
  $employee_type = $employee_data['employee_type'] ?? 'Not set';
  $employee_department = $employee_data['department'] ?? 'Not set';
  $employee_position = $employee_data['position'] ?? 'Not set';
  $employee_bio = $employee_data['credentials'] ?? 'Not set';

} else {
  $full_name = "User";
  $first_name = "User";
  $last_name = "";
  $initials = "US";
  $employee_number = "";
  $employee_email = "";
  $employee_phone = "";
  $employee_type = "Not available";
  $employee_department = "Not available";
  $employee_position = "Not available";
  $employee_bio = "Not available";
}
$stmt_emp->close();

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
  <title>Account Settings</title>
  <link rel="stylesheet" href="../../css/user/task.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .alert {
      padding: 15px 20px;
      margin-bottom: 20px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: slideIn 0.3s ease;
    }

    .alert-success {
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
    }

    .alert-error {
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
    }

    .alert i {
      font-size: 20px;
    }

    .alert-close {
      margin-left: auto;
      background: none;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: inherit;
      opacity: 0.7;
    }

    .alert-close:hover {
      opacity: 1;
    }

    @keyframes slideIn {
      from {
        transform: translateY(-20px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .password-requirements {
      margin-top: 10px;
      padding: 10px;
      background: #f8f9fa;
      border-radius: 4px;
      font-size: 13px;
    }

    .password-requirements ul {
      margin: 5px 0;
      padding-left: 20px;
    }

    .password-requirements li {
      margin: 3px 0;
      color: #666;
    }

    .password-requirements li.valid {
      color: #28a745;
    }

    .password-requirements li.valid::before {
      content: "✓ ";
      font-weight: bold;
    }

    .btn-loading {
      position: relative;
      pointer-events: none;
      opacity: 0.7;
    }

    .btn-loading::after {
      content: "";
      position: absolute;
      width: 16px;
      height: 16px;
      top: 50%;
      left: 50%;
      margin-left: -8px;
      margin-top: -8px;
      border: 2px solid #ffffff;
      border-radius: 50%;
      border-top-color: transparent;
      animation: spinner 0.6s linear infinite;
    }

    @keyframes spinner {
      to {
        transform: rotate(360deg);
      }
    }
  </style>
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
        $recent_notif_query = "SELECT id, title, content, url, date, read_status 
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
<a href=".<?php echo $notif['url']; ?>" style="">

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
      $connection->close();
      ?>

      <button class="close-modal" id="closeNotifModal">Close</button>
    </div>
  </div>

  <div class="main-container">
    <div class="sidebar">
      <div>
        <div class="profile-card">
          <div class="profile-circle"><?php echo $initials; ?></div>
          <h3><?php echo htmlspecialchars($full_name); ?></h3>
          <p><?php echo htmlspecialchars($employee_position); ?></p>
        </div>
        <ul class="menu">
          <li><a href="dashboard.php" style="text-decoration:none; color:inherit; display:block;">Dashboard</a></li>
          <li><a href="activity.php" style="text-decoration:none; color:inherit; display:block;">Activity</a></li>
          <li><a href="calendar.php" style="text-decoration:none; color:inherit; display:block;">Calendar</a></li>
          <li class="active"><a href="settings.php" style="text-decoration:none; color:inherit; display:block;">Settings</a></li>
                              <li><a href="logout.php" style="text-decoration:none; color:inherit; display:block;">Logout</a>
                    </li>
        </ul>
      </div>
      <div class="bottom-logo">
        <img src="logo 2.png" alt="Logo" />
      </div>
    </div>

    <div class="main-content">
      <h1>Account Settings</h1>
      <p>Manage your profile, security, and preferences</p>

      <div id="alertContainer"></div>

      <div class="settings-tabs">
        <button class="tab-btn active" data-tab="profile">
          <i class="fas fa-user"></i> Profile
        </button>
        <button class="tab-btn" data-tab="security">
          <i class="fas fa-lock"></i> Security
        </button>
      </div>

      <!-- Profile Tab -->
      <div class="tab-content active" id="profile">
        <div class="settings-section">
          <h2><i class="fas fa-user-circle"></i> Profile Information</h2>

          <form class="settings-form" id="profileForm">
            <div class="form-row">
              <div class="form-group">
                <label for="firstName">First Name *</label>
                <input type="text" id="firstName" value="<?php echo htmlspecialchars($first_name); ?>" required />
              </div>
              <div class="form-group">
                <label for="lastName">Last Name *</label>
                <input type="text" id="lastName" value="<?php echo htmlspecialchars($last_name); ?>" required />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="profileEmail">Email Address *</label>
                <input type="email" id="profileEmail" value="<?php echo htmlspecialchars($employee_email); ?>" required />
              </div>
              <div class="form-group">
                <label for="profilePhone">Phone Number</label>
                <input type="tel" id="profilePhone" value="<?php echo htmlspecialchars($employee_phone); ?>" />
              </div>
            </div>

            <div class="form-group">
              <label for="department">Department</label>
              <input type="text" id="department" value="<?php echo htmlspecialchars($employee_department); ?>" readonly />
            </div>

            <div class="form-group">
              <label for="position">Position</label>
              <input type="text" id="position" value="<?php echo htmlspecialchars($employee_position); ?>" readonly />
            </div>

            <div class="form-actions">
              <button type="button" class="btn-secondary" id="cancelProfile">Cancel</button>
              <button type="submit" class="btn-primary" id="submitProfileBtn">
                <i class="fas fa-save"></i> Save Changes
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Security Tab -->
      <div class="tab-content" id="security">
        <div class="settings-section">
          <h2><i class="fas fa-shield-alt"></i> Security Settings</h2>

          <div class="security-card">
            <h3><i class="fas fa-key"></i> Change Password</h3>
            <form class="settings-form" id="passwordForm">
              <div class="form-group">
                <label for="currentPassword">Current Password *</label>
                <div class="password-input">
                  <input type="password" id="currentPassword" required />
                  <button type="button" class="toggle-password" data-target="currentPassword">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="form-group">
                <label for="newPassword">New Password *</label>
                <div class="password-input">
                  <input type="password" id="newPassword" required />
                  <button type="button" class="toggle-password" data-target="newPassword">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <small class="password-hint">
                  Must be at least 8 characters with uppercase, lowercase, number, and special character
                </small>

                <div class="password-requirements" id="passwordRequirements" style="display: none;">
                  <strong>Password must contain:</strong>
                  <ul>
                    <li id="req-length">At least 8 characters</li>
                    <li id="req-uppercase">One uppercase letter (A-Z)</li>
                    <li id="req-lowercase">One lowercase letter (a-z)</li>
                    <li id="req-number">One number (0-9)</li>
                    <li id="req-special">One special character (!@#$%^&*)</li>
                  </ul>
                </div>
              </div>

              <div class="form-group">
                <label for="confirmPassword">Confirm New Password *</label>
                <div class="password-input">
                  <input type="password" id="confirmPassword" required />
                  <button type="button" class="toggle-password" data-target="confirmPassword">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
                <small id="passwordMatchMsg" style="display: none; margin-top: 5px;"></small>
              </div>

              <div class="password-strength" id="passwordStrength" style="display: none;">
                <div class="strength-bar">
                  <div class="strength-fill" id="strengthFill"></div>
                </div>
                <span id="strengthText">Password strength</span>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary" id="submitPasswordBtn">
                  <i class="fas fa-key"></i> Update Password
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../js/user/task.js"></script>
  <script>
    // Show alert function
    function showAlert(message, type) {
      const alertContainer = document.getElementById('alertContainer');
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert alert-${type}`;
      alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
      `;

      alertContainer.innerHTML = '';
      alertContainer.appendChild(alertDiv);
      window.scrollTo({ top: 0, behavior: 'smooth' });

      setTimeout(() => {
        alertDiv.style.transition = 'opacity 0.5s';
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 500);
      }, 5000);
    }

    document.addEventListener('DOMContentLoaded', function () {
      // ==========================================
      // NOTIFICATION SYSTEM
      // ==========================================
      const notifBtn = document.getElementById('notifBtn');
      const notifDropdown = document.getElementById('notifDropdown');
      const viewAllNotif = document.getElementById('viewAllNotif');
      const notifModal = document.getElementById('notifModal');
      const closeNotifModal = document.getElementById('closeNotifModal');

      // Toggle notification dropdown
      if (notifBtn) {
        notifBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          notifDropdown.classList.toggle('active');
        });
      }

      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
          notifDropdown.classList.remove('active');
        }
      });

      // Mark notification as read when clicked
      document.querySelectorAll('.notif-item').forEach(item => {
        item.addEventListener('click', async function() {
          const notifId = this.getAttribute('data-notif-id');
          
          if (notifId && this.classList.contains('unread')) {
            try {
              const response = await fetch('../../backendPHP/mark_as_read.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notif_id=${notifId}`
              });

              const result = await response.json();

              if (result.success) {
                // Remove unread styling
                this.classList.remove('unread');
                
                // Remove unread dot
                const unreadDot = this.querySelector('.unread-dot');
                if (unreadDot) {
                  unreadDot.remove();
                }

                // Update badge count
                const badge = document.querySelector('.notif-badge');
                
                if (result.unread_count > 0) {
                  if (badge) {
                    badge.textContent = result.unread_count;
                  } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notif-badge';
                    newBadge.textContent = result.unread_count;
                    notifBtn.appendChild(newBadge);
                  }
                  notifBtn.setAttribute('data-count', result.unread_count);
                } else {
                  if (badge) {
                    badge.remove();
                  }
                  notifBtn.setAttribute('data-count', '0');
                }
              }
            } catch (error) {
              console.error('Error marking notification as read:', error);
            }
          }
        });
      });

      // Open full notification modal
      if (viewAllNotif) {
        viewAllNotif.addEventListener('click', function(e) {
          e.preventDefault();
          notifDropdown.classList.remove('active');
          notifModal.classList.add('active');
        });
      }

      // Close notification modal
      if (closeNotifModal) {
        closeNotifModal.addEventListener('click', function() {
          notifModal.classList.remove('active');
        });
      }

      // Close modal when clicking outside
      if (notifModal) {
        notifModal.addEventListener('click', function(e) {
          if (e.target === notifModal) {
            notifModal.classList.remove('active');
          }
        });
      }

      // ==========================================
      // TAB SWITCHING
      // ==========================================
      const tabBtns = document.querySelectorAll('.tab-btn');
      const tabContents = document.querySelectorAll('.tab-content');

      tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
          const tabId = this.getAttribute('data-tab');
          tabBtns.forEach(b => b.classList.remove('active'));
          tabContents.forEach(c => c.classList.remove('active'));
          this.classList.add('active');
          document.getElementById(tabId).classList.add('active');
        });
      });

      // ==========================================
      // PASSWORD TOGGLE
      // ==========================================
      document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          const targetId = this.getAttribute('data-target');
          const input = document.getElementById(targetId);
          const icon = this.querySelector('i');

          if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
          } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
          }
        });
      });

      // ==========================================
      // PASSWORD VALIDATION
      // ==========================================
      const newPasswordInput = document.getElementById('newPassword');
      const confirmPasswordInput = document.getElementById('confirmPassword');
      const passwordRequirements = document.getElementById('passwordRequirements');
      const passwordStrength = document.getElementById('passwordStrength');
      const strengthFill = document.getElementById('strengthFill');
      const strengthText = document.getElementById('strengthText');
      const passwordMatchMsg = document.getElementById('passwordMatchMsg');

      newPasswordInput.addEventListener('focus', function () {
        passwordRequirements.style.display = 'block';
        passwordStrength.style.display = 'block';
      });

      newPasswordInput.addEventListener('input', function () {
        const password = this.value;
        let strength = 0;

        // Check requirements
        const checks = [
          { id: 'req-length', test: password.length >= 8 },
          { id: 'req-uppercase', test: /[A-Z]/.test(password) },
          { id: 'req-lowercase', test: /[a-z]/.test(password) },
          { id: 'req-number', test: /[0-9]/.test(password) },
          { id: 'req-special', test: /[^A-Za-z0-9]/.test(password) }
        ];

        checks.forEach(check => {
          const el = document.getElementById(check.id);
          if (check.test) {
            el.classList.add('valid');
            strength++;
          } else {
            el.classList.remove('valid');
          }
        });

        // Update strength bar
        const percentage = (strength / 5) * 100;
        strengthFill.style.width = percentage + '%';

        if (strength <= 2) {
          strengthFill.style.backgroundColor = '#dc3545';
          strengthText.textContent = 'Weak';
        } else if (strength <= 4) {
          strengthFill.style.backgroundColor = '#ffc107';
          strengthText.textContent = 'Medium';
        } else {
          strengthFill.style.backgroundColor = '#28a745';
          strengthText.textContent = 'Strong';
        }

        checkPasswordMatch();
      });

      confirmPasswordInput.addEventListener('input', checkPasswordMatch);

      function checkPasswordMatch() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (confirmPassword.length > 0) {
          passwordMatchMsg.style.display = 'block';
          if (password === confirmPassword) {
            passwordMatchMsg.textContent = '✓ Passwords match';
            passwordMatchMsg.style.color = '#28a745';
          } else {
            passwordMatchMsg.textContent = '✗ Passwords do not match';
            passwordMatchMsg.style.color = '#dc3545';
          }
        } else {
          passwordMatchMsg.style.display = 'none';
        }
      }

      // ==========================================
      // PROFILE FORM - AJAX SUBMIT
      // ==========================================
      document.getElementById('profileForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const firstName = document.getElementById('firstName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const email = document.getElementById('profileEmail').value.trim();
        const phone = document.getElementById('profilePhone').value.trim();

        const submitBtn = document.getElementById('submitProfileBtn');
        submitBtn.classList.add('btn-loading');
        submitBtn.disabled = true;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../../backendPHP/update_profile.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function () {
          submitBtn.classList.remove('btn-loading');
          submitBtn.disabled = false;

          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);

              if (response.success) {
                showAlert(response.message, 'success');
                if (response.full_name) {
                  document.querySelectorAll('.profile-card h3').forEach(el => {
                    el.textContent = response.full_name;
                  });
                  document.querySelector('.user-name').textContent = response.full_name;
                }
              } else {
                showAlert(response.message, 'error');
              }
            } catch (e) {
              showAlert('Error processing response: ' + e.message, 'error');
            }
          } else {
            showAlert('Server error occurred (Status: ' + xhr.status + ')', 'error');
          }
        };

        xhr.onerror = function () {
          submitBtn.classList.remove('btn-loading');
          submitBtn.disabled = false;
          showAlert('Network error occurred', 'error');
        };

        const params = 'first_name=' + encodeURIComponent(firstName) +
          '&last_name=' + encodeURIComponent(lastName) +
          '&email=' + encodeURIComponent(email) +
          '&phone=' + encodeURIComponent(phone);

        xhr.send(params);
      });

      document.getElementById('cancelProfile').addEventListener('click', function () {
        location.reload();
      });

      // ==========================================
      // PASSWORD FORM - AJAX SUBMIT
      // ==========================================
      document.getElementById('passwordForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validation
        if (!currentPassword || !newPassword || !confirmPassword) {
          showAlert('All password fields are required.', 'error');
          return;
        }

        const allValid = document.querySelectorAll('.password-requirements li.valid').length === 5;
        if (!allValid) {
          showAlert('Please ensure your password meets all requirements.', 'error');
          return;
        }

        if (newPassword !== confirmPassword) {
          showAlert('Passwords do not match.', 'error');
          return;
        }

        const submitBtn = document.getElementById('submitPasswordBtn');
        submitBtn.classList.add('btn-loading');
        submitBtn.disabled = true;

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../../backendPHP/change_password.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function () {
          submitBtn.classList.remove('btn-loading');
          submitBtn.disabled = false;

          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);

              if (response.success) {
                showAlert(response.message, 'success');
                document.getElementById('passwordForm').reset();
                passwordRequirements.style.display = 'none';
                passwordStrength.style.display = 'none';
                passwordMatchMsg.style.display = 'none';
                document.querySelectorAll('.password-requirements li').forEach(li => {
                  li.classList.remove('valid');
                });
              } else {
                showAlert(response.message, 'error');
              }
            } catch (e) {
              showAlert('Error processing response: ' + e.message, 'error');
            }
          } else {
            showAlert('Server error occurred (Status: ' + xhr.status + ')', 'error');
          }
        };

        xhr.onerror = function () {
          submitBtn.classList.remove('btn-loading');
          submitBtn.disabled = false;
          showAlert('Network error occurred', 'error');
        };

        const params = 'current_password=' + encodeURIComponent(currentPassword) +
          '&new_password=' + encodeURIComponent(newPassword) +
          '&confirm_password=' + encodeURIComponent(confirmPassword);

        xhr.send(params);
      });
    });
  </script>
</body>

</html>