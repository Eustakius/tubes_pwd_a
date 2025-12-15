<?php
session_start();

// === Konfigurasi Koneksi Database (PDO Environment) ===
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Kredensial Default XAMPP (Development)
define('DB_NAME', 'reporting_system');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

// === Integrasi Pustaka PHPMailer untuk Layanan Email ===
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// === Modul Pengiriman Email Aktivasi Akun (SMTP) ===
function sendActivationEmail($email, $username, $token)
{
    $mail = new PHPMailer(true);

    try {
        // Mencatat output debug ke log error PHP (Debugging)
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = 'error_log';

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tsukishiroyuto@gmail.com';
        $mail->Password = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('tsukishiroyuto@gmail.com', 'Cyber Report System');
        $mail->addAddress($email, $username);

        $activation_link = "http://localhost/tubes_pwd_a/frontend/activate.html?token={$token}&email=" . urlencode($email);

        $mail->isHTML(true);
        $mail->Subject = 'Aktivasi Akun Cyber Report';
        $mail->Body = "
            <h2>Selamat datang, {$username}!</h2>
            <p>Klik link berikut untuk mengaktifkan akun:</p>
            <a href='{$activation_link}' style=\"background:#0d6efd;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;\">AKTIVASI AKUN</a>
            <p>Atau copy link berikut ke browser:</p>
            <p>{$activation_link}</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

// === Kumpulan Fungsi Utilitas (Helper Functions) ===
function hash_password($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash)
{
    return password_verify($password, $hash);
}

function generate_token()
{
    return bin2hex(random_bytes(32));
}

// Mengatur header respons standar JSON untuk semua endpoint API
header('Content-Type: application/json');
