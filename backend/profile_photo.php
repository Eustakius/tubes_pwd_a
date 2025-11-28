<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File foto tidak valid']);
    exit;
}

$file    = $_FILES['photo'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png'];

if (!in_array($ext, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format harus JPG/PNG']);
    exit;
}

if ($file['size'] > 2 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ukuran maksimal 2MB']);
    exit;
}

$uploadDir = __DIR__ . '/../assets/profile/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filename = 'user_' . $userId . '_' . time() . '.' . $ext;
$target   = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal upload file']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
if ($stmt->execute([$filename, $userId])) {
    $_SESSION['profile_pic'] = $filename;
    echo json_encode(['success' => true, 'message' => 'Foto profil diperbarui', 'file' => $filename]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database']);
}
