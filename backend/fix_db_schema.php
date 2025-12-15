<?php
require 'config.php';

echo "Memeriksa kelengkapan schema database...\n";

try {
    // 1. Cek Kolom 'active'
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'active'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1");
        echo "[OK] Kolom 'active' berhasil ditambahkan.\n";
    } else {
        echo "[SKIP] Kolom 'active' sudah ada.\n";
    }

    // 2. Cek Kolom 'profile_pic'
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT 'default.jpg'");
        echo "[OK] Kolom 'profile_pic' berhasil ditambahkan.\n";
    } else {
        echo "[SKIP] Kolom 'profile_pic' sudah ada.\n";
    }

    echo "\nDatabase fix selesai. Silakan coba login kembali.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
