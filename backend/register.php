<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid']);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Username atau email sudah terdaftar']);
        exit;
    }

    $hashed_password = hash_password($password);
    $token = generate_token();

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, active) VALUES (?, ?, ?, 1)");

    if ($stmt->execute([$username, $email, $hashed_password])) {
        if (sendActivationEmail($email, $username, $token)) {
            echo json_encode([
                'success' => true,
                'message' => 'Registrasi berhasil! Silakan cek email untuk aktivasi akun.'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Registrasi berhasil tetapi gagal mengirim email aktivasi.'
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data user.']);
    }

} elseif ($method === 'GET') {
    $username = $_GET['username'] ?? '';
    $email = $_GET['email'] ?? '';

    $conditions = [];
    $params = [];

    if (!empty($username)) {
        $conditions[] = "username = ?";
        $params[] = $username;
    }
    if (!empty($email)) {
        $conditions[] = "email = ?";
        $params[] = $email;
    }

    if (!empty($conditions)) {
        $sql = "SELECT id FROM users WHERE " . implode(' OR ', $conditions);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['available' => $stmt->rowCount() === 0]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Parameter username atau email diperlukan']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
