document.addEventListener('DOMContentLoaded', function () {
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
});