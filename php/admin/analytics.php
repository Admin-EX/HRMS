

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
    <title>Analytics Dashboard - BTech HRMS</title>
    <link rel="stylesheet" href="../../css/admin/analytics.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app">
        <!-- SIDEBAR -->
        <!-- SIDEBAR - EXACT SAME -->
     <?php include("../components/sidebar.php"); ?>

        <!-- MAIN CONTENT -->
        <main class="main">
            <!-- TOP BAR -->
  <?php include("../components/topbar.php"); ?>


            <!-- CONTENT HEADER -->
            <div class="content-header">
                <h1>Analytics Dashboard</h1>
                <p>Track educational qualifications and civil service eligibility across all departments</p>
            </div>

            <!-- STAT CARDS - Fixed data-id attributes -->
<div class="stats">
    <?php

    // 1. Total Employees Card (with TP/NTP breakdown)
    $totalQuery = "SELECT COUNT(*) AS total_count FROM employees";
    $totalResult = mysqli_query($connection, $totalQuery);
    $totalRow = mysqli_fetch_assoc($totalResult);
    $totalEmployees = $totalRow['total_count'];
    
    $tpQuery = "SELECT COUNT(*) AS tp_count FROM employees WHERE `employee_type` = 'TP'";
    $tpResult = mysqli_query($connection, $tpQuery);
    $tpRow = mysqli_fetch_assoc($tpResult);
    $tpCount = $tpRow['tp_count'];
    
    $ntpQuery = "SELECT COUNT(*) AS ntp_count FROM employees WHERE `employee_type` = 'NTP'";
    $ntpResult = mysqli_query($connection, $ntpQuery);
    $ntpRow = mysqli_fetch_assoc($ntpResult);
    $ntpCount = $ntpRow['ntp_count'];
    ?>
    <!-- Total Employees Card -->
    <div class="stat" data-id="1">
        <div class="stat-icon-container">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $totalEmployees; ?></h3>
            <p>Total Employees</p>
            <p class="subtext">TP: <?php echo $tpCount; ?> NTP: <?php echo $ntpCount; ?></p>
        </div>
    </div>

    <?php
    // 2. Pending Leave Card
    $leaveQuery = "SELECT COUNT(*) AS pending_count FROM `leave_requests` WHERE `status` = 'Pending'";
    $leaveResult = mysqli_query($connection, $leaveQuery);
    $leaveRow = mysqli_fetch_assoc($leaveResult);
    $pendingCount = $leaveRow['pending_count'];
    ?>
    <!-- Pending Leave Card -->
    <div class="stat" data-id="2">
        <div class="stat-icon-container">
            <i class="fas fa-calendar-times"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $pendingCount; ?></h3>
            <p>Pending Leave</p>
            <p class="subtext">Requires Action</p>
        </div>
    </div>

    <?php
    // 3. Masters Card (Teaching Personnel only)
    $mastersQuery = "SELECT COUNT(*) AS masters_count FROM employees 
                     WHERE `employee_type` = 'TP' 
                     AND (`educational_attainment` LIKE '%Masters%' 
                          OR `educational_attainment` LIKE '%Master%' 
                          OR `educational_attainment` LIKE '%MS%' 
                          OR `educational_attainment` LIKE '%MA%')";
    $mastersResult = mysqli_query($connection, $mastersQuery);
    $mastersRow = mysqli_fetch_assoc($mastersResult);
    $mastersCount = $mastersRow['masters_count'];
    ?>
    <!-- Masters Card -->
    <div class="stat" data-id="3">
        <div class="stat-icon-container">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $mastersCount; ?></h3>
            <p>Masters</p>
            <p class="subtext">Teaching Personnel</p>
        </div>
    </div>

    <?php
    // 4. Civil Service Card (Non-Teaching Personnel only)
    $civilQuery = "SELECT COUNT(*) AS civil_count FROM employees 
                   WHERE `employee_type` = 'NTP' 
                   AND (`credentials` LIKE '%Civil Service%' 
                        OR `credentials` LIKE '%CSE%' 
                        OR `credentials` LIKE '%Professional%' 
                        OR `credentials` LIKE '%Subprofessional%')";
    $civilResult = mysqli_query($connection, $civilQuery);
    $civilRow = mysqli_fetch_assoc($civilResult);
    $civilCount = $civilRow['civil_count'];
    ?>
    <!-- Civil Service Card -->
    <div class="stat" data-id="4">
        <div class="stat-icon-container">
            <i class="fas fa-file-contract"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $civilCount; ?></h3>
            <p>Civil Service</p>
            <p class="subtext">Non-Teaching Personnel</p>
        </div>
    </div>

    <?php
    // 5. Doctorate Card (Teaching Personnel only)
    $doctorateQuery = "SELECT COUNT(*) AS doctorate_count FROM employees 
                       WHERE `employee_type` = 'TP' 
                       AND (`educational_attainment` LIKE '%Doctorate%' 
                            OR `educational_attainment` LIKE '%PhD%' 
                            OR `educational_attainment` LIKE '%Doctor%')";
    $doctorateResult = mysqli_query($connection, $doctorateQuery);
    $doctorateRow = mysqli_fetch_assoc($doctorateResult);
    $doctorateCount = $doctorateRow['doctorate_count'];
    ?>
    <!-- Doctorate Card -->
    <div class="stat" data-id="5">
        <div class="stat-icon-container">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $doctorateCount; ?></h3>
            <p>Doctorate</p>
            <p class="subtext">Teaching Personnel</p>
        </div>
    </div>
</div>

            <!-- ANALYTICS SECTIONS -->
            <div class="analytics-section">
                <div class="section-header">
                    <h2 class="section-title">Analytics Purpose</h2>
                    <p class="section-description">
                        This analytics section tracks educational qualifications of Teaching Personnel and civil service eligibility of Non-Teaching Personnel. This data is essential for <span class="highlight">accreditation requirements, faculty development planning, and compliance monitoring.</span> Higher credentials directly impact program quality and institutional standards.
                    </p>
                </div>
            </div>

            <!-- SIDE BY SIDE LAYOUT -->
            <div class="side-by-side">
                <?php
// Get total Teaching Personnel count
$tpQuery = "SELECT COUNT(*) AS tp_count FROM employees WHERE `employee_type` = 'TP'";
$tpResult = mysqli_query($connection, $tpQuery);
$tpRow = mysqli_fetch_assoc($tpResult);
$tpCount = $tpRow['tp_count'] ?? 0;

// Get educational attainment breakdown for Teaching Personnel
$educationQuery = "SELECT 
    SUM(CASE WHEN `educational_attainment` LIKE '%Doctorate%' OR 
                   `educational_attainment` LIKE '%PhD%' OR 
                   `educational_attainment` LIKE '%Doctor%' THEN 1 ELSE 0 END) AS doctorate_completed,
    SUM(CASE WHEN `educational_attainment` LIKE '%Taking Doctorate%' OR 
                   `educational_attainment` LIKE '%Enrolled Doctorate%' THEN 1 ELSE 0 END) AS taking_doctorate,
    SUM(CASE WHEN `educational_attainment` LIKE '%Masters%' OR 
                   `educational_attainment` LIKE '%Master%' OR 
                   `educational_attainment` LIKE '%MS%' OR 
                   `educational_attainment` LIKE '%MA%' THEN 1 ELSE 0 END) AS masters_completed,
    SUM(CASE WHEN `educational_attainment` LIKE '%Taking Masters%' OR 
                   `educational_attainment` LIKE '%Enrolled Masters%' THEN 1 ELSE 0 END) AS taking_masters,
    SUM(CASE WHEN `educational_attainment` LIKE '%Bachelor%' OR 
                   `educational_attainment` LIKE '%BS%' OR 
                   `educational_attainment` LIKE '%College%' THEN 1 ELSE 0 END) AS bachelors_only
FROM employees 
WHERE `employee_type` = 'TP'";

$educationResult = mysqli_query($connection, $educationQuery);
$educationData = mysqli_fetch_assoc($educationResult);

// Extract counts
$doctorateCompleted = $educationData['doctorate_completed'] ?? 0;
$takingDoctorate = $educationData['taking_doctorate'] ?? 0;
$mastersCompleted = $educationData['masters_completed'] ?? 0;
$takingMasters = $educationData['taking_masters'] ?? 0;
$bachelorsOnly = $educationData['bachelors_only'] ?? 0;

// Calculate percentages
$doctoratePercent = $tpCount > 0 ? round(($doctorateCompleted / $tpCount) * 100) : 0;
$takingDoctoratePercent = $tpCount > 0 ? round(($takingDoctorate / $tpCount) * 100) : 0;
$mastersPercent = $tpCount > 0 ? round(($mastersCompleted / $tpCount) * 100) : 0;
$takingMastersPercent = $tpCount > 0 ? round(($takingMasters / $tpCount) * 100) : 0;
$bachelorsPercent = $tpCount > 0 ? round(($bachelorsOnly / $tpCount) * 100) : 0;
?>

<!-- TEACHING PERSONNEL SECTION -->
<div class="analytics-section">
    <div class="section-header">
        <h2 class="section-title">Teaching Personnel Educational Attainment</h2>
        <p class="section-description">Track faculty qualifications for accreditation compliance</p>
    </div>
    
    <div class="sample-badge">SAMPLE SIZE: N=<?php echo $tpCount; ?> TEACHING PERSONNEL</div>
    
    <div class="progress-container">
        <div class="progress-item">
            <div class="progress-header">
                <span class="progress-label">Doctorate Degree (Completed)</span>
                <span class="progress-value"><?php echo $doctorateCompleted; ?> (<?php echo $doctoratePercent; ?>%)</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill doctorate" style="width: <?php echo $doctoratePercent; ?>%">
                    <?php echo $doctoratePercent; ?>%
                </div>
            </div>
        </div>
        
        <div class="progress-item">
            <div class="progress-header">
                <span class="progress-label">Taking Doctorate Units</span>
                <span class="progress-value"><?php echo $takingDoctorate; ?> (<?php echo $takingDoctoratePercent; ?>%)</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill taking-doctorate" style="width: <?php echo $takingDoctoratePercent; ?>%">
                    <?php echo $takingDoctoratePercent; ?>%
                </div>
            </div>
        </div>
        
        <div class="progress-item">
            <div class="progress-header">
                <span class="progress-label">Masters Degree (Completed)</span>
                <span class="progress-value"><?php echo $mastersCompleted; ?> (<?php echo $mastersPercent; ?>%)</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill masters" style="width: <?php echo $mastersPercent; ?>%">
                    <?php echo $mastersPercent; ?>%
                </div>
            </div>
        </div>
        
        <div class="progress-item">
            <div class="progress-header">
                <span class="progress-label">Taking Masters Units</span>
                <span class="progress-value"><?php echo $takingMasters; ?> (<?php echo $takingMastersPercent; ?>%)</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill taking-masters" style="width: <?php echo $takingMastersPercent; ?>%">
                    <?php echo $takingMastersPercent; ?>%
                </div>
            </div>
        </div>
        
        <div class="progress-item">
            <div class="progress-header">
                <span class="progress-label">Bachelor's Degree Only</span>
                <span class="progress-value"><?php echo $bachelorsOnly; ?> (<?php echo $bachelorsPercent; ?>%)</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill bachelors" style="width: <?php echo $bachelorsPercent; ?>%">
                    <?php echo $bachelorsPercent; ?>%
                </div>
            </div>
        </div>
    </div>
    
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color" style="background: #3498db;"></div>
            <span>Doctorate Degree</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #2ecc71;"></div>
            <span>Taking Doctorate</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #9b59b6;"></div>
            <span>Masters Degree</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #f39c12;"></div>
            <span>Taking Masters</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #e74c3c;"></div>
            <span>Bachelor's Only</span>
        </div>
    </div>
</div>

<?php
// Get total NTP count
$ntpQuery = "SELECT COUNT(*) AS ntp_count FROM employees WHERE `employee_type` = 'NTP'";
$ntpResult = mysqli_query($connection, $ntpQuery);
$ntpRow = mysqli_fetch_assoc($ntpResult);
$ntpCount = $ntpRow['ntp_count'] ?? 0;

// Get Civil Service Eligibility for NTP only
$eligibilityQuery = "SELECT 
    SUM(CASE WHEN `credentials` LIKE '%Professional%' THEN 1 ELSE 0 END) AS professional,
    SUM(CASE WHEN `credentials` LIKE '%Subprofessional%' OR 
                   `credentials` LIKE '%Sub-professional%' THEN 1 ELSE 0 END) AS subprofessional,
    SUM(CASE WHEN `credentials` NOT LIKE '%Professional%' AND 
                   `credentials` NOT LIKE '%Subprofessional%' AND 
                   `credentials` NOT LIKE '%Sub-professional%' AND
                   `credentials` NOT LIKE '%CSE%' AND
                   `credentials` NOT LIKE '%Civil Service%' THEN 1 ELSE 0 END) AS no_eligibility
FROM employees 
WHERE `employee_type` = 'NTP'";

$eligibilityResult = mysqli_query($connection, $eligibilityQuery);
$eligibilityData = mysqli_fetch_assoc($eligibilityResult);

$professionalCount = $eligibilityData['professional'] ?? 0;
$subprofessionalCount = $eligibilityData['subprofessional'] ?? 0;
$noEligibilityCount = $eligibilityData['no_eligibility'] ?? 0;

// Calculate percentages
$professionalPercent = $ntpCount > 0 ? round(($professionalCount / $ntpCount) * 100) : 0;
$subprofessionalPercent = $ntpCount > 0 ? round(($subprofessionalCount / $ntpCount) * 100) : 0;
$noEligibilityPercent = $ntpCount > 0 ? round(($noEligibilityCount / $ntpCount) * 100) : 0;

// Overall eligibility rate
$eligibleCount = $professionalCount + $subprofessionalCount;
$eligibilityRate = $ntpCount > 0 ? round(($eligibleCount / $ntpCount) * 100) : 0;
?>

<!-- NON-TEACHING PERSONNEL SECTION -->
<div class="infographic-card">
    <div class="infographic-header">
        <h3 class="infographic-title">Non-Teaching Personnel</h3>
        <p class="infographic-subtitle">Civil Service Eligibility</p>
    </div>
    
    <div class="sample-badge">SAMPLE SIZE: N=<?php echo $ntpCount; ?> NTP</div>
    
    <!-- ELIGIBILITY CARDS -->
    <div class="eligibility-grid">
        <div class="eligibility-card professional" data-type="professional">
            <div class="eligibility-value"><?php echo $professionalCount; ?></div>
            <div class="eligibility-label">Professional Level</div>
            <div class="eligibility-percentage"><?php echo $professionalPercent; ?>%</div>
        </div>
        
        <div class="eligibility-card subprofessional" data-type="subprofessional">
            <div class="eligibility-value"><?php echo $subprofessionalCount; ?></div>
            <div class="eligibility-label">Sub-Professional</div>
            <div class="eligibility-percentage"><?php echo $subprofessionalPercent; ?>%</div>
        </div>
        
        <div class="eligibility-card no-eligibility" data-type="no-eligibility">
            <div class="eligibility-value"><?php echo $noEligibilityCount; ?></div>
            <div class="eligibility-label">Not Yet Eligible</div>
            <div class="eligibility-percentage"><?php echo $noEligibilityPercent; ?>%</div>
        </div>
    </div>
    
    <!-- CIRCULAR PROGRESS -->
    <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; padding: 20px; margin: 20px 0; position: relative; overflow: hidden;">
        <div style="text-align: center; color: #2c3e50; font-size: 16px; font-weight: 600; margin-bottom: 20px;">
            Civil Service Eligibility Overview
        </div>
        
        <div class="circular-progress-container">
            <div class="circular-progress">
                <div class="circular-progress-bg"></div>
                <div class="circular-progress-inner">
                    <div class="circular-progress-value"><?php echo $eligibilityRate; ?>%</div>
                    <div class="circular-progress-label">Eligibility Rate</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="legend">
        <div class="legend-item">
            <div class="legend-color" style="background: #3498db;"></div>
            <span>Professional Level</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #9b59b6;"></div>
            <span>Sub-Professional</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: #e74c3c;"></div>
            <span>Not Yet Eligible</span>
        </div>
    </div>
</div>
            </div>

<?php
// Query to get department distribution
$deptQuery = "SELECT 
    `department`,
    SUM(CASE WHEN `employee_type` = 'TP' THEN 1 ELSE 0 END) AS teaching_count,
    SUM(CASE WHEN `employee_type` = 'NTP' THEN 1 ELSE 0 END) AS non_teaching_count,
    COUNT(*) AS total_count
FROM `employees` 
WHERE `department` IS NOT NULL AND `department` != ''
GROUP BY `department`
ORDER BY total_count DESC";

$deptResult = mysqli_query($connection, $deptQuery);

// Store all departments
$departments = [];
$totalTeaching = 0;
$totalNonTeaching = 0;
$grandTotal = 0;

if ($deptResult) {
    while ($row = mysqli_fetch_assoc($deptResult)) {
        $dept = $row['department'];
        $teaching = $row['teaching_count'] ?? 0;
        $nonTeaching = $row['non_teaching_count'] ?? 0;
        $total = $row['total_count'] ?? 0;
        
        // Calculate teaching distribution percentage
        $teachingPercent = $total > 0 ? round(($teaching / $total) * 100) : 0;
        
        $departments[] = [
            'name' => $dept,
            'teaching' => $teaching,
            'non_teaching' => $nonTeaching,
            'total' => $total,
            'teaching_percent' => $teachingPercent
        ];
        
        $totalTeaching += $teaching;
        $totalNonTeaching += $nonTeaching;
        $grandTotal += $total;
    }
}

// Add a total row
$departments[] = [
    'name' => '<strong>TOTAL</strong>',
    'teaching' => $totalTeaching,
    'non_teaching' => $totalNonTeaching,
    'total' => $grandTotal,
    'teaching_percent' => $grandTotal > 0 ? round(($totalTeaching / $grandTotal) * 100) : 0
];
?>

<!-- DEPARTMENT DISTRIBUTION TABLE -->
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>DEPARTMENT/INSTITUTE</th>
                <th>TEACHING PERSONNEL</th>
                <th>NON-TEACHING PERSONNEL</th>
                <th>TOTAL EMPLOYEES</th>
                <th>TEACHING DISTRIBUTION</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($departments as $index => $dept): 
                $isTotalRow = ($index === count($departments) - 1);
                $rowClass = $isTotalRow ? 'total-row' : '';
                $deptName = $isTotalRow ? $dept['name'] : htmlspecialchars($dept['name']);
            ?>
            <tr data-dept="<?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $dept['name']))); ?>" class="<?php echo $rowClass; ?>">
                <td><?php echo $deptName; ?></td>
                <td><?php echo $dept['teaching']; ?></td>
                <td><?php echo $dept['non_teaching']; ?></td>
                <td><strong><?php echo $dept['total']; ?></strong></td>
                <td>
                    <?php if (!$isTotalRow): 
                        $badgeClass = $dept['teaching_percent'] >= 80 ? 'high-dist' : 
                                     ($dept['teaching_percent'] >= 50 ? 'medium-dist' : 'low-dist');
                    ?>
                    <span class="distribution-badge <?php echo $badgeClass; ?>">
                        <?php echo $dept['teaching_percent']; ?>%
                    </span>
                    <?php else: ?>
                    <span class="distribution-badge total-dist">
                        <?php echo $dept['teaching_percent']; ?>%
                    </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

            <div class="analytics-section">
                <p style="font-size: 12px; color: #666; text-align: center;">
                    <strong>Note:</strong> Data updated as of October 2024. For detailed credential verification, contact HR department.
                </p>
            </div>

            <!-- FOOTER -->
            <?php include("../components/footer.php"); ?>
        </main>
    </div>

    <!-- MODAL -->
    <div id="analyticsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-chart-bar"></i>
                    Analytics Details
                </h3>
                <button class="close-modal" id="closeAnalyticsModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-details" id="analyticsDetails">
                    <!-- Details will be populated by JavaScript -->
                </div>
                <div class="modal-description" id="analyticsDescription">
                    <!-- Description will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-actions">
                <button class="action-btn view-btn" id="viewReportBtn">
                    <i class="fas fa-file-export"></i>
                    Export Report
                </button>
                <button class="action-btn contact-btn" id="closeAnalyticsModalBtn">
                    <i class="fas fa-times"></i>
                    Close
                </button>
            </div>
        </div>
    </div>

<script>

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
        
</script>
    <script>


// Initialize analytics data
let analyticsData = {};

// Fetch data from PHP
async function fetchAnalyticsData() {
    try {
        const response = await fetch('analytics_data.php?get_data=true');
        analyticsData = await response.json();
        initializeEventListeners();
    } catch (error) {
        console.error('Error fetching analytics data:', error);
        // Fallback to static data
        analyticsData = <?php 
            // Include the data directly for fallback
            require_once 'db_connection.php';
            require_once 'analytics_data.php';
            echo json_encode(getAnalyticsData($connection));
        ?>;
        initializeEventListeners();
    }
}

// DOM Elements
const analyticsModal = document.getElementById('analyticsModal');
const closeAnalyticsModal = document.getElementById('closeAnalyticsModal');
const closeAnalyticsModalBtn = document.getElementById('closeAnalyticsModalBtn');
const analyticsDetails = document.getElementById('analyticsDetails');
const analyticsDescription = document.getElementById('analyticsDescription');
const modalTitle = document.getElementById('modalTitle');
const viewReportBtn = document.getElementById('viewReportBtn');

// Show analytics modal
function showAnalyticsModal(data) {
    analyticsDetails.innerHTML = '';
    analyticsDescription.innerHTML = '';
    modalTitle.textContent = data.title;
    
    // Add details
    if (data.details && Array.isArray(data.details)) {
        data.details.forEach(detail => {
            const detailItem = document.createElement('div');
            detailItem.className = 'detail-item';
            detailItem.innerHTML = `
                <span class="detail-label">${detail.label}</span>
                <div class="detail-value">${detail.value}</div>
            `;
            analyticsDetails.appendChild(detailItem);
        });
    }
    
    // Add description
    if (data.description) {
        analyticsDescription.innerHTML = data.description;
    }
    
    // Show modal
    analyticsModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Initialize event listeners
function initializeEventListeners() {
    // Stat card clicks
    document.querySelectorAll('.stat').forEach(card => {
        card.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const data = analyticsData[id];
            if (data) showAnalyticsModal(data);
        });
        
        // Add hover effect
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.08)';
        });
    });

    // Table row clicks (for department table)
    document.querySelectorAll('.table-container tbody tr:not(.total-row)').forEach(row => {
        row.addEventListener('click', function() {
            const dept = this.getAttribute('data-dept');
            // Convert kebab-case to proper name
            const deptName = dept.split('-')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
            
            const data = analyticsData[deptName];
            if (data) {
                showAnalyticsModal(data);
            } else {
                // Create generic department data
                const teaching = this.cells[1].textContent;
                const nonTeaching = this.cells[2].textContent;
                const total = this.cells[3].textContent;
                const distribution = this.querySelector('.distribution-badge').textContent;
                
                const genericData = {
                    title: deptName + " Department",
                    details: [
                        { label: "Teaching Personnel", value: teaching },
                        { label: "Non-Teaching Personnel", value: nonTeaching },
                        { label: "Total Employees", value: total },
                        { label: "Teaching Distribution", value: distribution },
                        { label: "Average Age", value: "38.5" },
                        { label: "Years of Service", value: "7.2" }
                    ],
                    description: `<h4>${deptName} Department Profile</h4>This department has ${total} employees with ${distribution} teaching personnel.`
                };
                showAnalyticsModal(genericData);
            }
        });
    });

    // Turnover bar clicks
    document.querySelectorAll('.turnover-bar').forEach(bar => {
        bar.addEventListener('click', function() {
            const year = this.getAttribute('data-year');
            const data = analyticsData[year];
            if (data) showAnalyticsModal(data);
        });
    });

    // Eligibility card clicks
    document.querySelectorAll('.eligibility-card').forEach(card => {
        card.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            const value = this.querySelector('.eligibility-value').textContent;
            const percentage = this.querySelector('.eligibility-percentage').textContent;
            
            let title, details;
            
            switch(type) {
                case 'professional':
                    title = "Professional Level Eligibility";
                    details = [
                        { label: "Total Employees", value: value },
                        { label: "Percentage", value: percentage },
                        { label: "Average Age", value: "42.5" },
                        { label: "Years of Service", value: "8.3" },
                        { label: "Male", value: Math.round(value * 0.54) },
                        { label: "Female", value: Math.round(value * 0.46) }
                    ];
                    break;
                case 'subprofessional':
                    title = "Sub-Professional Level Eligibility";
                    details = [
                        { label: "Total Employees", value: value },
                        { label: "Percentage", value: percentage },
                        { label: "Average Age", value: "38.2" },
                        { label: "Years of Service", value: "6.1" },
                        { label: "Male", value: Math.round(value * 0.48) },
                        { label: "Female", value: Math.round(value * 0.52) }
                    ];
                    break;
                case 'no-eligibility':
                    title = "Not Yet Eligible Staff";
                    details = [
                        { label: "Total Employees", value: value },
                        { label: "Percentage", value: percentage },
                        { label: "Average Age", value: "35.8" },
                        { label: "Years of Service", value: "3.5" },
                        { label: "Needs Training", value: Math.round(value * 0.6) },
                        { label: "New Hires", value: Math.round(value * 0.4) }
                    ];
                    break;
            }
            
            if (title) {
                showAnalyticsModal({
                    title: title,
                    details: details,
                    description: `<h4>${title}</h4>${value} non-teaching personnel (${percentage}) fall under this category.`
                });
            }
        });
    });

    // Graph bar clicks (Leave Distribution)
    document.querySelectorAll('.graph-bar').forEach(bar => {
        bar.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            const value = this.querySelector('.graph-bar-value').textContent;
            const label = this.querySelector('.graph-bar-label').textContent;
            
            const data = {
                title: label + " Leave Analysis",
                details: [
                    { label: "Total " + label + " Leave", value: value },
                    { label: "Percentage", value: "24%" },
                    { label: "Average Duration", value: type === 'sick' ? "3.2 days" : 
                                                       type === 'vacation' ? "5.5 days" : 
                                                       type === 'emergency' ? "1.5 days" : 
                                                       type === 'maternity' ? "60 days" : "7 days" },
                    { label: "Male", value: Math.round(value * 0.52) },
                    { label: "Female", value: Math.round(value * 0.48) },
                    { label: "Approval Rate", value: "92%" }
                ],
                description: `<h4>${label} Leave Patterns</h4>${value} ${label.toLowerCase()} leave requests represent ${type === 'sick' ? 'the highest' : 'a significant'} category of leave among non-teaching personnel.`
            };
            
            showAnalyticsModal(data);
        });
    });

    // Close modal events
    closeAnalyticsModal.addEventListener('click', () => {
        analyticsModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });

    closeAnalyticsModalBtn.addEventListener('click', () => {
        analyticsModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    });

    viewReportBtn.addEventListener('click', () => {
        // Export functionality
        const modalTitleText = modalTitle.textContent;
        const details = [];
        document.querySelectorAll('.detail-item').forEach(item => {
            const label = item.querySelector('.detail-label').textContent;
            const value = item.querySelector('.detail-value').textContent;
            details.push({ label, value });
        });
        
        // Simulate export
        console.log('Exporting report:', { title: modalTitleText, details });
        alert(`Analytics report for "${modalTitleText}" exported successfully!`);
    });

    // Close modal when clicking outside
    analyticsModal.addEventListener('click', (e) => {
        if (e.target === analyticsModal) {
            analyticsModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });

    // Animation for progress bars on load
    setTimeout(() => {
        document.querySelectorAll('.progress-bar-fill').forEach(bar => {
            const currentWidth = bar.style.width;
            bar.style.width = '0%';
            
            setTimeout(() => {
                bar.style.transition = 'width 1s ease-in-out';
                bar.style.width = currentWidth;
            }, 100);
        });
    }, 500);

    // Animation for circular progress
    setTimeout(() => {
        const circularProgress = document.querySelector('.circular-progress');
        if (circularProgress) {
            const percentage = parseInt(circularProgress.querySelector('.circular-progress-value').textContent);
            const circularProgressBg = circularProgress.querySelector('.circular-progress-bg');
            
            if (circularProgressBg && !isNaN(percentage)) {
                circularProgressBg.style.background = 'conic-gradient(#ecf0f1 0deg, #ecf0f1 360deg)';
                
                setTimeout(() => {
                    circularProgressBg.style.transition = 'background 2s ease-in-out';
                    circularProgressBg.style.background = `conic-gradient(#2ecc71 0deg, #2ecc71 calc(${percentage} * 3.6deg), #ecf0f1 calc(${percentage} * 3.6deg), #ecf0f1 360deg)`;
                }, 1000);
            }
        }
    }, 1000);
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', fetchAnalyticsData);

</script>
</body>
</html>