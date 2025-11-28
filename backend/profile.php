<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Silakan login.']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT id, username, email, profile_pic, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $user]);

} elseif ($method === 'POST') {
    $input    = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $email    = trim($input['email'] ?? '');

    if (empty($username) || empty($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username dan email wajib diisi']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?"
    );
    $stmt->execute([$username, $email, $userId]);
    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    if ($stmt->execute([$username, $email, $userId])) {
        $_SESSION['username']   = $username;
        $_SESSION['user_email'] = $email;
        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
