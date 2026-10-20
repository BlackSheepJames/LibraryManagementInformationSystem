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
require_once __DIR__ . '/audit_brgy.php';

// File paths
$target_dir = "uploads/modules/";
$cover_dir  = "uploads/covers/";

if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
if (!is_dir($cover_dir)) mkdir($cover_dir, 0777, true);

$upload_msg = "";

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['module_file'])) {
    $title       = trim($_POST['module_title']);
    $theme       = trim($_POST['module_theme']);
    $date_added  = $_POST['date_added'];

    $file_name   = basename($_FILES["module_file"]["name"]);
    $cover_name  = basename($_FILES["cover_photo"]["name"]);

    $target_file = $target_dir . $file_name;
    $cover_file  = $cover_dir . $cover_name;

    $allowed_types  = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip'];
    $allowed_images = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $file_type  = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $cover_type = strtolower(pathinfo($cover_file, PATHINFO_EXTENSION));

    if (empty($title) || empty($theme) || empty($date_added) || empty($cover_name)) {
        $upload_msg = "⚠ Please fill in all fields and select a cover photo.";
    } elseif (!in_array($file_type, $allowed_types)) {
        $upload_msg = "❌ Only PDF, DOC, PPT, and ZIP files are allowed.";
    } elseif (!in_array($cover_type, $allowed_images)) {
        $upload_msg = "❌ Cover photo must be JPG, PNG, GIF, or WebP.";
    } elseif (
        move_uploaded_file($_FILES["module_file"]["tmp_name"], $target_file) &&
        move_uploaded_file($_FILES["cover_photo"]["tmp_name"], $cover_file)
    ) {
        $stmt = $conn->prepare("INSERT INTO modules (title, theme, date_added, file_name, cover_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $title, $theme, $date_added, $file_name, $cover_name);

    if ($stmt->execute()) {
      $upload_msg = "✅ Module and cover photo uploaded successfully!";
      if (function_exists('log_brgy_action')) {
        log_brgy_action('upload_module', $title . ' | file: ' . $file_name);
      }
    } else {
      $upload_msg = "❌ Database error: " . $stmt->error;
    }
        $stmt->close();
    } else {
        $upload_msg = "❌ Sorry, there was an error uploading your files.";
    }
}

// Search filter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$modules = [];

if (!empty($search_query)) {
    $stmt = $conn->prepare("SELECT * FROM modules WHERE title LIKE ? OR theme LIKE ?");
    $like = "%" . $search_query . "%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM modules ORDER BY date_added DESC");
}

if ($result) {
    $modules = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Brgy Manage Training Modules</title>
  <style>
      :root{--accent:#0b6cf5;--muted:#6b7280;--bg:#f7f9fc;--card:#ffffff;--maxw:1100px;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      *{box-sizing:border-box}
      body{margin:0;background:var(--bg);color:#0b1220;-webkit-font-smoothing:antialiased}
      .container{max-width:var(--maxw);margin:0 auto;padding:28px}
      .container1{max-width:1000px; margin:40px auto 0; /* 20px space at the top */ background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }      .section{margin-bottom:30px;padding:20px;border:1px solid #ddd;border-radius:8px;background:#fafafa}
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
      .message{margin-top:10px;padding:10px;border-radius:6px;background:#f1f5f9}
      .file-list{margin-top:30px;background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
      .file-list h3{margin-top:0}
      .file-item{padding:8px 0;border-bottom:1px solid #eee}
      .file-item:last-child{border-bottom:none}
      .file-item a{text-decoration:none;color:#0b6cf5}
      .table-container{overflow-x:auto}
      table{width:100%;border-collapse:collapse;margin-top:20px;background:#fff}
      th,td{border:1px solid #ddd;padding:10px;text-align:left}
      th{background:#f4f4f4}
      .search-box{display:flex;gap:10px;margin-top:10px}
      .search-box input{flex:1;padding:8px;border:1px solid #ccc;border-radius:4px}
      .search-box button{padding:8px 12px;background:#007bff;color:#fff;border:none;border-radius:4px;cursor:pointer}
      .search-box button:hover{background:#0056b3}      
      .action-btn{padding:6px 10px;margin:2px;text-decoration:none;color:#fff;border-radius:4px;font-size:0.85rem}
      .edit-btn{background:orange}
      .view-count{font-size:0.85rem;color:#555}
      footer{padding:28px 0;color:var(--muted);font-size:.9rem}
      .small{font-size:.85rem}
      @media(max-width:1000px){.hero{grid-template-columns:1fr}.split{grid-template-columns:1fr}.grid-3{grid-template-columns:1fr}}
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
<h2>Upload Training Modules</h2>
<form method="POST" enctype="multipart/form-data" class="upload-form-enhanced">
    <div class="form-group">
        <label>📌 Title</label>
        <input type="text" name="module_title" placeholder="Enter module title" required>
    </div>
    <div class="form-group">
        <label>🎯 Theme</label>
        <input type="text" name="module_theme" placeholder="Enter theme" required>
    </div>
    <div class="form-group">
        <label>📅 Date Added</label>
        <input type="date" name="date_added" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="form-group file-upload">
        <label>📂 Select Module File</label>
        <input type="file" name="module_file" required>
        <small>Allowed: PDF, DOC, PPT, ZIP</small>
    </div>
    <div class="form-group file-upload">
        <label>🖼 Cover Photo</label>
        <input type="file" name="cover_photo" accept="image/*" required>
        <div id="coverPreview"></div>
        <small>Allowed: JPG, PNG, GIF, WebP</small>
    </div>
    <button type="submit" class="btn-submit">🚀 Upload Module</button>
</form>
<?php if (!empty($upload_msg)) echo "<p class='upload-message'>$upload_msg</p>"; ?>
</div>

<div class="section">
<h3>Uploaded Modules</h3>
<form method="GET" class="search-box">
    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search_query) ?>">
    <button type="submit">Search</button>
    <?php if (!empty($search_query)): ?>
        <button type="button" onclick="window.location.href='admin_trainmods_sql.php'">Clear</button>
    <?php endif; ?>
</form>
<div class="table-container">
<?php if (!empty($modules)): ?>
<table>
    <tr>
        <th>Cover</th>
        <th>Title</th>
        <th>Theme</th>
        <th>Date Added</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($modules as $m): ?>
    <tr>
        <td><img src="uploads/covers/<?= htmlspecialchars($m['cover_name']) ?>" alt="Cover" style="width:60px;height:auto;border-radius:4px;"></td>
        <td><?= htmlspecialchars($m['title']) ?></td>
        <td><?= htmlspecialchars($m['theme']) ?></td>
        <td><?= htmlspecialchars($m['date_added']) ?></td>
        <td>
            <a href="brgy_update_module.php?id=<?= $m['id'] ?>" class="action-btn edit-btn">✏️ Edit</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
<p>No modules found.</p>
<?php endif; ?>
</div>
</div>
</div>

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
    </footer>

  </main>
<script>
document.querySelector('input[name="cover_photo"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('coverPreview');
    preview.innerHTML = '';
    if (file && file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.maxWidth = '100px';
        img.style.marginTop = '8px';
        img.style.borderRadius = '4px';
        preview.appendChild(img);
    }
});
function toggleLogoutMenu(e) {
  e.preventDefault();
  const menu = document.getElementById('logoutMenu');
  menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}

// Close dropdown if clicked outside
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
