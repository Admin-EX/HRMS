<?php
include("../../database/connection.php");
session_start();
if (empty($_SESSION['loggedUser'])) {
    header("Location: ../../index.html");
    exit;
}

if (!isset($_SESSION['loggedUser'])) {
  header("Location: ../../login.php");
  exit();
}

// ============================================
// HELPER FUNCTIONS - DEFINED FIRST
// ============================================
function getInitials($name)
{
  $words = explode(' ', $name);
  $initials = '';
  foreach ($words as $word) {
    if (!empty($word)) {
      $initials .= strtoupper($word[0]);
    }
  }
  return substr($initials, 0, 2);
}

function timeAgo($datetime)
{
  $posted_date = new DateTime($datetime);
  $now = new DateTime();
  $diff = $now->diff($posted_date);

  if ($diff->d == 0) {
    if ($diff->h == 0) {
      if ($diff->i == 0) {
        return 'Just now';
      } else {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
      }
    } else {
      return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    }
  } elseif ($diff->d == 1) {
    return '1 day ago';
  } elseif ($diff->d < 7) {
    return $diff->d . ' days ago';
  } elseif ($diff->d < 30) {
    $weeks = floor($diff->d / 7);
    return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
  } else {
    return $posted_date->format('M d, Y');
  }
}

$employee_id = $_SESSION['loggedUser'];
$_SESSION['employee_number'] = $employee_id; // Ensure consistency

// Initialize variables
$employee_data = null;

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

  $full_name = htmlspecialchars($employee_data['full_name'] ?? 'User');
  $name_parts = explode(' ', $full_name);
  $first_name = $name_parts[0] ?? 'User';

  $initials = getInitials($full_name);
  $employee_number = $employee_data['employee_number'] ?? '';
  $employee_email = htmlspecialchars($employee_data['email'] ?? 'Not set');
  $employee_phone = htmlspecialchars($employee_data['phone'] ?? 'Not set');
  $employee_type = htmlspecialchars($employee_data['employee_type'] ?? 'Not set');
  $employee_department = htmlspecialchars($employee_data['department'] ?? 'Not set');
  $employee_position = htmlspecialchars($employee_data['position'] ?? 'Not set');
  $educational_attainment = htmlspecialchars($employee_data['educational_attainment'] ?? 'Not set');
  $date_hired = $employee_data['date_hired'] ?? null;
  $employment_status = htmlspecialchars($employee_data['employment_status'] ?? 'Not set');

  if ($date_hired && $date_hired != '0000-00-00') {
    $date_hired_formatted = date('F j, Y', strtotime($date_hired));
  } else {
    $date_hired_formatted = 'Not set';
  }

} else {
  $full_name = "User";
  $first_name = "User";
  $initials = "US";
  $employee_number = "";
  $employee_email = "Not available";
  $employee_phone = "Not available";
  $employee_type = "Not available";
  $employee_department = "Not available";
  $employee_position = "Not available";
  $educational_attainment = "Not available";
  $date_hired_formatted = "Not available";
  $employment_status = "Not available";
}

// Fetch calendar events
$calendar_events = [];
if (!empty($employee_number)) {
  $calendardetail = "SELECT `id`, `employee_id`, `title`, `start_datetime`, `category`, `color`, `details`, `time_display`, `is_imported`, `created_at`, `updated_at` 
                       FROM `calendar_events` 
                       WHERE employee_id = ?";
  $stmt_cal = $connection->prepare($calendardetail);
  $stmt_cal->bind_param("s", $employee_number);
  $stmt_cal->execute();
  $calendardetail_result = $stmt_cal->get_result();

  if ($calendardetail_result) {
    $calendar_events = $calendardetail_result->fetch_all(MYSQLI_ASSOC);
  }
  $stmt_cal->close();
}

$stmt_emp->close();

// Initialize document variables for notification modal (if used)
$complete_docs = 0;
$pending_docs = 0;
$missing_docs = 0;
$pending_leaves = 0;
$medical_status = 'missing';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Pro Calendar - Activity</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    /* ====== Base ====== */
    :root {
      --brand-a: #00a884;
      --brand-b: #00c0ff;
      --bg: #f5f8fa;
      --muted: #777;
      --card: #fff;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: "Poppins", sans-serif
    }

    body {
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      color: #222
    }

    /* ====== Top bar ====== */
    .top-bar {
      width: 100%;
      background: linear-gradient(135deg, var(--brand-a), var(--brand-b));
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 50px;
      border-bottom: 3px solid rgba(255, 255, 255, 0.2);
      position: fixed;
      top: 0;
      left: 0;
      z-index: 10;
      height: 75px;
    }

    .top-left {
      background: white;
      padding: 6px 14px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .top-left img {
      width: 200px;
      height: auto;
      object-fit: contain;
      display: block;
    }

    .top-right {
      display: flex;
      align-items: center;
      gap: 20px;
      position: relative;
    }

    /* Notification Badge (Red Dot) */
    .notif {
      position: relative;
      cursor: pointer;
      padding: 10px;
      border-radius: 50%;
      transition: background-color 0.3s;
      font-size: 22px;
      color: rgb(246, 222, 10);
    }

    .notif:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }

    .notif-badge {
      position: absolute;
      top: 5px;
      right: 5px;
      background-color: #e74c3c;
      color: white;
      border-radius: 50%;
      padding: 2px 6px;
      font-size: 0.7em;
      font-weight: bold;
      min-width: 18px;
      height: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.1);
      }
    }

    .user-name {
      font-weight: 500;
      font-size: 16px;
    }

    /* Notification Dropdown */
    .notif-dropdown {
      display: none;
      position: absolute;
      top: 60px;
      right: 20px;
      width: 350px;
      max-height: 500px;
      overflow-y: auto;
      background: white;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
      z-index: 1000;
      color: #333;
    }

    .notif-dropdown.active {
      display: block;
      animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .notif-item {
      display: flex;
      align-items: flex-start;
      padding: 15px;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
      transition: background-color 0.2s;
      position: relative;
    }

    .notif-item:hover {
      background-color: #f8f9fa;
    }

    .notif-item.unread {
      background-color: #e3f2fd;
      border-left: 3px solid #2196f3;
    }

    .notif-item.unread:hover {
      background-color: #d1e7fd;
    }

    .notif-item i {
      font-size: 1.2em;
      color: #3498db;
      margin-right: 12px;
      margin-top: 3px;
    }

    .notif-details-dropdown {
      flex: 1;
    }

    .notif-details-dropdown strong {
      display: block;
      color: #2c3e50;
      font-size: 0.95em;
      margin-bottom: 4px;
    }

    .notif-details-dropdown p {
      color: #555;
      font-size: 0.85em;
      margin: 4px 0;
      line-height: 1.4;
    }

    .notif-time {
      color: #95a5a6;
      font-size: 0.75em;
      font-style: italic;
    }

    .unread-dot {
      position: absolute;
      top: 50%;
      right: 15px;
      transform: translateY(-50%);
      width: 8px;
      height: 8px;
      background-color: #2196f3;
      border-radius: 50%;
    }

    .view-all {
      text-align: center;
      padding: 10px;
      background: linear-gradient(135deg, var(--brand-a), var(--brand-b));
      color: white;
      cursor: pointer;
      font-weight: 500;
    }

    .view-all:hover {
      opacity: 0.9;
    }

    /* Notification Modal */
    .notif-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.4);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 100;
      backdrop-filter: blur(3px);
    }

    .notif-modal.active {
      display: flex;
    }

    .notif-content {
      background: #fff;
      padding: 25px;
      border-radius: 12px;
      width: 400px;
      max-height: 80vh;
      overflow-y: auto;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      animation: fadeInUp 0.3s ease;
    }

    .notif-content h3 {
      margin-bottom: 15px;
      text-align: center;
      color: #00a884;
    }

    .close-modal {
      width: 100%;
      background: linear-gradient(135deg, var(--brand-a), var(--brand-b));
      color: white;
      border: none;
      padding: 12px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1em;
      margin-top: 20px;
      transition: background-color 0.3s;
    }

    .close-modal:hover {
      opacity: 0.9;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ====== Layout ====== */
    .main-container {
      display: flex;
      flex: 1;
      margin-top: 75px;
      min-height: calc(100vh - 75px)
    }

    /* Sidebar */
    .sidebar {
      width: 250px;
      background: #fff;
      border-right: 2px solid #d9d9d9;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 0 0 25px 0;
    }

    .profile-card {
      text-align: center;
      padding: 25px 15px 15px;
      border-bottom: 1px solid #eee;
    }

    .profile-circle {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--brand-a), var(--brand-b));
      color: white;
      font-size: 28px;
      line-height: 90px;
      margin: 0 auto 12px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .profile-card h3 {
      color: #007b66;
      font-size: 17px;
      margin-bottom: 3px;
    }

    .profile-card p {
      font-size: 13px;
      color: #777;
    }

    .menu {
      list-style: none;
      margin-top: 20px;
      padding: 0;
    }

    .menu li {
      padding: 14px 25px;
      cursor: pointer;
      transition: 0.3s;
      color: #333;
      font-size: 15px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .menu li.active,
    .menu li:hover {
      background: linear-gradient(135deg, var(--brand-a), var(--brand-b));
      color: #fff;
      border-radius: 0 20px 20px 0;
    }

    .menu a {
      text-decoration: none;
      color: inherit;
      display: block;
      width: 100%;
    }

    .bottom-logo {
      text-align: center;
      padding: 20px 0px 0;
      border-top: 1px solid #eee;
    }

    .bottom-logo img {
      width: 250px;
      height: 100px;
      object-fit: contain;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      padding: 25px 50px;
      display: flex;
      gap: 25px;
    }

    .left-col {
      flex: 1;
      min-width: 520px;
    }

    .right-col {
      width: 320px;
    }

    .card {
      background: var(--card);
      border-radius: 12px;
      padding: 18px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .page-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .page-title {
      font-size: 22px;
      color: var(--brand-a);
      font-weight: 700;
    }

    .nav-btns {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .nav-btn {
      background: #fff;
      border: 1px solid #e6e6e6;
      padding: 8px 10px;
      border-radius: 8px;
      cursor: pointer;
      color: #333;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .nav-btn.primary {
      background: linear-gradient(135deg, var(--brand-a), var(--brand-b));
      border: 0;
      color: #fff;
    }

    .calendar-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 8px;
      margin-top: 12px;
    }

    .weekday {
      font-weight: 700;
      color: #007b66;
      text-align: center;
      padding: 8px 6px;
    }

    .cell {
      min-height: 110px;
      background: #fbfdfe;
      border-radius: 10px;
      padding: 8px;
      position: relative;
      overflow: hidden;
      cursor: default;
      border: 1px solid transparent;
    }

    .cell.empty {
      background: transparent;
    }

    .cell .date-num {
      position: absolute;
      top: 8px;
      left: 8px;
      font-weight: 600;
      color: #333;
    }

    .cell .events {
      margin-top: 30px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .evt-badge {
      display: inline-block;
      padding: 6px 8px;
      border-radius: 8px;
      color: #fff;
      font-size: 12px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 100%;
    }

    .cell:hover {
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .cell.today {
      border: 2px solid rgba(0, 168, 132, 0.14);
      background: linear-gradient(180deg, #f4fffb, #ffffff);
    }

    .upcoming-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .upcoming-item {
      display: flex;
      gap: 10px;
      align-items: center;
      padding: 8px;
      border-radius: 8px;
      border: 1px solid #f0f0f0;
      background: #fff;
      cursor: pointer;
    }

    .legend {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 12px;
    }

    .legend .chip {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 8px;
      border-radius: 8px;
      font-size: 13px;
      background: #fff;
      border: 1px solid #eee;
    }

    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.4);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 120;
      backdrop-filter: blur(3px);
    }

    .modal-backdrop.active {
      display: flex;
    }

    .modal {
      width: 460px;
      max-width: 95%;
      background: #fff;
      padding: 25px 30px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      position: relative;
      animation: fadeIn 0.3s ease;
    }

    .modal h3 {
      color: var(--brand-a);
      margin-bottom: 15px;
      text-align: center;
      font-size: 20px;
    }

    .form-row {
      margin-bottom: 10px;
    }

    .form-row label {
      display: block;
      font-size: 13px;
      color: #333;
      margin-bottom: 6px;
    }

    .form-row input[type="text"],
    .form-row input[type="date"],
    .form-row input[type="time"],
    .form-row textarea,
    .form-row select {
      width: 100%;
      padding: 9px 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 14px;
    }

    .row {
      display: flex;
      gap: 8px;
    }

    .actions {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
      margin-top: 15px;
    }

    .btn {
      padding: 8px 12px;
      border-radius: 8px;
      border: 0;
      cursor: pointer;
      font-weight: 600;
    }

    .btn.cancel {
      background: #f2f2f2;
      color: #333;
    }

    .btn.save {
      background: linear-gradient(135deg, var(--brand-a), var(--brand-b));
      color: #fff;
    }

    .btn.delete {
      background: #ffd9d9;
      color: #b30000;
      margin-right: auto;
    }

    .close-btn {
      position: absolute;
      top: 12px;
      right: 15px;
      font-size: 20px;
      color: #999;
      cursor: pointer;
      transition: 0.2s;
    }

    .close-btn:hover {
      color: #333;
    }

    .imported-events-section {
      margin-top: 15px;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
      border-left: 4px solid #00a884;
    }

    .imported-events-section h4 {
      color: #007b66;
      margin-bottom: 10px;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .imported-event-item {
      background: white;
      border-radius: 6px;
      padding: 10px 12px;
      margin-bottom: 8px;
      border: 1px solid #e0e0e0;
      cursor: pointer;
      transition: 0.2s;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .imported-event-item:hover {
      background: #f0fbf8;
      border-color: #00a884;
      transform: translateY(-1px);
    }

    .imported-event-item .event-title {
      font-weight: 500;
      color: #333;
      flex: 1;
    }

    .imported-event-item .event-date {
      font-size: 12px;
      color: #777;
      background: #f0f0f0;
      padding: 2px 8px;
      border-radius: 4px;
      margin-left: 10px;
    }

    @media (max-width:1000px) {
      .main-content {
        flex-direction: column;
        padding: 18px;
      }

      .right-col {
        width: 100%;
      }

      .left-col {
        min-width: unset;
      }

      .sidebar {
        display: none;
      }
    }
    a {
  text-decoration: none;
}
  </style>
</head>

<body>

  <div class="top-bar">
    <div class="top-left">
      <img src="logo 2.png" alt="Logo">
    </div>
    <div class="top-right">
      <?php
      // Count unread notifications
      $unread_query = "SELECT COUNT(*) as unread_count FROM activity_log 
                       WHERE employee_number = ? AND read_status = 'unread'";
      $stmt_unread = mysqli_prepare($connection, $unread_query);
      mysqli_stmt_bind_param($stmt_unread, "s", $employee_id);
      mysqli_stmt_execute($stmt_unread);
      $unread_result = mysqli_stmt_get_result($stmt_unread);
      $unread_row = mysqli_fetch_assoc($unread_result);
      $unread_count = $unread_row['unread_count'] ?? 0;
      mysqli_stmt_close($stmt_unread);
      ?>

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

        $stmt_recent = mysqli_prepare($connection, $recent_notif_query);
        mysqli_stmt_bind_param($stmt_recent, "s", $employee_id);
        mysqli_stmt_execute($stmt_recent);
        $recent_notif_result = mysqli_stmt_get_result($stmt_recent);

        if (mysqli_num_rows($recent_notif_result) > 0) {
          while ($notif = mysqli_fetch_assoc($recent_notif_result)) {
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
        mysqli_stmt_close($stmt_recent);
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

      $stmt_all = mysqli_prepare($connection, $all_notif_query);
      mysqli_stmt_bind_param($stmt_all, "s", $employee_id);
      mysqli_stmt_execute($stmt_all);
      $all_notif_result = mysqli_stmt_get_result($stmt_all);

      if (mysqli_num_rows($all_notif_result) > 0) {
        while ($notif = mysqli_fetch_assoc($all_notif_result)) {
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
      mysqli_stmt_close($stmt_all);
      ?>

      <button class="close-modal" id="closeNotifModal">Close</button>
    </div>
  </div>

  <!-- LAYOUT -->
  <div class="main-container">
    <aside class="sidebar">
      <div>
        <div class="profile-card">
          <div class="profile-circle"><?php echo $initials; ?></div>
          <h3><?php echo $full_name; ?></h3>
          <p><?php echo $employee_position ?: $employee_type; ?></p>
        </div>

        <nav>
          <ul class="menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="activity.php">Activity</a></li>
            <li class="active"><a href="calendar.php">Calendar</a></li>
            <li><a href="settings.php">Settings</a></li>
                                <li><a href="logout.php" style="text-decoration:none; color:inherit; display:block;">Logout</a>
                    </li>
          </ul>
        </nav>
      </div>

      <div class="bottom-logo">
        <img src="logo 2.png" alt="Logo" />
      </div>
    </aside>

    <main class="main-content">
      <div class="left-col">
        <div class="card">
          <div class="page-head">
            <div class="page-title">Calendar</div>
            <div class="nav-btns">
              <button class="nav-btn" id="todayBtn">Today</button>
              <button class="nav-btn" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
              <button class="nav-btn" id="nextBtn"><i class="fas fa-chevron-right"></i></button>
              <button class="nav-btn primary" id="addQuickBtn"><i class="fas fa-plus"></i> New</button>
            </div>
          </div>

          <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
            <div style="font-weight:700;color:#333" id="monthYearLabel"></div>
            <div style="color:var(--muted);font-size:13px">Click a cell to add event • Click event to edit</div>
          </div>

          <div class="calendar-grid" id="calendarGrid">
            <div class="weekday">Sun</div>
            <div class="weekday">Mon</div>
            <div class="weekday">Tue</div>
            <div class="weekday">Wed</div>
            <div class="weekday">Thu</div>
            <div class="weekday">Fri</div>
            <div class="weekday">Sat</div>
          </div>
        </div>
      </div>

      <div class="right-col">
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <div style="font-weight:700;color:#333">Upcoming</div>
            <div style="font-size:13px;color:var(--muted)"><a href="#" id="viewAllUpcoming">View all</a></div>
          </div>
          <div class="upcoming-list" id="upcomingList"></div>

          <hr style="margin:14px 0;border:none;border-top:1px solid #f0f0f0">

          <div class="imported-events-section" id="importedEventsSection">
            <h4><i class="fas fa-bullhorn"></i> Announcements from Activity</h4>
            <div id="importedEventsList"></div>
            <div id="noImportedEvents" style="text-align:center;color:#777;font-size:13px;padding:10px">
              <i class="fas fa-info-circle"></i> Click "Add to Calendar" in Activity page to import events
            </div>
          </div>

          <div style="font-weight:700;color:#333;margin-bottom:8px">Legend</div>
          <div class="legend" id="legend"></div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal-backdrop" id="modalBackdrop">
    <div class="modal">
      <h3 id="modalTitle">Add Schedule</h3>

      <div class="form-row">
        <label for="evtTitle">Title *</label>
        <input id="evtTitle" type="text" placeholder="Event title">
      </div>

      <div class="row">
        <div style="flex:1" class="form-row">
          <label for="evtDate">Date *</label>
          <input id="evtDate" type="date">
        </div>
        <div style="width:140px" class="form-row">
          <label for="evtTime">Time</label>
          <input id="evtTime" type="time">
        </div>
      </div>

      <div class="form-row">
        <label for="evtCategory">Category</label>
        <select id="evtCategory"></select>
      </div>

      <div class="form-row">
        <label for="evtDetails">Details</label>
        <textarea id="evtDetails" rows="3" placeholder="Notes (optional)"></textarea>
      </div>

      <div class="actions">
        <button class="btn delete" id="deleteBtn" style="display:none">Delete</button>
        <button class="btn cancel" id="cancelBtn">Cancel</button>
        <button class="btn save" id="saveBtn">Save</button>
      </div>
    </div>
  </div>

  <script>
    // Notification System JavaScript
    document.addEventListener('DOMContentLoaded', function () {
      const notifBtn = document.getElementById('notifBtn');
      const notifDropdown = document.getElementById('notifDropdown');
      const viewAllNotif = document.getElementById('viewAllNotif');
      const notifModal = document.getElementById('notifModal');
      const closeNotifModal = document.getElementById('closeNotifModal');

      // Toggle notification dropdown
      if (notifBtn) {
        notifBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          notifDropdown.classList.toggle('active');
        });
      }

      // Close dropdown when clicking outside
      document.addEventListener('click', function (e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
          notifDropdown.classList.remove('active');
        }
      });

      // Mark notification as read when clicked
      document.querySelectorAll('.notif-item').forEach(item => {
        item.addEventListener('click', async function () {
          const notifId = this.getAttribute('data-notif-id');

          if (notifId && this.classList.contains('unread')) {
            try {
              const response = await fetch('mark_as_read.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notif_id=${notifId}`
              });

              const result = await response.json();

              if (result.success) {
                this.classList.remove('unread');

                const unreadDot = this.querySelector('.unread-dot');
                if (unreadDot) {
                  unreadDot.remove();
                }

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
        viewAllNotif.addEventListener('click', function () {
          notifDropdown.classList.remove('active');
          notifModal.classList.add('active');
        });
      }

      // Close notification modal
      if (closeNotifModal) {
        closeNotifModal.addEventListener('click', function () {
          notifModal.classList.remove('active');
        });
      }

      // Close modal when clicking outside
      if (notifModal) {
        notifModal.addEventListener('click', function (e) {
          if (e.target === notifModal) {
            notifModal.classList.remove('active');
          }
        });
      }
    });


    /***********************
     * Calendar App Script *
     ***********************/

    (function () {
      // categories & colors
      const CATEGORIES = [
        { id: 'work', label: 'Work', color: '#007bff' },
        { id: 'meeting', label: 'Meeting', color: '#ff8a00' },
        { id: 'personal', label: 'Personal', color: '#00a884' },
        { id: 'deadline', label: 'Deadline', color: '#e53935' },
        { id: 'announcement', label: 'Announcement', color: '#9c27b0' },
        { id: 'others', label: 'Other', color: '#6c757d' }
      ];

      const STORAGE_KEY = 'emp_calendar_events_v2';
      const IMPORTED_EVENTS_KEY = 'imported_announcements_v1';
      const REMINDER_OFFSET_MIN = 5;

      // UI elements
      const calendarGrid = document.getElementById('calendarGrid');
      const monthYearLabel = document.getElementById('monthYearLabel');
      const prevBtn = document.getElementById('prevBtn');
      const nextBtn = document.getElementById('nextBtn');
      const todayBtn = document.getElementById('todayBtn');
      const addQuickBtn = document.getElementById('addQuickBtn');
      const modalBackdrop = document.getElementById('modalBackdrop');
      const modalTitle = document.getElementById('modalTitle');
      const evtTitle = document.getElementById('evtTitle');
      const evtDate = document.getElementById('evtDate');
      const evtTime = document.getElementById('evtTime');
      const evtCategory = document.getElementById('evtCategory');
      const evtDetails = document.getElementById('evtDetails');
      const saveBtn = document.getElementById('saveBtn');
      const cancelBtn = document.getElementById('cancelBtn');
      const deleteBtn = document.getElementById('deleteBtn');
      const upcomingList = document.getElementById('upcomingList');
      const legend = document.getElementById('legend');
      const importedEventsList = document.getElementById('importedEventsList');
      const noImportedEvents = document.getElementById('noImportedEvents');

      const notifBtn = document.getElementById("notifBtn");
      const notifDropdown = document.getElementById("notifDropdown");
      const notifModal = document.getElementById("notifModal");
      const viewAllNotif = document.getElementById("viewAllNotif");
      const closeNotifModal = document.getElementById("closeNotifModal");

      let currentViewDate = new Date();
      let events = [];
      let importedAnnouncements = [];
      let editingEventId = null;
      let reminderTimers = {};

      /* ---- helper utils ---- */
      function uid() { return String(Date.now()) + Math.floor(Math.random() * 1000); }
      function dateToYMD(d) {
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
      }
      function datetimeFor(dateStr, timeStr) {
        if (!timeStr) return dateStr;
        return dateStr + 'T' + timeStr;
      }

      /* ---- Database operations ---- */
      async function saveEventToDatabase(eventData, action = 'create') {
        const formData = new FormData();
        formData.append('action', action);

        if (action === 'create' || action === 'update') {
          formData.append('title', eventData.title);
          formData.append('start_datetime', eventData.start.replace('T', ' ') + ':00');
          formData.append('category', eventData.category);
          formData.append('color', eventData.color);
          formData.append('details', eventData.details || '');
          formData.append('time_display', eventData.timeDisplay || '');
          formData.append('is_imported', eventData.isImported ? 1 : 0);

          if (action === 'update') {
            formData.append('id', eventData.id);
          }
        } else if (action === 'delete') {
          formData.append('id', eventData.id);
        }

        try {
          const response = await fetch('../../backendPHP/api_calendar.php', {
            method: 'POST',
            body: formData
          });

          // Get raw response text
          const rawResponse = await response.text();
          console.log('=== RAW SERVER RESPONSE ===');
          console.log(rawResponse);
          console.log('=== END RESPONSE ===');

          // Try to parse as JSON
          const result = JSON.parse(rawResponse);
          return result;

        } catch (error) {
          console.error('Database operation failed:', error);

          if (error instanceof SyntaxError) {
            console.error('Server returned HTML/PHP errors instead of JSON');
            console.error('First 500 chars of response:', rawResponse.substring(0, 500));
          }

          return { success: false, message: 'Network error' };
        }
      }

      function loadEvents() {
        try {
          const raw = localStorage.getItem(STORAGE_KEY);
          events = raw ? JSON.parse(raw) : [];
        } catch (e) { events = []; }
      }

      function saveEvents() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(events));
      }

      function loadImportedAnnouncements() {
        try {
          const raw = localStorage.getItem(IMPORTED_EVENTS_KEY);
          importedAnnouncements = raw ? JSON.parse(raw) : [];
        } catch (e) { importedAnnouncements = []; }
      }

      function saveImportedAnnouncements() {
        localStorage.setItem(IMPORTED_EVENTS_KEY, JSON.stringify(importedAnnouncements));
      }

      /* ---- Import Announcements from Activity ---- */
      async function importAnnouncementFromActivity(title, date, description) {
        const alreadyImported = importedAnnouncements.find(ann =>
          ann.title === title && ann.date === date
        );

        if (alreadyImported) {
          alert(`"${title}" is already in your calendar!`);
          return false;
        }

        const announcement = {
          id: uid(),
          title: title,
          date: date,
          description: description || '',
          importedDate: new Date().toISOString(),
          source: 'activity'
        };

        importedAnnouncements.push(announcement);
        saveImportedAnnouncements();

        const calendarEvent = {
          id: announcement.id,
          title: title,
          start: date + 'T09:00',
          category: 'announcement',
          color: '#9c27b0',
          details: description || '',
          timeDisplay: '09:00',
          isImported: true
        };

        // Save to database
        const dbResult = await saveEventToDatabase(calendarEvent, 'create');

        if (dbResult.success) {
          // Update ID with database ID
          calendarEvent.id = dbResult.id;
          announcement.id = dbResult.id;

          events.push(calendarEvent);
          saveEvents();

          renderCalendar();
          renderUpcoming();
          renderImportedEvents();
          scheduleAllReminders();

          return true;
        } else {
          alert('Failed to save event to database: ' + dbResult.message);
          return false;
        }
      }

      function checkForNewImports() {
        const urlParams = new URLSearchParams(window.location.search);
        const importedTitle = urlParams.get('import_title');
        const importedDate = urlParams.get('import_date');
        const importedDesc = urlParams.get('import_desc');

        if (importedTitle && importedDate) {
          window.history.replaceState({}, document.title, window.location.pathname);

          importAnnouncementFromActivity(
            decodeURIComponent(importedTitle),
            importedDate,
            importedDesc ? decodeURIComponent(importedDesc) : ''
          ).then(success => {
            if (success) {
              alert(`✅ "${decodeURIComponent(importedTitle)}" added to calendar!`);
            }
          });
        }
      }

      /* ---- render imported events ---- */
      function renderImportedEvents() {
        importedEventsList.innerHTML = '';

        if (importedAnnouncements.length === 0) {
          noImportedEvents.style.display = 'block';
          return;
        }

        noImportedEvents.style.display = 'none';

        const sorted = [...importedAnnouncements].sort((a, b) =>
          new Date(b.importedDate) - new Date(a.importedDate)
        ).slice(0, 5);

        sorted.forEach(ann => {
          const item = document.createElement('div');
          item.className = 'imported-event-item';

          const dateObj = new Date(ann.date);
          const formattedDate = dateObj.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
          });

          item.innerHTML = `
        <div class="event-title">${ann.title}</div>
        <div class="event-date">${formattedDate}</div>
      `;

          item.addEventListener('click', () => {
            const dateParts = ann.date.split('-');
            currentViewDate = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
            renderCalendar();

            setTimeout(() => {
              const todayCell = document.querySelector('.cell.today');
              if (todayCell) {
                todayCell.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
              }
            }, 100);
          });

          importedEventsList.appendChild(item);
        });

        if (importedAnnouncements.length > 5) {
          const viewAll = document.createElement('div');
          viewAll.style.textAlign = 'center';
          viewAll.style.marginTop = '10px';
          viewAll.innerHTML = `<a href="#" id="viewAllImported" style="color:#00a884;font-size:12px">View all ${importedAnnouncements.length} imported events</a>`;
          importedEventsList.appendChild(viewAll);

          document.getElementById('viewAllImported').addEventListener('click', (e) => {
            e.preventDefault();
            showAllImportedEvents();
          });
        }
      }

      function showAllImportedEvents() {
        const modal = document.createElement('div');
        modal.className = 'modal-backdrop active';
        modal.innerHTML = `
      <div class="modal" style="width: 500px;">
        <h3>All Imported Announcements</h3>
        <div style="max-height: 400px; overflow-y: auto; margin-bottom: 15px;">
          ${importedAnnouncements.map(ann => {
          const date = new Date(ann.date);
          const importDate = new Date(ann.importedDate);
          return `
              <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 8px; border-left: 4px solid #9c27b0;">
                <div style="font-weight: 600; color: #333;">${ann.title}</div>
                <div style="font-size: 13px; color: #666; margin-top: 4px;">${ann.description || 'No description'}</div>
                <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 12px; color: #888;">
                  <span>Event Date: ${date.toLocaleDateString()}</span>
                  <span>Imported: ${importDate.toLocaleDateString()}</span>
                </div>
              </div>
            `;
        }).join('')}
        </div>
        <button class="btn save" id="closeImportedModal" style="width: 100%;">Close</button>
      </div>
    `;

        document.body.appendChild(modal);

        modal.addEventListener('click', (e) => {
          if (e.target === modal || e.target.id === 'closeImportedModal') {
            document.body.removeChild(modal);
          }
        });
      }

      function renderLegend() {
        legend.innerHTML = '';
        CATEGORIES.forEach(c => {
          const chip = document.createElement('div');
          chip.className = 'chip';
          chip.innerHTML = `<span style="width:12px;height:12px;background:${c.color};border-radius:4px;display:inline-block"></span><span>${c.label}</span>`;
          legend.appendChild(chip);
        });
      }

      function renderCalendar() {
        while (calendarGrid.children.length > 7) calendarGrid.removeChild(calendarGrid.lastChild);

        const year = currentViewDate.getFullYear();
        const month = currentViewDate.getMonth();

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        monthYearLabel.textContent = currentViewDate.toLocaleString(undefined, { month: 'long', year: 'numeric' });

        for (let i = 0; i < firstDay; i++) {
          const cell = document.createElement('div');
          cell.className = 'cell empty';
          calendarGrid.appendChild(cell);
        }

        for (let day = 1; day <= daysInMonth; day++) {
          const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
          const cell = document.createElement('div');
          cell.className = 'cell';
          cell.dataset.date = dateStr;

          const today = new Date();
          if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
            cell.classList.add('today');
          }

          const num = document.createElement('div');
          num.className = 'date-num';
          num.textContent = day;
          cell.appendChild(num);

          const evtsContainer = document.createElement('div');
          evtsContainer.className = 'events';

          const dayEvents = events.filter(ev => {
            const startDate = (ev.start || '').split('T')[0];
            return startDate === dateStr;
          }).sort((a, b) => {
            const at = a.start.includes('T') ? a.start : a.start + 'T00:00';
            const bt = b.start.includes('T') ? b.start : b.start + 'T00:00';
            return new Date(at) - new Date(bt);
          });

          dayEvents.slice(0, 3).forEach(ev => {
            const badge = document.createElement('div');
            badge.className = 'evt-badge';
            badge.textContent = (ev.timeDisplay ? ev.timeDisplay + ' • ' : '') + ev.title;
            badge.style.background = ev.color || '#888';
            badge.title = ev.title + (ev.details ? ' — ' + ev.details : '');
            badge.dataset.id = ev.id;

            if (ev.category === 'announcement' && ev.isImported) {
              badge.innerHTML = `<i class="fas fa-bullhorn" style="margin-right: 4px;"></i> ${badge.textContent}`;
            }

            badge.addEventListener('click', (e) => {
              e.stopPropagation();
              openEditModal(ev.id);
            });
            evtsContainer.appendChild(badge);
          });

          if (dayEvents.length > 3) {
            const more = document.createElement('div');
            more.className = 'evt-badge';
            more.style.background = '#ececec';
            more.style.color = '#333';
            more.textContent = `+${dayEvents.length - 3} more`;
            more.title = 'Click to view all';
            more.addEventListener('click', (e) => {
              e.stopPropagation();
              openAddModal(dateStr);
            });
            evtsContainer.appendChild(more);
          }

          cell.appendChild(evtsContainer);

          cell.addEventListener('click', (e) => {
            if (e.target.classList.contains('evt-badge')) return;
            openAddModal(dateStr);
          });

          calendarGrid.appendChild(cell);
        }
      }

      function renderUpcoming() {
        upcomingList.innerHTML = '';
        const now = new Date();
        const upcoming = events.map(ev => {
          const start = ev.start.includes('T') ? new Date(ev.start) : new Date(ev.start + 'T00:00');
          return { ...ev, _startObj: start };
        }).filter(e => e._startObj >= new Date(now.getTime() - 1000 * 60 * 60 * 24))
          .sort((a, b) => a._startObj - b._startObj)
          .slice(0, 8);

        if (upcoming.length === 0) {
          const none = document.createElement('div');
          none.style.color = 'var(--muted)';
          none.textContent = 'No upcoming events';
          upcomingList.appendChild(none);
          return;
        }

        upcoming.forEach(ev => {
          const item = document.createElement('div');
          item.className = 'upcoming-item';

          item.innerHTML = `<div style="width:10px;height:40px;border-radius:6px;background:${ev.color};flex-shrink:0"></div>
        <div style="flex:1">
          <div style="font-weight:700">${ev.isImported ? '<i class="fas fa-bullhorn" style="color:#9c27b0;margin-right:4px;"></i>' : ''}${ev.title}</div>
          <div style="font-size:13px;color:#666;margin-top:4px">${formatDisplayDate(ev)}</div>
        </div>`;
          item.addEventListener('click', () => openEditModal(ev.id));
          upcomingList.appendChild(item);
        });
      }

      function formatDisplayDate(ev) {
        const start = ev.start.includes('T') ? new Date(ev.start) : new Date(ev.start + 'T00:00');
        const opts = { month: 'short', day: 'numeric' };
        const timePart = ev.start.includes('T') && ev.timeDisplay ? ' • ' + ev.timeDisplay : '';
        return `${start.toLocaleDateString(undefined, opts)}${timePart}`;
      }

      function openAddModal(dateStr) {
        editingEventId = null;
        modalTitle.textContent = 'Add Schedule';
        evtTitle.value = '';
        evtDate.value = dateStr || dateToYMD(new Date());
        evtTime.value = '';
        evtCategory.value = CATEGORIES[0].id;
        evtDetails.value = '';
        deleteBtn.style.display = 'none';
        modalBackdrop.classList.add('active');
      }

      function openEditModal(id) {
        const ev = events.find(e => String(e.id) === String(id));
        if (!ev) return;
        editingEventId = ev.id;
        modalTitle.textContent = 'Edit Schedule';
        evtTitle.value = ev.title;
        if (ev.start.includes('T')) {
          const parts = ev.start.split('T');
          evtDate.value = parts[0];
          evtTime.value = parts[1].slice(0, 5);
        } else {
          evtDate.value = ev.start;
          evtTime.value = '';
        }
        evtCategory.value = ev.category || CATEGORIES[0].id;
        evtDetails.value = ev.details || '';
        deleteBtn.style.display = 'inline-block';
        modalBackdrop.classList.add('active');
      }

      function closeModal() {
        modalBackdrop.classList.remove('active');
      }

      // Save (create or update) - WITH DATABASE
      saveBtn.addEventListener('click', async () => {
        const title = evtTitle.value.trim();
        const date = evtDate.value;
        const time = evtTime.value;
        const cat = evtCategory.value;
        const details = evtDetails.value.trim();

        if (!title) { alert('Please enter a title'); return; }
        if (!date) { alert('Please select a date'); return; }

        const catObj = CATEGORIES.find(c => c.id === cat) || CATEGORIES[0];
        const start = datetimeFor(date, time);
        const obj = {
          id: editingEventId || uid(),
          title,
          start,
          category: catObj.id,
          color: catObj.color,
          details,
          timeDisplay: time ? time : '',
          isImported: false
        };

        // Save to database
        const action = editingEventId ? 'update' : 'create';
        const dbResult = await saveEventToDatabase(obj, action);

        if (!dbResult.success) {
          alert('Failed to save event: ' + dbResult.message);
          return;
        }

        // If creating new event, update ID with database ID
        if (!editingEventId) {
          obj.id = dbResult.id;
        }

        // Update localStorage
        if (editingEventId) {
          const idx = events.findIndex(e => String(e.id) === String(editingEventId));
          if (idx > -1) events[idx] = obj;
        } else {
          events.push(obj);
        }

        saveEvents();
        scheduleAllReminders();
        renderCalendar();
        renderUpcoming();
        closeModal();

        alert('Event saved successfully!');
      });

      // Delete - WITH DATABASE
      deleteBtn.addEventListener('click', async () => {
        if (!editingEventId) return;
        if (!confirm('Delete this schedule?')) return;

        // Delete from database
        const dbResult = await saveEventToDatabase({ id: editingEventId }, 'delete');

        if (!dbResult.success) {
          alert('Failed to delete event: ' + dbResult.message);
          return;
        }

        // Also remove from imported announcements if it's an imported one
        const evToDelete = events.find(e => String(e.id) === String(editingEventId));
        if (evToDelete && evToDelete.isImported) {
          importedAnnouncements = importedAnnouncements.filter(ann => ann.id !== editingEventId);
          saveImportedAnnouncements();
          renderImportedEvents();
        }

        events = events.filter(e => String(e.id) !== String(editingEventId));
        saveEvents();
        cancelReminder(editingEventId);
        renderCalendar();
        renderUpcoming();
        closeModal();

        alert('Event deleted successfully!');
      });

      cancelBtn.addEventListener('click', closeModal);
      modalBackdrop.addEventListener('click', (e) => {
        if (e.target === modalBackdrop) closeModal();
      });

      prevBtn.addEventListener('click', () => { currentViewDate.setMonth(currentViewDate.getMonth() - 1); renderCalendar(); });
      nextBtn.addEventListener('click', () => { currentViewDate.setMonth(currentViewDate.getMonth() + 1); renderCalendar(); });
      todayBtn.addEventListener('click', () => { currentViewDate = new Date(); renderCalendar(); });
      addQuickBtn.addEventListener('click', () => openAddModal(dateToYMD(new Date())));

      document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

      function scheduleAllReminders() {
        Object.keys(reminderTimers).forEach(id => clearTimeout(reminderTimers[id]));
        reminderTimers = {};

        const now = Date.now();
        events.forEach(ev => {
          if (!ev.start) return;
          if (!ev.start.includes('T')) return;
          const evDate = new Date(ev.start);
          const remindAt = evDate.getTime() - REMINDER_OFFSET_MIN * 60 * 1000;
          const ms = remindAt - now;
          if (ms > 0) {
            const t = setTimeout(() => {
              alert(`Reminder: "${ev.title}" at ${ev.timeDisplay || evDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`);
              delete reminderTimers[ev.id];
            }, ms);
            reminderTimers[ev.id] = t;
          }
        });
      }

      function cancelReminder(id) {
        if (reminderTimers[id]) { clearTimeout(reminderTimers[id]); delete reminderTimers[id]; }
      }

      if (notifBtn && notifDropdown) {
        notifBtn.addEventListener("click", () => {
          notifDropdown.classList.toggle("active");
        });

        document.addEventListener("click", (e) => {
          if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove("active");
          }
        });
      }

      document.addEventListener('DOMContentLoaded', function () {
        const notifBtn = document.getElementById('notifBtn');
        const notifDropdown = document.getElementById('notifDropdown');
        const viewAllNotif = document.getElementById('viewAllNotif');
        const notifModal = document.getElementById('notifModal');
        const closeNotifModal = document.getElementById('closeNotifModal');

        // Toggle notification dropdown
        if (notifBtn) {
          notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
            console.log("Notification button clicked");
          });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
          if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove('active'); // ✅ FIXED
          }
          console.log("Document clicked");
        });

        // Mark notification as read when clicked
        document.querySelectorAll('.notif-item').forEach(item => {
          item.addEventListener('click', async function () {
            const notifId = this.getAttribute('data-notif-id');

            if (notifId && this.classList.contains('unread')) {
              try {
                const response = await fetch('mark_as_read.php', {
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

                  console.log(`Notification ${notifId} marked as read`);
                }
              } catch (error) {
                console.error('Error marking notification as read:', error);
              }
            }
          });
        });

        // Open full notification modal
        if (viewAllNotif) {
          viewAllNotif.addEventListener('click', function (e) {
            e.preventDefault();
            notifDropdown.classList.remove('active');
            notifModal.classList.add('active');
            console.log("Opening notification modal");
          });
        }

        // Close notification modal
        if (closeNotifModal) {
          closeNotifModal.addEventListener('click', function () {
            notifModal.classList.remove('active');
            console.log("Closing notification modal");
          });
        }

        // Close modal when clicking outside
        if (notifModal) {
          notifModal.addEventListener('click', function (e) {
            if (e.target === notifModal) {
              notifModal.classList.remove('active');
              console.log("Modal closed by clicking outside");
            }
          });
        }
      });

      function init() {
        evtCategory.innerHTML = '';
        CATEGORIES.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id;
          opt.textContent = c.label;
          evtCategory.appendChild(opt);
        });
        renderLegend();

        loadEvents();
        loadImportedAnnouncements();
        checkForNewImports();
        loadDatabaseEvents();

        events = events.map(ev => {
          const cat = CATEGORIES.find(c => c.id === ev.category) || CATEGORIES[0];
          return {
            id: ev.id || uid(),
            title: ev.title || 'Untitled',
            start: ev.start || dateToYMD(new Date()),
            category: ev.category || cat.id,
            color: ev.color || cat.color,
            details: ev.details || '',
            timeDisplay: ev.timeDisplay || ev.time_display || (ev.start && ev.start.includes('T') ? ev.start.split('T')[1].slice(0, 5) : ''),
            isImported: ev.isImported || ev.is_imported || false
          };
        });

        renderCalendar();
        renderUpcoming();
        renderImportedEvents();
        scheduleAllReminders();
      }

      document.getElementById('viewAllUpcoming').addEventListener('click', (e) => {
        e.preventDefault();
        if (events.length === 0) return;
        const future = events.map(ev => {
          const dt = ev.start.includes('T') ? new Date(ev.start) : new Date(ev.start + 'T00:00');
          return { ...ev, _d: dt };
        }).sort((a, b) => a._d - b._d);
        if (future.length) {
          currentViewDate = new Date(future[0]._d.getFullYear(), future[0]._d.getMonth(), 1);
          renderCalendar();
        }
      });

      function loadDatabaseEvents() {
        <?php
        if (!empty($calendar_events)) {
          foreach ($calendar_events as $event) {
            ?>
            const event_<?php echo $event['id']; ?> = {
              id: '<?php echo $event['id']; ?>',
              title: '<?php echo addslashes($event['title']); ?>',
              date: '<?php echo date('Y-m-d', strtotime($event['start_datetime'])); ?>',
              start: '<?php echo date('Y-m-d\TH:i', strtotime($event['start_datetime'])); ?>',
              category: '<?php echo $event['category']; ?>',
              color: '<?php echo $event['color']; ?>',
              details: '<?php echo addslashes($event['details']); ?>',
              timeDisplay: '<?php echo $event['time_display']; ?>',
              isImported: <?php echo $event['is_imported'] ? 'true' : 'false'; ?>,
              employee_id: '<?php echo $event['employee_id']; ?>'
            };

            if (!events.find(e => e.id === '<?php echo $event['id']; ?>')) {
              events.push(event_<?php echo $event['id']; ?>);
            }
            <?php
          }
          echo 'saveEvents();';
        }
        ?>
      }

      init();

      window.__calendar_events = events;
      window.__imported_announcements = importedAnnouncements;
      window.__calendar_refresh = function () {
        loadEvents();
        loadImportedAnnouncements();
        renderCalendar();
        renderUpcoming();
        renderImportedEvents();
        scheduleAllReminders();
      };
    })();

    document.addEventListener("click", () => {

    })
  </script>
  <?php include __DIR__ . '/logout_confirm.php'; ?>
</body>

</html>