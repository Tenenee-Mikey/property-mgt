<?php
// FILE: mail_config.php
// Gmail SMTP configuration for 2FA

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

// SMTP settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'miikapalmer22@gmail.com');      // CHANGE THIS
define('SMTP_PASS', 'gyox nzsw poqc pgga'); // CHANGE THIS
define('SMTP_FROM', 'miikapalmer22@gmail.com');      // CHANGE THIS
define('SMTP_FROM_NAME', 'Property Management System');

function sendOTPEmail($toEmail, $toName, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your 2FA Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif;'>
                <h2>Property Management System</h2>
                <p>Hello <strong>$toName</strong>,</p>
                <p>Your one-time verification code is:</p>
                <h1 style='font-size: 32px; letter-spacing: 5px; color: #2E75B6;'>$otp</h1>
                <p>This code expires in <strong>10 minutes</strong>.</p>
                <p>Do not share this code with anyone.</p>
                <hr>
                <p style='font-size: 12px; color: #999;'>If you didn't attempt to login, ignore this email.</p>
            </div>
        ";
        $mail->AltBody = "Your 2FA verification code is: $otp. Expires in 10 minutes.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>