<?php
include __DIR__ . '/../../database/connection.php';
session_start();
error_reporting(0);

if (empty($_SESSION['loggedUser'])) {
    header("Location: ../../index.html");
    exit;
}

$sql = "SELECT 
    employee_number AS id,
    full_name AS name,
    employee_type AS type,
    department,
    position,
    credentials,
    gender,
    address,
    phone,
    email,
    status,
    COUNT(*) OVER() AS total_count
FROM employees";
$result = mysqli_query($connection, $sql);

$employees = [];
while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../css/admin/dashboard.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="app">
        <!-- SIDEBAR - EXACT SAME AS LEAVE PAGE -->
     <?php include(__DIR__ . '/../components/sidebar.php'); ?>

        <!-- MAIN CONTENT -->
        <main class="main">
            <!-- TOP BAR -->
  <?php include(__DIR__ . '/../components/topbar.php'); ?>



            <!-- CONTENT HEADER - EXACT SAME STYLE AS LEAVE PAGE -->
            <div class="content-header">
                <h1>Dashboard Overview</h1>
                <p>Comprehensive view of employee statistics and analytics</p>
            </div>

<?php
// Single query for all stats
$query = "SELECT 
    COUNT(*) AS total_employees,
    SUM(CASE WHEN `educational_attainment` LIKE '%Masters%' OR 
    `educational_attainment` LIKE '%Taking Masters%' OR 
                   `educational_attainment` LIKE '%Bachelor%' OR 
                   `educational_attainment` LIKE '%Doctorate%' OR 
                   `educational_attainment` LIKE '%Taking Doctorate%' OR 
                   `educational_attainment` LIKE '%Graduate%' THEN 1 ELSE 0 END) AS graduate_degrees,
    SUM(CASE WHEN `credentials` LIKE '%Civil Service%' OR 
                   `credentials` LIKE '%CSE%' OR 
                   `credentials` LIKE '%Professional%' OR 
                   `credentials` LIKE '%Subprofessional%' THEN 1 ELSE 0 END) AS civil_service,
    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, `date_hired`, CURDATE()) >= 10 AND 
                   `date_hired` IS NOT NULL AND 
                   `date_hired` != '0000-00-00' THEN 1 ELSE 0 END) AS ten_plus_years
FROM employees";

$result = mysqli_query($connection, $query);
$data = mysqli_fetch_assoc($result);

$totalEmployees = $data['total_employees'] ?? 0;
$graduateCount = $data['graduate_degrees'] ?? 0;
$civilServiceCount = $data['civil_service'] ?? 0;
$tenPlusYearsCount = $data['ten_plus_years'] ?? 0;

// Announcement query for dashboard preview
$announcements = [];
$annQuery = "SELECT id, title, content, announcement_date, priority
             FROM announcements
             WHERE status = 'Active' AND announcement_date <= NOW()
             ORDER BY announcement_date DESC
             LIMIT 3";
try {
    $annResult = mysqli_query($connection, $annQuery);
    if ($annResult) {
        while ($row = mysqli_fetch_assoc($annResult)) {
            $announcements[] = $row;
        }
    }
} catch (Throwable $e) {
    // Table may not exist or announcements are not yet configured.
    $announcements = [];
}
?>

<!-- STATS CARDS - EXACT SAME STYLE AS LEAVE PAGE -->
<section class="stats">
    <div class="stat" onclick="viewTotalEmployees()">
        <div class="stat-icon blue">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $totalEmployees; ?></h3>
            <p>Total Employees</p>
        </div>
    </div>

    <div class="stat" onclick="viewGraduateDegrees()">
        <div class="stat-icon green">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $graduateCount; ?></h3>
            <p>With Graduate Degrees</p>
        </div>
    </div>

    <div class="stat" onclick="viewCivilServiceEligible()">
        <div class="stat-icon purple">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $civilServiceCount; ?></h3>
            <p>Civil Service Eligible</p>
        </div>
    </div>

    <div class="stat" onclick="viewYearsService()">
        <div class="stat-icon yellow">
            <i class="fas fa-award"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $tenPlusYearsCount; ?></h3>
            <p>10+ Years Service</p>
        </div>
    </div>
</section>

            <div class="dashboard-panel announcement-panel">
                <div class="panel-header">
                    <div>
                        <h3>Latest Announcements</h3>
                        <small>Visible to employee accounts</small>
                    </div>
                    <div class="panel-actions">
                        <button id="addAnnouncementBtn" class="action-btn view-btn" title="Add announcement">
                            <i class="fas fa-plus"></i> Add Announcement
                        </button>
                    </div>
                </div>
                <div class="announcement-list">
                    <?php if (count($announcements) > 0): ?>
                        <?php foreach ($announcements as $ann): ?>
                            <?php $important = in_array(strtolower($ann['priority']), ['high', 'urgent']); ?>
                            <div class="announcement-item<?= $important ? ' important' : '' ?>" data-id="<?= (int)$ann['id'] ?>">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                                    <div style="flex:1">
                                        <h4 class="ann-title"><?= htmlspecialchars($ann['title']) ?></h4>
                                        <div class="announcement-meta">
                                            <span class="ann-date"><?= date('M d, Y', strtotime($ann['announcement_date'])) ?></span>
                                            <span class="ann-priority"><?= ucfirst(htmlspecialchars($ann['priority'] ?: 'Normal')) ?> Priority</span>
                                        </div>
                                        <p class="ann-content"><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                                    </div>
                                    <div class="announcement-actions" style="margin-left:12px;display:flex;flex-direction:column;gap:8px;">
                                        <button class="action-btn edit-ann-btn" data-id="<?= (int)$ann['id'] ?>"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="action-btn archive-btn del-ann-btn" data-id="<?= (int)$ann['id'] ?>"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="announcement-fallback">No active announcements are available right now.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DASHBOARD CONTENT -->
            <div class="dashboard-content">
                <!-- LEFT COLUMN -->
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <div>
                            <h3>Teaching Personnel - Educational Attainment</h3>
                            <small>Breakdown by degree level and institution (n=401)</small>
                        </div>
                    </div>

                    <div class="chart-container">
                        <!-- Doctorate Degree (Completed) -->
                        <div class="chart-item">
                            <div class="chart-header">
                                <span class="chart-label">Doctorate Degree (Completed)</span>
                                <span class="chart-count" id="doctorate-count">0</span>
                            </div>
                            <div class="chart-bar-container">
                                <div class="chart-bar doctorate" id="doctorateBar">0</div>
                                <span class="chart-percentage" id="doctoratePercent">0%</span>
                            </div>
                            <div class="chart-details">
                                <span id="doctorate-school">PUP: 18+ UP: 12+ BSU: 15+ Others: 13</span>
                            </div>
                        </div>

                        <!-- Currently Taking Doctorate -->
                        <div class="chart-item">
                            <div class="chart-header">
                                <span class="chart-label">Currently Taking Doctorate</span>
                                <span class="chart-count" id="takingdoctorate-count">0</span>
                            </div>
                            <div class="chart-details">
                                <span id="takingdoctorate-school">PUP: 14+ BSU: 12+ PLM: 10+ Others: 11</span>
                            </div>
                        </div>

                        <!-- Masters Completed -->
                        <div class="chart-item">
                            <div class="chart-header">
                                <span class="chart-label">Masters Completed</span>
                                <span id="masters-count" class="chart-count">0</span>
                            </div>
                            <div class="chart-bar-container">
                                <div class="chart-bar masters" id="mastersBar">0</div>
                                <span class="chart-percentage" id="mastersPercent">0%</span>
                            </div>
                            <div class="chart-details">
                                <span id="masters-school">PUP: 25+ BSU: 22+ PLM: 18+ Others: 19</span>
                            </div>
                        </div>

                        <!-- Taking Masters Units -->
                        <div class="chart-item">
                            <div class="chart-header">
                                <span class="chart-label">Taking Masters Units</span>
                                <span class="chart-count" id="takingmasters-count">0</span>
                            </div>
                            <div class="chart-details">
                                <span id="takingmasters-school">PUP: 30+ BSU: 28+ PLM: 20+ Others: 20</span>
                            </div>
                        </div>

                        <!-- Bachelor Only -->
                        <div class="chart-item">
                            <div class="chart-header">
                                <span class="chart-label">Bachelor Only</span>
                                <span id="bachelor-count">0</span>
                            </div>
                            <div class="chart-bar-container">
                                <div class="chart-bar bachelor" id="bachelorBar">114</div>
                                <span class="chart-percentage" id="bachelorPercent">0%</span>
                            </div>
                            <div class="chart-details">
                                <span>Various Institutions</span>
                            </div>
                        </div>

                        <div class="chart-total">
                            Total Teaching Personnel: <strong id="totalTP">401 employees</strong>
                        </div>
                    </div>

                    <!-- EMPLOYMENT STATUS DETAILS -->
<div class="employment-details">
    <div class="details-header">
        <h4>Employment Status Distribution</h4>
    </div>

    <div class="details-content">
        <?php
        // Get total count of all employees
        $totalQuery = "SELECT COUNT(*) AS total_count FROM employees";
        $totalResult = mysqli_query($connection, $totalQuery);
        $totalRow = mysqli_fetch_assoc($totalResult);
        $totalEmployees = $totalRow['total_count'];
        ?>
        <div class="details-total">
            <div class="details-total-value"><?php echo $totalEmployees; ?></div>
            <div class="details-total-label">Total Employees</div>
        </div>
        
        <div class="details-categories">
            <?php
            // Get Permanent count
            $countQuery = "SELECT COUNT(*) AS total_count FROM employees WHERE `status` = 'Permanent'";
            $countResult = mysqli_query($connection, $countQuery);
            $countRow = mysqli_fetch_assoc($countResult);
            $permanentCount = $countRow['total_count'];
            $permanentPercentage = $totalEmployees > 0 ? round(($permanentCount / $totalEmployees) * 100) : 0;
            ?>
            <div class="category-item permanent" onclick="viewPermanentEmployees()">
                <div class="category-value"><?php echo $permanentCount; ?></div>
                <div class="category-label">Permanent</div>
                <div class="category-percentage"><?php echo $permanentPercentage; ?>% of total</div>
            </div>
            
            <?php
            // Get Contractual count
            $countQuery = "SELECT COUNT(*) AS total_count FROM employees WHERE `status` = 'Contractual'";
            $countResult = mysqli_query($connection, $countQuery);
            $countRow = mysqli_fetch_assoc($countResult);
            $contractualCount = $countRow['total_count'];
            $contractualPercentage = $totalEmployees > 0 ? round(($contractualCount / $totalEmployees) * 100) : 0;
            ?>
            <div class="category-item contractual" onclick="viewContractualEmployees()">
                <div class="category-value"><?php echo $contractualCount; ?></div>
                <div class="category-label">Contractual</div>
                <div class="category-percentage"><?php echo $contractualPercentage; ?>% of total</div>
            </div>
            
            <?php
            // Get COS count
            $countQuery = "SELECT COUNT(*) AS total_count FROM employees WHERE `status` = 'COS'";
            $countResult = mysqli_query($connection, $countQuery);
            $countRow = mysqli_fetch_assoc($countResult);
            $cosCount = $countRow['total_count'];
            $cosPercentage = $totalEmployees > 0 ? round(($cosCount / $totalEmployees) * 100) : 0;
            ?>
            <div class="category-item cos" onclick="viewCOSEmployees()">
                <div class="category-value"><?php echo $cosCount; ?></div>
                <div class="category-label">COS</div>
                <div class="category-percentage"><?php echo $cosPercentage; ?>% of total</div>
            </div>
        </div>
    </div>
    
    <div class="details-note">
        <strong>Note:</strong> COS (Contract of Service) updated to match <?php echo $cosPercentage; ?>% of <?php echo $totalEmployees; ?> total employees
    </div>
</div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="dashboard-panel">
                    <div class="panel-header">
                        <h3>Employment Status</h3>
                    </div>

                    <div class="status-container">
                        <div class="status-item permanent" onclick="viewPermanentEmployees()">
                            <div class="status-label">
                                <strong>Permanent</strong>
                                <small>Full-time employees</small>
                            </div>
                            <?php
                            $countQuery = "SELECT COUNT(*) AS total_count FROM employees WHERE `status` = 'Permanent'";
                            $countResult = mysqli_query($connection, $countQuery);
                            $countRow = mysqli_fetch_assoc($countResult);
                            $permanentCount = $countRow['total_count'];
                            ?>
                            <div class="status-value"><?php echo $permanentCount; ?></div>
                        </div>

                        <div class="status-item contractual" onclick="viewContractualEmployees()">
                            <div class="status-label">
                                <strong>Contractual</strong>
                                <small>Temporary employees</small>
                            </div>
                            <div class="status-value">
                                <?php
                                // Fixed spelling: "contractual" not "contructual"
                                $countQuery = "SELECT COUNT(*) AS total_count FROM employees WHERE `status` = 'Contractual'";
                                $countResult = mysqli_query($connection, $countQuery);
                                $countRow = mysqli_fetch_assoc($countResult);
                                $contractualCount = $countRow['total_count'];
                                echo $contractualCount;
                                ?>
                            </div>
                        </div>

                        <div class="status-item cos" onclick="viewCOSEmployees()">
                            <div class="status-label">
                                <strong>COS</strong>
                                <small>Contract of Service</small>
                            </div>
                            <div class="status-value">
                                <?php
                                $countQuery = "SELECT COUNT(*) AS total_count FROM employees WHERE `status` = 'COS'";
                                $countResult = mysqli_query($connection, $countQuery);
                                $countRow = mysqli_fetch_assoc($countResult);
                                $cosCount = $countRow['total_count'];
                                echo $cosCount;
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="panel-header" style="margin-top: 30px;">
                        <h3>Years of Service</h3>
                        <small>For promotion & recognition eligibility</small>
                    </div>

                    <?php
                    // Query all employees with their date_hired
                    $query = "SELECT `date_hired` FROM `employees` WHERE `date_hired` IS NOT NULL";
                    $result = mysqli_query($connection, $query);

                    // Initialize counters
                    $count30Plus = 0;
                    $count20to29 = 0;
                    $count10to19 = 0;
                    $countBelow10 = 0;
                    $currentYear = date('Y');

                    if ($result) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            if (!empty($row['date_hired'])) {
                                // Parse the date_hired
                                $hiredDate = new DateTime($row['date_hired']);
                                $currentDate = new DateTime();

                                // Calculate years of service
                                $interval = $hiredDate->diff($currentDate);
                                $yearsService = $interval->y;

                                // Categorize
                                if ($yearsService >= 30) {
                                    $count30Plus++;
                                } elseif ($yearsService >= 20) {
                                    $count20to29++;
                                } elseif ($yearsService >= 10) {
                                    $count10to19++;
                                } else {
                                    $countBelow10++;
                                }
                            }
                        }
                    }
                    ?>

                    <div class="years-container">
                        <div class="year-item" onclick="view30PlusYears()">
                            <div>
                                <div class="year-range">30+ Years</div>
                                <div class="year-category">Long service awardees</div>
                            </div>
                            <div class="year-count"><?php echo $count30Plus; ?></div>
                        </div>

                        <div class="year-item" onclick="view20to29Years()">
                            <div>
                                <div class="year-range">20-29 Years</div>
                                <div class="year-category">Senior employees</div>
                            </div>
                            <div class="year-count"><?php echo $count20to29; ?></div>
                        </div>

                        <div class="year-item" onclick="view10to19Years()">
                            <div>
                                <div class="year-range">10-19 Years</div>
                                <div class="year-category">Mid-career employees</div>
                            </div>
                            <div class="year-count"><?php echo $count10to19; ?></div>
                        </div>

                        <div class="year-item" onclick="viewBelow10Years()">
                            <div>
                                <div class="year-range">Below 10 Years</div>
                                <div class="year-category">New & junior employees</div>
                            </div>
                            <div class="year-count"><?php echo $countBelow10; ?></div>
                        </div>
                    </div>
<?php
// Get total count of NTP only (exact match)
$totalQuery = "SELECT COUNT(*) AS total_count FROM `employees` 
               WHERE `employee_type` = 'NTP'";

$totalResult = mysqli_query($connection, $totalQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalNTP = $totalRow['total_count'] ?? 0;

// Get mutually exclusive counts for NTP only
$countQuery = "SELECT 
    -- Support staff (janitors, security, maintenance) - prioritized
    SUM(CASE WHEN LOWER(`position`) LIKE '%janitor%' OR
                   LOWER(`position`) LIKE '%security%' OR
                   LOWER(`position`) LIKE '%guard%' OR
                   LOWER(`position`) LIKE '%maintenance%' OR
                   LOWER(`position`) LIKE '%utility%' OR
                   LOWER(`position`) LIKE '%driver%' OR
                   LOWER(`position`) LIKE '%clerk%' OR
                   LOWER(`position`) LIKE '%staff%' THEN 1 ELSE 0 END) AS support_count,
    
    -- Bachelor's degree holders (excluding support staff)
    SUM(CASE WHEN (LOWER(`educational_attainment`) LIKE '%bachelor%' OR
                   LOWER(`educational_attainment`) LIKE '%bs%' OR
                   LOWER(`educational_attainment`) LIKE '%college%' OR
                   LOWER(`educational_attainment`) LIKE '%graduate%') AND
                   NOT (LOWER(`position`) LIKE '%janitor%' OR
                        LOWER(`position`) LIKE '%security%' OR
                        LOWER(`position`) LIKE '%guard%' OR
                        LOWER(`position`) LIKE '%maintenance%' OR
                        LOWER(`position`) LIKE '%utility%' OR
                        LOWER(`position`) LIKE '%driver%' OR
                        LOWER(`position`) LIKE '%clerk%' OR
                        LOWER(`position`) LIKE '%staff%') THEN 1 ELSE 0 END) AS bachelors_count,
    
    -- Vocational/Technical (excluding support staff and bachelor's)
    SUM(CASE WHEN (LOWER(`educational_attainment`) LIKE '%vocational%' OR
                   LOWER(`educational_attainment`) LIKE '%technical%' OR
                   LOWER(`educational_attainment`) LIKE '%tesda%' OR
                   LOWER(`educational_attainment`) LIKE '%certificate%') AND
                   NOT (LOWER(`position`) LIKE '%janitor%' OR
                        LOWER(`position`) LIKE '%security%' OR
                        LOWER(`position`) LIKE '%guard%' OR
                        LOWER(`position`) LIKE '%maintenance%' OR
                        LOWER(`position`) LIKE '%utility%' OR
                        LOWER(`position`) LIKE '%driver%' OR
                        LOWER(`position`) LIKE '%clerk%' OR
                        LOWER(`position`) LIKE '%staff%') AND
                   NOT (LOWER(`educational_attainment`) LIKE '%bachelor%' OR
                        LOWER(`educational_attainment`) LIKE '%bs%' OR
                        LOWER(`educational_attainment`) LIKE '%college%' OR
                        LOWER(`educational_attainment`) LIKE '%graduate%') THEN 1 ELSE 0 END) AS vocational_count,
    
    -- High School (excluding all above categories)
    SUM(CASE WHEN (LOWER(`educational_attainment`) LIKE '%high school%' OR
                   LOWER(`educational_attainment`) LIKE '%secondary%' OR
                   LOWER(`educational_attainment`) LIKE '%highschool%') AND
                   NOT (LOWER(`position`) LIKE '%janitor%' OR
                        LOWER(`position`) LIKE '%security%' OR
                        LOWER(`position`) LIKE '%guard%' OR
                        LOWER(`position`) LIKE '%maintenance%' OR
                        LOWER(`position`) LIKE '%utility%' OR
                        LOWER(`position`) LIKE '%driver%' OR
                        LOWER(`position`) LIKE '%clerk%' OR
                        LOWER(`position`) LIKE '%staff%') AND
                   NOT (LOWER(`educational_attainment`) LIKE '%bachelor%' OR
                        LOWER(`educational_attainment`) LIKE '%bs%' OR
                        LOWER(`educational_attainment`) LIKE '%college%' OR
                        LOWER(`educational_attainment`) LIKE '%graduate%') AND
                   NOT (LOWER(`educational_attainment`) LIKE '%vocational%' OR
                        LOWER(`educational_attainment`) LIKE '%technical%' OR
                        LOWER(`educational_attainment`) LIKE '%tesda%' OR
                        LOWER(`educational_attainment`) LIKE '%certificate%') THEN 1 ELSE 0 END) AS highschool_count
FROM `employees` 
WHERE `employee_type` = 'NTP'";

$countResult = mysqli_query($connection, $countQuery);
$counts = mysqli_fetch_assoc($countResult);

// Set default values
$supportCount = $counts['support_count'] ?? 0;
$bachelorsCount = $counts['bachelors_count'] ?? 0;
$vocationalCount = $counts['vocational_count'] ?? 0;
$highschoolCount = $counts['highschool_count'] ?? 0;
?>

<div class="panel-header" style="margin-top: 30px;">
    <h3>Non-Teaching Personnel</h3>
    <small>Breakdown by educational level (n=<?php echo $totalNTP; ?>)</small>
</div>

<div class="non-teaching-container">
    <div class="ntp-item" onclick="viewBachelorsNTP()">
        <div class="ntp-value"><?php echo $bachelorsCount; ?></div>
        <div class="ntp-label">With Bachelor's</div>
    </div>

    <div class="ntp-item" onclick="viewVocationalNTP()">
        <div class="ntp-value"><?php echo $vocationalCount; ?></div>
        <div class="ntp-label">Vocational/Technical</div>
    </div>

    <div class="ntp-item" onclick="viewHighSchoolNTP()">
        <div class="ntp-value"><?php echo $highschoolCount; ?></div>
        <div class="ntp-label">High School</div>
    </div>

    <div class="ntp-item" onclick="viewSupportStaffNTP()">
        <div class="ntp-value"><?php echo $supportCount; ?></div>
        <div class="ntp-label">Support Staff</div>
    </div>

    <div class="ntp-note">
        Includes janitors, security guards, maintenance staff
    </div>
</div>
                </div>

                <!-- LEAVE REQUESTS PANEL -->
              <?php
// Get pending leave requests count
$countQuery = "SELECT COUNT(*) AS pending_count FROM `leave_requests` WHERE `status` = 'Pending'";
$countResult = mysqli_query($connection, $countQuery);
$countRow = mysqli_fetch_assoc($countResult);
$pendingCount = $countRow['pending_count'];

// Get pending leave requests (limit to 4 for display)
$query = "SELECT lr.*, e.full_name, e.position 
          FROM `leave_requests` lr
          LEFT JOIN `employees` e ON lr.employee_number = e.employee_number
          WHERE lr.`status` = 'Pending'
          ORDER BY lr.`date_created` DESC
          LIMIT 4";

$result = mysqli_query($connection, $query);
$leaveRequests = [];
$hasRequests = false;

if ($result && mysqli_num_rows($result) > 0) {
    $hasRequests = true;
    while ($row = mysqli_fetch_assoc($result)) {
        $leaveRequests[] = $row;
    }
}
?>

<div class="dashboard-panel" style="grid-column: 1 / -1; margin-top: 20px;">
    <div class="panel-header">
        <div>
            <h3>Pending Leave Requests</h3>
            <small>Requires immediate attention</small>
        </div>
        <span style="background: #e74c3c; color: white; padding: 4px 10px; border-radius: 20px; font-size: 13px; font-weight: 600;">
            <?php echo $pendingCount; ?> pending
        </span>
    </div>

    <div class="leave-container">
        <?php if ($hasRequests): ?>
            <?php 
            // Colors for avatars
            $avatarColors = ['#9b59b6', '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#1abc9c', '#34495e', '#d35400'];
            $colorIndex = 0;
            
            foreach ($leaveRequests as $request): 
                // Get initials for avatar
                $nameParts = explode(' ', $request['full_name'] ?? '');
                $initials = '';
                if (count($nameParts) >= 2) {
                    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts)-1], 0, 1));
                } elseif (!empty($nameParts[0])) {
                    $initials = strtoupper(substr($nameParts[0], 0, 2));
                } else {
                    $initials = 'NA';
                }
                
                // Format dates
                $startDate = date('M d', strtotime($request['start_date']));
                $endDate = date('M d', strtotime($request['end_date']));
                
                // Get color for avatar
                $avatarColor = $avatarColors[$colorIndex % count($avatarColors)];
                $colorIndex++;
            ?>
            <div class="leave-item">
                <div class="leave-avatar" style="background: <?php echo $avatarColor; ?>;">
                    <?php echo $initials; ?>
                </div>
                <div class="leave-info">
                    <strong><?php echo htmlspecialchars($request['full_name'] ?? 'Unknown'); ?></strong>
                    <p>
                        <?php echo htmlspecialchars($request['type'] ?? 'Leave'); ?> • 
                        <?php echo htmlspecialchars($request['days'] ?? 0); ?> days • 
                        <?php echo $startDate . ' - ' . $endDate; ?>
                    </p>
                </div>
                <div class="action-buttons">
                    <button class="action-btn view-btn" onclick="viewLeaveRequest(<?php echo $request['id']; ?>)">
                        <i class="fas fa-eye"></i> 
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="leave-item" style="justify-content: center; padding: 20px;">
                <div class="leave-info" style="text-align: center;">
                    <p style="color: #7f8c8d; font-style: italic;">No pending leave requests</p>
                </div>
            </div>
        <?php endif; ?>

        <a href="SuperAdminLeave.php" class="view-all">
            View All Requests
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>
            </div>

            <!-- FOOTER -->
            <?php include("../components/footer.php"); ?>
        </main>
    </div>


    <div id="addAnnouncementModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-bullhorn"></i>
                    Add Announcement
                </h3>
                <button class="close-modal" id="closeAddAnnouncementModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addAnnouncementForm">
                    <div class="form-row">
                        <label>Title</label>
                        <input type="text" name="title" id="announcementTitle" required />
                    </div>
                    <div class="form-row">
                        <label>Date</label>
                        <input type="date" name="announcement_date" id="announcementDate" value="<?php echo date('Y-m-d'); ?>" required />
                    </div>
                    <div class="form-row">
                        <label>Priority</label>
                        <select name="priority" id="announcementPriority">
                            <option value="Normal" selected>Normal</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>Content</label>
                        <textarea name="content" id="announcementContent" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-actions">
                <button class="action-btn view-btn" id="saveAnnouncementBtn"><i class="fas fa-save"></i> Save</button>
                <button class="action-btn" id="cancelAnnouncementBtn">Cancel</button>
            </div>
        </div>
    </div>

    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">
                    <i class="fas fa-users"></i>
                    Employee List
                </h3>
                <button class="close-modal" id="closeEmployeeModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="employeeSearch" placeholder="Search employees by name or ID...">
                </div>
                <div class="modal-count" id="employeeCount">Showing 0 employees</div>
                <div class="employee-list" id="employeeList">
                    <!-- Employee items will be populated here -->
                </div>
            </div>
            <div class="modal-actions">
                <button class="action-btn view-btn" id="closeModalBtn">
                    <i class="fas fa-times"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</body>
<script src="../../js/admin/dashboard.js"></script>

</html>