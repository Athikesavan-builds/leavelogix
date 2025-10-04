<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}

    body{
      min-height:100vh;
      background:url('images/sunrise.png') no-repeat center center/cover;
      transition:background 0.4s ease,color 0.3s ease;
      color:#222;
    }
    body.dark{background:url('images/moon.jpg') no-repeat center center/cover;color:#eee;}

    header{display:flex;justify-content:space-between;align-items:center;padding:15px 30px;}
    .logo-fixed{display:flex;align-items:center;gap:8px;cursor:pointer;padding:6px 12px;
      border:2px solid rgba(0,0,0,0.15);border-radius:12px;background:rgba(255,255,255,0.7);
      backdrop-filter:blur(6px);font-weight:700;text-decoration:none;color:#333;}
    .logo-fixed img{height:40px;width:40px;border-radius:50%;border:2px solid #5b5ff4;padding:3px;background:#fff;}
    body.dark .logo-fixed{background:rgba(34,34,34,0.7);border:2px solid rgba(255,255,255,0.2);color:#eee;}
    body.dark .logo-fixed img{border:2px solid #f062c0;background:#222;}
    .actions{display:flex;align-items:center;gap:12px;}
    .icon-btn{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;
      background:rgba(255,255,255,0.85);cursor:pointer;font-size:18px;
      box-shadow:0 4px 12px rgba(0,0,0,0.06);transition:transform .18s;}
    .icon-btn:hover{transform:translateY(-3px);}
    body.dark .icon-btn{background:rgba(0,0,0,0.45);color:#fff;}

    .title-container{text-align:center;margin:20px 0;}
    .title-container h1{font-size:26px;font-weight:700;background:#0ebfff;color:#fff;
      display:inline-block;padding:6px 16px;border-radius:8px;}

    .settings-container{display:flex;gap:20px;justify-content:center;width:90%;max-width:1100px;margin:0 auto;}
    .settings-box{
      flex:1;background:rgba(255, 255, 255, 0.521);padding:25px;border-radius:12px;
      box-shadow:0 4px 12px rgb(5, 5, 5);
    }
    body.dark .settings-box{background:rgba(0, 0, 0, 0.868);}

    h2{margin-bottom:12px;color:#007bff;}
    body.dark h2{color:#f062c0;}
    label{font-weight:600;display:block;margin-top:15px;margin-bottom:6px;}
    input,select{
      width:100%;padding:10px;border-radius:8px;border:1px solid #ccc;font-size:15px;margin-bottom:12px;
    }
    button{background:#007bff;color:#fff;font-weight:600;cursor:pointer;border:none;
      padding:10px 16px;border-radius:8px;margin-top:10px;}
    button:hover{background:#0056b3;}
    body.dark input, body.dark select{background:#00000076;color:#fff;border:1px solid #00000000;}

    .holiday-list{margin-top:10px;}
    .holiday-item{padding:8px 10px;border-radius:6px;background:#f4f4f4;margin-bottom:6px;}
    body.dark .holiday-item{background:#00000000;color:#ffffff;}

    .balance-card{
      background:#fafafa98;border-radius:12px;padding:20px;box-shadow:0 3px 8px rgba(0, 0, 0, 0.485);
    }
    body.dark .balance-card{background:#222;color:#eeeeee;}
    .balance-card p{margin:8px 0;font-weight:600;}
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <a href="admin-dashboard.php" class="logo-fixed">
      <img src="images/new-logo.jpeg" alt="LeaveLogix Logo">
      <span>LeaveLogix</span>
    </a>
    <div class="actions">
      <div id="darkToggle" class="icon-btn" title="Toggle Dark Mode">🌙</div>
    </div>
  </header>

  <!-- Title -->
  <div class="title-container">
    <h1>Settings</h1>
  </div>

  <!-- Main Settings Container -->
  <div class="settings-container">

    <!-- Right Side: Leave Balance Policies -->
    <div class="settings-box balance-card">
      <h2>Leave Balance Policy</h2>
      <label>Annual Leave Allocation</label>
      <input type="number" id="annualLeave" placeholder="e.g. 20">
    
      <label>Sick Leave Allocation</label>
      <input type="number" id="sickLeave" placeholder="e.g. 10">

      <h2>Roles & Permissions</h2>
      <label>Default Role</label>
      <select id="defaultRole">
        <option>Employee</option>
        <option>Student</option>
        <option>Manager</option>
        <option>Admin</option>
      </select>

      <h2>Notifications</h2>
      <label>Email Notifications</label>
      <select id="emailNotif">
        <option>Enabled</option>
        <option>Disabled</option>
      </select>

      <button onclick="saveSettings()">💾 Save Settings</button>
      <p id="msg" style="margin-top:10px;font-weight:600;"></p>
    </div>
  </div>

  <script>
    // Dark Mode sync
    const toggle=document.getElementById("darkToggle");
    if(localStorage.getItem("darkMode")==="enabled"){document.body.classList.add("dark");toggle.textContent="☀️";}
    toggle.addEventListener("click",()=>{
      if(document.body.classList.toggle("dark")){
        localStorage.setItem("darkMode","enabled");toggle.textContent="☀️";
      } else {
        localStorage.setItem("darkMode","disabled");toggle.textContent="🌙";
      }
    });

    // Load saved holidays + settings
    window.onload=()=>{
      let holidays=JSON.parse(localStorage.getItem("holidays"))||[];
      holidays.forEach(h=>renderHoliday(h.date,h.name));
      if(localStorage.getItem("annualLeave")) document.getElementById("annualLeave").value=localStorage.getItem("annualLeave");
      if(localStorage.getItem("sickLeave")) document.getElementById("sickLeave").value=localStorage.getItem("sickLeave");
      if(localStorage.getItem("defaultRole")) document.getElementById("defaultRole").value=localStorage.getItem("defaultRole");
      if(localStorage.getItem("emailNotif")) document.getElementById("emailNotif").value=localStorage.getItem("emailNotif");
    }

    function addHoliday(){
      const date=document.getElementById("holidayDate").value;
      const name=document.getElementById("holidayName").value;
      if(!date||!name) return alert("Please enter both date and name");
      renderHoliday(date,name);
      let holidays=JSON.parse(localStorage.getItem("holidays"))||[];
      holidays.push({date,name});
      localStorage.setItem("holidays",JSON.stringify(holidays));
      document.getElementById("holidayDate").value="";
      document.getElementById("holidayName").value="";
    }

    function renderHoliday(date,name){
      const div=document.createElement("div");
      div.className="holiday-item";
      div.textContent=`${date} - ${name}`;
      document.getElementById("holidayList").appendChild(div);
    }

    function saveSettings(){
      localStorage.setItem("annualLeave",document.getElementById("annualLeave").value);
      localStorage.setItem("sickLeave",document.getElementById("sickLeave").value);
      localStorage.setItem("defaultRole",document.getElementById("defaultRole").value);
      localStorage.setItem("emailNotif",document.getElementById("emailNotif").value);
      document.getElementById("msg").textContent="✅ Settings Saved Successfully!";
      document.getElementById("msg").style.color="green";
    }
  </script>
</body>
</html>
