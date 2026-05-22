<?php
include("../../database/connection.php");
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
    <link rel="stylesheet" href="../../css/admin/leave.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app">
        <!-- SIDEBAR -->
        <?php include("../components/sidebar.php"); ?>

        <!-- MAIN CONTENT -->
        <main class="main">
            <!-- TOP BAR -->
            <?php include("../components/topbar.php"); ?>

            <!-- CONTENT HEADER -->
            <div class="content-header">
                <h1>Leave Management</h1>
                <p>Manage employee leave requests, offset schedules, and request forms</p>
            </div>

            <!-- MAIN TABS -->
            <div class="main-tabs-container">
                <div class="main-tabs">
                    <button class="main-tab active" data-tab="leave">
                        <i class="fas fa-calendar-alt"></i>
                        Leave Requests
                    </button>
                    <button class="main-tab" data-tab="offset">
                        <i class="fas fa-exchange-alt"></i>
                        Offset Schedule
                    </button>
                    <button class="main-tab" data-tab="request">
                        <i class="fas fa-file-alt"></i>
                        Request Forms
                    </button>
                </div>
            </div>

            <!-- ======================== LEAVE TAB ======================== -->
            <div id="leaveTab" class="tab-content active">

                <!-- STATS CARDS -->
                <section class="stats">
                    <div class="stat">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="pendingRequests">0</h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="approvedRequests">0</h3>
                            <p>Approved This Month</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-icon red">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="deniedRequests">0</h3>
                            <p>Denied This Month</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-icon blue">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="autoRejected">0</h3>
                            <p>Auto-Rejected (Limit)</p>
                        </div>
                    </div>
                </section>

                <!-- ALERT BANNER -->
                <div class="alert-banner warning" id="leaveLimitAlert">
                    <div class="alert-icon">⚠️</div>
                    <div class="alert-content">
                        <h4>Leave Limit Enforcement Active</h4>
                        <p>System has automatically rejected <strong>2 requests</strong> for exceeding leave limits. Review override options if needed.</p>
                        <div class="alert-actions">
                            <button class="alert-btn" id="viewAutoRejected">
                                <i class="fas fa-eye"></i> View Auto-Rejected
                            </button>
                            <button class="alert-btn" id="configureLimits">
                                <i class="fas fa-cog"></i> Configure Limits
                            </button>
                        </div>
                    </div>
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
                                <button class="filter-tab active" data-status="all">All</button>
                                <button class="filter-tab" data-status="pending">Pending</button>
                                <button class="filter-tab" data-status="approved">Approved</button>
                                <button class="filter-tab" data-status="denied">Denied</button>
                                <button class="filter-tab" data-status="auto_rejected">Auto-Rejected</button>
                            </div>
                            <button class="export-btn" id="leaveExportBtn">
                                <i class="fas fa-file-export"></i>
                                Export Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="table-container">
                    <table id="leaveTable">
                        <thead>
                            <tr>
                                <th>REQUEST ID</th>
                                <th>EMPLOYEE</th>
                                <th>LEAVE TYPE</th>
                                <th>DATE RANGE</th>
                                <th>DAYS</th>
                                <th>LEAVE BALANCE</th>
                                <th>WET SIGNATURE</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody"></tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div class="pagination">
                    <div class="pagination-info" id="paginationInfo">Showing 0 to 0 of 0 results</div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" id="prevBtn" disabled>Previous</button>
                        <button class="pagination-btn" id="nextBtn" disabled>Next</button>
                    </div>
                </div>

                <!-- SIGNATURE WARNING -->
                <div class="signature-warning">
                    <div class="warning-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>System Rules & Limits</h4>
                    </div>
                    <div class="warning-content">
                        <p><strong>Automatic Leave Limit Enforcement:</strong> System will automatically reject leave requests if employee exceeds their allocated leave balance.</p>
                        <p><strong>Wet Signature Required:</strong> All requests must have physical wet signatures. Admin can override limits in special cases.</p>
                        <p><strong>Leave Limits Per Employee:</strong> Sick Leave (10 days), Vacation Leave (15 days), Emergency Leave (5 days) per year.</p>
                    </div>
                </div>
            </div>

            <!-- ======================== OFFSET TAB ======================== -->
            <div id="offsetTab" class="tab-content">

                <!-- OFFSET STATS -->
                <section class="stats">
                    <div class="stat">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="offsetPending">0</h3>
                            <p>Pending Offsets</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="offsetTotal">0</h3>
                            <p>Total Offsets</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-icon green">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="offsetEmployees">0</h3>
                            <p>Employees Involved</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-icon red">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="offsetSubjects">0</h3>
                            <p>Subjects Affected</p>
                        </div>
                    </div>
                </section>

                <!-- OFFSET SEARCH & FILTERS -->
                <div class="search-filters-container">
                    <div class="search-filters-wrapper">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="offsetSearchInput" placeholder="Search by employee number, subject code, or section...">
                        </div>
                        <div class="filters-wrapper">
                            <button class="export-btn" id="offsetExportBtn">
                                <i class="fas fa-file-export"></i>
                                Export Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- OFFSET TABLE -->
                <div class="table-container">
                    <table id="offsetTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>EMPLOYEE NO.</th>
                                <th>SUBJECT CODE</th>
                                <th>SUBJECT DESCRIPTION</th>
                                <th>ACADEMIC TERM</th>
                                <th>SECTION</th>
                                <th>ORIGINAL SCHEDULE</th>
                                <th>OFFSET SCHEDULE</th>
                                <th>REASON</th>
                                <th>PREPARED BY</th>
                                <th>SUBMIT DATE</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="offsetTableBody">
                            <tr><td colspan="12" style="text-align:center; padding:20px;">Loading offset records...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- OFFSET PAGINATION -->
                <div class="pagination">
                    <div class="pagination-info" id="offsetPaginationInfo">Showing 0 to 0 of 0 results</div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" id="offsetPrevBtn" disabled>Previous</button>
                        <button class="pagination-btn" id="offsetNextBtn" disabled>Next</button>
                    </div>
                </div>
            </div>

            <!-- ======================== REQUEST TAB ======================== -->
            <div id="requestTab" class="tab-content">

                <!-- REQUEST STATS -->
                <section class="stats">
                    <div class="stat">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="requestPending">0</h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-icon blue">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="requestTotal">0</h3>
                            <p>Total Requests</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-icon green">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="requestEmployees">0</h3>
                            <p>Employees Involved</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-icon red">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="requestTypes">0</h3>
                            <p>Request Types</p>
                        </div>
                    </div>
                </section>

                <!-- REQUEST SEARCH & FILTERS -->
                <div class="search-filters-container">
                    <div class="search-filters-wrapper">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="requestSearchInput" placeholder="Search by employee number, type, or prepared by...">
                        </div>
                        <div class="filters-wrapper">
                            <div class="filter-tabs" id="requestFilterTabs">
                                <button class="filter-tab active" data-type="all">All</button>
                                <!-- Types will be populated dynamically -->
                            </div>
                            <button class="export-btn" id="requestExportBtn">
                                <i class="fas fa-file-export"></i>
                                Export Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- REQUEST TABLE -->
                <div class="table-container">
                    <table id="requestTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>EMPLOYEE NO.</th>
                                <th>REQUEST TYPE</th>
                                <th>REASON</th>
                                <th>PREPARED BY</th>
                                <th>SUBMIT DATE</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="requestTableBody">
                            <tr><td colspan="7" style="text-align:center; padding:20px;">Loading request forms...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- REQUEST PAGINATION -->
                <div class="pagination">
                    <div class="pagination-info" id="requestPaginationInfo">Showing 0 to 0 of 0 results</div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" id="requestPrevBtn" disabled>Previous</button>
                        <button class="pagination-btn" id="requestNextBtn" disabled>Next</button>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <?php include("../components/footer.php"); ?>
        </main>
    </div>

    <!-- LEAVE DETAILS MODAL -->
    <div id="leaveDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-alt"></i> Leave Request Details</h3>
                <button class="close-modal" id="closeDetailsModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="leave-details-grid" id="leaveDetailsContent"></div>
                <div class="leave-limit-info" id="leaveLimitInfo"></div>
                <div class="signature-preview">
                    <h4 class="signature-title"><i class="fas fa-signature"></i> Wet Signature Verification</h4>
                    <div class="signature-image" id="signaturePreview">
                        <p style="color:#666; font-style:italic;">Signature preview will appear here</p>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="action-btn deny-btn" id="denyLeaveBtn"><i class="fas fa-times"></i></button>
                <button class="action-btn approve-btn" id="approveLeaveBtn"><i class="fas fa-check"></i></button>
                <button class="action-btn override-btn" id="overrideLimitBtn"><i class="fas fa-unlock"></i> Override Limit</button>
                <button class="action-btn view-btn" id="closeModalBtn"><i class="fas fa-times"></i></button>
            </div>
        </div>
    </div>

    <!-- OFFSET DETAILS MODAL -->
    <div id="offsetDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exchange-alt"></i> Offset Schedule Details</h3>
                <button class="close-modal" onclick="closeModal('offsetDetailsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="leave-details-grid" id="offsetDetailsContent"></div>
            </div>
            <div class="modal-actions">
                <button class="action-btn deny-btn" id="denyOffsetBtn"><i class="fas fa-times"></i> Reject</button>
                <button class="action-btn approve-btn" id="approveOffsetBtn"><i class="fas fa-check"></i> Approve</button>
                <button class="action-btn view-btn" onclick="closeModal('offsetDetailsModal')"><i class="fas fa-times"></i> Close</button>
            </div>
        </div>
    </div>

    <!-- REQUEST DETAILS MODAL -->
    <div id="requestDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-alt"></i> Request Form Details</h3>
                <button class="close-modal" onclick="closeModal('requestDetailsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="leave-details-grid" id="requestDetailsContent"></div>
            </div>
            <div class="modal-actions">
                <button class="action-btn deny-btn" id="denyRequestBtn"><i class="fas fa-times"></i> Reject</button>
                <button class="action-btn approve-btn" id="approveRequestBtn"><i class="fas fa-check"></i> Approve</button>
                <button class="action-btn view-btn" onclick="closeModal('requestDetailsModal')"><i class="fas fa-times"></i> Close</button>
            </div>
        </div>
    </div>

</body>
<script src="../../js/admin/leave.js"></script>
<!-- Reusable confirmation modal -->
<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content confirm-modal">
        <div class="modal-header">
            <h3 id="confirmTitle"><i class="fas fa-question-circle"></i> Confirm Action</h3>
            <button class="close-modal" onclick="document.getElementById('confirmModal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body confirm-body">
            <div id="confirmMessage" class="confirm-message"></div>
            <div id="confirmInputWrapper" class="confirm-input-wrapper">
                <label for="confirmReason" class="confirm-label">Reason</label>
                <textarea id="confirmReason" class="confirm-textarea"></textarea>
            </div>
        </div>
        <div class="modal-actions confirm-actions">
            <button class="action-btn view-btn" id="confirmNo">Cancel</button>
            <button class="action-btn approve-btn" id="confirmYes">Confirm</button>
        </div>
    </div>
</div>
<script>
    // ======================== MAIN TAB SWITCHING ========================
    const mainTabs = document.querySelectorAll('.main-tab');
    const tabContents = document.querySelectorAll('.tab-content');

    mainTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');

            mainTabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(targetTab + 'Tab').classList.add('active');

            if (targetTab === 'offset' && offsetRecords.length === 0) loadOffsetRecords();
            if (targetTab === 'request' && requestForms.length === 0) loadRequestForms();
        });
    });

    // ======================== CLOSE MODAL HELPER ========================
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    window.addEventListener('click', function(e) {
        ['leaveDetailsModal', 'offsetDetailsModal', 'requestDetailsModal'].forEach(id => {
            const modal = document.getElementById(id);
            if (e.target === modal) modal.style.display = 'none';
        });
    });

    // ======================== NOTIFICATION BUTTON ========================
    const notifBtn = document.getElementById('notificationBtn');
    const notifDropdown = document.getElementById('notificationDropdown');
    if (notifBtn) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
        });
        document.addEventListener('click', function(e) {
            if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.remove('active');
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            ['leaveDetailsModal', 'offsetDetailsModal', 'requestDetailsModal'].forEach(id => {
                document.getElementById(id).style.display = 'none';
            });
        }
    });
</script>
</html>