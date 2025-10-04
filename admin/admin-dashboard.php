<?php
// admin-dashboard.php
session_start();

// If not logged in → redirect
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit;
}

// DB Config
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'leavelogix';

// Connect DB
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Fetch Admin Info
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT fullname, email, designation, org_type FROM admin WHERE id=? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
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
    body{min-height:100vh;background:url('images/sunrise.png') no-repeat center center/cover;
         transition:background 0.45s ease,color 0.3s ease;color:#222;}
    body.dark{background:url('images/moon.jpg') no-repeat center center/cover;color:#eee;}
    header{display:flex;justify-content:space-between;align-items:center;padding:15px 30px;position:relative;}
    .logo-fixed{display:flex;align-items:center;gap:8px;cursor:pointer;padding:6px 12px;
        border:2px solid rgba(0,0,0,0.15);border-radius:12px;background:rgba(255,255,255,0.7);
        backdrop-filter:blur(6px);transition:0.3s;font-weight:700;text-decoration:none;color:#333;}
    .logo-fixed img{height:40px;width:40px;border-radius:50%;border:2px solid #5b5ff4;padding:3px;background:#fff;}
    .logo-fixed:hover{background:rgba(255,255,255,0.9);transform:scale(1.05);}
    body.dark .logo-fixed{background:rgba(34,34,34,0.7);border:2px solid rgba(255,255,255,0.2);color:#eee;}
    body.dark .logo-fixed img{border:2px solid #f062c0;background:#222;}
    .actions{display:flex;align-items:center;gap:12px;}
    .icon-btn{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;
        background:rgba(255,255,255,0.85);cursor:pointer;font-size:18px;position:relative;
        box-shadow:0 4px 12px rgba(0,0,0,0.06);transition:transform .18s, background .18s;}
    .icon-btn:hover{transform:translateY(-3px);}
    body.dark .icon-btn{background:rgba(0,0,0,0.45);color:#fff;}
    .count-badge{position:absolute;top:6px;right:6px;background:red;color:#fff;font-size:12px;
        padding:2px 5px;border-radius:8px;display:none;}
    .dropdown{position:absolute;top:60px;right:80px;background:rgba(255,255,255,0.95);
        border-radius:10px;padding:10px;min-width:220px;box-shadow:0 4px 12px rgba(0,0,0,0.1);
        display:none;z-index:100;}
    .dropdown p{font-size:14px;padding:8px;margin:0;border-bottom:1px solid #eee;}
    .dropdown p:last-child{border-bottom:none;}
    body.dark .dropdown{background:rgba(34,34,34,0.95);color:#fff;}
    body.dark .dropdown p{border-bottom:1px solid rgba(255,255,255,0.15);}
    .title-container{text-align:center;margin:20px 0;}
    .title-container h1{font-size:28px;font-weight:700;background:#0ebfff;display:inline-block;
        padding:5px 15px;border-radius:8px;animation:fadeInDown 0.8s ease;color:#fff;}
    .card-container{display:flex;flex-wrap:wrap;gap:20px;justify-content:flex-start;
        padding:20px 40px;animation:fadeIn 1s ease;}
    .card{flex:1 1 220px;max-width:3000px;background:rgba(255,255,255,0.5);border-radius:12px;
        padding:20px;box-shadow:0 4px 10px rgba(0,0,0,0.1);transition:transform 0.3s ease,box-shadow 0.3s ease;}
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
    .profile-box{text-align:center;margin:10px 0;font-size:15px;}
    .profile-box p{margin:5px 0;}
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <!-- Logo Left -->
    <a href="index.html" class="logo-fixed">
      <img src="images/new-logo.jpeg" alt="LeaveLogix Logo">
      <span>LeaveLogix</span>
    </a>

    <!-- Actions Right -->
    <div class="actions">
      <div class="icon-btn" title="Profile" onclick="goProfile()">👤</div>
      <div id="darkToggle" class="icon-btn" title="Toggle Dark Mode">🌙</div>
      <div class="icon-btn" title="Logout" onclick="logout()">➡️</div>
    </div>
  </header>


  <!-- Title -->
  <div class="title-container">
    <h1>Dashboard</h1>
  </div>

  <!-- Cards -->
  <div class="card-container">
    <div class="card">
      <h2>Manage Users</h2>
      <p>Add, edit, or remove users. Maintain accurate records of employees and students with personal details, department info, leave balance, and activity logs.</p>
      <button onclick="window.location.href='manage-users.php'">Open</button>
    </div>
    <div class="card">
      <h2>Approve Leaves</h2>
      <p>View and manage all pending leave requests in real-time. Approve or reject them instantly with remarks. Track absentee patterns and ensure fairness.</p>
       <button onclick="window.location.href='approve-leaves.php'">Open</button>
    </div>
    <div class="card">
      <h2>Reports & Analytics</h2>
      <p>Access detailed department-wise reports, leave usage trends, and analytics dashboards. Export charts and summaries in PDF/Excel formats.</p>
      <button onclick="window.location.href='admin-reports.php'">Open</button>
    </div>
    <div class="card">
      <h2>Settings</h2>
      <p>Adjust leave policies, holiday calendars, and balance allocations. Configure roles, permissions, and notification settings as needed.</p>
    <button onclick="window.location.href='settings.php'">Open</button>
    </div>
  </div>

  <script>
    function goProfile(){ window.location.href="admin-profile.php"; }
    function logout(){ window.location.href="admin-logout.php"; }

    // Dark Mode Toggle
    const toggle=document.getElementById("darkToggle");
    if(localStorage.getItem("darkMode")==="enabled"){document.body.classList.add("dark");toggle.textContent="☀️";}
    toggle.addEventListener("click",()=>{
      if(document.body.classList.toggle("dark")){
        localStorage.setItem("darkMode","enabled");
        toggle.textContent="☀️";
      } else {
        localStorage.setItem("darkMode","disabled");
        toggle.textContent="🌙";
      }
    });

    // Notifications
    const notifBtn=document.getElementById("notifBtn");
    const notifDropdown=document.getElementById("notifDropdown");
    const notifList=document.getElementById("notifList");
    const notifCount=document.getElementById("notifCount");

    function loadNotifications(){
      let notifs=JSON.parse(localStorage.getItem("notifications")||"[]");
      notifList.innerHTML="";
      if(notifs.length===0){
        notifList.innerHTML="<p>No new notifications</p>";
        notifCount.style.display="none";
      }else{
        notifs.forEach(n=>{
          let p=document.createElement("p");
          p.textContent=n;
          notifList.appendChild(p);
        });
        notifCount.textContent=notifs.length;
        notifCount.style.display="block";
      }
    }

    notifBtn.addEventListener("click",()=>{
      notifDropdown.style.display=notifDropdown.style.display==="block"?"none":"block";
    });

    loadNotifications();
  </script>
</body>
</html>
