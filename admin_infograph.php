<?php
// Database connection
$servername = "localhost";
$username = "root";      
$password = "";          
$dbname   = "arthurnerritech_library";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Require brgyOfficer or mdrrmOfficer
require_once __DIR__ . '/auth.php';
require_role('mdrrmOfficer');

$upload_msg = "";

// Handle insert
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['infograph_title']);
    $theme      = trim($_POST['infograph_theme']);
    $date_added = $_POST['date_added'];

    // File handling (store as BLOB)
    $imageData = null;
    if (!empty($_FILES['infograph_image']['tmp_name'])) {
        $imageData = file_get_contents($_FILES['infograph_image']['tmp_name']);
    }

    if (empty($title) || empty($theme) || empty($date_added) || empty($imageData)) {
        $upload_msg = "⚠ Please fill in all fields and upload an image.";
    } else {
        $stmt = $conn->prepare("INSERT INTO infograph (title, theme, date_added, image) VALUES (?, ?, ?, ?)");
        $null = NULL; 
        $stmt->bind_param("sssb", $title, $theme, $date_added, $null);
        $stmt->send_long_data(3, $imageData);

        if ($stmt->execute()) {
            $upload_msg = "✅ Infographic saved successfully!";
        } else {
            $upload_msg = "❌ Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM infograph WHERE id=$id");
    header("Location: admin_infograph.php"); 
    exit;
}
// Fetch data (without image here, only metadata)
$result = $conn->query("SELECT id, title, theme, date_added FROM infograph ORDER BY date_added DESC");
$infographics = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>



<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Infographics</title>
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
      .hero{display:grid;grid-template-columns:1fr 420px;gap:28px;align-items:center;padding:48px 0}
      .hero h1{font-size:2.1rem;margin:0 0 12px;line-height:1.05}
      .hero p.lead{color:var(--muted);margin:0 0 18px}
      .hero .hero-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
      .card{background:var(--card);padding:18px;border-radius:12px;box-shadow:0 6px 20px rgba(17,24,39,.06)}
      .mock-device{height:360px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#fff,#f3f6ff);border:1px solid rgba(213,214,216,.06)}
      .mock-device .cover{width:84%;height:78%;border-radius:8px;background-image:linear-gradient(135deg,#fef3c7,#fde68a);display:flex;align-items:flex-end;padding:16px;font-weight:700}
      .grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:26px}
      .feature h4{margin:0 0 8px}
      .muted{color:var(--muted)}
      .split{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin:36px 0}
      .testimonials .quote{background:linear-gradient(180deg,#fff,#fcfcff);padding:14px;border-radius:10px;margin-bottom:12px;box-shadow:0 6px 18px rgba(16,24,40,.04)}
      footer{padding:28px 0;color:var(--muted);font-size:.9rem}
      .small{font-size:.85rem}
      /* FORM STYLING */
      .upload-form-enhanced {background:#fff;padding:25px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.06);transition:.3s ease;margin-bottom:30px;}
      .upload-form-enhanced:hover {transform:translateY(-2px)}
      .upload-form-enhanced .form-group {margin-bottom:15px}
      .upload-form-enhanced label {font-weight:600;display:block;margin-bottom:5px}
      .upload-form-enhanced input[type="text"],.upload-form-enhanced input[type="date"],.upload-form-enhanced input[type="file"] {width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;outline:none;transition:border-color .3s ease}
      .upload-form-enhanced input:focus {border-color:var(--accent);box-shadow:0 0 0 3px rgba(11,108,245,0.1)}
      .btn-submit {display:inline-block;background:var(--accent);color:#fff;padding:12px;border:none;border-radius:6px;font-size:1rem;font-weight:600;cursor:pointer;transition:background .3s ease}
      .btn-submit:hover {background:#084cbf}
      .upload-message {margin-top:12px;padding:12px;border-radius:6px;font-weight:500}
      .upload-message.success{background:#d1fae5;color:#065f46}
      .upload-message.error{background:#fee2e2;color:#991b1b}s
.file-upload {
  border: 2px dashed #ccc;
  padding: 15px;
  border-radius: 6px;
  background: #fafafa;
  text-align: center;
  transition: border-color .3s ease;
}
.file-upload:hover {
  border-color: var(--accent);
}
.file-upload small {
  display: block;
  margin-top: 6px;
  font-size: 0.85rem;
  color: var(--muted);
}
      table{width:100%;border-collapse:collapse;margin-top:20px;background:#fff}
      th,td{border:1px solid #ddd;padding:10px;text-align:left}
      th{background:#f4f4f4}
  /* Fixed thumbnail for infographics to save space */
  .thumb{width:120px;height:80px;object-fit:cover;border-radius:6px;display:block}
      @media(max-width:1000px){.hero{grid-template-columns:1fr}.split{grid-template-columns:1fr}.grid-3{grid-template-columns:1fr}}
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
    <h2>🖼 Add Infographic Details</h2>
    <form method="POST" enctype="multipart/form-data" class="upload-form-enhanced">
        <div class="form-group">
            <label>📌 Title</label>
            <input type="text" name="infograph_title" placeholder="Enter infographic title" required>
        </div>
        <div class="form-group">
            <label>🎯 Theme</label>
            <select name="infograph_theme" required>
                <option value="">-- Select Disaster Theme --</option>
                <option value="Earthquake">🌍 Earthquake</option>
                <option value="Flood">🌊 Flood</option>
                <option value="Typhoon">🌪️ Typhoon</option>
                <option value="Fire">🔥 Fire</option>
                <option value="Landslide">🏔️ Landslide</option>
                <option value="Volcanic Eruption">🌋 Volcanic Eruption</option>
                <option value="Tsunami">🌊 Tsunami</option>
                <option value="Pandemic">🦠 Pandemic</option>
                <option value="Other">⚠️ Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>📅 Date Added</label>
            <input type="date" name="date_added" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group file-upload">
            <label>🖼 Infographic Image</label>
            <input type="file" name="infograph_image" accept="image/*" required>
            <div id="preview"></div>
            <small>Allowed: JPG, PNG, GIF</small>
        </div>
        <button type="submit" class="btn-submit">🚀 Save Infographic</button>
    </form>
    <?php if (!empty($upload_msg)): ?>
        <p class="upload-message <?= strpos($upload_msg,'✅')!==false ? 'success':'error' ?>"><?= $upload_msg ?></p>
    <?php endif; ?>

<h3>📋 Saved Infographics</h3>
<?php if (!empty($infographics)): ?>
<table>
    <tr>
        <th>Title</th>
        <th>Theme</th>
        <th>Date Added</th>
        <th>Image</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($infographics as $i): ?>
    <tr>
        <td><?= htmlspecialchars($i['title']) ?></td>
        <td><?= htmlspecialchars($i['theme']) ?></td>
        <td><?= htmlspecialchars($i['date_added']) ?></td>
        <td>
            <?php
                $id = $i['id'];
                $imgRes = $conn->query("SELECT image FROM infograph WHERE id=$id");
                if ($imgRes && $row = $imgRes->fetch_assoc()) {
          echo '<img class="thumb" src="data:image/jpeg;base64,' . base64_encode($row['image']) . '">';
                }
            ?>
        </td>
        <td>
            <a href="admin_edit_infograph.php?id=<?= $i['id'] ?>" style="padding:6px 10px; background:#0b6cf5; color:white; border-radius:6px; text-decoration:none;">✏ Edit</a>
            <a href="admin_infograph.php?delete=<?= $i['id'] ?>" onclick="return confirm('Are you sure you want to delete this infographic?')" style="padding:6px 10px; background:#e11d48; color:white; border-radius:6px; text-decoration:none;">🗑 Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
    <p>No infographics yet.</p>
<?php endif; ?>

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
<script>
function toggleLogoutMenu(e) {
  e.preventDefault();
  const menu = document.getElementById('logoutMenu');
  menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}
document.querySelector('input[name="infograph_image"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('preview');
    preview.innerHTML = '';
  if (file && file.type.startsWith('image/')) {
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.className = 'thumb';
    img.style.marginTop = '8px';
    preview.appendChild(img);
  }
});
</script>
</body>
</html>
