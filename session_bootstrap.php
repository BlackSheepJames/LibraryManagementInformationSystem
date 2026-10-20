<?php
// Share one login session across the sibling *.arthurnerri.tech apps.
$hostName = strtolower($_SERVER['HTTP_HOST'] ?? '');
$cookieDomain = '';
if (str_ends_with($hostName, '.arthurnerri.tech') || $hostName === 'arthurnerri.tech') {
    $cookieDomain = '.arthurnerri.tech';
} elseif (str_ends_with($hostName, '.arthurnerri')) {
    $cookieDomain = '.arthurnerri';
}
if (str_ends_with($hostName, '.arthurnerri.tech') || $hostName === 'arthurnerri.tech') {
    $systemAdminDashboardUrl = 'https://sysadmin.arthurnerri.tech/admin_dashboard.php';
} elseif (str_starts_with($hostName, 'localhost')) {
    $systemAdminDashboardUrl = 'http://' . $hostName . '/systemAdmin/admin_dashboard.php';
} else {
    $systemAdminDashboardUrl = 'http://' . $hostName . '/systemAdmin/admin_dashboard.php';
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('ARTHURNERRI_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $cookieDomain,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
if (($_SESSION['role'] ?? '') === 'systemAdmin') {
    header('Location: ' . $systemAdminDashboardUrl);
    exit();
}
?>
