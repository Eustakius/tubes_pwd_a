<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email dan password wajib diisi']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, email, password, active, profile_pic, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
    exit;
}

if ((int) $user['active'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akun belum aktif. Silakan cek email untuk aktivasi.']);
    exit;
}

if (!verify_password($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Email atau password salah']);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['profile_pic'] = $user['profile_pic'];

echo json_encode([
    'success' => true,
    'message' => 'Login berhasil',
    'data' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'profile_pic' => $user['profile_pic']
    ]
]);
