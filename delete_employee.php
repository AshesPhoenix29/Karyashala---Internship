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

$icNoToDelete = isset($_POST['ic_no']) ? (int)$_POST['ic_no'] : 0;
$loggedInIc = (int)$_SESSION['user_ic'];

if ($icNoToDelete === 0) {
    header("Location: dashboard.php?panel=karyashala-admins-view&error=" . urlencode("Invalid employee ID."));
    exit();
}

// Self-deletion check
if ($icNoToDelete === $loggedInIc) {
    header("Location: dashboard.php?panel=karyashala-admins-view&error=" . urlencode("Security guard: You cannot delete your own active session account."));
    exit();
}

try {
    // Delete query
    $query = "DELETE FROM karyashala_admin WHERE ic_no = ?";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $icNoToDelete);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($success) {
            header("Location: dashboard.php?panel=karyashala-admins-view&success=" . urlencode("Employee record deleted successfully. The IC No " . $icNoToDelete . " is now free."));
            exit();
        } else {
            header("Location: dashboard.php?panel=karyashala-admins-view&error=" . urlencode("Failed to delete the employee record."));
            exit();
        }
    } else {
        header("Location: dashboard.php?panel=karyashala-admins-view&error=" . urlencode("Database statement preparation failed."));
        exit();
    }
} catch (Exception $e) {
    header("Location: dashboard.php?panel=karyashala-admins-view&error=" . urlencode("An error occurred: " . $e->getMessage()));
    exit();
}
?>
