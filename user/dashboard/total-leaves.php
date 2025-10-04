<?php
// total-leaves.php
session_start();

// ---- DB Config ----
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "leavelogix";

// Connect DB
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("DB Connection failed: " . $mysqli->connect_error);
}

// Ensure table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    reason TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Mock login user (replace with real session logic)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // demo
}
$user_id = $_SESSION['user_id'];

// Total allocated leaves (fixed, e.g. 30)
$total_allocated = 30;

// Calculate used leaves (only approved ones)
$res = $mysqli->query("SELECT from_date, to_date, status 
                       FROM leave_requests 
                       WHERE user_id=$user_id AND status='Approved'");

$used_days = 0;
while ($row = $res->fetch_assoc()) {
    $from = new DateTime($row['from_date']);
    $to = new DateTime($row['to_date']);
    $diff = $to->diff($from)->days + 1; // inclusive
    $used_days += $diff;
}

$balance = max(0, $total_allocated - $used_days);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Total Leaves - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Inter",sans-serif}
    body{
      min-height:100vh;
      background:url("../../images/sunrise.png") no-repeat center/cover;
      color:#111;
      transition:background .4s,color .3s;
    }
    body.dark{
      background:url("../../images/moon.jpg") no-repeat center/cover;
      color:#eee;
    }
    header{display:flex;justify-content:space-between;align-items:center;padding:15px 30px;}

    /* Logo Style */
    .logo-fixed {
      display:flex;align-items:center;gap:8px;
      cursor:pointer;padding:6px 12px;
      border:2px solid rgba(0,0,0,0.15);
      border-radius:12px;
      background:rgba(255,255,255,0.7);
      backdrop-filter:blur(6px);
      transition:0.3s;
      font-weight:600;
      text-decoration:none;
      color:#333;
    }
    .logo-fixed:hover {background:rgba(255,255,255,0.9);transform:scale(1.05);}
    .logo-fixed img {
      height:40px;width:40px;border-radius:50%;
      border:2px solid #5b5ff4;
      padding:3px;background:#fff;
    }
    body.dark .logo-fixed {
      background:rgba(34,34,34,0.7);
      border:2px solid rgba(255,255,255,0.2);
      color:#eee;
    }
    body.dark .logo-fixed img {
      border:2px solid #f062c0;background:#222;
    }

    /* Dark Mode Button */
    .icon-btn{
      width:40px;height:40px;border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      background:rgba(255,255,255,.85);cursor:pointer;
      box-shadow:0 4px 12px rgba(0,0,0,.08);
      transition:.2s;font-size:18px;
    }
    body.dark .icon-btn{background:rgba(0,0,0,.5);color:#ffffff}

    .page-title{padding:0 32px 24px}
    .page-title h1{font-size:38px;margin-bottom:6px}
    .page-title p{color:rgba(0,0,0,.6)}
    body.dark .page-title p{color:rgba(209,198,198,0.6)}

    main{display:flex;justify-content:center;padding:20px;}
    .card{
      max-width:500px;width:100%;
      background:rgba(255,255,255,.92);
      border:2px solid rgba(78,159,244,.2);
      border-radius:16px;
      padding:24px;
      box-shadow:0 8px 24px rgba(0,0,0,.08);
      animation:fadeInUp .6s ease both;
    }
    body.dark .card{
      background:rgba(20,20,20,.85);
      border:2px solid rgba(240,98,192,.2);
      box-shadow:0 8px 24px rgba(0,0,0,.3);
    }
    .stat{margin-bottom:20px}
    .stat h3{font-size:18px;margin-bottom:6px}
    .progress{height:14px;background:rgba(0,0,0,.1);border-radius:10px;overflow:hidden}
    .progress-bar{height:100%;background:#4e9ff4;width:0;transition:.8s}

    @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
  </style>
</head>
<body>
  <header>
    <!-- Logo Left -->
    <a href="dashboard.php" class="logo-fixed">
      <img src="../../images/new-logo.jpeg" alt="LeaveLogix Logo">
      <span>LeaveLogix</span>
    </a>

    <!-- Dark Mode Button -->
    <div class="icon-btn" id="darkToggle">🌙</div>
  </header>

  <section class="page-title">
    <h1>Total Leaves</h1>
    <p>Overview of your leave balance</p>
  </section>

  <main>
    <div class="card">
      <div class="stat">
        <h3>Total Allocated</h3>
        <p id="total"><?php echo $total_allocated; ?> days</p>
      </div>
      <div class="stat">
        <h3>Used</h3>
        <p id="used"><?php echo $used_days; ?> days</p>
        <div class="progress"><div class="progress-bar" id="usedBar"></div></div>
      </div>
      <div class="stat">
        <h3>Balance</h3>
        <p id="balance"><?php echo $balance; ?> days</p>
      </div>
    </div>
  </main>

  <script>
    // Dark mode toggle
    const darkToggle=document.getElementById('darkToggle');
    function applyDark(){
      if(localStorage.getItem('darkMode')==='enabled'){
        document.body.classList.add('dark');darkToggle.textContent='☀️';
      }else{document.body.classList.remove('dark');darkToggle.textContent='🌙';}
    }
    darkToggle.onclick=()=>{
      if(document.body.classList.toggle('dark')){
        localStorage.setItem('darkMode','enabled');darkToggle.textContent='☀️';
      }else{
        localStorage.setItem('darkMode','disabled');darkToggle.textContent='🌙';
      }
    };
    applyDark();

    // Progress bar fill
    const used=<?php echo $used_days; ?>;
    const total=<?php echo $total_allocated; ?>;
    const percent=(used/total)*100;
    document.getElementById('usedBar').style.width=(percent>100?100:percent)+"%";
  </script>
</body>
</html>
