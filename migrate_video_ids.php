<?php
// Simple admin-only migration helper: assigns non-zero unique ids to videos rows
require_once __DIR__ . '/auth.php';
require_role('mdrrmOfficer');

$servername = "localhost";
$username = "root";
$password = "";
$dbname   = "arthurnerritech_library";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die('DB connect failed');

// Check if migration needed (rows with id <= 0)
$res = $conn->query("SELECT COUNT(*) AS cnt FROM videos WHERE id <= 0 OR id IS NULL");
$row = $res->fetch_assoc();
if ((int)$row['cnt'] === 0) {
    echo "No migration needed. All rows have valid ids.";
    exit;
}

// Perform safe migration: create a new auto-increment column, then drop old id
echo "Starting migration...\n";

// Add temporary column
if ($conn->query("ALTER TABLE videos ADD COLUMN tmp_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST")) {
    echo "Added tmp_id\n";
    // Drop old id column if exists
    // Attempt to drop if present and not same as tmp_id
    $conn->query("ALTER TABLE videos DROP COLUMN id");
    // Rename tmp_id to id
    if ($conn->query("ALTER TABLE videos CHANGE COLUMN tmp_id id INT NOT NULL AUTO_INCREMENT")) {
        echo "Renamed tmp_id to id and set AUTO_INCREMENT PRIMARY KEY\n";
    } else {
        echo "Failed to rename tmp_id: " . $conn->error . "\n";
    }
} else {
    echo "Failed to add tmp_id: " . $conn->error . "\n";
}

echo "Migration finished. Verify table with SHOW CREATE TABLE videos;";
$conn->close();

?>
