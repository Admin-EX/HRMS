// ─── EMPLOYEE DATABASE (populated from DB via AJAX) ──────────────────────────
let employeeDatabase = {};
let employeeCounts   = {};

// ─── LOAD ALL EMPLOYEES ON PAGE LOAD ─────────────────────────────────────────
function loadEmployeeDatabase() {
    fetch('../../backendPHP/get_employee_dashboard.php')          // ← adjust path as needed
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                console.error('Failed to load employee data:', data);
                return;
            }
            employeeDatabase = data.categories;
            employeeCounts   = data.counts;
            console.log('Employee database loaded:', employeeCounts);
        })
        .catch(err => console.error('Employee DB error:', err));
}

// ─── TEACHING ANALYTICS (unchanged — your existing endpoint) ─────────────────
function loadAnalytics() {
    fetch('../../backendPHP/teachingAnalytics.php')
        .then(res => res.json())
        .then(data => {
            const teachingAnalytics = {
                doctorate:      Number(data.doctorate)      || 0,
                takingDoctorate:Number(data.takingDoctorate)|| 0,
                masters:        Number(data.masters)        || 0,
                takingMasters:  Number(data.takingMasters)  || 0,
                bachelor:       Number(data.bachelor)       || 0,
                total:          Number(data.total)          || 0,
                schools:        data.schools                || {}
            };
            updateAnalyticsBars(teachingAnalytics);
        })
        .catch(err => console.error('Analytics error:', err));
}

function updateAnalyticsBars(teachingAnalytics) {
    const maxValue = Math.max(
        teachingAnalytics.doctorate,
        teachingAnalytics.masters,
        teachingAnalytics.bachelor,
        1
    );

    const doctoratePercent = Math.round((teachingAnalytics.doctorate / maxValue) * 100);
    const mastersPercent   = Math.round((teachingAnalytics.masters   / maxValue) * 100);
    const bachelorPercent  = Math.round((teachingAnalytics.bachelor  / maxValue) * 100);

    // Doctorate
    document.getElementById('doctorate-count').textContent = teachingAnalytics.doctorate;
    document.getElementById('doctorate-school').textContent = formatSchools(teachingAnalytics.schools, 'Doctorate');
    document.getElementById('doctorateBar').textContent     = teachingAnalytics.doctorate;
    document.getElementById('doctorateBar').style.width     = `${doctoratePercent}%`;
    document.getElementById('doctoratePercent').textContent = `${doctoratePercent}%`;

    // Taking Doctorate
    document.getElementById('takingdoctorate-count').textContent  = teachingAnalytics.takingDoctorate;
    document.getElementById('takingdoctorate-school').textContent = formatSchools(teachingAnalytics.schools, 'takingDoctorate');

    // Masters
    document.getElementById('masters-count').textContent  = teachingAnalytics.masters;
    document.getElementById('masters-school').textContent = formatSchools(teachingAnalytics.schools, 'Masters');
    document.getElementById('mastersBar').textContent     = teachingAnalytics.masters;
    document.getElementById('mastersBar').style.width     = `${mastersPercent}%`;
    document.getElementById('mastersPercent').textContent = `${mastersPercent}%`;

    // Taking Masters
    document.getElementById('takingmasters-count').textContent  = teachingAnalytics.takingMasters;
    document.getElementById('takingmasters-school').textContent = formatSchools(teachingAnalytics.schools, 'takingMasters');

    // Bachelor
    const bachelorCountEl  = document.getElementById('bachelor-count');
    const bachelorSchoolEl = document.getElementById('bachelor-school');
    if (bachelorCountEl)  bachelorCountEl.textContent  = teachingAnalytics.bachelor;
    if (bachelorSchoolEl) bachelorSchoolEl.textContent = formatSchools(teachingAnalytics.schools, 'Bachelors');
    document.getElementById('bachelorBar').textContent     = teachingAnalytics.bachelor;
    document.getElementById('bachelorBar').style.width     = `${bachelorPercent}%`;
    document.getElementById('bachelorPercent').textContent = `${bachelorPercent}%`;

    // Total
    document.getElementById('totalTP').textContent = `${teachingAnalytics.total} employees`;
}

// Toast helper: creates a container and shows a toast message
function showToast(message, type = 'info', duration = 3000) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        Object.assign(container.style, {
            position: 'fixed',
            right: '20px',
            top: '20px',
            zIndex: 99999,
            display: 'flex',
            flexDirection: 'column',
            gap: '10px',
            alignItems: 'flex-end'
        });
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast-message';
    toast.textContent = message;
    const bg = type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#34495e';
    Object.assign(toast.style, {
        background: bg,
        color: '#fff',
        padding: '10px 14px',
        borderRadius: '6px',
        boxShadow: '0 6px 18px rgba(0,0,0,0.12)',
        opacity: '0',
        transform: 'translateY(-8px)',
        transition: 'opacity 220ms ease, transform 220ms ease',
        maxWidth: '360px',
        fontSize: '14px'
    });

    container.appendChild(toast);

    // animate in
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    // remove after duration
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(() => container.removeChild(toast), 300);
    }, duration);
}

function formatSchools(schoolsData, category) {
    if (!schoolsData || !schoolsData[category] || schoolsData[category].length === 0) {
        return 'No data';
    }
    return schoolsData[category]
        .map(school => `${school.name} (${school.count})`)
        .join(', ');
}

// ─── MODAL HELPERS ────────────────────────────────────────────────────────────
const employeeModal    = document.getElementById('employeeModal');
const closeEmployeeModal = document.getElementById('closeEmployeeModal');
const closeModalBtn    = document.getElementById('closeModalBtn');
const modalTitle       = document.getElementById('modalTitle');
const employeeList     = document.getElementById('employeeList');
const employeeSearch   = document.getElementById('employeeSearch');
const employeeCount    = document.getElementById('employeeCount');

// Add Announcement modal elements
const addAnnouncementBtn = document.getElementById('addAnnouncementBtn');
const addAnnouncementModal = document.getElementById('addAnnouncementModal');
const closeAddAnnouncementModal = document.getElementById('closeAddAnnouncementModal');
const addAnnouncementForm = document.getElementById('addAnnouncementForm');
const saveAnnouncementBtn = document.getElementById('saveAnnouncementBtn');
const cancelAnnouncementBtn = document.getElementById('cancelAnnouncementBtn');

let currentEmployees = [];

function openModal(categoryKey, iconClass, label) {
    const employees = employeeDatabase[categoryKey] || [];
    const count     = employeeCounts[categoryKey]   || employees.length;

    currentEmployees = employees;
    modalTitle.innerHTML = `<i class="${iconClass}"></i> ${label} (${count})`;
    displayEmployees(employees);
    employeeModal.style.display = 'flex';
    employeeSearch.value = '';
}

function displayEmployees(employees) {
    employeeList.innerHTML = '';
    employeeCount.textContent = `Showing ${employees.length} employees`;

    if (employees.length === 0) {
        employeeList.innerHTML = `
            <div style="text-align:center;padding:40px;color:#666;">
                <i class="fas fa-users" style="font-size:48px;margin-bottom:15px;opacity:0.3;"></i>
                <p>No employees found matching your search</p>
            </div>`;
        return;
    }

    employees.forEach(emp => {
        const item = document.createElement('div');
        item.className = 'employee-item';
        item.innerHTML = `
            <div class="employee-avatar">${emp.initials}</div>
            <div class="employee-info">
                <div class="employee-name">${emp.name}</div>
                <div class="employee-details">
                    <span class="employee-id">${emp.id}</span>
                    <span class="employee-position">${emp.position}</span>
                    <span>${emp.department}</span>
                    ${emp.yearsService !== undefined
                        ? `<span>${emp.yearsService} yrs service</span>` : ''}
                    ${emp.degree
                        ? `<span><i class="fas fa-graduation-cap"></i> ${emp.degree}</span>` : ''}
                    ${emp.credentials
                        ? `<span><i class="fas fa-id-card"></i> ${emp.credentials}</span>` : ''}
                </div>
            </div>`;
        employeeList.appendChild(item);
    });
}

function filterEmployees(searchTerm) {
    if (!searchTerm) { displayEmployees(currentEmployees); return; }
    const term     = searchTerm.toLowerCase();
    const filtered = currentEmployees.filter(emp =>
        emp.name.toLowerCase().includes(term)       ||
        emp.id.toLowerCase().includes(term)         ||
        emp.position.toLowerCase().includes(term)   ||
        emp.department.toLowerCase().includes(term)
    );
    displayEmployees(filtered);
}

// ─── VIEW FUNCTIONS (was all hardcoded — now uses DB) ─────────────────────────
function viewPermanentEmployees()  { openModal('permanent',       'fas fa-user-check',      'Permanent Employees'); }
function viewContractualEmployees(){ openModal('contractual',     'fas fa-file-contract',   'Contractual Employees'); }
function viewCOSEmployees()        { openModal('cos',             'fas fa-briefcase',       'COS Employees'); }

function view30PlusYears()  { openModal('years30plus',  'fas fa-award',      '30+ Years Service'); }
function view20to29Years()  { openModal('years20to29',  'fas fa-medal',      '20-29 Years Service'); }
function view10to19Years()  { openModal('years10to19',  'fas fa-star',       '10-19 Years Service'); }
function viewBelow10Years() { openModal('below10',      'fas fa-user-clock', 'Below 10 Years Service'); }

function viewBachelorsNTP()   { openModal('bachelorsNTP',    'fas fa-user-graduate',  "NTP with Bachelor's"); }
function viewVocationalNTP()  { openModal('vocationalNTP',   'fas fa-tools',          'Vocational/Technical NTP'); }
function viewHighSchoolNTP()  { openModal('highSchoolNTP',   'fas fa-user',           'High School NTP'); }
function viewSupportStaffNTP(){ openModal('supportStaffNTP', 'fas fa-hands-helping',  'Support Staff NTP'); }

function viewGraduateDegrees()    { openModal('graduateDegrees',      'fas fa-user-graduate', 'Graduate Degrees'); }
function viewCivilServiceEligible(){ openModal('civilServiceEligible', 'fas fa-id-card',       'Civil Service Eligible'); }

function viewTotalEmployees() { openModal('all', 'fas fa-users', 'All Employees'); }

function viewYearsService() {
    // Combine 10+ year groups into one list
    const combined = [
        ...(employeeDatabase.years30plus  || []),
        ...(employeeDatabase.years20to29  || []),
        ...(employeeDatabase.years10to19  || []),
    ];
    const total = combined.length;
    currentEmployees = combined;
    modalTitle.innerHTML = `<i class="fas fa-award"></i> 10+ Years Service (${total})`;
    displayEmployees(combined);
    employeeModal.style.display = 'flex';
    employeeSearch.value = '';
}

// ─── LEAVE REQUESTS (kept separate — fetched from your existing data) ─────────
function viewLeaveRequest(employeeId) {
    // If you have a separate leave endpoint, fetch it here.
    // For now it mirrors the original alert behaviour.
    fetch(`../../backendPHP/getLeaveRequest.php?id=${encodeURIComponent(employeeId)}`)
        .then(res => res.json())
        .then(req => {
            if (!req) { showToast('No leave request found', 'error'); return; }
            showToast('Loaded leave request details', 'info');
        })
        .catch(() => { showToast('Could not load leave request details', 'error'); });
}

// ─── INITIALISE ───────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Load data
    loadAnalytics();
    loadEmployeeDatabase();

    // Animate chart bars
    setTimeout(() => {
        document.querySelectorAll('.chart-bar').forEach(bar => {
            bar.style.transition = 'width 0.8s ease-in-out';
        });
    }, 100);

    // Notification bell
    const notifBtn      = document.getElementById('notificationBtn');
    const notifDropdown = document.getElementById('notificationDropdown');
    notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('active');
    });
    document.addEventListener('click', function (e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove('active');
        }
    });

    // Filter dropdowns
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            this.closest('.filter-dropdown').classList.toggle('active');
        });
    });
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.filter-dropdown').forEach(dd => {
            if (!dd.contains(e.target)) dd.classList.remove('active');
        });
    });

    // Modal close buttons
    closeEmployeeModal.addEventListener('click', () => employeeModal.style.display = 'none');
    closeModalBtn.addEventListener('click',      () => employeeModal.style.display = 'none');
    employeeModal.addEventListener('click', e => {
        if (e.target === employeeModal) employeeModal.style.display = 'none';
    });
    // Add Announcement modal handlers
    if (addAnnouncementBtn && addAnnouncementModal) {
        addAnnouncementBtn.addEventListener('click', function () {
            addAnnouncementModal.style.display = 'flex';
            // reset form
            if (addAnnouncementForm) addAnnouncementForm.reset();
        });
    }
    if (closeAddAnnouncementModal) closeAddAnnouncementModal.addEventListener('click', () => addAnnouncementModal.style.display = 'none');
    if (cancelAnnouncementBtn) cancelAnnouncementBtn.addEventListener('click', (e) => { e.preventDefault(); addAnnouncementModal.style.display = 'none'; });
    if (addAnnouncementModal) addAnnouncementModal.addEventListener('click', e => { if (e.target === addAnnouncementModal) addAnnouncementModal.style.display = 'none'; });

    if (saveAnnouncementBtn && addAnnouncementForm) {
        saveAnnouncementBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const formData = new FormData(addAnnouncementForm);
            // Basic validation
            if (!formData.get('title') || !formData.get('content')) {
                showToast('Please provide title and content for the announcement.', 'error');
                return;
            }
            // If form has data-edit-id attribute, perform update instead
            const editId = addAnnouncementForm.dataset.editId;
            const endpoint = editId ? '../../backendPHP/update_announcement.php' : '../../backendPHP/insert_announcement.php';
            if (editId) formData.append('id', editId);
            fetch(endpoint, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const list = document.querySelector('.announcement-list');
                        const ann = data.announcement;
                        const important = (ann.priority || '').toLowerCase() === 'high' || (ann.priority || '').toLowerCase() === 'urgent';
                        const itemHtml = buildAnnouncementHtml(ann, important);
                        if (editId) {
                            // replace existing element
                            const existing = list.querySelector(`.announcement-item[data-id="${editId}"]`);
                            if (existing) {
                                existing.outerHTML = itemHtml;
                            }
                        } else {
                            // prepend new
                            list.insertAdjacentHTML('afterbegin', itemHtml);
                        }
                        // reset edit state
                        delete addAnnouncementForm.dataset.editId;
                        addAnnouncementForm.reset();
                        addAnnouncementModal.style.display = 'none';
                        showToast(data.message || 'Announcement saved', 'success');
                    } else {
                        showToast(data.message || 'Failed to save announcement', 'error');
                    }
                }).catch(err => { console.error(err); showToast('Error saving announcement', 'error'); });
        });
    }

    // Delegate edit/delete button clicks inside announcement list
    document.querySelector('.announcement-list')?.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-ann-btn');
        const delBtn = e.target.closest('.del-ann-btn');
        if (editBtn) {
            const id = editBtn.dataset.id;
            openEditAnnouncement(id);
            return;
        }
        if (delBtn) {
            const id = delBtn.dataset.id;
            if (!confirm('Delete this announcement?')) return;
            fetch('../../backendPHP/delete_announcement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${encodeURIComponent(id)}`
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    const el = document.querySelector(`.announcement-item[data-id="${id}"]`);
                    if (el) el.remove();
                    showToast(data.message || 'Announcement deleted', 'success');
                } else {
                    showToast(data.message || 'Failed to delete announcement', 'error');
                }
            }).catch(err => { console.error(err); showToast('Error deleting announcement', 'error'); });
        }
    });

    function openEditAnnouncement(id) {
        // fetch announcement details (could be available in DOM)
        const el = document.querySelector(`.announcement-item[data-id="${id}"]`);
        if (!el) { showToast('Announcement not found', 'error'); return; }
        const title = el.querySelector('.ann-title')?.textContent || '';
        const content = el.querySelector('.ann-content')?.textContent || '';
        const dateText = el.querySelector('.ann-date')?.textContent || '';
        const priorityText = el.querySelector('.ann-priority')?.textContent.replace(' Priority', '') || 'Normal';

        // Attempt to parse dateText (format like "May 21, 2026") back to YYYY-MM-DD
        let isoDate = '';
        try {
            const d = new Date(dateText);
            if (!isNaN(d)) {
                isoDate = d.toISOString().slice(0,10);
            }
        } catch (e) { isoDate = ''; }

        // populate form
        addAnnouncementForm.dataset.editId = id;
        addAnnouncementForm.querySelector('#announcementTitle').value = title;
        addAnnouncementForm.querySelector('#announcementContent').value = content.replace(/<br\/?\s*>/g, '\n');
        if (isoDate) addAnnouncementForm.querySelector('#announcementDate').value = isoDate;
        const pr = addAnnouncementForm.querySelector('#announcementPriority');
        if (pr) pr.value = priorityText;
        // update modal title
        addAnnouncementModal.querySelector('.modal-header h3').innerHTML = '<i class="fas fa-edit"></i> Edit Announcement';
        addAnnouncementModal.style.display = 'flex';
    }

    function buildAnnouncementHtml(ann, important) {
        const id = ann.id;
        const title = escapeHtml(ann.title);
        const content = escapeHtml(ann.content).replace(/\n/g, '<br/>');
        const date = formatDatePretty(ann.announcement_date);
        const priority = capitalizeFirst(ann.priority || 'Normal');
        const impClass = important ? ' important' : '';
        return `
            <div class="announcement-item${impClass}" data-id="${id}">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                    <div style="flex:1">
                        <h4 class="ann-title">${title}</h4>
                        <div class="announcement-meta">
                            <span class="ann-date">${date}</span>
                            <span class="ann-priority">${priority} Priority</span>
                        </div>
                        <p class="ann-content">${content}</p>
                    </div>
                    <div class="announcement-actions" style="margin-left:12px;display:flex;flex-direction:column;gap:8px;">
                        <button class="action-btn edit-ann-btn" data-id="${id}"><i class="fas fa-edit"></i> Edit</button>
                        <button class="action-btn archive-btn del-ann-btn" data-id="${id}"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>
            </div>
        `;
    }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && employeeModal.style.display === 'flex') {
            employeeModal.style.display = 'none';
        }
    });

    // Search inside modal
    employeeSearch.addEventListener('input', function () {
        filterEmployees(this.value);
    });

    // Document status filter in modal
    const docStatusFilter = document.getElementById('docStatusFilter');
    if (docStatusFilter) {
        docStatusFilter.addEventListener('change', function () {
            const status = this.value;
            document.querySelectorAll('#documentsTableBody tr').forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    const badge = row.querySelector('.doc-status-badge');
                    row.style.display = (badge && badge.classList.contains(status)) ? '' : 'none';
                }
            });
        });
    }

    // Document / preview modals close on outside click
    window.addEventListener('click', function (event) {
        const documentModal = document.getElementById('documentModal');
        const previewModal  = document.getElementById('documentPreviewModal');
        if (event.target === documentModal) closeDocumentModal();
        if (event.target === previewModal)  closePreviewModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDocumentModal();
            closePreviewModal();
        }
    });
});

// Small helpers used above
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatDatePretty(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' });
}

function capitalizeFirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : s; }