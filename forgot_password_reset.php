<?php
ob_start();
require_once __DIR__ . '/session_bootstrap.php';
include('config.php');

$error = '';
$success = '';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (!$token) {
    $error = 'Invalid or missing token.';
}

if (!$error) {
    $stmt = $conn->prepare('SELECT pr.id AS pr_id, pr.user_id, pr.expires_at, u.username 
        FROM password_resets pr 
        JOIN arthurnerri_users u ON pr.user_id = u.id 
        WHERE pr.token = ?');
    if ($stmt) {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $expires_at = $row['expires_at'];
            $pr_id = $row['pr_id'];
            $user_id = $row['user_id'];

            if (strtotime($expires_at) < time()) {
                $error = 'This reset link has expired. Please request a new one.';
            } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $password = $_POST['password'] ?? '';
                    $password_confirm = $_POST['password_confirm'] ?? '';

          // Mark that a POST was attempted so we can show retry controls
          $post_attempted = true;

          // Password requirements: 8+ chars, 1 uppercase, 1 symbol
          if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
          } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter.';
          } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $error = 'Password must contain at least one symbol.';
          } elseif ($password !== $password_confirm) {
            $error = 'Passwords do not match.';
          } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $upd = $conn->prepare('UPDATE arthurnerri_users SET password = ? WHERE id = ?');
            if ($upd) {
              $upd->bind_param('si', $hashed, $user_id);
              $upd->execute();
              $upd->close();

              $del = $conn->prepare('DELETE FROM password_resets WHERE id = ?');
              if ($del) {
                $del->bind_param('i', $pr_id);
                $del->execute();
                $del->close();
              }

              $success = 'Your password has been reset successfully. You may now <a href="index.php">login</a>.';
            } else {
              $error = 'Unable to update password. Please try again later.';
            }
          }
                }
            }
        } else {
            $error = 'Invalid token.';
        }

        $stmt->close();
    } else {
        $error = 'Database error.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Reset Password - MDRRMO Learning Library</title>
  <style>
      :root {
        --accent:#0b6cf5;
        --danger:#dc2626;
        --muted:#6b7280;
        --bg:#f7f9fc;
        --card:#ffffff;
        --maxw:1100px;
        font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial
      }
      *{box-sizing:border-box}
      body{margin:0;background:var(--bg);color:#0b1220;-webkit-font-smoothing:antialiased}
      .container{max-width:var(--maxw);margin:0 auto;padding:20px}

      header{
        background:linear-gradient(90deg, rgba(116,3,3,1), transparent);
        position:sticky;top:0;z-index:50;backdrop-filter:blur(6px)
      }
      .topbar{
        display:flex;align-items:center;justify-content:space-between;
        gap:12px;padding:12px 0;flex-wrap:wrap
      }
      .logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;flex-wrap:wrap}
      .logo img{height:32px;width:auto;border-radius:4px}
      .card{
        background:var(--card);
        padding:20px;
        border-radius:12px;
        box-shadow:0 6px 20px rgba(17,24,39,.06);
        max-width:480px;
        margin:20px auto
      }
      h2{margin-top:0;font-size:1.4rem}
      label{display:block;margin-top:14px;font-weight:600;font-size:.95rem}
      input{
        width:100%;padding:10px;margin-top:6px;
        border:1px solid #ddd;border-radius:6px;font-size:1rem
      }
      button{
        margin-top:16px;padding:12px 16px;width:100%;
        background:var(--accent);color:#fff;border:none;border-radius:8px;
        font-weight:600;cursor:pointer;transition:.2s;font-size:1rem
      }
      button:hover{background:#0751c3}
      .error,.success{
        padding:12px;border-radius:6px;margin-bottom:14px;font-size:.95rem
      }
      .error{background:#fee2e2;border:1px solid #f3c2c6;color:#6a1414}
      .success{background:#e6ffed;border:1px solid #b7f1c9;color:#154b20}
      footer{padding:20px 0;color:var(--muted);font-size:.85rem;text-align:center}

      /* MOBILE STYLES */
      @media(max-width:600px){
        .container{padding:16px}
        .card{margin:16px auto;padding:18px}
        h2{font-size:1.2rem}
        button{padding:12px;font-size:.95rem}
      }
  </style>
</head>
<body>
  <!-- HEADER -->
  <header>
    <div class="container topbar">
      <a href="http://library.arthurnerri.tech" class="logo">
        <img src="img/logo.png" alt="Site Logo">
        <div class="text">
          <div style="font-weight:800; color: rgba(255,170,121,1); font-size:1rem">MDRRMO LEARNING LIBRARY</div>
          <div style="font-size:0.8rem;color:white">Learning Materials</div>
        </div>
      </a>
    </div>
  </header>

  <!-- MAIN CONTENT -->
  <main class="container">
    <div class="card">
      <h2>Reset Password</h2>

      <?php if ($error): ?>
          <div class="error"><?php echo $error; ?></div>
      <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?php echo $success; ?></div>
    <?php elseif (!$error || ($_SERVER['REQUEST_METHOD'] === 'POST')): ?>
          <div class="error" id="pw-requirements" style="background:#f7f9fc;color:#333;border:1px solid #ddd;margin-bottom:10px;">
      <strong>Password requirements:</strong><br>
      - Must be at least 8 characters long<br>
      - Must contain at least one uppercase letter<br>
      - Must contain at least one symbol (e.g. !@#$%^&*)
      </div>
      <form method="post" action="forgot_password_reset.php" id="pw-reset-form">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" required>
              
        <label for="password_confirm">Confirm new password</label>
        <input type="password" id="password_confirm" name="password_confirm" required>
              
        <button type="submit">Reset password</button>
      </form>
      <?php if (!empty($post_attempted) && $error): ?>
        <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
          <button type="button" id="retry-btn" style="background:#fff;color:#0b1220;border:1px solid #ddd;padding:10px 14px;border-radius:8px;cursor:pointer">Retry</button>
          <a href="forgot_password_reset.php?token=<?php echo urlencode($token); ?>" style="padding:10px 14px;background:transparent;border-radius:8px;border:1px solid #ddd;text-decoration:none;color:inherit">Reload</a>
        </div>
      <?php endif; ?>

      <script>
        (function(){
          var retry = document.getElementById('retry-btn');
          var form = document.getElementById('pw-reset-form');
          if(retry && form){
            retry.addEventListener('click', function(){
              // Clear password fields and remove any visible error messages
              document.getElementById('password').value = '';
              document.getElementById('password_confirm').value = '';
              var err = document.querySelector('.error');
              if(err){ err.style.display = 'none'; }
              var req = document.getElementById('pw-requirements');
              if(req){ req.style.display = 'block'; }
              document.getElementById('password').focus();
            });
          }
        })();
      </script>
    <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <footer>
      © <?= date('Y') ?> MDRRMO Learning Library. All rights reserved.
    </footer>
  </main>
</body>
</html>
