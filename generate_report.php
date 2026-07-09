<?php
if (isset($_COOKIE[session_name()])) {
    session_start();
}

// Ensure user is logged in as Admin
if (!isset($_SESSION['user_ic']) || $_SESSION['user_designation'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("Unauthorized access."));
    exit();
}

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$selectedKaryashalaAdmins = isset($_POST['karyashala_admins']) ? $_POST['karyashala_admins'] : [];

if (empty($selectedKaryashalaAdmins) || !is_array($selectedKaryashalaAdmins)) {
    header("Location: dashboard.php?panel=karyashala-admins-report&error=" . urlencode("Please select at least one Karyashala Admin."));
    exit();
}

// Calculate the past two years
$currentYear = (int)date('Y');
$previousYear = $currentYear - 1;

$reportData = [
    'year_current' => $currentYear,
    'year_previous' => $previousYear,
    'generated_by' => $_SESSION['user_name'],
    'employees' => [] // We can keep 'employees' key for compatibility, or use both for security
];

try {
    // Query comparison statistics for each selected Karyashala Admin
    $query = "
        SELECT 
            e.ic_no, 
            e.name,
            SUM(CASE WHEN YEAR(w.attended_date) = ? THEN 1 ELSE 0 END) as count_current,
            SUM(CASE WHEN YEAR(w.attended_date) = ? THEN 1 ELSE 0 END) as count_previous
        FROM karyashala_admin e
        LEFT JOIN workshops w ON e.ic_no = w.ic_no
        WHERE e.ic_no = ?
        GROUP BY e.ic_no, e.name
    ";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {
        foreach ($selectedKaryashalaAdmins as $empId) {
            $empIdInt = (int)$empId;
            mysqli_stmt_bind_param($stmt, "iii", $currentYear, $previousYear, $empIdInt);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $reportData['employees'][] = [
                    'ic_no' => $row['ic_no'],
                    'name' => $row['name'],
                    'current_count' => (int)$row['count_current'],
                    'previous_count' => (int)$row['count_previous']
                ];
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        header("Location: dashboard.php?panel=karyashala-admins-report&error=" . urlencode("Failed to prepare stats query."));
        exit();
    }

    if (empty($reportData['employees'])) {
        header("Location: dashboard.php?panel=karyashala-admins-report&error=" . urlencode("No valid Karyashala Admin records found for selected IDs."));
        exit();
    }

    // Save report data as JSON
    $reportTitle = "Workshop Attendance Report ($previousYear - $currentYear)";
    $reportContent = json_encode($reportData);

    $insertQuery = "INSERT INTO reports (title, content) VALUES (?, ?)";
    $stmtInsert = mysqli_prepare($conn, $insertQuery);
    if ($stmtInsert) {
        mysqli_stmt_bind_param($stmtInsert, "ss", $reportTitle, $reportContent);
        $success = mysqli_stmt_execute($stmtInsert);
        mysqli_stmt_close($stmtInsert);

        if ($success) {
            header("Location: dashboard.php?panel=admin-reports&success=" . urlencode("Report successfully generated."));
            exit();
        } else {
            header("Location: dashboard.php?panel=karyashala-admins-report&error=" . urlencode("Failed to save report to database."));
            exit();
        }
    } else {
        header("Location: dashboard.php?panel=karyashala-admins-report&error=" . urlencode("Failed to prepare report storage query."));
        exit();
    }

} catch (Exception $e) {
    header("Location: dashboard.php?panel=karyashala-admins-report&error=" . urlencode("An error occurred: " . $e->getMessage()));
    exit();
}
?>
