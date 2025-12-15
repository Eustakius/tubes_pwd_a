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
    // Get comments for a report
    $reportId = isset($_GET['report_id']) ? (int) $_GET['report_id'] : 0;

    // Check permission: User must own report OR be admin
    // For simplicity efficiently: Admin can see all, User can see if they are owner.
    // We'll trust the report owning check to a joined query or pre-check.

    // Simplest: Just fetch. If empty return [].
    // ideally check ownership.
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
    // Handle both JSON (legacy) and Multipart (new)
    // Actually, it's safer to just rely on POST for the new feature or check content type.
    // But since we are upgrading the frontend, we can just switch to $_POST.

    $reportId = $_POST['report_id'] ?? null;
    $message = $_POST['message'] ?? '';

    // Fallback for JSON requests if any legacy clients (optional, but good practice)
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

    // Verify ownership/permission (same logic)
    $stmt = $pdo->prepare("SELECT user_id, status FROM reports WHERE id = ?");
    $stmt->execute([$reportId]);
    $report = $stmt->fetch();

    if (!$report) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }

    $role = $_SESSION['role'] ?? 'user';
    // Logic: Admin can reply to anyone. User can only reply to own.
    if ($role !== 'admin' && $report['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    // Check status
    if ($report['status'] === 'closed' && $role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ticket is closed']);
        exit;
    }

    // Handle Attachment
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
            // Map PHP error codes to messages
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
            // If it's just "No file", we ignore (it's optional). But if name was set and we are here, it's an error.
            if ($fileError !== UPLOAD_ERR_NO_FILE) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $msg]);
                exit;
            }
        }
    }

    // Check if empty (no message and no attachment)
    if (empty($message) && !$attachment) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message or Attachment required']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO comments (report_id, user_id, message, attachment) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$reportId, $userId, $message, $attachment])) {

        // 1. Update last_reply_by
        $newReplyBy = ($role === 'admin') ? 'admin' : 'user';
        $stmt = $pdo->prepare("UPDATE reports SET last_reply_by = ? WHERE id = ?");
        $stmt->execute([$newReplyBy, $reportId]);

        // 2. Send Email Notification
        // Determine recipient
        $recipientEmail = '';
        $subject = "New Reply on Ticket #$reportId";
        $body = "There is a new reply on your ticket: \n\n" . $message . "\n\nLog in to view details.";

        if ($role === 'admin') {
            // Admin replied -> Notify User
            // We already fetched report above, but didn't select email. 
            // In crud_comments GET, we join users, but here we only fetched report.
            // Let's re-fetch owner email
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$report['user_id']]);
            $owner = $stmt->fetch();
            if ($owner)
                $recipientEmail = $owner['email'];
        } else {
            // User replied -> Notify Admin
            // In a real app, you'd have a specific admin email or a list. 
            // We'll just define a system admin email or leave blank if not simple.
            // Let's assume a hardcoded admin for demo, or loop all admins.
            // For safety/spam avoidance in demo, maybe just skip or use a strict one.
            $recipientEmail = 'admin@example.com'; // Placeholder
        }

        if ($recipientEmail) {
            require_once 'PHPMailer/email_helper.php';
            if (file_exists('PHPMailer/email_helper.php')) {
                sendEmail($recipientEmail, $subject, $body);
            }
        }

        // Return the actual inserted data for real-time UI
        $newId = $pdo->lastInsertId();
        // Fetch the inserted row to be precise
        $stmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = ?");
        $stmt->execute([$newId]);
        $newComment = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'message' => 'Comment added', 'comment' => $newComment]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
