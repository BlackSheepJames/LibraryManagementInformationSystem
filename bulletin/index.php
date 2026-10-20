<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Digital Bulletin Board</title>
  <style>
      :root {
        --accent:#0b6cf5;
        --muted:#6b7280;
        --bg:#f7f9fc;
        --card:#ffffff;
        --maxw:1100px;
        font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial;
      }
      *{box-sizing:border-box;}
      body {
        margin:0;
        background:var(--bg);
        color:#0b1220;
        -webkit-font-smoothing:antialiased;
      }
      .container{max-width:var(--maxw);margin:0 auto;padding:28px;}
      header{
        background:linear-gradient(90deg, rgba(116, 3, 3, 1), transparent);
        position:sticky;top:0;z-index:50;backdrop-filter: blur(6px);
      }
      .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0;}
      .logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;}
      .logo img{height:35px;width:auto;border-radius:4px;}
      nav ul{list-style:none;display:flex;gap:14px;margin:0;padding:0;align-items:center;}
      nav a{color:rgba(56, 20, 20, 1);text-decoration:none;font-weight:600;font-size:0.95rem;}
      .cta{background:rgba(255,59,59,1);color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none;font-weight:700;box-shadow:0 6px 18px rgba(11,108,245,.12);}
      .dropdown{position:relative;}
      .dropdown-menu {
        display:none;
        position:absolute;
        top:100%;right:0;
        background:#fff;
        border:1px solid rgba(0,0,0,0.1);
        padding:10px;
        border-radius:6px;
        box-shadow:0 4px 10px rgba(0,0,0,0.1);
        width:180px;
        z-index:100;
      }
      .dropdown-menu p{font-size:0.85rem;margin:0 0 8px;color:#333;}
      .dropdown-menu a{
        display:inline-block;
        padding:5px 8px;
        font-size:0.85rem;
        text-decoration:none;
        color:#fff;
        border-radius:4px;
        margin-right:5px;
      }
      .confirm-btn{background:red;}
      .dropdown-menu a:not(.confirm-btn){background:gray;}

      main{min-height:80vh;}
      h2{color:rgba(116, 3, 3, 1);}
      .calendar-frame {
        border-radius:12px;
        overflow:hidden;
        box-shadow:0 4px 12px rgba(0,0,0,0.1);
        margin-bottom:24px;
      }
      iframe {
        width:100%;
        height:400px;
        border:0;
      }
      /* Bulletin layout */
#bulletin-board {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 20px;
  justify-content: center;
}

/* Card styling */
.bulletin-card {
  background: #ffffff;
  border-left: 6px solid rgba(116, 3, 3, 1);
  border-radius: 10px;
  box-shadow: 0 6px 18px rgba(17, 24, 39, .06);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  text-align: left;
  max-width: 220px;        /* 👈 keeps uniform card width */
  min-height: 20px;       /* 👈 consistent height */
  margin: auto;
  padding: 16px;
}

.bulletin-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 22px rgba(0, 0, 0, 0.12);
}

/* Inner content */
.card-content {
  flex: 1;
}

.bulletin-card h3 {
  margin: 0 0 8px;
  color: rgba(116, 3, 3, 1);
  font-size: 1rem;
}

.bulletin-card p {
  color: #333;
  font-size: 0.9rem;
  margin-bottom: 12px;
}

.card-footer small {
  color: #555;
}

.card-link {
  display: inline-block;
  margin-top: 6px;
  font-size: 0.85rem;
  color: rgba(116, 3, 3, 1);
  text-decoration: none;
  font-weight: 600;
}
.card-link:hover {
  text-decoration: underline;
}

/* Category-based tint backgrounds */
.bulletin-card.policy { background: #eaf2ff; }
.bulletin-card.event { background: #e9f9ed; }
.bulletin-card.feature { background: #fff3f4; }
.bulletin-card.module { background: #fff7e6; }
/* === Tab Buttons (Elegant Red-Accent Style) === */
.tab-btn {
  background: rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(116, 3, 3, 0.3);
  color: rgba(116, 3, 3, 1);
  font-weight: 700;
  font-size: 0.95rem;
  padding: 10px 18px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.35s ease;
  backdrop-filter: blur(6px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  letter-spacing: 0.3px;
}

/* Hover effect: soft color shift and subtle glow */
.tab-btn:hover {
  background: linear-gradient(135deg, rgba(255, 77, 77, 0.9), rgba(255, 100, 100, 0.8));
  color: #fff;
  box-shadow: 0 6px 18px rgba(116, 3, 3, 0.25);
  transform: translateY(-2px);
}

/* Active (selected) tab styling */
.tab-btn.active,
.tab-btn[aria-pressed="true"] {
  background: linear-gradient(135deg, rgba(116, 3, 3, 1), rgba(255, 77, 77, 0.9));
  color: #fff;
  border-color: rgba(116, 3, 3, 0.8);
  box-shadow: 0 6px 20px rgba(116, 3, 3, 0.35);
  transform: translateY(-1px);
}

/* Subtle pressed effect */
.tab-btn:active {
  transform: scale(0.98);
  box-shadow: 0 3px 10px rgba(116, 3, 3, 0.2);
}

/* Responsive tweaks */
@media (max-width: 1200px) {
  #bulletin-board { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
}
@media (max-width: 768px) {
  #bulletin-board { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
  #bulletin-board { grid-template-columns: 1fr; }
}
/* Mobile adjustments */
@media (max-width: 600px) {
  .tab-btn {
    font-size: 0.85rem;
    padding: 8px 14px;
  }
}
</style>
</head>
<body>
  <header>
    <div class="container topbar">
      <a href="https://library.arthurnerri.tech/index.php" class="logo">
        <img src="img/logo.png" alt="Site Logo">
        <div class="text">
          <div style="font-weight:800; color: rgba(255, 170, 121, 1)">MDRRMO LEARNING LIBRARY</div>
          <div style="font-size:0.8rem;color:white">Learning Materials</div>
        </div>
      </a>
    </div>
  </header>

  <main class="container">
    <div class="calendar-frame">
      <iframe src="https://calendar.google.com/calendar/embed?src=en.philippines%23holiday%40group.v.calendar.google.com&ctz=Asia%2FManila" frameborder="0" scrolling="no"></iframe>
    </div>

    <section>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <h2 style="text-align:center; color:rgba(116,3,3,1); margin-bottom:30px;">
            Digital Bulletin Board
        </h2>
        <div style="margin-left:auto;display:flex;gap:10px;align-items:center;justify-content:center;">
          <button id="tab-board" class="tab-btn" aria-pressed="true">Board</button>
          <button id="tab-upcoming" class="tab-btn" aria-pressed="false">Upcoming Events</button>
        </div>
      </div>

      <div id="bulletin-board">
        <!-- Announcements will load here -->
      </div>
    </section>
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

  // Tab logic and fetching by mode
  let currentMode = 'past'; // 'past' (board) or 'upcoming'

  const tabBoard = document.getElementById('tab-board');
  const tabUpcoming = document.getElementById('tab-upcoming');

  function setActiveTab(mode) {
    currentMode = mode;
    tabBoard.classList.toggle('active', mode === 'past');
    tabUpcoming.classList.toggle('active', mode === 'upcoming');
    tabBoard.setAttribute('aria-pressed', mode === 'past');
    tabUpcoming.setAttribute('aria-pressed', mode === 'upcoming');
  }

  tabBoard.addEventListener('click', function(){ loadAnnouncements('past'); setActiveTab('past'); });
  tabUpcoming.addEventListener('click', function(){ loadAnnouncements('upcoming'); setActiveTab('upcoming'); });

  async function loadAnnouncements(mode = 'past') {
    try {
      const response = await fetch('fetch_announcements.php?mode=' + encodeURIComponent(mode));
      const data = await response.text();
      document.getElementById('bulletin-board').innerHTML = data;
    } catch (err) {
      document.getElementById('bulletin-board').innerHTML = '<p>Failed to load announcements.</p>';
      console.error(err);
    }
  }

  // initial load
  setActiveTab('past');
  loadAnnouncements(currentMode);
  setInterval(function(){ loadAnnouncements(currentMode); }, 10000);
  // Listen for storage events from admin page to refresh immediately when announcements change
  window.addEventListener('storage', function(e){
    if (e.key === 'bulletin_updated') loadAnnouncements(currentMode);
  });
  </script>
</body>
</html>
