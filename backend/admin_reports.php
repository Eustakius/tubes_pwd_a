<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Memvalidasi peran pengguna (Role-Based Access Control)
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied. Admin only.']);
    exit;
}

// Mengambil seluruh data laporan beserta informasi pelapor
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

if ($status === 'all') {
    $stmt = $pdo->query("SELECT r.*, u.username, u.email FROM reports r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT r.*, u.username, u.email FROM reports r JOIN users u ON r.user_id = u.id WHERE r.status = ? ORDER BY r.created_at DESC");
    $stmt->execute([$status]);
}

$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $reports]);
