# Sistem Pelaporan Insiden Siber (Cyber Incident Reporting System)
**Universitas Atma Jaya Yogyakarta**

Aplikasi berbasis web untuk pelaporan, pelacakan, dan penanganan insiden keamanan siber secara real-time.

## 📋 Fitur Utama

### 1. Panel Pengguna (User)
- **Pelaporan Insiden**: Melaporkan insiden dengan Judul, Deskripsi, Kategori, Prioritas, dan Lokasi (Geo-tagging).
- **Upload Bukti**: Mendukung unggah gambar dan file PDF sebagai bukti insiden.
- **Real-time Chat**: Diskusi langsung dengan Admin terkait tiket yang dibuat, termasuk kirim lampiran file.
- **Status Tracking**: Memantau status laporan (Open, In Progress, Closed).

### 2. Panel Administrator
- **Dashboard Eksekutif**: Tampilan grid asimetris yang modern dengan statistik real-time.
- **Manajemen Laporan**: Filter laporan berdasarkan status, ubah status, dan hapus laporan.
- **Export PDF Canggih**:
  - **Laporan Ringkasan**: Unduh ringkasan eksekutif berisi statistik dan daftar insiden prioritas tinggi.
  - **Laporan Detail**: Unduh detail per tiket lengkap dengan riwayat chat dan gambar bukti.

## 🛠️ Teknologi yang Digunakan
- **Backend**: Native PHP 8.x
- **Frontend**: HTML5, CSS3 (Modern Dashboard Layout), Vanilla JavaScript
- **Database**: MariaDB / MySQL
- **Dependencies**: FPDF (untuk generate PDF), PHPMailer

## 🚀 Cara Instalasi

1. **Persiapan Database**
   - Buat database baru bernama `reporting_system`.
   - Import file `reporting_system_final.sql` ke dalam database tersebut.

2. **Konfigurasi**
   - Buka file `backend/config.php`.
   - Sesuaikan konfigurasi database (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`).

3. **Jalankan Server**
   Buka terminal di folder root project dan jalankan PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
   Atau letakkan folder ini di dalam `htdocs` jika menggunakan XAMPP/Apache.

4. **Akses Aplikasi**
   - Buka browser dan akses: `http://localhost:8000/frontend/login.html`
   - **Akun Admin Default**:
     - Username: `admin`
     - Password: `123` (atau sesuaikan dengan hash di database)

## 📝 Catatan Pengembang
Project ini dibuat sebagai Tugas Besar Pengembangan Web (PWD) di Universitas Atma Jaya Yogyakarta.
Didesain dengan antarmuka yang responsif dan fitur keamanan standar (Prepared Statements, XSS Protection, Password Hashing).
