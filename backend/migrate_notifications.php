<?php
require 'config.php';

try {
    // Add last_reply_by column
    $pdo->exec("ALTER TABLE reports ADD COLUMN last_reply_by ENUM('user', 'admin') DEFAULT NULL");
    echo "Column 'last_reply_by' added.\n";
} catch (PDOException $e) {
    echo "Column 'last_reply_by' likely exists or error: " . $e->getMessage() . "\n";
}

echo "Migration V3 (Notifications) completed.\n";
