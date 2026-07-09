<?php
if (isset($_COOKIE[session_name()])) {
    session_start();
}

// Ensure user is logged in as Admin or Karyashala Admin
if (!isset($_SESSION['user_ic']) || ($_SESSION['user_designation'] !== 'admin' && $_SESSION['user_designation'] !== 'karyashala_admin')) {
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
$designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$remark = isset($_POST['remark']) ? trim($_POST['remark']) : null;

// Validation
if (empty($icNo) || empty($name) || empty($designation) || empty($phone) || empty($email)) {
    header("Location: dashboard.php?panel=karyashala-admins-update&error=" . urlencode("All fields are required."));
    exit();
}

if (strlen($designation) > 20) {
    header("Location: dashboard.php?panel=karyashala-admins-update&error=" . urlencode("Designation must be at most 20 characters long."));
    exit();
}

if (!ctype_digit($icNo)) {
    header("Location: dashboard.php?panel=karyashala-admins-update&error=" . urlencode("Invalid ID."));
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: dashboard.php?panel=karyashala-admins-update&error=" . urlencode("Please enter a valid email address."));
    exit();
}

if (!preg_match('/^\d{10}$/', $phone)) {
    header("Location: dashboard.php?panel=karyashala-admins-update&error=" . urlencode("Phone number must be exactly 10 digits."));
    exit();
}

try {
    // Check if the email is already in use by someone else in admin or karyashala_admin
    $email_check_query = "
        SELECT email FROM admin WHERE email = ?
        UNION ALL
        SELECT email FROM karyashala_admin WHERE email = ? AND ic_no != ?
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
            header("Location: dashboard.php?panel=karyashala-admins-update&error=" . urlencode("This email is already in use by another user."));
            exit();
        }
    }

    // Begin transaction for safety
    mysqli_begin_transaction($conn);

    // Execute update statement
    $update_query = "UPDATE karyashala_admin SET name = ?, designation = ?, phone = ?, email = ?, remark = ? WHERE ic_no = ?";
    $stmt_update = mysqli_prepare($conn, $update_query);
    
    if (!$stmt_update) {
        throw new Exception("Update statement preparation failed.");
    }
    
    $icNoInt = (int)$icNo;
    mysqli_stmt_bind_param($stmt_update, "sssssi", $name, $designation, $phone, $email, $remark, $icNoInt);
    $success = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);
    
    if (!$success) {
        throw new Exception("Failed to update employee record.");
    }

    // Update workshops if any
    $workshops = isset($_POST['workshops']) ? $_POST['workshops'] : [];
    if (!empty($workshops)) {
        foreach ($workshops as $wsId => $wsData) {
            $wsTitle = isset($wsData['title']) ? trim($wsData['title']) : '';
            $wsDate = isset($wsData['attended_date']) ? trim($wsData['attended_date']) : '';
            $wsIdInt = (int)$wsId;
            
            if (empty($wsTitle) || empty($wsDate)) {
                throw new Exception("Workshop fields cannot be left empty.");
            }
            
            // Validate date format YYYY-MM-DD
            $dateParts = explode('-', $wsDate);
            if (count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
                throw new Exception("Invalid workshop date format.");
            }
            
            $ws_query = "UPDATE workshops SET title = ?, attended_date = ? WHERE id = ? AND ic_no = ?";
            $stmt_ws = mysqli_prepare($conn, $ws_query);
            if (!$stmt_ws) {
                throw new Exception("Workshop update preparation failed.");
            }
            mysqli_stmt_bind_param($stmt_ws, "ssii", $wsTitle, $wsDate, $wsIdInt, $icNoInt);
            $ws_success = mysqli_stmt_execute($stmt_ws);
            mysqli_stmt_close($stmt_ws);
            
            if (!$ws_success) {
                throw new Exception("Failed to update workshop details.");
            }
        }
    }

    // Insert new workshops if any
    $newWorkshops = isset($_POST['new_workshops']) ? $_POST['new_workshops'] : [];
    if (!empty($newWorkshops)) {
        foreach ($newWorkshops as $wsData) {
            $wsTitle = isset($wsData['title']) ? trim($wsData['title']) : '';
            $wsDate = isset($wsData['attended_date']) ? trim($wsData['attended_date']) : '';
            
            if (empty($wsTitle) || empty($wsDate)) {
                throw new Exception("New workshop fields cannot be left empty.");
            }
            
            // Validate date format YYYY-MM-DD
            $dateParts = explode('-', $wsDate);
            if (count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
                throw new Exception("Invalid new workshop date format.");
            }
            
            $new_ws_query = "INSERT INTO workshops (ic_no, title, attended_date) VALUES (?, ?, ?)";
            $stmt_new_ws = mysqli_prepare($conn, $new_ws_query);
            if (!$stmt_new_ws) {
                throw new Exception("New workshop insert preparation failed.");
            }
            mysqli_stmt_bind_param($stmt_new_ws, "iss", $icNoInt, $wsTitle, $wsDate);
            $new_ws_success = mysqli_stmt_execute($stmt_new_ws);
            mysqli_stmt_close($stmt_new_ws);
            
            if (!$new_ws_success) {
                throw new Exception("Failed to save new workshop details.");
            }
        }
    }

    mysqli_commit($conn);
    header("Location: dashboard.php?panel=karyashala-admins-update&success=" . urlencode("Employee information and workshops updated successfully."));
    exit();

} catch (Exception $e) {
    mysqli_rollback($conn);
    header("Location: dashboard.php?panel=karyashala-admins-update&error=" . urlencode("An error occurred: " . $e->getMessage()));
    exit();
}
?>
