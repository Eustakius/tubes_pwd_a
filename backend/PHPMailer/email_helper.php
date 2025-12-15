<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

function sendEmail($to, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tubespwda@gmail.com'; // Hardcoded credentials from register.php
        $mail->Password = 'kvof qzrz jfcf ybcn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('tubespwda@gmail.com', 'Incident Admin'); // Sender
        $mail->addAddress($to);

        $mail->isHTML(false); // Plain text for comments is safer/easier
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error silently usually, or return false
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}
