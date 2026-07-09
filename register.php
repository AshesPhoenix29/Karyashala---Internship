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
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Server-side validation
if (empty($name) || empty($designation) || empty($phone) || empty($email) || empty($password) || empty($confirmPassword)) {
    header("Location: index.php?error=" . urlencode("All fields are required."));
    exit();
}

if ($designation !== 'admin' && $designation !== 'karyashala_admin') {
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

if (strlen($password) < 6) {
    header("Location: index.php?error=" . urlencode("Password must be at least 6 characters long."));
    exit();
}

if ($password !== $confirmPassword) {
    header("Location: index.php?error=" . urlencode("Passwords do not match."));
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
        header("Location: index.php?error=" . urlencode("This email is already registered."));
        exit();
    }
} else {
    header("Location: index.php?error=" . urlencode("Database check preparation failed."));
    exit();
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

// 3. Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 4. Insert the record into the appropriate table
if ($designation === 'admin') {
    $insert_query = "
        INSERT INTO admin (ic_no, name, designation, phone, email, password) 
        VALUES (?, ?, 'admin', ?, ?, ?)
    ";
} else {
    $insert_query = "
        INSERT INTO karyashala_admin (ic_no, name, designation, phone, email, password) 
        VALUES (?, ?, 'karyashala_admin', ?, ?, ?)
    ";
}

$stmt_insert = mysqli_prepare($conn, $insert_query);
$exec_success = false;

if ($stmt_insert) {
    mysqli_stmt_bind_param($stmt_insert, "issss", $nextIc, $name, $phone, $email, $hashedPassword);
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
