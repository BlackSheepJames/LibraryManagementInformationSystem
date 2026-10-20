<?php
// Endpoint to accept AJAX audit logs or simple GET/POST from server
// This file can be included safely. When included, it exposes functionality via
// the `write_audit` and `logAction` helpers from `audit.php`. The main request
// handling runs only when this file is requested directly (not included).

if (session_status() !== PHP_SESSION_ACTIVE) {
    @require_once __DIR__ . '/session_bootstrap.php';
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit.php';

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    // Only run request handling when executed directly
    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? null;

    // Normalize incoming data: prefer POST, then GET, then try to parse raw JSON or urlencoded body
    $action = $_POST['action'] ?? $_GET['action'] ?? null;
    $details = $_POST['details'] ?? $_GET['details'] ?? null;
    if (!$action) {
        // Try raw body (JSON or urlencoded)
        $raw = file_get_contents('php://input');
        if ($raw) {
            $ct = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($ct, 'application/json') !== false) {
                $parsed = json_decode($raw, true);
                if (is_array($parsed)) {
                    $action = $parsed['action'] ?? $action;
                    $details = $parsed['details'] ?? $details;
                    $username = $parsed['username'] ?? $username;
                    $role = $parsed['role'] ?? $role;
                }
            } else {
                // attempt to parse urlencoded raw body
                parse_str($raw, $parsed);
                if (!empty($parsed)) {
                    $action = $parsed['action'] ?? $action;
                    $details = $parsed['details'] ?? $details;
                    $username = $parsed['username'] ?? $username;
                    $role = $parsed['role'] ?? $role;
                }
            }
        }
    }

    header('Content-Type: application/json');
    if (!$action) {
        // helpful diagnostic for debugging
        $raw = file_get_contents('php://input');
        echo json_encode([
            'success' => false,
            'error' => 'Missing action',
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? null,
            'raw_length' => $raw ? strlen($raw) : 0
        ]);
        exit;
    }

    // Use distinct local names for DB credentials to avoid collision with $username (user)
    $db_conn = $GLOBALS['conn'] ?? null;
    if (!$db_conn) {
        $db_host = $servername ?? 'localhost';
        $db_user = ${'username'} ?? 'root';
        $db_pass = ${'password'} ?? '';
        $db_name = ${'dbname'} ?? 'arthurnerritech_library';

        $db_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($db_conn->connect_error) {
            echo json_encode(['success' => false, 'error' => 'DB connection failed']);
            exit;
        }
        $created_conn = true;
    } else {
        $created_conn = false;
    }

    $ok = write_audit($db_conn, $username, $role, $action, $details);
    if ($created_conn) $db_conn->close();

    echo json_encode(['success' => (bool)$ok]);
}
?>