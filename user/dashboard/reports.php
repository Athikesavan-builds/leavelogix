<?php
// reports.php (User Side)
session_start();
$user_id = $_SESSION['user_id'] ?? 0;

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'leavelogix';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Selected month (default: all months)
$selected_month = $_GET['month'] ?? '';

// SQL query
$sql = "SELECT id, reason, status, from_date, to_date,
        DATEDIFF(to_date, from_date) + 1 AS days
        FROM leave_requests
        WHERE user_id = ?";

if ($selected_month !== '') {
    $sql .= " AND MONTH(from_date) = ?";
}

$stmt = $conn->prepare($sql);
if ($selected_month !== '') {
    $stmt->bind_param("ii", $user_id, $selected_month);
} else {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();
$leaves = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Leaves - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body { min-height: 100vh; background: url('../images/sunrise.png') no-repeat center center/cover; transition: background 0.5s ease, color 0.3s ease; color: #222; padding: 80px 20px; }
    body.dark { background: url('../images/moon.jpg') no-repeat center center/cover; color: #eee; }

    /* Logo */
    .logo-fixed { position: absolute; top: 20px; left: 20px; display: flex; align-items: center; gap: 8px; padding: 6px 12px; border: 2px solid rgba(0,0,0,0.15); border-radius: 12px; background: rgba(255,255,255,0.7); backdrop-filter: blur(6px); font-weight: 600; text-decoration: none; color: #333; }
    .logo-fixed img { height: 40px; width: 40px; border-radius: 50%; border: 2px solid #5b5ff4; padding: 3px; background: #fff; }
    body.dark .logo-fixed { background: rgba(34,34,34,0.7); border: 2px solid rgba(255,255,255,0.2); color: #eee; }
    body.dark .logo-fixed img { border: 2px solid #f062c0; background: #222; }

    /* Dark mode toggle */
    #darkToggle { position: absolute; top: 20px; right: 20px; cursor: pointer; font-size: 22px; }

    h2 { font-size: 26px; font-weight: 700; margin-bottom: 10px; }
    p.subtitle { font-size: 14px; margin-bottom: 20px; }

    .reports-container { max-width: 950px; margin: auto; display: flex; flex-direction: column; gap: 20px; }

    .box { padding: 20px; border: 1.5px solid #007bff; border-radius: 12px; background: rgba(255,255,255,0.8); }
    body.dark .box { background: rgba(34,34,34,0.75); border-color: #f062c0; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 10px; border: 1px solid #ccc; text-align: center; font-size: 14px; }
    th { background: #007bff; color: #fff; }
    body.dark th { background: #f062c0; }
    body.dark td { border-color: #666; }

    .export-btn { background: #ffc107; border: none; padding: 8px 15px; font-weight: 600; border-radius: 6px; cursor: pointer; float: right; margin-bottom: 10px; }
    .export-btn:hover { background: #e0a800; }
    select { margin-left: 10px; padding: 6px; border-radius: 6px; }

    @media(max-width:768px){
      table, thead, tbody, th, td, tr { display:block; }
      thead { display:none; }
      tr { margin-bottom: 12px; padding: 10px; border-radius: 8px; background: rgba(255,255,255,0.1); }
      td { padding: 8px; display:flex; justify-content:space-between; }
      td::before { content: attr(data-label); font-weight:600; }
      body.dark tr { background: rgba(0,0,0,0.7); }
    }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
  <a href="dashboard.php" class="logo-fixed">
    <img src="../images/new-logo.jpeg" alt="LeaveLogix Logo">
    <span>LeaveLogix</span>
  </a>
  <span id="darkToggle">🌙</span>

  <div class="reports-container">
    <div class="box">
      <button class="export-btn" onclick="exportTable()">Export PDF</button>
      <h2>My Leave Reports</h2>
      <p class="subtitle">Your applied leaves with status and duration</p>

      <!-- Month Filter -->
      <form method="GET" style="margin-bottom:15px;">
        <label for="month">Filter by Month:</label>
        <select name="month" id="month" onchange="this.form.submit()">
          <option value="">All</option>
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= ($selected_month == $m ? 'selected' : '') ?>>
              <?= date("F", mktime(0,0,0,$m,1)) ?>
            </option>
          <?php endfor; ?>
        </select>
      </form>

      <table id="leaveTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Reason</th>
            <th>Status</th>
            <th>From Date</th>
            <th>To Date</th>
            <th>Days</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!empty($leaves)): ?>
          <?php foreach($leaves as $leave): ?>
            <tr>
              <td data-label="ID"><?= htmlspecialchars($leave['id']) ?></td>
              <td data-label="Reason"><?= htmlspecialchars($leave['reason']) ?></td>
              <td data-label="Status"><?= htmlspecialchars($leave['status']) ?></td>
              <td data-label="From"><?= htmlspecialchars($leave['from_date']) ?></td>
              <td data-label="To"><?= htmlspecialchars($leave['to_date']) ?></td>
              <td data-label="Days"><?= htmlspecialchars($leave['days']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6">No leaves applied yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    // Dark Mode Toggle
    const toggle=document.getElementById("darkToggle");
    if(localStorage.getItem("darkMode")==="enabled"){document.body.classList.add("dark"); toggle.textContent="☀️";}
    toggle.addEventListener("click",()=>{const isDark=document.body.classList.toggle("dark");localStorage.setItem("darkMode",isDark?"enabled":"disabled");toggle.textContent=isDark?"☀️":"🌙";});

    // Export PDF
    function exportTable() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.text("My Leave Reports", 14, 15);
      doc.autoTable({ html: "#leaveTable", startY: 25 });
      doc.save("My_Leave_Reports.pdf");
    }
  </script>
</body>
</html>