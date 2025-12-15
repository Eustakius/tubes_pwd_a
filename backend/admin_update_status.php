<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verifikasi Hak Akses Administrator
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admins only']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ID or Status']);
    exit;
}

$validStatuses = ['open', 'progress', 'closed'];
if (!in_array($data['status'], $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->execute([$data['status'], $data['id']]);

    // Menambahkan komentar sistem? Opsional tetapi baik untuk audit trail.
    // $stmt = $pdo->prepare("INSERT INTO comments (report_id, user_id, message) VALUES (?, ?, ?)");
    // $stmt->execute([$data['id'], $_SESSION['user_id'], "Status diubah menjadi " . $data['status']]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
