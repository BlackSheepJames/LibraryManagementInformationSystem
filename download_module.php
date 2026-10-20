<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/session_bootstrap.php';
if (!isset($_GET['id'])) {
    die('No id');
}
$id = intval($_GET['id']);

// The modules table is stored in the `arthurnerritech_library` database in this project.
// Some pages (like community_trainmods.php) use that DB name directly, while config.php
// may point to a different DB. Force the correct DB here to avoid missing-table errors.
$db_for_modules = 'arthurnerritech_library';
$conn = new mysqli($servername, $username, $password, $db_for_modules);
if ($conn->connect_error) die('DB error: ' . $conn->connect_error);

$stmt = $conn->prepare("SELECT title, file_name FROM modules WHERE id = ?");
if ($stmt === false) {
    error_log('Prepare failed in download_module.php: ' . $conn->error);
    die('Server error: unable to prepare DB statement.');
}
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($title, $file_name);
if ($stmt->fetch()) {
    $path = __DIR__ . '/uploads/modules/' . $file_name;
    if (file_exists($path)) {
    $user = $_SESSION['username'] ?? 'guest';
    $role = $_SESSION['role'] ?? null;
    // store only title in details
    write_audit($conn, $user, $role, 'download_module', $title);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    } else {
        echo 'File not found';
    }
} else {
    echo 'Not found';
}
$stmt->close();
$conn->close();
?>
