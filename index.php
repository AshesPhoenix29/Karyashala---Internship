<?php
// Only start session if session cookie exists (meaning the user might be logged in)
if (isset($_COOKIE[session_name()])) {
    session_start();
    if (isset($_SESSION['user_ic'])) {
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karyashala - Login & Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <!-- Application Title -->
        <h1 class="app-title">Karyashala</h1>
        
        <!-- Display GET alerts (success/error messages) -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" id="session-alert">
                Registration Successful! Your unique IC No is <strong><?php echo htmlspecialchars($_GET['success']); ?></strong>. Please write this down to log in.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-success" id="session-alert">
                You have logged out successfully.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger" id="session-alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Form Navigation Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('login-tab')">Login</button>
            <button class="tab-btn" onclick="switchTab('signup-tab')">Sign Up</button>
        </div>

        <!-- Login Form -->
        <div id="login-tab" class="tab-content active">
            <form action="login_process.php" method="POST" id="login-form">
                <h2>User Login</h2>
                <div class="form-group">
                    <label for="login_ic">IC Number (Employee ID):</label>
                    <input type="text" name="ic_no" id="login_ic" required placeholder="e.g. 1001">
                    <span class="error-text" id="login-ic-error"></span>
                </div>
                
                <div class="form-group">
                    <label for="login_designation">Designation:</label>
                    <select name="designation" id="login_designation" required>
                        <option value="" disabled selected>Select your designation</option>
                        <option value="admin">Admin</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>
                
                <button type="submit" id="login-submit-btn" class="btn" disabled>Log In</button>
            </form>
        </div>

        <!-- Sign Up Form -->
        <div id="signup-tab" class="tab-content">
            <form action="register.php" method="POST" id="signup-form">
                <h2>Registration</h2>
                
                <div class="form-group">
                    <label for="signup_name">Full Name:</label>
                    <input type="text" name="name" id="signup_name" required placeholder="Enter full name">
                    <span class="error-text" id="signup-name-error"></span>
                </div>

                <div class="form-group">
                    <label for="signup_designation">Designation:</label>
                    <select name="designation" id="signup_designation" required>
                        <option value="" disabled selected>Select designation</option>
                        <option value="admin">Admin</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="signup_phone">Phone Number:</label>
                    <input type="text" name="phone" id="signup_phone" required placeholder="10-digit mobile number">
                    <span class="error-text" id="signup-phone-error"></span>
                </div>

                <div class="form-group">
                    <label for="signup_email">Email Address:</label>
                    <input type="email" name="email" id="signup_email" required placeholder="e.g. name@domain.com">
                    <span class="error-text" id="signup-email-error"></span>
                </div>

                <button type="submit" id="signup-submit-btn" class="btn" disabled>Sign Up</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
