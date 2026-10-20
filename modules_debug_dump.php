<?php
// modules_debug_dump.php
// Quick debug endpoint: prints all rows from `modules` as JSON and an HTML table

header('Content-Type: text/html; charset=utf-8');

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'arthurnerritech_library';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo '<h2>DB connection failed:</h2><pre>' . htmlspecialchars($conn->connect_error) . '</pre>';
    exit;
}

$res = $conn->query("SELECT id, title, theme, date_added, file_name, cover_name FROM modules ORDER BY date_added DESC");
if ($res === false) {
    http_response_code(500);
    echo '<h2>Query failed:</h2><pre>' . htmlspecialchars($conn->error) . '</pre>';
    exit;
}

$rows = $res->fetch_all(MYSQLI_ASSOC);

echo '<h1>modules table dump</h1>';
echo '<p>Total rows: ' . count($rows) . '</p>';

echo '<h2>JSON</h2>';
echo '<pre>' . htmlspecialchars(json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';

echo '<h2>HTML Table</h2>';
if (count($rows) === 0) {
    echo '<p><em>No rows found in modules table.</em></p>';
} else {
    echo '<table border="1" cellpadding="6" cellspacing="0">';
    echo '<tr><th>id</th><th>title</th><th>theme</th><th>date_added</th><th>file_name</th><th>cover_name</th></tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($r['id']) . '</td>';
        echo '<td>' . htmlspecialchars($r['title']) . '</td>';
        echo '<td>' . htmlspecialchars($r['theme']) . '</td>';
        echo '<td>' . htmlspecialchars($r['date_added']) . '</td>';
        echo '<td>' . htmlspecialchars($r['file_name']) . '</td>';
        echo '<td>' . htmlspecialchars($r['cover_name']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

$conn->close();

echo '<p>Done.</p>';

?>
