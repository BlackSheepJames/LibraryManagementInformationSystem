<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
// Only allow mdrrmOfficer to view barangay audit logs
require_role('mdrrmOfficer');

// Single-page scroll view: optional search and action filter
$qParam = trim($_GET['q'] ?? '');
$filterAction = trim($_GET['action'] ?? '');

// Actions originating from brgy_*.php pages
$brgyActions = [
  'brgy_download_infographic',
  'brgy_download_module',
  'brgy_download_video',
  'brgy_play_video',
  'upload_video',
  'upload_infographic',
  'upload_module',
  'update_video',
  'update_infographic',
  'update_module',
  'brgy_watch',
  'brgy_update',
  'brgy_edit'
];

// Open a dedicated connection to the library DB where brgy pages log audits
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
}, $brgyActions);
$inList = implode(',', $escaped);

// limit rows fetched to a reasonable amount to keep page responsive
$perPage = 200; // fetch up to 200 rows; UI will show scrollable table

$baseSql = "SELECT id, username, role, action, details, ip_address, user_agent, created_at 
            FROM brgy_audit_log 
            WHERE action IN ($inList)";

// By default → show only today's logs
$todayOnly = true;

// If search or filter is applied → allow all dates
if ($filterAction !== '' || $qParam !== '') {
  $todayOnly = false;
}

if ($todayOnly) {
  $baseSql .= " AND DATE(created_at) = CURDATE()";
}

// apply optional search and action filter safely
if ($filterAction !== '') {
  $filterActionEsc = $auditConn->real_escape_string($filterAction);
  $baseSql .= " AND action = '" . $filterActionEsc . "'";
}

if ($qParam !== '') {
  // search across username, details, user_agent, action
  $baseSql .= " AND (username LIKE ? OR details LIKE ? OR user_agent LIKE ? OR action LIKE ?)";
}

$baseSql .= " ORDER BY created_at DESC LIMIT " . intval($perPage);

$stmt = $auditConn->prepare($baseSql);
if ($stmt === false) {
  http_response_code(500);
  echo "Database error: could not prepare brgy audit query (" . htmlspecialchars($auditConn->error) . ")";
  exit;
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
  <title>Barangay Audit Log</title>
  <style>
    :root{--accent:#0b6cf5;--muted:#6b7280;--bg:#f7f9fc;--card:#fff;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
    *{box-sizing:border-box}
    body{margin:0;background:var(--bg);color:#0b1220;-webkit-font-smoothing:antialiased}
    .container{max-width:1100px;margin:0 auto;padding:28px}
    header{background:linear-gradient(90deg, rgba(116, 3, 3, 1), transparent); position:sticky; top:0; z-index:50; backdrop-filter: blur(6px);}
    .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0}
    .logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit}
    .logo img {height: 35px; width: auto; border-radius: 4px;}
    nav ul{list-style:none;display:flex;gap:14px;margin:0;padding:0;align-items:center}
    nav a{color:rgba(56, 20, 20, 1); text-decoration:none; font-weight:600; font-size:0.95rem;}
    .dropdown{position:relative}
    .dropdown-menu{display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid rgba(0,0,0,0.1);padding:10px;border-radius:6px;box-shadow:0 4px 10px rgba(0,0,0,0.1);width:180px;z-index:100}
    .dropdown-menu p{font-size:0.85rem;margin:0 0 8px;color:#333}
    .dropdown-menu a{display:inline-block;padding:5px 8px;font-size:0.85rem;text-decoration:none;color:#fff;border-radius:4px;margin-right:5px;}
    .confirm-btn{background:red}
    .dropdown-menu a:not(.confirm-btn){background:gray}

    h1{margin:0 0 10px;font-size:1.8rem}
    p.lead{color:var(--muted);margin-bottom:20px}
    form.search-row{display:flex;gap:8px;margin-bottom:16px;align-items:center}
    form.search-row input, form.search-row select{padding:10px;border:1px solid #ccc;border-radius:6px;font-size:0.9rem}
    form.search-row button{padding:10px 16px;border-radius:6px;background:var(--accent);color:#fff;border:none;font-weight:600;cursor:pointer}
    .card{background:var(--card);padding:20px;border-radius:12px;box-shadow:0 6px 20px rgba(17,24,39,.06)}
    table{width:100%;border-collapse:collapse;font-size:0.9rem}
    th,td{padding:10px;text-align:left;border-bottom:1px solid #eee}
    th{background:#f9fafb;font-weight:700}
    tbody tr:hover{background:#f3f6ff}
    .table-container{max-height:420px;overflow:auto;border-radius:8px;border:1px solid #eee}
    footer{padding:28px 0;color:var(--muted);font-size:.9rem;margin-top:40px}
  </style>
</head>
<body>
<header>
  <div class="container topbar">
    <a href="admin_dashboard.php" class="logo">
      <img src="img/logo.png" alt="Site Logo">
      <div>
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

<main class="container">
  <h1>Barangay Audit Log</h1>
  <p class="lead">Showing latest actions from <code>brgy_*.php</code> pages. Use search or action filter to narrow results.</p>

  <div class="card">
    <form method="GET" class="search-row">
      <input name="q" placeholder="Search details" value="<?= htmlspecialchars($qParam) ?>">
      <select name="action">
        <option value="">All actions</option>
        <?php foreach ($brgyActions as $act): ?>
          <option value="<?= htmlspecialchars($act) ?>" <?= $filterAction === $act ? 'selected' : ''?>><?= htmlspecialchars($act) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Search</button>
    </form>

    <div class="table-container">
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
  </div>
</main>

<script>
function toggleLogoutMenu(e){
  e.preventDefault();
  const menu=document.getElementById('logoutMenu');
  menu.style.display=(menu.style.display==='block')?'none':'block';
}
document.addEventListener('click',function(event){
  const menu=document.getElementById('logoutMenu');
  const dropdown=document.querySelector('.dropdown');
  if(!dropdown.contains(event.target)){menu.style.display='none';}
});
</script>
</body>
</html>
