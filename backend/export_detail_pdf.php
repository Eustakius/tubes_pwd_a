<?php
require 'config.php';
require 'fpdf.php';

if (!isset($_GET['id'])) {
    die("Report ID required");
}

$id = (int) $_GET['id'];
$userId = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'user';

// Fetch Report
$stmt = $pdo->prepare("SELECT r.*, u.username, u.email FROM reports r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    die("Report not found");
}

// Permission Check
if ($role !== 'admin' && $report['user_id'] != $userId) {
    die("Unauthorized");
}

// Fetch Comments
$stmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.report_id = ? ORDER BY c.created_at ASC");
$stmt->execute([$id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Incident Details Report', 0, 1, 'C');
        $this->Ln(5);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, 25, 200, 25);
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Generated on ' . date('Y-m-d H:i') . ' | Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// --- Report Details ---
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Case #' . $report['id'] . ': ' . $report['title'], 0, 1, 'L', true);
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 8, 'Status:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 8, strtoupper($report['status']), 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 8, 'Priority:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 8, $report['priority'] ?? 'Low', 0, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 8, 'Category:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 8, $report['category'] ?? 'Other', 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 8, 'Date:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 8, $report['created_at'], 0, 1);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 8, 'Reporter:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 8, $report['username'] . ' (' . $report['email'] . ')', 0, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(30, 8, 'Location:', 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 8, $report['location'] ?: 'N/A', 0, 1);

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, 'Description:', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 6, $report['description']);
$pdf->Ln(5);

// --- Evidence Section ---
if ($report['evidence']) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, 'Evidence Attached:', 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Filename: ' . $report['evidence'], 0, 1);

    // Attempt to show image if it's an image
    $ext = strtolower(pathinfo($report['evidence'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        $imgPath = __DIR__ . '/uploads/' . $report['evidence'];
        if (file_exists($imgPath)) {
            $pdf->Ln(2);
            // Limit width to 100
            $pdf->Image($imgPath, null, null, 100);
            $pdf->Ln(5);
        }
    } else {
        $pdf->Cell(0, 6, '(File is PDF or other format, please file details online)', 0, 1);
    }
}

// --- Chat Legend/Transcript ---
$pdf->Ln(10);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetFillColor(50, 50, 50);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, ' Discussion Transcript', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(2);

if (empty($comments)) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 10, 'No discussion history recorded.', 0, 1);
} else {
    foreach ($comments as $c) {
        $date = substr($c['created_at'], 0, 16);
        $user = $c['username'];
        $msg = $c['message'];

        // Box for each comment
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Cell(0, 6, "$user ($date)", 0, 1, 'L', true);

        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 6, $msg);
        $pdf->Ln(2); // Spacing
    }
}

$pdf->Output('I', 'Incident_Report_' . $id . '.pdf');
