<?php
require_once 'config.php';

// Mencegah output yang tidak diinginkan (Konfigurasi Error Reporting)
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Silakan login dahulu.']);
    exit;
}

$userId = $_SESSION['user_id'];

switch ($method) {
    case 'POST':
        // Menangani input JSON dan Multipart/Form-Data untuk fleksibilitas request
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $location = trim($input['location'] ?? '');
            $latitude = $input['latitude'] ?? null;
            $longitude = $input['longitude'] ?? null;
            $category = trim($input['category'] ?? 'Other'); // Added
            $priority = trim($input['priority'] ?? 'Low');   // Added
        } else {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $latitude = $_POST['latitude'] ?? null;
            $longitude = $_POST['longitude'] ?? null;
            $category = trim($_POST['category'] ?? 'Other'); // Added
            $priority = trim($_POST['priority'] ?? 'Low');   // Added
        }

        if (empty($title) || empty($description)) { // Validasi Data Input (Pastikan semua field wajib terisi)
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Judul dan Deskripsi wajib diisi']);
            exit;
        }

        // Memproses unggah bukti laporan (File Upload Handling)
        $evidencePath = null;
        if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $fileType = mime_content_type($_FILES['evidence']['tmp_name']);

            if (in_array($fileType, $allowedTypes)) {
                $ext = pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION);
                $filename = 'evidence_' . time() . '_' . uniqid() . '.' . $ext;
                $targetDir = __DIR__ . '/uploads/';

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                if (move_uploaded_file($_FILES['evidence']['tmp_name'], $targetDir . $filename)) {
                    $evidencePath = $filename;
                }
            } else { // Pemeriksaan validitas tipe file (Security Check)
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only Images/PDF allowed.']);
                exit;
            }
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO reports (user_id, title, description, location, latitude, longitude, evidence, category, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $title, $description, $location, $latitude, $longitude, $evidencePath, $category, $priority]);
            echo json_encode(['success' => true, 'message' => 'Laporan berhasil dibuat']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
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

        $input = json_decode(file_get_contents('php://input'), true);
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $status = trim($input['status'] ?? '');

        $allowedStatus = ['open', 'progress', 'closed'];
        if ($status !== '' && !in_array($status, $allowedStatus, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
        $stmt->execute([$id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan']);
            exit;
        }

        // Memeriksa hak akses: Pemilik Laporan atau Administrator (Access Control)
        $role = $_SESSION['role'] ?? 'user';
        if ($role !== 'admin' && $report['user_id'] != $userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        // Aturan Bisnis: Pengguna tidak dapat mengedit tiket yang sudah ditutup (Quality of Life)
        if ($role !== 'admin' && $report['status'] === 'closed') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Cannot edit a closed ticket']);
            exit;
        }

        $newTitle = $title !== '' ? $title : $report['title'];
        $newDescription = $description !== '' ? $description : $report['description'];
        $newStatus = $status !== '' ? $status : $report['status'];
        // Asumsi: Kategori dan Prioritas mungkin disertakan dalam body PUT (Payload)
        $newCategory = $input['category'] ?? $report['category'];
        $newPriority = $input['priority'] ?? $report['priority'];

        try {
            // Jika admin, mereka bisa mengupdate semua laporan. Jika user, hanya milik mereka.
            if ($role === 'admin') {
                $stmt = $pdo->prepare(
                    "UPDATE reports SET title = ?, description = ?, status = ?, category = ?, priority = ? WHERE id = ?"
                );
                $stmt->execute([$newTitle, $newDescription, $newStatus, $newCategory, $newPriority, $id]);
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE reports SET title = ?, description = ?, status = ?, category = ?, priority = ? WHERE id = ? AND user_id = ?"
                );
                $stmt->execute([$newTitle, $newDescription, $newStatus, $newCategory, $newPriority, $id, $userId]);
            }
            echo json_encode(['success' => true, 'message' => 'Laporan berhasil diupdate']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Update Failed: ' . $e->getMessage()]);
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

        $stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ?");
        $stmt->execute([$id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Laporan tidak ditemukan']);
            exit;
        }

        // Verifikasi Hak Akses dan Status Tiket sebelum penghapusan
        $role = $_SESSION['role'] ?? 'user';
        if ($role !== 'admin') {
            if ($report['user_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                exit;
            }
            if ($report['status'] === 'closed') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Cannot delete a closed ticket']);
                exit;
            }
        }

        $stmt = $pdo->prepare(
            "DELETE FROM reports WHERE id = ?"
        );
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
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
