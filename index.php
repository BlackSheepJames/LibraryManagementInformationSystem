<?php
ob_start(); // Start output buffering
require_once __DIR__ . '/session_bootstrap.php';
include('config.php');

// Avoid redirect issues by disabling output buffering
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($usernameOrEmail) && !empty($password)) {
        // Secure query using prepared statements
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM arthurnerri_users WHERE username = ? OR email = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);

                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['id'] = $user['id'];

                    // Insert audit log securely
                    $logStmt = $conn->prepare("INSERT INTO audit_logs (username, role, action) VALUES (?, ?, 'User logged in')");
                    if ($logStmt) {
                        $logStmt->bind_param("ss", $user['username'], $user['role']);
                        $logStmt->execute();
                        $logStmt->close();
                    }

                    // Redirect based on role
                    switch ($user['role']) {
                        case 'systemAdmin':
                            header('Location: ' . $systemAdminDashboardUrl);
                            break;
                        case 'brgyOfficer':
                            header("Location: brgy_dashboard.php");
                            break;
                        case 'mdrrmOfficer':
                            header("Location: admin_dashboard.php");
                            break;
                        case 'residents':
                            header("Location: community_dashboard.php");
                            break;
                        default:
                            header("Location: index.php");
                            break;
                    }
                    exit();
                }
            }
            $stmt->close();
        }
    }

    $_SESSION['error'] = "Invalid username/email or password!";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MDRRMO Learning Library</title>
  <style>
    :root { --accent:#0b6cf5; --muted:#6b7280; --bg:#f7f9fc; --card:#ffffff; --error:#dc2626; font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body { 
        font-family: Segoe UI, Arial, Helvetica, sans-serif; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        height: 100vh; 
        overflow: hidden; 
        background: url('img/bg1.jpg') no-repeat center center fixed; 
        background-size: cover;
        animation: fadeInBg 2s ease-in-out; 
    }

    /* Floating subtle background animation */
    @keyframes fadeInBg {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .login-container { 
        background: rgba(0, 0, 0, 0.6); 
        backdrop-filter: blur(8px); 
        padding: 40px; 
        border-radius: 12px; 
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4); 
        width: 100%; 
        max-width: 420px; 
        color: #fff; 
        transform: translateY(30px); 
        opacity: 0; 
        animation: slideUp 0.8s ease-out forwards; 
    }

    @keyframes slideUp {
        to { transform: translateY(0); opacity: 1; }
    }

    .logo { 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        gap: 12px; 
        margin-bottom: 32px; 
        animation: bounce 2s infinite; 
    }

    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-10px); }
        60% { transform: translateY(-5px); }
    }

    .logo-img { width: 50px; height: 50px; object-fit: contain; border-radius: 8px; }
    .logo-text h1 { margin: 0; font-size: 1.5rem; font-weight: 800; color: #fff; text-align: center; }
    .logo-text p { color: #fff; text-align: center; }

    h2 { font-size: 1.75rem; font-weight: 700; margin-bottom: 20px; color: #fff; text-align: center; }

    .form-group { margin-bottom: 20px; transition: transform 0.3s ease; }
    .form-group input { 
        width: 100%; padding: 12px 16px; 
        border: 1px solid #e5e7eb; 
        border-radius: 8px; font-size: 16px; 
        transition: all 0.3s ease; 
    }
    .form-group input:focus { 
        outline: none; 
        border-color: var(--accent); 
        box-shadow: 0 0 0 3px rgba(11, 108, 245, 0.2); 
        transform: scale(1.02); 
    }
.ann-btn {
  display: inline-block;
  padding: 10px 24px;
  background: linear-gradient(135deg, #0b6cf5, #00b4d8);
  color: #fff;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 700;
  font-size: 15px;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 15px rgba(11, 108, 245, 0.4);
  position: relative;
  overflow: hidden;
  transition: all 0.35s ease;
  backdrop-filter: blur(6px);
  border: 1px solid rgba(255,255,255,0.2);
  text-align: center;
  margin: 0 auto 25px auto;  /* Centers the button and adds spacing below */
  display: block;            /* Converts it into a block-level element for centering */
  max-width: 220px;          /* Keeps it neatly sized */
}

/* Subtle inner shine animation */
.ann-btn::before {
  content: "";
  position: absolute;
  top: 0; left: -75%;
  width: 50%; height: 100%;
  background: linear-gradient(120deg, rgba(255,255,255,0.25), rgba(255,255,255,0));
  transform: skewX(-25deg);
  transition: left 0.6s ease;
}

/* Hover effects */
.ann-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(11, 108, 245, 0.5);
  background: linear-gradient(135deg, #007bff, #00d4ff);
}
.ann-btn:hover::before {
  left: 125%;
}

/* Optional click feedback */
.ann-btn:active {
  transform: scale(0.97);
  box-shadow: 0 4px 10px rgba(11, 108, 245, 0.3);
}

    .login-btn { 
        width: 100%; padding: 12px; 
        background: linear-gradient(90deg, rgba(226,29,29,1), rgba(255,59,59,1)); 
        border: none; 
        color: white; font-size: 16px; font-weight: 700; 
        border-radius: 8px; cursor: pointer; 
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s; 
        margin-bottom: 16px; 
    }
    .login-btn:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 6px 20px rgba(226, 29, 29, 0.3); 
    }
    .login-btn::before {
        content: "";
        position: absolute;
        top: 0; left: -100%;
        width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
    }
    .login-btn:hover::before { left: 100%; }

    .register-link { text-align: center; font-size: 14px; color: var(--muted); }
    .register-link a { color: var(--accent); text-decoration: none; font-weight: 600; transition: color 0.2s; }
    .register-link a:hover { color: #0952c9; text-decoration: underline; }

    .error-message { background: rgba(255, 0, 0, 0.1); color: #ffbaba; border: 1px solid rgba(255, 0, 0, 0.4); padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; animation: shake 0.3s; }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .home-btn { 
        position: absolute; top: 20px; left: 20px; 
        background: rgba(11, 108, 245, 0.9); 
        color: #fff; padding: 10px 18px; 
        border-radius: 8px; font-weight: 600; 
        text-decoration: none; font-size: 14px; 
        transition: background 0.3s, transform 0.2s; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.2); 
        animation: fadeIn 1s ease-in;
    }
    .home-btn:hover { background: rgba(9, 82, 201, 1); transform: translateY(-2px); }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 480px) { 
        .login-container { padding: 24px; margin: 16px; } 
        h2 { font-size: 1.5rem; } 
    }
  /* Compact two-column login layout */
body { min-height:100dvh; height:auto; overflow-y:auto; padding:16px; }
.login-container, .container { width:min(820px,calc(100vw - 32px)); max-width:820px; margin:auto; }
.login-container form, .container form { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px 16px; }
.login-container .form-group, .container .form-group { margin:0; }
.login-container form > .login-btn, .login-container form > button, .login-container form > .register-link, .login-container form > .email, .login-container form > .error-message, .container form > .login-btn, .container form > button, .container form > p { grid-column:1 / -1; }
.login-container form > div[style], .container form > div[style] { grid-column:1 / -1; }
.back-button { display:inline-flex; align-items:center; gap:8px; margin:0 0 14px; padding:9px 14px; border-radius:8px; color:#fff; background:rgba(24,42,143,.92); text-decoration:none; font-weight:600; }
.back-button:hover { filter:brightness(1.12); }
@media(max-width:620px) { .login-container,.container { width:min(100%,520px); padding:24px 20px; } .login-container form,.container form { grid-template-columns:1fr; } }
</style>
</head>
<body>
    <a href="/main/" class="home-btn">← Back to Home</a>
    <div class="login-container">
      <div class="logo">
        <img src="img/logo.png" alt="MDRRMO Logo" class="logo-img">
        <div class="logo-text">
          <h1>MDRRMO</h1>
          <p>Learning Library</p>
        </div>
      </div>

<a href="https://library.arthurnerri.tech/bulletin/index.php" class="ann-btn">📢 ANNOUNCEMENTS</a>
      
      <?php if (isset($error_message)): ?>
        <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>

      <form action="index.php" method="POST">
        <?php if (isset($_SESSION['error'])): ?>
          <div style="color: black; font-weight: bold; text-align: center;">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>
        <div class="form-group">
          <label for="username">Username or Email</label>
          <input type="text" id="username" name="username" required placeholder="Enter your username or email">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required placeholder="Enter your password">
        </div>
        <button type="submit" class="login-btn">Login</button>
        <p class="register-link">Forgot your password? <a href="forgot_password_request.php">Reset it here</a></p>
        <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
      </form>
    </div>
</body>
</html>
