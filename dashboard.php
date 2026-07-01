<?php
// Start session only if session cookie is present
if (isset($_COOKIE[session_name()])) {
    session_start();
}

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user_ic'])) {
    header("Location: index.php?error=" . urlencode("Access denied. Please log in first."));
    exit();
}

require_once __DIR__ . '/db.php';

$icNo = $_SESSION['user_ic'];
$name = $_SESSION['user_name'];
$designation = $_SESSION['user_designation'];
$email = $_SESSION['user_email'];
$phone = $_SESSION['user_phone'];

// Determine which panel to show by default
$activePanel = isset($_GET['panel']) ? htmlspecialchars($_GET['panel']) : 'home';

$employees = [];
$reports = [];
$workshops = [];

if ($designation === 'admin') {
    // Fetch all employees for tables
    $emp_res = mysqli_query($conn, "SELECT * FROM employee ORDER BY ic_no ASC");
    if ($emp_res) {
        while ($row = mysqli_fetch_assoc($emp_res)) {
            $employees[] = $row;
        }
        mysqli_free_result($emp_res);
    }

    // Fetch all workshops grouped by employee ID
    $workshops_map = [];
    $ws_res = mysqli_query($conn, "SELECT * FROM workshops ORDER BY attended_date DESC");
    if ($ws_res) {
        while ($row = mysqli_fetch_assoc($ws_res)) {
            $workshops_map[$row['ic_no']][] = $row;
        }
        mysqli_free_result($ws_res);
    }

    // Fetch generated reports
    $rep_res = mysqli_query($conn, "SELECT * FROM reports ORDER BY created_at DESC");
    if ($rep_res) {
        while ($row = mysqli_fetch_assoc($rep_res)) {
            $reports[] = $row;
        }
        mysqli_free_result($rep_res);
    }
} else {
    // Fetch employee's workshops
    $query = "SELECT * FROM workshops WHERE ic_no = ? ORDER BY attended_date DESC";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        $icNoInt = (int)$icNo;
        mysqli_stmt_bind_param($stmt, "i", $icNoInt);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $workshops[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karyashala - Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Main Dashboard Container -->
    <div class="dashboard-wrapper">
        
        <!-- Sliding Sidebar Menu -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Karyashala</h3>
                <p><?php echo htmlspecialchars($name); ?> (<?php echo ucfirst(htmlspecialchars($designation)); ?>)</p>
            </div>
            
            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <button class="nav-item <?php echo $activePanel === 'home' ? 'active' : ''; ?>" onclick="showPanel('home')">
                            Home
                        </button>
                    </li>
                    
                    <?php if ($designation === 'admin'): ?>
                        <li class="menu-heading">Employees</li>
                        <li>
                            <button class="nav-item <?php echo $activePanel === 'employees-view' ? 'active' : ''; ?>" onclick="showPanel('employees-view')">
                                View Employees
                            </button>
                        </li>
                        <li>
                            <button class="nav-item <?php echo $activePanel === 'employees-update' ? 'active' : ''; ?>" onclick="showPanel('employees-update')">
                                Update Info
                            </button>
                        </li>
                        <li>
                            <button class="nav-item <?php echo $activePanel === 'employees-report' ? 'active' : ''; ?>" onclick="showPanel('employees-report')">
                                Get Report
                            </button>
                        </li>
                        
                        <li class="menu-heading">Admin</li>
                        <li>
                            <button class="nav-item <?php echo $activePanel === 'admin-reports' ? 'active' : ''; ?>" onclick="showPanel('admin-reports')">
                                Generated Reports
                            </button>
                        </li>
                    <?php else: ?>
                        <li class="menu-heading">Workshops</li>
                        <li>
                            <button class="nav-item <?php echo $activePanel === 'workshops' ? 'active' : ''; ?>" onclick="showPanel('workshops')">
                                My Workshops
                            </button>
                        </li>
                    <?php endif; ?>
                    
                    <li class="logout-link">
                        <a href="logout.php" class="nav-item logout-btn">Log Out</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="main-content" id="main-content">
            
            <!-- Dashboard Header -->
            <header class="main-header">
                <button class="toggle-btn" id="toggle-sidebar-btn" onclick="toggleSidebar()">
                    ✕ <!-- Icon changed dynamically via JS -->
                </button>
                <h2>Dashboard Desk</h2>
            </header>

            <!-- Alerts Box -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" id="dashboard-alert">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" id="dashboard-alert">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="panel-container">
                <!-- PANEL: HOME (Admin & Employee) -->
                <section id="panel-home" class="content-panel <?php echo $activePanel === 'home' ? 'active' : ''; ?>">
                    <h2>Profile Summary</h2>
                    <div class="profile-card">
                        <p><strong>IC Number (Emp ID):</strong> <?php echo htmlspecialchars($icNo); ?></p>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($name); ?></p>
                        <p><strong>Designation:</strong> <?php echo ucfirst(htmlspecialchars($designation)); ?></p>
                        <p><strong>Email Address:</strong> <?php echo htmlspecialchars($email); ?></p>
                        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($phone); ?></p>
                    </div>
                </section>

                <?php if ($designation === 'admin'): ?>
                    <!-- PANEL: VIEW EMPLOYEES (Admin Only) -->
                    <section id="panel-employees-view" class="content-panel <?php echo $activePanel === 'employees-view' ? 'active' : ''; ?>">
                        <h2>View Employees Directory</h2>
                        <?php if (empty($employees)): ?>
                            <p>No employees registered yet.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>IC No</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($emp['ic_no']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['phone']); ?></td>
                                            <td>
                                                <button class="icon-btn" onclick="openViewModal(<?php echo htmlspecialchars(json_encode($emp)); ?>, <?php echo htmlspecialchars(json_encode(isset($workshops_map[$emp['ic_no']]) ? $workshops_map[$emp['ic_no']] : [])); ?>)" title="View Details">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" fill="#2c3e50" style="vertical-align: middle;">
                                                        <path d="M256 96c-101.37 0-194.21 66.86-256 160 61.79 93.14 154.63 160 256 160s194.21-66.86 256-160c-61.79-93.14-154.63-160-256-160zm0 256c-53 0-96-43-96-96s43-96 96-96 96 43 96 96-43 96-96 96zm0-152c-30.93 0-56 25.07-56 56s25.07 56 56 56 56-25.07 56-56-25.07-56-56-56z"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>

                    <!-- PANEL: UPDATE EMPLOYEES (Admin Only) -->
                    <section id="panel-employees-update" class="content-panel <?php echo $activePanel === 'employees-update' ? 'active' : ''; ?>">
                        <h2>Update Employees Directory</h2>
                        <?php if (empty($employees)): ?>
                            <p>No employees registered yet.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>IC No</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($emp['ic_no']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['phone']); ?></td>
                                            <td>
                                                <button class="icon-btn edit-btn" onclick="openUpdateModal(<?php echo htmlspecialchars(json_encode($emp)); ?>)" title="Edit Details">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#27ae60" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>

                    <!-- PANEL: GET REPORT (Admin Only) -->
                    <section id="panel-employees-report" class="content-panel <?php echo $activePanel === 'employees-report' ? 'active' : ''; ?>">
                        <h2>Generate Attendance Report</h2>
                        <p>Select employees from the list below to compare workshop attendance in the past two years.</p>
                        
                        <?php if (empty($employees)): ?>
                            <p>No employees registered yet.</p>
                        <?php else: ?>
                            <form action="generate_report.php" method="POST">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;"><input type="checkbox" id="select-all-checkbox" onclick="toggleSelectAll(this)"></th>
                                            <th>IC No</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employees as $emp): ?>
                                            <tr>
                                                <td><input type="checkbox" name="employees[]" value="<?php echo htmlspecialchars($emp['ic_no']); ?>" class="emp-checkbox"></td>
                                                <td><?php echo htmlspecialchars($emp['ic_no']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <button type="submit" class="btn" style="margin-top: 15px; width: auto; padding: 10px 20px;">Generate Report</button>
                            </form>
                        <?php endif; ?>
                    </section>

                    <!-- PANEL: GENERATED REPORTS (Admin Only) -->
                    <section id="panel-admin-reports" class="content-panel <?php echo $activePanel === 'admin-reports' ? 'active' : ''; ?>">
                        <h2>Generated Reports Log</h2>
                        <?php if (empty($reports)): ?>
                            <p>No reports generated yet.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Report Title</th>
                                        <th>Created Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $rep): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($rep['title']); ?></td>
                                            <td><?php echo htmlspecialchars($rep['created_at']); ?></td>
                                            <td>
                                                <button class="btn table-btn" onclick="openReportModal(<?php echo htmlspecialchars(json_encode($rep)); ?>)">
                                                    View Report
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <!-- PANEL: MY WORKSHOPS (Employee Only) -->
                    <section id="panel-workshops" class="content-panel <?php echo $activePanel === 'workshops' ? 'active' : ''; ?>">
                        <h2>My Attended Workshops</h2>
                        
                        <div class="workshop-layout">
                            <!-- Left: List of workshops -->
                            <div class="workshop-list">
                                <h3>Registered History</h3>
                                <?php if (empty($workshops)): ?>
                                    <p>You haven't logged any workshops yet.</p>
                                <?php else: ?>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Workshop Name</th>
                                                <th>Date Attended</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($workshops as $ws): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($ws['title']); ?></td>
                                                    <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($ws['attended_date']))); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>

                            <!-- Right: Log new workshop form -->
                            <div class="workshop-form">
                                <h3>Log New Workshop</h3>
                                <form action="add_workshop.php" method="POST">
                                    <div class="form-group">
                                        <label for="workshop_title">Workshop Title:</label>
                                        <input type="text" name="title" id="workshop_title" required placeholder="e.g. Machine Learning Basics">
                                    </div>
                                    <div class="form-group">
                                        <label for="attended_date">Attended Date:</label>
                                        <input type="date" name="attended_date" id="attended_date" required max="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <button type="submit" class="btn">Add Workshop</button>
                                </form>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL: VIEW EMPLOYEE (Admin Only) -->
    <div id="view-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('view-modal')">&times;</span>
            <h2>Employee Details</h2>
            <div class="form-group">
                <label>IC Number:</label>
                <input type="text" id="view-ic" disabled>
            </div>
            <div class="form-group">
                <label>Name:</label>
                <input type="text" id="view-name" disabled>
            </div>
            <div class="form-group">
                <label>Phone Number:</label>
                <input type="text" id="view-phone" disabled>
            </div>
            <div class="form-group">
                <label>Email Address:</label>
                <input type="text" id="view-email" disabled>
            </div>
            <div class="form-group">
                <label>Registered Date:</label>
                <input type="text" id="view-date" disabled>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label style="color: #2c3e50;">Attended Workshops:</label>
                <div id="view-workshops-timeline" class="workshops-timeline" style="margin-top: 10px; max-height: 180px; overflow-y: auto; padding: 5px;">
                    <!-- Workshops details populated dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: UPDATE EMPLOYEE (Admin Only) -->
    <div id="update-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('update-modal')">&times;</span>
            <h2>Update Employee Details</h2>
            <form action="update_employee.php" method="POST" id="update-employee-form">
                <input type="hidden" name="ic_no" id="update-ic">
                
                <div class="form-group">
                    <label for="update-name">Full Name:</label>
                    <input type="text" name="name" id="update-name" required>
                    <span class="error-text" id="update-name-error"></span>
                </div>
                <div class="form-group">
                    <label for="update-phone">Phone Number:</label>
                    <input type="text" name="phone" id="update-phone" required>
                    <span class="error-text" id="update-phone-error"></span>
                </div>
                <div class="form-group">
                    <label for="update-email">Email Address:</label>
                    <input type="email" name="email" id="update-email" required>
                    <span class="error-text" id="update-email-error"></span>
                </div>
                
                <button type="submit" id="update-submit-btn" class="btn">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- MODAL: VIEW REPORT DETAILS (Admin Only) -->
    <div id="report-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeModal('report-modal')">&times;</span>
            <h2 id="report-title-label">Workshop Attendance Report</h2>
            <p style="font-size: 13px; color: #666; margin-top: -10px; margin-bottom: 20px;">
                Generated by <span id="report-author"></span>
            </p>
            
            <table class="data-table" id="report-detail-table">
                <thead>
                    <tr>
                        <th>IC No</th>
                        <th>Name</th>
                        <th id="report-th-prev">Previous Year</th>
                        <th id="report-th-curr">Current Year</th>
                    </tr>
                </thead>
                <tbody id="report-detail-tbody">
                    <!-- Loaded dynamically via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
