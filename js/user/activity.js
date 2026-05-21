// ============================================================
// MEDICAL CERTIFICATE MANAGER
// ============================================================
class MedicalCertificateManager {
  constructor() {
    this.EXPIRY_DAYS_WARNING = 30;
    this.loadCertificateData();
    this.init();
  }

  loadCertificateData() {
    const expiryDate = new Date();
    expiryDate.setDate(expiryDate.getDate() + 15);
    this.certificateData = {
      hasCertificate: true,
      expiryDate: expiryDate.toISOString(),
      uploadedDate: new Date().toISOString(),
      status: 'active'
    };
  }

  init() {
    this.checkExpiry();
    this.setupEventListeners();
    this.updateNotifications();
  }

  checkExpiry() {
    if (!this.certificateData.hasCertificate) return;
    const expiryDate = new Date(this.certificateData.expiryDate);
    const today = new Date();
    const timeDiff = expiryDate.getTime() - today.getTime();
    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
    this.certificateData.daysUntilExpiry = daysDiff;
    this.certificateData.isExpired = daysDiff <= 0;
    this.certificateData.isExpiringSoon = daysDiff > 0 && daysDiff <= this.EXPIRY_DAYS_WARNING;
  }

  updateNotifications() {
    const notifDropdown = document.getElementById('notifDropdown');
    const notifBtn = document.getElementById('notifBtn');
    const defaultNotif = document.getElementById('defaultNotif');
    if (!notifDropdown || !notifBtn) return;

    const notifItems = notifDropdown.querySelectorAll('.notif-item:not(#defaultNotif)');
    notifItems.forEach(item => item.remove());

    let notificationCount = 0;
    let notifications = [];

    if (this.certificateData.hasCertificate) {
      if (this.certificateData.isExpired) {
        notifications.push({
          type: 'urgent', icon: 'fa-exclamation-circle',
          title: 'Medical Certificate Expired!',
          message: `Expired ${Math.abs(this.certificateData.daysUntilExpiry)} days ago. Upload a new one immediately.`,
          time: 'Just now'
        });
        notificationCount++;
      } else if (this.certificateData.isExpiringSoon) {
        notifications.push({
          type: 'warning', icon: 'fa-exclamation-triangle',
          title: 'Medical Certificate Expiring Soon',
          message: `Expires in ${this.certificateData.daysUntilExpiry} days. Please renew it soon.`,
          time: 'Just now'
        });
        notificationCount++;
      }
    } else {
      notifications.push({
        type: 'warning', icon: 'fa-file-medical',
        title: 'Medical Certificate Required',
        message: 'You have not uploaded a medical certificate.',
        time: 'Just now'
      });
      notificationCount++;
    }

    notifications.forEach(notif => {
      const notifItem = document.createElement('div');
      notifItem.className = `notif-item ${notif.type}`;
      notifItem.innerHTML = `
        <i class="fas ${notif.icon}"></i>
        <div class="notif-details-dropdown">
          <strong>${notif.title}</strong>
          <p>${notif.message}</p>
          <span class="notif-time-dropdown">${notif.time}</span>
        </div>
        ${notif.type === 'urgent' ? '<span class="notif-badge">URGENT</span>' : ''}
      `;
      if (defaultNotif) {
        notifDropdown.insertBefore(notifItem, defaultNotif);
      } else {
        notifDropdown.appendChild(notifItem);
      }
    });

    if (notificationCount > 0) {
      notifBtn.setAttribute('data-count', notificationCount);
      notifBtn.classList.remove('no-notif');
      if (defaultNotif) defaultNotif.style.display = 'none';
    } else {
      notifBtn.classList.add('no-notif');
      if (defaultNotif) defaultNotif.style.display = 'flex';
    }
  }

  setupEventListeners() {
    const uploadDocBtn = document.getElementById('uploadDocBtn');
    if (uploadDocBtn) {
      uploadDocBtn.addEventListener('click', () => {
        const docType = document.getElementById('docTypeSelect');
        const fileInput = document.getElementById('docUpload');
        if (!docType || !fileInput) return;
        if (docType.value === 'Medical Certificate' && fileInput.files.length > 0) {
          const expiryDate = new Date();
          expiryDate.setFullYear(expiryDate.getFullYear() + 1);
          this.certificateData = {
            hasCertificate: true,
            expiryDate: expiryDate.toISOString(),
            uploadedDate: new Date().toISOString(),
            status: 'active'
          };
          this.checkExpiry();
          this.updateNotifications();
        }
      });
    }
  }

  refresh() {
    this.checkExpiry();
    this.updateNotifications();
  }
}

// ============================================================
// DOCUMENT PREVIEW MODAL (inject into page)
// ============================================================
function injectPreviewModal() {
  if (document.getElementById('docPreviewModal')) return;

  const modal = document.createElement('div');
  modal.id = 'docPreviewModal';
  modal.style.cssText = `
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.82); align-items:center; justify-content:center;
  `;
  modal.innerHTML = `
    <div style="
      background:#1a1d2e; border-radius:16px; width:92vw; max-width:960px;
      max-height:90vh; display:flex; flex-direction:column;
      box-shadow:0 24px 80px rgba(0,0,0,0.6); overflow:hidden;
      border:1px solid rgba(255,255,255,0.08);
    ">
      <!-- Header -->
      <div style="
        display:flex; align-items:center; justify-content:space-between;
        padding:16px 24px; background:#252840;
        border-bottom:1px solid rgba(255,255,255,0.07);
      ">
        <div style="display:flex; align-items:center; gap:12px;">
          <i class="fas fa-file-alt" style="color:#6c8fff; font-size:18px;"></i>
          <span id="previewDocName" style="color:#fff; font-weight:600; font-size:15px;">Document</span>
          <span id="previewDocStatus" style="
            font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px;
            background:rgba(108,143,255,0.15); color:#6c8fff; letter-spacing:.5px;
          ">APPROVED</span>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
          <a id="previewDownloadBtn" href="#" download style="
            display:flex; align-items:center; gap:7px; padding:8px 16px;
            background:#6c8fff; color:#fff; border-radius:8px; text-decoration:none;
            font-size:13px; font-weight:600; transition:background .2s;
          " onmouseover="this.style.background='#4f6edb'" onmouseout="this.style.background='#6c8fff'">
            <i class="fas fa-download"></i> Download
          </a>
          <button id="closePreviewModal" style="
            background:rgba(255,255,255,0.08); border:none; border-radius:8px;
            color:#aaa; width:36px; height:36px; cursor:pointer; font-size:16px;
            display:flex; align-items:center; justify-content:center; transition:all .2s;
          " onmouseover="this.style.background='rgba(255,255,255,0.15)'"
             onmouseout="this.style.background='rgba(255,255,255,0.08)'">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div id="previewBody" style="flex:1; overflow:auto; padding:0; min-height:400px; position:relative;">
        <!-- Content injected here -->
      </div>

      <!-- Footer meta -->
      <div id="previewMeta" style="
        padding:12px 24px; background:#252840; border-top:1px solid rgba(255,255,255,0.07);
        display:flex; gap:24px; flex-wrap:wrap;
      "></div>
    </div>
  `;

  document.body.appendChild(modal);

  // Close handlers
  document.getElementById('closePreviewModal').addEventListener('click', closePreview);
  modal.addEventListener('click', e => { if (e.target === modal) closePreview(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closePreview(); });
}

function closePreview() {
  const modal = document.getElementById('docPreviewModal');
  if (modal) {
    modal.style.display = 'none';
    document.getElementById('previewBody').innerHTML = '';
  }
}

// ── Determine base URL from the current page path ────────────────────────────
function getBaseUrl() {
  const path = window.location.pathname; // e.g. /hrms/php/admin/dashboard.php
  // Walk up until we're at the project root (where uploads/ lives)
  // Adjust depth if your project structure differs
  const parts = path.split('/').filter(Boolean);
  // Find index of "hrms" or go up 3 levels from current page
  const depth = parts.length - 1; // number of directories above this file
  return '../'.repeat(depth);
}

function openPreview(filePath, docName, status, meta) {
  injectPreviewModal();

  const modal       = document.getElementById('docPreviewModal');
  const body        = document.getElementById('previewBody');
  const nameEl      = document.getElementById('previewDocName');
  const statusEl    = document.getElementById('previewDocStatus');
  const downloadBtn = document.getElementById('previewDownloadBtn');
  const metaEl      = document.getElementById('previewMeta');

  // Full URL to the file
  const base    = getBaseUrl();
  const fileUrl = base + filePath;

  nameEl.textContent  = docName || 'Document';

  // Status pill styling
  const statusColors = {
    approved: { bg: 'rgba(39,174,96,0.15)',  color: '#27ae60', label: 'APPROVED'  },
    pending:  { bg: 'rgba(243,156,18,0.15)', color: '#f39c12', label: 'PENDING'   },
    missing:  { bg: 'rgba(231,76,60,0.15)',  color: '#e74c3c', label: 'MISSING'   },
    rejected: { bg: 'rgba(231,76,60,0.15)',  color: '#e74c3c', label: 'REJECTED'  },
  };
  const sc = statusColors[status] || statusColors.pending;
  statusEl.textContent       = sc.label;
  statusEl.style.background  = sc.bg;
  statusEl.style.color       = sc.color;

  // Download button
  downloadBtn.href     = fileUrl;
  downloadBtn.download = docName || 'document';

  // Meta footer
  metaEl.innerHTML = '';
  if (meta) {
    Object.entries(meta).forEach(([label, value]) => {
      if (!value) return;
      metaEl.innerHTML += `
        <span style="color:#888; font-size:12px;">
          ${label}: <strong style="color:#bbb;">${value}</strong>
        </span>`;
    });
  }

  // Render content by file type
  const ext = filePath.split('.').pop().toLowerCase();
  body.innerHTML = '';

  if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
    // Image preview
    body.innerHTML = `
      <div style="display:flex; align-items:center; justify-content:center; padding:24px; min-height:400px;">
        <img src="${fileUrl}"
             alt="${docName}"
             style="max-width:100%; max-height:70vh; border-radius:8px;
                    box-shadow:0 8px 32px rgba(0,0,0,0.4); object-fit:contain;"
             onerror="this.parentElement.innerHTML='<div style=\'color:#888;text-align:center;padding:60px\'><i class=\'fas fa-image-slash\' style=\'font-size:48px;margin-bottom:12px;display:block;\'></i>Could not load image.<br><a href=\'${fileUrl}\' target=\'_blank\' style=\'color:#6c8fff;\'>Open in new tab →</a></div>'" />
      </div>`;

  } else if (ext === 'pdf') {
    // PDF preview via <iframe>
    body.innerHTML = `
      <iframe src="${fileUrl}"
              style="width:100%; height:70vh; border:none; display:block;"
              title="${docName}">
      </iframe>
      <div style="text-align:center; padding:16px; display:none;" id="pdfFallback">
        <p style="color:#888; margin-bottom:12px;">
          <i class="fas fa-file-pdf" style="color:#e74c3c; font-size:32px; display:block; margin-bottom:8px;"></i>
          Could not render PDF inline.
        </p>
        <a href="${fileUrl}" target="_blank" style="
          color:#6c8fff; text-decoration:none; font-weight:600;
        "><i class="fas fa-external-link-alt"></i> Open PDF in new tab</a>
      </div>`;

    // Fallback if iframe fails to load
    const iframe = body.querySelector('iframe');
    iframe.addEventListener('error', () => {
      iframe.style.display = 'none';
      body.querySelector('#pdfFallback').style.display = 'block';
    });

  } else if (['doc', 'docx'].includes(ext)) {
    // Word docs — use Google Docs viewer
    const googleViewerUrl = `https://docs.google.com/gview?url=${encodeURIComponent(window.location.origin + '/' + filePath)}&embedded=true`;
    body.innerHTML = `
      <iframe src="${googleViewerUrl}"
              style="width:100%; height:70vh; border:none; display:block;"
              title="${docName}">
      </iframe>
      <div style="text-align:center; padding:16px; color:#888; font-size:13px;">
        <i class="fas fa-info-circle"></i>
        If the preview doesn't load,
        <a href="${fileUrl}" target="_blank" style="color:#6c8fff;">download the file</a> to view it.
      </div>`;
  } else {
    // Unsupported — show download prompt
    body.innerHTML = `
      <div style="display:flex; flex-direction:column; align-items:center;
                  justify-content:center; min-height:300px; gap:16px; color:#888;">
        <i class="fas fa-file" style="font-size:56px; color:#6c8fff;"></i>
        <p style="font-size:15px;">This file type cannot be previewed in the browser.</p>
        <a href="${fileUrl}" download style="
          padding:10px 24px; background:#6c8fff; color:#fff; border-radius:8px;
          text-decoration:none; font-weight:600;
        "><i class="fas fa-download"></i> Download to view</a>
      </div>`;
  }

  modal.style.display = 'flex';
}

// ============================================================
// MAIN INIT
// ============================================================
let medicalManager;

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeApp);
} else {
  initializeApp();
}

function initializeApp() {
  medicalManager = new MedicalCertificateManager();

  // ── Inject preview modal early ───────────────────────────
  injectPreviewModal();

  // ── Notification dropdown ────────────────────────────────
  const notifBtn      = document.getElementById('notifBtn');
  const notifDropdown = document.getElementById('notifDropdown');
  const notifModal    = document.getElementById('notifModal');
  const viewAllNotif  = document.getElementById('viewAllNotif');
  const closeNotifModal      = document.getElementById('closeNotifModal');
  const viewAllAnnouncements = document.getElementById('viewAllAnnouncements');
  const viewAllDocs          = document.getElementById('viewAllDocs');
  const docsModalFull        = document.getElementById('docsModalFull');
  const closeDocsModal       = document.getElementById('closeDocsModal');

  if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', () => notifDropdown.classList.toggle('active'));
    document.addEventListener('click', e => {
      if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target))
        notifDropdown.classList.remove('active');
    });
  }

  if (viewAllNotif && notifModal && notifDropdown) {
    viewAllNotif.addEventListener('click', () => {
      notifDropdown.classList.remove('active');
      notifModal.classList.add('active');
    });
  }

  if (viewAllAnnouncements && notifModal)
    viewAllAnnouncements.addEventListener('click', () => notifModal.classList.add('active'));

  if (viewAllDocs && docsModalFull)
    viewAllDocs.addEventListener('click', () => docsModalFull.classList.add('active'));

  if (closeNotifModal && notifModal)
    closeNotifModal.addEventListener('click', () => notifModal.classList.remove('active'));

  if (closeDocsModal && docsModalFull)
    closeDocsModal.addEventListener('click', () => docsModalFull.classList.remove('active'));

  if (notifModal)
    notifModal.addEventListener('click', e => { if (e.target === notifModal) notifModal.classList.remove('active'); });

  if (docsModalFull)
    docsModalFull.addEventListener('click', e => { if (e.target === docsModalFull) docsModalFull.classList.remove('active'); });

  // ── Submit Documents / HR modals ─────────────────────────
  const docsModal    = document.getElementById('docsModal');
  const hrModal      = document.getElementById('hrModal');
  const docsModalBtn = document.getElementById('docsModalBtn');
  const hrModalBtn   = document.getElementById('hrModalBtn');

  if (docsModalBtn && docsModal) docsModalBtn.onclick = () => docsModal.style.display = 'flex';
  if (hrModalBtn   && hrModal)   hrModalBtn.onclick   = () => hrModal.style.display   = 'flex';

  document.querySelectorAll('.close-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const modal = btn.closest('.modal-overlay');
      if (modal) modal.style.display = 'none';
    });
  });

  window.addEventListener('click', e => {
    [docsModal, hrModal].forEach(modal => {
      if (modal && e.target === modal) modal.style.display = 'none';
    });
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      if (notifModal)    notifModal.classList.remove('active');
      if (docsModalFull) docsModalFull.classList.remove('active');
      if (docsModal)     docsModal.style.display = 'none';
      if (hrModal)       hrModal.style.display   = 'none';
      if (notifDropdown) notifDropdown.classList.remove('active');
      closePreview();
    }
  });

  // ── VIEW buttons — open real file in preview modal ───────
  document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const filePath   = this.getAttribute('data-file');
      const docName    = this.getAttribute('data-doc-name')   || 'Document';
      const status     = this.getAttribute('data-status')     || 'pending';
      const uploadDate = this.getAttribute('data-upload-date') || '';
      const expiryDate = this.getAttribute('data-expiry-date') || '';

      if (!filePath) {
        showToast('No file path available for this document.', 'error');
        return;
      }

      openPreview(
        filePath,
        docName,
        status,
        { 'Uploaded': uploadDate, 'Expires': expiryDate }
      );
    });
  });

  // ── DOWNLOAD buttons — now <a> tags, just show a toast ───
  document.querySelectorAll('.download-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const docName = this.getAttribute('download') || 'document';
      showToast(`Downloading "${docName}"…`, 'success');
    });
  });

  // ── UPLOAD buttons — open submit modal with doc type pre-selected
  document.querySelectorAll('.upload-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const docType       = this.getAttribute('data-doc-type');
      const docTypeSelect = document.getElementById('docTypeSelect');

      if (docTypeSelect && docType) docTypeSelect.value = docType;
      if (docsModalFull) docsModalFull.classList.remove('active');
      if (docsModal)     docsModal.style.display = 'flex';
    });
  });

  // ── Notification items — mark as read on click ───────────
  document.querySelectorAll('.notif-item[data-notif-id]').forEach(item => {
    item.addEventListener('click', function () {
      const notifId = this.getAttribute('data-notif-id');
      if (!notifId) return;
      fetch(`../../backendPHP/markNotifRead.php?id=${notifId}`)
        .catch(() => {});
      this.classList.remove('unread');
      const dot = this.querySelector('.unread-dot');
      if (dot) dot.remove();
    });
  });

  // ── Add to Calendar buttons ───────────────────────────────
  document.querySelectorAll('.add-to-calendar-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      const title       = this.getAttribute('data-title') || 'Event';
      const date        = this.getAttribute('data-date')  || new Date().toISOString();
      const description = this.getAttribute('data-content') || '';
      const eventDate   = new Date(date);
      const dateStr     = eventDate.toLocaleDateString('en-US', { month:'long', day:'numeric', year:'numeric' });
      showToast(`"${title}" added to your calendar for ${dateStr}`, 'success');
    });
  });

  // ── Periodic expiry check ─────────────────────────────────
  setInterval(() => { if (medicalManager) medicalManager.refresh(); }, 60000);

  console.log('Activity page initialized ✓');
}

// ============================================================
// TOAST HELPER
// ============================================================
function showToast(message, type = 'info') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = `
      position:fixed; bottom:24px; right:24px; z-index:99999;
      display:flex; flex-direction:column; gap:10px; pointer-events:none;
    `;
    document.body.appendChild(container);
  }

  const colors = {
    success: '#27ae60',
    error:   '#e74c3c',
    info:    '#6c8fff',
    warning: '#f39c12',
  };

  const toast = document.createElement('div');
  toast.style.cssText = `
    background:#1a1d2e; color:#fff; padding:12px 20px; border-radius:10px;
    border-left:4px solid ${colors[type] || colors.info};
    box-shadow:0 8px 24px rgba(0,0,0,0.35); font-size:14px;
    pointer-events:all; max-width:340px;
    animation: slideInRight .3s ease;
  `;

  const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
  toast.innerHTML = `<i class="fas ${icons[type] || icons.info}" style="color:${colors[type] || colors.info}; margin-right:8px;"></i>${message}`;

  container.appendChild(toast);
  setTimeout(() => {
    toast.style.transition = 'opacity .4s, transform .4s';
    toast.style.opacity    = '0';
    toast.style.transform  = 'translateX(20px)';
    setTimeout(() => toast.remove(), 400);
  }, 3500);
}

// Inject toast animation
const style = document.createElement('style');
style.textContent = `@keyframes slideInRight { from { opacity:0; transform:translateX(40px); } to { opacity:1; transform:translateX(0); } }`;
document.head.appendChild(style);