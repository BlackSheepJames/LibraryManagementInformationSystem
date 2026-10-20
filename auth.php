<?php
// auth.php - role-based access helper
if (session_status() !== PHP_SESSION_ACTIVE) {
    require_once __DIR__ . '/session_bootstrap.php';
}

function normalize_role($role) {
    if (!is_string($role)) {
        return $role;
    }

    $roleMap = [
        'mainAdmins' => 'mdrrmOfficer',
        'mainadmins' => 'mdrrmOfficer',
        'admins' => 'brgyOfficer',
        'users' => 'residents',
        'user' => 'residents',
        'admin' => 'brgyOfficer',
        'mainAdmin' => 'mdrrmOfficer',
    ];

    return $roleMap[$role] ?? $role;
}

function is_allowed_role($allowedRoles) {
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    $role = normalize_role($_SESSION['role'] ?? null);
    $allowedRoles = array_map('normalize_role', $allowedRoles);

    return in_array($role, $allowedRoles, true);
}

function require_role($allowedRoles) {
    if (!is_allowed_role($allowedRoles)) {
        // unauthorized: send 403 and stop
        http_response_code(403);
        echo "Forbidden. You don't have permission to access this page.";
        exit;
    }
}

?>
