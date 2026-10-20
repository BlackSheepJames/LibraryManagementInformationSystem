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

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$videos = [];
$sql = "SELECT * FROM videos";
if ($search_query !== '') {
  $esc = $conn->real_escape_string(strtolower($search_query));
  $sql .= " WHERE LOWER(title) LIKE '%$esc%' OR LOWER(theme) LIKE '%$esc%' OR LOWER(publisher) LIKE '%$esc%'";
}
$sql .= " ORDER BY date_added DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $videos[] = $row;
  }
}
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Watch Videos</title>
  <style>
      :root{
        --accent:#0b6cf5;--muted:#6b7280;--bg:#f7f9fc;--card:#ffffff;
        --maxw:1100px;
        font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial
      }
      *{box-sizing:border-box}
      body{margin:0;background:var(--bg);color:#0b1220;-webkit-font-smoothing:antialiased}
      .container{max-width:var(--maxw);margin:0 auto;padding:28px}
      header{
        background:linear-gradient(90deg, rgba(116, 3, 3, 1), transparent);
        position:sticky;top:0;z-index:50;backdrop-filter: blur(6px);
        transition: all 0.3s ease;
      }
      .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0}
      .logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;transition:transform .3s}
      .logo:hover{transform:scale(1.05);}
      .logo img {height: 35px; width: auto; border-radius: 4px;}
      nav ul{list-style:none;display:flex;gap:14px;margin:0;padding:0;align-items:center}
      nav a{
        color:rgba(56, 20, 20, 1);
        text-decoration:none;font-weight:600;font-size:0.95rem;
        position:relative;transition:color .3s;
      }
      nav a::after{
        content:"";position:absolute;left:0;bottom:-4px;width:0;height:2px;
        background:rgba(255,59,59,1);transition:width .3s;
      }
      nav a:hover{color:rgba(255,59,59,1);}
      nav a:hover::after{width:100%;}
      .cta{
        background:rgba(255,59,59,1);color:#fff;padding:10px 14px;border-radius:8px;
        text-decoration:none;font-weight:700;box-shadow:0 6px 18px rgba(11,108,245,.12);
        transition:transform .3s, box-shadow .3s;
      }
      .cta:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(11,108,245,.18);}
      
      /* Dropdown */
      .dropdown {position:relative;}
      .dropdown-menu {
        display:none;opacity:0;transform:translateY(-10px);
        position:absolute;top:100%;right:0;background:#fff;
        border:1px solid rgba(0,0,0,0.1);padding:10px;border-radius:6px;
        box-shadow:0 4px 10px rgba(0,0,0,0.1);width:180px;z-index:100;
        transition:opacity .3s ease, transform .3s ease;
      }
      .dropdown-menu.show {display:block;opacity:1;transform:translateY(0);}
      .dropdown-menu p {font-size:0.85rem;margin:0 0 8px;color:#333;}
      .dropdown-menu a {display:inline-block;padding:5px 8px;font-size:0.85rem;text-decoration:none;color:#fff;border-radius:4px;margin-right:5px;transition:background .3s;}
      .confirm-btn {background:red;}
      .dropdown-menu a:not(.confirm-btn) {background:gray;}
      .dropdown-menu a:hover {opacity:0.85;}
      .muted{color:var(--muted)}
      footer{padding:28px 0;color:var(--muted);font-size:.9rem}
      .small{font-size:.85rem}
      .video-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin:20px 0;}
      .video-card{background:var(--card);border-radius:12px;box-shadow:0 6px 20px rgba(17,24,39,.06);overflow:hidden;display:flex;flex-direction:column;}
      .video-card video{width:100%;height:200px;object-fit:contain;background:#000;}
      .video-card .info{padding:10px;}
      .video-card h3{margin:0 0 5px;font-size:0.95rem;}
      .video-card p{margin:2px 0;font-size:0.8rem;color:var(--muted);}
    </style>
</head>
<body>
      <!-- HEADER -->
  <header>
    <div class="container topbar fade-in">
      <a href="admin_dashboard.php" class="logo">
          <img src="img/logo.png" alt="Site Logo">
        <div class="text">
          <div style="font-weight:800; color: rgba(255, 170, 121, 1)">MDRRMO LEARNING LIBRARY</div>
          <div style="font-size:0.8rem;color:white">Learning Materials</div>
        </div>
      </a>
        <nav>
          <ul>
            <li><a href="community_trainmods.php">Training Modules</a></li>
            <li><a href="community_watch.php" class="cta">Videos</a></li>
            <li><a href="community_infograph.php">Infographics</a></li>
            <li><a href="community_games.php">Games</a></li>
            <li><a href="http://library.arthurnerri.tech/email/community_contact.php">Contact Us</a></li>
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

    <h1 style="margin-top:20px;">📺 Community Videos</h1>
    <form method="GET" style="max-width:800px;margin:10px auto 18px;display:flex;gap:8px;">
      <input type="text" name="search" placeholder="Search by title, theme, or publisher..." value="<?= htmlspecialchars($search_query) ?>" style="flex:1;padding:8px;border:1px solid #ccc;border-radius:6px;">
      <button type="submit" style="padding:8px 12px;background:#0b6cf5;color:#fff;border:none;border-radius:6px;cursor:pointer;">Search</button>
      <?php if ($search_query !== ''): ?>
        <a href="community_watch.php" style="display:inline-flex;align-items:center;padding:8px 12px;background:#ddd;border-radius:6px;text-decoration:none;color:#333;">Clear</a>
      <?php endif; ?>
    </form>
    <div class="video-grid">
      <?php if (!empty($videos)): ?>
        <?php foreach ($videos as $v): ?>
          <div class="video-card">
            <video controls data-id="<?= (int)$v['id'] ?>" data-title="<?= htmlspecialchars($v['title'], ENT_QUOTES) ?>">
              <source src="uploads/videos/<?= htmlspecialchars($v['file_name']) ?>" type="video/mp4">
              Your browser does not support the video tag.
            </video>
            <div class="info">
              <h3><?= htmlspecialchars($v['title']) ?></h3>
              <p><strong>Theme:</strong> <?= htmlspecialchars($v['theme']) ?></p>
              <p><strong>Publisher:</strong> <?= htmlspecialchars($v['publisher']) ?></p>
              <p><small><?= htmlspecialchars($v['date_added']) ?></small></p>
              <p><small><a href="download_video.php?id=<?= (int)$v['id'] ?>" style="text-decoration:none; color:var(--accent);">⬇️ Download</a></small></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No videos uploaded yet.</p>
      <?php endif; ?>
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
              <div><a href="#" style="color:inherit; text-decoration:none;">Contact Us</a></div>
            </div>
          </div>

          <div style="min-width:200px;">
            <div style="font-weight:700; margin-bottom:6px;">Follow us</div>
            <a href="https://www.facebook.com/luisiana.mdrrmo" class="muted small" style="text-decoration:none; margin-right:12px;">Facebook</a>
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
  menu.classList.toggle('show');
}

// Close dropdown if clicked outside
document.addEventListener('click', function(event) {
  const menu = document.getElementById('logoutMenu');
  const dropdown = document.querySelector('.dropdown');
  if (!dropdown.contains(event.target)) {
    menu.classList.remove('show');
  }
});

// Animate sections on scroll
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('animate');
    }
  });
});
document.querySelectorAll('footer, main').forEach(el => observer.observe(el));
        

// Log when a video starts playing
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('video[data-id]').forEach(function(v) {
      v.addEventListener('play', function() {
      const id = v.getAttribute('data-id');
      const title = v.getAttribute('data-title') || '';
      // Send AJAX POST to log_action.php with title only
      fetch('log_action.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'play_video', details: title })
      }).catch(()=>{});

      // Optionally hit an endpoint to increment views (non-blocking)
  fetch('increment_video_view.php?id=' + encodeURIComponent(id), { credentials: 'same-origin' }).catch(()=>{});
    }, { once: true }); // only log first play per page load
  });
});
</script>
</body>
</html>
