<?php
// Helper to log barangay audit actions into arthurnerritech_library.brgy_audit_log
if (session_status() === PHP_SESSION_NONE) require_once __DIR__ . '/session_bootstrap.php';

function log_brgy_action($action, $details = '') {
    $servername = 'localhost';
    $username = 'root';
    $password = '';
    $dbname   = 'arthurnerritech_library';

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) return false;

    $username_s = isset($_SESSION['username']) ? $_SESSION['username'] : 'anonymous';
    $role_s = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare("INSERT INTO brgy_audit_log (username, role, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) { $conn->close(); return false; }
    $stmt->bind_param('ssssss', $username_s, $role_s, $action, $details, $ip, $ua);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

?>
