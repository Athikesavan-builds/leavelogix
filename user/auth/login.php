<?php
// login.php - single-file login page + backend
// Edit these DB settings to match your environment:
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'leavelogix';

// Redirect targets
$redirect_on_success = '../dashboard/dashboard.php';
$redirect_on_failure = 'login.php';

// Helper: escape for HTML
function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// --- Ensure DB exists and schema parity with register.php ---
$mysqli = new mysqli ($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("DB connect failed: (" . $mysqli->connect_errno . ") " . h($mysqli->connect_error));
}
$createDbSql = "CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$mysqli->query($createDbSql)) {
    die("Failed to create database: " . h($mysqli->error));
}
if (!$mysqli->select_db($db_name)) {
    die("Failed to select database: " . h($mysqli->error));
}

$tableQueries = [
"CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fullname` VARCHAR(255) NOT NULL,
    `department_id` INT UNSIGNED NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'employee',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`email`),
    KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `leave_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `default_days` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

"CREATE TABLE IF NOT EXISTS `leave_requests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `type_id` INT UNSIGNED NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY (`user_id`),
    KEY (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($tableQueries as $sql) {
    if (!$mysqli->query($sql)) {
        die("Table creation failed: " . h($mysqli->error));
    }
}

// Insert default leave types if missing
$defaultLeaveTypes = [
    ['Casual Leave', 12],
    ['Sick Leave', 10],
    ['Earned Leave', 15]
];
$insLt = $mysqli->prepare("INSERT IGNORE INTO leave_types (name, default_days) VALUES (?, ?)");
foreach ($defaultLeaveTypes as $lt) {
    $insLt->bind_param('si', $lt[0], $lt[1]);
    $insLt->execute();
}
$insLt->close();

// --- Handle login POST ---
$errors = [];
$posted_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!filter_var($posted_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email.";
    }
    if (strlen($password) < 1) {
        $errors[] = "Please enter your password.";
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare("SELECT id, fullname, department_id, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) {
            $errors[] = "Server error (prepare).";
        } else {
            $stmt->bind_param('s', $posted_email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $errors[] = "Invalid credentials.";
            } else {
                $stmt->bind_result($user_id, $fullname, $department_id, $db_email, $password_hash, $role);
                $stmt->fetch();
                if (!password_verify($password, $password_hash)) {
                    $errors[] = "Invalid credentials.";
                } else {
                    // Rehash if needed
                    if (password_needs_rehash($password_hash, PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $u = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ? LIMIT 1");
                        if ($u) {
                            $u->bind_param('si', $newHash, $user_id);
                            $u->execute();
                            $u->close();
                        }
                    }

                    // Start secure session
                    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
                    $cookieParams = session_get_cookie_params();
                    session_set_cookie_params([
                        'lifetime' => $cookieParams['lifetime'],
                        'path' => $cookieParams['path'],
                        'domain' => $cookieParams['domain'],
                        'secure' => $secure,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                    session_start();
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['email'] = $db_email;
                    $_SESSION['role'] = $role;
                    $_SESSION['department_id'] = $department_id;
                    $_SESSION['logged_in_at'] = time();

                    // Redirect to dashboard
                    header("Location: " . $redirect_on_success);
                    exit;
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

    body {
      background: url('../../images/sunrise.png') no-repeat center center/cover;
      min-height: 100vh; display: flex; justify-content: center; align-items: center; transition: background 0.4s;
    }

    .login-container {
      background: #ffffff63; border-radius: 20px; padding: 40px; width: 90%; max-width: 450px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); animation: fadeIn 1s ease forwards;
    }

    @keyframes fadeIn { from { opacity: 0; transform: scale(0.95);} to { opacity: 1; transform: scale(1); } }

    /* Logo Top-left */
    .logo-fixed { position: absolute; top: 20px; left: 20px; cursor: pointer; z-index: 1000; display:flex; align-items:center; gap:8px; padding:6px 12px; border:2px solid rgba(0,0,0,0.15); border-radius:12px; background:rgba(255,255,255,0.7); backdrop-filter: blur(6px); transition:0.3s; font-weight:600; text-decoration:none; color:#333; }
    .logo-fixed:hover { background: rgba(255,255,255,0.9); transform: scale(1.05); }
    .logo-fixed img { height:40px; width:40px; border-radius:50%; border:2px solid #5b5ff4; padding:3px; background:#fff; }
    body.dark .logo-fixed { background: rgba(34,34,34,0.7); border:2px solid rgba(255,255,255,0.2); color:#eee; }
    body.dark .logo-fixed img { border:2px solid #f062c0; background:#222; }

    h2 { text-align:center; margin-bottom:20px; font-size:1.8rem; animation: slideUp 1s ease forwards; }
    @keyframes slideUp { from{opacity:0; transform:translateY(30px);} to{opacity:1; transform:translateY(0);} }

    .form-group { margin-bottom: 15px; animation: fadeSlide 1s ease forwards; }
    .form-group:nth-child(1) { animation-delay:0.2s; }
    .form-group:nth-child(2) { animation-delay:0.4s; }
    @keyframes fadeSlide { from{opacity:0; transform:translateY(20px);} to{opacity:1; transform:translateY(0);} }

    label { display:block; margin-bottom:5px; font-size:0.9rem; font-weight:600; }
    input { width:100%; padding:12px; border-radius:8px; border:1px solid #ccc; transition:0.3s; }
    input:focus { border-color:#5b5ff4; box-shadow:0 0 8px rgba(91,95,244,0.4); outline:none; }

    .btn { width:100%; padding:12px; border-radius:8px; border:none; background:#5b5ff4; color:#fff; font-weight:bold; cursor:pointer; margin-top:10px; transition:0.3s; animation:fadeSlide 1s ease forwards; animation-delay:0.6s; }
    .btn:hover { background:#3d3de0; transform:scale(1.05); box-shadow:0 6px 15px rgba(0,0,0,0.2); }

    p { text-align:center; margin-top:15px; font-size:0.9rem; }
    a { color:#5b5ff4; text-decoration:none; } a:hover { text-decoration:underline; }

    body.dark { background: url('../../images/moon.jpg') no-repeat center center/cover; }
    body.dark .login-container { background:#2a2a2a79; color:#eee; }
    body.dark input { background:#333; color:#fff; border:1px solid #555; }
    body.dark .btn { background:#f062c0; }
    body.dark .btn:hover { background:#d84ca9; }

    .dark-toggle { position:absolute; top:20px; right:30px; background:#fff; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 3px 10px rgba(0,0,0,0.2); font-size:1.2rem; }
    body.dark .dark-toggle { background:#444; color:#fff; }

    /* messages */
    .messages { margin-bottom: 12px; }
    .messages .error { background:#ffe6e6; color:#900; padding:10px; border-radius:8px; }
  </style>
</head>
<body>

 <!-- Logo Top-left (click = homepage) -->
<a href="../../index.php" class="logo-fixed">
  <img src="../../images/new-logo.jpeg" alt="LeaveLogix Logo">
  <span>LeaveLogix</span>
</a>

  <!-- Dark Mode Toggle -->
  <div class="dark-toggle" onclick="toggleDarkMode()">🌙</div>

  <div class="login-container">
    <h2>Login</h2>

    <?php if (!empty($errors)): ?>
      <div class="messages">
        <?php foreach ($errors as $err): ?>
          <div class="error"><?= h($err) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST" novalidate>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="you@company.com" required value="<?= h($posted_email) ?>">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="********" required>
      </div>
      <button type="submit" class="btn">login</button>
    </form>

   
    <p>Don't have an account? <a href="register.php">Register</a></p>
  </div>

  <script>
    // Dark Mode
    if (localStorage.getItem("darkMode") === "enabled") {
      document.body.classList.add("dark");
      document.querySelector(".dark-toggle").textContent = "☀️";
    }

    function toggleDarkMode() {
      let toggleBtn = document.querySelector(".dark-toggle");
      if (document.body.classList.contains("dark")) {
        document.body.classList.remove("dark");
        localStorage.setItem("darkMode", "disabled");
        toggleBtn.textContent = "🌙";
      } else {
        document.body.classList.add("dark");
        localStorage.setItem("darkMode", "enabled");
        toggleBtn.textContent = "☀️";
      }
    }
  </script>

</body>
</html>
