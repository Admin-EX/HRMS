<?php
include("../../database/connection.php");
session_start();
if (empty($_SESSION['loggedUser'])) {
    header("Location: ../../index.html");
    exit;
}
error_reporting(0);
// Helper function for time ago
function timeAgo($datetime) {
    $date = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->d == 0 && $diff->h < 24) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return 'Just now';
            }
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->d == 1) {
        return '1 day ago';
    } elseif ($diff->d < 7) {
        return $diff->d . ' days ago';
    } else {
        return $date->format('M j, Y');
    }
}

// Get admin info
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'Super Admin';

// Count unread admin notifications  
$unread_count = 0;
if ($connection->query("SHOW TABLES LIKE 'admin_notifications'")->num_rows > 0) {
    $unread_query = "SELECT COUNT(*) as unread_count FROM admin_notifications 
                     WHERE read_status = 'unread'";
    $unread_result = $connection->query($unread_query);
    if ($unread_result) {
        $unread_row = $unread_result->fetch_assoc();
        $unread_count = $unread_row['unread_count'] ?? 0;
    }
}

// Get document statistics
$stats_query = "SELECT 
                COUNT(DISTINCT e.employee_number) as total_employees,
                SUM(CASE WHEN ed.status = 'pending' THEN 1 ELSE 0 END) as pending_approval,
                SUM(CASE WHEN ed.status = 'approved' THEN 1 ELSE 0 END) as approved_docs,
                SUM(CASE WHEN ed.status = 'rejected' THEN 1 ELSE 0 END) as rejected_docs,
                SUM(CASE WHEN ed.status = 'for_revision' THEN 1 ELSE 0 END) as for_revision
                FROM employees e
                LEFT JOIN employee_documents ed ON e.employee_number = ed.employee_number";
$stats_result = $connection->query($stats_query);
$stats = $stats_result->fetch_assoc();

$total_employees = $stats['total_employees'] ?? 0;
$pending_approval = $stats['pending_approval'] ?? 0;
$approved_docs = $stats['approved_docs'] ?? 0;
$rejected_docs = $stats['rejected_docs'] ?? 0;
$for_revision = $stats['for_revision'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Review - Admin</title>
    <link rel="stylesheet" href="../../css/admin/records.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .document-modal {
            max-width: 1400px;
        }

        .preview-modal {
            max-width: 1000px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 30px;
            border-bottom: 1px solid #e0e0e0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .modal-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .modal-header-left i {
            font-size: 28px;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .modal-header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
        }

        /* Employee Info Card */
        .employee-info-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .info-item i {
            font-size: 24px;
            color: #667eea;
        }

        .info-item small {
            display: block;
            color: #7f8c8d;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .info-item strong {
            display: block;
            color: #2c3e50;
            font-size: 15px;
        }

        /* Documents Section */
        .documents-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
        }

        .section-header h3 {
            margin: 0;
            font-size: 18px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .document-filters {
            display: flex;
            gap: 10px;
        }

        .doc-filter-select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            background: white;
            transition: all 0.3s ease;
        }

        .doc-filter-select:hover {
            border-color: #667eea;
        }

        .doc-filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Documents Table */
        .documents-table-wrapper {
            overflow-x: auto;
        }

        .documents-table {
            width: 100%;
            border-collapse: collapse;
        }

        .documents-table thead {
            background: #f8f9fa;
        }

        .documents-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e0e0e0;
        }

        .documents-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
            font-size: 14px;
        }

        .documents-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .documents-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Document Status Badges */
        .doc-status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .doc-status-badge.approved {
            background: #d4edda;
            color: #155724;
        }

        .doc-status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .doc-status-badge.for_revision {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Document Actions */
        .doc-actions {
            display: flex;
            gap: 8px;
        }

        .doc-action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .doc-action-btn.view {
            background: #3498db;
            color: white;
        }

        .doc-action-btn.view:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }

        .doc-action-btn.download {
            background: #2ecc71;
            color: white;
        }

        .doc-action-btn.download:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
        }

        .doc-action-btn.review {
            background: #f39c12;
            color: white;
        }

        .doc-action-btn.review:hover {
            background: #e67e22;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(243, 156, 18, 0.3);
        }

        /* Notes Section */
        .notes-section {
            margin-top: 25px;
            background: #fff9e6;
            border: 1px solid #ffd966;
            border-radius: 12px;
            padding: 20px;
        }

        .notes-section h3 {
            margin: 0 0 15px 0;
            color: #856404;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notes-content {
            background: white;
            padding: 15px;
            border-radius: 8px;
            color: #555;
            line-height: 1.6;
        }

        .note-item {
            padding: 12px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .note-item:last-child {
            margin-bottom: 0;
        }

        .note-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .note-type {
            font-weight: 600;
            color: #2c3e50;
        }

        .note-date {
            color: #7f8c8d;
            font-size: 12px;
        }

        .note-text {
            color: #555;
            font-size: 14px;
        }

        /* Modal Footer */
        .modal-footer {
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }

        .btn-primary, .btn-secondary {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #555;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        /* File Type Icons */
        .file-icon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .file-icon i {
            font-size: 16px;
        }

        .file-icon.pdf i {
            color: #e74c3c;
        }

        .file-icon.image i {
            color: #3498db;
        }

        .file-icon.doc i {
            color: #2980b9;
        }

        .file-icon.other i {
            color: #95a5a6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                max-height: 95vh;
            }
            
            .info-row {
                grid-template-columns: 1fr;
            }
            
            .documents-table {
                font-size: 12px;
            }
            
            .documents-table th,
            .documents-table td {
                padding: 10px 8px;
            }
            
            .doc-actions {
                flex-direction: column;
            }
        }
    </style>
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
                <h1>Document Review & Approval</h1>
                <p>Review, approve or reject employee-submitted documents</p>
            </div>

            <!-- SEARCH AND FILTERS -->
            <div class="search-filters-container">
                <div class="search-filters-wrapper">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, employee ID, or department...">
                    </div>
                    
                    <div class="filters-wrapper">
                        <!-- Filter by Type Dropdown -->
                        <div class="filter-dropdown" id="filterTypeDropdown">
                            <button class="filter-btn" id="filterTypeBtn">
                                <i class="fas fa-filter"></i>
                                <span id="filterTypeText">Employee Type</span>
                                <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 12px;"></i>
                            </button>
                            <div class="filter-dropdown-content">
                                <a href="#" data-filter="all">All Employees</a>
                                <a href="#" data-filter="tp">Teaching Personnel</a>
                                <a href="#" data-filter="ntp">Non-Teaching Personnel</a>
                            </div>
                        </div>
                        
                        <!-- Filter by Review Status Dropdown -->
                        <div class="filter-dropdown" id="filterReviewDropdown">
                            <button class="filter-btn" id="filterReviewBtn">
                                <i class="fas fa-check-circle"></i>
                                <span id="filterReviewText">Review Status</span>
                                <i class="fas fa-chevron-down" style="margin-left: auto; font-size: 12px;"></i>
                            </button>
                            <div class="filter-dropdown-content">
                                <a href="#" data-status="all">All Status</a>
                                <a href="#" data-status="pending">Pending Approval</a>
                                <a href="#" data-status="approved">Approved</a>
                                <a href="#" data-status="rejected">Rejected</a>
                                <a href="#" data-status="for_revision">For Revision</a>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <button class="pending-reviews-btn" id="pendingReviewsBtn">
                            <i class="fas fa-clock"></i>
                            Pending Reviews
                            <span class="pending-count" id="pendingReviewsCount"><?php echo $pending_approval; ?></span>
                        </button>
                        <button class="export-btn" id="exportBtn">
                            <i class="fas fa-file-export"></i>
                            Export Report
                        </button>
                    </div>
                </div>
            </div>

            <!-- STATS CARDS -->
            <section class="stats">
                <div class="stat" onclick="filterByStatus('all')">
                    <div class="stat-icon green">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="totalEmployees"><?php echo $total_employees; ?></h3>
                        <p>Total Employees</p>
                    </div>
                </div>
                
                <div class="stat" onclick="filterByStatus('pending')">
                    <div class="stat-icon yellow">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="pendingApproval"><?php echo $pending_approval; ?></h3>
                        <p>Pending Approval</p>
                    </div>
                </div>
                
                <div class="stat" onclick="filterByStatus('approved')">
                    <div class="stat-icon blue">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="approvedDocs"><?php echo $approved_docs; ?></h3>
                        <p>Approved Documents</p>
                    </div>
                </div>
                
                <div class="stat" onclick="filterByStatus('rejected')">
                    <div class="stat-icon orange">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="rejectedDocs"><?php echo $rejected_docs; ?></h3>
                        <p>Rejected Documents</p>
                    </div>
                </div>
                
                <div class="stat" onclick="filterByStatus('for_revision')">
                    <div class="stat-icon purple">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="forRevision"><?php echo $for_revision; ?></h3>
                        <p>For Revision</p>
                    </div>
                </div>
            </section>

            <!-- LOADING INDICATOR -->
            <div id="loadingIndicator" style="display: none; text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #3498db;"></i>
                <p style="margin-top: 15px; color: #7f8c8d;">Loading employee records...</p>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="table-container">
                <table id="employeeTable">
                    <thead>
                        <tr>
                            <th>EMPLOYEE ID</th>
                            <th>NAME</th>
                            <th>TYPE</th>
                            <th>DEPARTMENT</th>
                            <th>DOCUMENTS</th>
                            <th>REVIEW STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #95a5a6;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 30px;"></i>
                                <p style="margin-top: 10px;">Loading data...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="pagination">
                <div class="pagination-info" id="paginationInfo">
                    Showing 0 to 0 of 0 results
                </div>
                <div class="pagination-controls">
                    <button class="pagination-btn" id="prevBtn" disabled>
                        <i class="fas fa-chevron-left"></i> Previous
                    </button>
                    <button class="pagination-btn" id="nextBtn" disabled>
                        Next <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- FOOTER -->
            <?php include("../components/footer.php"); ?>
        </main>

        <!-- DOCUMENT VIEWER MODAL -->
        <div class="modal" id="documentModal">
            <div class="modal-content document-modal">
                <div class="modal-header">
                    <div class="modal-header-left">
                        <i class="fas fa-folder-open"></i>
                        <div>
                            <h2>Employee Documents</h2>
                            <p id="modalEmployeeName">Loading...</p>
                        </div>
                    </div>
                    <button class="modal-close" onclick="closeDocumentModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <!-- Employee Info Section -->
                    <div class="employee-info-card">
                        <div class="info-row">
                            <div class="info-item">
                                <i class="fas fa-id-card"></i>
                                <div>
                                    <small>Employee ID</small>
                                    <strong id="modalEmpId">-</strong>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-building"></i>
                                <div>
                                    <small>Department</small>
                                    <strong id="modalDepartment">-</strong>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-user-tag"></i>
                                <div>
                                    <small>Employee Type</small>
                                    <strong id="modalEmpType">-</strong>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-file-alt"></i>
                                <div>
                                    <small>Total Documents</small>
                                    <strong id="modalTotalDocs">0</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Table -->
                    <div class="documents-section">
                        <div class="section-header">
                            <h3><i class="fas fa-paperclip"></i> Uploaded Documents</h3>
                            <div class="document-filters">
                                <select id="docStatusFilter" class="doc-filter-select">
                                    <option value="all">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="for_revision">For Revision</option>
                                </select>
                            </div>
                        </div>

                        <div id="documentsLoadingIndicator" style="display: none; text-align: center; padding: 40px;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 30px; color: #3498db;"></i>
                            <p style="margin-top: 10px; color: #7f8c8d;">Loading documents...</p>
                        </div>

                        <div class="documents-table-wrapper">
                            <table class="documents-table">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Document Name</th>
                                        <th>File Size</th>
                                        <th>Upload Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Reviewed By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="documentsTableBody">
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px; color: #95a5a6;">
                                            <i class="fas fa-inbox"></i>
                                            <p>No documents found</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Document Notes Section -->
                    <div class="notes-section" id="notesSection" style="display: none;">
                        <h3><i class="fas fa-comment-dots"></i> Review Notes</h3>
                        <div id="notesContent" class="notes-content">
                            <!-- Notes will be populated here -->
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn-secondary" onclick="closeDocumentModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button class="btn-primary" onclick="printDocuments()">
                        <i class="fas fa-print"></i> Print List
                    </button>
                </div>
            </div>
        </div>

        <!-- DOCUMENT PREVIEW MODAL -->
        <div class="modal" id="documentPreviewModal">
            <div class="modal-content preview-modal">
                <div class="modal-header">
                    <h2 id="previewDocumentName">Document Preview</h2>
                    <button class="modal-close" onclick="closePreviewModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="documentPreviewContainer">
                        <iframe id="documentPreviewFrame" style="width: 100%; height: 600px; border: none;"></iframe>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" onclick="closePreviewModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button class="btn-primary" onclick="downloadDocument()">
                        <i class="fas fa-download"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../js/admin/record.js"></script>
    <script>
        // Global variables
        let currentPage = 1;
        let currentFilter = {
            employeeType: 'all',
            reviewStatus: 'all',
            search: ''
        };
        let currentDocumentPath = '';

        // Load employee data from database
        async function loadEmployeeData() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            const tableBody = document.getElementById('tableBody');
            
            // Show loading
            loadingIndicator.style.display = 'block';
            tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';

            try {
                const params = new URLSearchParams({
                    page: currentPage,
                    per_page: 10,
                    employee_type: currentFilter.employeeType,
                    review_status: currentFilter.reviewStatus,
                    search: currentFilter.search
                });

                const response = await fetch(`../../backendPHP/records_fetch.php?${params}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    displayEmployees(data.data);
                    updatePagination(data.pagination);
                    updateStatistics(data.statistics);
                } else {
                    tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #e74c3c;">Error loading data</td></tr>';
                }
            } catch (error) {
                console.error('Error loading employee data:', error);
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i><br>Error loading data. Please refresh the page.</td></tr>';
            } finally {
                loadingIndicator.style.display = 'none';
            }
        }

        // Display employees in table
        function displayEmployees(employees) {
            const tableBody = document.getElementById('tableBody');
            
            if (employees.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #95a5a6;"><i class="fas fa-inbox"></i><br>No records found</td></tr>';
                return;
            }

            tableBody.innerHTML = '';
            
            employees.forEach(emp => {
                const row = document.createElement('tr');
                
                // Status badge HTML
                let statusBadge = '';
                switch(emp.review_status) {
                    case 'pending':
                        statusBadge = '<span class="review-status-badge pending-approval">PENDING</span>';
                        break;
                    case 'approved':
                        statusBadge = '<span class="review-status-badge approved">APPROVED</span>';
                        break;
                    case 'rejected':
                        statusBadge = '<span class="review-status-badge rejected">REJECTED</span>';
                        break;
                    case 'for_revision':
                        statusBadge = '<span class="review-status-badge for-revision">FOR REVISION</span>';
                        break;
                }

                row.innerHTML = `
                    <td>${emp.employee_number}</td>
                    <td>${emp.full_name}</td>
                    <td>${emp.employee_type}</td>
                    <td>${emp.department}</td>
                    <td>${emp.documents}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn view-btn" onclick="viewProfile('${emp.employee_number}')" title="View Profile">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn review-btn" onclick="reviewDocuments('${emp.employee_number}')" title="Review Documents">
                                <i class="fas fa-file-check"></i>
                            </button>

                        </div>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        }

        // Update pagination
        function updatePagination(pagination) {
            const paginationInfo = document.getElementById('paginationInfo');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            paginationInfo.textContent = `Showing ${pagination.from} to ${pagination.to} of ${pagination.total_records} results`;
            
            prevBtn.disabled = pagination.current_page <= 1;
            nextBtn.disabled = pagination.current_page >= pagination.total_pages;
        }

        // Update statistics
        function updateStatistics(stats) {
            document.getElementById('totalEmployees').textContent = stats.total_employees;
            document.getElementById('pendingApproval').textContent = stats.pending_approval;
            document.getElementById('approvedDocs').textContent = stats.approved_docs;
            document.getElementById('rejectedDocs').textContent = stats.rejected_docs;
            document.getElementById('forRevision').textContent = stats.for_revision;
            document.getElementById('pendingReviewsCount').textContent = stats.pending_approval;
        }

        // Filter functions
        function filterByStatus(status) {
            currentFilter.reviewStatus = status;
            currentPage = 1;
            loadEmployeeData();
        }

        // View all documents for an employee
        async function viewProfile(employeeNumber) {
            const modal = document.getElementById('documentModal');
            const documentsTableBody = document.getElementById('documentsTableBody');
            const loadingIndicator = document.getElementById('documentsLoadingIndicator');
            
            // Show modal
            modal.classList.add('active');
            
            // Show loading
            loadingIndicator.style.display = 'block';
            documentsTableBody.innerHTML = '';
            
            try {
                // Fetch employee and document data
                const response = await fetch(`../../backendPHP/fetch_employee_documents.php?employee_number=${employeeNumber}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Populate employee info
                    populateEmployeeInfo(data.employee);
                    
                    // Populate documents table
                    populateDocuments(data.documents);
                } else {
                    showError('Failed to load employee documents: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error loading documents:', error);
                showError('Error loading documents. Please try again.');
            } finally {
                loadingIndicator.style.display = 'none';
            }
        }

        // Populate employee information in modal
        function populateEmployeeInfo(employee) {
            document.getElementById('modalEmployeeName').textContent = employee.full_name;
            document.getElementById('modalEmpId').textContent = employee.employee_number;
            document.getElementById('modalDepartment').textContent = employee.department || 'N/A';
            document.getElementById('modalEmpType').textContent = employee.employee_type || 'N/A';
            document.getElementById('modalTotalDocs').textContent = employee.total_documents || 0;
        }

        // Populate documents table
        function populateDocuments(documents) {
            const tableBody = document.getElementById('documentsTableBody');
            
            if (!documents || documents.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #95a5a6;">
                            <i class="fas fa-inbox" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                            <p>No documents found for this employee</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tableBody.innerHTML = '';
            
            documents.forEach(doc => {
                const row = createDocumentRow(doc);
                tableBody.appendChild(row);
            });
        }

        // Create a table row for a document
        function createDocumentRow(doc) {
            const row = document.createElement('tr');
            
            // Determine file icon
            const fileIcon = getFileIcon(doc.file_type);
            
            // Format file size
            const fileSize = formatFileSize(doc.file_size);
            
            // Format dates
            const uploadDate = formatDate(doc.upload_date);
            const expiryDate = doc.expiry_date ? formatDate(doc.expiry_date) : 'N/A';
            
            // Status badge
            const statusBadge = getStatusBadge(doc.status);
            
            // Reviewed by info
            const reviewedBy = doc.reviewed_by || 'Pending';
            
            row.innerHTML = `
                <td>
                    <div class="file-icon ${getFileClass(doc.file_type)}">
                        <i class="${fileIcon}"></i>
                        <span>${doc.document_type}</span>
                    </div>
                </td>
                <td>${doc.document_name}</td>
                <td>${fileSize}</td>
                <td>${uploadDate}</td>
                <td>${expiryDate}</td>
                <td>${statusBadge}</td>
                <td>${reviewedBy}</td>
                <td>
                    <div class="doc-actions">
                        <button class="doc-action-btn view" onclick="previewDocument('${doc.file_path}', '${doc.document_name}')" title="View Document">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button class="doc-action-btn download" onclick="downloadSingleDocument('${doc.file_path}', '${doc.document_name}')" title="Download">
                            <i class="fas fa-download"></i> Download
                        </button>
${doc.status === 'pending' ? `
    <button class="doc-action-btn approve" onclick="approveDocument(${doc.id})">
        <i class="fas fa-check"></i> Approve
    </button>
    <button class="doc-action-btn reject" onclick="rejectDocument(${doc.id})">
        <i class="fas fa-times"></i> Reject
    </button>
` : ''}    </div>
                </td>
            `;
            
            // Add click event to show notes if available
            if (doc.notes) {
                row.addEventListener('click', function(e) {
                    if (!e.target.closest('.doc-actions')) {
                        showDocumentNotes(doc);
                    }
                });
                row.style.cursor = 'pointer';
            }
            
            return row;
        }

        // Helper functions
        function getFileIcon(fileType) {
            const type = fileType.toLowerCase();
            if (type.includes('pdf')) return 'fas fa-file-pdf';
            if (type.includes('image') || type.includes('jpg') || type.includes('png') || type.includes('jpeg')) return 'fas fa-file-image';
            if (type.includes('doc') || type.includes('word')) return 'fas fa-file-word';
            if (type.includes('sheet') || type.includes('excel')) return 'fas fa-file-excel';
            return 'fas fa-file';
        }

        function getFileClass(fileType) {
            const type = fileType.toLowerCase();
            if (type.includes('pdf')) return 'pdf';
            if (type.includes('image') || type.includes('jpg') || type.includes('png') || type.includes('jpeg')) return 'image';
            if (type.includes('doc') || type.includes('word')) return 'doc';
            return 'other';
        }

        function formatFileSize(bytes) {
            if (!bytes || bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
        }

        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }

        function getStatusBadge(status) {
            const badges = {
                'pending': '<span class="doc-status-badge pending">Pending</span>',
                'approved': '<span class="doc-status-badge approved">Approved</span>',
                'rejected': '<span class="doc-status-badge rejected">Rejected</span>',
                'for_revision': '<span class="doc-status-badge for_revision">For Revision</span>'
            };
            return badges[status] || '<span class="doc-status-badge">Unknown</span>';
        }

        function showDocumentNotes(doc) {
            const notesSection = document.getElementById('notesSection');
            const notesContent = document.getElementById('notesContent');
            
            if (doc.notes) {
                notesContent.innerHTML = `
                    <div class="note-item">
                        <div class="note-header">
                            <span class="note-type">${doc.document_type}</span>
                            <span class="note-date">${formatDate(doc.review_date)}</span>
                        </div>
                        <div class="note-text">${doc.notes}</div>
                        ${doc.reviewed_by ? `<div style="margin-top: 8px; font-size: 12px; color: #7f8c8d;">Reviewed by: ${doc.reviewed_by}</div>` : ''}
                    </div>
                `;
                notesSection.style.display = 'block';
            } else {
                notesSection.style.display = 'none';
            }
        }

        function previewDocument(filePath, documentName) {
            const previewModal = document.getElementById('documentPreviewModal');
            const previewFrame = document.getElementById('documentPreviewFrame');
            const previewDocName = document.getElementById('previewDocumentName');
            
            currentDocumentPath = filePath;
            previewDocName.textContent = documentName;
            previewFrame.src = filePath;
            previewModal.classList.add('active');
        }

        function closePreviewModal() {
            const previewModal = document.getElementById('documentPreviewModal');
            const previewFrame = document.getElementById('documentPreviewFrame');
            previewModal.classList.remove('active');
            previewFrame.src = '';
            currentDocumentPath = '';
        }

        function downloadSingleDocument(filePath, documentName) {
            const link = document.createElement('a');
            link.href = filePath;
            link.download = documentName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function downloadDocument() {
            if (currentDocumentPath) {
                const link = document.createElement('a');
                link.href = currentDocumentPath;
                link.download = document.getElementById('previewDocumentName').textContent;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        function closeDocumentModal() {
            const modal = document.getElementById('documentModal');
            modal.classList.remove('active');
            document.getElementById('notesSection').style.display = 'none';
        }

        function printDocuments() {
            window.print();
        }

        function showError(message) {
            const tableBody = document.getElementById('documentsTableBody');
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #e74c3c;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 40px; display: block; margin-bottom: 10px;"></i>
                        <p>${message}</p>
                    </td>
                </tr>
            `;
        }

        function reviewDocuments(empId) {
            viewProfile(empId);
        }

        function reviewSingleDocument(documentId) {
            console.log('Review document:', documentId);
            showToast('Review functionality coming soon for document ID: ' + documentId, 'info');
        }

        function editEmployee(empId) {
            console.log('Editing employee:', empId);
            window.location.href = `edit_employee.php?id=${empId}`;
        }

        function archiveEmployee(empId) {
            console.log('Archiving employee:', empId);
            if (confirm('Are you sure you want to archive this employee?')) {
                showToast('Archive functionality coming soon', 'info');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadEmployeeData();
            
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentFilter.search = this.value;
                    currentPage = 1;
                    loadEmployeeData();
                }, 500);
            });

            // Employee type filter
            document.querySelectorAll('#filterTypeDropdown .filter-dropdown-content a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentFilter.employeeType = this.dataset.filter;
                    document.getElementById('filterTypeText').textContent = this.textContent;
                    currentPage = 1;
                    loadEmployeeData();
                });
            });

            // Review status filter
            document.querySelectorAll('#filterReviewDropdown .filter-dropdown-content a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    currentFilter.reviewStatus = this.dataset.status;
                    document.getElementById('filterReviewText').textContent = this.textContent;
                    currentPage = 1;
                    loadEmployeeData();
                });
            });

            // Pagination
            document.getElementById('prevBtn').addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    loadEmployeeData();
                }
            });

            document.getElementById('nextBtn').addEventListener('click', function() {
                currentPage++;
                loadEmployeeData();
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
        });
        // Add this to your existing JavaScript in the records.php file

// Export Report Button Handler
document.getElementById('exportBtn').addEventListener('click', function() {
    // Show loading state
    const originalContent = this.innerHTML;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
    this.disabled = true;
    showToast('Preparing export report...', 'info');
    
    // Build URL with current filters
    const params = new URLSearchParams({
        employee_type: currentFilter.employeeType,
        review_status: currentFilter.reviewStatus,
        search: currentFilter.search
    });
    
    // Redirect to export PHP file
    window.location.href = `../../backendPHP/export_records_report.php?${params}`;
    
    // Reset button after a delay (file download won't navigate away from page)
    setTimeout(() => {
        this.innerHTML = originalContent;
        this.disabled = false;
    }, 2000);
});

// Alternative: Open in new tab instead of current window
// Uncomment this and comment out the above if you prefer
/*
document.getElementById('exportBtn').addEventListener('click', function() {
    const params = new URLSearchParams({
        employee_type: currentFilter.employeeType,
        review_status: currentFilter.reviewStatus,
        search: currentFilter.search
    });
    
    window.open(`../../backendPHP/export_records_report.php?${params}`, '_blank');
});
*/

// Toast helper: creates a pop-up notification container and shows messages
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
    const background = type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#34495e';
    Object.assign(toast.style, {
        background,
        color: '#fff',
        padding: '10px 14px',
        borderRadius: '8px',
        boxShadow: '0 6px 18px rgba(0,0,0,0.14)',
        opacity: '0',
        transform: 'translateY(-10px)',
        transition: 'opacity 220ms ease, transform 220ms ease',
        maxWidth: '360px',
        fontSize: '14px',
        lineHeight: '1.4'
    });

    container.appendChild(toast);
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            if (toast.parentNode === container) container.removeChild(toast);
        }, 260);
    }, duration);
}

async function approveDocument(documentId) {
    if (!confirm('Approve this document?')) return;
    const empId = document.getElementById('modalEmpId').textContent;
    const res = await fetch('../../backendPHP/update_document_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ document_id: documentId, status: 'approved' })
    });
    const data = await res.json();
    if (data.success) {
        showToast('Document approved successfully.', 'success');
        viewProfile(empId);
        loadEmployeeData();
    } else {
        showToast('Error: ' + data.message, 'error');
    }
}

async function rejectDocument(documentId) {
    const reason = prompt('Rejection reason (optional):');
    if (reason === null) return;
    const empId = document.getElementById('modalEmpId').textContent;
    const res = await fetch('../../backendPHP/update_document_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ document_id: documentId, status: 'rejected', notes: reason })
    });
    const data = await res.json();
    if (data.success) {
        showToast('Document rejected successfully.', 'success');
        viewProfile(empId);
        loadEmployeeData();
    } else {
        showToast('Error: ' + data.message, 'error');
    }
}
    </script>
</body>
</html>