<?php
// --- CONFIG ---
$db_host = "localhost";
$db_user = "root";   // change if needed
$db_pass = "";       // change if needed
$db_name = "leavelogix";

// Connect to DB
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

$success = $error = "";

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname   = trim($_POST['fullname']);
    $email      = trim($_POST['email']);
    $designation= trim($_POST['designation']);
    $orgType    = $_POST['orgType'];
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm'];

    // Validation
    if ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare ("SELECT id FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            // Hash password
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);

            // Insert new admin
            $stmt = $conn->prepare("INSERT INTO admin (fullname, email, designation, org_type, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $fullname, $email, $designation, $orgType, $hashedPass);

            if ($stmt->execute()) {
                $success = "✅ Registration successful! Redirecting to login...";
                header("refresh:2;url=admin-login.php");
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Register - LeaveLogix</title>
  <style>
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
    }

    body {
      background: url("images/sunrise.png") no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      transition: background 0.4s;
    }

    /* Dark Mode Background */
    body.dark {
      background: url("images/moon.jpg") no-repeat center center fixed;
      background-size: cover;
    }

    .container {
      background: #ffffff7a;
      border-radius: 20px;
      padding: 40px;
      width: 90%;
      max-width: 500px;
      text-align: center;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: background 0.3s, color 0.3s;
      animation: fadeIn 1s ease forwards;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }

    /* Logo Top-left */
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
    .logo-fixed:hover {
      background: rgba(255,255,255,0.9);
      transform: scale(1.05);
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

    h1 {
      font-size: 1.8rem;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 18px;
      text-align: left;
    }

    .form-group label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
      color: #333;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
      transition: 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus {
      border-color: #5b5ff4;
      outline: none;
      box-shadow: 0 0 6px rgba(91,95,244,0.4);
    }

    .btn {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 2px solid #5b5ff4;
      background: #5b5ff4;
      color: #fff;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
    }

    .btn:hover {
      background: #fff;
      color: #5b5ff4;
      transform: scale(1.05);
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }

    p {
      margin-top: 15px;
      color: #555;
    }

    p a {
      color: #5b5ff4;
      text-decoration: none;
      font-weight: 600;
    }

    p a:hover {
      text-decoration: underline;
    }

    /* Dark Mode Styles */
    body.dark .container {background: #2a2a2a88;color: #eee;}
    body.dark .form-group label {color: #ccc;}
    body.dark .form-group input,
    body.dark .form-group select {
      background: #333;border: 1px solid #555;color: #fff;
    }
    body.dark .btn {background: #f062c0;border: 2px solid #f062c0;}
    body.dark .btn:hover {background: transparent;color: #f062c0;}
    body.dark p {color: #bbb;}
    body.dark p a {color: #f062c0;}

    /* Toggle Button */
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
    body.dark .dark-toggle {background: #444;color: #fff;}
  </style>
</head>
<body>

  <!-- Logo Top-left -->
  <a href="index.html" class="logo-fixed">
    <img src="images/new-logo.jpeg" alt="LeaveLogix Logo">
    <span>LeaveLogix</span>
  </a>

  <!-- Dark Mode Toggle -->
  <div class="dark-toggle" onclick="toggleDarkMode()">🌙</div>

  <div class="container">
    <h1>Register</h1>

    <?php if ($error): ?>
      <p style="color:red;font-weight:600;"><?= $error ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
      <p style="color:green;font-weight:600;"><?= $success ?></p>
    <?php endif; ?>

    <form onsubmit="return validateForm()" method="POST" action="">
      <div class="form-group">
        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname" placeholder="Enter full name" required>
      </div>
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter email" required>
      </div>
      <div class="form-group">
        <label for="designation">Designation</label>
        <input type="text" id="designation" name="designation" placeholder="Enter designation" required>
      </div>
      <div class="form-group">
        <label for="orgType">Organization Type</label>
        <select id="orgType" name="orgType" required>
          <option value="">-- Select Organization --</option>
          <option value="College">College</option>
          <option value="Company">Company</option>
        </select>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Create password" required>
      </div>
      <div class="form-group">
        <label for="confirm">Confirm Password</label>
        <input type="password" id="confirm" name="confirm" placeholder="Confirm password" required>
      </div>
      <button type="submit" class="btn">Register</button>
    </form>

    <p>Already have an account? <a href="admin-login.php">Login</a></p>
  </div>

  <script>
     // Load saved dark mode state
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

    // Validation + Save orgType
    function validateForm() {
      let pass = document.getElementById("password").value;
      let confirm = document.getElementById("confirm").value;
      let org = document.getElementById("orgType").value;

      if (pass !== confirm) {
        alert("Passwords do not match!");
        return false;
      }
      if (org === "") {
        alert("Please select organization type!");
        return false;
      }

      // Save orgType for manage-users.html
      localStorage.setItem("orgType", org);

      return true;
    }
  </script>
</body>
</html>
