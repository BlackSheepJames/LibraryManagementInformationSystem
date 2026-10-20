<?php
// Simple modules API: returns JSON list of modules or a single module by id
header('Content-Type: application/json; charset=utf-8');

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'arthurnerritech_library';
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// Optional id to fetch single module
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    if ($id > 0) {
        $stmt = $conn->prepare('SELECT id, title, theme, date_added, file_name, cover_name FROM modules WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Module not found']);
        } else {
            echo json_encode(['module' => $row]);
        }
        $stmt->close();
    } else {
        $result = $conn->query('SELECT id, title, theme, date_added, file_name, cover_name FROM modules ORDER BY date_added DESC');
        $all = [];
        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $all[] = $r;
            }
        }
        echo json_encode(['modules' => $all]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn->close();
}

?>