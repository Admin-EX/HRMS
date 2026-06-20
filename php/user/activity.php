<?php
include("../../database/connection.php");
session_start();
if (empty($_SESSION['loggedUser'])) {
    header("Location: ../../index.html");
    exit;
}

// ============================================
// SESSION & AUTHENTICATION
// ============================================
if (!isset($_SESSION['loggedUser'])) {
    header("Location: ../../login.php");
    exit();
}

$employee_id = $_SESSION['loggedUser'];

// ============================================
// FILE UPLOAD HANDLER (AJAX)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['uploadDocBtn'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['loggedUser'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $employee_id = $_SESSION['loggedUser'];
    $doc_type = $_POST['doc_type'] ?? '';

    $required_docs = [
        '201 File'               => 'Employee 201 File',
        'Teaching Certificate'   => 'Teaching Certificate',
        'Medical Certificate'    => 'Medical Certificate',
        'Educational Transcript' => 'Educational Transcript',
        'ID Documents'           => 'ID & TIN Documents',
        'Employment Contract'    => 'Employment Contract',
        'Performance Reviews'    => 'Performance Reviews'
    ];

    if (!array_key_exists($doc_type, $required_docs)) {
        echo json_encode(['success' => false, 'message' => 'Invalid document type']);
        exit;
    }

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'No file uploaded';
        if (isset($_FILES['document']['error'])) {
            switch ($_FILES['document']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:  $error_message = 'File too large'; break;
                case UPLOAD_ERR_PARTIAL:    $error_message = 'File upload was interrupted'; break;
                case UPLOAD_ERR_NO_FILE:    $error_message = 'No file selected'; break;
                default:                    $error_message = 'Upload error occurred';
            }
        }
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit;
    }

    $file        = $_FILES['document'];
    $fileName    = basename($file['name']);
    $fileTmpName = $file['tmp_name'];
    $fileSize    = $file['size'];
    $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed     = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

    if (!in_array($fileExt, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX']);
        exit;
    }

    if ($fileSize > 10485760) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 10MB)']);
        exit;
    }

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpName);
    finfo_close($finfo);

    $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    if (!in_array($mimeType, $allowedMimes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format detected']);
        exit;
    }

    $uploadBaseDir = __DIR__ . '/../../uploads/documents/';
    $year          = date('Y');
    $month         = date('m');
    $uploadDir     = $uploadBaseDir . $year . '/' . $month . '/';

    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
    }

    $sanitizedDocType = preg_replace('/[^a-zA-Z0-9]/', '_', $doc_type);
    $randomString     = bin2hex(random_bytes(8));
    $newFileName      = $employee_id . '_' . $sanitizedDocType . '_' . time() . '_' . $randomString . '.' . $fileExt;
    $fileDestination  = $uploadDir . $newFileName;
    $relativeFilePath = 'uploads/documents/' . $year . '/' . $month . '/' . $newFileName;

    if (move_uploaded_file($fileTmpName, $fileDestination)) {
        chmod($fileDestination, 0644);

        try {
            $stmt = $connection->prepare("
                INSERT INTO employee_documents (
                    employee_number, document_type, document_name,
                    file_path, file_size, file_type, status,
                    upload_date, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    document_name = VALUES(document_name),
                    file_path     = VALUES(file_path),
                    file_size     = VALUES(file_size),
                    file_type     = VALUES(file_type),
                    status        = 'pending',
                    upload_date   = NOW(),
                    updated_at    = NOW()
            ");
            $stmt->bind_param("ssssis", $employee_id, $doc_type, $newFileName, $relativeFilePath, $fileSize, $mimeType);
            $success = $stmt->execute();

            if ($success) {
                echo json_encode([
                    'success'   => true,
                    'message'   => 'Document uploaded successfully! Awaiting admin approval.',
                    'file_path' => $relativeFilePath,
                    'file_name' => $newFileName
                ]);
                $activitysql = "INSERT INTO `activity_log`(`employee_number`, `title`, `content`, `url`, `read_status`) 
                    VALUES ('$employee_id', 'Required Document', 'You have successfully uploaded the $doc_type', '/activity.php', 'unread')";
                mysqli_query($connection, $activitysql);
            } else {
                unlink($fileDestination);
                echo json_encode(['success' => false, 'message' => 'Database error: Could not save document record']);
            }

            $stmt->close();

        } catch (Exception $e) {
            if (file_exists($fileDestination)) unlink($fileDestination);
            error_log("Document upload error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred while saving the document']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
    }

    exit;
}

// ============================================
// FETCH EMPLOYEE DATA
// ============================================
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
$emp_result  = $stmt_emp->get_result();
$employee_data = $emp_result->fetch_assoc();
$stmt_emp->close();

if ($employee_data) {
    $full_name              = $employee_data['full_name']              ?? 'User';
    $name_parts             = explode(' ', $full_name);
    $first_name             = $name_parts[0]                          ?? 'User';
    $initials               = getInitials($full_name);
    $employee_email         = $employee_data['email']                 ?? 'Not set';
    $employee_phone         = $employee_data['phone']                 ?? 'Not set';
    $employee_type          = $employee_data['employee_type']         ?? 'Not set';
    $employee_department    = $employee_data['department']            ?? 'Not set';
    $employee_position      = $employee_data['position']              ?? 'Not set';
    $educational_attainment = $employee_data['educational_attainment']?? 'Not set';
    $date_hired             = $employee_data['date_hired']            ?? 'Not set';
    $employment_status      = $employee_data['employment_status']     ?? 'Not set';
    $date_hired_formatted   = ($date_hired && $date_hired != 'Not set')
                                ? date('F j, Y', strtotime($date_hired))
                                : 'Not set';
} else {
    $full_name = $first_name = "User";
    $initials  = "US";
    $employee_email = $employee_phone = $employee_type = $employee_department =
    $employee_position = $educational_attainment = $date_hired_formatted = $employment_status = "Not available";
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        if (!empty($word)) $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 2);
}

function getStatusClass($status) {
    switch ($status) {
        case 'approved': return 'complete';
        case 'pending':  return 'pending';
        default:         return 'missing';
    }
}

function getStatusText($status) {
    switch ($status) {
        case 'approved': return 'Complete';
        case 'pending':  return 'Pending';
        case 'rejected': return 'Rejected';
        case 'expired':  return 'Expired';
        default:         return 'Missing';
    }
}

function timeAgo($datetime) {
    $posted_date = new DateTime($datetime);
    $now  = new DateTime();
    $diff = $now->diff($posted_date);
    if ($diff->d == 0) {
        if ($diff->h == 0) {
            return $diff->i == 0 ? 'Just now' : $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d == 1) {
        return '1 day ago';
    } elseif ($diff->d < 7) {
        return $diff->d . ' days ago';
    } elseif ($diff->d < 30) {
        $weeks = floor($diff->d / 7);
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    }
    return $posted_date->format('M d, Y');
}

// ============================================
// FETCH EMPLOYEE DOCUMENTS
// ============================================
$documents_query = "SELECT id, employee_number, document_type, document_name,
                           file_path, file_size, file_type, status,
                           upload_date, expiry_date, reviewed_by, review_date,
                           notes, created_at, updated_at
                    FROM employee_documents
                    WHERE employee_number = ?
                    ORDER BY FIELD(document_type,
                        'Medical Certificate','201 File','Teaching Certificate',
                        'Educational Transcript','ID Documents',
                        'Employment Contract','Performance Reviews')";

$stmt_docs       = $connection->prepare($documents_query);
$stmt_docs->bind_param("s", $employee_id);
$stmt_docs->execute();
$docs_result_obj = $stmt_docs->get_result();
$docs_result     = [];
while ($row = $docs_result_obj->fetch_assoc()) $docs_result[] = $row;
$stmt_docs->close();

$employee_documents = [];
$document_statuses  = [];
$complete_docs = $missing_docs = $pending_docs = 0;
$medical_status = 'missing';
$medical_expiry = '';

$required_docs = [
    '201 File'               => 'Employee 201 File',
    'Teaching Certificate'   => 'Teaching Certificate',
    'Medical Certificate'    => 'Medical Certificate',
    'Educational Transcript' => 'Educational Transcript',
    'ID Documents'           => 'ID & TIN Documents',
    'Employment Contract'    => 'Employment Contract',
    'Performance Reviews'    => 'Performance Reviews'
];

foreach ($docs_result as $doc) {
    $key = $doc['document_type'];
    // Keep only the most recent upload per document type
    if (!isset($employee_documents[$key]) ||
        strtotime($doc['upload_date']) > strtotime($employee_documents[$key]['upload_date'])) {
        $employee_documents[$key] = $doc;
        $document_statuses[$key]  = $doc['status'];
    }

    if ($doc['status'] == 'approved')       $complete_docs++;
    elseif ($doc['status'] == 'pending')    $pending_docs++;
    elseif ($doc['status'] == 'missing')    $missing_docs++;

    if ($key == 'Medical Certificate') {
        $medical_status = $doc['status'];
        if (!empty($doc['expiry_date']))
            $medical_expiry = date('F j, Y', strtotime($doc['expiry_date']));
    }
}

foreach ($required_docs as $doc_type => $doc_name) {
    if (!isset($employee_documents[$doc_type])) {
        $missing_docs++;
        $document_statuses[$doc_type] = 'missing';
    }
}

$total_docs = count($required_docs);

// ============================================
// PENDING LEAVE REQUESTS
// ============================================
$stmt_pending = $connection->prepare(
    "SELECT COUNT(*) AS pending_count FROM leave_requests WHERE employee_number = ? AND status = 'pending'"
);
$stmt_pending->bind_param("s", $employee_id);
$stmt_pending->execute();
$pending_leaves = $stmt_pending->get_result()->fetch_assoc()['pending_count'] ?? 0;
$stmt_pending->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Activity</title>
    <link rel="stylesheet" href="../../css/user/activity.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="top-bar">
    <div class="top-left">
        <img src="logo 2.png" alt="Logo">
    </div>
    <div class="top-right">
        <?php
        $stmt_unread = mysqli_prepare($connection,
            "SELECT COUNT(*) AS unread_count FROM activity_log WHERE employee_number = ? AND read_status = 'unread'");
        mysqli_stmt_bind_param($stmt_unread, "s", $employee_id);
        mysqli_stmt_execute($stmt_unread);
        $unread_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_unread))['unread_count'] ?? 0;
        mysqli_stmt_close($stmt_unread);
        ?>
        <div class="notif" id="notifBtn" data-count= "            <?php if ($unread_count > 0): ?>
                <?= $unread_count ?>
            <?php endif; ?>
">
            <i class="fas fa-bell"></i>
        </div>
        <div class="user-name"><?= htmlspecialchars($full_name) ?></div>

        <!-- Notification Dropdown -->
        <div class="notif-dropdown" id="notifDropdown">
            <?php
            $stmt_recent = mysqli_prepare($connection,
                "SELECT id, title, content, date, read_status FROM activity_log
                 WHERE employee_number = ? ORDER BY date DESC LIMIT 5");
            mysqli_stmt_bind_param($stmt_recent, "s", $employee_id);
            mysqli_stmt_execute($stmt_recent);
            $recent_notif_result = mysqli_stmt_get_result($stmt_recent);

            if (mysqli_num_rows($recent_notif_result) > 0):
                while ($notif = mysqli_fetch_assoc($recent_notif_result)):
                    $notif_time  = timeAgo($notif['date']);
                    $is_unread   = $notif['read_status'] == 'unread';
                    $notif_icon  = 'fas fa-bell';
                    if (strpos($notif['title'], 'Document') !== false) $notif_icon = 'fas fa-file-upload';
                    elseif (strpos($notif['title'], 'Calendar') !== false) $notif_icon = 'fas fa-calendar-alt';
                    elseif (strpos($notif['title'], 'Leave') !== false) $notif_icon = 'fas fa-plane-departure';
                    ?>
                    <div class="notif-item <?= $is_unread ? 'unread' : '' ?>"
                         data-notif-id="<?= $notif['id'] ?>">
                        <i class="<?= $notif_icon ?>"></i>
                        <div class="notif-details-dropdown">
                            <strong><?= htmlspecialchars($notif['title']) ?></strong>
                            <p><?= htmlspecialchars(substr($notif['content'], 0, 60)) ?>...</p>
                            <span class="notif-time"><?= $notif_time ?></span>
                        </div>
                        <?php if ($is_unread): ?><span class="unread-dot"></span><?php endif; ?>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="notif-item" id="defaultNotif">
                    <i class="fas fa-check-circle"></i>
                    <div class="notif-details-dropdown">
                        <strong>No new notifications</strong>
                        <p>You're all caught up!</p>
                    </div>
                </div>
            <?php endif;
            mysqli_stmt_close($stmt_recent); ?>
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

<!-- Documents Modal (Full) -->
<div class="docs-modal" id="docsModalFull">
    <div class="docs-content">
        <h3>Employee Documents — <?= htmlspecialchars($full_name) ?></h3>
        <p class="employee-info">
            ID: <?= htmlspecialchars($employee_id) ?> | Department: <?= htmlspecialchars($employee_department) ?>
        </p>

        <div class="doc-stats">
            <div class="stat-item"><span class="stat-number"><?= $complete_docs ?></span><span class="stat-label">Complete</span></div>
            <div class="stat-item"><span class="stat-number"><?= $pending_docs ?></span><span class="stat-label">Pending</span></div>
            <div class="stat-item"><span class="stat-number"><?= $missing_docs ?></span><span class="stat-label">Missing</span></div>
        </div>

        <?php foreach ($required_docs as $doc_type => $doc_name):
            $status       = $document_statuses[$doc_type] ?? 'missing';
            $doc_data     = $employee_documents[$doc_type] ?? null;
            $status_class = getStatusClass($status);
            $status_text  = getStatusText($status);

            $icon = 'fa-file-alt';
            if ($doc_type == 'Medical Certificate')    $icon = 'fa-notes-medical';
            elseif ($doc_type == 'Teaching Certificate')   $icon = 'fa-certificate';
            elseif ($doc_type == 'Educational Transcript') $icon = 'fa-graduation-cap';
            elseif ($doc_type == 'ID Documents')           $icon = 'fa-id-card';
            elseif ($doc_type == 'Employment Contract')    $icon = 'fa-file-contract';
            elseif ($doc_type == 'Performance Reviews')    $icon = 'fa-briefcase';

            // ── Does this document have a real uploaded file? ──────────────────
            $has_file = $doc_data
                     && !empty($doc_data['file_path'])
                     && $doc_data['file_path'] !== 'NULL'
                     && $doc_data['file_path'] !== null;
        ?>
        <div class="doc-item-modal <?= $status_class ?>">
            <div class="doc-info-modal">
                <i class="fas <?= $icon ?>"></i>
                <div class="doc-details">
                    <strong><?= htmlspecialchars($doc_name) ?></strong>
                    <p><?php
                        switch ($doc_type) {
                            case '201 File':               echo 'Complete employee records including personal information and employment history'; break;
                            case 'Teaching Certificate':   echo 'Professional teaching license and certification documents'; break;
                            case 'Medical Certificate':    echo 'Recent health examination results and medical clearance'; break;
                            case 'Educational Transcript': echo 'Official transcript showing completed educational units'; break;
                            case 'ID Documents':           echo 'Government-issued IDs and Tax Identification Number'; break;
                            case 'Employment Contract':    echo 'Signed employment agreement and terms of service'; break;
                            case 'Performance Reviews':    echo 'Quarterly and annual performance evaluation reports'; break;
                        }
                    ?></p>
                    <?php if ($doc_data && !empty($doc_data['upload_date'])): ?>
                        <span class="doc-upload-date">
                            Uploaded: <?= date('M j, Y', strtotime($doc_data['upload_date'])) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($doc_data && !empty($doc_data['expiry_date'])): ?>
                        <span class="doc-expiry">
                            Expires: <?= date('F j, Y', strtotime($doc_data['expiry_date'])) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($doc_data && !empty($doc_data['notes'])): ?>
                        <span class="doc-notes" style="color:#e67e22;font-size:12px;">
                            <i class="fas fa-comment-alt"></i> <?= htmlspecialchars($doc_data['notes']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <span class="doc-status <?= $status_class ?>"><?= htmlspecialchars($status_text) ?></span>

            <!-- ── ACTIONS ─────────────────────────────────────────────────── -->
            <div class="doc-actions">
                <?php if ($has_file):
                    $file_path   = htmlspecialchars($doc_data['file_path']);
                    $file_name   = htmlspecialchars($doc_data['document_name'] ?? $doc_type);
                    $doc_status  = $doc_data['status'] ?? 'pending';
                    $upload_dt   = !empty($doc_data['upload_date']) ? date('M j, Y', strtotime($doc_data['upload_date'])) : '';
                    $expiry_dt   = !empty($doc_data['expiry_date']) ? date('M j, Y', strtotime($doc_data['expiry_date'])) : '';
                ?>
                    <!-- View: opens preview modal in JS -->
                    <button class="doc-action-btn view-btn"
                            data-file="<?= $file_path ?>"
                            data-doc-name="<?= htmlspecialchars($doc_type) ?>"
                            data-status="<?= htmlspecialchars($doc_status) ?>"
                            data-upload-date="<?= $upload_dt ?>"
                            data-expiry-date="<?= $expiry_dt ?>">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <!-- Download: plain anchor for direct browser download -->
                    <a class="doc-action-btn download-btn"
                       href="../../<?= $file_path ?>"
                       download="<?= $file_name ?>"
                       target="_blank">
                        <i class="fas fa-download"></i> Download
                    </a>
                    <!-- Also allow re-upload if not yet approved -->
                    <?php if ($doc_status !== 'approved'): ?>
                        <button class="doc-action-btn upload-btn"
                                data-doc-type="<?= htmlspecialchars($doc_type) ?>">
                            <i class="fas fa-redo"></i> Re-upload
                        </button>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- No file at all — show upload -->
                    <button class="doc-action-btn upload-btn"
                            data-doc-type="<?= htmlspecialchars($doc_type) ?>">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <button class="close-modal" id="closeDocsModal">Close</button>
    </div>
</div>

<div class="modal-overlay" id="announcementsModal">
    <div class="modal">
        <span class="close-btn" id="closeAnnouncementsModal">&times;</span>
        <h3>All Announcements</h3>

        <?php
        $all_announcements_modal = [];
        $ann_res_modal = mysqli_query($connection,
            "SELECT id, title, content, announcement_date AS date, priority, status, 'announcement' AS source_type
             FROM announcements
             WHERE status = 'Active' AND announcement_date <= NOW()
               AND (end_date IS NULL OR end_date >= NOW())
             ORDER BY announcement_date DESC");
        if ($ann_res_modal && mysqli_num_rows($ann_res_modal) > 0)
            while ($r = mysqli_fetch_assoc($ann_res_modal)) $all_announcements_modal[] = $r;

        $stmt_ann_modal = mysqli_prepare($connection,
            "SELECT id, title, content, date, 'Medium' AS priority, 'Active' AS status, 'activity' AS source_type
             FROM activity_log
             WHERE employee_number = ?
               AND title IN ('Calendar','Required Document','Offset Document','Leave Document')
             ORDER BY date DESC");
        mysqli_stmt_bind_param($stmt_ann_modal, "s", $employee_id);
        mysqli_stmt_execute($stmt_ann_modal);
        $act_modal_result = mysqli_stmt_get_result($stmt_ann_modal);
        if ($act_modal_result && mysqli_num_rows($act_modal_result) > 0)
            while ($r = mysqli_fetch_assoc($act_modal_result)) $all_announcements_modal[] = $r;
        mysqli_stmt_close($stmt_ann_modal);

        usort($all_announcements_modal, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        ?>

        <?php if (count($all_announcements_modal) > 0): ?>
            <?php foreach ($all_announcements_modal as $ann):
                $is_important = isset($ann['priority']) && strtolower($ann['priority']) == 'high';
                $source_badge = $ann['source_type'] == 'activity' ? '<span class="source-badge activity">Personal</span>' : '';
                $is_cal = strpos(strtolower($ann['title']), 'meeting') !== false
                       || strpos(strtolower($ann['title']), 'event') !== false
                       || strpos(strtolower($ann['title']), 'deadline') !== false;
            ?>
                <div class="<?= $is_important ? 'announcement important' : 'announcement' ?>">
                    <strong><?= htmlspecialchars($ann['title']) ?> <?= $source_badge ?></strong>
                    <small><?= timeAgo($ann['date']) ?></small>
                    <p><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                    <?php if ($is_cal && $ann['source_type'] == 'announcement'): ?>
                        <div class="announcement-actions">
                            <button class="add-to-calendar-btn"
                                data-announcement-id="<?= $ann['id'] ?>"
                                data-title="<?= htmlspecialchars($ann['title'], ENT_QUOTES) ?>"
                                data-content="<?= htmlspecialchars($ann['content'], ENT_QUOTES) ?>"
                                data-date="<?= $ann['date'] ?>">
                                <i class="fas fa-calendar-plus"></i> Add to Calendar
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="announcement">
                <strong>No Announcements</strong>
                <small>Today</small>
                <p>There are no announcements available right now.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Main Layout -->
<div class="main-container">
    <div class="sidebar">
        <div>
            <div class="profile-card">
                <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                <h3><?= htmlspecialchars($full_name) ?></h3>
                <p><?= htmlspecialchars($employee_position) ?></p>
                <small><?= htmlspecialchars($employee_department) ?></small>
                <div class="document-summary">
                    <span class="doc-stat">📄 <?= $complete_docs ?>/<?= count($required_docs) ?> Docs Complete</span>
                </div>
            </div>
            <ul class="menu">
                <li><a href="dashboard.php" style="text-decoration:none;color:inherit;display:block;">Dashboard</a></li>
                <li class="active"><a href="activity.php" style="text-decoration:none;color:inherit;display:block;">Activity</a></li>
                <li><a href="calendar.php" style="text-decoration:none;color:inherit;display:block;">Calendar</a></li>
                <li><a href="settings.php" style="text-decoration:none;color:inherit;display:block;">Settings</a></li>
                <li><a href="logout.php" style="text-decoration:none;color:inherit;display:block;">Logout</a></li>
            </ul>
        </div>
        <div class="bottom-logo"><img src="logo 2.png" alt="Logo" /></div>
    </div>

    <div class="main-content">
        <!-- My Documents -->
        <div class="section">
            <h3>My Documents <span class="view" id="viewAllDocs">View All →</span></h3>
            <p class="doc-summary"><?= $complete_docs ?> complete, <?= $pending_docs ?> pending, <?= $missing_docs ?> missing</p>
            <?php
            $top_docs = ['201 File', 'Medical Certificate', 'Teaching Certificate', 'Educational Transcript'];
            foreach ($top_docs as $doc_type):
                $status       = $document_statuses[$doc_type] ?? 'missing';
                $status_class = getStatusClass($status);
                $status_text  = getStatusText($status);
                $icon = 'fa-file-alt';
                if ($doc_type == 'Medical Certificate')    $icon = 'fa-notes-medical';
                elseif ($doc_type == 'Teaching Certificate')   $icon = 'fa-certificate';
                elseif ($doc_type == 'Educational Transcript') $icon = 'fa-graduation-cap';
            ?>
                <div class="doc-item">
                    <div class="doc-info"><i class="fas <?= $icon ?>"></i> <?= htmlspecialchars($required_docs[$doc_type]) ?></div>
                    <span class="status <?= $status_class ?>"><?= htmlspecialchars($status_text) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex-row">
            <!-- Announcements -->
            <div class="left section">
                <h3>Announcements <span class="view" id="viewAllAnnouncements">View All →</span></h3>

                <div class="announcement">
                    <strong>Welcome, <?= htmlspecialchars($first_name) ?>!</strong>
                    <small>Today</small>
                    <p>Welcome to your activity dashboard. You have <?= $complete_docs ?> out of <?= count($required_docs) ?> required documents completed.</p>
                </div>

                <?php if ($medical_status == 'missing' || $medical_status == 'expired'): ?>
                    <div class="announcement important">
                        <strong>Medical Certificate Required</strong>
                        <small>Important</small>
                        <p>Your medical certificate is <?= getStatusText($medical_status) ?>. Please upload it through the "Submit Documents" button.</p>
                    </div>
                <?php endif; ?>

                <?php
                $all_announcements = [];

                $ann_res = mysqli_query($connection,
                    "SELECT id, title, content, announcement_date AS date, priority, status, 'announcement' AS source_type
                     FROM announcements
                     WHERE status = 'Active' AND announcement_date <= NOW()
                       AND (end_date IS NULL OR end_date >= NOW())
                     ORDER BY announcement_date DESC LIMIT 10");
                if ($ann_res && mysqli_num_rows($ann_res) > 0)
                    while ($r = mysqli_fetch_assoc($ann_res)) $all_announcements[] = $r;

                $stmt_a = mysqli_prepare($connection,
                    "SELECT id, title, content, date, 'Medium' AS priority, 'Active' AS status, 'activity' AS source_type
                     FROM activity_log
                     WHERE employee_number = ?
                       AND title IN ('Calendar','Required Document','Offset Document','Leave Document')
                     ORDER BY date DESC LIMIT 5");
                mysqli_stmt_bind_param($stmt_a, "s", $employee_id);
                mysqli_stmt_execute($stmt_a);
                $act_res = mysqli_stmt_get_result($stmt_a);
                if ($act_res && mysqli_num_rows($act_res) > 0)
                    while ($r = mysqli_fetch_assoc($act_res)) $all_announcements[] = $r;
                mysqli_stmt_close($stmt_a);

                usort($all_announcements, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

                $display_count = 0;
                foreach ($all_announcements as $ann):
                    if ($display_count >= 5) break;
                    $is_important = isset($ann['priority']) && strtolower($ann['priority']) == 'high';
                    $source_badge = $ann['source_type'] == 'activity'
                        ? '<span class="source-badge activity">Personal</span>' : '';
                    $is_cal = strpos(strtolower($ann['title']), 'meeting') !== false
                           || strpos(strtolower($ann['title']), 'event') !== false
                           || strpos(strtolower($ann['title']), 'deadline') !== false;
                ?>
                    <div class="<?= $is_important ? 'announcement important' : 'announcement' ?>">
                        <strong><?= htmlspecialchars($ann['title']) ?> <?= $source_badge ?></strong>
                        <small><?= timeAgo($ann['date']) ?></small>
                        <p><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                        <?php if ($is_cal && $ann['source_type'] == 'announcement'): ?>
                            <div class="announcement-actions">
                                <button class="add-to-calendar-btn"
                                    data-announcement-id="<?= $ann['id'] ?>"
                                    data-title="<?= htmlspecialchars($ann['title'], ENT_QUOTES) ?>"
                                    data-content="<?= htmlspecialchars($ann['content'], ENT_QUOTES) ?>"
                                    data-date="<?= $ann['date'] ?>">
                                    <i class="fas fa-calendar-plus"></i> Add to Calendar
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php $display_count++; endforeach;

                if (count($all_announcements) == 0): ?>
                    <div class="announcement">
                        <strong>No New Announcements</strong>
                        <small>Today</small>
                        <p>Check back later for updates and important information.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="right section">
                <h3>Quick Actions</h3>
                <div class="quick-actions">
                    <a href="requestLeave.php" class="action-card">
                        <i class="fas fa-plane"></i><p>Request Leave</p>
                        <?php if ($pending_leaves > 0): ?>
                            <span class="badge"><?= $pending_leaves ?> pending</span>
                        <?php endif; ?>
                    </a>
                    <div class="action-card" id="docsModalBtn">
                        <i class="fas fa-file-upload"></i><p>Submit Documents</p>
                        <?php if ($missing_docs > 0): ?>
                            <span class="badge"><?= $missing_docs ?> missing</span>
                        <?php endif; ?>
                    </div>
                    <a href="requestform.php" class="action-card"><i class="fas fa-clipboard-list"></i><p>Request Form</p></a>
                    <a href="offset.php" class="action-card"><i class="fas fa-clock"></i><p>Offset Form</p></a>
                    <a href="settings.php" class="action-card"><i class="fas fa-user-edit"></i><p>Update Profile</p></a>
                </div>
                <div class="note">⚠️ Please ensure all documents are submitted before the end of the month to avoid delays in processing.</div>
            </div>
        </div>
        <!-- ══════════════════════════════════════════════
     SUBMITTED DOCUMENTS STATUS TRACKER
     ══════════════════════════════════════════════ -->
<div class="section" id="docTrackerSection">
    <h3>
        <i class="fas fa-folder-open" style="color:#667eea;margin-right:8px;"></i>
        My Submitted Documents
        <span class="view" id="viewAllDocs2" style="float:right;cursor:pointer;">View All →</span>
    </h3>

    <!-- Progress Bar -->
    <?php
    $progress_pct = $total_docs > 0 ? round(($complete_docs / $total_docs) * 100) : 0;
    ?>
    <div class="doc-progress-bar-wrapper">
        <div class="doc-progress-labels">
            <span><?= $complete_docs ?> of <?= $total_docs ?> documents complete</span>
            <span><?= $progress_pct ?>%</span>
        </div>
        <div class="doc-progress-track">
            <div class="doc-progress-fill" style="width: <?= $progress_pct ?>%"></div>
        </div>
    </div>

    <!-- Document Cards Grid -->
    <div class="doc-tracker-grid">
        <?php foreach ($required_docs as $doc_type => $doc_name):
            $status      = $document_statuses[$doc_type] ?? 'missing';
            $doc_data    = $employee_documents[$doc_type] ?? null;
            $has_file    = $doc_data && !empty($doc_data['file_path']) && $doc_data['file_path'] !== 'NULL';

            $icon_map = [
                '201 File'               => 'fa-file-alt',
                'Teaching Certificate'   => 'fa-certificate',
                'Medical Certificate'    => 'fa-notes-medical',
                'Educational Transcript' => 'fa-graduation-cap',
                'ID Documents'           => 'fa-id-card',
                'Employment Contract'    => 'fa-file-contract',
                'Performance Reviews'    => 'fa-briefcase',
            ];
            $icon = $icon_map[$doc_type] ?? 'fa-file';

            $status_config = [
                'approved'     => ['label' => 'Approved',     'cls' => 'tracker-approved',     'dot' => '#27ae60'],
                'pending'      => ['label' => 'Pending',      'cls' => 'tracker-pending',      'dot' => '#f39c12'],
                'rejected'     => ['label' => 'Rejected',     'cls' => 'tracker-rejected',     'dot' => '#e74c3c'],
                'for_revision' => ['label' => 'For Revision', 'cls' => 'tracker-revision',     'dot' => '#3498db'],
                'missing'      => ['label' => 'Not Uploaded', 'cls' => 'tracker-missing',      'dot' => '#bdc3c7'],
            ];
            $cfg = $status_config[$status] ?? $status_config['missing'];
        ?>
        <div class="doc-tracker-card <?= $cfg['cls'] ?>">
            <div class="tracker-card-top">
                <div class="tracker-icon-wrap">
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <span class="tracker-status-dot" style="background:<?= $cfg['dot'] ?>;"></span>
            </div>
            <div class="tracker-card-body">
                <strong class="tracker-doc-name"><?= htmlspecialchars($doc_name) ?></strong>
                <span class="tracker-status-badge <?= $cfg['cls'] ?>"><?= $cfg['label'] ?></span>

                <?php if ($doc_data): ?>
                    <div class="tracker-meta">
                        <?php if (!empty($doc_data['upload_date'])): ?>
                            <span><i class="fas fa-upload"></i> <?= date('M j, Y', strtotime($doc_data['upload_date'])) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($doc_data['reviewed_by'])): ?>
                            <span><i class="fas fa-user-check"></i> <?= htmlspecialchars($doc_data['reviewed_by']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($doc_data['review_date'])): ?>
                            <span><i class="fas fa-calendar-check"></i> <?= date('M j, Y', strtotime($doc_data['review_date'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($doc_data['notes'])): ?>
                        <div class="tracker-notes">
                            <i class="fas fa-comment-alt"></i>
                            <?= htmlspecialchars($doc_data['notes']) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="tracker-card-footer">
                <?php if ($has_file): ?>
                    <button class="tracker-btn tracker-view"
                        data-file="<?= htmlspecialchars($doc_data['file_path']) ?>"
                        data-doc-name="<?= htmlspecialchars($doc_type) ?>">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <?php if ($status !== 'approved'): ?>
                        <button class="tracker-btn tracker-reupload upload-btn"
                            data-doc-type="<?= htmlspecialchars($doc_type) ?>">
                            <i class="fas fa-redo"></i> Re-upload
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="tracker-btn tracker-upload upload-btn"
                        data-doc-type="<?= htmlspecialchars($doc_type) ?>">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal-overlay" id="docsModal">
    <div class="modal">
        <span class="close-btn" id="closeUploadModal">&times;</span>
        <h3>Submit Document</h3>
        <p>Employee: <?= htmlspecialchars($full_name) ?> (<?= htmlspecialchars($employee_id) ?>)</p>
        <form id="docUploadForm" method="POST" enctype="multipart/form-data">
            <label for="docTypeSelect">Document Type</label>
            <select id="docTypeSelect" name="doc_type" required>
                <option value="">-- Select Document Type --</option>
                <?php foreach ($required_docs as $dt => $dn):
                    $s = $document_statuses[$dt] ?? 'missing';
                    if ($s != 'approved'): ?>
                        <option value="<?= htmlspecialchars($dt) ?>">
                            <?= htmlspecialchars($dn) ?> (<?= htmlspecialchars(getStatusText($s)) ?>)
                        </option>
                    <?php endif;
                endforeach; ?>
            </select>
            <label for="docUpload">Upload File</label>
            <input type="file" id="docUpload" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required />
            <div class="upload-info">
                <p><i class="fas fa-info-circle"></i> Max file size: 10MB. Supported: PDF, JPG, PNG, DOC, DOCX</p>
            </div>
            <div id="uploadStatus"></div>
            <input type="hidden" name="uploadDocBtn" value="POST" />
            <button type="submit" id="uploadDocBtn" class="upload-btn-full">
                <i class="fas fa-upload"></i> Upload Document
            </button>
        </form>
    </div>
</div>

<!-- HR Contact Modal -->
<div class="modal-overlay" id="hrModal">
    <div class="modal">
        <span class="close-btn" id="closeHrModal">&times;</span>
        <h3>Contact HR Department</h3>
        <div class="contact-info">
            <p><strong>From:</strong> <?= htmlspecialchars($full_name) ?></p>
            <p><strong>Employee ID:</strong> <?= htmlspecialchars($employee_id) ?></p>
            <p><strong>Department:</strong> <?= htmlspecialchars($employee_department) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($employee_email) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($employee_phone) ?></p>
        </div>
        <label>Subject</label>
        <input type="text" id="hrSubject" placeholder="Enter subject of your message" />
        <label>Message</label>
        <textarea id="hrMessage" rows="5" placeholder="Type your message here..."></textarea>
        <label>Priority</label>
        <select id="hrPriority">
            <option value="low">Low Priority</option>
            <option value="normal" selected>Normal Priority</option>
            <option value="high">High Priority</option>
            <option value="urgent">Urgent</option>
        </select>
        <button id="sendHrMessageBtn">Send Message to HR</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadForm   = document.getElementById('docUploadForm');
    const uploadBtn    = document.getElementById('uploadDocBtn');
    const uploadStatus = document.getElementById('uploadStatus');
    const docsModal    = document.getElementById('docsModal');

    if (uploadForm) {
        uploadForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(uploadForm);
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            uploadStatus.innerHTML = '<p style="color:#007bff;">Uploading document...</p>';
            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const result   = await response.json();
                if (result.success) {
                    uploadStatus.innerHTML = `<p style="color:#28a745;"><i class="fas fa-check-circle"></i> ${result.message}</p>`;
                    uploadForm.reset();
                    setTimeout(() => { docsModal.style.display = 'none'; uploadStatus.innerHTML = ''; window.location.reload(); }, 2000);
                } else {
                    uploadStatus.innerHTML = `<p style="color:#dc3545;"><i class="fas fa-exclamation-circle"></i> ${result.message}</p>`;
                }
            } catch (err) {
                uploadStatus.innerHTML = '<p style="color:#dc3545;"><i class="fas fa-exclamation-triangle"></i> An error occurred. Please try again.</p>';
            } finally {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Document';
            }
        });
    }

    const closeUploadModal = document.getElementById('closeUploadModal');
    if (closeUploadModal) {
        closeUploadModal.addEventListener('click', () => {
            docsModal.style.display = 'none';
            uploadForm.reset();
            uploadStatus.innerHTML = '';
        });
    }
});
// Wire tracker View buttons to existing preview modal
document.querySelectorAll('.tracker-view').forEach(btn => {
    btn.addEventListener('click', function() {
        const filePath = this.dataset.file;
        const docName  = this.dataset.docName;
        // Re-use your existing view-btn click logic or open directly
        const previewFrame = document.getElementById('documentPreviewFrame');
        const previewName  = document.getElementById('previewDocumentName');
        const previewModal = document.getElementById('documentPreviewModal');
        if (previewFrame && previewModal) {
            previewName.textContent = docName;
            previewFrame.src = '../../' + filePath;
            previewModal.classList.add('active');
        } else {
            window.open('../../' + filePath, '_blank');
        }
    });
});


docsModalBtn.addEventListener('click', () => {
    docsModal.style.display = 'flex'
})

const viewAllDocs = document.getElementById('viewAllDocs');
const viewAllDocs2 = document.getElementById('viewAllDocs2');
const docsModalFull = document.getElementById('docsModalFull');
const closeDocsModal = document.getElementById('closeDocsModal');
const viewAllAnnouncements = document.getElementById('viewAllAnnouncements');
const announcementsModal = document.getElementById('announcementsModal');
const closeAnnouncementsModal = document.getElementById('closeAnnouncementsModal');

if (viewAllDocs) {
    viewAllDocs.addEventListener('click', function () {
        if (docsModalFull) docsModalFull.classList.add('active');
    });
}
if (viewAllDocs2) {
    viewAllDocs2.addEventListener('click', function () {
        if (docsModalFull) docsModalFull.classList.add('active');
    });
}
if (closeDocsModal && docsModalFull) {
    closeDocsModal.addEventListener('click', function () {
        docsModalFull.classList.remove('active');
    });
}
if (docsModalFull) {
    docsModalFull.addEventListener('click', function (e) {
        if (e.target === docsModalFull) {
            docsModalFull.classList.remove('active');
        }
    });
}

if (viewAllAnnouncements) {
    viewAllAnnouncements.addEventListener('click', function () {
        if (announcementsModal) announcementsModal.classList.add('active');
    });
}
if (closeAnnouncementsModal && announcementsModal) {
    closeAnnouncementsModal.addEventListener('click', function () {
        announcementsModal.classList.remove('active');
    });
}
if (announcementsModal) {
    announcementsModal.addEventListener('click', function (e) {
        if (e.target === announcementsModal) {
            announcementsModal.classList.remove('active');
        }
    });
}

// Wire tracker Upload/Re-upload buttons to existing upload modal
document.querySelectorAll('.tracker-btn.upload-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const docType = this.dataset.docType;
        const select  = document.getElementById('docTypeSelect');
        const modal   = document.getElementById('docsModal');
        if (select) {
            select.value = docType;
        }
        if (modal) modal.style.display = 'flex';
    });
});

    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.querySelector(`.notif-dropdown`);
    const viewAllNotif = document.getElementById('viewAllNotif');
    const notifModal = document.getElementById('notifModal');
    const closeNotifModal = document.getElementById('closeNotifModal');

    // Toggle notification dropdown
    if (notifBtn) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
            console.log("Notification button clicked");
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove('active'); // ✅ FIXED
        }
        console.log("Document clicked");
    });

    // Mark notification as read when clicked
    document.querySelectorAll('.notif-item').forEach(item => {
        item.addEventListener('click', async function() {
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
        viewAllNotif.addEventListener('click', function(e) {
            e.preventDefault();
            notifDropdown.classList.remove('active');
            notifModal.classList.add('active');
            console.log("Opening notification modal");
        });
    }

    // Close notification modal
    if (closeNotifModal) {
        closeNotifModal.addEventListener('click', function() {
            notifModal.classList.remove('active');
            console.log("Closing notification modal");
        });
    }

    // Close modal when clicking outside
    if (notifModal) {
        notifModal.addEventListener('click', function(e) {
            if (e.target === notifModal) {
                notifModal.classList.remove('active');
                console.log("Modal closed by clicking outside");
            }
        });
    }
</script>

<?php include __DIR__ . '/logout_confirm.php'; ?>
