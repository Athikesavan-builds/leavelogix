<?php
// manage-users.php
session_start();

// DB Config
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "leavelogix";

// Connect DB
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: manage-users.php");
    exit;
}

// Handle Add/Edit User
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id'] ?? "";
    $fullname = $_POST['fullname'];
    $role = $_POST['role'];
    $department_id = $_POST['department_id'];
    $designation = $_POST['designation'];
    $email = $_POST['email'];

    if ($id) {
        // Update existing user
        $stmt = $conn->prepare("UPDATE users SET fullname=?, role=?, department_id=?, designation=?, email=? WHERE id=?");
        $stmt->bind_param("ssissi", $fullname, $role, $department_id, $designation, $email, $id);
        $stmt->execute();
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users(fullname, role, department_id, designation, email, password_hash, created_at) VALUES (?,?,?,?,?, '', NOW())");
        $stmt->bind_param("ssiss", $fullname, $role, $department_id, $designation, $email);
        $stmt->execute();
    }

    header("Location: manage-users.php");
    exit;
}

// Fetch Users
$result = $conn->query("SELECT * FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
    body{min-height:100vh;background:url('images/sunrise.png') no-repeat center center/cover;transition:background 0.4s ease,color 0.3s ease;color:#222;}
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
    .title-container h1{font-size:26px;font-weight:700;background:#0ebfff;color:#fff;display:inline-block;padding:6px 16px;border-radius:8px;}
    .table-container{margin:20px auto;width:90%;overflow-x:auto;}
    table{width:100%;border-collapse:collapse;font-size:15px;}
    thead{background:#007bff;color:#ffffff;}
    thead th{padding:12px;text-align:left;}
    tbody td{padding:10px;border-bottom:1px solid #ffffff;}
    tbody tr:nth-child(even){background:#f9f9f900;}
    tbody tr:nth-child(odd){background:#ffffff00;}
    tbody tr:hover{background:rgba(0,0,0,0.07);}
    body.dark thead{background:#f062c0;}
    body.dark tbody tr:hover{background:rgba(0, 0, 0, 0.09);}
    .btn{padding:5px 12px;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
    .btn-add{background:#28a745;color:#fff;margin-bottom:12px;}
    .btn-edit{background:#ffc107;color:#222;}
    .btn-del{background:#dc3545;color:#fff;}
    @media(max-width:768px){
      table, thead, tbody, th, td, tr{display:block;}
      thead{display:none;}
      tr{margin-bottom:12px;padding:10px;border-radius:8px;}
      td{padding:8px;display:flex;justify-content:space-between;}
      td::before{content:attr(data-label);font-weight:600;}
      body.dark tr{background:rgba(0, 0, 0, 0.953);}
    }
    /* Modal */
    #userForm{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);
      justify-content:center;align-items:center;}
    #userForm form{background:#fff;padding:20px;border-radius:10px;min-width:300px;}
  </style>
</head>
<body>
  <header>
    <a href="admin-dashboard.php" class="logo-fixed">
      <img src="images/new-logo.jpeg" alt="LeaveLogix Logo">
      <span>LeaveLogix</span>
    </a>
    <div class="actions">
      <div id="darkToggle" class="icon-btn" title="Toggle Dark Mode">🌙</div>
    </div>
  </header>

  <div class="title-container">
    <h1>Manage Users</h1>
  </div>

  <div class="table-container">
    <button class="btn btn-add" onclick="openForm()">+ Add User</button>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Name</th><th>Role</th><th>Department ID</th><th>Designation</th><th>Email</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row=$result->fetch_assoc()): ?>
        <tr>
          <td data-label="ID"><?= $row['id'] ?></td>
          <td data-label="Name"><?= htmlspecialchars($row['fullname']) ?></td>
          <td data-label="Role"><?= $row['role'] ?></td>
          <td data-label="Department ID"><?= $row['department_id'] ?></td>
          <td data-label="Designation"><?= htmlspecialchars($row['designation']) ?></td>
          <td data-label="Email"><?= $row['email'] ?></td>
          <td data-label="Actions">
            <button class="btn btn-edit" onclick="openForm(
              <?= $row['id'] ?>,
              '<?= htmlspecialchars($row['fullname']) ?>',
              '<?= $row['role'] ?>',
              '<?= $row['department_id'] ?>',
              '<?= htmlspecialchars($row['designation']) ?>',
              '<?= $row['email'] ?>'
            )">Edit</button>
            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this user?')">
              <button class="btn btn-del">Delete</button>
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Popup Form -->
  <div id="userForm">
    <form method="POST" onclick="event.stopPropagation()">
      <input type="hidden" name="id" id="userId">
      <label>Full Name</label><br><input type="text" name="fullname" id="fullname" required><br><br>
      <label>Role</label><br>
      <select name="role" id="role">
        <option value="Employee">Employee</option>
        <option value="Student">Student</option>
      </select><br><br>
      <label>Department ID</label><br><input type="text" name="department_id" id="department_id" required><br><br>
      <label>Designation</label><br><input type="text" name="designation" id="designation" required><br><br>
      <label>Email</label><br><input type="email" name="email" id="email" required><br><br>
      <button type="submit" class="btn btn-add">Save</button>
      <button type="button" class="btn btn-del" onclick="closeForm()">Cancel</button>
    </form>
  </div>

  <script>
    const toggle=document.getElementById("darkToggle");
    if(localStorage.getItem("darkMode")==="enabled"){document.body.classList.add("dark");toggle.textContent="☀️";}
    toggle.addEventListener("click",()=>{if(document.body.classList.toggle("dark")){localStorage.setItem("darkMode","enabled");toggle.textContent="☀️";}else{localStorage.setItem("darkMode","disabled");toggle.textContent="🌙";}});

    function openForm(id="",fullname="",role="Employee",dept_id="",designation="",email=""){
      const modal=document.getElementById("userForm");
      modal.style.display="flex";
      document.getElementById("userId").value=id;
      document.getElementById("fullname").value=fullname;
      document.getElementById("role").value=role;
      document.getElementById("department_id").value=dept_id;
      document.getElementById("designation").value=designation;
      document.getElementById("email").value=email;
    }
    function closeForm(){document.getElementById("userForm").style.display="none";}
    // Close modal if clicked outside form
    document.getElementById("userForm").addEventListener("click",()=>closeForm());
  </script>
</body>
</html>
