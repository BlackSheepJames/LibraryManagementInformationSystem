<?php
require_once __DIR__ . '/audit_brgy.php';
// Similar behavior to download_video.php but logs into brgy_audit_log
require_once __DIR__ . '/session_bootstrap.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo 'Invalid id'; exit; }

$conn = new mysqli('localhost','root','', 'arthurnerritech_library');
if ($conn->connect_error) { http_response_code(500); echo 'DB error'; exit; }

$stmt = $conn->prepare('SELECT title, file_name, video_data FROM videos WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) { http_response_code(404); echo 'Video not found'; exit; }
$row = $res->fetch_assoc(); $stmt->close();

$title = $row['title']; $file_name = $row['file_name']; $video_blob = $row['video_data'];

// log
log_brgy_action('brgy_download_video', $title);

$path = __DIR__ . '/uploads/videos/' . $file_name;
if (!empty($file_name) && file_exists($path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
    header('Expires: 0'); header('Cache-Control: must-revalidate'); header('Pragma: public');
    header('Content-Length: ' . filesize($path)); readfile($path); exit;
}
if (!empty($video_blob)) { header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="download_' . $id . '.mp4"'); echo $video_blob; exit; }

http_response_code(404); echo 'File not available';
