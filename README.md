# Sistem Informasi Pemesanan dan Manajemen Inventaris Perlengkapan Acara Arsy Tenda

Sistem Informasi berbasis web untuk mengelola persewaan tenda, panggung, meja, kursi, dan perlengkapan pesta di Pelaihari, Kabupaten Tanah Laut, Kalimantan Selatan. Sistem ini mengintegrasikan ketersediaan inventaris berbasis jadwal (*real-time availability*), survei lapangan via Google Maps, pembayaran online terintegrasi (Midtrans Snap Sandbox), penguncian stok otomatis selama 5 jam, penugasan operasional pekerja, hingga pemeriksaan barang kembali dan denda kursi.

---

## 🌟 Fitur Utama Sistem

1. **Warm Editorial Design System**:
   - Tampilan profesional berstandar *Vercel Web Interface Guidelines* dan aksesibilitas **WCAG 2.1 AA**.
   - Tipografi elegan Plus Jakarta Sans dan Playfair Display, palet warna warm ivory/cream (`#FBF9F5`), border tegas `#191919`, dan kapsul pill responsif di desktop maupun mobile.

2. **Katalog & Konfigurator Sewa Real-Time**:
   - Pengecekan ketersediaan unit sewa secara *real-time* per tanggal rencana acara guna mencegah pemesanan ganda (*anti-overbooking*).
   - Dukungan variasi inventaris (ukuran tenda, penutup samping, varian meja/kursi) yang menyesuaikan stok dan harga otomatis.

3. **Integrasi Google Maps Platform**:
   - **Maps JavaScript API**: Penentuan titik lokasi pengiriman acara dan survei lapangan dengan pin peta interaktif.
   - **Geocoding & Reverse Geocoding API**: Konversi alamat ke koordinat lintang/bujur dan sebaliknya secara otomatis.
   - **Google Routes API**: Kalkulasi jarak dan rute pengiriman armada logistik dari kantor Arsy Tenda Pelaihari (`-3.7955, 114.7712`), dengan transparansi ongkir (gratis $\le 15\text{ km}$, selebihnya Rp 5.000/km ekstra).

4. **Penguncian Stok (5-Hour Hold) & Pembayaran Midtrans Snap**:
   - Penguncian stok otomatis selama maksimal 5 jam saat pesanan dibuat.
   - Laravel Scheduler (`orders:release-expired-holds`) otomatis melepas stok jika pembayaran tidak diselesaikan dalam 5 jam.
   - Re-validasi ketersediaan stok sebelum pembuatan pembayaran baru untuk mencegah overbooking.
   - Integrasi Midtrans Snap Sandbox dengan verifikasi SHA512 signature key pada webhook callback.

5. **Layanan Survei Lapangan**:
   - Pengajuan survei titik lokasi acara sebelum checkout utama.
   - Rekomendasi teknis perlengkapan dari petugas lapangan yang dapat langsung dikonversi pelanggan ke keranjang belanja.

6. **Panel Administrator & Petugas Lapangan (Role-Based Access Control)**:
   - **Admin**: Manajemen transaksi, verifikasi pembayaran manual dengan audit log, manajemen inventaris, penugasan teknisi lapangan, dan verifikasi pemeriksaan barang kembali.
   - **Pekerja/Petugas**: Melihat daftar tugas lapangan yang ditugaskan (persiapan, pengiriman, pemasangan, pemeriksaan), memperbarui progres pengerjaan, dan mencatat kondisi fisik barang kembali (termasuk denda kursi rusak/hilang).
   - **Pelanggan**: Dashboard riwayat sewa, status pembayaran instan, peta lokasi acara, dan pengaturan akun.

---

## 🚀 Panduan Instalasi & Menjalankan Sistem di Lingkungan Lokal

### 1. Prasyarat Sistem
- **PHP**: ^8.2 atau ^8.3 (dengan ekstensi `pdo_sqlite` / `pdo_mysql`, `curl`, `mbstring`, `openssl`)
- **Composer**: ^2.2
- **Node.js**: ^18.0 atau ^20.0
- **NPM**: ^9.0

### 2. Clone Repository & Setup Dependensi
```bash
git clone https://github.com/Rainn404/TugasAkhir.git
cd TugasAkhir

# Install dependensi PHP
composer install

# Install dependensi frontend
npm install
```

### 3. Konfigurasi Environment (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi Midtrans dan Google Maps Platform di dalam file `.env`:
```ini
# Midtrans Payment Gateway (Sandbox)
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=your-midtrans-server-key
MIDTRANS_CLIENT_KEY=your-midtrans-client-key

# Google Maps Platform
GOOGLE_MAPS_API_KEY=your-maps-api-key
GOOGLE_MAPS_CLIENT_KEY=your-maps-browser-client-key
GOOGLE_MAPS_SERVER_KEY=your-maps-server-key
ARSY_TENDA_LOCATION_NAME="Kantor Arsy Tenda Pelaihari"
ARSY_TENDA_LATITUDE=-3.7955
ARSY_TENDA_LONGITUDE=114.7712
```

### 4. Migrasi & Seed Database
```bash
# Menjalankan migrasi database dan seeding data inventaris bawaan
php artisan migrate --seed

# Membuat symlink storage foto produk
php artisan storage:link
```

### 5. Build Aset Frontend & Jalankan Server Lokal
```bash
# Kompilasi aset CSS dan JavaScript Vite
npm run build

# Menjalankan development server Laravel
php artisan serve
```
Akses aplikasi melalui browser di: **http://127.0.0.1:8000**

---

## 🔑 Akun Demo Pengembangan Lokal

> [!NOTE]
> Akun bawaan berikut hanya dibuat pada lingkungan lokal (`APP_ENV=local`). Seeder secara otomatis mengabaikan pembuatan akun demo ini pada lingkungan produksi (`production`).

| Role Pengguna | Alamat Email | Kata Sandi | Akses Panel |
|---|---|---|---|
| **Administrator** | `admin@example.com` | `password` | `/admin` (Manajemen Penuh) |
| **Petugas Lapangan (Pekerja)** | `pekerja@example.com` | `password` | `/admin` (Tugas Operasional) |
| **Pelanggan (Customer)** | `customer@example.com` | `password` | `/dashboard` & Katalog |

---

## 🧪 Menjalankan Automated Test Suite

Seluruh logika bisnis, pencegahan overbooking, otentikasi role, webhook pembayaran, dan integrasi Google Maps telah dilengkapi dengan automated tests berbasis PHPUnit / Laravel Testing:

```bash
# Menjalankan seluruh pengujian (Feature & Unit Tests)
php artisan test
```

Target kualitas: **55+ tests passed (300+ assertions, 0 errors, 0 failures)**.

---

## 📄 Lisensi & Hak Cipta
Hak Cipta &copy; 2026 **Arsy Tenda Pelaihari**. Seluruh hak cipta dilindungi undang-undang.
