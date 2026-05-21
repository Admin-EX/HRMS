<?php
// topbar.php - Reusable topbar component with notifications
// Assumes $connection is available and session is started

$unread_count = 0;
$notifications = [];
$admin_id = $_SESSION["loggedUser"];
if ($connection->query("SHOW TABLES LIKE 'activity_log'")->num_rows > 0) {
    // Count unread notifications
    $unread_query = "SELECT COUNT(*) as unread_count FROM activity_log 
                     WHERE employee_number = '$admin_id' AND read_status = 'unread'";
    $unread_result = $connection->query($unread_query);
    if ($unread_result) {
        $unread_row = $unread_result->fetch_assoc();
        $unread_count = $unread_row['unread_count'] ?? 0;
    }
    
    // Fetch latest 10 notifications
    $notif_query = "SELECT id, employee_number, title, content, url, read_status, date 
                    FROM activity_log WHERE employee_number = '$admin_id'
                    ORDER BY date DESC 
                    LIMIT 10";
    $notif_result = $connection->query($notif_query);
    if ($notif_result) {
        while ($row = $notif_result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
}

// Page title and description can be passed as variables
$page_title = $page_title ?? '';
$page_description = $page_description ?? 'Welcome to BTech HRMS';
?>

<!-- TOP BAR -->
<header class="topbar">
    <div class="top-left">
        <h2><?php echo htmlspecialchars($page_title); ?></h2>
        <p><?php echo htmlspecialchars($page_description); ?></p>
    </div>
    <div class="top-right">
        <div class="notification" id="notificationBtn">
            <i class="fas fa-bell"></i>
            <?php if ($unread_count > 0): ?>
                <span class="notif-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Notification Dropdown -->
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
                <h4>Notifications</h4>
                <a href="#" id="markAllRead">Mark all as read</a>
            </div>
            <div class="notification-list" id="notificationList">
                <?php if (empty($notifications)): ?>
                    <div class="notification-item">
                        <div class="notif-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="notif-content">
                            <h5>No notifications</h5>
                            <p>You're all caught up!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?php echo $notif['read_status'] === 'unread' ? 'unread' : ''; ?>" 
                             data-id="<?php echo $notif['id']; ?>"
                             data-url=".<?php echo htmlspecialchars($notif['url']); ?>">
                            <div class="notif-icon">
                                <?php
                                // Determine icon based on title
                                $icon = 'fa-bell';
                                if (strpos($notif['title'], 'Calendar') !== false) {
                                    $icon = 'fa-calendar';
                                } elseif (strpos($notif['title'], 'Document') !== false || strpos($notif['title'], 'Offset') !== false || strpos($notif['title'], 'Leave') !== false || strpos($notif['title'], 'Required') !== false) {
                                    $icon = 'fa-file-alt';
                                } elseif (strpos($notif['title'], 'schedule') !== false) {
                                    $icon = 'fa-clock';
                                }
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="notif-content">
                                <h5><?php echo htmlspecialchars($notif['title']); ?></h5>
                                <p><?php echo htmlspecialchars($notif['content']); ?></p>
                                <span class="notif-time">
                                    <?php 
                                    $time_ago = time() - strtotime($notif['date']);
                                    if ($time_ago < 60) {
                                        echo 'Just now';
                                    } elseif ($time_ago < 3600) {
                                        echo floor($time_ago / 60) . ' min ago';
                                    } elseif ($time_ago < 86400) {
                                        echo floor($time_ago / 3600) . ' hr ago';
                                    } elseif ($time_ago < 604800) {
                                        echo floor($time_ago / 86400) . ' days ago';
                                    } else {
                                        echo date('M d, Y', strtotime($notif['date']));
                                    }
                                    ?>
                                </span>
                            </div>
                            <?php if ($notif['read_status'] === 'unread'): ?>
                                <div class="unread-dot"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="notification-footer">
                <a href="notifications.php" id="viewAllNotifications">View All Notifications</a>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark individual notification as read and navigate when clicked
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notifId = this.dataset.id;
            const notifUrl = this.dataset.url;
            
            // Only process if there's a valid notification ID
            if (!notifId) return;
            
            // Mark as read
            fetch('mark_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'mark_single_read',
                    id: notifId 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Navigate to URL if it exists
                    if (notifUrl && notifUrl.trim() !== '') {
                        window.location.href = notifUrl;
                    }
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
                // Navigate anyway even if marking as read fails
                if (notifUrl && notifUrl.trim() !== '') {
                    window.location.href = notifUrl;
                }
            });
        });
    });
});
</script>