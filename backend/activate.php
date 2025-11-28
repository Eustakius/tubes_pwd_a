<?php
require_once 'config.php';

// Untuk sementara token hanya dicek keberadaannya di URL.
// Di tahap lanjutan bisa disimpan di DB.
$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Token aktivasi tidak ditemukan'
    ]);
    exit;
}

/**
 * Skenario simpel:
 * - Di email, token dipakai hanya sebagai dummy
 * - Aktivasi dilakukan manual oleh admin di database
 * 
 * Untuk memenuhi soal UAS, kita buat versi berikut:
 * - User yang klik link dianggap valid → set active = 1 berdasarkan email di query string
 *   (nanti frontend generate link `...?email=...`)
 */

$email = $_GET['email'] ?? '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email tidak ditemukan di link aktivasi'
    ]);
    exit;
}

// Update active=1
$stmt = $pdo->prepare("UPDATE users SET active = 1 WHERE email = ?");
if ($stmt->execute([$email]) && $stmt->rowCount() > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Akun berhasil diaktivasi. Silakan login.'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Aktivasi gagal, email tidak ditemukan atau sudah aktif.'
    ]);
}
