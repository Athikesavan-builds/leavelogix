<?php
// admin-profile.php
session_start();

// Database config
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'leavelogix';

// Connect
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) die("DB Connection failed: " . $conn->connect_error);

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];

// Fetch profile data
$stmt = $conn->prepare("SELECT fullName, email, department, designation, avatar FROM admin WHERE id=?");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc() ?: [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['fullName'] ?? '';
    $email = $_POST['email'] ?? '';
    $department = $_POST['department'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $avatarFileName = $profile['avatar']; // keep old avatar if not changed

    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $avatarFileName = 'avatar_' . $admin_id . '.' . $ext;
        // Make sure uploads folder exists
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/' . $avatarFileName);
    }

    // Update DB
    $stmt = $conn->prepare("UPDATE admin SET fullName=?, email=?, department=?, designation=?, avatar=? WHERE id=?");
    $stmt->bind_param('sssssi', $fullName, $email, $department, $designation, $avatarFileName, $admin_id);
    $stmt->execute();

    // Refresh profile values
    $profile = [
        'fullName'=>$fullName,
        'email'=>$email,
        'department'=>$department,
        'designation'=>$designation,
        'avatar'=>$avatarFileName
    ];

    $message = "Profile updated successfully!";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Profile - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

    :root{
      --accent:#4e9ff4;
      --accent-2:#5b5ff4;
      --pink:#f062c0;
      --card-radius:16px;
    }

    *{box-sizing:border-box;margin:0;padding:0;font-family:"Inter",system-ui,Arial,sans-serif}

    body{
      min-height:100vh;
      background: url("images/sunrise.png") no-repeat center center/cover;
      transition: background 0.45s ease, color 0.3s ease;
      color:#111;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }
    body.dark{
      background: url("images/moon.jpg") no-repeat center center/cover;
      color:#eaeaea;
    }

    /* Header */
    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 32px;
      position: relative;
      z-index: 1000;
    }

    /* Logo Left */
    .logo-fixed {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
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
    .logo-fixed:hover { background: rgba(255,255,255,0.9); transform: scale(1.05); }

    body.dark .logo-fixed {
      background: rgba(34,34,34,0.7);
      border: 2px solid rgba(255,255,255,0.2);
      color: #eee;
    }
    body.dark .logo-fixed img {
      border: 2px solid #f062c0;
      background: #222;
    }

    /* Right Actions */
    .top-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .icon-btn {
      width:40px;height:40px;border-radius:50%;
      display:flex;align-items:center;justify-content:center;
      background:rgba(255,255,255,0.85);
      cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,0.06);
      transition:transform .18s, background .18s;
      font-size:18px;
    }
    .icon-btn:hover{ transform:translateY(-3px) }
    body.dark .icon-btn{ background: rgba(0,0,0,0.45); color:#fff; }

    /* Page Title */
    .page-title{
      text-align:left;
      padding: 0 32px 24px 32px;
    }
    .page-title h1{ font-size:48px; margin-bottom:6px; letter-spacing: -0.5px;}
    .page-title p{ color: rgba(0,0,0,0.6); }
    body.dark .page-title p{ color: rgba(255,255,255,0.7) }

    /* Layout */
    .wrap{
      display:flex;
      gap:28px;
      padding: 20px 32px 60px;
      align-items:flex-start;
      max-width:1200px;
      margin: 0 auto;
      width:100%;
    }

    /* Left card */
    .left-card{
      width:320px;
      background: rgba(255, 255, 255, 0.433);
      border-radius: var(--card-radius);
      padding:26px;
      border:2px solid rgba(78,159,244,0.15);
      box-shadow: 0 8px 30px rgba(0,0,0,0.06);
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:18px;
      animation: slideInLeft .7s ease both;
    }
    body.dark .left-card{
      background: rgba(24, 24, 24, 0.556);
      border:2px solid rgba(240,98,192,0.12);
      box-shadow: 0 8px 30px rgba(0,0,0,0.28);
    }

    .avatar{
      width:110px;height:110px;border-radius:50%;background:linear-gradient(180deg,#e6eefc,#cde6ff);
      display:flex;align-items:center;justify-content:center;font-size:42px;color:#0b2540;
      overflow:hidden;border:6px solid rgba(78,159,244,0.12);
      cursor:pointer;
    }
    body.dark .avatar{ background: linear-gradient(180deg,#2b2b34,#1e1e2a); color:#fff; border-color: rgba(240,98,192,0.12); }

    .user-name{ font-size:20px; font-weight:700; text-align:center }
    .user-sub{ font-size:15px; color: rgba(0,0,0,0.6); text-align:center}
    body.dark .user-sub{ color: rgba(255,255,255,0.65) }

    /* Right big form card */
    .right-card{
      flex:1;
      background: rgba(255, 255, 255, 0.493);
      border-radius: var(--card-radius);
      padding:26px;
      border:2px solid rgba(78,159,244,0.12);
      box-shadow: 0 10px 35px rgba(0,0,0,0.06);
      animation: slideInUp .7s ease both;
    }
    body.dark .right-card{
      background: rgba(20, 20, 20, 0.521);
      border:2px solid rgba(240,98,192,0.12);
      box-shadow: 0 10px 35px rgba(0,0,0,0.28);
    }

    .section-title{ font-weight:700; font-size:20px; margin-bottom:12px; }
    .form-grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:18px 24px;
    }

    label{ font-size:13px; font-weight:600; display:block; margin-bottom:6px; color:inherit }
    input[type="text"], input[type="email"], input[type="tel"]{
      width:100%;
      padding:12px 14px;
      border-radius:12px;
      border:2px solid rgba(78,159,244,0.3);
      background: transparent;
      outline:none;
      transition: box-shadow .18s, border-color .18s;
      color:inherit;
      min-height:48px;
    }
    input::placeholder{ color: rgba(0,0,0,0.35) }
    body.dark input::placeholder{ color: rgba(255,255,255,0.35) }
    input:focus{
      box-shadow: 0 6px 18px rgba(91,95,244,0.12);
      border-color: rgba(91,95,244,0.9);
    }

    .actions-row{
      display:flex;
      gap:12px;align-items:center;margin-top:18px;
    }

    .btn-primary{
      background: linear-gradient(90deg,var(--accent-2),var(--accent));
      color:#fff;padding:12px 22px;border-radius:12px;border:none;font-weight:700;
      cursor:pointer; box-shadow:0 8px 20px rgba(78,159,244,0.18);
      transition: transform .15s;
    }
    .btn-primary:hover{ transform: translateY(-3px) }

    .btn-outline{
      background:transparent;border:2px solid rgba(78,159,244,0.25);
      padding:10px 18px;border-radius:12px;cursor:pointer;font-weight:700;
      color:inherit;
    }
    .btn-outline:hover{ background: rgba(78,159,244,0.06) }

    .muted{ font-size:13px;color:rgba(0,0,0,0.6) }
    body.dark .muted{ color: rgba(255,255,255,0.65) }

    .toast{
      position:fixed; right:22px; bottom:22px; background: #222; color:#fff; padding:10px 14px; border-radius:10px;
      transform: translateY(20px); opacity:0; pointer-events:none; transition:all .25s;
      z-index:9999;
    }
    .toast.show{ transform: translateY(0); opacity:1; pointer-events:auto; }

    @keyframes slideInLeft{ from{opacity:0; transform:translateX(-25px)} to{opacity:1; transform:none} }
    @keyframes slideInUp{ from{opacity:0; transform:translateY(15px)} to{opacity:1; transform:none} }

    @media (max-width: 980px){
      .wrap{flex-direction:column;padding:20px}
      .left-card{width:100%;order:2}
      .right-card{order:1}
      .page-title h1{ font-size:36px }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="topbar">
    <a href="admin-dashboard.php" class="logo-fixed">
      <img src="images/new-logo.jpeg" alt="LeaveLogix Logo">
      <span>LeaveLogix</span>
    </a>
    <div class="top-actions">
      <div class="icon-btn" title="Profile" onclick="goProfile()">👤</div>
      <div id="darkToggle" class="icon-btn" title="Toggle dark mode">🌙</div>
      <div class="icon-btn" title="Logout" onclick="logout()">➡️</div>
    </div>
  </header>

  <section class="page-title">
    <h1>Profile</h1>
    <p class="muted">manage your profile & leave balance</p>
  </section>

  <main class="wrap" role="main">
    <!-- Left profile summary -->
    <aside class="left-card" aria-label="Profile summary">
      <div id="avatar" class="avatar" title="Click to change picture">
        <?php if (!empty($profile['avatar'])): ?>
          <img src="uploads/<?= htmlspecialchars($profile['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        <?php else: ?>
          <svg width="58" height="58" viewBox="0 0 24 24" fill="none" aria-hidden>
            <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5z" fill="#071830"/>
            <path d="M2 20c0-3.314 4.477-6 10-6s10 2.686 10 6v1H2v-1z" fill="#071830"/>
          </svg>
        <?php endif; ?>
      </div>
      <div class="user-name" id="displayName"><?= htmlspecialchars($profile['fullName'] ?? 'Your Name') ?></div>
      <div class="user-sub" id="displayDept"><?= htmlspecialchars($profile['department'] ?? 'Department') ?></div>
      <div class="user-sub" id="displayRole"><?= htmlspecialchars($profile['designation'] ?? 'Designation') ?></div>
    </aside>

    <!-- Right profile form -->
    <section class="right-card" aria-label="Profile form">
      <div class="section-title">Personal Details</div>
      <form id="profileForm" method="POST" enctype="multipart/form-data">
        <div class="form-grid">
          <div>
            <label for="fullName">Full name</label>
            <input id="fullName" name="fullName" type="text" placeholder="Full Name" value="<?= htmlspecialchars($profile['fullName'] ?? '') ?>" required />
          </div>
          <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" placeholder="you@company.com" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required />
          </div>
          <div>
            <label for="department">Department</label>
            <input id="department" name="department" type="text" placeholder="Engineering" value="<?= htmlspecialchars($profile['department'] ?? '') ?>" />
          </div>
          <div>
            <label for="designation">Designation</label>
            <input id="designation" name="designation" type="text" placeholder="Software Engineer" value="<?= htmlspecialchars($profile['designation'] ?? '') ?>" />
          </div>
          <div>
            <label for="avatarFile">Change Avatar</label>
            <input id="avatarFile" name="avatar" type="file" accept="image/*" />
          </div>
        </div>
        <div class="actions-row">
          <button type="submit" class="btn-primary" id="saveBtn">Save</button>
          <div style="flex:1"></div>
        </div>
      </form>
    </section>
  </main>

  <div id="toast" class="toast" role="status" aria-live="polite">
    <?= isset($message) ? $message : '' ?>
  </div>

    <script>
    const darkToggle = document.getElementById('darkToggle');
    const toast = document.getElementById('toast');

    const keys = ['fullName','email','phone','department','designation','location','daysLeft','avatarDataUrl'];
    function loadProfile(){
      keys.forEach(k=>{
        const el = document.getElementById(k);
        const val = localStorage.getItem(k);
        if(el && val !== null) el.value = val;
      });
      const name = localStorage.getItem('fullName') || 'Your Name';
      const dept = localStorage.getItem('department') || 'Department';
      const des = localStorage.getItem('designation') || 'Designation';
      document.getElementById('displayName').textContent = name;
      document.getElementById('displayDept').textContent = dept;
      document.getElementById('displayRole').textContent = des;
      const avatarData = localStorage.getItem('avatarDataUrl');
      if(avatarData){ setAvatarImage(avatarData); }
    }

    document.getElementById('profileForm').addEventListener('submit', (e)=>{
      e.preventDefault();
      const toSave = ['fullName','email','phone','department','designation','location'];
      toSave.forEach(k=>{
        const el = document.getElementById(k);
        if(el) localStorage.setItem(k, el.value || '');
      });
      loadProfile();
      showToast('Profile saved');
    });

    document.getElementById('changePassBtn').addEventListener('click', ()=>{
      const email = document.getElementById('email').value || '';
      if(!email) return showToast('Please enter email to change password');
      showToast('Password change link sent to ' + email);
    });

    function applyDarkFromStorage(){
      if(localStorage.getItem('darkMode') === 'enabled'){
        document.body.classList.add('dark');
        darkToggle.textContent = '☀️';
      } else {
        document.body.classList.remove('dark');
        darkToggle.textContent = '🌙';
      }
    }
    darkToggle.addEventListener('click', ()=>{
      if(document.body.classList.toggle('dark')){
        localStorage.setItem('darkMode','enabled'); darkToggle.textContent='☀️';
      } else {
        localStorage.setItem('darkMode','disabled'); darkToggle.textContent='🌙';
      }
    });

    function showToast(message){
      toast.textContent = message;
      toast.classList.add('show');
      setTimeout(()=> toast.classList.remove('show'), 2000);
    }

    function goHome(){ window.location.href = 'index.html'; }
    function goProfile(){ /* already here */ }
    function logout(){ localStorage.removeItem('auth'); window.location.href='admin-login.php'; }

    const avatarDiv = document.getElementById('avatar');
    const avatarFile = document.getElementById('avatarFile');
    avatarDiv.addEventListener('click', ()=> avatarFile.click() );
    avatarFile.addEventListener('change', (ev)=>{
      const f = ev.target.files && ev.target.files[0];
      if(!f) return;
      const reader = new FileReader();
      reader.onload = function(e){
        const data = e.target.result;
        localStorage.setItem('avatarDataUrl', data);
        setAvatarImage(data);
        showToast('Avatar updated');
      };
      reader.readAsDataURL(f);
    });
    function setAvatarImage(dataUrl){
      avatarDiv.innerHTML = '';
      const img = document.createElement('img');
      img.src = dataUrl;
      img.alt = 'avatar';
      img.style.width = '100%'; img.style.height = '100%'; img.style.objectFit = 'cover'; img.style.borderRadius = '50%';
      avatarDiv.appendChild(img);
    }

    applyDarkFromStorage();
    loadProfile();
  </script>
</body>
</html>
