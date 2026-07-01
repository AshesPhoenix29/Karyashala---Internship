<?php
if (isset($_COOKIE[session_name()])) {
    session_start();
}

// Ensure user is logged in as Employee
if (!isset($_SESSION['user_ic']) || $_SESSION['user_designation'] !== 'employee') {
    header("Location: index.php?error=" . urlencode("Unauthorized access."));
    exit();
}

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

$icNo = $_SESSION['user_ic'];
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$attendedDate = isset($_POST['attended_date']) ? trim($_POST['attended_date']) : '';

// Validation
if (empty($title) || empty($attendedDate)) {
    header("Location: dashboard.php?panel=workshops&error=" . urlencode("All fields are required."));
    exit();
}

// Simple date validation check (YYYY-MM-DD)
$dateParts = explode('-', $attendedDate);
if (count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
    header("Location: dashboard.php?panel=workshops&error=" . urlencode("Please enter a valid date."));
    exit();
}

try {
    $insert_query = "INSERT INTO workshops (ic_no, title, attended_date) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_query);
    
    if ($stmt) {
        $icNoInt = (int)$icNo;
        mysqli_stmt_bind_param($stmt, "iss", $icNoInt, $title, $attendedDate);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if ($success) {
            header("Location: dashboard.php?panel=workshops&success=" . urlencode("Workshop added successfully."));
            exit();
        } else {
            header("Location: dashboard.php?panel=workshops&error=" . urlencode("Failed to save workshop record."));
            exit();
        }
    } else {
        header("Location: dashboard.php?panel=workshops&error=" . urlencode("Database statement preparation failed."));
        exit();
    }
} catch (Exception $e) {
    header("Location: dashboard.php?panel=workshops&error=" . urlencode("An error occurred: " . $e->getMessage()));
    exit();
}
?>
