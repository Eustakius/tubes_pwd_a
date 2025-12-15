<?php
require 'config.php';

try {
    echo "Connected to database: " . $dbname . "\n";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Columns in 'users' table:\n";
    foreach ($columns as $col) {
        echo "- " . $col . "\n";
    }
    
    if (in_array('active', $columns)) {
        echo "\nSUCCESS: Column 'active' EXISTS.\n";
    } else {
        echo "\nFAILURE: Column 'active' does NOT exist.\n";
        
        // Attempt force add
        echo "Attempting to force add 'active' column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN active TINYINT(1) DEFAULT 1");
        echo "Column 'active' added.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
