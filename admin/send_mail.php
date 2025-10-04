<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // PHPMailer via Composer

function sendLeaveStatusMail($toEmail, $userName, $leaveDate, $status) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aathik9346@gmail.com';       // Change to your email
        $mail->Password   = 'lahq fkuo lahu fqdj';          // App password (not normal one)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('aathik9346@gmail.com', 'LeaveLogix Admin');
        $mail->addAddress($toEmail, $userName);

        $statusText = ucfirst($status); // Approved or Rejected
        $mail->isHTML(true);
        $mail->Subject = "Your Leave Request Has Been $statusText";

        $mail->Body = "
            <h3>Dear $userName,</h3>
            <p>Your leave request for <strong>$leaveDate</strong> has been <strong>$statusText</strong>.</p>
            <p>If you have any questions, please contact your department head.</p>
            <br>
            <p>Regards,<br>LeaveLogix Team</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
