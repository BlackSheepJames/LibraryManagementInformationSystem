<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
// Only allow mdrrmOfficer to view audit logs
require_role('mdrrmOfficer');

// Single-page scroll view: optional search and action filter
$qParam = trim($_GET['q'] ?? '');
$filterAction = trim($_GET['action'] ?? '');

// limit rows fetched to a reasonable amount to keep page responsive
$perPage = 200; // fetch up to 200 rows; UI will show scrollable table

// Only show actions originating from community_* pages
$communityActions = [
  'download_infographic',
  'download_module',
  'download_video',
  'play_video'
];

// Open a dedicated connection to the library DB where community pages log audits
$auditDb = 'arthurnerritech_library';
$auditConn = new mysqli(
  $GLOBALS['servername'] ?? 'localhost',
  $GLOBALS['username'] ?? 'root',
  $GLOBALS['password'] ?? '',
  $auditDb
);

if ($auditConn->connect_error) {
  http_response_code(500);
  echo "Could not connect to audit DB: " . htmlspecialchars($auditConn->connect_error);
  exit;
}

// Build escaped IN list using the audit connection
$escaped = array_map(function($a) use ($auditConn) {
  return "'" . $auditConn->real_escape_string($a) . "'";
}, $communityActions);
$inList = implode(',', $escaped);

// Base query
$baseSql = "SELECT id, username, role, action, details, ip_address, user_agent, created_at 
            FROM audit_log 
            WHERE action IN ($inList)";

// By default: only today's logs
$todayOnly = true;

// If user uses search or action filter → allow all dates
if ($filterAction !== '' || $qParam !== '') {
  $todayOnly = false;
}

if ($todayOnly) {
  $baseSql .= " AND DATE(created_at) = CURDATE()";
}

// Apply optional search and action filter safely
if ($filterAction !== '') {
  $filterActionEsc = $auditConn->real_escape_string($filterAction);
  $baseSql .= " AND action = '" . $filterActionEsc . "'";
}

if ($qParam !== '') {
  $baseSql .= " AND (username LIKE ? OR details LIKE ? OR user_agent LIKE ? OR action LIKE ?)";
}

$baseSql .= " ORDER BY created_at DESC LIMIT " . intval($perPage);

$stmt = $auditConn->prepare($baseSql);

if ($stmt === false) {
  // fallback to older audit_logs table if audit_log is not present
  $sql2 = "SELECT id, username, role, action, '' AS details, '' AS ip_address, '' AS user_agent, timestamp AS created_at 
           FROM audit_logs 
           WHERE action IN ($inList)";

  if ($todayOnly) {
    $sql2 .= " AND DATE(timestamp) = CURDATE()";
  }

  if ($filterAction !== '') {
    $filterActionEsc = $auditConn->real_escape_string($filterAction);
    $sql2 .= " AND action = '" . $filterActionEsc . "'";
  }

  if ($qParam !== '') {
    $sql2 .= " AND (username LIKE ? OR action LIKE ?)";
  }

  $sql2 .= " ORDER BY timestamp DESC LIMIT " . intval($perPage);

  $stmt = $auditConn->prepare($sql2);
  if ($stmt === false) {
    http_response_code(500);
    echo "Database error: could not prepare audit query on audit DB.";
    exit;
  }

  if ($qParam !== '') {
    $like = '%' . $qParam . '%';
    $stmt->bind_param('ss', $like, $like);
  }

  $stmt->execute();
  $res = $stmt->get_result();
} else {
  if ($qParam !== '') {
    $like = '%' . $qParam . '%';
    $stmt->bind_param('ssss', $like, $like, $like, $like);
  }
  $stmt->execute();
  $res = $stmt->get_result();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Community Audit Log</title>
  <style>
      :root{--accent:#0b6cf5;--muted:#6b7280;--bg:#f7f9fc;--card:#ffffff;--maxw:1100px;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      *{box-sizing:border-box}
      body{margin:0;background:var(--bg);color:#0b1220;-webkit-font-smoothing:antialiased}
      .container{max-width:var(--maxw);margin:0 auto;padding:28px}
      header{background:linear-gradient(90deg, rgba(116, 3, 3, 1), transparent); position:sticky; top:0; z-index:50; backdrop-filter: blur(6px);}
      .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0}
      .logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit}
      .logo img {height: 35px; width: auto; border-radius: 4px;}
      nav ul{list-style:none;display:flex;gap:14px;margin:0;padding:0;align-items:center}
      nav a{color:rgba(56, 20, 20, 1); text-decoration:none; font-weight:600; font-size:0.95rem;}
      .dropdown {position:relative;}
      .dropdown-menu {display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid rgba(0,0,0,0.1);padding:10px;border-radius:6px;box-shadow:0 4px 10px rgba(0,0,0,0.1);width:180px;z-index:100;}
      .dropdown-menu p {font-size:0.85rem;margin:0 0 8px;color:#333;}
      .dropdown-menu a {display:inline-block;padding:5px 8px;font-size:0.85rem;text-decoration:none;color:#fff;border-radius:4px;margin-right:5px;}
      .confirm-btn {background:red;}
      .dropdown-menu a:not(.confirm-btn) {background:gray;}
      h1{margin:0 0 10px;font-size:1.8rem}
      form.audit-search{margin-bottom:18px;display:flex;gap:8px;align-items:center}
      form.audit-search input, form.audit-search select{padding:8px;border:1px solid #ccc;border-radius:6px;font-size:.95rem}
      form.audit-search button{padding:8px 12px;border-radius:6px;background:var(--accent);color:#fff;border:none;font-weight:600}
      .table-wrap{max-height:420px;overflow:auto;border:1px solid #eee;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.05)}
      table{width:100%;border-collapse:collapse;font-size:.9rem}
      th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
      th{background:#f1f5f9;font-weight:600;position:sticky;top:0;z-index:5}
      tbody tr:nth-child(even){background:#fafafa}
      footer{padding:28px 0;color:var(--muted);font-size:.9rem}
  </style>
</head>
<body>
  <!-- HEADER -->
  <header>
    <div class="container topbar">
      <a href="admin_dashboard.php" class="logo">
          <img src="img/logo.png" alt="Site Logo">
        <div class="text">
          <div style="font-weight:800; color: rgba(255, 170, 121, 1)">MDRRMO LEARNING LIBRARY</div>
          <div style="font-size:0.8rem;color:white">Learning Materials</div>
        </div>
      </a>
        <nav>
          <ul>
            <li><a href="brgy_audit_log.php">Barangay Audits</a></li>
            <li><a href="audit_log.php">Community Audits</a></li>
            <li><a href="admin_trainmods.php">Training Modules</a></li>
            <li><a href="admin_watch.php">Videos</a></li>
            <li><a href="admin_infograph.php">Infographics</a></li>
            <li><a href="admin_statistics.php">Statistics</a></li>
            <li class="dropdown">
              <a href="#" onclick="toggleLogoutMenu(event)">Logout ▼</a>
              <div class="dropdown-menu" id="logoutMenu">
                <p>Are you sure you want to logout?</p>
                <a href="logout.php" class="confirm-btn">Yes, Logout</a>
                <a href="#" onclick="toggleLogoutMenu(event)">Cancel</a>
              </div>
            </li>
          </ul>
        </nav>
    </div>
  </header>

  <!-- MAIN -->
  <main class="container">
    <h1>Community Audit Log</h1>
    <p class="muted">Showing latest actions. Use search or action filter to narrow results.</p>

    <form method="GET" class="audit-search">
      <input name="q" placeholder="Search details" 
             value="<?= htmlspecialchars($qParam) ?>">
      <select name="action">
        <option value="">All actions</option>
        <?php foreach ($communityActions as $act): ?>
          <option value="<?= htmlspecialchars($act) ?>" <?= $filterAction === $act ? 'selected' : ''?>><?= htmlspecialchars($act) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Search</button>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP</th><th>User Agent</th><th>When</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $res->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['username'] ?? 'guest') ?></td>
            <td><?= htmlspecialchars($row['role'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['action']) ?></td>
            <td><?= htmlspecialchars($row['details']) ?></td>
            <td><?= htmlspecialchars($row['ip_address']) ?></td>
            <td><?= htmlspecialchars($row['user_agent']) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </main>
<script>
function toggleLogoutMenu(e) {
  e.preventDefault();
  const menu = document.getElementById('logoutMenu');
  menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}
document.addEventListener('click', function(event) {
  const menu = document.getElementById('logoutMenu');
  const dropdown = document.querySelector('.dropdown');
  if (!dropdown.contains(event.target)) {
    menu.style.display = 'none';
  }
});
</script>
</body>
</html>
