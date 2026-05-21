/**
 * Notification Functionality
 * Handles notification dropdown, real-time updates, and mark as read
 */

class NotificationManager {
    constructor() {
        this.notificationBtn = document.getElementById('notificationBtn');
        this.notificationDropdown = document.getElementById('notificationDropdown');
        this.notificationList = document.getElementById('notificationList');
        this.markAllReadBtn = document.getElementById('markAllRead');
        this.viewAllBtn = document.getElementById('viewAllNotifications');
        
        this.init();
    }
    
    init() {
        // Toggle dropdown on click
        if (this.notificationBtn) {
            this.notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (this.notificationDropdown && 
                !this.notificationBtn.contains(e.target) && 
                !this.notificationDropdown.contains(e.target)) {
                this.closeDropdown();
            }
        });
        
        // Mark all as read
        if (this.markAllReadBtn) {
            this.markAllReadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.markAllAsRead();
            });
        }
        
        // View all notifications
        if (this.viewAllBtn) {
            this.viewAllBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.viewAllNotifications();
            });
        }
        
        // Load notifications on init
        this.loadNotifications();
        
        // Poll for new notifications every 30 seconds
        setInterval(() => this.loadNotifications(), 30000);
    }
    
    toggleDropdown() {
        if (this.notificationDropdown) {
            this.notificationDropdown.classList.toggle('active');
            
            // Load notifications when opening
            if (this.notificationDropdown.classList.contains('active')) {
                this.loadNotifications();
            }
        }
    }
    
    closeDropdown() {
        if (this.notificationDropdown) {
            this.notificationDropdown.classList.remove('active');
        }
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('../../backendPHP/fetch_notifications.php');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.displayNotifications(data.notifications);
                this.updateBadge(data.unread_count);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.showErrorState();
        }
    }
    
    displayNotifications(notifications) {
        if (!this.notificationList) return;
        
        if (!notifications || notifications.length === 0) {
            this.showEmptyState();
            return;
        }
        
        this.notificationList.innerHTML = '';
        
        notifications.forEach(notif => {
            const item = this.createNotificationItem(notif);
            this.notificationList.appendChild(item);
        });
    }
    
    createNotificationItem(notif) {
        const div = document.createElement('div');
        div.className = `notification-item ${notif.read_status === 'unread' ? 'unread' : ''}`;
        div.dataset.id = notif.id;
        
        // Determine icon class based on type
        let iconClass = 'info';
        if (notif.type === 'warning') iconClass = 'warning';
        if (notif.type === 'success') iconClass = 'success';
        if (notif.type === 'error' || notif.type === 'danger') iconClass = 'danger';
        
        div.innerHTML = `
            <div class="notif-icon ${iconClass}">
                <i class="${this.getIconForType(notif.type)}"></i>
            </div>
            <div class="notif-content">
                <h5>${this.escapeHtml(notif.title)}</h5>
                <p>${this.escapeHtml(notif.message)}</p>
                <div class="notif-time">${this.timeAgo(notif.created_at)}</div>
            </div>
        `;
        
        // Add click handler
        div.addEventListener('click', () => {
            this.markAsRead(notif.id);
            if (notif.action_url) {
                window.location.href = notif.action_url;
            }
        });
        
        return div;
    }
    
    getIconForType(type) {
        const icons = {
            'info': 'fas fa-info-circle',
            'warning': 'fas fa-exclamation-triangle',
            'success': 'fas fa-check-circle',
            'error': 'fas fa-times-circle',
            'danger': 'fas fa-times-circle',
            'document': 'fas fa-file-alt',
            'leave': 'fas fa-calendar-alt',
            'approval': 'fas fa-clipboard-check'
        };
        
        return icons[type] || 'fas fa-bell';
    }
    
    showEmptyState() {
        if (!this.notificationList) return;
        
        this.notificationList.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <h5>No new notifications</h5>
                <p>You're all caught up!</p>
            </div>
        `;
    }
    
    showErrorState() {
        if (!this.notificationList) return;
        
        this.notificationList.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-exclamation-triangle"></i>
                <h5>Error loading notifications</h5>
                <p>Please try again later</p>
            </div>
        `;
    }
    
    updateBadge(count) {
        const badge = this.notificationBtn?.querySelector('.notif-badge');
        
        if (count > 0) {
            if (badge) {
                badge.textContent = count > 99 ? '99+' : count;
            } else {
                // Create badge if it doesn't exist
                const newBadge = document.createElement('span');
                newBadge.className = 'notif-badge';
                newBadge.textContent = count > 99 ? '99+' : count;
                this.notificationBtn?.appendChild(newBadge);
            }
        } else {
            // Remove badge if count is 0
            if (badge) {
                badge.remove();
            }
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch('../../backendPHP/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI
                const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread');
                }
                
                // Reload notifications to update badge
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('../../backendPHP/mark_all_notifications_read.php', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reload notifications
                this.loadNotifications();
                
                // Show success message
                this.showToast('All notifications marked as read', 'success');
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
            this.showToast('Error marking notifications as read', 'error');
        }
    }
    
    viewAllNotifications() {
        // Redirect to notifications page or show modal
        window.location.href = 'notifications.php';
    }
    
    timeAgo(datetime) {
        const date = new Date(datetime);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };
        
        for (const [name, value] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / value);
            if (interval >= 1) {
                return interval === 1 ? `1 ${name} ago` : `${interval} ${name}s ago`;
            }
        }
        
        return 'Just now';
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showToast(message, type = 'info') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#3498db'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize notification manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new NotificationManager();
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);