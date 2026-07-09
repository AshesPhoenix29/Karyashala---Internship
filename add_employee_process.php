<?php
if (isset($_COOKIE[session_name()])) {
    session_start();
}

// Ensure user is authorized
if (!isset($_SESSION['user_ic']) || ($_SESSION['user_designation'] !== 'admin' && $_SESSION['user_designation'] !== 'karyashala_admin')) {
    header("Location: index.php?error=" . urlencode("Unauthorized access."));
    exit();
}

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

// Get inputs
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$designation = isset($_POST['designation']) ? trim($_POST['designation']) : 'karyashala_admin';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';

$workshopTitle = isset($_POST['workshop_title']) ? trim($_POST['workshop_title']) : '';
$workshopDate = isset($_POST['workshop_date']) ? trim($_POST['workshop_date']) : '';

// Validation
if (empty($name) || empty($designation) || empty($phone) || empty($email) || empty($workshopTitle) || empty($workshopDate)) {
    header("Location: dashboard.php?panel=karyashala-admins-add&error=" . urlencode("All fields are required. Including Workshop Details."));
    exit();
}

if (strlen($designation) > 20) {
    header("Location: dashboard.php?panel=karyashala-admins-add&error=" . urlencode("Designation must be at most 20 characters long."));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: dashboard.php?panel=karyashala-admins-add&error=" . urlencode("Please enter a valid email address."));
    exit();
}

if (!preg_match('/^\d{10}$/', $phone)) {
    header("Location: dashboard.php?panel=karyashala-admins-add&error=" . urlencode("Phone number must be exactly 10 digits."));
    exit();
}

// 1. Check if email is already in use in EITHER admin or karyashala_admin table
$email_check_query = "
    SELECT email FROM (
        SELECT email FROM admin WHERE email = ?
        UNION ALL
        SELECT email FROM karyashala_admin WHERE email = ?
    ) AS combined LIMIT 1
";

$stmt_check = mysqli_prepare($conn, $email_check_query);
if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "ss", $email, $email);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    $num_rows = mysqli_stmt_num_rows($stmt_check);
    mysqli_stmt_close($stmt_check);
    
    if ($num_rows > 0) {
        header("Location: dashboard.php?panel=karyashala-admins-add&error=" . urlencode("This email is already registered."));
        exit();
    }
}

// 2. Find the first free IC Number starting from 1001
$query_all = "
    SELECT ic_no FROM admin
    UNION ALL
    SELECT ic_no FROM karyashala_admin
    ORDER BY ic_no ASC
";
$res_all = mysqli_query($conn, $query_all);
$allocated = [];
if ($res_all) {
    while ($row = mysqli_fetch_assoc($res_all)) {
        $allocated[] = (int)$row['ic_no'];
    }
    mysqli_free_result($res_all);
}

$nextIc = 1001;
while (in_array($nextIc, $allocated)) {
    $nextIc++;
}

// 3. Set the un-matchable password (no login access for employees added via dashboard)
$unmatchablePassword = 'NO_LOGIN';

// 4. Begin transaction (procedural style)
mysqli_begin_transaction($conn);

try {
    // 5. Insert employee into appropriate table
    if (strtolower($designation) === 'admin') {
        $insert_query = "
            INSERT INTO admin (ic_no, name, designation, phone, email, password) 
            VALUES (?, ?, 'admin', ?, ?, ?)
        ";
        $stmt_insert = mysqli_prepare($conn, $insert_query);
        if (!$stmt_insert) {
            throw new Exception("Insert query preparation failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_insert, "issss", $nextIc, $name, $phone, $email, $unmatchablePassword);
    } else {
        $insert_query = "
            INSERT INTO karyashala_admin (ic_no, name, designation, phone, email, password, remark) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt_insert = mysqli_prepare($conn, $insert_query);
        if (!$stmt_insert) {
            throw new Exception("Insert query preparation failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_insert, "issssss", $nextIc, $name, $designation, $phone, $email, $unmatchablePassword, $remark);
    }

    $exec_success = mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);

    if (!$exec_success) {
        throw new Exception("Execution of insert user query failed.");
    }

    // 6. Insert required workshop details
    // Validate date format YYYY-MM-DD
    $dateParts = explode('-', $workshopDate);
    if (count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
        throw new Exception("Invalid workshop date provided.");
    }

    $insert_ws_query = "INSERT INTO workshops (ic_no, title, attended_date) VALUES (?, ?, ?)";
    $stmt_ws = mysqli_prepare($conn, $insert_ws_query);
    if (!$stmt_ws) {
        throw new Exception("Workshop insert preparation failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt_ws, "iss", $nextIc, $workshopTitle, $workshopDate);
    $exec_ws = mysqli_stmt_execute($stmt_ws);
    mysqli_stmt_close($stmt_ws);

    if (!$exec_ws) {
        throw new Exception("Execution of workshop insert failed.");
    }

    // Commit transaction
    mysqli_commit($conn);

    header("Location: dashboard.php?panel=karyashala-admins-view&success=" . urlencode("Account added successfully! Generated IC No: " . $nextIc));
    exit();

} catch (Exception $e) {
    // Rollback transaction on failure
    mysqli_rollback($conn);
    header("Location: dashboard.php?panel=karyashala-admins-add&error=" . urlencode("Failed to add employee: " . $e->getMessage()));
    exit();
}
?>
