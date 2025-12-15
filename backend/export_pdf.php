<?php
require_once 'config.php';
require_once 'fpdf.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

// Logika Header dipindahkan ke CustomPDF untuk menangani Layout Single/All secara dinamis.


// Validasi Parameter ID Laporan
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 1000;

// Pengambilan Data dari Database
if ($id) {
    // Mode Laporan Tunggal
    $stmt = $pdo->prepare("SELECT r.*, u.username, u.email FROM reports r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->execute([$id]);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = "Report Detail #" . $id;
} else {
    // Mode Laporan Menyeluruh (Rekapitulasi)
    // Verifikasi Hak Akses Administrator
    if (isset($user['role']) && $user['role'] === 'admin') {
        $stmt = $pdo->query("SELECT r.*, u.username, u.email FROM reports r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT r.*, u.username, u.email FROM reports r JOIN users u ON r.user_id = u.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
    }
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = "Cyber Incident Reports - Full Export";
}

// Override Header untuk fleksibilitas tampilan dokumen PDF
class CustomPDF extends FPDF
{
    public $isDetail = false;

    function Header()
    {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Cyber Incident RMS', 0, 1, 'C');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i'), 0, 1, 'C');
        $this->Ln(5);

        if (!$this->isDetail) {
            // Header Tabel Data
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(230, 230, 230);

            // Tata Letak Halaman Format A4 Landscape
            $this->Cell(10, 8, 'ID', 1, 0, 'C', true);
            $this->Cell(30, 8, 'Date', 1, 0, 'C', true);
            $this->Cell(30, 8, 'Reporter', 1, 0, 'C', true);
            $this->Cell(25, 8, 'Category', 1, 0, 'C', true);
            $this->Cell(20, 8, 'Priority', 1, 0, 'C', true);
            $this->Cell(20, 8, 'Status', 1, 0, 'C', true);
            $this->Cell(80, 8, 'Title/Desc', 1, 0, 'C', true);
            $this->Cell(0, 8, 'Location', 1, 1, 'C', true);
        }
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new CustomPDF('L', 'mm', 'A4');
$pdf->isDetail = ($id != null);
$pdf->SetCreator('CyberApp RMS');
$pdf->SetTitle($title);
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Arial', '', 9);

if ($id && !empty($reports)) {
    // === TAMPILAN DETAIL TUNGGAL ===
    $row = $reports[0];

    // Informasi Header Laporan
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Report #' . $row['id'] . ': ' . $row['title'], 0, 1, 'L');
    $pdf->Ln(5);

    $pdf->SetFont('Arial', '', 11);

    // Indikator Status Laporan
    $pdf->Cell(30, 8, 'Status:', 0, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(50, 8, strtoupper($row['status']), 0, 0);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(30, 8, 'Priority:', 0, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(50, 8, $row['priority'] ?? 'Low', 0, 1);

    // Meta Data Laporan
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(30, 8, 'Category:', 0, 0);
    $pdf->Cell(50, 8, $row['category'] ?? '-', 0, 0);
    $pdf->Cell(30, 8, 'Date:', 0, 0);
    $pdf->Cell(50, 8, $row['created_at'], 0, 1);
    $pdf->Cell(30, 8, 'Reporter:', 0, 0);
    $pdf->Cell(50, 8, $row['username'] . ' (' . $row['email'] . ')', 0, 1);
    $pdf->Ln(5);

    // Deskripsi Kejadian
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Description', 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 11);
    $pdf->MultiCell(0, 6, $row['description'] . "\n\nLocation: " . $row['location'], 0, 'L');
    $pdf->Ln(5);

    // Bukti Pendukung
    if ($row['evidence']) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Attached Evidence', 0, 1, 'L', true);
        $pdf->Ln(2);
        $ext = strtolower(pathinfo($row['evidence'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $imgPath = 'uploads/' . $row['evidence'];
            if (file_exists($imgPath)) {
                // Penyesuaian ukuran gambar agar proporsional (max lebar 150mm)
                $pdf->Image($imgPath, null, null, 150);
            } else {
                $pdf->Cell(0, 6, '[Image file not found on server]', 0, 1);
            }
        } else {
            $pdf->Cell(0, 6, '[Attachment: ' . $row['evidence'] . ']', 0, 1);
        }
    }

    // === RIWAYAT DISKUSI / CHAT ===
    $stmtC = $pdo->prepare("SELECT c.*, u.username, u.role FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.report_id = ? ORDER BY c.created_at ASC");
    $stmtC->execute([$row['id']]);
    $comments = $stmtC->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($comments)) {
        $pdf->Ln(10);
        $pdf->SetFillColor(230, 230, 250);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Discussion History (' . count($comments) . ' messages)', 0, 1, 'L', true);
        $pdf->Ln(2);

        foreach ($comments as $c) {
            $isAdmin = ($c['role'] === 'admin');
            $pdf->SetFont('Arial', 'B', 10);
            $roleLabel = $isAdmin ? ' (Admin)' : '';
            $pdf->Cell(0, 6, $c['username'] . $roleLabel . ' - ' . substr($c['created_at'], 0, 16), 0, 1);

            $pdf->SetFont('Arial', '', 10);
            if (!empty($c['message'])) {
                $pdf->MultiCell(0, 6, $c['message']);
            }

            // Lampiran File dalam Chat
            if (!empty($c['attachment'])) {
                $attPath = 'uploads/' . $c['attachment'];
                if (file_exists($attPath)) {
                    $ext = strtolower(pathinfo($c['attachment'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        // Menyematkan Gambar ke dalam PDF
                        // Lebar maksimum 80mm
                        $pdf->Image($attPath, null, null, 80);
                        $pdf->Ln(2);
                    } else {
                        // Tautan/Teks Referensi File
                        $pdf->SetFont('Arial', 'I', 9);
                        $pdf->Cell(0, 6, '[Attachment: ' . $c['attachment'] . ']', 0, 1);
                    }
                } else {
                    $pdf->SetFont('Arial', 'I', 9);
                    $pdf->Cell(0, 6, '[Attachment Found but File Missing: ' . $c['attachment'] . ']', 0, 1);
                }
            }
            // Garis Pemisah (Divider)
            $pdf->SetDrawColor(220, 220, 220);
            $pdf->Line($pdf->GetX(), $pdf->GetY() + 2, $pdf->GetX() + 190, $pdf->GetY() + 2);
            $pdf->Ln(4);
        }
    }

} else {
    // === TAMPILAN RINGKASAN EKSEKUTIF ===

    // 1. Perhitungan Statistik Insiden
    $total = count($reports);
    $stats = ['open' => 0, 'progress' => 0, 'closed' => 0];
    $prio = ['Critical' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0];

    foreach ($reports as $r) {
        $stats[strtolower($r['status'])]++;
        $p = $r['priority'] ?? 'Low';
        if (isset($prio[$p]))
            $prio[$p]++;
    }

    // 2. Render Header Dokumen Ringkasan
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Executive Summary Report', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 10, 'Overview of incident status and critical items.', 0, 1, 'L');
    $pdf->Ln(5);

    // 3. Bagian Statistik Utama
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, '1. Status Overview', 0, 1);

    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(40, 10, 'Total Reports: ' . $total, 0, 1);
    $pdf->Cell(40, 8, ' - Open: ' . $stats['open'], 0, 1);
    $pdf->Cell(40, 8, ' - In Progress: ' . $stats['progress'], 0, 1);
    $pdf->Cell(40, 8, ' - Closed: ' . $stats['closed'], 0, 1);
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, '2. Priority Breakdown', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(40, 8, ' - Critical: ' . $prio['Critical'], 0, 1);
    $pdf->Cell(40, 8, ' - High: ' . $prio['High'], 0, 1);
    $pdf->Cell(40, 8, ' - Medium: ' . $prio['Medium'], 0, 1);
    $pdf->Cell(40, 8, ' - Low: ' . $prio['Low'], 0, 1);
    $pdf->Ln(10);

    // 4. Daftar Perhatian Kritis & Prioritas Tinggi
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, '3. Critical & High Priority Items (Recent)', 0, 1);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);

    // Header Tabel Ringkasan
    $pdf->Cell(15, 8, 'ID', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Date', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Priority', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Status', 1, 0, 'C', true);
    $pdf->Cell(0, 8, 'Title', 1, 1, 'L', true);

    $pdf->SetFont('Arial', '', 10);
    $count = 0;
    foreach ($reports as $row) {
        $p = $row['priority'] ?? 'Low';
        if ($p === 'Critical' || $p === 'High') {
            if ($count >= 15)
                break; // Batasi tampilan hingga 15 item terbaru

            $pdf->Cell(15, 8, $row['id'], 1, 0, 'C');
            $pdf->Cell(30, 8, substr($row['created_at'], 0, 10), 1, 0, 'C');

            // Penebalan Teks untuk Prioritas Tinggi/Kritis
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(25, 8, $p, 1, 0, 'C');

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(25, 8, $row['status'], 1, 0, 'C');
            $pdf->Cell(0, 8, substr($row['title'], 0, 50), 1, 1, 'L');
            $count++;
        }
    }
    if ($count == 0) {
        $pdf->Cell(0, 8, 'No critical or high priority items found.', 1, 1, 'C');
    }

}

$pdf->Output('I', $title . '.pdf');