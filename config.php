<?php
// Database connection
$host = "localhost";
$user = "root";       // உங்கள் MySQL username
$pass = "";           // password இருந்தால் இடுங்கள்
$db   = "leavelogix"; // உங்கள் DB பெயர்

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Mail Configuration (SMTP)
$mail_host     = "smtp.gmail.com";   // Gmail SMTP
$mail_username = "leavelogix@gmail.com";  // உங்கள் Gmail
$mail_password = "pvpt hiph gizm nvea";    // Gmail App Password (normal password போடக்கூடாது!)
$mail_port     = 587;  // TLS = 587, SSL = 465
$mail_from     = "leavelogix@gmail.com";  // From Email
$mail_fromName = "LeaveLogix System";    // From Name
