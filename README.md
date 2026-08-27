# Sistem Informasi Pemesanan dan Manajemen Inventaris Perlengkapan Acara Arsy Tenda

Sistem Informasi berbasis web untuk mengelola pemesanan, konfigurasi perlengkapan, inventaris, jadwal, lokasi acara, survei lapangan, dan pembayaran online terintegrasi (Midtrans & Google Maps).

---

## 🌟 Fitur Utama

- **Identitas Visual Monochrome & Modern**:
  - Dibangun dengan **Bootstrap 5**, **Inter Font**, dan palette monochrome elegan (`#111111`, `#333333`, `#FFFFFF`, `#F8F9FA`, `#E5E5E5`).
- **Event Rental Configurator (Pemesanan 6-Tahap)**:
  - Stepper terstruktur: `01 Acara` $\rightarrow$ `02 Perlengkapan` $\rightarrow$ `03 Konfigurasi` $\rightarrow$ `04 Lokasi` $\rightarrow$ `05 Checkout` $\rightarrow$ `06 Pembayaran`.
  - Konfigurator variasi tenda (ukuran, jenis penutup samping/full), meja, kursi, panggung, dan blower.
  - Peta interaktif (Leaflet / Google Maps koordinat GPS dan pencarian alamat pengiriman).
- **Engine Ketersediaan Berbasis Jadwal (*Double Booking Prevention*)**:
  - Perhitungan ketersediaan stok inventaris secara *real-time* berdasarkan jadwal acara untuk mencegah bentrokan sewa.
  - Status ketersediaan: `AVAILABLE` (Hijau), `LIMITED` (Kuning), dan `UNAVAILABLE` (Merah).
- **Payment Gateway Midtrans**:
  - Pembuatan Snap Token otomatis saat checkout dan popup pembayaran instan.
  - Webhook listener aman dengan verifikasi signature key.
- **Pengajuan Survei Lokasi**:
  - Form survei lapangan dengan penentuan pin peta lokasi untuk pengukuran tenda & akses jalan.
- **Dashboard Pelanggan**:
  - Ringkasan pesanan, riwayat sewa, kegiatan acara, pelacakan survei, dan pengaturan profil.
- **Panel Administrator & Operasional Lengkap**:
  - **Operasional**: Manajemen pesanan, kegiatan acara, dan survei lapangan.
  - **Inventaris**: CRUD peralatan, upload foto produk, satuan, dan variasi inventaris.
  - **Manajemen Pengguna**: Data pelanggan & data akun pekerja / petugas lapangan.
  - **Laporan**: Laporan pesanan & pendapatan, laporan penggunaan inventaris, dan laporan transaksi pembayaran dengan view cetak (*Print*).

---

## 🚀 Panduan Instalasi & Menjalankan Sistem

### 1. Clone Repository & Setup Dependensi
```bash
git clone https://github.com/Rainn404/ArsyTenda.git
cd ArsyTenda

# Install Dependensi PHP & Frontend
composer install
npm install
```

### 2. Konfigurasi Environment (.env)
Salin file `.env.example` ke `.env` dan atur konfigurasi database serta kredensial Midtrans:
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Migrasi & Seed Database
```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

### 4. Jalankan Server Lokal
```bash
npm run build
php artisan serve
```
Akses aplikasi melalui browser di: **http://127.0.0.1:8000**

---

## 🔑 Akun Bawaan Sistem (Demo)

| Role | Email | Password | Hak Akses |
|---|---|---|---|
| **Administrator** | `admin@example.com` | `password` | Panel Admin (`/admin`) |
| **Petugas Lapangan (Pekerja)** | `pekerja@example.com` | `password` | Panel Operasional (`/admin`) |
| **Pelanggan (Customer)** | `customer@example.com` | `password` | Katalog & Dashboard (`/dashboard`) |

---

## 🧪 Menjalankan Automated Tests
```bash
php artisan test
```

---

## 📄 Lisensi
Hak Cipta &copy; 2026 **Arsy Tenda**.
