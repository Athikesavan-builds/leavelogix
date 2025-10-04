<?php
// index.php
// Backend + frontend in one file for LeaveLogix landing page.

// (Optional) Start a session if needed for login state
session_start();

// If already logged in, you can auto-redirect user to dashboard:
if (isset($_SESSION['user_id'])) {
  header("Location: user/dashboard/dashboard.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LeaveLogix - Smart Leave Management</title>
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
      overflow: hidden;
    }

    /* Dark Mode Background */
    body.dark {
      background: url("images/moon.jpg") no-repeat center center fixed;
      background-size: cover;
    }

    .container {
      background: #fff;
      border-radius: 20px;
      padding: 40px;
      width: 90%;
      max-width: 600px;
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

/* Dark Mode for Logo */
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
      font-size: 2rem;
      margin-bottom: 15px;
      animation: slideUp 1s ease forwards;
    }

    h1 span {
      color: #f5b60a;
    }

    h1 .blue {
      color: #5b5ff4;
    }

    p {
      margin-bottom: 20px;
      color: #555;
      line-height: 1.6;
      animation: slideUp 1.2s ease forwards;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .btn {
      padding: 12px 22px;
      border-radius: 8px;
      border: 2px solid #5b5ff4;
      background: #fff;
      color: #5b5ff4;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
      animation: fadeIn 1.5s ease forwards;
    }

    .btn:hover {
      background: #5b5ff4;
      color: #fff;
      transform: scale(1.05);
      box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }

    /* Dark Mode Styles */
    body.dark .container {
      background: #2a2a2a;
      color: #eee;
    }

    body.dark p {
      color: #ccc;
    }

    body.dark .btn {
      background: #333;
      border: 2px solid #f062c0;
      color: #f062c0;
    }

    body.dark .btn:hover {
      background: #f062c0;
      color: #fff;
    }

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
      animation: fadeIn 1s ease forwards;
      z-index: 1000;
    }

    body.dark .dark-toggle {
      background: #444;
      color: #fff;
    }
  </style>
</head>
<body>

 <!-- Logo Top-left -->
<a href="index.php" class="logo-fixed">
  <img src="images/new-logo.jpeg" alt="LeaveLogix Logo">
  <span>LeaveLogix</span>
</a>

  <!-- Dark Mode Toggle -->
  <div class="dark-toggle" onclick="toggleDarkMode()">🌙</div>

  <div class="container">
    <h1>Make leave tracking <span>simple</span> & <span class="blue">beautiful</span></h1>
    <p>Approvals, calendar view, reports and team management — all in one place. 
    Clean UI and smooth animations for better user experience.</p>

    <!-- Redirect to login.php -->
    <button class="btn" onclick="window.location.href='user/auth/login.php'">Get Started</button>
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
  </script>

</body>
</html>
