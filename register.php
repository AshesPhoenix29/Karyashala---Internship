<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Get and sanitize inputs
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Server-side validation
if (empty($name) || empty($designation) || empty($phone) || empty($email)) {
    header("Location: index.php?error=" . urlencode("All fields are required."));
    exit();
}

if ($designation !== 'admin' && $designation !== 'employee') {
    header("Location: index.php?error=" . urlencode("Invalid designation selected."));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?error=" . urlencode("Please provide a valid email address."));
    exit();
}

if (!preg_match('/^\d{10}$/', $phone)) {
    header("Location: index.php?error=" . urlencode("Phone number must be exactly 10 digits."));
    exit();
}

// 1. Check if email is already in use in EITHER admin or employee table
$email_check_query = "
    SELECT email FROM (
        SELECT email FROM admin WHERE email = ?
        UNION ALL
        SELECT email FROM employee WHERE email = ?
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
        header("Location: index.php?error=" . urlencode("This email is already registered."));
        exit();
    }
} else {
    header("Location: index.php?error=" . urlencode("Database check preparation failed."));
    exit();
}

// 2. Find the maximum IC Number currently allocated
$max_query = "
    SELECT MAX(ic_no) as max_ic FROM (
        SELECT ic_no FROM admin
        UNION ALL
        SELECT ic_no FROM employee
    ) AS combined
";

$max_res = mysqli_query($conn, $max_query);
$maxIc = null;
if ($max_res) {
    $rowMax = mysqli_fetch_assoc($max_res);
    $maxIc = $rowMax['max_ic'];
    mysqli_free_result($max_res);
}

// Generate the next IC No (Starts at 1001)
$nextIc = $maxIc ? (int)$maxIc + 1 : 1001;

// 3. Insert the record into the appropriate table
if ($designation === 'admin') {
    $insert_query = "
        INSERT INTO admin (ic_no, name, designation, phone, email) 
        VALUES (?, ?, 'admin', ?, ?)
    ";
} else {
    $insert_query = "
        INSERT INTO employee (ic_no, name, designation, phone, email) 
        VALUES (?, ?, 'employee', ?, ?)
    ";
}

$stmt_insert = mysqli_prepare($conn, $insert_query);
$exec_success = false;

if ($stmt_insert) {
    mysqli_stmt_bind_param($stmt_insert, "isss", $nextIc, $name, $phone, $email);
    $exec_success = mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);
}

if ($exec_success) {
    header("Location: index.php?success=" . $nextIc);
    exit();
} else {
    header("Location: index.php?error=" . urlencode("An error occurred during registration: " . mysqli_error($conn)));
    exit();
}
?>
