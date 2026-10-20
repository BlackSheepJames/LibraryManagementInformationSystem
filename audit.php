<?php
// audit.php - helper to write audit logs to DB
require_once __DIR__ . '/config.php';

// Helper: insert an audit entry into the provided mysqli connection
function insert_audit_to_conn($c, $username, $role, $action, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Try newer schema first
    $stmt = @$c->prepare("INSERT INTO audit_log (username, role, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('ssssss', $username, $role, $action, $details, $ip, $ua);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) return true;
    }

    // Fallback to older schema
    $stmt2 = @$c->prepare("INSERT INTO audit_logs (username, role, action) VALUES (?, ?, ?)");
    if ($stmt2) {
        $actionWithDetails = $action;
        if ($details) {
            $actionWithDetails .= ' | ' . substr($details, 0, 1000);
        }
        $stmt2->bind_param('sss', $username, $role, $actionWithDetails);
        $res2 = $stmt2->execute();
        $stmt2->close();
        return (bool)$res2;
    }

    return false;
}

function write_audit($conn, $username, $role, $action, $details = null) {
    // Write to the provided connection first
    $w1 = insert_audit_to_conn($conn, $username, $role, $action, $details);

    // Also ensure the entry exists in the library DB so audit_log.php can read it
    $libDb = 'arthurnerritech_library';
    $currentDb = null;
    $extraWritten = false;
    try {
        $res = $conn->query("SELECT DATABASE() AS db");
        if ($res) {
            $row = $res->fetch_assoc();
            $currentDb = $row['db'] ?? null;
        }
    } catch (Throwable $e) {
        // ignore
    }

    if ($currentDb !== $libDb) {
        // open a short-lived connection to the library DB
        $libConn = new mysqli($GLOBALS['servername'] ?? 'localhost', $GLOBALS['username'] ?? 'root', $GLOBALS['password'] ?? '', $libDb);
        if (!$libConn->connect_error) {
            $extraWritten = insert_audit_to_conn($libConn, $username, $role, $action, $details);
            $libConn->close();
        }
    } else {
        // already written to library DB via $conn
        $extraWritten = $w1;
    }

    return $w1 || $extraWritten;
}

// Convenience wrapper that creates its own connection when only called with strings
function write_audit_simple($username, $role, $action, $details = null) {
    $servername = $GLOBALS['servername'] ?? 'localhost';
    $dbuser = $GLOBALS['username'] ?? 'root';
    $dbpass = $GLOBALS['password'] ?? '';
    $dbname = $GLOBALS['dbname'] ?? 'arthurnerritech_library';
    $conn = new mysqli($servername, $dbuser, $dbpass, $dbname);
    if ($conn->connect_error) return false;
    $ok = write_audit($conn, $username, $role, $action, $details);
    $conn->close();
    return $ok;
}

// Server-side helper used by admin scripts to log actions using existing connection
function logAction($conn, $action, $details = null) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @require_once __DIR__ . '/session_bootstrap.php';
    }
    $user = $_SESSION['username'] ?? 'guest';
    $role = $_SESSION['role'] ?? null;
    return write_audit($conn, $user, $role, $action, $details);
}

?>
