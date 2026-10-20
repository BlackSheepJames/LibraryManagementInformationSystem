<?php
ob_start();
require_once __DIR__ . '/session_bootstrap.php';
include('config.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');

    if ($username === '') {
        $message = 'Please enter your username.';
    } else {
        // Find user by username
        $stmt = $conn->prepare('SELECT id, email, username FROM arthurnerri_users WHERE username = ?');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Generate a secure token
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

                // Insert into password_resets
                $ins = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
                if ($ins) {
                    $ins->bind_param('iss', $user['id'], $token, $expires_at);
                    $ins->execute();
                    $ins->close();

                    // Mask email for display
                    $email = $user['email'];
                    $parts = explode('@', $email);
                    $local = $parts[0];
                    $masked_local = substr($local, 0, 1) . str_repeat('*', max(0, strlen($local) - 2)) . (strlen($local) > 1 ? substr($local, -1) : '');
                    $masked_email = $masked_local . '@' . ($parts[1] ?? '');

                    // Build reset link
                    $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') .
                        '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) .
                        '/forgot_password_reset.php?token=' . $token;

                    // Try to send email
                    $subject = 'Password reset request';
                    $body = "Hello {$user['username']},\n\nWe received a request to reset your password. Use the link below to reset it (expires in 1 hour):\n\n{$reset_link}\n\nIf you didn't request this, ignore this message.";

                    $sent = false;
                    if (!empty($user['email'])) {
                        try {
                            require_once __DIR__ . '/email/PHPMailer-master/src/Exception.php';
                            require_once __DIR__ . '/email/PHPMailer-master/src/PHPMailer.php';
                            require_once __DIR__ . '/email/PHPMailer-master/src/SMTP.php';
                            require_once __DIR__ . '/email/config.php';

                            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = SMTP_HOST;
                            $mail->SMTPAuth = true;
                            $mail->Username = SMTP_USER;
                            $mail->Password = SMTP_PASS;
                            if (!empty(SMTP_SECURE)) {
                                $mail->SMTPSecure = SMTP_SECURE;
                            }
                            $mail->Port = SMTP_PORT;

                            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                            $mail->addAddress($user['email'], $user['username']);

                            $mail->isHTML(false);
                            $mail->Subject = $subject;
                            $mail->Body = $body;

                            $mail->send();
                            $sent = true;
                        } catch (Exception $e) {
                            $headers = 'From: no-reply@' . $_SERVER['HTTP_HOST'] . "\r\n" .
                                       'Reply-To: no-reply@' . $_SERVER['HTTP_HOST'] . "\r\n" .
                                       'X-Mailer: PHP/' . phpversion();
                            $sent = @mail($user['email'], $subject, $body, $headers);
                        }
                    }

                    $message = 'Please check your email to change your password.';

                } else {
                    $message = 'Unable to create password reset request. Please try again later.';
                }
            } else {
                $message = 'Are you sure this is your username? If not, send an email here: <a href="email/index.php">Contact us</a>';
            }

            $stmt->close();
        } else {
            $message = 'Database error.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Forgot Password - MDRRMO Learning Library</title>
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
      .container{max-width:var(--maxw);margin:0 auto;padding:28px}

      header{
        background:linear-gradient(90deg, rgba(116,3,3,1), transparent);
        position:sticky;top:0;z-index:50;backdrop-filter:blur(6px)
      }
      .topbar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0}
      .logo{display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit}
      .logo img{height:35px;width:auto;border-radius:4px}

      .card{
        background:var(--card);
        padding:24px;
        border-radius:12px;
        box-shadow:0 6px 20px rgba(17,24,39,.06);
        max-width:480px;
        margin:34px auto
      }
      h2{margin-top:0}
      label{display:block;margin-top:14px;font-weight:600}
      input[type=text]{
        width:100%;padding:10px;margin-top:6px;
        border:1px solid #ddd;border-radius:6px;font-size:.95rem
      }
      button{
        margin-top:16px;padding:12px 16px;
        background:var(--accent);color:#fff;border:none;border-radius:8px;
        font-weight:600;cursor:pointer;transition:.2s
      }
      button:hover{background:#0751c3}
      .message{padding:10px;margin-top:14px;border-radius:6px;font-size:.9rem;background:#f1f5f9}
      .hint{color:var(--muted);font-size:.9rem}

      footer{padding:28px 0;color:var(--muted);font-size:.9rem}
      .small{font-size:.85rem}
  </style>
</head>
<body>
  <!-- HEADER -->
  <header>
    <div class="container topbar">
      <a href="http://library.arthurnerri.tech" class="logo">
        <img src="img/logo.png" alt="Site Logo">
        <div class="text">
          <div style="font-weight:800; color: rgba(255,170,121,1)">MDRRMO LEARNING LIBRARY</div>
          <div style="font-size:0.8rem;color:white">Learning Materials</div>
        </div>
      </a>
    </div>
  </header>

  <!-- MAIN CONTENT -->
  <main class="container">
    <div class="card">
      <h2>Forgot Password</h2>
      <p class="hint">Enter your username. If it exists, we'll send a reset link to the registered email.</p>

      <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
      <?php endif; ?>

      <form method="post" action="forgot_password_request.php">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>
        <button type="submit">Send reset link</button>
      </form>

      <p style="margin-top:12px;"><a href="index.php">Back to login</a></p>
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
      </div>
    </footer>
  </main>
</body>
</html>
