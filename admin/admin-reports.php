<?php
// admin-reports.php

// DB connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'leavelogix'; // your DB name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch leave data with user info
$sql = "SELECT l.id, u.fullname AS name, u.designation AS designation, l.reason, l.status, l.from_date, l.to_date,
        DATEDIFF(l.to_date, l.from_date) + 1 AS days
        FROM leave_requests l
        JOIN users u ON l.user_id = u.id
        ORDER BY l.id DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports - LeaveLogix (Admin)</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }
    body { min-height:100vh; background:url('../images/sunrise.png') no-repeat center center/cover; transition: background 0.5s ease, color 0.3s ease; color:#222; padding:80px 20px; }
    body.dark { background:url('../images/moon.jpg') no-repeat center center/cover; color:#eee; }
    .logo-fixed { position:absolute; top:20px; left:20px; display:flex; align-items:center; gap:8px; padding:6px 12px; border:2px solid rgba(0,0,0,0.15); border-radius:12px; background:rgba(255,255,255,0.7); backdrop-filter:blur(6px); font-weight:600; text-decoration:none; color:#333; }
    .logo-fixed img { height:40px; width:40px; border-radius:50%; border:2px solid #5b5ff4; padding:3px; background:#fff; }
    body.dark .logo-fixed { background:rgba(34,34,34,0.7); border:2px solid rgba(255,255,255,0.2); color:#eee; }
    body.dark .logo-fixed img { border:2px solid #f062c0; background:#222; }
    #darkToggle { position:absolute; top:20px; right:20px; cursor:pointer; font-size:22px; }
    h1 { font-size:26px; font-weight:700; margin-bottom:10px; }
    p.subtitle { font-size:14px; margin-bottom:20px; }
    .reports-container { display:flex; flex-direction:column; gap:20px; }
    .chart-box { padding:20px; border:1.5px solid #007bff; border-radius:12px; background:rgba(255,255,255,0.75); }
    body.dark .chart-box { background: rgba(34,34,34,0.7); border-color:#f062c0; }
    table { width:100%; border-collapse:collapse; margin-top:10px; }
    th, td { padding:10px; border:1px solid #ccc; text-align:center; font-size:14px; }
    th { background:#007bff; color:#fff; }
    body.dark th { background:#f062c0; }
    @media(max-width:768px){
      table, thead, tbody, th, td, tr { display:block; }
      thead { display:none; }
      tr { margin-bottom:12px; padding:10px; border-radius:8px; background:rgba(255,255,255,0.1); }
      td { padding:8px; display:flex; justify-content:space-between; }
      td::before { content: attr(data-label); font-weight:600; }
      body.dark tr { background:rgba(0,0,0,0.8); }
    }
    .export-btn { background:#ffc107; border:none; padding:8px 15px; font-weight:600; border-radius:6px; cursor:pointer; float:right; margin-bottom:10px; }
    .export-btn:hover { background:#e0a800; }
  </style>
</head>
<body>
  <a href="dashboard.php" class="logo-fixed">
    <img src="../images/new-logo.jpeg" alt="LeaveLogix Logo">
    <span>LeaveLogix</span>
  </a>
  <span id="darkToggle">🌙</span>

  <h1>Reports (Admin)</h1>
  <p class="subtitle">Organization filtered reports with export options</p>

  <div class="reports-container">
    <div class="chart-box">
      <button class="export-btn" onclick="exportTable()">Export PDF</button>
      <h3>Detailed table</h3>
      <table id="leaveTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Designation</th>
            <th>Reason</th>
            <th>Status</th>
            <th>From Date</th>
            <th>To Date</th>
            <th>Days</th>
          </tr>
        </thead>
        <tbody>
        <?php if($result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td data-label="ID"><?= $row['id'] ?></td>
              <td data-label="Name"><?= htmlspecialchars($row['name']) ?></td>
              <td data-label="Designation"><?= htmlspecialchars($row['designation']) ?></td>
              <td data-label="Reason"><?= htmlspecialchars($row['reason']) ?></td>
              <td data-label="Status"><?= htmlspecialchars($row['status']) ?></td>
              <td data-label="From Date"><?= htmlspecialchars($row['from_date']) ?></td>
              <td data-label="To Date"><?= htmlspecialchars($row['to_date']) ?></td>
              <td data-label="Days"><?= $row['days'] ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="9">No records found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<script>
  // Dark Mode Toggle
  const toggle = document.getElementById("darkToggle");
  if(localStorage.getItem("darkMode") === "enabled"){
      document.body.classList.add("dark");
      toggle.textContent = "☀️";
  }
  toggle.addEventListener("click", () => {
      const isDark = document.body.classList.toggle("dark");
      localStorage.setItem("darkMode", isDark ? "enabled" : "disabled");
      toggle.textContent = isDark ? "☀️" : "🌙";
  });

  // Export PDF
  function exportTable() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text("Admin Leave Reports", 14, 15);
    doc.autoTable({ html: "#leaveTable", startY: 25 });
    doc.save("Admin_Leave_Report.pdf");
  }
</script>
</body>
</html>
<?php $conn->close(); ?>