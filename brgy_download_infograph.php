<?php
// Streams an infographic image from the database as a downloadable file
$servername = "localhost";
$username = "arthurnerritech";
$password = "arthurtech_2025";
$dbname   = "arthurnerritech_library";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo 'Database connection error';
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo 'Missing id';
    exit;
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT title, image FROM infograph WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    http_response_code(404);
    echo 'Not found';
    exit;
}
$stmt->bind_result($title, $imageData);
$stmt->fetch();
$stmt->close();

// Detect MIME type (basic detection)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = $finfo ? finfo_buffer($finfo, $imageData) : 'application/octet-stream';
if ($finfo) finfo_close($finfo);

$safeTitle = preg_replace('/[^A-Za-z0-9 _-]/', '', $title);
$filename = ($safeTitle ? $safeTitle : 'infographic') . '-' . $id;

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . strlen($imageData));
echo $imageData;
exit;

?>
