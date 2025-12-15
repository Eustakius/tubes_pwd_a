<?php
require 'config.php';

$email = 'admin@cyberreport.com';
$new_pass = '123';
$hash = password_hash($new_pass, PASSWORD_DEFAULT);

// 1. Cek apakah user admin ada
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // 2. Jika ada, reset password
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $user['id']]);
    echo "Password untuk user '$email' berhasil di-reset menjadi '$new_pass'.\n";
} else {
    // 3. Jika tidak ada, buat user baru
    echo "User admin belum ada. Membuat user baru...\n";
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, active, profile_pic) VALUES (?, ?, ?, ?, 1, 'default.jpg')");
    $stmt->execute(['admin', $email, $hash, 'admin']);
    echo "User admin berhasil dibuat.\nEmail: $email\nPassword: $new_pass\n";
}
