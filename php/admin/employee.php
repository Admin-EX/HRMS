<?php
include "../../database/connection.php";
session_start();
if (empty($_SESSION['loggedUser'])) {
    header("Location: ../../index.html");
    exit;
}
error_reporting(0);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../css/admin/employee.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <!-- Add Employee Modal -->
    <div id="addEmployeeModal" class="modal">
        <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New Employee</h2>
                <span class="close" id="closeAddEmployeeModal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addEmployeeForm">
                    <div class="form-grid">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3>Basic Information</h3>

                            <div class="form-group">
                                <label for="employee_number">Employee Number *</label>
                                <input type="text" id="employee_number" name="employee_number" required
                                    placeholder="e.g., EMP-2024-001">
                            </div>

                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" id="full_name" name="full_name" required
                                    placeholder="First Middle Last">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="employee_type">Employee Type *</label>
                                    <select id="employee_type" name="employee_type" required>
                                        <option value="">Select Type</option>
                                        <option value="TP">Teaching Personnel</option>
                                        <option value="NTP">Non-Teaching Personnel</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="gender">Gender *</label>
                                    <select id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Details -->
                        <div class="form-section">
                            <h3>Employment Details</h3>

                            <div class="form-group">
                                <label for="department">Department *</label>
                                <input type="text" id="department" name="department" required
                                    placeholder="e.g., Computer Science">
                            </div>

                            <div class="form-group">
                                <label for="position">Position *</label>
                                <input type="text" id="position" name="position" required
                                    placeholder="e.g., Professor, Admin Staff">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="date_hired">Date Hired *</label>
                                    <input type="date" id="date_hired" name="date_hired" required>
                                </div>

                                <div class="form-group">
                                    <label for="employment_status">Employment Status *</label>
                                    <select id="employment_status" name="employment_status" required>
                                        <option value="">Select Status</option>
                                        <option value="Permanent">Permanent</option>
                                        <option value="Temporary">Temporary</option>
                                        <option value="Contract">Contract</option>
                                        <option value="Part-time">Part-time</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="status">Current Status *</label>
                                <select id="status" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Resigned">Resigned</option>
                                </select>
                            </div>
                        </div>

                        <!-- Educational Background -->
                        <div class="form-section">
                            <h3>Educational Background</h3>

                            <div class="form-group">
                                <label for="credentials">Credentials *</label>
                                <select id="credentials" name="credentials" required>
                                    <option value="">Select Credentials</option>
                                    <option value="Doctorate">Doctorate</option>
                                    <option value="Masters">Masters</option>
                                    <option value="Taking Doctorate">Taking Doctorate</option>
                                    <option value="Taking Masters">Taking Masters</option>
                                    <option value="Bachelor">Bachelor</option>
                                    <option value="PhD">PhD</option>
                                    <option value="Professional">Professional</option>
                                    <option value="Sub-Professional">Sub-Professional</option>
                                    <option value="No Eligibility">No Eligibility</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="educational_attainment">Educational Attainment</label>
                                <input type="text" id="educational_attainment" name="educational_attainment"
                                    placeholder="e.g., PhD in Computer Science">
                            </div>

                            <div class="form-group">
                                <label for="school">School/University</label>
                                <input type="text" id="school" name="school"
                                    placeholder="e.g., University of the Philippines">
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="form-section">
                            <h3>Contact Information</h3>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" required placeholder="employee@example.com">
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" required placeholder="+63 XXX XXX XXXX">
                            </div>

                            <div class="form-group">
                                <label for="address">Address *</label>
                                <textarea id="address" name="address" rows="3" required
                                    placeholder="Complete Address"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" id="closeAddEmployeeModalBtn">Cancel</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Create Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Credential Modal -->
    <div id="credentialModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header"
                style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white;">
                <h2><i class="fas fa-id-card"></i> Employee Credentials Created</h2>
                <span class="close" id="closeCredentialModal" style="color: white;">&times;</span>
            </div>
            <div class="modal-body">
                <div id="credentialCard" class="credential-card">
                    <div class="credential-header">
                        <div class="credential-logo">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="credential-title">Employee Account Created</div>
                        <div class="credential-subtitle">Please save these credentials securely</div>
                    </div>

                    <div class="credential-info">
                        <div class="credential-item">
                            <div class="credential-label">Employee Name</div>
                            <div class="credential-value" id="credentialFullName">-</div>
                        </div>

                        <div class="credential-item">
                            <div class="credential-label">Employee ID</div>
                            <div class="credential-value" id="credentialEmployeeId">-</div>
                        </div>

                        <div class="credential-item">
                            <div class="credential-label">Employee Number</div>
                            <div class="credential-value" id="credentialEmployeeNumber">-</div>
                        </div>

                        <div class="credential-password">
                            <div class="credential-label">Temporary Password</div>
                            <div class="credential-value" id="credentialPassword"
                                style="font-family: monospace; font-size: 18px; color: #dc2626;">
                                ********
                            </div>
                            <div style="font-size: 11px; color: #92400e; margin-top: 8px;">
                                <i class="fas fa-exclamation-triangle"></i> Please change this password after first
                                login
                            </div>
                        </div>
                    </div>

                    <div class="credential-footer">
                        <div>Created on <span id="credentialDate">-</span></div>
                        <div style="margin-top: 5px; font-weight: 600;">Employee Management System</div>
                    </div>
                </div>

                <div class="credential-actions">
                    <button type="button" class="btn-secondary" id="printCredentialBtn">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button type="button" class="btn-primary" id="downloadCredentialBtn">
                        <i class="fas fa-download"></i> Download as Image
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-primary" id="closeCredentialModalBtn">
                    <i class="fas fa-check"></i> Done
                </button>
            </div>
        </div>
    </div>
    <div class="app">
        <!-- SIDEBAR - EXACT SAME -->
     <?php include("../components/sidebar.php"); ?>

        <!-- MAIN CONTENT -->
        <main class="main">
            <!-- TOP BAR -->
  <?php include("../components/topbar.php"); ?>


            <!-- CONTENT HEADER - EXACT SAME STYLE -->
            <div class="content-header">
                <h1>Employee Directory</h1>
                <p>Search, view, and manage all employees in the system</p>
            </div>

            <!-- SEARCH AND FILTERS -->
            <div class="search-filters-container">
                <div class="search-filters-wrapper">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, employee ID, or department...">
                    </div>

                    <div class="filters-wrapper">
                        <div class="filter-tabs">
                            <button class="filter-tab active" data-filter="all">All</button>
                            <button class="filter-tab" data-filter="tp">Teaching</button>
                            <button class="filter-tab" data-filter="ntp">Non-Teaching</button>
                        </div>

                        <button class="export-btn" id="exportButton">
                            <i class="fas fa-file-export"></i>
                            Export Report
                        </button>
                    </div>
                </div>
            </div>

            <!-- STATS CARDS - EXACT SAME STYLE -->
            <section class="stats">
                <div class="stat" onclick="filterByType('all')">
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="totalEmployees">14</h3>
                        <p>Total Employees</p>
                    </div>
                </div>

                <div class="stat" onclick="filterByType('tp')">
                    <div class="stat-icon green">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="teachingEmployees">8</h3>
                        <p>Teaching Personnel</p>
                    </div>
                </div>

                <div class="stat" onclick="filterByType('ntp')">
                    <div class="stat-icon purple">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="nonTeachingEmployees">6</h3>
                        <p>Non-Teaching Personnel</p>
                    </div>
                </div>

                <div class="stat">
                    <div class="stat-icon orange">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="activeEmployees">14</h3>
                        <p>Active Status</p>
                    </div>
                </div>
            </section>

            <!-- QUICK CREDENTIAL FILTERS -->
            <div class="credential-quick-filters">
                <button class="credential-filter-btn" data-credential="Doctorate">
                    <i class="fas fa-user-graduate"></i> Doctorate
                    <span class="credential-badge doctorate-badge" id="doctorateCount">0</span>
                </button>
                <button class="credential-filter-btn" data-credential="Masters">
                    <i class="fas fa-graduation-cap"></i> Masters
                    <span class="credential-badge masters-badge" id="mastersCount">0</span>
                </button>
                <button class="credential-filter-btn" data-credential="Taking Doctorate">
                    <i class="fas fa-book-reader"></i> Taking Doctorate
                    <span class="credential-badge taking-doctorate-badge" id="takingDoctorateCount">0</span>
                </button>
                <button class="credential-filter-btn" data-credential="Taking Masters">
                    <i class="fas fa-book"></i> Taking Masters
                    <span class="credential-badge taking-masters-badge" id="takingMastersCount">0</span>
                </button>
                <button class="credential-filter-btn" data-credential="Professional">
                    <i class="fas fa-briefcase"></i> Professional
                    <span class="credential-badge professional-badge" id="professionalCount">0</span>
                </button>
            </div>


            <!-- CREDENTIAL SUMMARY CARDS -->
            <div class="credential-stats">
                <div class="credential-stat" onclick="filterByCredential('Doctorate')">
                    <div class="credential-stat-value" id="credDoctorateCount">2</div>
                    <div class="credential-stat-label">Doctorate Holders</div>
                    <div class="credential-stat-subtext">Teaching Personnel</div>
                </div>
                <div class="credential-stat" onclick="filterByCredential('Masters')">
                    <div class="credential-stat-value" id="credMastersCount">1</div>
                    <div class="credential-stat-label">Masters Holders</div>
                    <div class="credential-stat-subtext">Teaching Personnel</div>
                </div>
                <div class="credential-stat" onclick="filterByCredential('Taking Doctorate')">
                    <div class="credential-stat-value" id="credTakingDoctorateCount">1</div>
                    <div class="credential-stat-label">Taking Doctorate</div>
                    <div class="credential-stat-subtext">Teaching Personnel</div>
                </div>
                <div class="credential-stat" onclick="filterByCredential('Taking Masters')">
                    <div class="credential-stat-value" id="credTakingMastersCount">1</div>
                    <div class="credential-stat-label">Taking Masters</div>
                    <div class="credential-stat-subtext">Teaching Personnel</div>
                </div>
                <div class="credential-stat" onclick="filterByCredential('Professional')">
                    <div class="credential-stat-value" id="credProfessionalCount">2</div>
                    <div class="credential-stat-label">Professional</div>
                    <div class="credential-stat-subtext">Non-Teaching Personnel</div>
                </div>
            </div>
            <!-- Add Employee Button (add this to your table header section) -->
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; margin-left: 20px;">
                <button id="addEmployeeBtn" class="add-employee-btn">
                    <i class="fas fa-plus"></i> Add Employee
                </button>
            </div>
            <!-- EMPLOYEE TABLE -->
            <div class="table-container">
                <table id="employeeTable">
                    <thead>
                        <tr>
                            <th>EMPLOYEE ID</th>
                            <th>NAME</th>
                            <th>TYPE</th>
                            <th>DEPARTMENT</th>
                            <th>POSITION</th>
                            <th>CREDENTIALS</th>
                            <th>GENDER</th>
                            <th>STATUS</th>
                            <th>EMPLOYMENT STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="pagination">
                <div class="pagination-info" id="paginationInfo">
                    Showing 1 to 10 of 14 results
                </div>
                <div class="pagination-controls">
                    <button class="pagination-btn" id="prevBtn" disabled>Previous</button>
                    <button class="pagination-btn" id="nextBtn" disabled>Next</button>
                </div>
            </div>

            <!-- FOOTER - EXACT SAME -->
            <div class="footer">
                <p>© 2024 BTech HRMS - Human Resource Management System</p>
                <p>Version 2.1.0 | Last updated: October 2024</p>
            </div>
        </main>
    </div>

    <!-- EMPLOYEE DETAILS MODAL -->
    <div id="employeeDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user"></i>
                    Employee Details
                </h3>
                <button class="close-modal" id="closeDetailsModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="employee-details-grid" id="employeeDetailsContent">
                    <!-- Details will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-actions">
                <button class="action-btn contact-btn" id="contactEmployeeBtn">
                    <i class="fas fa-envelope"></i>
                </button>
                <button class="action-btn view-btn" id="closeModalBtn">
                    <i class="fas fa-times"></i>
 
                </button>
            </div>
        </div>
    </div>

    <!-- CONTACT MODAL -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-envelope"></i>
                    Contact Employee
                </h3>
                <button class="close-modal" id="closeContactModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="employee-details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Employee Name</span>
                        <div class="detail-value" id="contactEmployeeName"></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email Address</span>
                        <div class="detail-value" id="contactEmail"></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Phone Number</span>
                        <div class="detail-value" id="contactPhone"></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Department</span>
                        <div class="detail-value" id="contactDepartment"></div>
                    </div>
                </div>

                <div class="contact-quick-actions">
                    <div class="contact-action email" onclick="sendEmail()">
                        <i class="fas fa-envelope"></i>
                        <span>Email</span>
                    </div>
                    <div class="contact-action call" onclick="makeCall()">
                        <i class="fas fa-phone"></i>
                        <span>Call</span>
                    </div>
                    <div class="contact-action sms" onclick="sendSMS()">
                        <i class="fas fa-sms"></i>
                        <span>SMS</span>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="action-btn view-btn" id="closeContactModalBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    <div id="errorModal" class="error-modal">
    <div class="error-modal-content">
        <h3><i class="fas fa-exclamation-triangle"></i> Error</h3>
        <pre id="errorModalMessage"></pre>
        <button id="errorModalOk">OK</button>
    </div>
</div>

</body>
<script src="../../js/admin/employee.js"></script>

</html>