<?php
// admin-login.php
session_start();

// Database config
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'leavelogix';

// Connect DB
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle login form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT id, fullname, email, designation, org_type, password FROM admin WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();

            if (password_verify($password, $row['password'])) {
                // ✅ Success: create session
                $_SESSION['admin_id']   = $row['id'];
                $_SESSION['admin_name'] = $row['fullname'];
                $_SESSION['admin_email'] = $row['email'];
                $_SESSION['designation'] = $row['designation'];
                $_SESSION['org_type'] = $row['org_type'];

                header("Location: admin-dashboard.php");
                exit;
            } else {
                $error = "❌ Invalid password!";
            }
        } else {
            $error = "❌ Admin not found!";
        }
    } else {
        $error = "⚠️ Please fill all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
    body{ min-height:100vh; display:flex; align-items:center; justify-content:center;
          background:url('images/sunrise.png') no-repeat center center/cover;
          transition:background 0.5s ease,color 0.3s ease; color:#222; }
    body.dark{background:url('images/moon.jpg') no-repeat center center/cover;color:#eee;}
    .logo-fixed{position:absolute;top:20px;left:20px;display:flex;align-items:center;gap:8px;
        padding:6px 12px;border:2px solid rgba(0,0,0,0.15);border-radius:12px;
        background:rgba(255,255,255,0.7);backdrop-filter:blur(6px);
        font-weight:600;text-decoration:none;color:#333;transition:0.3s;}
    .logo-fixed img{height:40px;width:40px;border-radius:50%;border:2px solid #5b5ff4;
        padding:3px;background:#fff;}
    body.dark .logo-fixed{background:rgba(34,34,34,0.7);border:2px solid rgba(255,255,255,0.2);color:#eee;}
    body.dark .logo-fixed img{border:2px solid #f062c0;background:#222;}
    #darkToggle{position:absolute;top:20px;right:20px;cursor:pointer;font-size:22px;}
    .login-box{background:rgba(255,255,255,0.65);padding:40px;border-radius:16px;
        box-shadow:0 4px 12px rgba(0,0,0,0.2);width:100%;max-width:400px;}
    body.dark .login-box{background:rgba(34,34,34,0.7);}
    .login-box h2{text-align:center;margin-bottom:20px;font-weight:700;color:#007bff;}
    body.dark .login-box h2{color:#f062c0;}
    .input-group{margin-bottom:15px;}
    .input-group label{display:block;font-size:14px;margin-bottom:6px;font-weight:600;}
    .input-group input{width:100%;padding:10px;border:1.5px solid #ccc;border-radius:8px;}
    .input-group input:focus{border-color:#007bff;}
    body.dark .input-group input{background:#222;color:#eee;border-color:#555;}
    .btn{width:100%;padding:10px;border:none;border-radius:8px;background:#007bff;
        color:#fff;font-weight:600;cursor:pointer;}
    .btn:hover{background:#0056b3;}
    body.dark .btn{background:#f062c0;}
    body.dark .btn:hover{background:#d84fae;}
    .extra{text-align:center;margin-top:15px;font-size:14px;}
    .extra a{color:#007bff;font-weight:600;text-decoration:none;}
    body.dark .extra a{color:#f062c0;}
    .error{color:red;text-align:center;margin-bottom:10px;font-weight:600;}
  </style>
</head>
<body>
  <a href="index.html" class="logo-fixed">
    <img src="images/new-logo.jpeg" alt="LeaveLogix Logo"><span>LeaveLogix</span>
  </a>
  <span id="darkToggle">🌙</span>

  <div class="login-box">
    <h2>Admin Login</h2>
    <?php if (!empty($error)): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST" action="">
      <div class="input-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
    <div class="extra">
      <p>Don’t have an account? <a href="admin-register.php">Register</a></p>
    </div>
  </div>

  <script>
    const toggle=document.getElementById("darkToggle");
    if(localStorage.getItem("darkMode")==="enabled"){
        document.body.classList.add("dark");toggle.textContent="☀️";
    }
    toggle.addEventListener("click",()=>{
        if(document.body.classList.contains("dark")){
            document.body.classList.remove("dark");
            localStorage.setItem("darkMode","disabled");
            toggle.textContent="🌙";
        } else {
            document.body.classList.add("dark");
            localStorage.setItem("darkMode","enabled");
            toggle.textContent="☀️";
        }
    });
  </script>
</body>
</html>
