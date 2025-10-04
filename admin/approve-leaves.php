<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php'; // Composer autoload

// --- Database connection ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "leavelogix";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("DB Connection Failed: " . mysqli_connect_error());
}

// --- Mail function ---
function sendLeaveStatusMail($toEmail, $userName, $fromDate, $toDate, $status) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aathik9346@gmail.com';     // your gmail
        $mail->Password   = 'lahq fkuo lahu fqdj';      // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('aathik9346@gmail.com', 'LeaveLogix Admin');
        $mail->addAddress($toEmail, $userName);

        $statusText = ucfirst($status);

        $mail->isHTML(true);
        $mail->Subject = "Leave Request $statusText";
        $mail->Body = "
            <h3>Dear $userName,</h3>
            <p>Your leave request from <b>$fromDate</b> to <b>$toDate</b> has been <b style='color:blue;'>$statusText</b>.</p>
            <p>Regards,<br>LeaveLogix Team</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
    }
}

// --- Approve / Reject logic ---
if (isset($_GET['approve']) || isset($_GET['reject'])) {
    $id = intval($_GET['approve'] ?? $_GET['reject']);
    $newStatus = isset($_GET['approve']) ? "Approved" : "Rejected";

    // Update leave status
    $stmt = $conn->prepare("UPDATE leave_requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $newStatus, $id);
    $stmt->execute();

    // Fetch user details
    $res = mysqli_query($conn, "
        SELECT u.email, u.fullname, l.from_date, l.to_date
        FROM leave_requests l
        JOIN users u ON l.user_id = u.id
        WHERE l.id = $id
    ");
    if ($data = mysqli_fetch_assoc($res)) {
        sendLeaveStatusMail($data['email'], $data['fullname'], $data['from_date'], $data['to_date'], $newStatus);
    }

    header("Location: approve-leaves.php");
    exit;
}

// --- Fetch leave requests ---
$sql = "
    SELECT l.id, u.fullname AS name, u.role, l.from_date, l.to_date, l.reason, l.status
    FROM leave_requests l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
";
$res = mysqli_query($conn, $sql);
$requests = mysqli_fetch_all($res, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Approve Leaves - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
    body{min-height:100vh;background:url('../images/sunrise.png') no-repeat center/cover;
      transition:background 0.4s ease,color 0.3s ease;color:#222;}
    body.dark{background:url('../images/moon.jpg') no-repeat center/cover;color:#eee;}
    header{display:flex;justify-content:space-between;align-items:center;padding:15px 30px;}
    .logo-fixed{display:flex;align-items:center;gap:8px;cursor:pointer;padding:6px 12px;
      border:2px solid rgba(0,0,0,0.15);border-radius:12px;background:rgba(255,255,255,0.7);
      backdrop-filter:blur(6px);font-weight:700;text-decoration:none;color:#333;}
    .logo-fixed img{height:40px;width:40px;border-radius:50%;border:2px solid #5b5ff4;padding:3px;background:#fff;}
    body.dark .logo-fixed{background:rgba(34,34,34,0.7);border:2px solid rgba(255,255,255,0.2);color:#eee;}
    .actions{display:flex;align-items:center;gap:12px;}
    .icon-btn{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;
      background:rgba(255,255,255,0.85);cursor:pointer;font-size:18px;}
    body.dark .icon-btn{background:rgba(0,0,0,0.45);color:#fff;}
    .title-container{text-align:center;margin:20px 0;}
    .title-container h1{font-size:26px;font-weight:700;background:#0ebfff;color:#fff;
      display:inline-block;padding:6px 16px;border-radius:8px;}
    .table-container{margin:20px auto;width:90%;overflow-x:auto;}
    table{width:100%;border-collapse:collapse;font-size:15px;}
    thead{background:#007bff;color:#fff;}
    thead th{padding:12px;text-align:left;}
    tbody td{padding:10px;border-bottom:1px solid #ccc;}
    tbody tr:hover{background:rgba(0,0,0,0.07);}
    body.dark thead{background:#f062c0;}
    .btn{padding:5px 12px;border:none;border-radius:6px;cursor:pointer;font-weight:600;}
    .btn-approve{background:#28a745;color:#fff;}
    .btn-reject{background:#dc3545;color:#fff;}
  </style>
</head>
<body>
  <header>
    <a href="admin-dashboard.php" class="logo-fixed">
      <img src="images/new-logo.jpeg" alt="LeaveLogix Logo">
      <span>LeaveLogix</span>
    </a>
    <div class="actions">
      <div id="darkToggle" class="icon-btn">🌙</div>
    </div>
  </header>

  <div class="title-container">
    <h1>Approve Leaves</h1>
  </div>

  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Name</th><th>Role</th><th>From</th><th>To</th>
          <th>Reason</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($requests): ?>
          <?php foreach ($requests as $r): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><?= htmlspecialchars($r['role']) ?></td>
              <td><?= $r['from_date'] ?></td>
              <td><?= $r['to_date'] ?></td>
              <td><?= htmlspecialchars($r['reason']) ?></td>
              <td style="font-weight:bold;color:
                <?= $r['status']=="Approved"?"green":($r['status']=="Rejected"?"red":"#555") ?>">
                <?= $r['status'] ?>
              </td>
              <td>
                <a class="btn btn-approve" href="?approve=<?= $r['id'] ?>">Approve</a>
                <a class="btn btn-reject" href="?reject=<?= $r['id'] ?>">Reject</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center;">No leave requests found</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <script>
    // Dark Mode Toggle
    const toggle=document.getElementById("darkToggle");
    if(localStorage.getItem("darkMode")==="enabled"){
      document.body.classList.add("dark");toggle.textContent="☀️";
    }
    toggle.addEventListener("click",()=>{
      if(document.body.classList.toggle("dark")){
        localStorage.setItem("darkMode","enabled");toggle.textContent="☀️";
      } else {
        localStorage.setItem("darkMode","disabled");toggle.textContent="🌙";
      }
    });
  </script>
</body>
</html>
