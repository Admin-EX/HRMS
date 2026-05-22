// ============================================================
// LEAVE TAB - State & DOM
// ============================================================
let leaveRequests = [];
let leaveLimits = {};
let employeeLeaveUsage = {};
let currentFilter = 'all';
let currentSearch = '';
let currentLeave = null;

const tableBody             = document.getElementById('tableBody');
const searchInput           = document.getElementById('searchInput');
const filterTabs            = document.querySelectorAll('.filter-tab');
const pendingRequestsEl     = document.getElementById('pendingRequests');
const approvedRequestsEl    = document.getElementById('approvedRequests');
const deniedRequestsEl      = document.getElementById('deniedRequests');
const autoRejectedEl        = document.getElementById('autoRejected');
const paginationInfo        = document.getElementById('paginationInfo');
const leaveLimitAlert       = document.getElementById('leaveLimitAlert');
const viewAutoRejectedBtn   = document.getElementById('viewAutoRejected');
const configureLimitsBtn    = document.getElementById('configureLimits');
const leaveDetailsModal     = document.getElementById('leaveDetailsModal');
const closeDetailsModal     = document.getElementById('closeDetailsModal');
const closeModalBtn         = document.getElementById('closeModalBtn');
const approveLeaveBtn       = document.getElementById('approveLeaveBtn');
const denyLeaveBtn          = document.getElementById('denyLeaveBtn');
const overrideLimitBtn      = document.getElementById('overrideLimitBtn');
const leaveDetailsContent   = document.getElementById('leaveDetailsContent');
const leaveLimitInfo        = document.getElementById('leaveLimitInfo');
const signaturePreview      = document.getElementById('signaturePreview');

// Toast helper (same behaviour as dashboard): shows brief notifications
function showToast(message, type = 'info', duration = 3000) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        Object.assign(container.style, {
            position: 'fixed', right: '20px', top: '20px', zIndex: 99999,
            display: 'flex', flexDirection: 'column', gap: '10px', alignItems: 'flex-end'
        });
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast-message';
    toast.textContent = message;
    const bg = type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#34495e';
    Object.assign(toast.style, {
        background: bg, color: '#fff', padding: '10px 14px', borderRadius: '6px',
        boxShadow: '0 6px 18px rgba(0,0,0,0.12)', opacity: '0', transform: 'translateY(-8px)',
        transition: 'opacity 220ms ease, transform 220ms ease', maxWidth: '360px', fontSize: '14px'
    });

    container.appendChild(toast);
    requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; });
    setTimeout(() => {
        toast.style.opacity = '0'; toast.style.transform = 'translateY(-8px)';
        setTimeout(() => container.removeChild(toast), 300);
    }, duration);
}

// ============================================================
// OFFSET TAB - State
// ============================================================
let offsetRecords       = [];
let offsetSearchTerm    = '';
let currentOffset       = null;

// ============================================================
// REQUEST TAB - State
// ============================================================
let requestForms        = [];
let requestSearchTerm   = '';
let currentRequestType  = 'all';
let currentRequest      = null;

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    loadLeaveLimits();
    loadLeaveRequests();
    setupLeaveEventListeners();
    setupOffsetEventListeners();
    setupRequestEventListeners();
});

// ============================================================
// LEAVE - LOAD DATA
// ============================================================
function loadLeaveLimits() {
    fetch('../../backendPHP/admin_leave/get_leave_limits.php')
        .then(r => r.json())
        .then(data => { leaveLimits = data; })
        .catch(() => {
            leaveLimits = {
                "Sick Leave":       { maxDays: 10,  description: "Per year" },
                "Vacation Leave":   { maxDays: 15,  description: "Per year" },
                "Emergency Leave":  { maxDays: 5,   description: "Per year" },
                "Maternity Leave":  { maxDays: 105, description: "Per pregnancy" },
                "Paternity Leave":  { maxDays: 7,   description: "Per child" }
            };
        });
}

function loadLeaveRequests() {
    tableBody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">Loading leave requests...</td></tr>';
    fetch('../../backendPHP/admin_leave/get_leave_request.php')
        .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
        .then(data => {
            if (data.error) throw new Error(data.error);
            leaveRequests = data.requests;
            employeeLeaveUsage = data.leaveUsage;
            autoRejectExceedingRequests();
            updateLeaveStats();
            renderLeaveTable();
        })
        .catch(err => {
            tableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:20px;color:red;">Error: ${err.message}</td></tr>`;
        });
}

function updateLeaveStats() {
    pendingRequestsEl.textContent  = leaveRequests.filter(r => r.status === 'pending').length;
    approvedRequestsEl.textContent = leaveRequests.filter(r => r.status === 'approved').length;
    deniedRequestsEl.textContent   = leaveRequests.filter(r => r.status === 'rejected').length;
    autoRejectedEl.textContent     = leaveRequests.filter(r => r.status === 'auto_rejected').length;
}

// ============================================================
// LEAVE - LOGIC
// ============================================================
function checkLeaveLimit(request) {
    if (!request.checkLimit) return { exceeds: false };
    const empId = request.employee.id;
    const type  = request.leaveType;
    if (!employeeLeaveUsage[empId] || !employeeLeaveUsage[empId][type]) return { exceeds: false };
    const remaining = employeeLeaveUsage[empId][type].remaining;
    const used      = employeeLeaveUsage[empId][type].used;
    const limit     = leaveLimits[type] ? leaveLimits[type].maxDays : 0;
    if (request.days > remaining) {
        return { exceeds: true, requestedDays: request.days, remainingDays: remaining, usedDays: used, limit,
            message: `Employee has only ${remaining} ${type} days remaining. Requested: ${request.days} days. Limit: ${limit} days/year.` };
    }
    return { exceeds: false, remainingDays: remaining };
}

function autoRejectExceedingRequests() {
    let changed = false;
    leaveRequests.forEach(req => {
        if (req.status === 'pending' && req.checkLimit) {
            const check = checkLeaveLimit(req);
            if (check.exceeds) {
                req.status = 'auto_rejected';
                req.autoRejected = true;
                req.rejectionReason = `AUTO-REJECTED: ${check.message}`;
                changed = true;
                updateLeaveStatus(req.id, 'auto_rejected', check.message);
            }
        }
    });
    if (changed) { updateLeaveStats(); renderLeaveTable(); }
}

function updateLeaveStatus(requestId, status, reason = '') {
    const fd = new FormData();
    fd.append('request_id', requestId);
    fd.append('status', status);
    fd.append('reason', reason);
    fetch('../../backendPHP/admin_leave/update_leave_status.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => { if (!d.success) console.error('Error updating status:', d.message); })
        .catch(err => console.error('Error updating leave status:', err));
}

function filterLeaveData() {
    let filtered = [...leaveRequests];
    if (currentSearch) {
        const s = currentSearch.toLowerCase();
        filtered = filtered.filter(r =>
            r.employee.name.toLowerCase().includes(s) ||
            r.employee.id.toLowerCase().includes(s) ||
            r.employee.department.toLowerCase().includes(s) ||
            r.id.toLowerCase().includes(s)
        );
    }
    if (currentFilter !== 'all') {
        filtered = filtered.filter(r => r.status === currentFilter);
    }
    return filtered;
}

function formatDate(ds) {
    if (!ds) return 'N/A';
    return new Date(ds).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getStatusBadge(status, autoRejected = false) {
    if (autoRejected || status === 'auto_rejected')
        return '<span class="status-badge auto-rejected-badge"><i class="fas fa-robot"></i> Auto-Rejected</span>';
    const map = {
        pending:  '<span class="status-badge pending-badge">Pending</span>',
        approved: '<span class="status-badge approved-badge">Approved</span>',
        denied:   '<span class="status-badge denied-badge">Denied</span>',
    };
    return map[status] || '';
}

function getSignatureBadge(sig) {
    return sig === 'verified'
        ? '<span class="signature-badge verified-badge"><i class="fas fa-check-circle"></i> Verified</span>'
        : '<span class="signature-badge pending-sign-badge"><i class="fas fa-clock"></i> Pending</span>';
}

function getLeaveBalanceDisplay(request) {
    const empId = request.employee.id;
    const type  = request.leaveType;
    if (!employeeLeaveUsage[empId] || !employeeLeaveUsage[empId][type])
        return '<span style="color:#666;">Not tracked</span>';
    const used      = employeeLeaveUsage[empId][type].used;
    const remaining = employeeLeaveUsage[empId][type].remaining;
    const limit     = leaveLimits[type] ? leaveLimits[type].maxDays : 0;
    const pct       = (used / limit) * 100;
    let cls = 'progress-safe';
    if (pct >= 80) cls = 'progress-danger';
    else if (pct >= 60) cls = 'progress-warning';
    return `<div class="limit-info">
        <span>${remaining}/${limit} days</span>
        <div class="limit-progress"><div class="limit-progress-bar ${cls}" style="width:${pct}%"></div></div>
    </div>`;
}

function renderLeaveTable() {
    const filtered = filterLeaveData();
    tableBody.innerHTML = '';
    if (filtered.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:20px;">No leave requests found</td></tr>';
        paginationInfo.textContent = 'Showing 0 to 0 of 0 results';
        return;
    }
    filtered.forEach(req => {
        const check       = checkLeaveLimit(req);
        const overLimit   = check.exceeds && req.status === 'pending';
        const row         = document.createElement('tr');
        if (overLimit) row.style.background = '#fff5f5';
        row.innerHTML = `
            <td><strong>${req.id}</strong></td>
            <td>
                <div class="employee-cell">
                    <div class="employee-avatar">${req.employee.avatar}</div>
                    <div class="employee-info">
                        <div class="employee-name">${req.employee.name}</div>
                        <div class="employee-id">${req.employee.id}</div>
                        <div class="employee-dept">${req.employee.department}</div>
                    </div>
                </div>
            </td>
            <td>${req.leaveType}</td>
            <td>${req.dateRange}</td>
            <td><strong>${req.days} days</strong></td>
            <td>${getLeaveBalanceDisplay(req)}</td>
            <td>${getSignatureBadge(req.signature)}</td>
            <td>${getStatusBadge(req.status, req.autoRejected)}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn view-btn view-details-btn" data-id="${req.id}" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${req.status === 'pending' ? (overLimit ? `
                        <button class="action-btn override-btn override-limit-btn" data-id="${req.id}" title="Override Limit">
                            <i class="fas fa-unlock"></i>
                        </button>
                    ` : `
                        <button class="action-btn approve-btn approve-request-btn" data-id="${req.id}" title="Approve">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="action-btn deny-btn deny-request-btn" data-id="${req.id}" title="Deny">
                            <i class="fas fa-times"></i>
                        </button>
                    `) : ''}
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
    paginationInfo.textContent = `Showing 1 to ${filtered.length} of ${filtered.length} results`;
    addLeaveTableListeners();
}

function addLeaveTableListeners() {
    document.querySelectorAll('.view-details-btn').forEach(btn =>
        btn.addEventListener('click', () => showLeaveDetails(btn.dataset.id)));
    document.querySelectorAll('.approve-request-btn').forEach(btn =>
        btn.addEventListener('click', () => approveLeaveRequest(btn.dataset.id)));
    document.querySelectorAll('.deny-request-btn').forEach(btn =>
        btn.addEventListener('click', () => denyLeaveRequest(btn.dataset.id)));
    document.querySelectorAll('.override-limit-btn').forEach(btn =>
        btn.addEventListener('click', () => overrideLeaveLimit(btn.dataset.id)));
}

function showLeaveDetails(requestId) {
    currentLeave = leaveRequests.find(r => r.id === requestId);
    if (!currentLeave) return;
    const check      = checkLeaveLimit(currentLeave);
    const overLimit  = check.exceeds && currentLeave.status === 'pending';

    leaveDetailsContent.innerHTML = `
        <div class="detail-item"><span class="detail-label">Request ID</span>
            <div class="detail-value ${overLimit ? 'danger' : ''}">${currentLeave.id}</div></div>
        <div class="detail-item"><span class="detail-label">Employee</span>
            <div class="detail-value">${currentLeave.employee.name} (${currentLeave.employee.id})</div></div>
        <div class="detail-item"><span class="detail-label">Department</span>
            <div class="detail-value">${currentLeave.employee.department}</div></div>
        <div class="detail-item"><span class="detail-label">Leave Type</span>
            <div class="detail-value ${overLimit ? 'danger' : ''}">${currentLeave.leaveType}</div></div>
        <div class="detail-item"><span class="detail-label">Date Range</span>
            <div class="detail-value">${currentLeave.dateRange}</div></div>
        <div class="detail-item"><span class="detail-label">Duration</span>
            <div class="detail-value ${overLimit ? 'danger' : ''}">${currentLeave.days} day${currentLeave.days > 1 ? 's' : ''}</div></div>
        <div class="detail-item"><span class="detail-label">Reason</span>
            <div class="detail-value">${currentLeave.reason}</div></div>
        <div class="detail-item"><span class="detail-label">Contact During Leave</span>
            <div class="detail-value">${currentLeave.contactDuringLeave}</div></div>
        <div class="detail-item"><span class="detail-label">Submitted Date</span>
            <div class="detail-value">${formatDate(currentLeave.submittedDate)}</div></div>
        ${currentLeave.status === 'approved' ? `
        <div class="detail-item"><span class="detail-label">Approved By</span>
            <div class="detail-value">${currentLeave.approvedBy || 'Admin'}</div></div>
        <div class="detail-item"><span class="detail-label">Approval Date</span>
            <div class="detail-value">${formatDate(currentLeave.approvedDate)}</div></div>
        ` : ''}
        ${currentLeave.status === 'denied' ? `
        <div class="detail-item"><span class="detail-label">Denied By</span>
            <div class="detail-value">${currentLeave.deniedBy || 'Admin'}</div></div>
        <div class="detail-item"><span class="detail-label">Denial Reason</span>
            <div class="detail-value">${currentLeave.denialReason}</div></div>
        ` : ''}
        ${currentLeave.status === 'auto_rejected' ? `
        <div class="detail-item"><span class="detail-label">Rejection Reason</span>
            <div class="detail-value danger">${currentLeave.rejectionReason}</div></div>
        ` : ''}
    `;

    // Leave limit info
    const empId = currentLeave.employee.id;
    const type  = currentLeave.leaveType;
    let limitHTML = '';
    if (employeeLeaveUsage[empId] && leaveLimits[type]) {
        const limit     = leaveLimits[type].maxDays;
        const used      = (employeeLeaveUsage[empId][type] || {}).used || 0;
        const remaining = (employeeLeaveUsage[empId][type] || {}).remaining || limit;
        const pct       = (used / limit) * 100;
        let barClass = pct >= 80 ? 'danger' : pct >= 60 ? 'warning' : 'safe';
        limitHTML = `
            <div class="leave-limit-title"><i class="fas fa-chart-bar"></i> Leave Balance for ${currentLeave.employee.name}</div>
            <div class="limit-bars">
                <div class="limit-bar">
                    <div class="limit-label">${type}:</div>
                    <div class="limit-bar-progress"><div class="limit-bar-fill ${barClass}" style="width:${pct}%"></div></div>
                    <div class="limit-text">${used}/${limit} days</div>
                </div>
            </div>
            ${overLimit ? `<div style="margin-top:15px;padding:15px;background:#ffebee;border-radius:8px;border-left:4px solid #e74c3c;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                    <i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i>
                    <strong style="color:#c62828;">Leave Limit Exceeded!</strong>
                </div>
                <p style="margin:0;color:#c62828;font-size:14px;">Employee has only <strong>${remaining}</strong> days remaining. Requested: <strong>${currentLeave.days}</strong> days.</p>
            </div>` : ''}
        `;
    }
    leaveLimitInfo.innerHTML = limitHTML;

    signaturePreview.innerHTML = `<div style="text-align:center;">
        <i class="fas fa-signature" style="font-size:48px;color:#3498db;margin-bottom:10px;"></i>
        <p style="color:#666;margin:10px 0;">${currentLeave.employee.name}</p>
        <p style="color:#999;font-size:12px;">Submitted: ${formatDate(currentLeave.submittedDate)}</p>
        <div style="margin-top:15px;padding:10px;background:#f8f9fa;border-radius:5px;">
            ${currentLeave.signature === 'verified'
                ? '<span class="signature-badge verified-badge"><i class="fas fa-check-circle"></i> Wet Signature Verified</span>'
                : '<span class="signature-badge pending-sign-badge"><i class="fas fa-clock"></i> Awaiting Signature</span>'}
        </div>
    </div>`;

    approveLeaveBtn.style.display  = currentLeave.status === 'pending' && !overLimit ? 'inline-flex' : 'none';
    denyLeaveBtn.style.display     = currentLeave.status === 'pending' ? 'inline-flex' : 'none';
    overrideLimitBtn.style.display = currentLeave.status === 'pending' && overLimit ? 'inline-flex' : 'none';

    leaveDetailsModal.style.display = 'flex';
}

function approveLeaveRequest(requestId) {
    const req = leaveRequests.find(r => r.id === requestId);
    if (!req) return;
    const check = checkLeaveLimit(req);
    // replaced native confirm with modal-based confirmation
    // async modal handled below via showConfirm
    (async () => {
        if (check.exceeds) {
            const ok = await showConfirm(`Warning: This request exceeds leave limit!\n\n${check.message}\n\nApprove anyway?`);
            if (!ok) return;
        }
        const ok2 = await showConfirm(`Approve leave request ${requestId} for ${req.employee.name}?`);
        if (!ok2) return;

        const fd = new FormData();
        fd.append('request_id', requestId); fd.append('status', 'approved');
        fd.append('employee_id', req.employee.id); fd.append('leave_type', req.leaveType); fd.append('days', req.days);
        fetch('../../backendPHP/admin_leave/approve_leave.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d.success) {
                            req.status = 'approved'; req.approvedBy = 'Admin'; req.approvedDate = today();
                            if (employeeLeaveUsage[req.employee.id]?.[req.leaveType]) {
                                employeeLeaveUsage[req.employee.id][req.leaveType].used += req.days;
                                employeeLeaveUsage[req.employee.id][req.leaveType].remaining -= req.days;
                            }
                            updateLeaveStats(); renderLeaveTable();
                            leaveDetailsModal.style.display = 'none'; currentLeave = null;
                            showToast(`Leave request ${requestId} approved.`, 'success');
                        } else showToast('Error: ' + d.message, 'error');
                    }).catch(() => showToast('Error approving leave request', 'error'));
    })();
}

function denyLeaveRequest(requestId) {
    const req = leaveRequests.find(r => r.id === requestId);
    if (!req) return;
    (async () => {
        const input = await showConfirm('Please enter reason for denial:', { showInput: true, inputPlaceholder: 'Denial reason' });
        if (!input) return;
        const fd = new FormData();
        fd.append('request_id', requestId); fd.append('status', 'denied'); fd.append('reason', input);
        fetch('../../backendPHP/admin_leave/update_leave_status.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d.success) {
                    req.status = 'denied'; req.deniedBy = 'Admin'; req.deniedDate = today(); req.denialReason = input;
                    updateLeaveStats(); renderLeaveTable();
                    leaveDetailsModal.style.display = 'none'; currentLeave = null;
                    showToast(`Leave request ${requestId} denied.`, 'success');
                } else showToast('Error: ' + d.message, 'error');
            }).catch(() => showToast('Error denying leave request', 'error'));
    })();
}

function overrideLeaveLimit(requestId) {
    const req = leaveRequests.find(r => r.id === requestId);
    if (!req) return;
    const check = checkLeaveLimit(req);
    (async () => {
        const OReason = await showConfirm(`Enter reason for overriding leave limit:\n\n${check.message}\n\nOverride Reason:`, { showInput: true, inputPlaceholder: 'Override reason' });
        if (!OReason) return;
        const ok = await showConfirm(`Override limit and approve ${requestId}?\n\nReason: ${OReason}`);
        if (!ok) return;
        const fd = new FormData();
        fd.append('request_id', requestId); fd.append('status', 'approved');
        fd.append('employee_id', req.employee.id); fd.append('leave_type', req.leaveType);
        fd.append('days', req.days); fd.append('override_reason', OReason);
        fetch('../../backendPHP/admin_leave/approve_leave.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d.success) {
                    req.status = 'approved'; req.approvedBy = 'Admin (Override)'; req.approvedDate = today();
                    if (employeeLeaveUsage[req.employee.id]?.[req.leaveType]) {
                        employeeLeaveUsage[req.employee.id][req.leaveType].used += req.days;
                        employeeLeaveUsage[req.employee.id][req.leaveType].remaining -= req.days;
                    }
                    updateLeaveStats(); renderLeaveTable();
                    leaveDetailsModal.style.display = 'none'; currentLeave = null;
                    showToast(`Leave request ${requestId} approved with limit override.`, 'success');
                } else showToast('Error: ' + d.message, 'error');
            }).catch(() => showToast('Error overriding leave request', 'error'));
    })();
}

function today() { return new Date().toISOString().split('T')[0]; }

function setupLeaveEventListeners() {
    approveLeaveBtn.addEventListener('click', () => { if (currentLeave) approveLeaveRequest(currentLeave.id); });
    denyLeaveBtn.addEventListener('click',    () => { if (currentLeave) denyLeaveRequest(currentLeave.id); });
    overrideLimitBtn.addEventListener('click',() => { if (currentLeave) overrideLeaveLimit(currentLeave.id); });
    closeDetailsModal.addEventListener('click', () => { leaveDetailsModal.style.display = 'none'; currentLeave = null; });
    closeModalBtn.addEventListener('click',     () => { leaveDetailsModal.style.display = 'none'; currentLeave = null; });
    leaveDetailsModal.addEventListener('click', e => { if (e.target === leaveDetailsModal) { leaveDetailsModal.style.display = 'none'; currentLeave = null; } });

    viewAutoRejectedBtn?.addEventListener('click', () => {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        document.querySelector('[data-status="auto_rejected"]')?.classList.add('active');
        currentFilter = 'auto_rejected';
        renderLeaveTable();
    });
    configureLimitsBtn?.addEventListener('click', () => {
        let txt = 'Leave Limit Configuration\n\n';
        for (const t in leaveLimits) txt += `${t}: ${leaveLimits[t].maxDays} ${leaveLimits[t].description}\n`;
        showToast(txt, 'info', 5000);
    });
    searchInput?.addEventListener('input', () => { currentSearch = searchInput.value; renderLeaveTable(); });
    filterTabs.forEach(tab => tab.addEventListener('click', function () {
        filterTabs.forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.status;
        renderLeaveTable();
    }));
    document.getElementById('leaveExportBtn')?.addEventListener('click', exportLeaveReport);
}

function buildCSV(headers, rows) {
    const escapeCell = value => {
        if (value === null || value === undefined) return '';
        const text = String(value).replace(/"/g, '""');
        return text.includes(',') || text.includes('"') || text.includes('\n') ? `"${text}"` : text;
    };
    const lines = [headers.map(escapeCell).join(',')];
    rows.forEach(row => {
        lines.push(headers.map(header => escapeCell(row[header] ?? '')).join(','));
    });
    return lines.join('\r\n');
}

function downloadCSV(filename, content) {
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportLeaveReport() {
    const status = currentFilter || 'all';
    const search = currentSearch ? encodeURIComponent(currentSearch) : '';
    const url = `../../backendPHP/export_leave_report.php?status=${encodeURIComponent(status)}&search=${search}`;
    showToast('Preparing leave export...', 'info');

    const link = document.createElement('a');
    link.href = url;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportOffsetReport() {
    const rows = filterOffsetData();
    if (!rows.length) {
        showToast('No offset data available to export.', 'error');
        return;
    }
    const headers = ['ID', 'Employee Number', 'Subject Code', 'Subject Description', 'Academic Term', 'Section', 'Original Schedule', 'Offset Schedule', 'Reason', 'Prepared By', 'Submit Date', 'Status'];
    const data = rows.map(rec => ({
        'ID': rec.id,
        'Employee Number': rec.employee_number || '',
        'Subject Code': rec.subject_code || '',
        'Subject Description': rec.subject_description || '',
        'Academic Term': rec.academic_term || '',
        'Section': rec.schedule_section || '',
        'Original Schedule': `${formatDate(rec.original_sched_date)} ${rec.original_sched_time || ''}`.trim(),
        'Offset Schedule': `${formatDate(rec.offset_sched_date)} ${rec.offset_sched_time || ''}`.trim(),
        'Reason': rec.reason || '',
        'Prepared By': rec.prepaired_by || '',
        'Submit Date': formatDate(rec.submit_date),
        'Status': rec.status || ''
    }));
    const csv = buildCSV(headers, data);
    downloadCSV(`offset_report_${new Date().toISOString().slice(0,10)}.csv`, csv);
    showToast('Offset export downloaded.', 'success');
}

function exportRequestReport() {
    const rows = filterRequestData();
    if (!rows.length) {
        showToast('No request form data available to export.', 'error');
        return;
    }
    const headers = ['ID', 'Employee Number', 'Request Type', 'Reason', 'Prepared By', 'Submit Date', 'Status'];
    const data = rows.map(req => ({
        'ID': req.id,
        'Employee Number': req.employee_number || '',
        'Request Type': req.type || '',
        'Reason': req.reason || '',
        'Prepared By': req.prepared_by || '',
        'Submit Date': formatDate(req.submit_date),
        'Status': req.status || ''
    }));
    const csv = buildCSV(headers, data);
    downloadCSV(`request_report_${new Date().toISOString().slice(0,10)}.csv`, csv);
    showToast('Request export downloaded.', 'success');
}

// ============================================================
// OFFSET TAB - LOAD & RENDER
// ============================================================
function loadOffsetRecords() {
    const tbody = document.getElementById('offsetTableBody');
    tbody.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px;">Loading...</td></tr>';

    fetch('../../backendPHP/get_offsets.php')
        .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
        .then(data => {
            if (data.error) throw new Error(data.error);
            offsetRecords = data;
            updateOffsetStats();
            renderOffsetTable();
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="12" style="text-align:center;padding:20px;color:red;">Error: ${err.message}</td></tr>`;
        });
}

function updateOffsetStats() {
    document.getElementById('offsetTotal').textContent     = offsetRecords.length;
    document.getElementById('offsetPending').textContent   = offsetRecords.filter(r => r.status === 'pending' || !r.status).length;
    const uniqueEmps = new Set(offsetRecords.map(r => r.employee_number)).size;
    document.getElementById('offsetEmployees').textContent = uniqueEmps;
    const uniqueSubs = new Set(offsetRecords.map(r => r.subject_code)).size;
    document.getElementById('offsetSubjects').textContent  = uniqueSubs;
}

function filterOffsetData() {
    if (!offsetSearchTerm) return [...offsetRecords];
    const s = offsetSearchTerm.toLowerCase();
    return offsetRecords.filter(r =>
        (r.employee_number || '').toLowerCase().includes(s) ||
        (r.subject_code    || '').toLowerCase().includes(s) ||
        (r.schedule_section|| '').toLowerCase().includes(s) ||
        (r.prepaired_by    || '').toLowerCase().includes(s)
    );
}

function renderOffsetTable() {
    const tbody    = document.getElementById('offsetTableBody');
    const filtered = filterOffsetData();
    tbody.innerHTML = '';
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" style="text-align:center;padding:20px;">No offset records found</td></tr>';
        document.getElementById('offsetPaginationInfo').textContent = 'Showing 0 to 0 of 0 results';
        return;
    }
    filtered.forEach(rec => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${rec.id}</strong></td>
            <td>${rec.employee_number || '—'}</td>
            <td><strong>${rec.subject_code || '—'}</strong></td>
            <td>${rec.subject_description || '—'}</td>
            <td>${rec.academic_term || '—'}</td>
            <td>${rec.schedule_section || '—'}</td>
            <td>
                <div style="font-size:12px;color:#333;">
                    <div><i class="fas fa-calendar" style="color:#3498db;margin-right:4px;"></i>${formatDate(rec.original_sched_date)}</div>
                    <div><i class="fas fa-clock" style="color:#3498db;margin-right:4px;"></i>${rec.original_sched_time || '—'}</div>
                </div>
            </td>
            <td>
                <div style="font-size:12px;color:#333;">
                    <div><i class="fas fa-calendar-check" style="color:#2ecc71;margin-right:4px;"></i>${formatDate(rec.offset_sched_date)}</div>
                    <div><i class="fas fa-clock" style="color:#2ecc71;margin-right:4px;"></i>${rec.offset_sched_time || '—'}</div>
                </div>
            </td>
            <td style="max-width:160px;white-space:normal;font-size:12px;">${rec.reason || '—'}</td>
            <td>${rec.prepaired_by || '—'}</td>
            <td>${formatDate(rec.submit_date)}</td>
                <td>${rec.status || '—'}</td>
        
            <td>
                <div class="action-buttons">
                    <button class="action-btn view-btn offset-view-btn" data-id="${rec.id}" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn approve-btn offset-approve-btn" data-id="${rec.id}" title="Approve">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="action-btn deny-btn offset-deny-btn" data-id="${rec.id}" title="Reject">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
    document.getElementById('offsetPaginationInfo').textContent = `Showing 1 to ${filtered.length} of ${filtered.length} results`;
    addOffsetTableListeners();
}

function addOffsetTableListeners() {
    document.querySelectorAll('.offset-view-btn').forEach(btn =>
        btn.addEventListener('click', () => showOffsetDetails(btn.dataset.id)));
    document.querySelectorAll('.offset-approve-btn').forEach(btn =>
        btn.addEventListener('click', async () => {
            const ok = await showConfirm(`Approve offset record #${btn.dataset.id}?`);
            if (ok) updateOffsetStatus(btn.dataset.id, 'approved');
        }));
    document.querySelectorAll('.offset-deny-btn').forEach(btn =>
        btn.addEventListener('click', async () => {
            const reason = await showConfirm('Enter reason for rejection:', { showInput: true, inputPlaceholder: 'Rejection reason' });
            if (reason) updateOffsetStatus(btn.dataset.id, 'rejected', reason);
        }));
}

function showOffsetDetails(id) {
    currentOffset = offsetRecords.find(r => String(r.id) === String(id));
    if (!currentOffset) return;
    const o = currentOffset;
    document.getElementById('offsetDetailsContent').innerHTML = `
        <div class="detail-item"><span class="detail-label">Record ID</span>
            <div class="detail-value">${o.id}</div></div>
        <div class="detail-item"><span class="detail-label">Employee Number</span>
            <div class="detail-value">${o.employee_number || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Subject Code</span>
            <div class="detail-value">${o.subject_code || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Subject Description</span>
            <div class="detail-value">${o.subject_description || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Academic Term</span>
            <div class="detail-value">${o.academic_term || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Section</span>
            <div class="detail-value">${o.schedule_section || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Original Date</span>
            <div class="detail-value">${formatDate(o.original_sched_date)}</div></div>
        <div class="detail-item"><span class="detail-label">Original Time</span>
            <div class="detail-value">${o.original_sched_time || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Offset Date</span>
            <div class="detail-value">${formatDate(o.offset_sched_date)}</div></div>
        <div class="detail-item"><span class="detail-label">Offset Time</span>
            <div class="detail-value">${o.offset_sched_time || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Reason</span>
            <div class="detail-value">${o.reason || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Prepared By</span>
            <div class="detail-value">${o.prepaired_by || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Submit Date</span>
            <div class="detail-value">${formatDate(o.submit_date)}</div></div>
    `;
    document.getElementById('approveOffsetBtn').onclick = async () => {
        const ok = await showConfirm(`Approve offset #${o.id}?`);
        if (ok) updateOffsetStatus(o.id, 'approved');
    };
    document.getElementById('denyOffsetBtn').onclick    = async () => {
        const reason = await showConfirm('Enter reason for rejection:', { showInput: true, inputPlaceholder: 'Rejection reason' });
        if (reason) updateOffsetStatus(o.id, 'rejected', reason);
    };
    document.getElementById('offsetDetailsModal').style.display = 'flex';
}

// Modal-based confirmation helper. Returns `true` or `false`, or a string when input is enabled.
function showConfirm(message, options = {}) {
    const { showInput = false, inputPlaceholder = '' } = options;
    const modal = document.getElementById('confirmModal');
    const msgEl = document.getElementById('confirmMessage');
    const inputWrapper = document.getElementById('confirmInputWrapper');
    const inputEl = document.getElementById('confirmReason');
    const yesBtn = document.getElementById('confirmYes');
    const noBtn = document.getElementById('confirmNo');

    return new Promise(resolve => {
        if (!modal || !msgEl || !yesBtn || !noBtn) return resolve(false);
        msgEl.textContent = message;
        inputWrapper.style.display = showInput ? 'block' : 'none';
        inputEl.placeholder = inputPlaceholder;
        inputEl.value = '';
        modal.style.display = 'flex';

        function cleanup() {
            yesBtn.removeEventListener('click', onYes);
            noBtn.removeEventListener('click', onNo);
            modal.style.display = 'none';
        }
        function onYes() { const val = showInput ? inputEl.value.trim() : true; cleanup(); resolve(val); }
        function onNo()  { cleanup(); resolve(false); }

        yesBtn.addEventListener('click', onYes);
        noBtn.addEventListener('click', onNo);
    });
}

function updateOffsetStatus(id, status, reason = '') {
    const fd = new FormData();
    fd.append('offset_id', id);
    fd.append('status', status);
    fd.append('reason', reason);

    fetch('../../backendPHP/update_offset_status.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const rec = offsetRecords.find(r => String(r.id) === String(id));
                if (rec) rec.status = status;
                document.getElementById('offsetDetailsModal').style.display = 'none';
                renderOffsetTable();
                showToast(`Offset #${id} has been ${status}.`, 'success');
            } else {
                // ✅ Check both d.message and d.error
                showToast('Error: ' + (d.message || d.error || 'Unknown error'), 'error');
            }
        })
        .catch(err => showToast('Error updating offset status: ' + err, 'error'));
}
function setupOffsetEventListeners() {
    document.getElementById('offsetSearchInput')?.addEventListener('input', function () {
        offsetSearchTerm = this.value;
        renderOffsetTable();
    });
    document.getElementById('offsetExportBtn')?.addEventListener('click', exportOffsetReport);
}

// ============================================================
// REQUEST TAB - LOAD & RENDER
// ============================================================
function loadRequestForms() {
    const tbody = document.getElementById('requestTableBody');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;">Loading...</td></tr>';

    fetch('../../backendPHP/get_request_forms.php')
        .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
        .then(data => {
            if (data.error) throw new Error(data.error);
            requestForms = data;
            updateRequestStats();
            buildRequestTypeFilters();
            renderRequestTable();
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:20px;color:red;">Error: ${err.message}</td></tr>`;
        });
}

function updateRequestStats() {
    document.getElementById('requestTotal').textContent     = requestForms.length;
    document.getElementById('requestPending').textContent   = requestForms.filter(r => r.status === 'pending' || !r.status).length;
    const uniqueEmps = new Set(requestForms.map(r => r.employee_number)).size;
    document.getElementById('requestEmployees').textContent = uniqueEmps;
    const uniqueTypes = new Set(requestForms.map(r => r.type)).size;
    document.getElementById('requestTypes').textContent     = uniqueTypes;
}

function buildRequestTypeFilters() {
    const container = document.getElementById('requestFilterTabs');
    const types     = [...new Set(requestForms.map(r => r.type).filter(Boolean))];
    container.innerHTML = `<button class="filter-tab active" data-type="all">All</button>`;
    types.forEach(type => {
        const btn = document.createElement('button');
        btn.className = 'filter-tab';
        btn.dataset.type = type;
        btn.textContent = type;
        container.appendChild(btn);
    });
    container.querySelectorAll('.filter-tab').forEach(tab =>
        tab.addEventListener('click', function () {
            container.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentRequestType = this.dataset.type;
            renderRequestTable();
        })
    );
}

function filterRequestData() {
    let filtered = [...requestForms];
    if (currentRequestType !== 'all') filtered = filtered.filter(r => r.type === currentRequestType);
    if (requestSearchTerm) {
        const s = requestSearchTerm.toLowerCase();
        filtered = filtered.filter(r =>
            (r.employee_number || '').toLowerCase().includes(s) ||
            (r.type            || '').toLowerCase().includes(s) ||
            (r.prepared_by     || '').toLowerCase().includes(s) ||
            (r.reason          || '').toLowerCase().includes(s)
        );
    }
    return filtered;
}

function getRequestTypeBadge(type) {
    const colors = {
        'Overtime': '#3498db', 'Certificate': '#9b59b6', 'Clearance': '#2ecc71',
        'Transfer': '#e67e22', 'Resignation': '#e74c3c'
    };
    const color = colors[type] || '#95a5a6';
    return `<span style="background:${color}20;color:${color};padding:5px 12px;border-radius:20px;font-size:12px;font-weight:600;">${type || 'N/A'}</span>`;
}

function renderRequestTable() {
    const tbody    = document.getElementById('requestTableBody');
    const filtered = filterRequestData();
    tbody.innerHTML = '';
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px;">No request forms found</td></tr>';
        document.getElementById('requestPaginationInfo').textContent = 'Showing 0 to 0 of 0 results';
        return;
    }
    filtered.forEach(rec => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${rec.id}</strong></td>
            <td>${rec.employee_number || '—'}</td>
            <td>${getRequestTypeBadge(rec.type)}</td>
            <td style="max-width:200px;white-space:normal;font-size:12px;">${rec.reason || '—'}</td>
            <td>${rec.prepared_by || '—'}</td>
            <td>${formatDate(rec.submit_date)}</td>
            <td>${rec.status}</td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn view-btn request-view-btn" data-id="${rec.id}" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn approve-btn request-approve-btn" data-id="${rec.id}" title="Approve">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="action-btn deny-btn request-deny-btn" data-id="${rec.id}" title="Reject">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
    document.getElementById('requestPaginationInfo').textContent = `Showing 1 to ${filtered.length} of ${filtered.length} results`;
    addRequestTableListeners();
}

function addRequestTableListeners() {
    document.querySelectorAll('.request-view-btn').forEach(btn =>
        btn.addEventListener('click', () => showRequestDetails(btn.dataset.id)));
    document.querySelectorAll('.request-approve-btn').forEach(btn =>
        btn.addEventListener('click', async () => {
            const ok = await showConfirm(`Approve request form #${btn.dataset.id}?`);
            if (ok) updateRequestStatus(btn.dataset.id, 'approved');
        }));
    document.querySelectorAll('.request-deny-btn').forEach(btn =>
        btn.addEventListener('click', async () => {
            const reason = await showConfirm('Enter reason for rejection:', { showInput: true, inputPlaceholder: 'Rejection reason' });
            if (reason) updateRequestStatus(btn.dataset.id, 'rejected', reason);
        }));
}

function showRequestDetails(id) {
    currentRequest = requestForms.find(r => String(r.id) === String(id));
    if (!currentRequest) return;
    const req = currentRequest;
    document.getElementById('requestDetailsContent').innerHTML = `
        <div class="detail-item"><span class="detail-label">Record ID</span>
            <div class="detail-value">${req.id}</div></div>
        <div class="detail-item"><span class="detail-label">Employee Number</span>
            <div class="detail-value">${req.employee_number || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Request Type</span>
            <div class="detail-value">${req.type || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Prepared By</span>
            <div class="detail-value">${req.prepared_by || '—'}</div></div>
        <div class="detail-item"><span class="detail-label">Submit Date</span>
            <div class="detail-value">${formatDate(req.submit_date)}</div></div>
        <div class="detail-item" style="grid-column: span 2;"><span class="detail-label">Reason</span>
            <div class="detail-value">${req.reason || '—'}</div></div>
    `;
    document.getElementById('approveRequestBtn').onclick = async () => { const ok = await showConfirm(`Approve request #${req.id}?`); if (ok) updateRequestStatus(req.id, 'approved'); };
    document.getElementById('denyRequestBtn').onclick    = async () => { const r = await showConfirm('Enter reason for rejection:', { showInput: true, inputPlaceholder: 'Rejection reason' }); if (r) updateRequestStatus(req.id, 'rejected', r); };
    document.getElementById('requestDetailsModal').style.display = 'flex';
}

function updateRequestStatus(id, status, reason = '') {
    const fd = new FormData();
    fd.append('request_id', id);
    fd.append('status', status);
    fd.append('reason', reason);

    fetch('../../backendPHP/update_request_form.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const rec = requestForms.find(r => String(r.id) === String(id));
                if (rec) rec.status = status;
                document.getElementById('requestDetailsModal').style.display = 'none';
                renderRequestTable();
                showToast(`Request #${id} has been ${status}.`, 'success');
            } else {
                // ✅ fallback handles both 'message' and 'error' keys
                showToast('Error: ' + (d.message || d.error || 'Unknown error'), 'error');
            }
        })
        .catch(err => showToast('Error updating request status: ' + err, 'error'));
}

function setupRequestEventListeners() {
    document.getElementById('requestSearchInput')?.addEventListener('input', function () {
        requestSearchTerm = this.value;
        renderRequestTable();
    });
    document.getElementById('requestExportBtn')?.addEventListener('click', exportRequestReport);
}