<?php
require_once __DIR__ . '/audit_brgy.php';

$target_dir = __DIR__ . '/uploads/modules/';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$file = '';
if ($id > 0) {
	// lookup in DB
	$conn = new mysqli('localhost','root','', 'arthurnerritech_library');
	if ($conn->connect_error) { http_response_code(500); echo 'DB error'; exit; }
	$stmt = $conn->prepare('SELECT file_name, title FROM modules WHERE id = ? LIMIT 1');
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$res = $stmt->get_result();
	if ($res && $res->num_rows > 0) {
		$row = $res->fetch_assoc();
		$file = $row['file_name'];
		$title = $row['title'] ?? '';
	}
	$stmt->close();
	$conn->close();
} else {
	if (!isset($_GET['file'])) { http_response_code(400); echo 'Missing file'; exit; }
	$file = basename($_GET['file']);
}

$path = $target_dir . $file;
if (!file_exists($path)) { http_response_code(404); echo 'File not found'; exit; }

// log download
log_brgy_action('brgy_download_module', $file ?? $title ?? '');

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Expires: 0'); header('Cache-Control: must-revalidate'); header('Pragma: public');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
