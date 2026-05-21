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
            if (!req) { alert('No leave request found.'); return; }
            alert(
                `Leave Request Details:\n\n` +
                `Employee : ${req.name}\n` +
                `Leave Type: ${req.leaveType}\n` +
                `Duration  : ${req.duration}\n` +
                `Dates     : ${req.startDate} to ${req.endDate}\n` +
                `Status    : ${req.status}\n` +
                `Reason    : ${req.reason}`
            );
        })
        .catch(() => alert('Could not load leave request details.'));
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