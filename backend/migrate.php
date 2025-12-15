<?php
require 'config.php';

try {
    // 1. Add evidence column to reports if not exists
    $checkCol = $pdo->query("SHOW COLUMNS FROM reports LIKE 'evidence'");
    if ($checkCol->rowCount() == 0) {
        $pdo->exec("ALTER TABLE reports ADD COLUMN evidence VARCHAR(255) DEFAULT NULL");
        echo "Added 'evidence' column to reports.\n";
    } else {
        echo "'evidence' column already exists.\n";
    }

    // 2. Create comments table
    $sql = "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Created 'comments' table.\n";

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
