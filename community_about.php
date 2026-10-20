<?php
// index.php
// Simple single-file PHP interface inspired by https://www.allyoucanbooks.com/
// NOTE: replace placeholder images/text with your own assets when ready.

$newsletter_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    $email = filter_var(trim($_POST['newsletter_email']), FILTER_VALIDATE_EMAIL);
    if ($email) {
        // For demo: pretend to save; in production, insert to DB / mail service
        $newsletter_msg = "Thank you — {$email} has been subscribed!";
    } else {
        $newsletter_msg = "Please enter a valid email address.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>About Us</title>
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
      @media(max-width:1000px){.hero{grid-template-columns:1fr}.split{grid-template-columns:1fr}.grid-3{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <header>
    <div class="container topbar">
      <a href="community_dashboard.php" class="logo">
          <img src="img/logo.png" alt="Site Logo">
        <div class="text">
          <div style="font-weight:800; color: rgba(255, 170, 121, 1)">MDRRMO LEARNING LIBRARY</div>
          <div style="font-size:0.8rem;color:white">Learning Materials</div>
        </div>
      </a>
        <nav>
          <ul>
            <li><a href="http://library.arthurnerri.tech/community_trainmods.php">Training Modules</a></li>
            <li><a href="http://library.arthurnerri.tech/community_watch.php">Videos</a></li>
            <li><a href="http://library.arthurnerri.tech/community_infograph.php">Infographics</a></li>
            <li><a href="http://library.arthurnerri.tech/email/community_contact.php">Contact Us</a></li>
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
