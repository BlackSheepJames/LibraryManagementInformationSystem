<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Contact Us</title>
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
      .card{background:var(--card);padding:24px;border-radius:12px;box-shadow:0 6px 20px rgba(17,24,39,.06);max-width:760px;margin:34px auto}
      h2{margin-top:0}
      label{display:block;margin-top:14px;font-weight:600}
      input[type=text],input[type=email],textarea,select{width:100%;padding:10px;margin-top:6px;border:1px solid #ddd;border-radius:6px;font-size:.95rem}
      button{margin-top:16px;padding:12px 16px;background:var(--accent);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:.2s}
      button:hover{background:#0751c3}
      .row{display:flex;gap:12px;flex-wrap:wrap}
      .col{flex:1}
      .notice{padding:10px;margin-top:14px;border-radius:6px;font-size:.9rem}
      .success{background:#e6ffed;border:1px solid #b7f1c9;color:#154b20}
      .error{background:#fff1f0;border:1px solid #f3c2c6;color:#6a1414}
      .hint{color:var(--muted);font-size:.9rem}

      /* FOOTER */
      footer{padding:28px 0;color:var(--muted);font-size:.9rem}
      .small{font-size:.85rem}
  </style>
</head>
<body>
  <!-- HEADER -->
  <header>
    <div class="container topbar">
      <a href="http://library.arthurnerri.tech/community_dashboard.php" class="logo">
        <img src="img/logo1.png" alt="Site Logo">
        <div class="text">
          <div style="font-weight:800; color: rgba(255,170,121,1)">MDRRMO LEARNING LIBRARY</div>
          <div style="font-size:0.8rem;color:white">Learning Materials</div>
        </div>
      </a>
        <nav>
          <ul>
            <li><a href="community_trainmods.php">Training Modules</a></li>
            <li><a href="community_watch.php">Videos</a></li>
            <li><a href="community_infograph.php">Infographics</a></li>
            <li><a href="community_games.php">Games</a></li>
            <li><a href="http://library.arthurnerri.tech/email/community_contact.php" class="cta">Contact Us</a></li>
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

  <!-- MAIN CONTENT -->
  <main class="container">
    <div class="card">
      <h2>Send Email</h2>
      <p class="hint">Fill the fields and use mdrrmo@arthurnerri.tech as recipient to notify them with your concern/account creation</p>

      <form id="sendForm">
        <label for="to">To (recipient email)</label>
        <input id="to" name="to" type="email" required placeholder="recipient@example.com" />

        <div class="row">
          <div class="col">
            <label for="fromName">Your name</label>
            <input id="fromName" name="fromName" type="text" placeholder="Sender name" />
          </div>
          <div class="col">
            <label for="fromEmail">Your email (used as Reply-To)</label>
            <input id="fromEmail" name="fromEmail" type="email" placeholder="you@example.com" />
          </div>
        </div>

        <label for="subject">Subject</label>
        <input id="subject" name="subject" type="text" required placeholder="Subject" />

        <label for="message">Message</label>
        <textarea id="message" name="message" rows="8" required placeholder="Write your message here..."></textarea>

        <label for="isHtml">Message format</label>
        <select id="isHtml" name="isHtml">
          <option value="1">HTML</option>
          <option value="0">Plain text</option>
        </select>

        <button type="submit">Send</button>
      </form>

      <div id="result"></div>
    </div>

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
            <p class="muted small">Unlimited access to disaster preparedness and awareness books, infographics, and videos.</p>
          </div>

        <div style="margin-top:22px; border-top:1px solid rgba(15,23,42,0.03); padding-top:14px; color:var(--muted); display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
          <div>© <?= date('Y') ?> MDRRMO Learning Library. All rights reserved.</div>
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

    // FORM SUBMIT HANDLER
    const form = document.getElementById('sendForm');
    const result = document.getElementById('result');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      result.innerHTML = '';
      const data = {
        to: form.to.value,
        subject: form.subject.value,
        message: form.message.value,
        fromName: form.fromName.value,
        fromEmail: form.fromEmail.value,
        isHtml: form.isHtml.value === '1'
      };

      try {
        const res = await fetch('send.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });
        const payload = await res.json();
        if (res.ok && payload.success) {
          result.innerHTML = '<div class="notice success">Email sent.</div>';
          form.reset();
        } else {
          result.innerHTML = '<div class="notice error">Error: ' + (payload.error || 'Unknown') + '</div>';
        }
      } catch (err) {
        result.innerHTML = '<div class="notice error">Network error: ' + err.message + '</div>';
      }
    });
  </script>
</body>
</html>
