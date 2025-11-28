<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Silakan login dahulu.']);
    exit;
}

$userId = $_SESSION['user_id'];

switch ($method) {
    case 'POST':
        $input       = json_decode(file_get_contents('php://input'), true);
        $title       = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $location    = trim($input['location'] ?? '');

        if (empty($title)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Judul laporan wajib diisi']);
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO reports (user_id, title, description, location) VALUES (?, ?, ?, ?)"
        );
        if ($stmt->execute([$userId, $title, $description, $location])) {
            echo json_encode(['success' => true, 'message' => 'Laporan berhasil dibuat']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal membuat laporan']);
        }
        break;

    case 'GET':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($report) {
                echo json_encode(['success' => true, 'data' => $report]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan']);
            }
        } else {
            $stmt = $pdo->prepare(
                "SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC"
            );
            $stmt->execute([$userId]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $reports]);
        }
        break;

    case 'PUT':
        parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
        $id = isset($query['id']) ? (int) $query['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID laporan tidak valid']);
            exit;
        }

        $input       = json_decode(file_get_contents('php://input'), true);
        $title       = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $status      = trim($input['status'] ?? '');

        $allowedStatus = ['open', 'progress', 'closed'];
        if ($status !== '' && !in_array($status, $allowedStatus, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan']);
            exit;
        }

        $newTitle       = $title !== '' ? $title : $report['title'];
        $newDescription = $description !== '' ? $description : $report['description'];
        $newStatus      = $status !== '' ? $status : $report['status'];

        $stmt = $pdo->prepare(
            "UPDATE reports SET title = ?, description = ?, status = ? WHERE id = ? AND user_id = ?"
        );
        if ($stmt->execute([$newTitle, $newDescription, $newStatus, $id, $userId])) {
            echo json_encode(['success' => true, 'message' => 'Laporan berhasil diupdate']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate laporan']);
        }
        break;

    case 'DELETE':
        parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
        $id = isset($query['id']) ? (int) $query['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID laporan tidak valid']);
            exit;
        }

        $stmt = $pdo->prepare(
            "DELETE FROM reports WHERE id = ? AND user_id = ?"
        );
        if ($stmt->execute([$id, $userId]) && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Laporan berhasil dihapus']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan atau gagal dihapus']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
