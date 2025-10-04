<?php
// dashboard.php
session_start();

// --- DB CONFIG ---
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "leavelogix";

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("DB Connection failed: " . $mysqli->connect_error);
}

// --- Check login ---
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ---- Fetch Notifications from DB ----
// Table: notifications(user_id, message, created_at)
$mysqli->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$notifRes = $mysqli->query("SELECT message, created_at FROM notifications 
                            WHERE user_id=$user_id 
                            ORDER BY created_at DESC LIMIT 10");

$notifications = [];
while ($row = $notifRes->fetch_assoc()) {
    $notifications[] = $row['message'] . " (" . date("d M H:i", strtotime($row['created_at'])) . ")";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}

    body{
      min-height:100vh;
      background:url('../../images/sunrise.png') no-repeat center center/cover;
      transition:background 0.45s ease,color 0.3s ease;
      color:#222;
    }
    body.dark{background:url('../../images/moon.jpg') no-repeat center center/cover;color:#eee;}

    /* Header */
    header{display:flex;justify-content:space-between;align-items:center;padding:15px 30px;position:relative;}

    /* Logo */
    .logo-fixed{display:flex;align-items:center;gap:8px;cursor:pointer;
      padding:6px 12px;border:2px solid rgba(0,0,0,0.15);border-radius:12px;
      background:rgba(255,255,255,0.7);backdrop-filter:blur(6px);
      transition:0.3s;font-weight:700;text-decoration:none;color:#333;}
    .logo-fixed img{height:40px;width:40px;border-radius:50%;border:2px solid #5b5ff4;padding:3px;background:#fff;}
    .logo-fixed:hover{background:rgba(255,255,255,0.9);transform:scale(1.05);}
    body.dark .logo-fixed{background:rgba(34,34,34,0.7);border:2px solid rgba(255,255,255,0.2);color:#eee;}
    body.dark .logo-fixed img{border:2px solid #f062c0;background:#222;}

    /* Actions */
    .actions{display:flex;align-items:center;gap:12px;position:relative;}
    .icon-btn{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;
      background:rgba(255,255,255,0.85);cursor:pointer;font-size:18px;
      box-shadow:0 4px 12px rgba(0,0,0,0.06);transition:transform .18s, background .18s;}
    .icon-btn:hover{transform:translateY(-3px);}
    body.dark .icon-btn{background:rgba(0,0,0,0.45);color:#fff;}

    /* Notification Dropdown */
    .notif-box{position:absolute;top:55px;right:60px;width:280px;max-height:300px;
      overflow-y:auto;background:#fff;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.15);
      display:none;flex-direction:column;z-index:100;padding:10px;}
    body.dark .notif-box{background:#333;color:#fff;}
    .notif-box h3{margin-bottom:8px;font-size:16px;border-bottom:1px solid #ddd;padding-bottom:5px;}
    .notif-item{padding:8px;border-bottom:1px solid #eee;font-size:14px;}
    .notif-item:last-child{border-bottom:none;}
    body.dark .notif-item{border-bottom:1px solid #555;}

    /* Title */
    .title-container{text-align:center;margin:20px 0;}
    .title-container h1{font-size:28px;font-weight:700;background:#0ebfff;display:inline-block;
      padding:5px 15px;border-radius:8px;animation:fadeInDown 0.8s ease;color:#fff;}

    /* Cards */
    .card-container{display:flex;flex-wrap:wrap;gap:20px;justify-content:flex-start;padding:20px 40px;animation:fadeIn 1s ease;}
    .card{flex:1 1 220px;max-width:3000px;background:rgba(255,255,255,0.5);border-radius:12px;padding:20px;
      box-shadow:0 4px 10px rgba(0,0,0,0.1);transition:transform 0.3s ease,box-shadow 0.3s ease;}
    .card:hover{transform:translateY(-6px);box-shadow:0 8px 16px rgba(0,0,0,0.15);}
    body.dark .card{background:rgba(20,20,20,0.5);box-shadow:0 4px 12px rgba(255,255,255,0.1);}
    .card h2{font-size:18px;margin-bottom:10px;}
    .card p{font-size:14px;margin-bottom:15px;}
    .card button{border:1.5px solid #007bff;background:transparent;color:#007bff;
      padding:6px 15px;border-radius:6px;cursor:pointer;transition:0.3s;font-weight:600;}
    .card button:hover{background:#007bff;color:#fff;}

    @keyframes fadeIn{from{opacity:0;transform:translateY(15px);}to{opacity:1;transform:translateY(0);}}
    @keyframes fadeInDown{from{opacity:0;transform:translateY(-15px);}to{opacity:1;transform:translateY(0);}}

    @media(max-width:768px){.card-container{justify-content:center;}}
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <!-- Logo -->
    <a href="dashboard.php" class="logo-fixed">
      <img src="../../images/new-logo.jpeg" alt="LeaveLogix Logo">
      <span>LeaveLogix</span>
    </a>

    <!-- Actions -->
    <div class="actions">
      <div class="icon-btn" title="Profile" onclick="goProfile()">👤</div>
      <div id="darkToggle" class="icon-btn" title="Toggle Dark Mode">🌙</div>
      <div class="icon-btn" title="Logout" onclick="logout()">➡️</div>
    </div>

    <!-- Notification Dropdown -->
    <div id="notifBox" class="notif-box">
      <h3>Notifications</h3>
      <div id="notifList">
        <?php if (count($notifications) === 0): ?>
          <p>No notifications</p>
        <?php else: ?>
          <?php foreach ($notifications as $n): ?>
            <div class="notif-item"><?php echo htmlspecialchars($n); ?></div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Title -->
  <div class="title-container"><h1>Dashboard</h1></div>

  <!-- Cards -->
  <div class="card-container">
    <div class="card">
      <h2>Apply New Leave</h2>
      <p>Submit a new leave request with your preferred dates and reason. Once applied, it will be sent for approval.</p>
      <button onclick="window.location.href='apply-leave.php'">Apply</button>
    </div>
    <div class="card">
      <h2>Leave Status</h2>
      <p>Track the progress of your leave applications. Check whether your requests are pending, approved, or rejected.</p>
      <button onclick="window.location.href='status.php'">View</button>
    </div>
    <div class="card">
      <h2>Reports</h2>
      <p>View a detailed record of all your leave history. Download or print reports for personal reference or official use.</p>
      <button onclick="window.location.href='reports.php'">View</button>
    </div>
    <div class="card">
      <h2>Total Leaves</h2>
      <p>See your overall leave balance, including the number of days used and remaining for the current period.</p>
      <button onclick="window.location.href='total-leaves.php'">View</button>
    </div>
  </div>

  <script>
    function goProfile(){ window.location.href="profile.php"; }
    function logout(){ window.location.href="../auth/login.php"; }

    // Dark Mode
    const toggle=document.getElementById("darkToggle");
    if(localStorage.getItem("darkMode")==="enabled"){document.body.classList.add("dark");toggle.textContent="☀️";}
    toggle.addEventListener("click",()=>{
      if(document.body.classList.toggle("dark")){
        localStorage.setItem("darkMode","enabled");toggle.textContent="☀️";
      } else {
        localStorage.setItem("darkMode","disabled");toggle.textContent="🌙";
      }
    });

    // Notifications dropdown toggle
    const notifBtn=document.getElementById("notifBtn");
    const notifBox=document.getElementById("notifBox");
    notifBtn.addEventListener("click",()=>{
      notifBox.style.display=notifBox.style.display==="flex"?"none":"flex";
    });
  </script>
</body>
</html>
