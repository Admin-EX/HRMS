// ============================================================================
// EMPLOYEE MANAGEMENT SYSTEM - FIXED & IMPROVED VERSION
// ============================================================================

// State variables
let employees = [];
let currentPage = 1;
const rowsPerPage = 10;
let currentFilter = 'all';
let currentCredentialFilter = null;
let currentEmployee = null;

// DOM elements - Main
const tableBody = document.getElementById('tableBody');
const searchInput = document.getElementById('searchInput');
const paginationInfo = document.getElementById('paginationInfo');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const exportButton = document.getElementById('exportButton');

// DOM elements - Modals
const employeeDetailsModal = document.getElementById('employeeDetailsModal');
const contactModal = document.getElementById('contactModal');
const addEmployeeModalEl = document.getElementById('addEmployeeModal');
const credentialModalEl = document.getElementById('credentialModal');

// DOM elements - Modal Controls
const closeDetailsModal = document.getElementById('closeDetailsModal');
const closeContactModal = document.getElementById('closeContactModal');
const closeModalBtn = document.getElementById('closeModalBtn');
const closeContactModalBtn = document.getElementById('closeContactModalBtn');
const contactEmployeeBtn = document.getElementById('contactEmployeeBtn');
const employeeDetailsContent = document.getElementById('employeeDetailsContent');
const closeAddEmployeeModal = document.getElementById('closeAddEmployeeModal');
const closeCredentialModal = document.getElementById('closeCredentialModal');

// DOM elements - Forms & Buttons
const addEmployeeBtn = document.getElementById('addEmployeeBtn');
const addEmployeeForm = document.getElementById('addEmployeeForm');

// ============================================================================
// INITIALIZATION
// ============================================================================

document.addEventListener('DOMContentLoaded', function () {
    loadEmployees();
    setupEventListeners();
});

// ============================================================================
// DATA LOADING
// ============================================================================

/**
 * Load employees from the backend using Fetch API
 * This is the modern, recommended approach
 */
function loadEmployees() {
    // Show loading state
    tableBody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px;">Loading employees...</td></tr>';

    fetch('../../backendPHP/employee.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            employees = data;
            renderTable();
            updateCredentialCounts();
            updateStats();
        })
        .catch(error => {
            console.error('Error loading employees:', error);
            tableBody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px; color: red;">Error loading employee data. Please refresh the page.</td></tr>';
        });
}

// ============================================================================
// TABLE RENDERING
// ============================================================================

/**
 * Main function to render the employee table with filtering and pagination
 */
function renderTable() {
    tableBody.innerHTML = '';

    let filteredData = getFilteredEmployees();

    // Sort by name
    filteredData.sort((a, b) => a.name.localeCompare(b.name));

    // Calculate pagination
    const totalRows = filteredData.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = startIndex + rowsPerPage;
    const pageData = filteredData.slice(startIndex, endIndex);

    // Check if no results
    if (pageData.length === 0) {
        showNoResults();
        return;
    }

    // Render rows
    pageData.forEach(employee => {
        const row = createEmployeeRow(employee);
        tableBody.appendChild(row);
    });

    // Add event listeners to action buttons
    addTableEventListeners();

    // Update pagination
    updatePaginationUI(totalRows, totalPages);
}

/**
 * Get filtered employees based on current filters and search term
 */
function getFilteredEmployees() {
    let filteredData = [...employees];

    // Apply type filter
    if (currentFilter === 'tp') {
        filteredData = filteredData.filter(emp => emp.type === 'TP');
    } else if (currentFilter === 'ntp') {
        filteredData = filteredData.filter(emp => emp.type === 'NTP');
    }

    // Apply credential filter
    if (currentCredentialFilter) {
        filteredData = filteredData.filter(emp => emp.credentials === currentCredentialFilter);
    }

    // Apply search
    const searchTerm = searchInput.value.toLowerCase().trim();
    if (searchTerm) {
        filteredData = filteredData.filter(emp =>
            emp.name.toLowerCase().includes(searchTerm) ||
            emp.id.toLowerCase().includes(searchTerm) ||
            emp.department.toLowerCase().includes(searchTerm) ||
            emp.position.toLowerCase().includes(searchTerm)
        );
    }

    return filteredData;
}

/**
 * Create a table row for an employee
 */
function createEmployeeRow(employee) {
    const row = document.createElement('tr');

    const typeClass = employee.type === 'TP' ? 'tp-badge' : 'ntp-badge';
    const typeText = employee.type === 'TP' ? 'Teaching' : 'Non-Teaching';
    const statusClass = employee.status === 'Active' ? 'active-badge' : 'inactive-badge';
    const credentialBadgeClass = getCredentialBadgeClass(employee.credentials);
    const initials = getInitials(employee.name);

    row.innerHTML = `
        <td><strong>${escapeHtml(employee.id)}</strong></td>
        <td>
            <div class="employee-cell">
                <div class="employee-avatar">${initials}</div>
                <div class="employee-info">
                    <div class="employee-name">${escapeHtml(employee.name)}</div>
                    <div class="employee-id">${escapeHtml(employee.email)}</div>
                </div>
            </div>
        </td>
        <td><span class="type-badge ${typeClass}">${typeText}</span></td>
        <td>${escapeHtml(employee.department)}</td>
        <td>${escapeHtml(employee.position)}</td>
        <td>
            <span>${escapeHtml(employee.credentials)}</span>
            <span class="credential-badge ${credentialBadgeClass}">${employee.credentials.charAt(0)}</span>
        </td>
        <td>${escapeHtml(employee.gender)}</td>
        <td><span class="status-badge ${statusClass}">${employee.status}</span></td>
        <td><span class="status-badge ${statusClass}">${employee.employment_status}</span></td>
        <td>
            <div class="action-buttons">
                <button class="action-btn view-btn" data-id="${escapeHtml(employee.id)}">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="action-btn contact-btn" data-id="${escapeHtml(employee.id)}">
                    <i class="fas fa-envelope"></i>
                </button>
            </div>
        </td>
    `;

    return row;
}

/**
 * Show no results message
 */
function showNoResults() {
    tableBody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px;">No employees found</td></tr>';
    paginationInfo.textContent = 'Showing 0 to 0 of 0 results';
    prevBtn.disabled = true;
    nextBtn.disabled = true;
}

/**
 * Update pagination UI
 */
function updatePaginationUI(totalRows, totalPages) {
    const startCount = totalRows > 0 ? (currentPage - 1) * rowsPerPage + 1 : 0;
    const endCount = Math.min(currentPage * rowsPerPage, totalRows);
    paginationInfo.textContent = `Showing ${startCount} to ${endCount} of ${totalRows} results`;

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
}

// ============================================================================
// EVENT LISTENERS - TABLE ACTIONS
// ============================================================================

/**
 * Add event listeners to action buttons in the table
 */
function addTableEventListeners() {
    // View buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const employeeId = this.getAttribute('data-id');
            showEmployeeDetails(employeeId);
        });
    });

    // Contact buttons
    document.querySelectorAll('.contact-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const employeeId = this.getAttribute('data-id');
            showContactModal(employeeId);
        });
    });
}

// ============================================================================
// MODAL FUNCTIONS
// ============================================================================

/**
 * Show employee details modal
 */
function showEmployeeDetails(employeeId) {
    currentEmployee = employees.find(emp => emp.id === employeeId);

    if (!currentEmployee) {
        console.error('Employee not found:', employeeId);
        return;
    }

    // Update modal content
    employeeDetailsContent.innerHTML = generateEmployeeDetailsHTML(currentEmployee);

    // Show modal
    employeeDetailsModal.style.display = 'flex';
}

/**
 * Generate HTML for employee details
 */
function generateEmployeeDetailsHTML(employee) {
    return `
        <div class="detail-item">
            <span class="detail-label">Employee ID</span>
            <div class="detail-value">${escapeHtml(employee.id)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Full Name</span>
            <div class="detail-value">${escapeHtml(employee.name)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Employee Type</span>
            <div class="detail-value">${employee.type === 'TP' ? 'Teaching Personnel' : 'Non-Teaching Personnel'}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Department</span>
            <div class="detail-value">${escapeHtml(employee.department)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Position</span>
            <div class="detail-value">${escapeHtml(employee.position)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Credentials</span>
            <div class="detail-value">${escapeHtml(employee.credentials)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Gender</span>
            <div class="detail-value">${escapeHtml(employee.gender)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Address</span>
            <div class="detail-value">${escapeHtml(employee.address)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Phone Number</span>
            <div class="detail-value">${escapeHtml(employee.phone)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Email</span>
            <div class="detail-value">${escapeHtml(employee.email)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Status</span>
            <div class="detail-value">${escapeHtml(employee.status)}</div>
        </div>
        <div class="detail-item">
            <span class="detail-label">Employment Status</span>
            <div class="detail-value">${escapeHtml(employee.employment_status)}</div>
        </div>
    `;
}

/**
 * Show contact employee modal
 */
function showContactModal(employeeId) {
    currentEmployee = employees.find(emp => emp.id === employeeId);

    if (!currentEmployee) {
        console.error('Employee not found:', employeeId);
        return;
    }

    // Update modal content
    document.getElementById('contactEmployeeName').textContent = currentEmployee.name;
    document.getElementById('contactEmail').textContent = currentEmployee.email;
    document.getElementById('contactPhone').textContent = currentEmployee.phone;
    document.getElementById('contactDepartment').textContent = currentEmployee.department;

    // Show modal
    contactModal.style.display = 'flex';
}

/**
 * Show credential modal after employee creation
 */
function showCredentialModal(employeeId, employeeNumber, password, fullName) {
    document.getElementById('credentialEmployeeId').textContent = employeeId;
    document.getElementById('credentialEmployeeNumber').textContent = employeeNumber;
    document.getElementById('credentialPassword').textContent = password;
    document.getElementById('credentialFullName').textContent = fullName;
    document.getElementById('credentialDate').textContent = new Date().toLocaleDateString();

    credentialModalEl.style.display = 'flex';
}

/**
 * Close all modals
 */
function closeAllModals() {
    employeeDetailsModal.style.display = 'none';
    contactModal.style.display = 'none';
    addEmployeeModalEl.style.display = 'none';
    credentialModalEl.style.display = 'none';
    currentEmployee = null;
}

// ============================================================================
// CONTACT FUNCTIONS
// ============================================================================

/**
 * Send email to employee
 */
function sendEmail() {
    if (!currentEmployee) return;
    window.location.href = `mailto:${currentEmployee.email}`;
}

/**
 * Make phone call to employee
 */
function makeCall() {
    if (!currentEmployee) return;
    window.location.href = `tel:${currentEmployee.phone}`;
}

/**
 * Send SMS to employee
 */
function sendSMS() {
    if (!currentEmployee) return;
    window.location.href = `sms:${currentEmployee.phone}`;
}

// ============================================================================
// FILTER FUNCTIONS
// ============================================================================

/**
 * Filter employees by type (Teaching/Non-Teaching)
 */
function filterByType(type) {
    currentFilter = type;
    currentPage = 1;
    currentCredentialFilter = null;

    // Update filter tabs
    updateFilterTabsUI(type);

    // Remove active credential filter
    document.querySelectorAll('.credential-filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    renderTable();
}

/**
 * Filter employees by credential
 */
function filterByCredential(credential) {
    currentCredentialFilter = credential;
    currentPage = 1;
    currentFilter = 'all';

    // Update active button
    document.querySelectorAll('.credential-filter-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('data-credential') === credential) {
            btn.classList.add('active');
        }
    });

    // Update filter tabs
    updateFilterTabsUI('all');

    renderTable();
}

/**
 * Update filter tabs UI
 */
function updateFilterTabsUI(activeFilter) {
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
        if (tab.getAttribute('data-filter') === activeFilter) {
            tab.classList.add('active');
        }
    });
}

// ============================================================================
// STATISTICS FUNCTIONS
// ============================================================================

/**
 * Update employee statistics
 */
function updateStats() {
    const total = employees.length;
    const teaching = employees.filter(emp => emp.type === 'TP').length;
    const nonTeaching = employees.filter(emp => emp.type === 'NTP').length;
    const active = employees.filter(emp => emp.status === 'Active').length;

    updateStatElement('totalEmployees', total);
    updateStatElement('teachingEmployees', teaching);
    updateStatElement('nonTeachingEmployees', nonTeaching);
    updateStatElement('activeEmployees', active);
}

/**
 * Update credential counts
 */
function updateCredentialCounts() {
    const counts = {
        'Doctorate': employees.filter(emp => emp.credentials === 'Doctorate').length,
        'Masters': employees.filter(emp => emp.credentials === 'Masters').length,
        'Taking Doctorate': employees.filter(emp => emp.credentials === 'Taking Doctorate').length,
        'Taking Masters': employees.filter(emp => emp.credentials === 'Taking Masters').length,
        'Professional': employees.filter(emp => emp.credentials === 'Professional').length,
        'Sub-Professional': employees.filter(emp => emp.credentials === 'Sub-Professional').length,
        'No Eligibility': employees.filter(emp => emp.credentials === 'No Eligibility').length
    };

    // Update quick filter badges
    updateStatElement('doctorateCount', counts['Doctorate']);
    updateStatElement('mastersCount', counts['Masters']);
    updateStatElement('takingDoctorateCount', counts['Taking Doctorate']);
    updateStatElement('takingMastersCount', counts['Taking Masters']);
    updateStatElement('professionalCount', counts['Professional']);

    // Update credential stats
    updateStatElement('credDoctorateCount', counts['Doctorate']);
    updateStatElement('credMastersCount', counts['Masters']);
    updateStatElement('credTakingDoctorateCount', counts['Taking Doctorate']);
    updateStatElement('credTakingMastersCount', counts['Taking Masters']);
    updateStatElement('credProfessionalCount', counts['Professional']);
}

/**
 * Safely update a stat element
 */
function updateStatElement(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value;
    }
}

// ============================================================================
// ADD EMPLOYEE FUNCTIONS
// ============================================================================

/**
 * Handle add employee form submission
 */
function handleAddEmployee(e) {
    e.preventDefault();

    // Get form data
    const formData = new FormData(addEmployeeForm);

    // Show loading state
    const submitBtn = addEmployeeForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    submitBtn.disabled = true;

fetch('../../backendPHP/create_employee.php', {
    method: 'POST',
    body: formData
})
.then(response => response.text())
.then(text => {
    // Try to parse JSON first
    try {
        const data = JSON.parse(text);

        if (data.success) {
            addEmployeeModalEl.style.display = 'none';
            showCredentialModal(
                data.employee_id,
                data.employee_number,
                data.password,
                data.full_name
            );
            loadEmployees();
            addEmployeeForm.reset();
        } else {
            showErrorModal(data.message || 'Unknown error');
        }
    } catch (e) {
        // Not JSON → PHP/HTML error
        showErrorModal(text);
    }
})
.catch(error => {
    showErrorModal(error.message);
})
.finally(() => {
    // safety fallback
    submitBtn.innerHTML = 'Create Employee';
    submitBtn.disabled = false;
});

}

// ============================================================================
// CREDENTIAL PRINTING & DOWNLOADING
// ============================================================================

/**
 * Print employee credential
 */
function printCredential() {
    const printContent = document.getElementById('credentialCard').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');

    printWindow.document.write('<html><head><title>Employee Credential</title>');
    printWindow.document.write('<style>');
    printWindow.document.write(getCredentialPrintStyles());
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();

    setTimeout(() => {
        printWindow.print();
    }, 250);
}

/**
 * Get styles for credential printing
 */
function getCredentialPrintStyles() {
    return `
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .credential-card {
            background: white;
            border: 2px solid #2563eb;
            border-radius: 12px;
            padding: 30px;
            width: 400px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .credential-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .credential-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        .credential-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .credential-subtitle {
            font-size: 14px;
            color: #6b7280;
        }
        .credential-info {
            margin-bottom: 20px;
        }
        .credential-item {
            margin-bottom: 15px;
        }
        .credential-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .credential-value {
            font-size: 16px;
            color: #1f2937;
            font-weight: 600;
        }
        .credential-password {
            background: #fef3c7;
            padding: 12px;
            border-radius: 8px;
            border: 2px dashed #f59e0b;
            margin: 20px 0;
        }
        .credential-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
        @media print {
            body {
                background: white;
            }
        }
    `;
}

/**
 * Download credential as image
 */
function downloadCredential() {
    const credentialCard = document.getElementById('credentialCard');

    // Check if html2canvas is available
    if (typeof html2canvas === 'undefined') {
        alert('Please include html2canvas library to download as image.\n\nAdd this to your HTML:\n<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>');
        return;
    }

    html2canvas(credentialCard, {
        scale: 2,
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const link = document.createElement('a');
        const employeeNumber = document.getElementById('credentialEmployeeNumber').textContent;
        link.download = `employee_credential_${employeeNumber}.png`;
        link.href = canvas.toDataURL();
        link.click();
    }).catch(error => {
        console.error('Error generating image:', error);
        alert('Failed to generate credential image. Please try again.');
    });
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Get credential badge class
 */
function getCredentialBadgeClass(credential) {
    const badgeMap = {
        'Doctorate': 'doctorate-badge',
        'Masters': 'masters-badge',
        'Taking Doctorate': 'taking-doctorate-badge',
        'Taking Masters': 'taking-masters-badge',
        'Professional': 'professional-badge',
        'Sub-Professional': 'subprofessional-badge',
        'No Eligibility': 'no-eligibility-badge'
    };

    return badgeMap[credential] || 'no-eligibility-badge';
}

/**
 * Get initials from name
 */
function getInitials(name) {
    return name.split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
}

/**
 * Escape HTML to prevent XSS attacks
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show notification summary
 */
function showNotificationSummary() {
    const teachingCount = employees.filter(e => e.type === 'TP').length;
    const nonTeachingCount = employees.filter(e => e.type === 'NTP').length;
    const activeCount = employees.filter(e => e.status === 'Active').length;

    alert(
        `Employee Notifications:\n\n` +
        `• ${employees.length} total employees in system\n` +
        `• ${teachingCount} teaching personnel\n` +
        `• ${nonTeachingCount} non-teaching personnel\n` +
        `• ${activeCount} employees currently active`
    );
}

function exportEmployeeReport() {
    const params = new URLSearchParams();
    const employeeType = currentFilter || 'all';
    const credential = currentCredentialFilter || '';
    const search = searchInput ? searchInput.value.trim() : '';

    params.append('employee_type', employeeType);
    if (credential) {
        params.append('credential', credential);
    }
    if (search) {
        params.append('search', search);
    }

    const url = `../../backendPHP/export_employee_report.php?${params.toString()}`;
    window.location.href = url;
}

// ============================================================================
// EVENT LISTENERS SETUP
// ============================================================================

/**
 * Setup all event listeners
 */
function setupEventListeners() {
    // Search input
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            currentPage = 1;
            renderTable();
        });
    }

    // Pagination buttons
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const filteredData = getFilteredEmployees();
            const totalPages = Math.ceil(filteredData.length / rowsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        });
    }

    // Export button
    if (exportButton) {
        exportButton.addEventListener('click', () => {
            exportEmployeeReport();
        });
    }

    // Filter tabs
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            const filter = this.getAttribute('data-filter');
            filterByType(filter);
        });
    });

    // Credential filter buttons
    document.querySelectorAll('.credential-filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const credential = this.getAttribute('data-credential');
            filterByCredential(credential);
        });
    });

    // Credential stat cards
    document.querySelectorAll('.credential-stat').forEach(stat => {
        stat.addEventListener('click', function () {
            const label = this.querySelector('.credential-stat-label').textContent;
            const credential = label.split(' ')[0];
            filterByCredential(credential);
        });
    });

    

    // Add Employee Modal
    if (addEmployeeBtn) {
        addEmployeeBtn.addEventListener('click', () => {
            addEmployeeModalEl.style.display = 'flex';
            addEmployeeForm.reset();
        });
    }

    // Modal close buttons - Employee Details
    if (closeDetailsModal) {
        closeDetailsModal.addEventListener('click', () => {
            employeeDetailsModal.style.display = 'none';
            currentEmployee = null;
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            employeeDetailsModal.style.display = 'none';
            currentEmployee = null;
        });
    }

    // Modal close buttons - Contact
    if (closeContactModal) {
        closeContactModal.addEventListener('click', () => {
            contactModal.style.display = 'none';
            currentEmployee = null;
        });
    }

    if (closeContactModalBtn) {
        closeContactModalBtn.addEventListener('click', () => {
            contactModal.style.display = 'none';
            currentEmployee = null;
        });
    }

    // Modal close buttons - Add Employee
    if (closeAddEmployeeModal) {
        closeAddEmployeeModal.addEventListener('click', () => {
            addEmployeeModalEl.style.display = 'none';
        });
    }

    const closeAddEmployeeModalBtn = document.getElementById('closeAddEmployeeModalBtn');
    if (closeAddEmployeeModalBtn) {
        closeAddEmployeeModalBtn.addEventListener('click', () => {
            addEmployeeModalEl.style.display = 'none';
        });
    }

    // Modal close buttons - Credential
    if (closeCredentialModal) {
        closeCredentialModal.addEventListener('click', () => {
            credentialModalEl.style.display = 'none';
        });
    }

    const closeCredentialModalBtn = document.getElementById('closeCredentialModalBtn');
    if (closeCredentialModalBtn) {
        closeCredentialModalBtn.addEventListener('click', () => {
            credentialModalEl.style.display = 'none';
        });
    }

    // Contact button in details modal
    if (contactEmployeeBtn) {
        contactEmployeeBtn.addEventListener('click', () => {
            if (currentEmployee) {
                employeeDetailsModal.style.display = 'none';
                setTimeout(() => showContactModal(currentEmployee.id), 300);
            }
        });
    }

    // Close modals when clicking outside
    if (employeeDetailsModal) {
        employeeDetailsModal.addEventListener('click', (e) => {
            if (e.target === employeeDetailsModal) {
                employeeDetailsModal.style.display = 'none';
                currentEmployee = null;
            }
        });
    }

    if (contactModal) {
        contactModal.addEventListener('click', (e) => {
            if (e.target === contactModal) {
                contactModal.style.display = 'none';
                currentEmployee = null;
            }
        });
    }

    if (addEmployeeModalEl) {
        addEmployeeModalEl.addEventListener('click', (e) => {
            if (e.target === addEmployeeModalEl) {
                addEmployeeModalEl.style.display = 'none';
            }
        });
    }

    if (credentialModalEl) {
        credentialModalEl.addEventListener('click', (e) => {
            if (e.target === credentialModalEl) {
                credentialModalEl.style.display = 'none';
            }
        });
    }

    // Add Employee Form submission
    if (addEmployeeForm) {
        addEmployeeForm.addEventListener('submit', handleAddEmployee);
    }

    // Print and Download Credential buttons
    const printCredentialBtn = document.getElementById('printCredentialBtn');
    if (printCredentialBtn) {
        printCredentialBtn.addEventListener('click', printCredential);
    }

    const downloadCredentialBtn = document.getElementById('downloadCredentialBtn');
    if (downloadCredentialBtn) {
        downloadCredentialBtn.addEventListener('click', downloadCredential);
    }
}

// ============================================================================
// EXPORT FOR USE IN OTHER FILES (if needed)
// ============================================================================

// If you're using modules, you can export functions:
// export { loadEmployees, renderTable, filterByType, filterByCredential };
const errorModal = document.getElementById('errorModal');
const errorModalMessage = document.getElementById('errorModalMessage');
const errorModalOk = document.getElementById('errorModalOk');

function showErrorModal(message) {
    errorModalMessage.textContent = message;
    errorModal.style.display = 'flex';
}

errorModalOk.addEventListener('click', () => {
    errorModal.style.display = 'none';

    // Restore submit button
    submitBtn.innerHTML = 'Create Employee';
    submitBtn.disabled = false;
});

            // Notification dropdown
            const notifBtn = document.getElementById('notificationBtn');
            const notifDropdown = document.getElementById('notificationDropdown');
            
            notifBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                notifDropdown.classList.toggle('active');
            });
            
            document.addEventListener('click', function(e) {
                if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                    notifDropdown.classList.remove('active');
                }
            });

            // Filter dropdowns
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const dropdown = this.closest('.filter-dropdown');
                    dropdown.classList.toggle('active');
                });
            });

            document.addEventListener('click', function(e) {
                document.querySelectorAll('.filter-dropdown').forEach(dropdown => {
                    if (!dropdown.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });
            });

            // Document status filter in modal
            const docStatusFilter = document.getElementById('docStatusFilter');
            if (docStatusFilter) {
                docStatusFilter.addEventListener('change', function() {
                    const status = this.value;
                    const rows = document.querySelectorAll('#documentsTableBody tr');
                    
                    rows.forEach(row => {
                        if (status === 'all') {
                            row.style.display = '';
                        } else {
                            const statusBadge = row.querySelector('.doc-status-badge');
                            if (statusBadge && statusBadge.classList.contains(status)) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        }
                    });
                });
            }

            // Close modals on click outside
            window.addEventListener('click', function(event) {
                const documentModal = document.getElementById('documentModal');
                const previewModal = document.getElementById('documentPreviewModal');
                
                if (event.target === documentModal) {
                    closeDocumentModal();
                }
                
                if (event.target === previewModal) {
                    closePreviewModal();
                }
            });

            // ESC key to close modals
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeDocumentModal();
                    closePreviewModal();
                }
            });
        