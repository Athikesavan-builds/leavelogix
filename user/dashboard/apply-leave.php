<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php';

// --- Database connection ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "leavelogix";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("DB Connection Failed: " . mysqli_connect_error());
}

// initialize
$errors = [];
$success = "";
$recent = [];

// --- Mail function (user → admin) ---
function sendLeaveApplyMail($adminEmail, $userName, $fromDate, $toDate, $reason) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aathik9346@gmail.com';     // your gmail
        $mail->Password   = 'lahq fkuo lahu fqdj';      // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('aathik9346@gmail.com', 'LeaveLogix System');
        $mail->addAddress($adminEmail, "Admin");

        $mail->isHTML(true);
        $mail->Subject = "New Leave Application from $userName";
        $mail->Body = "
            <h3>Dear Admin,</h3>
            <p><b>$userName</b> has applied for leave from <b>$fromDate</b> to <b>$toDate</b>.</p>
            <p><b>Reason:</b> $reason</p>
            <p>Please login to LeaveLogix to approve or reject this request.</p>
            <p>Regards,<br>LeaveLogix System</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
    }
}

// --- Leave apply logic ---
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId   = $_SESSION['user_id'];
    // FIXED: correct POST names
    $fromDate = $_POST['fromDate'];
    $toDate   = $_POST['toDate'];
    $reason   = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, from_date, to_date, reason, status, created_at) VALUES (?, ?, ?, ?, 'Pending', NOW())");
    $stmt->bind_param("isss", $userId, $fromDate, $toDate, $reason);
    if ($stmt->execute()) {
        // Fetch user details for email
        $res = mysqli_query($conn, "SELECT fullname FROM users WHERE id = $userId");
        $user = mysqli_fetch_assoc($res);

        // Send mail to admin (change this to your admin email)
        sendLeaveApplyMail("aathik9346@gmail.com", $user['fullname'], $fromDate, $toDate, $reason);

        $success = "Leave request submitted successfully and mail sent to Admin!";
    } else {
        $errors[] = "Error submitting leave request: " . $stmt->error;
    }
}

// recent requests (optional)
$resRec = mysqli_query($conn, "SELECT from_date, to_date, reason, status FROM leave_requests WHERE user_id = " . $_SESSION['user_id'] . " ORDER BY created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($resRec)) {
    $recent[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Apply for Leave - LeaveLogix</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Inter",sans-serif}
    :root{--accent:#4e9ff4;--accent-2:#5b5ff4;--pink:#f062c0;--radius:16px;}
    body{min-height:100vh;background:url("../../images/sunrise.png") no-repeat center/cover;transition:background .4s,color .3s;color:#111;}
    body.dark{background:url("../../images/moon.jpg") no-repeat center/cover;color:#eee;}
    header{display:flex;justify-content:space-between;align-items:center;padding:15px 30px;}
    .logo-fixed{display:flex;align-items:center;gap:8px;cursor:pointer;padding:6px 12px;
      border:2px solid rgba(0,0,0,0.15);border-radius:12px;background:rgba(255,255,255,0.7);
      backdrop-filter:blur(6px);transition:0.3s;font-weight:600;text-decoration:none;color:#333;}
    .logo-fixed img{height:40px;width:40px;border-radius:50%;border:2px solid #5b5ff4;padding:3px;background:#fff;}
    body.dark .logo-fixed{background:rgba(34,34,34,0.7);border:2px solid rgba(255,255,255,0.2);color:#eee;}
    body.dark .logo-fixed img{border:2px solid #f062c0;background:#222;}
    .icon-btn{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;
      background:rgba(255,255,255,.85);cursor:pointer;font-size:18px;box-shadow:0 4px 12px rgba(0,0,0,.08);transition:.2s;}
    .icon-btn:hover{transform:translateY(-3px)}body.dark .icon-btn{background:rgba(0,0,0,.45);color:#fff;}
    .page-title{padding:20px 32px}
    .page-title h1{font-size:42px;margin-bottom:6px}
    .page-title p{color:rgba(0,0,0,.6)}body.dark .page-title p{color:rgba(255,255,255,.6)}
    main{display:flex;justify-content:center;align-items:flex-start;padding:20px}
    .card{width:100%;max-width:650px;background:rgba(255,255,255,.92);border-radius:var(--radius);
      padding:28px;box-shadow:0 10px 28px rgba(0,0,0,.08);border:2px solid rgba(78,159,244,.12);animation:fadeInUp .6s ease both;}
    body.dark .card{background:rgba(20,20,20,.85);border:2px solid rgba(240,98,192,.15);box-shadow:0 10px 28px rgba(0,0,0,.28);}
    label{display:block;margin-bottom:6px;font-weight:600;font-size:14px}
    input,textarea{width:100%;padding:12px 14px;margin-bottom:18px;border-radius:12px;
      border:2px solid rgba(78,159,244,.25);background:transparent;color:inherit;font-size:15px;transition:.2s;}
    input:focus,textarea:focus{border-color:var(--accent-2);box-shadow:0 0 0 3px rgba(91,95,244,.2);outline:none}
    textarea{min-height:100px;resize:vertical}
    .btn-primary{background:linear-gradient(90deg,var(--accent-2),var(--accent));border:none;color:#fff;font-weight:700;
      font-size:16px;padding:12px 20px;border-radius:12px;cursor:pointer;box-shadow:0 6px 16px rgba(78,159,244,.2);transition:.2s;}
    .btn-primary:hover{transform:translateY(-3px)}
    @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
    .msg{padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;}
    .error{background:#ffe6e6;color:#900;}
    .success{background:#e8fff0;color:#097a38;}
    .recent{margin-top:24px}
    .recent h3{margin-bottom:12px}
    .recent ul{list-style:none;padding:0}
    .recent li{padding:10px;border:1px solid rgba(0,0,0,.1);border-radius:8px;margin-bottom:8px;font-size:14px;}
    body.dark .recent li{border:1px solid rgba(255,255,255,.2);}
  </style>
</head>
<body>
  <header>
    <a href="dashboard.php" class="logo-fixed">
      <img src="../../images/new-logo.jpeg" alt="LeaveLogix Logo">
      <span>LeaveLogix</span>
    </a>
    <div class="icon-btn" id="darkToggle">🌙</div>
  </header>

  <section class="page-title">
    <h1>Apply for Leave</h1>
    <p>Submit your leave request below</p>
  </section>

  <main>
    <div class="card">
      <?php if ($errors): ?>
        <?php foreach ($errors as $err): ?>
          <div class="msg error"><?=htmlspecialchars($err)?></div>
        <?php endforeach; ?>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="msg success"><?=$success?></div>
      <?php endif; ?>

      <form method="POST">
        <label for="fromDate">From date</label>
        <input type="date" name="fromDate" required>

        <label for="toDate">To date</label>
        <input type="date" name="toDate" required>

        <label for="reason">Reason</label>
        <textarea name="reason" placeholder="Enter reason..." required></textarea>

        <button type="submit" class="btn-primary">Submit</button>
      </form>

      <div class="recent">
        <h3>Recent Requests</h3>
        <ul>
          <?php if ($recent): ?>
            <?php foreach ($recent as $row): ?>
              <li>
                <?=htmlspecialchars($row['from_date'])?> → <?=htmlspecialchars($row['to_date'])?><br>
                <em><?=htmlspecialchars($row['reason'])?></em><br>
                <strong>Status: <?=$row['status']?></strong>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li>No recent requests.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </main>

  <script>
    const darkToggle=document.getElementById('darkToggle');
    function applyDark(){
      if(localStorage.getItem('darkMode')==='enabled'){
        document.body.classList.add('dark');darkToggle.textContent='☀️';
      }else{document.body.classList.remove('dark');darkToggle.textContent='🌙';}
    }
    darkToggle.onclick=()=>{
      if(document.body.classList.toggle('dark')){
        localStorage.setItem('darkMode','enabled');darkToggle.textContent='☀️';
      }else{
        localStorage.setItem('darkMode','disabled');darkToggle.textContent='🌙';
      }
    };
    applyDark();
  </script>
</body>
</html>
