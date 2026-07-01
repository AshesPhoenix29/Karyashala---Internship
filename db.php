<?php
$host = '127.0.0.1';
$user = 'library_user';
$pass = 'library_pass123';
$dbname = 'karyashala';

// Establish connection using procedural mysqli
$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("
    <div style='font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 25px; border: 1px solid #cccccc; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.05);'>
        <h3 style='margin-top:0; color:#cc0000;'>Database Connection Failed</h3>
        <p>Could not connect to the database <strong>karyashala</strong>.</p>
        <p><strong>Error Details:</strong> <code>" . htmlspecialchars(mysqli_connect_error()) . "</code></p>
    </div>
    ");
}
?>
