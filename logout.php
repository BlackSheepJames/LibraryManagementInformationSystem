<?php
include('config.php');
require_once __DIR__ . '/session_bootstrap.php';

if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Insert into audit logs for logout
    $log_sql = "INSERT INTO audit_logs (username, role, action) VALUES ('$username', '$role', 'User logged out')";
    $conn->query($log_sql);
}

// Destroy session and redirect to login
session_destroy();
header("Location: index.php");
exit();
?>
