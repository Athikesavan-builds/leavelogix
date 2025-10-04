<?php
// leave_status.php
session_start();

// ---- Database Config ----
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "leavelogix";

// Connect
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("DB Connection failed: " . $mysqli->connect_error);
}

// Create table if not exists (same as apply_leave.php)
$mysqli->query("CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Mock: assume user logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // demo user
}
$user_id = $_SESSION['user_id'];

// Fetch user leave requests
$res = $mysqli->query("SELECT from_date,to_date,reason,status,created_at 
                       FROM leave_requests 
                       WHERE user_id=$user_id 
                       ORDER BY created_at DESC");
$requests = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Leave Status - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

    * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }

    body {
      background: url("../../images/sunrise.png") no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      transition: background 0.4s ease, color 0.3s ease;
      color:#222;
      padding: 20px;
    }
    body.dark {
      background: url("../../images/moon.jpg") no-repeat center center fixed;
      background-size: cover;
      color:#eee;
    }

    /* Logo */
    .logo-fixed {
      position: absolute;
      top: 20px;
      left: 20px;
      cursor: pointer;
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 6px 12px;
      border: 2px solid rgba(0,0,0,0.15);
      border-radius: 12px;
      background: rgba(255,255,255,0.7);
      backdrop-filter: blur(6px);
      transition: 0.3s;
      font-weight: 600;
      text-decoration: none;
      color: #333;
    }
    .logo-fixed img {
      height: 40px;
      width: 40px;
      border-radius: 50%;
      border: 2px solid #5b5ff4;
      padding: 3px;
      background: #fff;
    }
    body.dark .logo-fixed {
      background: rgba(34,34,34,0.7);
      border: 2px solid rgba(255,255,255,0.2);
      color: #eee;
    }
    body.dark .logo-fixed img {
      border: 2px solid #f062c0;
      background: #222;
    }

    /* Dark Toggle */
    .dark-toggle {
      position: absolute;
      top: 20px;
      right: 30px;
      background: #fff;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 3px 10px rgba(0,0,0,0.2);
      transition: 0.3s;
      font-size: 1.2rem;
      z-index: 1000;
    }
    body.dark .dark-toggle { background:#444; color:#fff; }

    /* Title */
    h1 {
      margin-top: 100px;
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 25px;
      animation: fadeInDown 0.8s ease;
      text-align: left;
    }

    /* Leave List */
    .leave-list {
      display: flex;
      flex-direction: column;
      gap: 15px;
      max-width: 650px;
      margin: 0 auto;
      animation: fadeIn 1s ease;
    }
    .leave-item {
      border: 2px solid #5bcbf5;
      border-radius: 8px;
      padding: 12px 18px;
      background: rgba(255,255,255,0.8);
      transition: transform 0.3s;
    }
    body.dark .leave-item { background: rgba(30,30,30,0.7); border-color: #888; }
    .leave-item:hover { transform: scale(1.02); }
    .leave-item span { font-size: 15px; font-weight: 500; }
    .status { font-weight: 700; }
    .pending { color: #f39c12; }
    .Approved { color: #27ae60; }
    .Rejected { color: #e74c3c; }

    @keyframes fadeIn { from { opacity:0; transform: translateY(15px);} to { opacity:1; transform: translateY(0);} }
    @keyframes fadeInDown { from { opacity:0; transform: translateY(-15px);} to { opacity:1; transform: translateY(0);} }
  </style>
</head>
<body>
  <!-- Logo -->
  <a href="dashboard.php" class="logo-fixed">
    <img src="../../images/new-logo.jpeg" alt="LeaveLogix Logo">
    <span>LeaveLogix</span>
  </a>

  <!-- Dark Toggle -->
  <div class="dark-toggle" onclick="toggleDarkMode()">🌙</div>

  <!-- Title -->
  <h1>Your Leave Requests</h1>

  <!-- Requests -->
  <div class="leave-list">
    <?php if ($requests): ?>
      <?php foreach ($requests as $row): ?>
        <div class="leave-item">
          <span>
            <?=htmlspecialchars($row['from_date'])?> → <?=htmlspecialchars($row['to_date'])?> |
            <?=htmlspecialchars($row['reason'])?>
          </span>
          <span class="status <?=htmlspecialchars($row['status'])?>">
            <?=htmlspecialchars($row['status'])?>
          </span>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="leave-item"><span>No leave requests yet.</span></div>
    <?php endif; ?>
  </div>

  <script>
    // Dark Mode localStorage
    if(localStorage.getItem("darkMode")==="enabled"){
      document.body.classList.add("dark");
      document.querySelector(".dark-toggle").textContent="☀️";
    }
    function toggleDarkMode(){
      let btn=document.querySelector(".dark-toggle");
      if(document.body.classList.contains("dark")){
        document.body.classList.remove("dark");
        localStorage.setItem("darkMode","disabled");
        btn.textContent="🌙";
      } else {
        document.body.classList.add("dark");
        localStorage.setItem("darkMode","enabled");
        btn.textContent="☀️";
      }
    }
  </script>
</body>
</html>
