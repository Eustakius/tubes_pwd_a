<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

if ($method === 'GET') {
    // Mengambil daftar komentar terkait laporan tertentu (Fetch Data)
    $reportId = isset($_GET['report_id']) ? (int) $_GET['report_id'] : 0;

    // Memvalidasi hak akses: Pengguna wajib pemilik laporan atau Administrator
    // Implementasi efisiensi: Admin memiliki akses global, User terbatas pada kepemilikan.
    // Kami mempercayakan pemeriksaan kepemilikan laporan pada query gabungan atau pra-pemeriksaan.

    // Mekanisme sederhana: Cukup ambil data. Jika kosong kembalikan [].
    // Idealnya memeriksa kepemilikan terlebih dahulu.
    $stmt = $pdo->prepare("SELECT user_id FROM reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }

    if ($role !== 'admin' && $report['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    $sql = "SELECT c.*, u.username, u.email 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.report_id = ? 
            ORDER BY c.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$reportId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $comments, 'current_user_id' => $userId]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Menangani permintaan format JSON (Legacy) maupun Multipart-form (Modern)
    // Sebenarnya, lebih aman hanya mengandalkan POST untuk fitur baru atau memeriksa tipe konten.
    // Namun karena kami sedang meningkatkan frontend, kami cukup beralih ke $_POST.

    $reportId = $_POST['report_id'] ?? null;
    $message = $_POST['message'] ?? '';

    // Fallback untuk permintaan JSON jika ada klien legacy (opsional, namun praktik yang baik)
    if (!$reportId) {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $reportId = $input['report_id'] ?? null;
            $message = $input['message'] ?? '';
        }
    }

    if (empty($reportId) || empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'Missing Report ID or User Session']);
        exit;
    }

    if (!$reportId || (!$message && empty($_FILES['attachment']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Report ID and Message (or File) required']);
        exit;
    }

    // Verifikasi ulang hak akses dan kepemilikan laporan (Validasi Keamanan)
    $stmt = $pdo->prepare("SELECT user_id, status FROM reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }

    $role = $_SESSION['role'] ?? 'user';
    // Logika: Admin dapat membalas siapa saja. User hanya dapat membalas milik sendiri.
    if ($role !== 'admin' && $report['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    // Memeriksa status laporan
    if ($report['status'] === 'closed' && $role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ticket is closed']);
        exit;
    }

    // Menangani proses unggah lampiran file (File Handling)
    $attachment = null;
    if (!empty($_FILES['attachment']['name'])) {
        $fileError = $_FILES['attachment']['error'];

        if ($fileError === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm'];
            $filename = $_FILES['attachment']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Images/Videos only.']);
                exit;
            }

            $newName = 'chat_' . uniqid() . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $newName;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
                $attachment = $newName;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file. Check folder permissions.']);
                exit;
            }
        } else {
            // Memetakan kode error PHP ke pesan yang dapat dibaca manusia
            $msg = 'Upload failed';
            switch ($fileError) {
                case UPLOAD_ERR_INI_SIZE:
                    $msg = 'File exceeds upload_max_filesize';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $msg = 'File exceeds MAX_FILE_SIZE';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $msg = 'File only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $msg = 'No file was uploaded';
                    break;
                default:
                    $msg = 'Unknown upload error code: ' . $fileError;
                    break;
            }
            // Jika hanya "No file", kami abaikan (ini opsional). Namun jika nama disetel dan kami di sini, itu error.
            if ($fileError !== UPLOAD_ERR_NO_FILE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
        }
    }

    // Memeriksa apakah kosong (tidak ada pesan dan tidak ada lampiran)
    if (empty($message) && !$attachment) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message or Attachment required']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO comments (report_id, user_id, message, attachment) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$reportId, $userId, $message, $attachment])) {

        // 1. Memperbarui status 'last_reply_by' pada laporan terkait
        $newReplyBy = ($role === 'admin') ? 'admin' : 'user';
        $stmt = $pdo->prepare("UPDATE reports SET last_reply_by = ? WHERE id = ?");
        $stmt->execute([$newReplyBy, $reportId]);

        // 2. Mengirimkan notifikasi email (Email Notification System)
        // Menentukan penerima
        $recipientEmail = '';
        $subject = "New Reply on Ticket #$reportId";
        $body = "There is a new reply on your ticket: \n\n" . $message . "\n\nLog in to view details.";

        if ($role === 'admin') {
            // Admin membalas -> Beritahu User
            // Kami sudah mengambil laporan di atas, tetapi tidak memilih email.
            // Di crud_comments GET, kami menggabungkan users, tetapi di sini kami hanya mengambil laporan.
            // Mari kita ambil ulang email pemilik.
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$report['user_id']]);
            $owner = $stmt->fetch();
            if ($owner)
                $recipientEmail = $owner['email'];
        } else {
            // User membalas -> Beritahu Admin
            // Dalam aplikasi nyata, Anda akan memiliki email admin khusus atau daftar.
            // Kami hanya akan mendefinisikan email admin sistem atau biarkan kosong jika tidak sederhana.
            // Mari kita asumsikan admin hardcoded untuk demo, atau loop semua admin.
            // Untuk keamanan/penghindaran spam dalam demo, mungkin lewati saja atau gunakan yang ketat.
            $recipientEmail = 'admin@example.com'; // Placeholder
        }

        if ($recipientEmail) {
            require_once 'PHPMailer/email_helper.php';
            if (file_exists('PHPMailer/email_helper.php')) {
                sendEmail($recipientEmail, $subject, $body);
            }
        }

        // Mengembalikan data yang baru disisipkan untuk pembaruan UI secara Real-time
        $newId = $pdo->lastInsertId();
        // Mengambil baris yang disisipkan agar presisi
        $stmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
        $stmt->execute([$newId]);
        $newComment = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'message' => 'Comment added', 'comment' => $newComment]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
