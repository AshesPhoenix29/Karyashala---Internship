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

$icNo = isset($_POST['ic_no']) ? trim($_POST['ic_no']) : '';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validation
if (empty($icNo) || empty($name) || empty($phone) || empty($email)) {
    header("Location: dashboard.php?panel=employees-update&error=" . urlencode("All fields are required."));
    exit();
}

if (!ctype_digit($icNo)) {
    header("Location: dashboard.php?panel=employees-update&error=" . urlencode("Invalid employee ID."));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: dashboard.php?panel=employees-update&error=" . urlencode("Please enter a valid email address."));
    exit();
}

if (!preg_match('/^\d{10}$/', $phone)) {
    header("Location: dashboard.php?panel=employees-update&error=" . urlencode("Phone number must be exactly 10 digits."));
    exit();
}

try {
    // Check if the email is already in use by someone else in admin or employee
    $email_check_query = "
        SELECT email FROM (
            SELECT email FROM admin WHERE email = ?
            UNION ALL
            SELECT email, ic_no FROM employee WHERE email = ? AND ic_no != ?
        ) AS combined LIMIT 1
    ";
    
    // Wait, the union returns columns, they must match in count.
    $email_check_query = "
        SELECT email FROM admin WHERE email = ?
        UNION ALL
        SELECT email FROM employee WHERE email = ? AND ic_no != ?
    ";
    
    $stmt_check = mysqli_prepare($conn, $email_check_query);
    if ($stmt_check) {
        $icNoInt = (int)$icNo;
        mysqli_stmt_bind_param($stmt_check, "ssi", $email, $email, $icNoInt);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        $num_rows = mysqli_stmt_num_rows($stmt_check);
        mysqli_stmt_close($stmt_check);
        
        if ($num_rows > 0) {
            header("Location: dashboard.php?panel=employees-update&error=" . urlencode("This email is already in use by another user."));
            exit();
        }
    }

    // Execute update statement
    $update_query = "UPDATE employee SET name = ?, phone = ?, email = ? WHERE ic_no = ?";
    $stmt_update = mysqli_prepare($conn, $update_query);
    
    if ($stmt_update) {
        $icNoInt = (int)$icNo;
        mysqli_stmt_bind_param($stmt_update, "sssi", $name, $phone, $email, $icNoInt);
        $success = mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
        
        if ($success) {
            header("Location: dashboard.php?panel=employees-update&success=" . urlencode("Employee information updated successfully."));
            exit();
        } else {
            header("Location: dashboard.php?panel=employees-update&error=" . urlencode("Failed to update employee details."));
            exit();
        }
    } else {
        header("Location: dashboard.php?panel=employees-update&error=" . urlencode("Database statement preparation failed."));
        exit();
    }
} catch (Exception $e) {
    header("Location: dashboard.php?panel=employees-update&error=" . urlencode("An error occurred: " . $e->getMessage()));
    exit();
}
?>
