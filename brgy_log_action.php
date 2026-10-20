<?php
require_once __DIR__ . '/audit_brgy.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
$action = $_POST['action'] ?? '';
$details = $_POST['details'] ?? '';
if (empty($action)) { http_response_code(400); echo 'Missing action'; exit; }
$ok = log_brgy_action($action, $details);
echo $ok ? 'OK' : 'ERR';
