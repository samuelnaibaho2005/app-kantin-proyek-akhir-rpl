[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/) [![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://www.mysql.com/) [![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)](https://getbootstrap.com/) [![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
# 🍽️ Sistem Informasi Kantin Kampus

Aplikasi web untuk memudahkan pemesanan makanan dan pengelolaan keuangan kantin kampus (UMKM). Dibangun dengan PHP Native, MySQL, dan Bootstrap 5.

![Kantin Kampus Banner](https://via.placeholder.com/1200x400/667eea/ffffff?text=Kantin+Kampus+-+Sistem+Informasi+UMKM)

---

## ✨ Fitur Utama

### 🏪 Untuk Pemilik Kantin:
- ✅ **Dashboard Real-time** dengan statistik penjualan & grafik
- ✅ **Kelola Menu** (CRUD) dengan upload foto
- ✅ **Manajemen Pesanan** dengan update status otomatis
- ✅ **Pencatatan Keuangan** (pemasukan & pengeluaran)
- ✅ **Laporan Keuangan** dengan breakdown & visualisasi
- ✅ **Analisis Bisnis** (menu terlaris, jam ramai, profit/loss)
- ✅ **Auto-sync** transaksi dari pesanan ke laporan

### 👥 Untuk Customer (Mahasiswa/Staf):
- ✅ **Browse Menu** dengan filter kategori & pencarian
- ✅ **Shopping Cart** dengan AJAX (no reload)
- ✅ **Checkout** dengan pilihan dine-in/takeaway
- ✅ **Tracking Status** pesanan real-time
- ✅ **Riwayat Pesanan** lengkap
- ✅ **Profile Management** dengan upload foto

### 🔒 Keamanan:
- ✅ **Password Hashing** dengan bcrypt
- ✅ **Prepared Statements** (SQL Injection Prevention)
- ✅ **Role-based Access Control**
- ✅ **Input Validation & Sanitization**
- ✅ **Session Management**
- ✅ **Soft Delete** untuk data integrity

---

## 📸 Demo & Screenshots

### Dashboard Kantin
![Dashboard Kantin](https://via.placeholder.com/800x500/667eea/ffffff?text=Dashboard+Kantin)

### Dashboard Customer
![Kelola Menu](https://via.placeholder.com/800x500/764ba2/ffffff?text=Kelola+Menu)

---

## 🛠️ Teknologi Stack

| Kategori | Teknologi |
|----------|-----------|
| **Frontend** | HTML5, CSS3, Bootstrap 5.3, JavaScript (Vanilla) |
| **Backend** | PHP 8.1+ (Native, tanpa framework) |
| **Database** | MySQL 8.0+ |
| **Charting** | Chart.js 4.x |
| **Icons** | Bootstrap Icons 1.11 |
| **Server** | Apache 2.4+ (Laragon) |

---

## 🚀 Instalasi

### Requirement

Pastikan sudah terinstall:
- **PHP** >= 8.1
- **MySQL** >= 8.0
- **Apache** Web Server
- **Laragon/XAMPP/MAMP** (rekomendasi)

### Step 1: Clone Repository

```bash
git clone https://github.com/samuelnaibaho2005/app-kantin-proyek-akhir-rpl.git
cd app-kantin-proyek-akhir-rpl
```

### Step 2: Setup Database

1. Buka **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Buat database baru: `kantin_kampus`
3. Import file SQL:
   - Pilih database `kantin_kampus`
   - Klik tab **Import**
   - Pilih file `database.sql`
   - Klik **Go**

### Step 3: Konfigurasi Database

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Sesuaikan dengan MySQL user
define('DB_PASS', '');              // Sesuaikan dengan MySQL password
define('DB_NAME', 'kantin_kampus');
```
### Step 4: Jalankan Aplikasi

**Untuk Laragon:**
```
http://localhost:8080/ proyek-akhir-kantin-rpl/
```

**Untuk XAMPP:**
```
http://localhost/ proyek-akhir-kantin-rpl/
```

---

## 📁 Struktur Folder

```
kantin-kampus/
├── assets/
│   ├── css/
│   │   └── style.css          # Custom styles
│   ├── js/
│   │   └── app.js             # Custom JavaScript
│   └── img/
│       └── logo.png
├── config/
│   └── database.php           # Database connection & helpers
├── includes/
│   ├── header.php             # Global header
│   └── footer.php             # Global footer
├── auth/
│   ├── login.php              # Login page
│   ├── register.php           # Registration page
│   ├── logout.php             # Logout handler
│   └── forgot-password.php    # Password reset
├── dashboard/
│   ├── kantin.php             # Dashboard untuk kantin
│   └── customer.php           # Dashboard untuk customer
├── menu/
│   ├── index.php              # Browse menu (customer)
│   ├── manage.php             # List menu (kantin)
│   ├── create.php             # Tambah menu
│   ├── edit.php               # Edit menu
│   └── delete.php             # Hapus menu
├── order/
│   ├── cart.php               # Shopping cart
│   ├── checkout.php           # Checkout page
│   ├── status.php             # Status tracking
│   └── manage.php             # Kelola pesanan (kantin)
├── transaction/
│   ├── index.php              # List transaksi
│   ├── create.php             # Tambah transaksi
│   ├── edit.php               # Edit transaksi
│   └── delete.php             # Hapus transaksi
├── profile/
│   └── index.php              # Profile management
├── api/
│   ├── add-to-cart.php        # API add to cart
│   ├── update-cart.php        # API update cart
│   └── get-order-detail.php   # API order detail
├── uploads/
│   ├── menus/                 # Upload foto menu
│   └── profiles/              # Upload foto profil
├── database.sql               # Database schema & dummy data
├── index.php                  # Landing page
├── README.md                  # This file
└── LICENSE                    # License file
```

---

## 📖 Penggunaan

### 1. Login sebagai Kantin

1. Akses: `http://localhost:8080/kantin-kampus/auth/login.php`
2. Login dengan akun kantin
3. Dashboard akan menampilkan:
   - Statistik penjualan hari ini
   - Grafik penjualan 7 hari terakhir
   - Menu terlaris
   - Jam ramai
   - Pesanan terbaru

### 2. Kelola Menu

1. Menu → Kelola Menu
2. Klik **"Tambah Menu Baru"**
3. Isi form: Nama, Kategori, Harga, Stok, Foto
4. Klik **"Simpan Menu"**

### 3. Kelola Pesanan

1. Menu → Pesanan
2. Filter berdasarkan status/tanggal
3. Update status pesanan:
   - Pending → **Proses**
   - Processing → **Siap**
   - Ready → **Selesai**
4. Status "Selesai" akan otomatis insert ke laporan keuangan

### 4. Pencatatan Keuangan

1. Menu → Keuangan
2. Tab **"Daftar Transaksi"**: Lihat semua transaksi
3. Tab **"Laporan"**: Lihat breakdown pengeluaran
4. Klik **"Tambah Transaksi"** untuk input manual

### 5. Pesan sebagai Customer

1. Login sebagai mahasiswa/staf
2. Browse menu → Pilih menu
3. Klik **"Tambah ke Keranjang"**
4. Klik icon keranjang → **"Checkout"**
5. Pilih tipe pesanan & metode pembayaran
6. Klik **"Konfirmasi Pesanan"**
7. Track status di **"Pesanan Saya"**

---

## 👨‍💻 Developer

Tugas Akhir Mata Kuliah **Rekayasa Perangkat Lunak**

**Anggota Tim:**
- Samuel Nikolas Naibaho - Backend Developer
- Ria Adelina - System Analyst and Frontend Developer
- Ronatal Habeahan - System Analyst
- Alif Asyari - Database Architect and Project Manager

---

<div align="center">

**⭐ Jika project ini bermanfaat, jangan lupa kasih Star! ⭐**

Made with ❤️ by Team 4 RPL

</div>

---

## 📄 Lisensi

Distributed under the **MIT License**. See [MIT License](LICENSE) for more information.
