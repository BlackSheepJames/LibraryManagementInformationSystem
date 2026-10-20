<?php
// Temporary debug toggle: append ?debug=1 to the URL on cPanel to see full errors.
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once __DIR__ . '/auth.php';
// Validate auth helper exists before calling
if (!function_exists('require_role')) {
    http_response_code(500);
    echo "<h2>Authentication helper missing</h2>";
    echo "<p>The file <code>auth.php</code> should define <code>require_role()</code>. Make sure it's present in the same folder.</p>";
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo "<pre>auth.php path: " . htmlspecialchars(__DIR__ . '/auth.php') . "</pre>";
    }
    exit;
}

// Restrict to normal user role
require_role('residents');

include('config.php');

// Verify DB connection
if (!isset($conn) || $conn === null) {
    http_response_code(500);
    echo "<h2>Database connection not configured</h2>";
    echo "<p>Open <code>config.php</code> and set the correct MySQL credentials for your cPanel database (host, user, password, dbname).</p>";
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo "<pre>\$conn is not set or is null. Check config.php</pre>";
    }
    exit;
}
if (isset($conn->connect_error) && $conn->connect_error) {
    http_response_code(500);
    echo "<h2>Database connection error</h2><pre>" . htmlspecialchars($conn->connect_error) . "</pre>";
    exit;
}

// Example query (keep your original game logic here)
$games = null;
if (isset($conn) && $conn !== null) {
    try {
        // Some environments enable MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT which causes ->query to throw
        $games = $conn->query("SELECT * FROM community_games ORDER BY created_at DESC");
    } catch (\mysqli_sql_exception $e) {
        // Query exception — display in debug mode, otherwise log and continue
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            http_response_code(500);
            echo "<h2>Database query error</h2>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            echo "<p>If the table <code>community_games</code> doesn't exist, import it into your cPanel MySQL database.</p>";
            exit;
        } else {
            error_log('community_games.php: DB query exception: ' . $e->getMessage());
            $games = null; // fall back to empty
        }
    }
    // If query didn't throw but returned false, handle that too
    if ($games === false) {
        if (isset($_GET['debug']) && $_GET['debug'] === '1') {
            http_response_code(500);
            echo "<h2>Database query error</h2>";
            echo "<pre>" . htmlspecialchars($conn->error) . "</pre>";
            echo "<p>If the table <code>community_games</code> doesn't exist, import it into your cPanel MySQL database.</p>";
            exit;
        } else {
            error_log('community_games.php: DB query failed: ' . ($conn->error ?? 'unknown error'));
            $games = null;
        }
    }
} else {
    // No DB connection — log and continue so page can still render for debugging/UI purposes.
    error_log('community_games.php: $conn is not available. Check config.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disaster Heroes - Learning Game</title>
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
      footer, main {opacity:0;}
      .animate {animation:fadeIn 1s ease forwards;}

      footer{padding:28px 0;color:var(--muted);font-size:.9rem}
      .small{font-size:.85rem}
      .card{background:var(--card);padding:18px;border-radius:12px;box-shadow:0 6px 20px rgba(17,24,39,.06);margin-bottom:20px;}

        .game-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            animation: slideDown 0.8s ease-out;
        }

        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .title {
            font-size: 48px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        .subtitle {
            font-size: 20px;
            color: #4a5568;
            margin-bottom: 20px;
        }

        .score-board {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .score-item {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            min-width: 120px;
            transition: transform 0.3s ease;
        }

        .score-item:hover {
            transform: translateY(-5px);
        }

        .score-number {
            font-size: 24px;
            font-weight: bold;
            color: #e53e3e;
        }

        .score-label {
            font-size: 14px;
            color: #718096;
            margin-top: 5px;
        }

        .game-area {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .disaster-scenario {
            text-align: center;
            margin-bottom: 30px;
        }

        .scenario-icon {
            font-size: 80px;
            margin-bottom: 15px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-15px); }
            60% { transform: translateY(-7px); }
        }

        .scenario-title {
            font-size: 28px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .scenario-description {
            font-size: 18px;
            color: #4a5568;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto;
        }

        .choices-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .choice-button {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .choice-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(66, 153, 225, 0.4);
        }

        .choice-button.correct {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            animation: correctPulse 0.6s ease-out;
        }

        .choice-button.incorrect {
            background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
            animation: shake 0.6s ease-out;
        }

        @keyframes correctPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .choice-icon {
            font-size: 24px;
            margin-right: 10px;
        }

        .progress-bar {
            background: #e2e8f0;
            border-radius: 10px;
            height: 20px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, #48bb78, #38a169);
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
            width: 0%;
        }

        .feedback-area {
            margin-top: 30px;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        .feedback-area.show {
            opacity: 1;
            transform: translateY(0);
        }

        .feedback-area.correct {
            background: #f0fff4;
            border: 2px solid #48bb78;
            color: #22543d;
        }

        .feedback-area.incorrect {
            background: #fed7d7;
            border: 2px solid #f56565;
            color: #742a2a;
        }

        .next-button {
            background: linear-gradient(135deg, #805ad5 0%, #6b46c1 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .next-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(128, 90, 213, 0.4);
        }

        .disaster-types {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .disaster-type {
            background: white;
            border-radius: 15px;
            padding: 15px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 120px;
        }

        .disaster-type:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .disaster-type.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .type-icon {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .type-name {
            font-size: 14px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .title {
                font-size: 36px;
            }
            
            .choices-container {
                grid-template-columns: 1fr;
            }
            
            .score-board {
                gap: 15px;
            }
            
            .game-area {
                padding: 20px;
            }
        }
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
            <li><a href="community_watch.php">Videos</a></li>
            <li><a href="community_infograph.php">Infographics</a></li>
            <li><a href="community_games.php" class="cta">Games</a></li>
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
    <div class="game-container">
        <div class="header">
            <h1 class="title">🦸‍♀️ Disaster Heroes 🦸‍♂️</h1>
            <p class="subtitle">Learn how to stay safe during emergencies!</p>
        </div>

        <div class="score-board">
            <div class="score-item">
                <div class="score-number" id="score">0</div>
                <div class="score-label">Score</div>
            </div>
            <div class="score-item">
                <div class="score-number" id="level">1</div>
                <div class="score-label">Level</div>
            </div>
            <div class="score-item">
                <div class="score-number" id="streak">0</div>
                <div class="score-label">Streak</div>
            </div>
        </div>

        <div class="disaster-types">
            <div class="disaster-type active" data-type="earthquake">
                <div class="type-icon">🌍</div>
                <div class="type-name">Earthquake</div>
            </div>
            <div class="disaster-type" data-type="fire">
                <div class="type-icon">🔥</div>
                <div class="type-name">Fire</div>
            </div>
            <div class="disaster-type" data-type="flood">
                <div class="type-icon">🌊</div>
                <div class="type-name">Flood</div>
            </div>
            <div class="disaster-type" data-type="storm">
                <div class="type-icon">⛈️</div>
                <div class="type-name">Storm</div>
            </div>
            <div class="disaster-type" data-type="general">
                <div class="type-icon">🚨</div>
                <div class="type-name">General</div>
            </div>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>

        <div class="game-area">
            <div class="disaster-scenario">
                <div class="scenario-icon" id="scenarioIcon">🌍</div>
                <h2 class="scenario-title" id="scenarioTitle">Earthquake Alert!</h2>
                <p class="scenario-description" id="scenarioDescription">
                    You feel the ground starting to shake while you're in your classroom. 
                    The earthquake alarm is sounding. What should you do first?
                </p>
            </div>

            <div class="choices-container" id="choicesContainer">
                <button class="choice-button" data-correct="false">
                    <span class="choice-icon">🏃‍♂️</span>
                    Run outside as fast as possible
                </button>
                <button class="choice-button" data-correct="true">
                    <span class="choice-icon">🛡️</span>
                    Drop, cover, and hold on under your desk
                </button>
                <button class="choice-button" data-correct="false">
                    <span class="choice-icon">🚪</span>
                    Stand in the doorway
                </button>
            </div>

            <div class="feedback-area" id="feedbackArea">
                <h3 id="feedbackTitle"></h3>
                <p id="feedbackText"></p>
                <button class="next-button" id="nextButton">Next Challenge</button>
            </div>
        </div>
    </div>
<!-- FOOTER -->
  <footer class="animate" style="margin-top:34px;">
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
        
// Game logic
        const scenarios = {
            earthquake: [
                {
                    icon: "🌍",
                    title: "Earthquake Alert!",
                    description: "You feel the ground starting to shake while you're in your classroom. The earthquake alarm is sounding. What should you do first?",
                    choices: [
                        { text: "🏃‍♂️ Run outside as fast as possible", correct: false },
                        { text: "🛡️ Drop, cover, and hold on under your desk", correct: true },
                        { text: "🚪 Stand in the doorway", correct: false }
                    ],
                    correctFeedback: "Excellent! Drop, Cover, and Hold On is the safest action during an earthquake. Stay under your desk until the shaking stops!",
                    incorrectFeedback: "Not quite right. During an earthquake, you should Drop, Cover, and Hold On under a sturdy desk or table to protect yourself from falling objects."
                },
                {
                    icon: "🏠",
                    title: "After the Earthquake",
                    description: "The shaking has stopped. You're safe under your desk. What should you do next?",
                    choices: [
                        { text: "🏃‍♂️ Run outside immediately", correct: false },
                        { text: "👂 Listen for instructions from your teacher", correct: true },
                        { text: "🧹 Start cleaning up fallen items", correct: false }
                    ],
                    correctFeedback: "Perfect! Always listen to your teacher or emergency responders for safe evacuation instructions after an earthquake.",
                    incorrectFeedback: "Remember to stay calm and listen for instructions from adults. They will guide you to safety when it's appropriate to move."
                },
                {
                    icon: "🎒",
                    title: "Earthquake Kit",
                    description: "Your family is preparing an earthquake emergency kit. Which item is most important to include?",
                    choices: [
                        { text: "🎮 Video games for entertainment", correct: false },
                        { text: "💧 Water bottles (1 gallon per person per day)", correct: true },
                        { text: "📺 Portable TV", correct: false }
                    ],
                    correctFeedback: "Great choice! Water is essential for survival. You need at least 1 gallon per person per day for drinking and sanitation.",
                    incorrectFeedback: "Water is the most critical item in any emergency kit. You can survive longer without food than without water!"
                },
                {
                    icon: "🏃‍♀️",
                    title: "Earthquake Evacuation",
                    description: "You're evacuating after an earthquake. What should you watch out for?",
                    choices: [
                        { text: "🪟 Broken glass and debris", correct: true },
                        { text: "📱 Taking photos for social media", correct: false },
                        { text: "🎒 Collecting your belongings", correct: false }
                    ],
                    correctFeedback: "Smart! After an earthquake, watch for broken glass, fallen objects, and structural damage. Safety first!",
                    incorrectFeedback: "Focus on safety hazards like broken glass and debris. Belongings can be replaced, but your safety cannot!"
                },
                {
                    icon: "🏢",
                    title: "Earthquake at Home",
                    description: "You're at home when an earthquake starts. Where is the safest place to take cover?",
                    choices: [
                        { text: "🚪 In a doorway", correct: false },
                        { text: "🛏️ Under a sturdy table or desk", correct: true },
                        { text: "🪟 Near a window", correct: false }
                    ],
                    correctFeedback: "Correct! Get under a sturdy table or desk and hold on. This protects you from falling objects.",
                    incorrectFeedback: "Doorways and windows are not safe during earthquakes. Find a sturdy table or desk to get under!"
                },
                {
                    icon: "🚗",
                    title: "Earthquake While Driving",
                    description: "You're in a car with your family when an earthquake happens. What should the driver do?",
                    choices: [
                        { text: "🚗 Pull over safely and stop", correct: true },
                        { text: "🏃‍♂️ Get out and run", correct: false },
                        { text: "⚡ Drive faster to get home", correct: false }
                    ],
                    correctFeedback: "Perfect! Pull over away from bridges, overpasses, and power lines. Stay in the car until shaking stops.",
                    incorrectFeedback: "The safest place is inside the car. Pull over safely and wait for the shaking to stop before continuing."
                },
                {
                    icon: "🌊",
                    title: "Earthquake and Tsunami",
                    description: "You feel a strong earthquake near the ocean. What should you do immediately?",
                    choices: [
                        { text: "🏔️ Move to higher ground immediately", correct: true },
                        { text: "🌊 Go to the beach to see the waves", correct: false },
                        { text: "🏠 Stay where you are", correct: false }
                    ],
                    correctFeedback: "Excellent! Strong earthquakes near the ocean can cause tsunamis. Get to high ground fast!",
                    incorrectFeedback: "Earthquakes near water can trigger tsunamis. Always move to higher ground immediately after coastal earthquakes!"
                },
                {
                    icon: "🔌",
                    title: "After Earthquake Safety",
                    description: "After an earthquake, you smell gas in your house. What should you do first?",
                    choices: [
                        { text: "💡 Turn on lights to see better", correct: false },
                        { text: "🚪 Leave the house immediately", correct: true },
                        { text: "🪟 Open all windows", correct: false }
                    ],
                    correctFeedback: "Right! Gas leaks are dangerous. Leave immediately and call for help from a safe distance.",
                    incorrectFeedback: "Never use lights or electrical switches if you smell gas! Leave immediately and get help."
                },
                {
                    icon: "📞",
                    title: "Earthquake Communication",
                    description: "After an earthquake, phone lines are busy. What's the best way to let family know you're safe?",
                    choices: [
                        { text: "📞 Keep calling until you get through", correct: false },
                        { text: "📱 Send a text message", correct: true },
                        { text: "📧 Send an email", correct: false }
                    ],
                    correctFeedback: "Smart! Text messages often work when phone calls don't because they use less network capacity.",
                    incorrectFeedback: "Text messages are more likely to get through than calls during emergencies when networks are overloaded."
                },
                {
                    icon: "🏫",
                    title: "Earthquake Drill Practice",
                    description: "Your school practices earthquake drills. How long should you stay under your desk during the drill?",
                    choices: [
                        { text: "⏰ Until the teacher says it's safe", correct: true },
                        { text: "⏱️ Just 10 seconds", correct: false },
                        { text: "🏃‍♂️ Get up as soon as shaking stops", correct: false }
                    ],
                    correctFeedback: "Perfect! Always wait for the all-clear from your teacher or emergency responders before moving.",
                    incorrectFeedback: "Wait for instructions from adults. They know when it's truly safe to move and evacuate."
                }
            ],
            fire: [
                {
                    icon: "🔥",
                    title: "Fire Emergency!",
                    description: "You smell smoke and hear the fire alarm in your school. What's the first thing you should do?",
                    choices: [
                        { text: "🚪 Feel the door handle before opening", correct: true },
                        { text: "🏃‍♂️ Run to the nearest exit immediately", correct: false },
                        { text: "🎒 Gather your belongings first", correct: false }
                    ],
                    correctFeedback: "Great thinking! Always feel the door handle first. If it's hot, don't open it - find another way out!",
                    incorrectFeedback: "Safety first! Always check if the door handle is hot before opening it during a fire. If it's hot, the fire might be on the other side."
                },
                {
                    icon: "💨",
                    title: "Smoke in the Hallway",
                    description: "There's smoke in the hallway, but you need to evacuate. How should you move?",
                    choices: [
                        { text: "🚶‍♂️ Walk normally through the smoke", correct: false },
                        { text: "🐍 Crawl low under the smoke", correct: true },
                        { text: "🛑 Wait for the smoke to clear", correct: false }
                    ],
                    correctFeedback: "Excellent! Crawling low keeps you under the smoke where the air is cleaner and safer to breathe.",
                    incorrectFeedback: "Remember: smoke rises, so the cleanest air is near the floor. Always crawl low under smoke when evacuating."
                },
                {
                    icon: "🚨",
                    title: "Fire Safety at Home",
                    description: "Your family is practicing fire safety. How often should you test smoke detectors?",
                    choices: [
                        { text: "📅 Once a year", correct: false },
                        { text: "🗓️ Once a month", correct: true },
                        { text: "⏰ Only when they beep", correct: false }
                    ],
                    correctFeedback: "Perfect! Test smoke detectors monthly to make sure they're working properly. Change batteries twice a year!",
                    incorrectFeedback: "Smoke detectors should be tested monthly to ensure they work when you need them most. Don't wait for the low battery beep!"
                },
                {
                    icon: "🚪",
                    title: "Fire Escape Plan",
                    description: "Your family is making a fire escape plan. How many escape routes should each room have?",
                    choices: [
                        { text: "1️⃣ One escape route", correct: false },
                        { text: "2️⃣ Two escape routes", correct: true },
                        { text: "3️⃣ Three escape routes", correct: false }
                    ],
                    correctFeedback: "Correct! Every room should have two ways out in case one is blocked by fire or smoke.",
                    incorrectFeedback: "Always plan two escape routes from every room. If one is blocked, you'll have another way out!"
                },
                {
                    icon: "🏠",
                    title: "Meeting Place",
                    description: "Where should your family meet after escaping a house fire?",
                    choices: [
                        { text: "🚗 In the garage", correct: false },
                        { text: "🌳 At a safe spot outside, like a tree", correct: true },
                        { text: "🏠 On the front porch", correct: false }
                    ],
                    correctFeedback: "Perfect! Choose a safe meeting place outside, away from the house, where everyone can gather.",
                    incorrectFeedback: "Meet far from the house at a safe outdoor location. Garages and porches are too close to the fire!"
                },
                {
                    icon: "👕",
                    title: "Stop, Drop, and Roll",
                    description: "If your clothes catch fire, what should you do?",
                    choices: [
                        { text: "🏃‍♂️ Run to get help", correct: false },
                        { text: "🛑 Stop, drop, and roll", correct: true },
                        { text: "💧 Look for water", correct: false }
                    ],
                    correctFeedback: "Excellent! Stop, drop to the ground, and roll to put out the flames. Running makes fire worse!",
                    incorrectFeedback: "Never run if your clothes are on fire! Stop, drop, and roll to smother the flames."
                },
                {
                    icon: "🧯",
                    title: "Fire Extinguisher",
                    description: "You see a small kitchen fire. Should you try to put it out yourself?",
                    choices: [
                        { text: "🧯 Yes, if you know how to use an extinguisher", correct: false },
                        { text: "🚪 No, get out and call for help", correct: true },
                        { text: "💧 Yes, use water", correct: false }
                    ],
                    correctFeedback: "Smart choice! Kids should never fight fires. Get out safely and let adults handle it.",
                    incorrectFeedback: "Children should never try to fight fires, even small ones. Get out and let trained adults handle it!"
                },
                {
                    icon: "🔥",
                    title: "Kitchen Fire Safety",
                    description: "What should you never put on a grease fire?",
                    choices: [
                        { text: "💧 Water", correct: true },
                        { text: "🧯 Fire extinguisher", correct: false },
                        { text: "🍞 Baking soda", correct: false }
                    ],
                    correctFeedback: "Right! Water on grease fires makes them spread and can cause serious burns. Turn off heat and cover if safe.",
                    incorrectFeedback: "Never use water on grease fires! It causes the fire to spread and can cause severe burns."
                },
                {
                    icon: "🚨",
                    title: "Fire Alarm Sound",
                    description: "You hear the fire alarm at night. What should you do first?",
                    choices: [
                        { text: "🛏️ Check if it's a false alarm", correct: false },
                        { text: "🚪 Get out immediately", correct: true },
                        { text: "👨‍👩‍👧‍👦 Wake up your family first", correct: false }
                    ],
                    correctFeedback: "Perfect! Treat every alarm as real. Get out fast and wake others as you go if possible.",
                    incorrectFeedback: "Never assume it's false! Get out immediately - you can wake others as you escape if it's safe."
                },
                {
                    icon: "📞",
                    title: "After Escaping Fire",
                    description: "You've safely escaped a house fire. What should you do next?",
                    choices: [
                        { text: "🏠 Go back for pets or belongings", correct: false },
                        { text: "📞 Call 911 from outside", correct: true },
                        { text: "🚗 Drive to the fire station", correct: false }
                    ],
                    correctFeedback: "Excellent! Call 911 from a safe location outside. Never go back into a burning building!",
                    incorrectFeedback: "Never go back inside! Call 911 from outside and let firefighters handle rescues and belongings."
                }
            ],
            flood: [
                {
                    icon: "🌊",
                    title: "Flash Flood Warning!",
                    description: "Heavy rain has caused flooding in your area. You see water rising in the street. What should you do?",
                    choices: [
                        { text: "🏠 Go to the highest floor of your building", correct: true },
                        { text: "🚗 Try to drive through the water", correct: false },
                        { text: "🚶‍♂️ Walk through the shallow water", correct: false }
                    ],
                    correctFeedback: "Smart choice! Getting to higher ground is the safest option during a flood. Never try to walk or drive through flood water!",
                    incorrectFeedback: "Remember: Turn Around, Don't Drown! Even shallow flood water can be dangerous. Always seek higher ground instead."
                },
                {
                    icon: "🚗",
                    title: "Driving in Flood Water",
                    description: "You're in a car with your family and see a flooded road ahead. How much water can make a car float?",
                    choices: [
                        { text: "📏 2 feet of water", correct: false },
                        { text: "📐 12 inches of water", correct: true },
                        { text: "📊 3 feet of water", correct: false }
                    ],
                    correctFeedback: "Correct! Just 12 inches of moving water can carry away a vehicle. Turn around, don't drown!",
                    incorrectFeedback: "It takes less water than you think! Just 12 inches of moving flood water can float most cars. Always turn around!"
                },
                {
                    icon: "🏠",
                    title: "Flood Preparation",
                    description: "A flood warning has been issued for your area. What should your family do first?",
                    choices: [
                        { text: "📱 Take photos of your belongings", correct: false },
                        { text: "🎒 Pack an emergency kit and important documents", correct: true },
                        { text: "🧹 Start cleaning the basement", correct: false }
                    ],
                    correctFeedback: "Excellent! Pack emergency supplies, important documents, and be ready to evacuate quickly if needed.",
                    incorrectFeedback: "Focus on safety first! Pack emergency supplies and important papers. Photos can wait until you're safe."
                },
                {
                    icon: "⚡",
                    title: "Flood and Electricity",
                    description: "Flood water is entering your basement where the electrical panel is. What should you do?",
                    choices: [
                        { text: "🔌 Turn off the main power switch", correct: false },
                        { text: "📞 Call the electric company immediately", correct: true },
                        { text: "💧 Try to pump out the water first", correct: false }
                    ],
                    correctFeedback: "Smart! Never touch electrical equipment in flood water. Call professionals to safely shut off power.",
                    incorrectFeedback: "Water and electricity are deadly together! Call the electric company - never touch electrical equipment in flood water."
                },
                {
                    icon: "🚶‍♂️",
                    title: "Walking in Flood Water",
                    description: "How deep does moving flood water need to be to knock you down?",
                    choices: [
                        { text: "📏 6 inches", correct: true },
                        { text: "📐 12 inches", correct: false },
                        { text: "📊 18 inches", correct: false }
                    ],
                    correctFeedback: "Correct! Just 6 inches of moving water can knock you down. Never walk through flood water!",
                    incorrectFeedback: "It takes very little! Just 6 inches of fast-moving flood water can sweep you off your feet."
                },
                {
                    icon: "🏘️",
                    title: "Flood Evacuation",
                    description: "Officials have ordered evacuation due to flooding. When should you leave?",
                    choices: [
                        { text: "⏰ Immediately when told", correct: true },
                        { text: "🌅 Wait until morning", correct: false },
                        { text: "📦 After packing everything important", correct: false }
                    ],
                    correctFeedback: "Perfect! Evacuate immediately when officials tell you to. Your life is more important than belongings.",
                    incorrectFeedback: "Don't delay! When evacuation is ordered, leave immediately. Belongings can be replaced, but lives cannot."
                },
                {
                    icon: "🌉",
                    title: "Flood and Bridges",
                    description: "You need to cross a bridge during flooding. What should you check first?",
                    choices: [
                        { text: "🌊 If water is flowing over the bridge", correct: true },
                        { text: "🚗 If other cars are crossing", correct: false },
                        { text: "⏰ How long the detour would take", correct: false }
                    ],
                    correctFeedback: "Right! Never cross a flooded bridge. Water flowing over bridges can wash them out or sweep vehicles away.",
                    incorrectFeedback: "Never cross flooded bridges! Water flowing over bridges is extremely dangerous and can wash away the bridge or your vehicle."
                },
                {
                    icon: "📻",
                    title: "Flood Information",
                    description: "During a flood, what's the best way to get emergency information?",
                    choices: [
                        { text: "📱 Social media", correct: false },
                        { text: "📻 Battery-powered radio", correct: true },
                        { text: "📺 Cable TV", correct: false }
                    ],
                    correctFeedback: "Excellent! Battery-powered radios work even when power is out and provide official emergency information.",
                    incorrectFeedback: "Battery-powered radios are most reliable during emergencies when power and internet may be out."
                },
                {
                    icon: "🏠",
                    title: "After the Flood",
                    description: "Flood water has receded from your house. What should you do before going inside?",
                    choices: [
                        { text: "🔍 Check for structural damage first", correct: true },
                        { text: "🧹 Start cleaning immediately", correct: false },
                        { text: "📱 Take photos for insurance", correct: false }
                    ],
                    correctFeedback: "Smart! Check for structural damage, gas leaks, and electrical hazards before entering a flooded building.",
                    incorrectFeedback: "Safety first! Check for damage and hazards before entering. The building might not be safe."
                },
                {
                    icon: "💧",
                    title: "Flood Water Safety",
                    description: "You see flood water that looks clean. Is it safe to drink?",
                    choices: [
                        { text: "✅ Yes, if it looks clear", correct: false },
                        { text: "❌ No, flood water is always contaminated", correct: true },
                        { text: "🔥 Yes, if you boil it first", correct: false }
                    ],
                    correctFeedback: "Correct! Flood water contains sewage, chemicals, and debris. Never drink it, even if it looks clean!",
                    incorrectFeedback: "Flood water is always contaminated with sewage, chemicals, and dangerous bacteria. Never drink it!"
                }
            ],
            storm: [
                {
                    icon: "⛈️",
                    title: "Severe Thunderstorm!",
                    description: "You hear thunder and see lightning outside. Where is the safest place to be?",
                    choices: [
                        { text: "🌳 Under a tall tree for shelter", correct: false },
                        { text: "🏠 Inside a sturdy building", correct: true },
                        { text: "🚗 In an open field", correct: false }
                    ],
                    correctFeedback: "Perfect! A sturdy building is the safest place during a thunderstorm. Stay away from windows and avoid water!",
                    incorrectFeedback: "Stay safe! During thunderstorms, always seek shelter in a sturdy building. Avoid trees, open areas, and water."
                },
                {
                    icon: "🌪️",
                    title: "Tornado Warning!",
                    description: "A tornado warning has been issued for your area. Where should you go in your house?",
                    choices: [
                        { text: "🏠 Basement or lowest floor, center room", correct: true },
                        { text: "🪟 Near windows to watch the storm", correct: false },
                        { text: "🏠 Highest floor of the house", correct: false }
                    ],
                    correctFeedback: "Excellent! The basement or lowest floor center room away from windows is safest during a tornado.",
                    incorrectFeedback: "For tornado safety, go to the lowest floor, center room, away from windows. Basements are ideal if available."
                },
                {
                    icon: "❄️",
                    title: "Winter Storm Safety",
                    description: "A blizzard is coming and power might go out. What's the most important thing to have ready?",
                    choices: [
                        { text: "🔥 Safe heating source and warm blankets", correct: true },
                        { text: "📱 Fully charged phone", correct: false },
                        { text: "🍕 Frozen food", correct: false }
                    ],
                    correctFeedback: "Great thinking! Staying warm is critical during winter storms. Have safe heating sources and extra blankets ready.",
                    incorrectFeedback: "Staying warm is the top priority in winter storms. Have safe heating sources, warm clothes, and blankets ready."
                },
                {
                    icon: "🌀",
                    title: "Hurricane Preparation",
                    description: "A hurricane is approaching your area. What should you do with outdoor furniture?",
                    choices: [
                        { text: "🏠 Bring it inside or secure it", correct: true },
                        { text: "🌳 Tie it to trees", correct: false },
                        { text: "🏠 Leave it where it is", correct: false }
                    ],
                    correctFeedback: "Perfect! Outdoor furniture becomes dangerous projectiles in hurricane winds. Secure or bring inside!",
                    incorrectFeedback: "Hurricane winds turn outdoor furniture into dangerous flying objects. Always secure or bring items inside!"
                },
                {
                    icon: "⚡",
                    title: "Lightning Safety",
                    description: "You're outside when you see lightning. How long should you wait after the last thunder before it's safe?",
                    choices: [
                        { text: "⏰ 30 minutes", correct: true },
                        { text: "⏱️ 10 minutes", correct: false },
                        { text: "⏲️ 5 minutes", correct: false }
                    ],
                    correctFeedback: "Correct! Wait 30 minutes after the last thunder before going outside. Lightning can strike from far away!",
                    incorrectFeedback: "Wait a full 30 minutes after the last thunder. Lightning can strike up to 10 miles from a storm!"
                },
                {
                    icon: "🏊‍♂️",
                    title: "Storm and Water",
                    description: "You're swimming when you hear thunder in the distance. What should you do?",
                    choices: [
                        { text: "🏊‍♂️ Keep swimming, it's far away", correct: false },
                        { text: "🏃‍♂️ Get out of water immediately", correct: true },
                        { text: "🏊‍♂️ Swim to the middle of the pool", correct: false }
                    ],
                    correctFeedback: "Smart! Water conducts electricity. Get out immediately when you hear thunder, even if it's distant!",
                    incorrectFeedback: "Water and lightning are deadly! Get out of all water immediately when you hear thunder, no matter how far away."
                },
                {
                    icon: "🚗",
                    title: "Tornado While Driving",
                    description: "You see a tornado while driving. What should you do?",
                    choices: [
                        { text: "🚗 Stay in the car and drive away", correct: false },
                        { text: "🏢 Find a sturdy building for shelter", correct: true },
                        { text: "🌉 Hide under an overpass", correct: false }
                    ],
                    correctFeedback: "Right! Get to a sturdy building if possible. Cars and overpasses are not safe from tornadoes!",
                    incorrectFeedback: "Find a sturdy building! Cars offer no protection from tornadoes, and overpasses can create deadly wind tunnels."
                },
                {
                    icon: "📱",
                    title: "Storm Warnings",
                    description: "What's the difference between a storm watch and a storm warning?",
                    choices: [
                        { text: "👀 Watch means conditions are right, warning means it's happening", correct: true },
                        { text: "⚠️ They mean the same thing", correct: false },
                        { text: "📺 Watch is on TV, warning is on radio", correct: false }
                    ],
                    correctFeedback: "Perfect! A watch means conditions are right for severe weather. A warning means it's actually happening!",
                    incorrectFeedback: "A watch means conditions are favorable for severe weather. A warning means severe weather is actually occurring!"
                },
                {
                    icon: "🌨️",
                    title: "Ice Storm Safety",
                    description: "During an ice storm, what's the biggest danger?",
                    choices: [
                        { text: "❄️ Getting too cold", correct: false },
                        { text: "⚡ Power lines falling down", correct: true },
                        { text: "🚗 Cars sliding on ice", correct: false }
                    ],
                    correctFeedback: "Correct! Ice storms bring down power lines and tree branches. Stay inside and away from windows!",
                    incorrectFeedback: "Ice storms cause power lines and tree branches to fall. This is the greatest danger during ice storms."
                },
                {
                    icon: "🌊",
                    title: "Storm Surge",
                    description: "What is storm surge during a hurricane?",
                    choices: [
                        { text: "🌊 Ocean water pushed onto land by wind", correct: true },
                        { text: "🌧️ Extra heavy rainfall", correct: false },
                        { text: "💨 Very strong wind gusts", correct: false }
                    ],
                    correctFeedback: "Excellent! Storm surge is ocean water pushed onto land by hurricane winds. It's very dangerous!",
                    incorrectFeedback: "Storm surge is ocean water pushed onto shore by hurricane winds. It can flood areas miles inland!"
                }
            ],
            general: [
                {
                    icon: "📞",
                    title: "Emergency Numbers",
                    description: "There's an emergency and you need help immediately. What number should you call in the United States?",
                    choices: [
                        { text: "📞 911", correct: true },
                        { text: "📞 411", correct: false },
                        { text: "📞 811", correct: false }
                    ],
                    correctFeedback: "Perfect! 911 is the emergency number for police, fire, and medical emergencies in the United States.",
                    incorrectFeedback: "Remember: 911 is the number to call for any emergency in the United States. It connects you to police, fire, or medical help."
                },
                {
                    icon: "🎒",
                    title: "Emergency Kit Basics",
                    description: "Every family should have an emergency kit. How many days of supplies should it contain?",
                    choices: [
                        { text: "📅 At least 3 days", correct: true },
                        { text: "📅 Just 1 day", correct: false },
                        { text: "📅 One week", correct: false }
                    ],
                    correctFeedback: "Correct! Emergency kits should have at least 3 days of food, water, and supplies for each person.",
                    incorrectFeedback: "Emergency kits should contain at least 3 days of supplies. This gives you time until help arrives or services are restored."
                },
                {
                    icon: "📻",
                    title: "Emergency Communication",
                    description: "During a disaster, what's the best way to get official emergency information?",
                    choices: [
                        { text: "📱 Social media posts", correct: false },
                        { text: "📻 Emergency radio broadcasts", correct: true },
                        { text: "👥 Neighbors and friends", correct: false }
                    ],
                    correctFeedback: "Excellent! Official emergency radio broadcasts provide accurate, up-to-date information from authorities.",
                    incorrectFeedback: "Emergency radio broadcasts give official information from authorities. Social media can spread false information during emergencies."
                },
                {
                    icon: "🏠",
                    title: "Family Emergency Plan",
                    description: "What's the most important part of a family emergency plan?",
                    choices: [
                        { text: "📍 A meeting place everyone knows", correct: true },
                        { text: "💰 Having cash available", correct: false },
                        { text: "🎒 Packing emergency bags", correct: false }
                    ],
                    correctFeedback: "Perfect! A designated meeting place ensures your family can find each other during an emergency.",
                    incorrectFeedback: "A meeting place is crucial! If you get separated during an emergency, everyone needs to know where to go."
                },
                {
                    icon: "🚨",
                    title: "Emergency Alert Systems",
                    description: "You receive an emergency alert on your phone. What should you do first?",
                    choices: [
                        { text: "📖 Read it carefully and follow instructions", correct: true },
                        { text: "📱 Share it on social media", correct: false },
                        { text: "🗑️ Delete it if you think it's fake", correct: false }
                    ],
                    correctFeedback: "Smart! Emergency alerts contain important safety information. Read carefully and follow the instructions.",
                    incorrectFeedback: "Always take emergency alerts seriously! Read them carefully and follow the safety instructions provided."
                },
                {
                    icon: "🏥",
                    title: "Medical Emergency",
                    description: "Someone is unconscious and not breathing. What should you do first after calling 911?",
                    choices: [
                        { text: "💊 Look for their medication", correct: false },
                        { text: "🫁 Start CPR if you know how", correct: true },
                        { text: "💧 Give them water", correct: false }
                    ],
                    correctFeedback: "Correct! If trained, start CPR immediately. If not trained, the 911 operator can guide you through it.",
                    incorrectFeedback: "CPR can save lives! If you're trained, start immediately. If not, the 911 operator can guide you."
                },
                {
                    icon: "🔦",
                    title: "Power Outage Safety",
                    description: "The power goes out during a storm. What should you use for light?",
                    choices: [
                        { text: "🕯️ Candles", correct: false },
                        { text: "🔦 Flashlights", correct: true },
                        { text: "🔥 Gas stove flame", correct: false }
                    ],
                    correctFeedback: "Perfect! Flashlights are safe and won't cause fires. Keep extra batteries in your emergency kit!",
                    incorrectFeedback: "Flashlights are safest! Candles and open flames can cause fires, especially during storms with strong winds."
                },
                {
                    icon: "🆘",
                    title: "Getting Help",
                    description: "You're trapped and need to signal for help. What's the universal distress signal?",
                    choices: [
                        { text: "📢 Three of anything (whistle blasts, shouts, etc.)", correct: true },
                        { text: "🔥 Making a fire", correct: false },
                        { text: "📱 Flashing your phone light", correct: false }
                    ],
                    correctFeedback: "Excellent! Three of anything (whistle blasts, shouts, horn honks) is the universal distress signal.",
                    incorrectFeedback: "Three of anything is the universal distress signal! Three whistle blasts, shouts, or horn honks means 'HELP!'"
                },
                {
                    icon: "🧠",
                    title: "Staying Calm",
                    description: "During an emergency, what's the most important thing to remember?",
                    choices: [
                        { text: "🧠 Stay calm and think clearly", correct: true },
                        { text: "🏃‍♂️ Move as fast as possible", correct: false },
                        { text: "📱 Take photos for evidence", correct: false }
                    ],
                    correctFeedback: "Perfect! Staying calm helps you think clearly and make good decisions that keep you safe.",
                    incorrectFeedback: "Staying calm is crucial! Panic leads to poor decisions. Take a deep breath and think before you act."
                },
                {
                    icon: "👨‍👩‍👧‍👦",
                    title: "Helping Others",
                    description: "You're safe after a disaster, but you see someone who needs help. What should you do?",
                    choices: [
                        { text: "🏃‍♂️ Rush in to help immediately", correct: false },
                        { text: "📞 Call for professional help first", correct: true },
                        { text: "📱 Take photos to post online", correct: false }
                    ],
                    correctFeedback: "Smart! Call professionals first. Make sure the area is safe before helping, or you might become another victim.",
                    incorrectFeedback: "Call for professional help first! Make sure it's safe before helping, or you could become another person who needs rescue."
                }
            ]
        };

        let currentScore = 0;
        let currentLevel = 1;
        let currentStreak = 0;
        let currentDisasterType = 'earthquake';
        let currentScenarioIndex = 0;
        let gameState = 'playing';

        function updateScore(points) {
            currentScore += points;
            document.getElementById('score').textContent = currentScore;
            
            if (currentScore > 0 && currentScore % 100 === 0) {
                currentLevel++;
                document.getElementById('level').textContent = currentLevel;
            }
        }

        function updateStreak(correct) {
            if (correct) {
                currentStreak++;
            } else {
                currentStreak = 0;
            }
            document.getElementById('streak').textContent = currentStreak;
        }

        function updateProgress() {
            const totalScenarios = scenarios[currentDisasterType].length;
            const progress = ((currentScenarioIndex + 1) / totalScenarios) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
        }

        function loadScenario() {
            const scenario = scenarios[currentDisasterType][currentScenarioIndex];
            
            document.getElementById('scenarioIcon').textContent = scenario.icon;
            document.getElementById('scenarioTitle').textContent = scenario.title;
            document.getElementById('scenarioDescription').textContent = scenario.description;
            
            const choicesContainer = document.getElementById('choicesContainer');
            choicesContainer.innerHTML = '';
            
            scenario.choices.forEach((choice, index) => {
                const button = document.createElement('button');
                button.className = 'choice-button';
                button.innerHTML = choice.text;
                button.dataset.correct = choice.correct;
                button.addEventListener('click', handleChoice);
                choicesContainer.appendChild(button);
            });
            
            document.getElementById('feedbackArea').classList.remove('show');
            gameState = 'playing';
            updateProgress();
        }

        function handleChoice(event) {
            if (gameState !== 'playing') return;
            
            const button = event.target;
            const isCorrect = button.dataset.correct === 'true';
            const scenario = scenarios[currentDisasterType][currentScenarioIndex];
            
            // Disable all buttons
            document.querySelectorAll('.choice-button').forEach(btn => {
                btn.style.pointerEvents = 'none';
                if (btn.dataset.correct === 'true') {
                    btn.classList.add('correct');
                } else if (btn === button && !isCorrect) {
                    btn.classList.add('incorrect');
                }
            });
            
            // Show feedback
            const feedbackArea = document.getElementById('feedbackArea');
            const feedbackTitle = document.getElementById('feedbackTitle');
            const feedbackText = document.getElementById('feedbackText');
            
            if (isCorrect) {
                feedbackArea.className = 'feedback-area show correct';
                feedbackTitle.textContent = '🎉 Excellent!';
                feedbackText.textContent = scenario.correctFeedback;
                updateScore(20 + (currentStreak * 5));
                updateStreak(true);
            } else {
                feedbackArea.className = 'feedback-area show incorrect';
                feedbackTitle.textContent = '💡 Learn & Try Again!';
                feedbackText.textContent = scenario.incorrectFeedback;
                updateStreak(false);
            }
            
            gameState = 'answered';
        }

        function nextScenario() {
            currentScenarioIndex++;
            
            if (currentScenarioIndex >= scenarios[currentDisasterType].length) {
                // Move to next disaster type or restart
                const disasterTypes = Object.keys(scenarios);
                const currentTypeIndex = disasterTypes.indexOf(currentDisasterType);
                
                if (currentTypeIndex < disasterTypes.length - 1) {
                    currentDisasterType = disasterTypes[currentTypeIndex + 1];
                    currentScenarioIndex = 0;
                    
                    // Update active disaster type
                    document.querySelectorAll('.disaster-type').forEach(type => {
                        type.classList.remove('active');
                        if (type.dataset.type === currentDisasterType) {
                            type.classList.add('active');
                        }
                    });
                } else {
                    // Game completed, restart
                    currentDisasterType = 'earthquake';
                    currentScenarioIndex = 0;
                    document.querySelector('.disaster-type[data-type="earthquake"]').classList.add('active');
                }
            }
            
            // Reset choice buttons
            document.querySelectorAll('.choice-button').forEach(btn => {
                btn.style.pointerEvents = 'auto';
                btn.classList.remove('correct', 'incorrect');
            });
            
            loadScenario();
        }

        // Event listeners
        document.getElementById('nextButton').addEventListener('click', nextScenario);

        document.querySelectorAll('.disaster-type').forEach(type => {
            type.addEventListener('click', function() {
                if (gameState === 'playing') return;
                
                document.querySelectorAll('.disaster-type').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                currentDisasterType = this.dataset.type;
                currentScenarioIndex = 0;
                
                // Reset choice buttons
                document.querySelectorAll('.choice-button').forEach(btn => {
                    btn.style.pointerEvents = 'auto';
                    btn.classList.remove('correct', 'incorrect');
                });
                
                loadScenario();
            });
        });

        // Initialize game
        loadScenario();
    </script>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'987b81d677038959',t:'MTc1OTMxNjk2MC4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>
