<?php
require 'config.php';

try {
    // Add category column
    $pdo->exec("ALTER TABLE reports ADD COLUMN category VARCHAR(50) DEFAULT 'Other'");
    echo "Column 'category' added.\n";
} catch (PDOException $e) {
    echo "Column 'category' likely exists or error: " . $e->getMessage() . "\n";
}

try {
    // Add priority column
    $pdo->exec("ALTER TABLE reports ADD COLUMN priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Low'");
    echo "Column 'priority' added.\n";
} catch (PDOException $e) {
    echo "Column 'priority' likely exists or error: " . $e->getMessage() . "\n";
}

echo "Migration V2 (Professional Fields) completed.\n";
