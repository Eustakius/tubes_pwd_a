<?php
require 'config.php';

try {
    // Cek apakah kolom active sudah ada
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'active'");
    if ($check->rowCount() == 0) {
        // Tambahkan kolom active jika belum ada
        // Default 1 (aktif) agar user lama tidak terkunci, atau 0 jika ingin ketat.
        // Di sini kita set 1 untuk kemudahan dev.
        $pdo->exec("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1");
        echo "Berhasil menambahkan kolom 'active' ke tabel 'users'.\n";
    } else {
        echo "Kolom 'active' sudah ada.\n";
    }
} catch (PDOException $e) {
    echo "Gagal migrasi: " . $e->getMessage() . "\n";
}
