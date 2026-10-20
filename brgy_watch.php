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

// Barangay audit helper
require_once 'audit_brgy.php';

$target_dir = "uploads/videos/";
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

// ===== Upload Video =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video_file'])) {
    $title      = trim($_POST['video_title']);
    $theme      = trim($_POST['video_theme']);
    $date_added = $_POST['date_added'];
    $publisher  = trim($_POST['publisher']);

    $file_name  = basename($_FILES["video_file"]["name"]);
    $target_file = $target_dir . $file_name;
    $file_type   = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'];

    if (empty($title) || empty($theme) || empty($date_added) || empty($publisher)) {
        $upload_msg = "Please fill in all fields.";
    } elseif (!in_array($file_type, $allowed_types)) {
        $upload_msg = "Only video files (mp4, webm, ogg, mov, avi, mkv) are allowed.";
  } elseif (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO videos (title, theme, date_added, publisher, file_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $theme, $date_added, $publisher, $file_name);
        $stmt->execute();
        $stmt->close();
    $upload_msg = "✅ Video uploaded successfully!";
    // Log to barangay audit
    if (function_exists('log_brgy_action')) {
      log_brgy_action('upload_video', $title . ' | file: ' . $file_name);
    }
    } else {
        $upload_msg = "❌ Sorry, there was an error uploading your video.";
    }
}


// ===== Search & Fetch Videos =====
$search_query = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$sql = "SELECT * FROM videos";
if ($search_query) {
    $esc = $conn->real_escape_string($search_query);
    $sql .= " WHERE LOWER(title) LIKE '%$esc%' OR LOWER(theme) LIKE '%$esc%'";
}
$sql .= " ORDER BY date_added DESC";
$videos = $conn->query($sql);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Brgy Manage Videos</title>
  <style>
      :root{--accent:#0b6cf5;--muted:#6b7280;--bg:#f7f9fc;--card:#ffffff;--maxw:1100px;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      *{box-sizing:border-box}
      body{margin:0;background:var(--bg);color:#0b1220;-webkit-font-smoothing:antialiased}
      .container{max-width:var(--maxw);margin:0 auto;padding:28px}
      .container1{max-width:1100px; margin:40px auto 0; background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
      .section{margin-bottom:30px;padding:20px;border:1px solid #ddd;border-radius:8px;background:#fafafa}
      .section h2,h3{margin-top:0;color:#333}
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
      .upload-form-enhanced {background:#fff;padding:25px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.06);transition:.3s ease}
      .upload-form-enhanced:hover {transform:translateY(-2px)}
      .upload-form-enhanced .form-group {margin-bottom:15px}
      .upload-form-enhanced label {font-weight:600;display:block;margin-bottom:5px}
      .upload-form-enhanced input[type="text"],.upload-form-enhanced input[type="date"],.upload-form-enhanced input[type="file"] {width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;outline:none;transition:border-color .3s ease}
      .upload-form-enhanced input:focus {border-color:var(--accent);box-shadow:0 0 0 3px rgba(11,108,245,0.1)}
      .file-upload {border:2px dashed #ccc;padding:15px;border-radius:6px;background:#fafafa;text-align:center;transition:border-color .3s ease}
      .file-upload:hover {border-color:var(--accent)}
      .btn-submit {display:inline-block;background:var(--accent);color:#fff;padding:12px;border:none;border-radius:6px;font-size:1rem;font-weight:600;cursor:pointer;transition:background .3s ease}
      .btn-submit:hover {background:#084cbf}
      .search-box{display:flex;gap:10px;margin-top:10px}
      .search-box input{flex:1;padding:8px;border:1px solid #ccc;border-radius:4px}
      .search-box button{padding:8px 12px;background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer}
      .search-box button:hover{background:#0056b3}
      .table-container{overflow-x:auto}
      table{width:100%;border-collapse:collapse;margin-top:20px;background:#fff}
  th,td{border:1px solid #ddd;padding:10px;text-align:left}
  th{background:#f4f4f4}
  /* Use fixed table layout to keep columns stable and prevent long content from expanding the table */
  table{table-layout:fixed}
  /* Constrain specific columns */
  /* Keep header widths stable, but center content in data cells */
  th.col-preview{width:160px}
  td.col-preview{width:160px;display:flex;align-items:center;justify-content:center}
  .col-title{width:44%;max-width:46ch;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
  .col-theme{width:12%}
  .col-date{width:12%}
  .col-publisher{width:12%}
  .col-actions{width:12%;white-space:nowrap}
  /* Center actions horizontally and vertically */
  th.col-actions, td.col-actions{width:12%;white-space:nowrap;text-align:center;vertical-align:middle}
  video {max-width: 140px;height: auto;border-radius: 6px;display: block;}
  .action-btn{display:inline-block;box-sizing:border-box;padding:6px 8px;margin:2px;text-decoration:none;color:#fff;border-radius:4px;font-size:0.85rem;max-width:100%;overflow:hidden;text-overflow:ellipsis}
  .action-btn.small{padding:6px 8px;font-size:0.82rem}
  .action-btn.wrap{display:inline-flex;align-items:center;gap:6px}
  /* Stacked action buttons container */
  .actions-stack{display:flex;flex-direction:column;align-items:center;gap:6px}
  .edit-btn{background:orange}
  </style>
</head>
<body>
  <header>
    <div class="container topbar">
      <a href="brgy_dashboard.php" class="logo">
          <img src="img/logo.png" alt="Site Logo">
        <div class="text">
          <div style="font-weight:800; color: rgba(255, 170, 121, 1)">MDRRMO LEARNING LIBRARY</div>
          <div style="font-size:0.8rem;color:white">Learning Materials</div>
        </div>
      </a>
        <nav>
          <ul>
            <li><a href="brgy_trainmods.php">Training Modules</a></li>
            <li><a href="brgy_watch.php">Videos</a></li>
            <li><a href="brgy_infograph.php">Infographics</a></li>
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

<div class="container1">
<div class="section">
<h2>Upload Video</h2>
<form method="POST" enctype="multipart/form-data" class="upload-form-enhanced">
    <div class="form-group">
        <label>📌 Title</label>
        <input type="text" name="video_title" placeholder="Enter video title" required>
    </div>
    <div class="form-group">
        <label>🎯 Theme</label>
        <input type="text" name="video_theme" placeholder="Enter theme" required>
    </div>
    <div class="form-group">
        <label>📅 Date Added</label>
        <input type="date" name="date_added" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="form-group">
        <label>🏢 Publisher</label>
        <input type="text" name="publisher" placeholder="Enter publisher" required>
    </div>
    <div class="form-group file-upload">
        <label>📹 Select Video File</label>
        <input type="file" name="video_file" id="videoFile" accept="video/*" required>
        <small>Allowed: MP4, WebM, OGG, MOV, AVI, MKV</small>
    </div>
    <button type="submit" class="btn-submit">🚀 Upload Video</button>
</form>
<?php if (!empty($upload_msg)) echo "<p class='upload-message'>$upload_msg</p>"; ?>
</div>

<div class="section">
<h3>Uploaded Videos</h3>
<form method="GET" class="search-box">
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search_query) ?>">
    <button type="submit">Search</button>
</form>
<div class="table-container">
<?php if ($videos && $videos->num_rows > 0): ?>
<table>
      <tr>
        <th class="col-preview">Preview</th>
        <th class="col-title">Title</th>
        <th class="col-theme">Theme</th>
        <th class="col-date">Date Added</th>
        <th class="col-publisher">Publisher</th>
        <th class="col-actions">Actions</th>
      </tr>
    <?php while ($v = $videos->fetch_assoc()): ?>
      <tr>
          <td class="col-preview">
            <?php if (!empty($v['file_name'])): ?>
        <video controls data-id="<?= (int)$v['id'] ?>" data-title="<?= htmlspecialchars($v['title'], ENT_QUOTES) ?>" preload="metadata">
          <source src="uploads/videos/<?= htmlspecialchars($v['file_name']) ?>" type="video/<?= pathinfo($v['file_name'], PATHINFO_EXTENSION) ?>">
          Your browser does not support the video tag.
        </video>
            <?php endif; ?>
          </td>
          <td class="col-title" title="<?= htmlspecialchars($v['title']) ?>"><?= htmlspecialchars($v['title']) ?></td>
          <td class="col-theme" title="<?= htmlspecialchars($v['theme']) ?>"><?= htmlspecialchars($v['theme']) ?></td>
          <td class="col-date"><?= htmlspecialchars($v['date_added']) ?></td>
          <td class="col-publisher" title="<?= htmlspecialchars($v['publisher']) ?>"><?= htmlspecialchars($v['publisher']) ?></td>
      <td class="col-actions">
        <div class="actions-stack">
          <a href="brgy_download_video.php?id=<?= (int)$v['id'] ?>" class="action-btn small wrap" style="background:transparent;color:var(--accent);text-decoration:none;padding:6px 8px;">⬇️<span class="sr-only">Download</span></a>
          <a href="brgy_update_video.php?id=<?= $v['id'] ?>" class="action-btn small edit-btn wrap">✏️<span class="sr-only">Edit</span></a>
        </div>
      </td>
      </tr>
    <?php endwhile; ?>
</table>
<?php else: ?>
<p>No videos found.</p>
<?php endif; ?>
</div>
</div>
</div>

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

// Log when a video starts playing (brgy)
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('video[data-id]').forEach(function(v) {
      v.addEventListener('play', function() {
      const id = v.getAttribute('data-id');
      const title = v.getAttribute('data-title') || '';
      // Send AJAX POST to brgy_log_action.php with title only
      fetch('brgy_log_action.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'brgy_play_video', details: title })
      }).catch(()=>{});
    }, { once: true }); // only log first play per page load
  });
});
</script>
</body>
</html>
