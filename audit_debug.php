<?php
// audit_debug.php - lightweight diagnostics to inspect audit_log and audit_logs tables
require_once __DIR__ . '/config.php';

function safeEcho($v) {
    echo htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Audit Debug</title>
  <style>body{font-family:Segoe UI,Arial;margin:18px;background:#f7f9fc}pre{background:#fff;padding:12px;border-radius:6px;box-shadow:0 6px 18px rgba(0,0,0,0.04);overflow:auto}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;text-align:left}</style>
</head>
<body>
  <h1>Audit debug</h1>
  <p>This script inspects both <code>audit_log</code> and <code>audit_logs</code> tables and prints counts + latest rows.</p>
  <p>Access this on your XAMPP server, e.g. <code>http://localhost/library/audit_debug.php</code></p>
<?php
// Helper to check table existence
function table_exists($conn, $table) {
    $t = $conn->real_escape_string($table);
    $q = $conn->query("SHOW TABLES LIKE '$t'");
    return $q && $q->num_rows > 0;
}

// List to inspect
$tables = ['audit_log', 'audit_logs'];
foreach ($tables as $table) {
    echo "<h2>Table: <code>" . htmlspecialchars($table) . "</code></h2>\n";
    if (!table_exists($conn, $table)) {
        echo "<p style=\"color:#b00\">Table does not exist in database <code>" . htmlspecialchars($conn->real_escape_string($conn->query("SELECT DATABASE() as db")->fetch_object()->db ?? '') . "</code>.</p>\n";
        continue;
    }

    // Count rows
    $countRes = $conn->query("SELECT COUNT(*) AS cnt FROM `$table`");
    $cnt = $countRes ? $countRes->fetch_assoc()['cnt'] : 'Error';
    echo "<p>Row count: <strong>" . htmlspecialchars($cnt) . "</strong></p>\n";

    // Show latest 100 rows (try common column names)
    // Determine column list first
    $colsRes = $conn->query("SHOW COLUMNS FROM `$table`");
    $cols = [];
    if ($colsRes) {
        while ($c = $colsRes->fetch_assoc()) {
            $cols[] = $c['Field'];
        }
    }
    if (empty($cols)) {
        echo "<p>No columns found (error reading columns).</p>\n";
        continue;
    }

    // Choose ordering column
    $orderCol = null;
    foreach (['created_at','timestamp','id'] as $candidate) {
        if (in_array($candidate, $cols)) { $orderCol = $candidate; break; }
    }
    $orderColSql = $orderCol ? "ORDER BY `$orderCol` DESC" : "";

    $sql = "SELECT * FROM `$table` $orderColSql LIMIT 100";
    echo "<p><code>SQL:</code> <pre>" . htmlspecialchars($sql) . "</pre></p>\n";
    $res = $conn->query($sql);
    if (!$res) {
        echo "<p style=\"color:#b00\">Query error: " . htmlspecialchars($conn->error) . "</p>\n";
        continue;
    }

    // Render table
    echo "<table><thead><tr>";
    foreach ($cols as $col) echo "<th>" . htmlspecialchars($col) . "</th>";
    echo "</tr></thead><tbody>\n";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        foreach ($cols as $col) {
            echo "<td>" . nl2br(htmlspecialchars($row[$col] ?? '')) . "</td>";
        }
        echo "</tr>\n";
    }
    echo "</tbody></table>\n";
}

// Also show recent audit entries where action matches community patterns (if audit_log exists)
echo "<h2>Recent community actions (any table)</h2>\n";
$communityActions = [
    'download_infographic',
    'download_module',
    'play_video',
    'increment_video_view'
];
$escaped = array_map(function($a) use ($conn){ return "'" . $conn->real_escape_string($a) . "'"; }, $communityActions);
$in = implode(',', $escaped);

// Search both tables
foreach (['audit_log','audit_logs'] as $t) {
    if (!table_exists($conn, $t)) continue;
    // pick action column name
    $colsRes = $conn->query("SHOW COLUMNS FROM `$t`");
    $cols = [];
    while ($c = $colsRes->fetch_assoc()) $cols[] = $c['Field'];
    if (!in_array('action', $cols)) continue;
    $orderCol = in_array('created_at', $cols) ? 'created_at' : (in_array('timestamp',$cols) ? 'timestamp' : 'id');
    $sql = "SELECT * FROM `$t` WHERE action IN ($in) ORDER BY `$orderCol` DESC LIMIT 100";
    echo "<h3>From table: <code>$t</code></h3>\n";
    echo "<p><code>" . htmlspecialchars($sql) . "</code></p>\n";
    $res = $conn->query($sql);
    if (!$res) { echo "<p style=\"color:#b00\">Query error: " . htmlspecialchars($conn->error) . "</p>\n"; continue; }
    if ($res->num_rows === 0) { echo "<p>No matching community actions found in <code>$t</code>.</p>\n"; continue; }

    echo "<table><thead><tr>";
    $showCols = array_unique(array_merge(['id','username','role','action','details','created_at'], $cols));
    foreach ($showCols as $col) echo "<th>" . htmlspecialchars($col) . "</th>";
    echo "</tr></thead><tbody>\n";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        foreach ($showCols as $col) echo "<td>" . nl2br(htmlspecialchars($row[$col] ?? '')) . "</td>";
        echo "</tr>\n";
    }
    echo "</tbody></table>\n";
}

?>
</body>
</html>
