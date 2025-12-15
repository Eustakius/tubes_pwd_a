<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // returns ['open' => 5, 'closed' => 2]

    // Ensure all keys exist
    $data = [
        'open' => $stats['open'] ?? 0,
        'progress' => $stats['progress'] ?? 0,
        'closed' => $stats['closed'] ?? 0,
        'total' => array_sum($stats)
    ];

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching stats']);
}
