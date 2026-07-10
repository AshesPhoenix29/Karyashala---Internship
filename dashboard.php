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

$karyashala_admins = [];
$reports = [];
$workshops_map = [];

// Fetch all Karyashala Admins and their workshops for viewing/updating directory (visible to both Admin and Karyashala Admin)
if ($designation === 'admin' || $designation === 'karyashala_admin') {
    // Fetch all Employees for tables (aliased to match old schema)
    $emp_res = mysqli_query($conn, "SELECT ic_number AS ic_no, name, role AS designation, phone_number AS phone, email, remark, created_at FROM Employee ORDER BY ic_number ASC");
    if ($emp_res) {
        while ($row = mysqli_fetch_assoc($emp_res)) {
            $karyashala_admins[] = $row;
        }
        mysqli_free_result($emp_res);
    }

    // Fetch all workshops grouped by Employee ID
    $ws_res = mysqli_query($conn, "SELECT id, ic_number AS ic_no, title, attended_date, created_at FROM workshop ORDER BY attended_date DESC");
    if ($ws_res) {
        while ($row = mysqli_fetch_assoc($ws_res)) {
            $workshops_map[$row['ic_no']][] = $row;
        }
        mysqli_free_result($ws_res);
    }
}

$verification_grouped = [];
$verified_records_grouped = [];
if ($designation === 'admin') {
    // 1. Fetch all verified records to construct the exclusion set
    $verified_set = [];
    $verified_res = mysqli_query($conn, "SELECT ic_number AS ic_no, year FROM verified_record");
    if ($verified_res) {
        while ($row = mysqli_fetch_assoc($verified_res)) {
            $verified_set[$row['year']][$row['ic_no']] = true;
        }
        mysqli_free_result($verified_res);
    }

    // 2. Fetch and group workshops by year and employee for Verification (excluding verified ones)
    $ver_q = "SELECT w.id, w.ic_number AS ic_no, w.title, w.attended_date, w.created_at,
                     e.name, e.email, e.role AS designation, e.phone_number AS phone, e.remark, e.created_at as emp_created_at 
              FROM workshop w 
              JOIN Employee e ON w.ic_number = e.ic_number 
              ORDER BY w.attended_date DESC";
    $ver_res = mysqli_query($conn, $ver_q);
    if ($ver_res) {
        while ($row = mysqli_fetch_assoc($ver_res)) {
            $year = date('Y', strtotime($row['attended_date']));
            $ic = $row['ic_no'];
            
            // Skip already verified records
            if (isset($verified_set[$year][$ic])) {
                continue;
            }
            
            if (!isset($verification_grouped[$year])) {
                $verification_grouped[$year] = [];
            }
            if (!isset($verification_grouped[$year][$ic])) {
                $verification_grouped[$year][$ic] = [
                    'ic_no' => $ic,
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'designation' => $row['designation'],
                    'phone' => $row['phone'],
                    'remark' => $row['remark'],
                    'created_at' => $row['emp_created_at'],
                    'workshops' => []
                ];
            }
            $verification_grouped[$year][$ic]['workshops'][] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'attended_date' => $row['attended_date']
            ];
        }
        mysqli_free_result($ver_res);
    }
    krsort($verification_grouped);

    // 3. Fetch and group verified records for "Verified Records" panel
    $verified_q = "
        SELECT vr.id, vr.ic_number AS ic_no, vr.year, vr.verified_at, vr.verified_by,
               e.name, e.email, e.role AS designation, e.phone_number AS phone, e.remark, e.created_at as emp_created_at,
               (SELECT COUNT(*) FROM workshop w WHERE w.ic_number = vr.ic_number AND YEAR(w.attended_date) = vr.year) as workshops_count
        FROM verified_record vr
        JOIN Employee e ON vr.ic_number = e.ic_number
        ORDER BY vr.year DESC, vr.verified_at DESC
    ";
    $v_res = mysqli_query($conn, $verified_q);
    if ($v_res) {
        while ($row = mysqli_fetch_assoc($v_res)) {
            $year = $row['year'];
            $ic = $row['ic_no'];
            
            // We fetch the workshops attended in that year for the view details modal in Verified Records
            $workshops_in_year = [];
            $ws_y_q = "SELECT id, ic_number AS ic_no, title, attended_date, created_at FROM workshop WHERE ic_number = ? AND YEAR(attended_date) = ? ORDER BY attended_date DESC";
            $stmt_ws_y = mysqli_prepare($conn, $ws_y_q);
            if ($stmt_ws_y) {
                mysqli_stmt_bind_param($stmt_ws_y, "ii", $ic, $year);
                mysqli_stmt_execute($stmt_ws_y);
                $res_ws_y = mysqli_stmt_get_result($stmt_ws_y);
                if ($res_ws_y) {
                    while ($ws_row = mysqli_fetch_assoc($res_ws_y)) {
                        $workshops_in_year[] = $ws_row;
                    }
                }
                mysqli_stmt_close($stmt_ws_y);
            }

            if (!isset($verified_records_grouped[$year])) {
                $verified_records_grouped[$year] = [];
            }
            $verified_records_grouped[$year][] = [
                'ic_no' => $ic,
                'name' => $row['name'],
                'email' => $row['email'],
                'designation' => $row['designation'],
                'phone' => $row['phone'],
                'remark' => $row['remark'],
                'created_at' => $row['emp_created_at'],
                'verified_at' => $row['verified_at'],
                'workshops_count' => $row['workshops_count'],
                'workshops' => $workshops_in_year
            ];
        }
        mysqli_free_result($v_res);
    }
    krsort($verified_records_grouped);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karyashala - Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Main Dashboard Container -->
    <div class="dashboard-wrapper">
        
        <!-- Sliding Sidebar Menu -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo-row">
                    <img src="DRDO-logo.png" alt="DRDO Logo" class="sidebar-logo">
                    <h3>Karyashala</h3>
                </div>
                <p><?php echo htmlspecialchars($name); ?> (<?php echo str_replace('_', ' ', ucwords(htmlspecialchars($designation), '_')); ?>)</p>
            </div>
            
            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <button class="nav-item <?php echo $activePanel === 'home' ? 'active' : ''; ?>" onclick="showPanel('home')">
                            Home
                        </button>
                    </li>
                    
                    <li class="menu-heading">Employees</li>
                    <li>
                        <button class="nav-item <?php echo $activePanel === 'karyashala-admins-view' ? 'active' : ''; ?>" onclick="showPanel('karyashala-admins-view')">
                            View Employees
                        </button>
                    </li>
                    <li>
                        <button class="nav-item <?php echo $activePanel === 'karyashala-admins-update' ? 'active' : ''; ?>" onclick="showPanel('karyashala-admins-update')">
                            Update Info
                        </button>
                    </li>
                    <li>
                        <button class="nav-item <?php echo $activePanel === 'karyashala-admins-add' ? 'active' : ''; ?>" onclick="showPanel('karyashala-admins-add')">
                            Add Employee
                        </button>
                    </li>
                    
                    <?php if ($designation === 'admin'): ?>
                        <li class="menu-heading">Admin</li>
                        <li>
                            <button class="nav-item <?php echo $activePanel === 'admin-verification' ? 'active' : ''; ?>" onclick="showPanel('admin-verification')">
                                Verification
                            </button>
                        </li>
                        <li>
                            <button class="nav-item <?php echo $activePanel === 'admin-verified-records' ? 'active' : ''; ?>" onclick="showPanel('admin-verified-records')">
                                Verified Records
                            </button>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($designation === 'karyashala_admin'): ?>
                        <!-- No workshops menu needed -->
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
                <!-- PANEL: HOME (Admin & Karyashala Admin) -->
                <section id="panel-home" class="content-panel <?php echo $activePanel === 'home' ? 'active' : ''; ?>">
                    <h2>Profile Summary</h2>
                    <div class="profile-card">
                        <p><strong>IC Number:</strong> <?php echo htmlspecialchars($icNo); ?></p>
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($name); ?></p>
                        <p><strong>Designation:</strong> <?php echo str_replace('_', ' ', ucwords(htmlspecialchars($designation), '_')); ?></p>
                        <p><strong>Email Address:</strong> <?php echo htmlspecialchars($email); ?></p>
                        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($phone); ?></p>
                    </div>
                </section>

                <!-- PANEL: VIEW KARYASHALA ADMINS / EMPLOYEES (Admin & Karyashala Admin) -->
                <section id="panel-karyashala-admins-view" class="content-panel <?php echo $activePanel === 'karyashala-admins-view' ? 'active' : ''; ?>">
                    <h2>View Employees Directory</h2>
                    <?php if (empty($karyashala_admins)): ?>
                        <p><?php echo ($designation === 'admin') ? 'No Karyashala Admins registered yet.' : 'No employees registered yet.'; ?></p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>IC No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Designation</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($karyashala_admins as $emp): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($emp['ic_no']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                                        <td>
                                            <div style="display: inline-flex; gap: 5px; align-items: center;">
                                                <button class="icon-btn" onclick="openViewModal(<?php echo htmlspecialchars(json_encode($emp)); ?>, <?php echo htmlspecialchars(json_encode(isset($workshops_map[$emp['ic_no']]) ? $workshops_map[$emp['ic_no']] : [])); ?>)" title="View Details">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" fill="#2c3e50" style="vertical-align: middle;">
                                                        <path d="M256 96c-101.37 0-194.21 66.86-256 160 61.79 93.14 154.63 160 256 160s194.21-66.86 256-160c-61.79-93.14-154.63-160-256-160zm0 256c-53 0-96-43-96-96s43-96 96-96 96 43 96 96-43 96-96 96zm0-152c-30.93 0-56 25.07-56 56s25.07 56 56 56 56-25.07 56-56-25.07-56-56-56z"/>
                                                    </svg>
                                                </button>
                                                <?php if ((int)$emp['ic_no'] !== (int)$icNo): ?>
                                                    <form action="delete_employee.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this employee record? This will also delete all their registered workshops.');" style="display:inline; margin:0;">
                                                        <input type="hidden" name="ic_no" value="<?php echo htmlspecialchars($emp['ic_no']); ?>">
                                                        <button type="submit" class="icon-btn" title="Delete Record" style="background: none; border: none; padding: 4px; cursor: pointer;">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#e74c3c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

                <!-- PANEL: UPDATE KARYASHALA ADMINS / EMPLOYEES (Admin & Karyashala Admin) -->
                <section id="panel-karyashala-admins-update" class="content-panel <?php echo $activePanel === 'karyashala-admins-update' ? 'active' : ''; ?>">
                    <h2>Update Employees Directory</h2>
                    <?php if (empty($karyashala_admins)): ?>
                        <p>No employees registered yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>IC No</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Designation</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($karyashala_admins as $emp): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($emp['ic_no']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                                        <td>
                                            <button class="icon-btn edit-btn" onclick="openUpdateModal(<?php echo htmlspecialchars(json_encode($emp)); ?>, <?php echo htmlspecialchars(json_encode(isset($workshops_map[$emp['ic_no']]) ? $workshops_map[$emp['ic_no']] : [])); ?>)" title="Edit Details">
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

                <!-- PANEL: ADD KARYASHALA ADMIN / EMPLOYEE (Admin & Karyashala Admin) -->
                <section id="panel-karyashala-admins-add" class="content-panel <?php echo $activePanel === 'karyashala-admins-add' ? 'active' : ''; ?>">
                    <h2>Add Employee</h2>
                    <form action="add_employee_process.php" method="POST" id="add-employee-form" style="max-width: 500px; margin-top: 15px;">
                        
                        <div class="form-group">
                            <label for="add_name">Full Name:</label>
                            <input type="text" name="name" id="add_name" required placeholder="Enter full name">
                            <span class="error-text" id="add-name-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="add_designation">Designation:</label>
                            <input type="text" name="designation" id="add_designation" required placeholder="e.g. karyashala_admin">
                            <span class="error-text" id="add-designation-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="add_phone">Phone Number:</label>
                            <input type="text" name="phone" id="add_phone" required placeholder="10-digit mobile number">
                            <span class="error-text" id="add-phone-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="add_email">Email Address:</label>
                            <input type="email" name="email" id="add_email" required placeholder="e.g. name@domain.com">
                            <span class="error-text" id="add-email-error"></span>
                        </div>



                        <div class="form-group">
                            <label for="add_remark">Remark:</label>
                            <textarea name="remark" id="add_remark" placeholder="Enter employee remark/notes (Optional)" rows="4" style="width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 10px; font-family: inherit; font-size: 14px; box-sizing: border-box; resize: vertical;"></textarea>
                        </div>

                        <h3 style="margin-top: 25px; border-bottom: 1px dashed #ddd; padding-bottom: 5px; color: #2c3e50;">Workshop Details</h3>
                        
                        <div class="form-group">
                            <label for="add_workshop_title">Workshop Title:</label>
                            <input type="text" name="workshop_title" id="add_workshop_title" required placeholder="e.g. Data Analytics Seminar">
                            <span class="error-text" id="add-workshop-title-error"></span>
                        </div>

                        <div class="form-group">
                            <label for="add_workshop_date">Attended Date:</label>
                            <input type="date" name="workshop_date" id="add_workshop_date" required max="<?php echo date('Y-m-d'); ?>">
                            <span class="error-text" id="add-workshop-date-error"></span>
                        </div>

                        <button type="submit" id="add-submit-btn" class="btn" style="margin-top: 15px;" disabled>Add Account</button>
                    </form>
                </section>

                <?php if ($designation === 'admin'): ?>
                    <!-- PANEL: VERIFICATION (Admin Only) -->
                    <section id="panel-admin-verification" class="content-panel <?php echo $activePanel === 'admin-verification' ? 'active' : ''; ?>">
                        <h2>Verification Directory</h2>
                        <p style="margin-bottom: 20px; color: #7f8c8d;">View workshop attendance statistics grouped by the year of attendance.</p>
                        
                        <?php if (empty($verification_grouped)): ?>
                            <p style="color: #7f8c8d; font-style: italic;">No workshop logs available in the system yet.</p>
                        <?php else: ?>
                            <?php foreach ($verification_grouped as $year => $employees): ?>
                                <div class="verification-year-section" style="margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eef2f5;">
                                    <h3 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 8px; margin-top: 0; margin-bottom: 15px; font-size: 18px;">
                                        Workshop in <?php echo $year; ?>
                                    </h3>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>IC No</th>
                                                <th>Name</th>
                                                <th>Designation</th>
                                                <th>Workshops Attended</th>
                                                <th>Verification</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($employees as $emp): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($emp['ic_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                                                    <td>
                                                        <span class="badge" style="background-color: #3498db; color: #fff; padding: 3px 10px; border-radius: 12px; font-weight: bold; font-size: 12px;">
                                                            <?php echo count($emp['workshops']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="icon-btn" onclick="openVerificationModal(<?php echo htmlspecialchars(json_encode($emp)); ?>, <?php echo $year; ?>)" title="Verify Details" style="padding: 2px;">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#f39c12" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                                                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h6" />
                                                                <path d="M13 2v4h4" />
                                                                <path d="M17 6v3" />
                                                                <line x1="7" y1="7" x2="11" y2="7" />
                                                                <line x1="7" y1="11" x2="12" y2="11" />
                                                                <line x1="7" y1="15" x2="10" y2="15" />
                                                                <path d="M14 12h8" />
                                                                <path d="M14 22h8" />
                                                                <path d="M15 12c0 3 2 4 3 5c-1 1-3 2-3 5" />
                                                                <path d="M21 12c0 3-2 4-3 5c1 1 3 2 3 5" />
                                                                <path d="M16 14h4l-2 2z" fill="#f39c12" fill-opacity="0.35" />
                                                                <path d="M18 20l-2 2h4z" fill="#f39c12" fill-opacity="0.35" />
                                                                <circle cx="18" cy="17" r="1" fill="#f39c12" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>

                    <!-- PANEL: VERIFIED RECORDS (Admin Only) -->
                    <section id="panel-admin-verified-records" class="content-panel <?php echo $activePanel === 'admin-verified-records' ? 'active' : ''; ?>">
                        <h2>Verified Records Directory</h2>
                        <p style="margin-bottom: 20px; color: #7f8c8d;">Log of employee workshop attendances verified by system administrators.</p>
                        
                        <?php if (empty($verified_records_grouped)): ?>
                            <p style="color: #7f8c8d; font-style: italic;">No records have been verified yet.</p>
                        <?php else: ?>
                            <?php foreach ($verified_records_grouped as $year => $employees): ?>
                                <div class="verified-year-section" style="margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #eef2f5;">
                                    <h3 style="color: #27ae60; border-bottom: 2px solid #2ecc71; padding-bottom: 8px; margin-top: 0; margin-bottom: 15px; font-size: 18px;">
                                        Verified in <?php echo $year; ?>
                                    </h3>
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>IC No</th>
                                                <th>Name</th>
                                                <th>Designation</th>
                                                <th>Workshops Attended</th>
                                                <th>Verified At</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($employees as $emp): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($emp['ic_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($emp['designation']); ?></td>
                                                    <td>
                                                        <span class="badge" style="background-color: #2ecc71; color: #fff; padding: 3px 10px; border-radius: 12px; font-weight: bold; font-size: 12px;">
                                                            <?php echo htmlspecialchars($emp['workshops_count']); ?>
                                                        </span>
                                                    </td>
                                                    <td style="font-size: 13px; color: #7f8c8d;"><?php echo htmlspecialchars($emp['verified_at']); ?></td>
                                                    <td>
                                                        <button class="icon-btn" onclick="openVerificationModal(<?php echo htmlspecialchars(json_encode($emp)); ?>, <?php echo $year; ?>, true)" title="View Verified Details" style="padding: 2px;">
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#27ae60" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                                                                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" fill="#2ecc71" fill-opacity="0.15" />
                                                                <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12" />
                                                                <path d="M12 2c-.88 0-1.74.11-2.56.33M4.78 6.4c-.65.65-1.19 1.41-1.58 2.26M2.33 14.56c.22.82.56 1.6 1.01 2.32M7.22 20.35c.78.47 1.63.82 2.54 1.03M16.78 20.35c-.78.47-1.63.82-2.54 1.03M20.66 16.88c.45-.72.79-1.5 1.01-2.32M21.78 8.66c-.39-.85-.93-1.61-1.58-2.26M14.56 2.33c.82.22 1.6.56 2.32 1.01" />
                                                                <path d="M12 2l1.6 2.2 2.7-.3.7 2.6 2.4 1.3-1 2.5 1.7 2.1-1.7 2.1 1 2.5-2.4 1.3-.7 2.6-2.7-.3L12 22l-1.6-2.2-2.7.3-.7-2.6-2.4-1.3 1-2.5-1.7-2.1 1.7-2.1-1-2.5 2.4-1.3.7-2.6 2.7.3L12 2z" />
                                                                <path d="M9 12l2 2 4-4" stroke="#27ae60" stroke-width="2.5" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <?php if ($designation === 'karyashala_admin'): ?>
                    <!-- Workshops section deleted -->
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL: VIEW KARYASHALA ADMIN (Admin Only) -->
    <div id="view-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeModal('view-modal')">&times;</span>
            <h2>Karyashala Admin Details</h2>
            <div class="form-group">
                <label>IC Number:</label>
                <input type="text" id="view-ic" disabled>
            </div>
            <div class="form-group">
                <label>Name:</label>
                <input type="text" id="view-name" disabled>
            </div>
            <div class="form-group">
                <label>Designation:</label>
                <input type="text" id="view-designation" disabled>
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
            <div class="form-group">
                <label>Remark:</label>
                <textarea id="view-remark" disabled rows="3" style="width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 10px; font-family: inherit; font-size: 14px; box-sizing: border-box; background-color: #f8f9fa; resize: none;"></textarea>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label style="color: #2c3e50;">Attended Workshops:</label>
                <div id="view-workshops-timeline" class="workshops-timeline" style="margin-top: 10px; max-height: 180px; overflow-y: auto; padding: 5px;">
                    <!-- Workshops details populated dynamically -->
                </div>
            </div>
        </div>
    </div>    <!-- MODAL: UPDATE KARYASHALA ADMIN (Admin Only) -->
    <div id="update-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeModal('update-modal')">&times;</span>
            <h2>Update Employee Details</h2>
            
            <div class="modal-tabs">
                <button type="button" id="update-tab-personal" class="modal-tab-btn active" onclick="switchUpdateTab('personal')">Personal Details</button>
                <button type="button" id="update-tab-workshops" class="modal-tab-btn" onclick="switchUpdateTab('workshops')">Workshops</button>
            </div>
            
            <form action="update_karyashala_admin.php" method="POST" id="update-karyashala-admin-form">
                <input type="hidden" name="ic_no" id="update-ic">
                
                <!-- Tab Pane: Personal Details -->
                <div id="update-pane-personal">
                    <div class="form-group">
                        <label for="update-name">Full Name:</label>
                        <input type="text" name="name" id="update-name" required>
                        <span class="error-text" id="update-name-error"></span>
                    </div>
                    <div class="form-group">
                        <label for="update-designation">Designation:</label>
                        <input type="text" name="designation" id="update-designation" required>
                        <span class="error-text" id="update-designation-error"></span>
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
                    <div class="form-group">
                        <label for="update-remark">Remark:</label>
                        <textarea name="remark" id="update-remark" placeholder="Enter employee remark/notes (Optional)" rows="4" style="width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 10px; font-family: inherit; font-size: 14px; box-sizing: border-box; resize: vertical;"></textarea>
                    </div>
                </div>
                
                <!-- Tab Pane: Workshops -->
                <div id="update-pane-workshops" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h4 style="margin: 0; color: #2c3e50;">Attended Workshops</h4>
                        <button type="button" class="btn" style="width: auto; padding: 6px 12px; font-size: 12px; background-color: #2ecc71; border-color: #2ecc71;" onclick="addNewWorkshopInput()">+ Add Workshop</button>
                    </div>
                    <div id="update-workshops-container" style="margin-bottom: 15px; max-height: 250px; overflow-y: auto; padding-right: 5px;">
                        <!-- Workshop fields populated dynamically by JS -->
                    </div>
                </div>
                
                <button type="submit" id="update-submit-btn" class="btn" style="margin-top: 15px;">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- MODAL: VERIFICATION DETAILS (Admin Only) -->
    <div id="verification-modal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close" onclick="closeModal('verification-modal')">&times;</span>
            <h2>Verification Details (<span id="ver-year-label"></span>)</h2>
            
            <div class="form-group">
                <label>IC Number:</label>
                <input type="text" id="ver-ic" disabled>
            </div>
            <div class="form-group">
                <label>Name:</label>
                <input type="text" id="ver-name" disabled>
            </div>
            <div class="form-group">
                <label>Designation:</label>
                <input type="text" id="ver-designation" disabled>
            </div>
            <div class="form-group">
                <label>Phone Number:</label>
                <input type="text" id="ver-phone" disabled>
            </div>
            <div class="form-group">
                <label>Email Address:</label>
                <input type="text" id="ver-email" disabled>
            </div>
            <div class="form-group">
                <label>Remark:</label>
                <textarea id="ver-remark" disabled rows="3" style="width: 100%; border: 1px solid #ccc; border-radius: 4px; padding: 10px; font-family: inherit; font-size: 14px; box-sizing: border-box; background-color: #f8f9fa; resize: none;"></textarea>
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label style="color: #2c3e50;">Attended Workshops in <span id="ver-year-subtitle"></span>:</label>
                <div id="ver-workshops-list" style="margin-top: 10px; max-height: 180px; overflow-y: auto; padding: 5px; border: 1px solid #eee; border-radius: 6px; background: #fafafa;">
                    <!-- Dynamically populated workshops -->
                </div>
            </div>
            
            <form action="verify_employee.php" method="POST" id="verify-action-form" style="margin-top: 20px; border-top: 1px dashed #ddd; padding-top: 15px; display: flex; justify-content: flex-end;">
                <input type="hidden" name="ic_no" id="verify-ic-input">
                <input type="hidden" name="year" id="verify-year-input">
                <button type="submit" class="btn" style="background-color: #27ae60; border-color: #27ae60; width: auto; padding: 10px 25px;">Verify Details</button>
            </form>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
