<?php
// register.php
// Single-file backend + embedded HTML/CSS for the Register page.
// Config - change DB credentials as needed:
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'leavelogix';

// Helper: sanitize output for HTML
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Connect (without DB) to create DB if needed
$mysqli = new mysqli($db_host, $db_user, $db_pass);
if ($mysqli->connect_errno) {
    die("DB connect failed: (" . $mysqli->connect_errno . ") " . h($mysqli->connect_error));
}

// Create database if not exists
$createDbSql = "CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$mysqli->query($createDbSql)) {
    die("Failed to create database: " . h($mysqli->error));
}

if (!$mysqli->select_db($db_name)) {
    die("Failed to select database: " . h($mysqli->error));
}

// Create tables (if not exists)
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
    `designation` VARCHAR(150) NULL,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'employee',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY (`email`),
    KEY (`department_id`),
    CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
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
    KEY (`type_id`),
    CONSTRAINT `fk_leave_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_leave_type` FOREIGN KEY (`type_id`) REFERENCES `leave_types`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($tableQueries as $sql) {
    if (!$mysqli->query($sql)) {
        die("Table creation failed: " . h($mysqli->error));
    }
}

// Ensure some default leave types exist
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

// Variables for form sticky values & messages
$fullname = '';
$department = '';
$designation = '';
$email = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize POST
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    $designation = isset($_POST['designation']) ? trim($_POST['designation']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Basic validation
    if (strlen($fullname) < 2) $errors[] = "Full name must be at least 2 characters.";
    if (strlen($department) < 1) $errors[] = "Department is required.";
    if (strlen($designation) < 2) $errors[] = "Designation is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";

    if (empty($errors)) {
        $mysqli->begin_transaction();
        try {
            // Ensure department exists or insert and get id
            $dept_id = null;
            $stmt = $mysqli->prepare("SELECT id FROM departments WHERE name = ? LIMIT 1");
            $stmt->bind_param('s', $department);
            $stmt->execute();
            $stmt->bind_result($did);
            if ($stmt->fetch()) $dept_id = $did;
            $stmt->close();

            if ($dept_id === null) {
                $insDept = $mysqli->prepare("INSERT INTO departments (name) VALUES (?)");
                $insDept->bind_param('s', $department);
                if (!$insDept->execute()) throw new Exception("Failed to insert department: " . $insDept->error);
                $dept_id = $insDept->insert_id;
                $insDept->close();
            }

            // Check email uniqueness
            $chk = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk->bind_param('s', $email);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $chk->close();
                $mysqli->rollback();
                $errors[] = "Email is already registered.";
            } else {
                $chk->close();
                // Insert user
                $pw_hash = password_hash($password, PASSWORD_DEFAULT);
                $insUser = $mysqli->prepare("INSERT INTO users (fullname, department_id, designation, email, password_hash) VALUES (?, ?, ?, ?, ?)");
                $insUser->bind_param('sisss', $fullname, $dept_id, $designation, $email, $pw_hash);
                if (!$insUser->execute()) {
                    throw new Exception("Insert user failed: " . $insUser->error);
                }
                $insUser->close();

                $mysqli->commit();
                $success = "Account created successfully — you can <a href='login.php'>login</a> now.";
                // Clear sensitive fields
                $password = '';
                $fullname = $department = $designation = $email = '';
            }
        } catch (Exception $ex) {
            $mysqli->rollback();
            $errors[] = "Registration failed: " . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
    body {
      background: url('../../images/sunrise.png') no-repeat center center/cover;
      min-height: 100vh; display:flex; justify-content:center; align-items:center;
      transition: background 0.4s;
    }
    .register-box {
      background:#fff; border-radius:20px; padding:40px; width:100%; max-width:650px;
      box-shadow:0 12px 35px rgba(0,0,0,0.15); animation: fadeInUp 1s ease forwards;
    }
    @keyframes fadeInUp { from { opacity:0; transform:translateY(40px);} to{opacity:1; transform:translateY(0);} }
    .logo-fixed {
      position:absolute; top:20px; left:20px; cursor:pointer; z-index:1000; display:flex; align-items:center; gap:8px;
      padding:6px 12px; border:2px solid rgba(0,0,0,0.15); border-radius:12px; background:rgba(255,255,255,0.7);
      backdrop-filter: blur(6px); transition:0.3s; font-weight:600; text-decoration:none; color:#333;
    }
    .logo-fixed:hover { background: rgba(255,255,255,0.9); transform:scale(1.05); }
    .logo-fixed img { height:40px; width:40px; border-radius:50%; border:2px solid #5b5ff4; padding:3px; background:#fff; }
    body.dark .logo-fixed { background: rgba(34,34,34,0.7); border:2px solid rgba(255,255,255,0.2); color:#eee; }
    body.dark .logo-fixed img { border:2px solid #f062c0; background:#222; }
    h2 { font-size:2rem; margin-bottom:8px; }
    p { color:#666; margin-bottom:25px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
    .form-group { display:flex; flex-direction:column; }
    .form-group label { margin-bottom:6px; font-size:0.9rem; font-weight:600; }
    .form-group input { padding:12px; border-radius:10px; border:1px solid #ccc; transition:0.3s; }
    .form-group input:focus { border-color:#4e2cd6; box-shadow:0 0 6px rgba(78,44,214,0.3); outline:none; }
    .btn { width:100%; padding:14px; border:none; border-radius:10px; background:#4e2cd6; color:#fff; font-weight:bold; cursor:pointer; transition:0.3s; }
    .btn:hover { background:#f062c0; transform:scale(1.05); box-shadow:0 6px 15px rgba(0,0,0,0.2); }
    .footer-text { text-align:center; margin-top:15px; }
    .footer-text a { color:#4e2cd6; text-decoration:none; font-weight:600; }
    .footer-text a:hover { text-decoration:underline; }
    body.dark { background: url('../../images/moon.jpg') no-repeat center center/cover; }
    body.dark .register-box { background:#242424; color:#eee; }
    body.dark .form-group input { background:#333; border:1px solid #555; color:#eee; }
    body.dark .btn { background:#f062c0; }
    body.dark .btn:hover { background:#d84ca9; }
    .dark-toggle { position:absolute; top:20px; right:30px; background:#fff; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 3px 10px rgba(0,0,0,0.2); transition:0.3s; font-size:1.2rem; }
    body.dark .dark-toggle { background:#444; color:#fff; }
    .messages { margin-bottom: 16px; }
    .messages .error { background:#ffe6e6; color:#900; padding:10px; border-radius:8px; margin-bottom:8px; }
    .messages .success { background:#e8fff0; color:#097a38; padding:10px; border-radius:8px; }
  </style>
</head>
<body>

<a href="../../index.php" class="logo-fixed">
  <img src="../../images/new-logo.jpeg" alt="LeaveLogix Logo">
  <span>LeaveLogix</span>
</a>

<div class="dark-toggle" onclick="toggleDarkMode()">🌙</div>

<div class="register-box">
  <h2>Create account</h2>
  <p>Sign up to start using LeaveLogix.</p>

  <?php if (!empty($errors) || $success): ?>
    <div class="messages">
      <?php foreach ($errors as $err): ?>
        <div class="error"><?= h($err) ?></div>
      <?php endforeach; ?>
      <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <form action="register.php" method="POST" novalidate>
    <div class="form-grid">
      <div class="form-group">
        <label>Full name</label>
        <input type="text" name="fullname" placeholder="Your Name" required value="<?= h($fullname) ?>">
      </div>
      <div class="form-group">
        <label>Department</label>
        <input type="text" name="department" placeholder="Engineering" required value="<?= h($department) ?>">
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group">
        <label>Designation</label>
        <input type="text" name="designation" placeholder="Software Engineer" required value="<?= h($designation) ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="you@company.com" required value="<?= h($email) ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Choose a strong password" required>
    </div>

    <button type="submit" class="btn">Create account</button>

    <div class="footer-text">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </form>
</div>

<script>
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
