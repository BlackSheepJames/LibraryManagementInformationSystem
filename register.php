<?php
require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/config.php';

if (empty($_SESSION['register_csrf'])) {
    $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
}

$error_message = '';
$success_message = '';
$username = '';
$email = '';

if (!empty($_SESSION['registration_success'])) {
    $success_message = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($postedToken) || !hash_equals($_SESSION['register_csrf'], $postedToken)) {
        $error_message = 'Your session expired. Please submit the form again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
            $error_message = 'Please complete all fields.';
        } elseif (strlen($username) < 3 || strlen($username) > 50 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            $error_message = 'Username must be 3–50 characters and use only letters, numbers, dots, underscores, or hyphens.';
        } elseif (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Enter a valid email address (up to 100 characters).';
        } elseif (strlen($password) < 8) {
            $error_message = 'Password must be at least 8 characters long.';
        } elseif (!hash_equals($password, $confirmPassword)) {
            $error_message = 'The passwords do not match.';
        } else {
            $check = $conn->prepare('SELECT id FROM arthurnerri_users WHERE username = ? OR email = ? LIMIT 1');
            if (!$check) {
                $error_message = 'Registration is temporarily unavailable. Please try again later.';
            } else {
                $check->bind_param('ss', $username, $email);
                $check->execute();
                $existing = $check->get_result();
                if ($existing && $existing->num_rows > 0) {
                    $error_message = 'That username or email is already registered.';
                }
                $check->close();

                if ($error_message === '') {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    // Public self-registration always creates a regular community account.
                    $role = 'residents';
                    $insert = $conn->prepare('INSERT INTO arthurnerri_users (username, email, password, role) VALUES (?, ?, ?, ?)');
                    if (!$insert) {
                        $error_message = 'Registration is temporarily unavailable. Please try again later.';
                    } else {
                        $insert->bind_param('ssss', $username, $email, $passwordHash, $role);
                        if ($insert->execute()) {
                            $audit = $conn->prepare("INSERT INTO audit_logs (username, role, action) VALUES (?, 'residents', 'User registered')");
                            if ($audit) { $audit->bind_param('s', $username); $audit->execute(); $audit->close(); }
                            $_SESSION['registration_success'] = 'Your account has been created. You can now sign in.';
                            $_SESSION['register_csrf'] = bin2hex(random_bytes(32));
                            $insert->close();
                            header('Location: register.php');
                            exit();
                        }
                        // Unique indexes also protect against two simultaneous registrations.
                        $error_message = ($insert->errno === 1062)
                            ? 'That username or email is already registered.'
                            : 'We could not create your account. Please try again.';
                        $insert->close();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MDRRMO Learning Library</title>
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
  
    /* Keep the login design while allowing the longer registration form to scroll on small screens. */
    body { min-height: 100vh; height: auto; overflow-y: auto; padding: 72px 16px 24px; }
    .login-container { margin: auto; }
    .register-link { color: #fff; }
    .register-link a { color: #7db5ff; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
    .success-message { background: rgba(16, 185, 129, 0.15); color: #bbf7d0; border: 1px solid rgba(16, 185, 129, 0.45); padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    .error-message { color: #ffbaba; }
    @media (max-height: 700px) { body { align-items: flex-start; } }/* Compact registration form: two fields per row on desktop. */
body { min-height:100dvh; height:auto; overflow-y:auto; padding:16px; }
.login-container { width:min(820px,calc(100vw - 32px)); max-width:820px; max-height:calc(100dvh - 32px); overflow-y:auto; margin:auto; padding:24px; }
.login-container form { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px 16px; }
.login-container .form-group { margin:0; min-width:0; }
.login-container form > button,.login-container form > .login-btn,.login-container form > .register-link { grid-column:1 / -1; }
.home-btn { position:fixed; top:12px; left:12px; z-index:5; padding:8px 12px; }
@media(max-width:620px) { body { padding:56px 12px 12px; } .login-container { width:100%; max-height:none; padding:20px; } .login-container form { grid-template-columns:1fr; } }
</style>
</head>
<body>
    <a href="index.php" class="home-btn">← Back to Login</a>
    <main class="login-container">
        <div class="logo">
            <img src="img/logo.png" alt="MDRRMO Logo" class="logo-img">
            <div class="logo-text">
                <h1>MDRRMO</h1>
                <p>Learning Library</p>
            </div>
        </div>

        <a href="https://library.arthurnerri.tech/bulletin/index.php" class="ann-btn">📢 ANNOUNCEMENTS</a>
        <h2>Create Account</h2>

        <?php if ($success_message !== ''): ?>
            <div class="success-message" role="status"><?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error_message !== ''): ?>
            <div class="error-message" role="alert"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['register_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_.-]+" autocomplete="username" placeholder="Choose a username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required maxlength="100" autocomplete="email" placeholder="Enter your email address" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Create a password (at least 8 characters)">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password" placeholder="Enter your password again">
            </div>
            <button type="submit" class="login-btn">Create Account</button>
            <p class="register-link">Already have an account? <a href="index.php">Sign in</a></p>
        </form>
    </main>
</body>
</html>
