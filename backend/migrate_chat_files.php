<?php
require 'config.php';

try {
    // Add attachment column to comments
    $pdo->exec("ALTER TABLE comments ADD COLUMN attachment VARCHAR(255) DEFAULT NULL");
    echo "Column 'attachment' added to comments.\n";
} catch (PDOException $e) {
    echo "Column 'attachment' likely exists or error: " . $e->getMessage() . "\n";
}

echo "Migration V4 (Chat Files) completed.\n";
