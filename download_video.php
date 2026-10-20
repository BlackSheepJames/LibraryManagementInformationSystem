<?php
// Streams a video file to the user and logs the download into arthurnerritech_library.audit_log
require_once __DIR__ . '/session_bootstrap.php';

// Accept id from GET, POST, or REQUEST. Also try parsing raw query string if needed.
$id = 0;
if (isset($_GET['id'])) $id = (int)$_GET['id'];
elseif (isset($_POST['id'])) $id = (int)$_POST['id'];
elseif (isset($_REQUEST['id'])) $id = (int)$_REQUEST['id'];
else {
    // Try parsing the query string manually as a last resort
    if (!empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $qs);
        if (isset($qs['id'])) $id = (int)$qs['id'];
    }
}

if ($id <= 0) {
    http_response_code(400);
    echo "Invalid id. Received parameters: ";
    echo htmlspecialchars(json_encode([ 'GET' => $_GET, 'POST' => $_POST, 'REQUEST' => $_REQUEST, 'QUERY_STRING' => $_SERVER['QUERY_STRING'] ]));
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname   = "arthurnerritech_library";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo "DB connection failed";
    exit;
}

// Fetch video metadata and file_name
$stmt = $conn->prepare("SELECT title, file_name, video_data FROM videos WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    http_response_code(404);
    echo "Video not found";
    $stmt->close();
    $conn->close();
    exit;
}
$row = $res->fetch_assoc();
$stmt->close();

$title = $row['title'];
$file_name = $row['file_name'];
$video_blob = $row['video_data'];

// Log the action to audit_log table in this DB (arthurnerritech_library)
$username_s = isset($_SESSION['username']) ? $_SESSION['username'] : 'anonymous';
$role_s = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';
$action = 'download_video';
$details = $title; // store title only per your preference
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

$logStmt = $conn->prepare("INSERT INTO audit_log (username, role, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
if ($logStmt) {
    $logStmt->bind_param('ssssss', $username_s, $role_s, $action, $details, $ip, $ua);
    $logStmt->execute();
    $logStmt->close();
}

$target_path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'videos' . DIRECTORY_SEPARATOR . $file_name;
if (!empty($file_name) && file_exists($target_path)) {
    // serve the file from disk
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($target_path));
    flush();
    readfile($target_path);
    $conn->close();
    exit;
}

// fallback: stream from DB blob if present
if (!empty($video_blob)) {
    // attempt to send blob
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="download_' . $id . '.mp4"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    // send blob content
    echo $video_blob;
    $conn->close();
    exit;
}

http_response_code(404);
echo "File not available";
$conn->close();
exit;

?>
