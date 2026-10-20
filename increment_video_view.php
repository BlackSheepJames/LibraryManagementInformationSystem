<?php
// Simple endpoint to increment views and log via audit
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/session_bootstrap.php';
if (!isset($_GET['id'])) exit('');
$id = intval($_GET['id']);
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) exit('');

$stmt = $conn->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

$user = $_SESSION['username'] ?? 'guest';
$role = $_SESSION['role'] ?? null;
// fetch the title for logging (store only title)
$tstmt = $conn->prepare("SELECT title FROM videos WHERE id = ? LIMIT 1");
if ($tstmt) {
	$tstmt->bind_param('i', $id);
	$tstmt->execute();
	$tstmt->bind_result($vtitle);
	if ($tstmt->fetch()) {
		write_audit($conn, $user, $role, 'increment_video_view', $vtitle);
	} else {
		write_audit($conn, $user, $role, 'increment_video_view', null);
	}
	$tstmt->close();
} else {
	// fallback: log without title
	write_audit($conn, $user, $role, 'increment_video_view', null);
}

$conn->close();
echo 'OK';
?>
