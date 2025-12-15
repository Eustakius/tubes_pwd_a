# Sistem Pelaporan Insiden Siber (Cyber Incident Reporting System)
**Universitas Atma Jaya Yogyakarta**

**Tugas Besar Pengembangan Web (PWD)**

---

## 📝 Tentang Proyek Ini
Laporan proyek akhir ini disusun untuk memenuhi tugas mata kuliah Pengembangan Web di Universitas Atma Jaya Yogyakarta. Aplikasi ini dirancang sebagai sistem pelaporan insiden keamanan siber yang memungkinkan pelacakan, penanganan, dan dokumentasi insiden secara real-time dan terstruktur.

## ⚠️ Catatan Implementasi (PENTING)
Aplikasi ini saat ini dikonfigurasi dan berjalan sepenuhnya pada lingkungan **Localhost**.
Apabila aplikasi ini hendak diunggah ke layanan hosting (Production), diperlukan beberapa penyesuaian konfigurasi, antara lain:
1.  **Koneksi Database**: File `backend/config.php` harus disesuaikan dengan kredensial database server hosting.
2.  **Base URL**: Path absolut atau routing mungkin perlu disesuaikan dengan struktur folder di hosting.
3.  **Permissions**: Folder `backend/uploads/` memerlukan izin tulis (write permission/chmod 777 atau 755) agar fitur upload bukti dapat berjalan.

---

## 📋 Fitur Utama
Sebagai pengembang, saya telah mengimplementasikan fitur-fitur berikut untuk mendukung fungsionalitas sistem:

### 1. Panel Pengguna (User)
- **Pelaporan Insiden**: Pengguna dapat melaporkan insiden lengkap dengan Geo-tagging (Lokasi) dan kategori.
- **Upload Bukti**: Mendukung unggah gambar sebagai bukti otentik insiden.
- **Interaksi Real-time**: Fitur chat langsung dengan administrator untuk koordinasi penanganan tiket.

### 2. Panel Administrator
- **Dashboard Eksekutif**: Tampilan visual asimetris yang menyajikan statistik insiden (Open, In Progress, Closed).
- **Manajemen Tiket**: Kemampuan untuk memfilter, memantau, dan memperbarui status laporan.
- **Fitur Ekspor PDF**:
  - **Executive Summary**: Ringkasan statistik dan daftar insiden prioritas tinggi.
  - **Detail Report**: Laporan mendalam per tiket termasuk riwayat diskusi (chat) dan lampiran.

## 🛠️ Teknologi Pengembangan
Dalam pengembangan sistem ini, saya menggunakan teknologi berikut:
- **Backend**: Native PHP 8.x
- **Frontend**: HTML5, CSS3 (Modern UI), JavaScript (Ajax/XHR)
- **Database**: MariaDB / MySQL
- **Dependencies**: FPDF, PHPMailer

## 🚀 Panduan Instalasi (Lokal)

1. **Persiapan Database**
   - Buat database baru dengan nama `reporting_system`.
   - Import file `reporting_system_final.sql` yang telah saya sertakan.

2. **Konfigurasi**
   - Sesuaikan file `backend/config.php` dengan user/password database lokal Anda.

3. **Menjalankan Server**
   Gunakan PHP built-in server pada folder root proyek:
   ```bash
   php -S localhost:8000
   ```
   Akses aplikasi melalui: `http://localhost:8000/frontend/login.html`

---
**Disusun Oleh:**
Mahasiswa Universitas Atma Jaya Yogyakarta
