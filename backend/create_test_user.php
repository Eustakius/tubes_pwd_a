<?php
require 'config.php';

$username = 'testuser';
$email = 'test@example.com';
$password = '123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'user';

// Cek apakah user sudah ada
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Reset password jika user sudah ada
    $stmt = $pdo->prepare("UPDATE users SET password = ?, active = 1 WHERE id = ?");
    $stmt->execute([$hash, $user['id']]);
    echo "Password untuk user '$email' berhasil di-reset menjadi '$password'.\n";
} else {
    // Buat user baru jika belum ada
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, active, profile_pic) VALUES (?, ?, ?, ?, 1, 'default.jpg')");
    $stmt->execute([$username, $email, $hash, $role]);
    echo "User '$username' ($email) berhasil dibuat.\nPassword: $password\n";
}
