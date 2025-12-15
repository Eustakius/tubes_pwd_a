<?php
require_once 'config.php';

// Untuk sementara token hanya dicek keberadaannya di URL.
// Di tahap lanjutan sebaiknya divalidasi dengan basis data.
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
 * Skenario Sederhana (Prototype):
 * - Di email, token digunakan hanya sebagai dummy verifikasi
 * - Aktivasi dilakukan secara manual oleh admin di database
 * 
 * Untuk memenuhi kebutuhan Ujian Akhir Semester (UAS), skenario disesuaikan:
 * - Pengguna yang menekan tautan dianggap valid -> set active = 1 berdasarkan email di query string
 *   (frontend akan menghasilkan tautan dengan format `...?email=...`)
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

// Memperbarui status akun menjadi aktif (active=1)
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
