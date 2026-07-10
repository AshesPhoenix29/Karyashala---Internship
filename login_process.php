<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$icNo = isset($_POST['ic_no']) ? trim($_POST['ic_no']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validation
if (empty($icNo) || empty($password)) {
    header("Location: index.php?error=" . urlencode("Please provide both IC Number and Password."));
    exit();
}

if (!ctype_digit($icNo)) {
    header("Location: index.php?error=" . urlencode("IC Number must contain numbers only."));
    exit();
}

try {
    $user = null;
    $designation = '';

    // Check Employee and role_table for login rights
    $query = "
        SELECT e.*, r.role as table_role 
        FROM `Employee` e 
        LEFT JOIN `role_table` r ON e.ic_number = r.ic_number 
        WHERE e.ic_number = ? 
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        $icNoInt = (int)$icNo;
        mysqli_stmt_bind_param($stmt, "i", $icNoInt);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $foundUser = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($foundUser && password_verify($password, $foundUser['password'])) {
            if ($foundUser['table_role'] === 'admin') {
                $user = $foundUser;
                $designation = 'admin';
            } else if ($foundUser['table_role'] === 'karyashala') {
                $user = $foundUser;
                $designation = 'karyashala_admin';
            }
        }
    }

    if ($user) {
        // ONLY START SESSION HERE - when login is verified and required
        session_start();
        $_SESSION['user_ic'] = $user['ic_number'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_designation'] = $designation;
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = $user['phone_number'];

        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } else {
        header("Location: index.php?error=" . urlencode("Invalid IC Number or Password."));
        exit();
    }
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode("An error occurred during login: " . $e->getMessage()));
    exit();
}
?>
