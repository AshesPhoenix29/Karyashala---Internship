<?php
session_start();
require_once 'db.php';

// Authentication and role check
if (!isset($_SESSION['user_ic']) || $_SESSION['user_designation'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $icNo = isset($_POST['ic_no']) ? trim($_POST['ic_no']) : '';
    $year = isset($_POST['year']) ? trim($_POST['year']) : '';
    $verifiedBy = $_SESSION['user_ic'];

    if (empty($icNo) || empty($year)) {
        header("Location: dashboard.php?panel=admin-verification&error=" . urlencode("Employee ID and Year are required for verification."));
        exit();
    }

    if (!ctype_digit($icNo) || !ctype_digit($year)) {
        header("Location: dashboard.php?panel=admin-verification&error=" . urlencode("Invalid parameters provided."));
        exit();
    }

    $icNoInt = (int)$icNo;
    $yearInt = (int)$year;
    $verifiedByInt = (int)$verifiedBy;

    try {
        // Prepare statement to insert verified record
        $insert_query = "INSERT INTO verified_records (ic_no, year, verified_by) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iii", $icNoInt, $yearInt, $verifiedByInt);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($success) {
                header("Location: dashboard.php?panel=admin-verification&success=" . urlencode("Employee IC " . $icNoInt . " verified successfully for year " . $yearInt . "!"));
                exit();
            } else {
                header("Location: dashboard.php?panel=admin-verification&error=" . urlencode("Failed to verify employee record. It may already be verified."));
                exit();
            }
        } else {
            header("Location: dashboard.php?panel=admin-verification&error=" . urlencode("Database preparation failed."));
            exit();
        }
    } catch (Exception $e) {
        header("Location: dashboard.php?panel=admin-verification&error=" . urlencode("An error occurred: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>
