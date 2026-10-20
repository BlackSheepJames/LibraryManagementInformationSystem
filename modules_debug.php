<?php
// modules_debug.php - Admin-only quick inspector for the `modules` table
require_once __DIR__ . '/auth.php';
require_role('mdrrmOfficer');

$servername = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'arthurnerritech_library';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

$sql = "SELECT id, title, theme, date_added, file_name, cover_name FROM modules ORDER BY date_added DESC";
$res = $conn->query($sql);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Modules Debug - DB Contents</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:18px;background:#f7f9fc}table{border-collapse:collapse;width:100%;background:#fff}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f4f4f4}</style>
</head>
<body>
  <h1>Modules table contents</h1>
  <?php if ($res === false): ?>
    <p style="color:red">Query error: <?= htmlspecialchars($conn->error) ?></p>
  <?php else: ?>
    <p>Total rows: <?= $res->num_rows ?></p>
    <?php if ($res->num_rows === 0): ?>
      <p>No modules found.</p>
    <?php else: ?>
      <table>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Theme</th>
          <th>Date Added</th>
          <th>File Name</th>
          <th>Cover Name</th>
        </tr>
        <?php while ($row = $res->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['id']) ?></td>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td><?= htmlspecialchars($row['theme']) ?></td>
          <td><?= htmlspecialchars($row['date_added']) ?></td>
          <td><?= htmlspecialchars($row['file_name']) ?></td>
          <td><?= htmlspecialchars($row['cover_name']) ?></td>
        </tr>
        <?php endwhile; ?>
      </table>
    <?php endif; ?>
  <?php endif; ?>
  <p style="margin-top:18px"><a href="admin_trainmods.php">Back to Admin Modules</a></p>
</body>
</html>
