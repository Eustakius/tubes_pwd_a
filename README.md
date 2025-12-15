# Sistem Pelaporan Insiden Siber (Cyber Incident Reporting System)
**Universitas Atma Jaya Yogyakarta**

**Tugas Besar Pengembangan Web (PWD)**

---

## 📝 Tentang Proyek Ini
Laporan proyek akhir ini disusun untuk memenuhi tugas mata kuliah Pengembangan Web di Universitas Atma Jaya Yogyakarta. Aplikasi ini dirancang secara khusus sebagai sistem pelaporan insiden keamanan siber yang komprehensif. Sistem ini memungkinkan organisasi untuk melakukan pelacakan, penanganan, dan dokumentasi insiden keamanan secara real-time, terstruktur, dan akuntabel. Dengan antarmuka yang modern dan responsif, aplikasi ini memfasilitasi komunikasi yang efisien antara pelapor (user) dan tim keamanan (admin).

## ⚠️ Catatan Implementasi (PENTING)
Aplikasi ini saat ini dikonfigurasi dan berjalan sepenuhnya pada lingkungan **Localhost**.
Apabila aplikasi ini hendak diunggah ke layanan hosting (Production), diperlukan beberapa penyesuaian konfigurasi standar, antara lain:

1.  **Koneksi Database**: File `backend/config.php` harus disesuaikan dengan kredensial database server hosting yang digunakan.
2.  **Base URL**: Path absolut atau routing mungkin perlu disesuaikan dengan struktur folder di hosting.
3.  **Konfigurasi PHP**: Pastikan server mengizinkan fungsi `file_uploads` dan ekstensi `pdo_mysql` & `gd` aktif.

---

## 📋 Fitur Utama
Sebagai pengembang, saya telah mengimplementasikan fitur-fitur berikut untuk mendukung fungsionalitas sistem:

### 1. Panel Pengguna (User)
- **Pelaporan Insiden**: Pengguna dapat melaporkan insiden lengkap dengan Geo-tagging (Lokasi) dan kategori spesifik.
- **Upload Bukti**: Mendukung unggah gambar (JPG/PNG) sebagai bukti otentik insiden.
- **Interaksi Real-time**: Fitur chat langsung dengan administrator untuk koordinasi penanganan tiket, mendukung lampiran file.
- **Pelacakan Status**: Memantau perkembangan laporan dari status 'Open', 'In Progress', hingga 'Closed'.

### 2. Panel Administrator
- **Dashboard Eksekutif**: Tampilan visual asimetris yang menyajikan statistik insiden secara real-time.
- **Manajemen Tiket**: Kemampuan penuh untuk memfilter, memantau, dan memperbarui status laporan.
- **Fitur Ekspor PDF**:
  - **Executive Summary**: Ringkasan statistik manajerial dan daftar insiden prioritas tinggi.
  - **Detail Report**: Laporan mendalam per tiket termasuk riwayat diskusi (chat) dan lampiran visual.

## 🛠️ Teknologi Pengembangan
Dalam pengembangan sistem ini, saya menggunakan teknologi berikut:
- **Backend**: Native PHP 8.x
- **Frontend**: HTML5, CSS3 (Modern UI), JavaScript (Ajax/XHR)
- **Database**: MariaDB / MySQL
- **Dependencies**: FPDF, PHPMailer

---

## 🔑 Kredensial Akses (Login)
Berikut adalah akun yang sudah disiapkan dalam database (`reporting_system_final.sql`). Anda dapat langsung menggunakannya setelah import database.

### 1. Akun Administrator
Memiliki akses penuh ke Dashboard, Manajemen Laporan, dan Chat Admin.
- **Username**: `admin`
- **Password**: `admin123`

### 2. Akun Pengguna (User)
Akses untuk membuat laporan baru dan berkomunikasi dengan admin.
- **Username**: `testuser`
- **Password**: `user123`

*(Password yang tersimpan di database sudah di-hash. Gunakan password teks di atas saat login).*

---

## 🚀 Panduan Instalasi

### 1. Persiapan Database
- Pastikan aplikasi XAMPP/WAMP atau layanan database MySQL sudah berjalan.
- Buat database baru dengan nama `reporting_system`.
- Import file `reporting_system_final.sql` yang disertakan dalam folder proyek ini.

### 2. Konfigurasi Koneksi
- Buka file `backend/config.php` menggunakan teks editor (VS Code / Notepad).
- Sesuaikan pengaturan `$host`, `$dbname`, `$username`, dan `$password` dengan konfigurasi database lokal Anda.

### 3. Menjalankan Server
Anda dapat menjalankan aplikasi ini menggunakan PHP built-in server. Buka Command Prompt atau Terminal di dalam folder proyek, lalu ketik:

```bash
php -S localhost:8000
```

### 4. Akses Aplikasi
Buka browser (Chrome/Edge/Firefox) dan kunjungi alamat berikut:
`http://localhost:8000/frontend/login.html`

---
**Disusun Oleh:**
**Eustakius Satu Rajawali Ku**
**NPM: 220711648**
Mahasiswa Universitas Atma Jaya Yogyakarta
