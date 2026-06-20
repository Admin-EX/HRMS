<div id="logoutConfirmModal" class="logout-confirm-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; z-index:120000;">
    <div class="logout-confirm-modal" style="background:#fff; border-radius:12px; max-width:420px; width:90%; padding:22px; box-shadow:0 16px 40px rgba(0,0,0,0.18);">
        <h3 style="margin:0 0 12px; font-size:1.2rem;">Confirm logout</h3>
        <p style="margin:0 0 20px; color:#444;">Are you sure you want to log out?</p>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button id="cancelLogoutBtn" type="button" style="padding:10px 14px; border:none; border-radius:8px; background:#e0e7ff; color:#1f3c88; cursor:pointer; font-weight:600;">Cancel</button>
            <button id="confirmLogoutBtn" type="button" style="padding:10px 14px; border:none; border-radius:8px; background:#e74c3c; color:#fff; cursor:pointer; font-weight:600;">Log Out</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const logoutLinks = Array.from(document.querySelectorAll('a[href="logout.php"]'));
        const modal = document.getElementById('logoutConfirmModal');
        const cancelBtn = document.getElementById('cancelLogoutBtn');
        const confirmBtn = document.getElementById('confirmLogoutBtn');
        let targetHref = 'logout.php';

        if (!modal || !cancelBtn || !confirmBtn || logoutLinks.length === 0) return;

        logoutLinks.forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                targetHref = this.href;
                modal.style.display = 'flex';
            });
        });

        cancelBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });

        confirmBtn.addEventListener('click', function () {
            window.location.href = targetHref;
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        });
    });
</script>
