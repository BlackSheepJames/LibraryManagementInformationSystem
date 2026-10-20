<?php
// Database connection
$servername = "localhost";
$username = "root";      // Default XAMPP MySQL username
$password = "";          // Default XAMPP MySQL password is empty
$dbname   = "arthurnerritech_library";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT title, image FROM infograph WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($title, $imageData);

    if ($stmt->fetch()) {
        // Include audit helper after establishing connection (avoid config.php overriding DB vars)
        require_once __DIR__ . '/audit.php';
        // Log the download (username/role from session if available) — only store title in details
        require_once __DIR__ . '/session_bootstrap.php';
        $user = $_SESSION['username'] ?? 'guest';
        $role = $_SESSION['role'] ?? null;
        write_audit($conn, $user, $role, 'download_infographic', $title);

        // Send headers for file download
        header("Content-Type: image/jpeg");
        header("Content-Disposition: attachment; filename=\"" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $title) . ".jpg\"");
        echo $imageData;
        exit;
    } else {
        echo "File not found!";
    }
    $stmt->close();
}
$conn->close();
?>
