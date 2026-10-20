<?php
// admin_view_module.php

require_once __DIR__ . '/auth.php';
require_role('mdrrmOfficer');

$target_dir = "uploads/modules/";

// Get module by ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;
if ($id <= 0) {
    die("Invalid module ID.");
}

// Connect to DB using existing config if available
// If you have a central config.php for DB credentials, include it instead.
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'arthurnerritech_library';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_errno) {
    die('Database connection failed: ' . $conn->connect_error);
}

$stmt = $conn->prepare('SELECT id, title, theme, date_added, file_name FROM modules WHERE id = ? LIMIT 1');
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die('Module not found.');
}
$module = $res->fetch_assoc();
$stmt->close();

$file_path = $target_dir . $module['file_name'];
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Build absolute file URL for Google Docs Viewer
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$absolute_file_url = $protocol . $_SERVER['HTTP_HOST'] . '/' . $file_path;

if (!file_exists($file_path)) {
    die("File not found.");
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Module - <?= htmlspecialchars($module['title']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f9fc; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; }
        iframe { width: 100%; height: 80vh; border: none; }
        .download-btn { display: inline-block; margin-top: 15px; padding: 10px 14px; background: #0b6cf5; color: #fff; text-decoration: none; border-radius: 6px; }
        .download-btn:hover { background: #084cbf; }
        .file-preview { margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h2><?= htmlspecialchars($module['title']) ?></h2>
    <p><strong>Theme:</strong> <?= htmlspecialchars($module['theme']) ?></p>
    <p><strong>Date Added:</strong> <?= htmlspecialchars($module['date']) ?></p>

    <div class="file-preview">
        <?php if (in_array($file_ext, ['pdf'])): ?>
            <iframe src="<?= $file_path ?>"></iframe>
        <?php elseif (in_array($file_ext, ['doc', 'docx', 'ppt', 'pptx'])): ?>
            <!-- Office files preview via Google Docs Viewer -->
            <iframe src="https://docs.google.com/viewer?url=<?= urlencode($absolute_file_url) ?>&embedded=true"></iframe>
        <?php elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
            <img src="<?= $file_path ?>" alt="Module File" style="max-width:100%;border-radius:6px;">
        <?php elseif ($file_ext === 'zip'): ?>
            <p>This is a ZIP file. Please download it to view contents.</p>
        <?php else: ?>
            <p>File preview is not available for this file type. Please download it.</p>
        <?php endif; ?>
    </div>

    <a class="download-btn" href="<?= $file_path ?>" download>⬇ Download File</a>
</div>
</body>
</html>
