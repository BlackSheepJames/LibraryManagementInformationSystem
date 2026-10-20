<?php
// Database connection
$servername = "localhost";
$username = "root";      // Default XAMPP MySQL username
$password = "";          // Default XAMPP MySQL password is empty
$dbname   = "arthurnerritech_library";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

require_once __DIR__ . '/auth.php';
require_role('mdrrmOfficer');

// 1. Total modules
$total_sql = "SELECT COUNT(*) AS total FROM modules";
$total_result = $conn->query($total_sql);
$total_modules = ($total_result->num_rows > 0) ? $total_result->fetch_assoc()['total'] : 0;

// 2. Distribution by theme
$themes_sql = "SELECT theme, COUNT(*) AS count FROM modules GROUP BY theme";
$themes_result = $conn->query($themes_sql);

$chartData = [];
$res = $conn->query("SELECT theme, COUNT(*) AS count FROM modules GROUP BY theme");
while ($row = $res->fetch_assoc()) {
    $chartData[] = $row;
}

// 3. Uploads per month
$monthly_sql = "SELECT DATE_FORMAT(date_added, '%Y-%m') AS month, COUNT(*) AS count 
                FROM modules 
                GROUP BY month 
                ORDER BY month";
$monthly_result = $conn->query($monthly_sql);

// 4. Recent uploads
$recent_sql = "SELECT title, theme, date_added 
               FROM modules 
               ORDER BY date_added DESC 
               LIMIT 5";
$recent_result = $conn->query($recent_sql);

// 5. Additional Stats
$newest_sql = "SELECT MAX(date_added) AS newest FROM modules";
$oldest_sql = "SELECT MIN(date_added) AS oldest FROM modules";
$popular_sql = "SELECT theme, COUNT(*) AS c FROM modules GROUP BY theme ORDER BY c DESC LIMIT 1";

$newest = $conn->query($newest_sql)->fetch_assoc()['newest'] ?? 'N/A';
$oldest = $conn->query($oldest_sql)->fetch_assoc()['oldest'] ?? 'N/A';
$popular = $conn->query($popular_sql)->fetch_assoc();
$popular_theme = $popular['theme'] ?? 'N/A';
$popular_count = $popular['c'] ?? 0;

// Total videos
  $total_videos = $conn->query("SELECT COUNT(*) AS total FROM videos")->fetch_assoc()['total'];

  // Videos per theme
  $themes_data = $conn->query("SELECT theme, COUNT(*) AS count FROM videos GROUP BY theme ORDER BY count DESC");

  // Videos per publisher
  $publishers_data = $conn->query("SELECT publisher, COUNT(*) AS count FROM videos GROUP BY publisher ORDER BY count DESC");

  // Most popular theme
  $popular_video = $conn->query("SELECT theme, COUNT(*) AS c FROM videos GROUP BY theme ORDER BY c DESC LIMIT 1")->fetch_assoc();
  $popular_video_theme = $popular_video['theme'] ?? 'N/A';
  $popular_video_count = $popular_video['c'] ?? 0;

  // Newest upload
  $newest_video = $conn->query("SELECT title, theme, publisher, date_added FROM videos ORDER BY date_added DESC LIMIT 1")->fetch_assoc();

  // Oldest upload
  $oldest_video = $conn->query("SELECT title, theme, publisher, date_added FROM videos ORDER BY date_added ASC LIMIT 1")->fetch_assoc();

  // Videos by theme
$videoThemesResult = $conn->query("SELECT theme, COUNT(*) AS count FROM videos GROUP BY theme ORDER BY count DESC");
$videoThemes = [];
while($row = $videoThemesResult->fetch_assoc()) {
    $videoThemes[] = $row;
}
$totalVideos = 0;
foreach($videoThemes as $row) {
    $totalVideos += $row['count'];
}
// Videos by publisher
$videoPublishersResult = $conn->query("SELECT publisher, COUNT(*) AS count FROM videos GROUP BY publisher ORDER BY count DESC");
$videoPublishers = [];
while($row = $videoPublishersResult->fetch_assoc()) {
    $videoPublishers[] = $row;
}

  // Handle CSV export for videos
if (isset($_POST['export_videos'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=video_analytics.csv');
    $output = fopen('php://output', 'w');

    // 🔹 Section 1: Summary
    fputcsv($output, ['Video Analytics Summary']);
    fputcsv($output, []); // empty line
    fputcsv($output, ['Total Videos', $total_videos]);
    fputcsv($output, ['Most Popular Theme', $popular_video_theme . " (" . $popular_video_count . " videos)"]);
    fputcsv($output, ['Newest Upload', $newest_video ? $newest_video['title'] . " (" . $newest_video['theme'] . ", " . $newest_video['publisher'] . ", " . $newest_video['date_added'] . ")" : "N/A"]);
    fputcsv($output, ['Oldest Upload', $oldest_video ? $oldest_video['title'] . " (" . $oldest_video['theme'] . ", " . $oldest_video['publisher'] . ", " . $oldest_video['date_added'] . ")" : "N/A"]);
    fputcsv($output, []); // empty line

    // 🔹 Section 2: Videos by Theme
    fputcsv($output, ['Videos by Theme']);
    fputcsv($output, ['Theme', 'Count']);
    $themes_csv = $conn->query("SELECT theme, COUNT(*) AS count FROM videos GROUP BY theme ORDER BY count DESC");
    while ($row = $themes_csv->fetch_assoc()) {
        fputcsv($output, [$row['theme'], $row['count']]);
    }
    fputcsv($output, []); // empty line

    // 🔹 Section 3: Videos by Publisher
    fputcsv($output, ['Videos by Publisher']);
    fputcsv($output, ['Publisher', 'Count']);
    $publishers_csv = $conn->query("SELECT publisher, COUNT(*) AS count FROM videos GROUP BY publisher ORDER BY count DESC");
    while ($row = $publishers_csv->fetch_assoc()) {
        fputcsv($output, [$row['publisher'], $row['count']]);
    }
    fputcsv($output, []); // empty line

    // 🔹 Section 4: Full Video List
    fputcsv($output, ['Full Video List']);
    fputcsv($output, ['Title', 'Theme', 'Publisher', 'Upload Date']);
    $videos = $conn->query("SELECT title, theme, publisher, date_added FROM videos ORDER BY date_added DESC");
    while ($row = $videos->fetch_assoc()) {
        fputcsv($output, [$row['title'], $row['theme'], $row['publisher'], $row['upload_date']]);
    }

    fclose($output);
    exit();
}
// ───── Infographics Analytics ─────

// Total infographics
$total_infographs = $conn->query("SELECT COUNT(*) AS total FROM infograph")->fetch_assoc()['total'] ?? 0;

// Infographics by theme
$infographThemesResult = $conn->query("SELECT theme, COUNT(*) AS count FROM infograph GROUP BY theme ORDER BY count DESC");
$infographThemes = [];
while ($row = $infographThemesResult->fetch_assoc()) {
    $infographThemes[] = $row;
}

// Newest and oldest infographic upload
$newest_infograph = $conn->query("SELECT title, theme, date_added FROM infograph ORDER BY date_added DESC LIMIT 1")->fetch_assoc();
$oldest_infograph = $conn->query("SELECT title, theme, date_added FROM infograph ORDER BY date_added ASC LIMIT 1")->fetch_assoc();

// Most popular theme
$popular_infograph = $conn->query("SELECT theme, COUNT(*) AS c FROM infograph GROUP BY theme ORDER BY c DESC LIMIT 1")->fetch_assoc();
$popular_infograph_theme = $popular_infograph['theme'] ?? 'N/A';
$popular_infograph_count = $popular_infograph['c'] ?? 0;

// Infographics per month
$infographMonthlyResult = $conn->query("SELECT DATE_FORMAT(date_added,'%Y-%m') AS month, COUNT(*) AS count FROM infograph GROUP BY month ORDER BY month");
$infographMonthly = [];
while($row = $infographMonthlyResult->fetch_assoc()) {
    $infographMonthly[] = $row;
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Statistics</title>
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
      .cta{background:rgba(255,59,59,1);color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none;font-weight:700;box-shadow:0 6px 18px rgba(11,108,245,.12)}
      .dropdown {position:relative;}
      .dropdown-menu {display:none;position:absolute;top:100%;right:0;background:#fff;border:1px solid rgba(0,0,0,0.1);padding:10px;border-radius:6px;box-shadow:0 4px 10px rgba(0,0,0,0.1);width:180px;z-index:100;}
      .dropdown-menu p {font-size:0.85rem;margin:0 0 8px;color:#333;}
      .dropdown-menu a {display:inline-block;padding:5px 8px;font-size:0.85rem;text-decoration:none;color:#fff;border-radius:4px;margin-right:5px;}
      .confirm-btn {background:red;}
      .dropdown-menu a:not(.confirm-btn) {background:gray;}
      .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-top:20px}
      .card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; 
              box-shadow: 0 2px 6px rgba(0,0,0,0.1); transition: transform 0.2s; }
      .card:hover { transform: translateY(-3px); }
      .card h2 { margin-bottom: 10px; color: #111827; }
      table{width:100%;border-collapse:collapse;margin-top:10px}
      th,td{padding:8px;border-bottom:1px solid #ddd;text-align:left}
      th{background:#f4f4f4}
      .chart-box {
        max-width: 500px;   /* shrink width */
        margin: 0 auto;     /* center the chart */
        padding: 10px;
      }

      canvas {
        max-height: 280px !important; /* smaller height */
      }
      footer{padding:28px 0;color:var(--muted);font-size:.9rem}
      .small{font-size:.85rem}
      .export-btn {background:#10b981;color:#fff;padding:8px 12px;border:none;border-radius:6px;cursor:pointer;}
  </style>
</head>
<body>
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
            <li><a href="brgy_audit_log.php">Brgy Audits</a></li>
            <li><a href="audit_log.php">Community Audits</a></li>
            <li><a href="admin_trainmods.php">Training Modules</a></li>
            <li><a href="admin_watch.php">Videos</a></li>
            <li><a href="admin_infograph.php">Infographics</a></li>
            <li><a href="admin_statistics.php">Statistics</a></li>
            <li class="dropdown">
              <a href="#" onclick="toggleLogoutMenu(event)">Logout ▼</a>
              <div class="dropdown-menu" id="logoutMenu">
                <p>Are you sure you want to logout?</p>
                <a href="index.php" class="confirm-btn">Yes, Logout</a>
                <a href="#" onclick="toggleLogoutMenu(event)">Cancel</a>
              </div>
            </li>
          </ul>
        </nav>
    </div>
  </header>
<main class="container">
    <h1>📊 Training Modules Analytics</h1>

    <div class="stats-grid">
        <div class="card">
            <h2>Total Modules</h2>
            <p style="font-size: 24px; font-weight: bold; color: #2563eb;">
                <?= $total_modules ?>
            </p>
        </div>
        <div class="card">
            <h2>Most Popular Theme</h2>
            <p><strong><?= htmlspecialchars($popular_theme) ?></strong> (<?= $popular_count ?> modules)</p>
        </div>
        <div class="card">
            <h2>Newest Upload</h2>
            <p><?= $newest ?></p>
        </div>
        <div class="card">
            <h2>Oldest Upload</h2>
            <p><?= $oldest ?></p>
        </div>
    </div>

<div style="display:flex; flex-wrap:wrap; gap:20px; margin-top:20px;">
    <!-- Modules by Theme -->
<!-- Modules by Theme -->
<div class="card" style="flex:1; min-width:320px;">
    <h2>Modules by Theme</h2>
    <canvas id="themeChart" style="width:100%; height:350px;"></canvas>

    <!-- Scrollable Table -->
    <div style="max-height:150px; overflow-y:auto; margin-top:15px; border:1px solid #ddd; border-radius:8px;">
        <table style="width:100%; border-collapse:collapse;">
            <thead style="position:sticky; top:0; background:#f8f9fa; z-index:1;">
                <tr>
                    <th style="padding:8px; border-bottom:1px solid #ccc;">Theme</th>
                    <th style="padding:8px; border-bottom:1px solid #ccc;">Count</th>
                </tr>
            </thead>
            <tbody>
                <?php $themes_result->data_seek(0); while ($row = $themes_result->fetch_assoc()): ?>
                    <tr>
                        <td style="padding:6px; border-bottom:1px solid #eee;"><?= htmlspecialchars($row['theme']) ?></td>
                        <td style="padding:6px; border-bottom:1px solid #eee; text-align:center;"><?= $row['count'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

    <!-- Monthly Uploads -->
    <div class="card" style="flex:1; min-width:320px; display:flex; flex-direction:column; align-items:center;">
        <h2 style="margin-bottom:20px;">Monthly Uploads</h2>
        <div style="flex:1; display:flex; align-items:center; justify-content:center; width:100%; padding:10px 0;">
            <canvas id="monthlyChart" style="width:90%; height:300px;"></canvas>
        </div>
    </div>
</div>
    <div class="card">
        <h2>Recent Uploads</h2>
        <table>
            <tr><th>Title</th><th>Theme</th><th>Date Added</th></tr>
            <?php if ($recent_result->num_rows > 0): ?>
                <?php while($row = $recent_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['theme']) ?></td>
                        <td><?= htmlspecialchars($row['date_added']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">No modules uploaded yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <button class="export-btn" onclick="exportTableToCSV('module_stats.csv')">⬇️ Export CSV</button>

<div class="section">
  <h3>📊 Learning Disaster Videos Analytics</h3>

  <?php
  // Total videos
  $total_videos = $conn->query("SELECT COUNT(*) AS total FROM videos")->fetch_assoc()['total'];

  // Videos per theme
  $themes_data = $conn->query("SELECT theme, COUNT(*) AS count FROM videos GROUP BY theme ORDER BY count DESC");

  // Videos per publisher
  $publishers_data = $conn->query("SELECT publisher, COUNT(*) AS count FROM videos GROUP BY publisher ORDER BY count DESC");

  // Most popular theme
  $popular_video = $conn->query("SELECT theme, COUNT(*) AS c FROM videos GROUP BY theme ORDER BY c DESC LIMIT 1")->fetch_assoc();
  $popular_video_theme = $popular_video['theme'] ?? 'N/A';
  $popular_video_count = $popular_video['c'] ?? 0;

  // Newest upload
  $newest_video = $conn->query("SELECT title, theme, publisher, date_added FROM videos ORDER BY date_added DESC LIMIT 1")->fetch_assoc();

  // Oldest upload
  $oldest_video = $conn->query("SELECT title, theme, publisher, date_added FROM videos ORDER BY date_added ASC LIMIT 1")->fetch_assoc();
  ?>

  <div class="stats-grid">
      <div class="card">
          <h2>Total Videos</h2>
          <p style="font-size: 22px; font-weight: bold; color: #2563eb;">
              <?= $total_videos ?>
          </p>
      </div>
      <div class="card">
          <h2>Most Popular Theme</h2>
          <p><strong><?= htmlspecialchars($popular_video_theme) ?></strong> (<?= $popular_video_count ?> videos)</p>
      </div>
      <div class="card">
          <h2>Newest Upload</h2>
          <p>
              <?= $newest_video ? htmlspecialchars($newest_video['title'])." (".$newest_video['theme'].", ".$newest_video['publisher'].", ".date("M d, Y", strtotime($newest_video['date_added'])).")" : "N/A" ?>
          </p>
      </div>
      <div class="card">
          <h2>Oldest Upload</h2>
          <p>
              <?= $oldest_video ? htmlspecialchars($oldest_video['title'])." (".$oldest_video['theme'].", ".$oldest_video['publisher'].", ".date("M d, Y", strtotime($oldest_video['date_added'])).")" : "N/A" ?>
          </p>
      </div>
  </div>
<div style="display:flex; flex-wrap:wrap; gap:20px; margin-top:20px;">
    <!-- Videos by Theme -->
    <div class="card" style="flex:1; min-width:350px;">
        <h2>Videos by Theme</h2>
        <canvas id="videoThemeChart" style="width:100%; height:350px;"></canvas>
        <div style="max-height:150px; overflow-y:auto; margin-top:15px;">
            <table style="width:100%;">
                <tr><th>Theme</th><th>Count</th></tr>
                <?php foreach($videoThemes as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['theme']) ?></td>
                        <td><?= $row['count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Videos by Publisher -->
    <div class="card" style="flex:1; min-width:350px;">
        <h2>Videos by Publisher</h2>
        <canvas id="videoPublisherChart" style="width:100%; height:350px;"></canvas>
        <div style="max-height:150px; overflow-y:auto; margin-top:15px;">
            <table style="width:100%;">
                <tr><th>Publisher</th><th>Count</th></tr>
                <?php foreach($videoPublishers as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['publisher']) ?></td>
                        <td><?= $row['count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

  <?php
  // Handle CSV export for videos
  if (isset($_POST['export_videos'])) {
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename=video_analytics.csv');
      $output = fopen('php://output', 'w');

      // Add CSV header
      fputcsv($output, ['Title', 'Theme', 'Publisher', 'Upload Date']);

      // Fetch all videos
      $videos = $conn->query("SELECT title, theme, publisher, date_added FROM videos ORDER BY date_added DESC");
      while ($row = $videos->fetch_assoc()) {
          fputcsv($output, $row);
      }
      fclose($output);
      exit();
  }
  ?>
      <!-- CSV Export Button -->
  <form method="post" action="">
      <button type="submit" name="export_videos" style="margin:15px 0; padding:10px 20px; background:#2563eb; color:#fff; border:none; border-radius:6px; cursor:pointer;">
          ⬇️ Download Video Analytics (CSV)
      </button>
  </form>
</div>
<div class="section">
  <h3>📊 Infographics Analytics</h3>

  <div class="stats-grid">
      <div class="card">
          <h2>Total Infographics</h2>
          <p style="font-size: 22px; font-weight: bold; color: #2563eb;"><?= $total_infographs ?></p>
      </div>
      <div class="card">
          <h2>Most Popular Theme</h2>
          <p><strong><?= htmlspecialchars($popular_infograph_theme) ?></strong> (<?= $popular_infograph_count ?> infographics)</p>
      </div>
      <div class="card">
          <h2>Newest Upload</h2>
          <p><?= $newest_infograph ? htmlspecialchars($newest_infograph['title'])." (".$newest_infograph['theme'].", ".date("M d, Y", strtotime($newest_infograph['date_added'])).")" : "N/A" ?></p>
      </div>
      <div class="card">
          <h2>Oldest Upload</h2>
          <p><?= $oldest_infograph ? htmlspecialchars($oldest_infograph['title'])." (".$oldest_infograph['theme'].", ".date("M d, Y", strtotime($oldest_infograph['date_added'])).")" : "N/A" ?></p>
      </div>
  </div>

  <!-- ✅ Side-by-side layout -->
  <div style="display:flex; flex-wrap:wrap; gap:20px; margin-top:20px;">
<!-- Infographics by Theme -->
<div class="card" style="flex:1; min-width:350px;">
    <h2>Infographics by Theme</h2>
    <canvas id="infographThemeChart" style="width:100%; height:350px;"></canvas>
    
    <!-- ✅ Scrollable table -->
    <div style="max-height:150px; overflow-y:auto; margin-top:15px; border:1px solid #ddd; border-radius:8px;">
        <table style="width:100%; border-collapse:collapse;">
            <tr style="background:#f3f4f6;">
                <th style="text-align:left; padding:6px;">Theme</th>
                <th style="text-align:left; padding:6px;">Count</th>
            </tr>
            <?php foreach($infographThemes as $row): ?>
                <tr>
                    <td style="padding:6px;"><?= htmlspecialchars($row['theme']) ?></td>
                    <td style="padding:6px;"><?= $row['count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

      <!-- Monthly Uploads -->
      <div class="card" style="flex:1; min-width:350px;">
          <h2>Monthly Uploads</h2>
          <canvas id="infographMonthlyChart" style="width:100%; height:350px;"></canvas>
      </div>
  </div>
</div>
</main>

  <main class="container">
    <!-- FOOTER -->
    <footer style="margin-top:34px;">
      <div class="container" style="padding:0;">
        <div style="display:flex; gap:18px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap;">
          <div style="max-width:420px;">
            <div class="logo" style="margin-bottom:8px;">
              <div class="mark" style="background:linear-gradient(135deg,#0b6cf5,#7dd3fc);">L</div>
              <div style="margin-left:10px;">
                <div style="font-weight:800;">MDRRMOLearningLibrary</div>
                <div class="muted small">Learning Materials</div>
              </div>
            </div>
            <p class="muted small">Unlimited access to disaster preparedness and awareness books, infographics, and videoos.</p>
          </div>

          <div style="flex:1; min-width:200px;">
            <div style="font-weight:700; margin-bottom:6px;">Department</div>
            <div class="muted small">
              <div><a href="#" style="color:inherit; text-decoration:none;">Our Story</a></div>
              <div><a href="#" style="color:inherit; text-decoration:none;">How It Works</a></div>
              <div><a href="#" style="color:inherit; text-decoration:none;">Contact Us</a></div>
            </div>
          </div>

          <div style="min-width:200px;">
            <div style="font-weight:700; margin-bottom:6px;">Follow us</div>
            <div class="muted small">Social links placeholder</div>
          </div>
        </div>

        <div style="margin-top:22px; border-top:1px solid rgba(15,23,42,0.03); padding-top:14px; color:var(--muted); display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
          <div>© <?= date('Y') ?> MDRRMO Learning Library. All rights reserved.</div>
          <div>
            <a href="#" class="muted small" style="text-decoration:none; margin-right:12px;">Terms</a>
            <a href="#" class="muted small" style="text-decoration:none; margin-right:12px;">Privacy</a>
            <a href="#" class="muted small" style="text-decoration:none;">Accessibility</a>
          </div>
        </div>
      </div>
    </footer>

  </main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
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

// Pass PHP data to JS
const moduleThemes = <?= json_encode($chartData) ?>;
const monthlyUploads = <?= json_encode(
    iterator_to_array($monthly_result)
) ?>;

// Register datalabels plugin globally
Chart.register(ChartDataLabels);

// Modules by Theme - Pie Chart with Percentages
(() => {
    const ctx = document.getElementById('themeChart').getContext('2d');
    const labels = moduleThemes.map(item => item.theme);
    const rawData = moduleThemes.map(item => Number(item.count) || 0);
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: rawData,
                backgroundColor: [
                    '#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6',
                    '#06b6d4','#84cc16','#e11d48','#a855f7','#f97316'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const dataset = context.chart.data.datasets[0].data.map(Number);
                            const sum = dataset.reduce((a, b) => a + b, 0) || 1;
                            const value = Number(context.parsed);
                            const percentage = ((value / sum) * 100).toFixed(1) + "%";
                            return context.label + ": " + value + " (" + percentage + ")";
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const data = ctx.chart.data.datasets[0].data.map(Number);
                        const sum = data.reduce((a, b) => a + b, 0) || 1;
                        return (Number(value) * 100 / sum).toFixed(1) + "%";
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
})();

// Monthly Uploads - Line Chart
(() => {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    const labels = monthlyUploads.map(item => item.month);
    const dataPoints = monthlyUploads.map(item => Number(item.count) || 0);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Uploads',
                data: dataPoints,
                borderColor: '#2563eb',
                backgroundColor: '#93c5fd55',
                borderWidth: 2,
                pointRadius: 3,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
})();

// CSV Export
function exportTableToCSV(filename) {
    let csv = [];
    let rows = document.querySelectorAll("table tr");
    for (let row of rows) {
        let cols = row.querySelectorAll("td, th");
        let data = [];
        cols.forEach(col => data.push(col.innerText));
        csv.push(data.join(","));
    }
    let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    let downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    document.body.appendChild(downloadLink);
    downloadLink.click();
};

const videoThemes = <?= json_encode($videoThemes) ?>;
const videoPublishers = <?= json_encode($videoPublishers) ?>;

const videoThemeCtx = document.getElementById('videoThemeChart').getContext('2d');
new Chart(videoThemeCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_column($videoThemes, 'theme')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($videoThemes, 'count')); ?>,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#66FF66', '#FF6699'
            ]
        }]
    },
    options: {
        plugins: {
            legend: {
                position: 'bottom'
            },
            datalabels: {
                color: '#fff',
                formatter: (value, ctx) => {
                    // ensure numeric arithmetic
                    const dataArr = ctx.chart.data.datasets[0].data.map(Number);
                    const sum = dataArr.reduce((a, b) => a + b, 0) || 1;
                    return (Number(value) * 100 / sum).toFixed(1) + "%";
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

const publisherColors = [
    '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', 
    '#06b6d4', '#84cc16', '#e11d48', '#a855f7', '#f97316',
    '#f43f5e', '#6366f1', '#14b8a6', '#facc15', '#ec4899'
];

new Chart(document.getElementById('videoPublisherChart'), {
    type: 'bar',
    data: {
        labels: videoPublishers.map(item => item.publisher),
        datasets: [{
            label: 'Videos',
            data: videoPublishers.map(item => item.count),
            backgroundColor: videoPublishers.map((_, i) => publisherColors[i % publisherColors.length]),
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: true }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
const infographThemes = <?= json_encode($infographThemes) ?>;
const infographMonthly = <?= json_encode($infographMonthly) ?>;

const ctxInfographTheme = document.getElementById('infographThemeChart').getContext('2d');
new Chart(ctxInfographTheme, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($infographThemes, 'theme')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($infographThemes, 'count')) ?>,
            backgroundColor: [
                '#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6',
                '#06b6d4','#84cc16','#f97316','#ec4899','#6366f1'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            datalabels: {
                formatter: (value, context) => {
                    const dataArr = context.chart.data.datasets[0].data.map(Number);
                    const sum = dataArr.reduce((a, b) => a + b, 0) || 1;
                    return (Number(value) * 100 / sum).toFixed(1) + "%";
                },
                color: '#fff',
                font: {
                    weight: 'bold',
                    size: 14
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // show both count & percentage in tooltip (coerce numbers)
                        const dataset = context.dataset.data.map(Number);
                        const sum = dataset.reduce((a, b) => a + b, 0) || 1;
                        const value = Number(context.parsed);
                        const percentage = ((value / sum) * 100).toFixed(1) + "%";
                        return context.label + ": " + value + " (" + percentage + ")";
                    }
                }
            }
        }
    },
    plugins: [ChartDataLabels]
});

// Monthly uploads - Line Chart
new Chart(document.getElementById('infographMonthlyChart'), {
    type: 'line',
    data: {
        labels: infographMonthly.map(item => item.month),
        datasets: [{
            label: 'Uploads',
            data: infographMonthly.map(item => item.count),
            borderColor: '#10b981',
            backgroundColor: '#6ee7b755',
            borderWidth: 2,
            pointRadius: 3,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

</body>
</html>
