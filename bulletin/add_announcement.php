<?php
include 'db_connect.php';

// Simple helper to normalize uploaded filenames
function safe_filename($name) {
	return preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
}

// handle POST actions: add, update or delete
$msg = '';
$editMode = false;
$editData = ['id'=>0,'title'=>'','content'=>'','category'=>'','link'=>'','attachment'=>''];
$reloadIndex = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? 'add';
	// [ ALL your PHP logic remains exactly the same — omitted here for brevity ]
	// (Keep your entire existing PHP logic for add/update/delete announcements unchanged)
	// ...
}

// Load edit mode data
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
	$eid = (int)$_GET['edit'];
	$g = $conn->prepare('SELECT id, title, content, category, link, attachment, date_posted FROM announcements WHERE id = ? LIMIT 1');
	$g->bind_param('i', $eid);
	$g->execute();
	$gr = $g->get_result();
	if ($row = $gr->fetch_assoc()) {
		$editMode = true;
		$editData = $row;
	}
	$g->close();
}

// Fetch announcements
$announcements = [];
$res = $conn->query('SELECT id, title, category, date_posted FROM announcements ORDER BY date_posted DESC');
if ($res) {
	while ($r = $res->fetch_assoc()) $announcements[] = $r;
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Manage Announcements</title>
  <style>
      :root{--accent:#0b6cf5;--muted:#6b7280;--bg:#f7f9fc;--card:#ffffff;--maxw:1100px;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
      *{box-sizing:border-box}
      body{margin:0;background:var(--bg);color:#0b1220;-webkit-font-smoothing:antialiased}
      .container{max-width:var(--maxw);margin:0 auto;padding:10px}
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

      /* Layout */
      main.container {margin-top: 15px;}
      .card {background:var(--card);padding:24px;border-radius:12px;box-shadow:0 6px 20px rgba(17,24,39,.06);margin-bottom:24px;}
      .card h3 {margin-top:0;}
      input[type=text], textarea, select, input[type=date], input[type=file] {
        width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:8px;
      }
      button {background:rgba(255,59,59,1); color:#fff; border:none; border-radius:8px; padding:10px 16px; cursor:pointer;}
      button:hover {opacity:0.9;}
      .msg {padding:10px; border-radius:8px; margin-bottom:12px;}
      .msg.success {background:#e6ffed;color:#0a6b2d;border:1px solid #b7f0c7;}
      .msg.error {background:#ffecec;color:#8b0000;border:1px solid #f4bcbc;}
      .ann-item {display:flex;justify-content:space-between;align-items:center;padding:10px;border-bottom:1px solid #eee;}
      .ann-item strong {font-size:1rem;}
      .ann-item small {color:var(--muted);}
  .ann-list { --ann-row-height:62px; max-height: calc(var(--ann-row-height) * 8); overflow:auto;}
      a.action-link {color:var(--accent);text-decoration:none;font-weight:600;}
      a.action-link:hover{text-decoration:underline;}

      footer{padding:28px 0;color:var(--muted);font-size:.9rem;margin-top:40px;}
      .small{font-size:.85rem}
  </style>
</head>
<body>
  <header>
    <div class="container topbar">
      <a href="../admin_dashboard.php" class="logo">
          <img src="img/logo.png" alt="Site Logo">
        <div class="text">
          <div style="font-weight:800; color: rgba(255, 170, 121, 1)">MDRRMO LEARNING LIBRARY</div>
          <div style="font-size:0.8rem;color:white">Learning Materials</div>
        </div>
      </a>
      <nav>
          <ul>
            <li><a href="../brgy_audit_log.php">Barangay Audits</a></li>
            <li><a href="../audit_log.php">Community Audits</a></li>
            <li><a href="../admin_trainmods.php">Training Modules</a></li>
            <li><a href="../admin_watch.php">Videos</a></li>
            <li><a href="../admin_infograph.php">Infographics</a></li>
            <li><a href="../admin_statistics.php">Statistics</a></li>
            <li class="dropdown">
              <a href="#" onclick="toggleLogoutMenu(event)">Logout ▼</a>
              <div class="dropdown-menu" id="logoutMenu">
                <p>Are you sure you want to logout?</p>
                <a href="https://library.arthurnerri.tech/logout.php" class="confirm-btn">Yes, Logout</a>
                <a href="#" onclick="toggleLogoutMenu(event)">Cancel</a>
              </div>
            </li>
          </ul>
      </nav>
    </div>
  </header>

  <main class="container">
    <h2 style="margin-bottom:20px;">📢 Announcements Management</h2>

    <?php if (!empty($msg)): ?>
      <div class="msg <?php echo (stripos($msg,'failed')===false && stripos($msg,'invalid')===false) ? 'success':'error' ?>">
        <?php echo htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($msg) && !empty($reloadIndex)): ?>
      <script>
        setTimeout(function(){ window.location.href = 'index.php'; }, 1200);
      </script>
    <?php endif; ?>

    <div class="card">
      <h3><?php echo $editMode ? 'Edit Announcement' : 'Post New Announcement'; ?></h3>
      <form id="announcementForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $editMode ? 'update' : 'add' ?>">
        <?php if ($editMode): ?>
          <input type="hidden" name="id" value="<?php echo (int)$editData['id'] ?>">
        <?php endif; ?>

        <input type="text" name="title" placeholder="Title" required value="<?php echo htmlspecialchars($editMode ? $editData['title'] : '') ?>">
        <textarea name="content" placeholder="Announcement details..." required rows="6"><?php echo $editMode ? htmlspecialchars($editData['content']) : '' ?></textarea>
        <select name="category" required>
          <option value="Policy Update" <?php echo ($editMode && $editData['category']==='Policy Update') ? 'selected':''; ?>>Policy Update</option>
          <option value="New Features" <?php echo ($editMode && $editData['category']==='New Features') ? 'selected':''; ?>>New Features</option>
          <option value="New Module" <?php echo ($editMode && $editData['category']==='New Module') ? 'selected':''; ?>>New Module</option>
        </select>
        <?php
          // prepare datetime-local value (YYYY-MM-DDTHH:MM)
          $dtVal = date('Y-m-d\TH:i');
          if ($editMode && !empty($editData['date_posted'])) {
              try { $d2 = new DateTime($editData['date_posted']); $dtVal = $d2->format('Y-m-d\TH:i'); } catch(Exception $e) { /* ignore */ }
          }
        ?>
        <input type="datetime-local" name="date_posted" value="<?php echo htmlspecialchars($dtVal) ?>">
        <input type="text" name="link" placeholder="Optional link" value="<?php echo htmlspecialchars($editMode ? $editData['link'] : '') ?>">
        <input type="file" name="attachment">

        <div style="margin-top:8px;">
          <button type="submit"><?php echo $editMode ? 'Save Changes' : 'Add Announcement'; ?></button>
          <?php if ($editMode): ?> <a href="add_announcement.php" class="action-link" style="margin-left:12px;">Cancel</a><?php endif; ?>
        </div>
      </form>
    </div>


    <div class="card">
      <h3>Existing Announcements</h3>
      <div style="margin-bottom:10px;display:flex;gap:8px;align-items:center;">
        <input id="annSearch" type="search" placeholder="Search announcements by title..." style="flex:1;padding:8px;border:1px solid #ddd;border-radius:8px;">
        <div style="font-size:0.9rem;color:var(--muted);"></div>
      </div>
      <div class="ann-list" id="annList">
        <?php if (empty($announcements)): ?>
          <p class="muted">No announcements yet.</p>
        <?php else: ?>
          <?php foreach ($announcements as $a): ?>
            <?php
              $aid = (int)$a['id'];
              $atitle = htmlspecialchars($a['title']);
              $acat = htmlspecialchars($a['category'] ?? '');
              $adate = '';
              if (!empty($a['date_posted'])) {
                  try { $adt = new DateTime($a['date_posted']); $adate = $adt->format('M j, Y \a\t g:i A'); } catch(Exception $e){ $adate = htmlspecialchars($a['date_posted']); }
              }
            ?>
            <div class="ann-item" data-id="<?php echo $aid ?>" data-title="<?php echo $atitle ?>" data-category="<?php echo $acat ?>">
              <div>
                <strong class="ann-title"><?php echo $atitle ?></strong> <span class="ann-cat">(<?php echo $acat ?>)</span><br>
                <small class="ann-date"><?php echo $adate ?></small>
              </div>
              <div style="display:flex; gap:8px; align-items:center;">
                <a href="add_announcement.php?edit=<?php echo $aid ?>" class="action-link">Edit</a>
                <form method="POST" style="margin:0;">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo $aid ?>">
                  <button type="submit" style="background:#6b7280;">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
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
    if (!dropdown.contains(event.target)) menu.style.display = 'none';
  });
  </script>
  <script>
  // Intercept delete forms and perform AJAX delete to avoid page reloads
  (function(){
    function initDeleteHandlers(){
      document.querySelectorAll('#annList form').forEach(function(f){
        f.addEventListener('submit', function(e){
          e.preventDefault();
          if (!confirm('Delete this announcement?')) return;
          var id = f.querySelector('input[name="id"]').value;
          fetch('delete_announcement.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(id)
          })
          .then(r=>r.json())
          .then(function(j){
            if (j.status === 'ok') {
            var item = f.closest('.ann-item'); if (item) item.remove();
              try { localStorage.setItem('bulletin_updated', Date.now()); } catch(e){}
            } else {
              alert('Delete failed: ' + (j.message||'unknown'));
            }
          }).catch(function(err){ console.error(err); alert('Delete failed'); });
        });
      });
    }
    // expose to allow attaching handlers for newly-added items
    window.initDeleteHandlers = initDeleteHandlers;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initDeleteHandlers); else initDeleteHandlers();
  })();
  </script>
  <script>
  // search/filter for admin announcements list
  (function(){
    var input = document.getElementById('annSearch');
    if (!input) return;
    var list = document.getElementById('annList');
    input.addEventListener('input', function(){
      var q = input.value.trim().toLowerCase();
      Array.prototype.forEach.call(list.querySelectorAll('.ann-item'), function(item){
        var title = (item.getAttribute('data-title') || '').toLowerCase();
        if (q === '' || title.indexOf(q) !== -1) item.style.display = ''; else item.style.display = 'none';
      });
    });
  })();
  </script>
  <script>
  // AJAX add/update handler for the admin form
  (function(){
    var form = document.getElementById('announcementForm');
    if (!form) return;
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var fd = new FormData(form);
      // send via fetch to save_announcement.php
      fetch('save_announcement.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(function(resp){
          if (resp.status !== 'ok') {
            alert('Save failed: ' + (resp.message || 'unknown'));
            return;
          }
          // Build or update the announcement item in the admin list
          var annList = document.getElementById('annList');
          if (!annList) return;
          var id = resp.id;
          var title = resp.title;
          var date_posted = resp.date_posted;
          var action = resp.action; // add or update

          if (action === 'add') {
            // create new ann-item
            var div = document.createElement('div');
            div.className = 'ann-item';
            div.setAttribute('data-id', id);
            div.setAttribute('data-title', title);
            // category available from response
            var category = resp.category || '';
            div.setAttribute('data-category', category);
            div.innerHTML = '<div>\n  <strong class="ann-title">'+escapeHtml(title)+'</strong> <span class="ann-cat">('+escapeHtml(category)+')</span><br>\n  <small class="ann-date">'+escapeHtml(date_posted)+'</small>\n</div>\n<div style="display:flex; gap:8px; align-items:center;">\n  <a href="add_announcement.php?edit='+id+'" class="action-link">Edit</a>\n  <form method="POST" style="margin:0;">\n    <input type="hidden" name="action" value="delete">\n    <input type="hidden" name="id" value="'+id+'">\n    <button type="submit" style="background:#6b7280;">Delete</button>\n  </form>\n</div>';
            // prepend so newest shows on top
            if (annList.firstChild) annList.insertBefore(div, annList.firstChild); else annList.appendChild(div);
            // attach delete handler
            if (window.initDeleteHandlers) window.initDeleteHandlers();
          } else if (action === 'update') {
            // find existing item by data-id
            var item = document.querySelector('.ann-item[data-id="'+id+'"]');
            if (item) {
              item.setAttribute('data-title', title);
              item.setAttribute('data-category', resp.category || '');
              var t = item.querySelector('.ann-title');
              var c = item.querySelector('.ann-cat');
              var d = item.querySelector('.ann-date');
              if (t) t.textContent = title;
              if (c) c.textContent = '(' + (resp.category || '') + ')';
              if (d) d.textContent = date_posted;
            }
            // clear edit mode UI on form
            form.reset();
            // remove hidden id and set action back to add
            var hid = form.querySelector('input[name="id"]'); if (hid) hid.remove();
            var act = form.querySelector('input[name="action"]'); if (act) act.value = 'add';
            // remove Cancel link if present
            var cancel = document.querySelector('a.action-link[href="add_announcement.php"]'); if (cancel) cancel.remove();
          }

          // notify index page to refresh
          try { localStorage.setItem('bulletin_updated', Date.now()); } catch(e){}
        }).catch(function(err){ console.error(err); alert('Save failed'); });
    });

    function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  })();
  </script>
</body>
</html>
