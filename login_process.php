<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$icNo = isset($_POST['ic_no']) ? trim($_POST['ic_no']) : '';
$designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';

// Validation
if (empty($icNo) || empty($designation)) {
    header("Location: index.php?error=" . urlencode("Please provide both IC Number and Designation."));
    exit();
}

if (!ctype_digit($icNo)) {
    header("Location: index.php?error=" . urlencode("IC Number must contain numbers only."));
    exit();
}

if ($designation !== 'admin' && $designation !== 'employee') {
    header("Location: index.php?error=" . urlencode("Invalid designation selected."));
    exit();
}

try {
    // Determine target table
    $table = ($designation === 'admin') ? 'admin' : 'employee';

    // Query credentials from the selected designation table using procedural MySQLi
    $query = "SELECT * FROM `$table` WHERE ic_no = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        $icNoInt = (int)$icNo;
        mysqli_stmt_bind_param($stmt, "i", $icNoInt);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            // ONLY START SESSION HERE - when login is verified and required
            session_start();
            $_SESSION['user_ic'] = $user['ic_no'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_designation'] = $user['designation'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'];

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: index.php?error=" . urlencode("Invalid IC Number or Designation combination."));
            exit();
        }
    } else {
        header("Location: index.php?error=" . urlencode("Database query preparation failed."));
        exit();
    }
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode("An error occurred during login: " . $e->getMessage()));
    exit();
}
?>
