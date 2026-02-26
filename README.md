# 🎮 GameVault — Dokumentasi Proyek

## Cara Setup

### 1. Prasyarat
- PHP 7.4+
- MySQL 5.7+ / MariaDB 10+
- Web server (XAMPP / Laragon / dsb.)

### 2. Jalankan Database
Buka phpMyAdmin atau MySQL CLI, lalu jalankan:
```sql
source /path/to/gamelibrary/database.sql
```

### 3. Konfigurasi Koneksi
Edit file `includes/connection.php`:
```php
define('DB_HOST', 'localhost');   // host database
define('DB_USER', 'root');        // username MySQL
define('DB_PASS', '');            // password MySQL
define('DB_NAME', 'gamelibrary_db');
```

### 4. Pastikan Folder Uploads Writable
```bash
chmod 755 assets/uploads/covers/
```

### 5. Akses Website
Buka browser: `http://localhost/gamelibrary/`

---

## 📁 Struktur Folder & File

```
gamelibrary/
│
├── index.php                  ← Halaman utama (SPA – Single Page App)
│                                  Berisi: Landing, Library, Account page
│                                  + semua overlay modal (auth, add/edit game, delete confirm)
│
├── database.sql               ← Script SQL untuk membuat database & tabel
│
├── includes/                  ← Semua logika backend (PHP)
│   ├── connection.php         ← Koneksi ke MySQL (define DB_HOST, USER, PASS, NAME)
│   ├── auth.php               ← Register, Login, Logout
│   ├── account.php            ← Get profil, Edit nickname/email, Ganti password, Hapus akun
│   ├── games.php              ← CRUD game: Add, Edit, Delete, Get games, Get categories
│   └── session_check.php      ← Cek status sesi login (dipanggil saat halaman dimuat)
│
├── assets/
│   ├── css/
│   │   └── style.css          ← Semua styling (dark theme, komponen, layout)
│   ├── js/
│   │   └── app.js             ← Semua logika frontend (SPA routing, fetch API, render)
│   └── uploads/
│       └── covers/            ← Folder penyimpanan cover game yang diupload user
│           └── .gitkeep
│
└── README.md                  ← Dokumentasi ini
```

---

## ⚙️ Penjelasan File Utama

### `includes/connection.php`
Koneksi database menggunakan `mysqli`. Semua file PHP backend me-`require_once` file ini.

### `includes/auth.php`
Menangani 3 aksi via POST:
- `action=register` → validasi & simpan user baru
- `action=login`    → verifikasi credential & set session
- `action=logout`   → destroy session

### `includes/account.php`
Menangani aksi akun (semua wajib login/session valid):
- `action=get_profile`    → ambil data user dari DB
- `action=edit_profile`   → update nickname & email
- `action=change_password`→ verifikasi old pass, update new pass
- `action=delete_account` → verifikasi pass, hapus user & semua gamenya

### `includes/games.php`
CRUD library game (wajib login):
- `action=get_games`      → ambil daftar game + filter genre/platform/search
- `action=get_categories` → ambil daftar genre & platform unik milik user
- `action=add_game`       → tambah game baru (+ opsional upload cover)
- `action=edit_game`      → update game (+ opsional ganti/hapus cover)
- `action=delete_game`    → hapus game & file cover-nya

### `assets/js/app.js`
SPA logic menggunakan vanilla JS + Fetch API:
- Routing: `showLanding()`, `showLibrary()`, `showAccount()`
- Auth: login/register/logout via fetch ke `auth.php`
- Games: render grid, filter, search, CRUD via fetch ke `games.php`
- Account: load profil, edit, ganti password, hapus akun via fetch ke `account.php`

---

## 🗄️ Struktur Database

### Tabel `users`
| Kolom      | Tipe         | Keterangan                |
|------------|--------------|---------------------------|
| id         | INT (PK, AI) | ID unik user              |
| username   | VARCHAR(50)  | Username unik             |
| nickname   | VARCHAR(50)  | Nama tampilan             |
| email      | VARCHAR(100) | Email unik                |
| password   | VARCHAR(255) | bcrypt hash               |
| avatar     | VARCHAR(255) | (opsional, belum dipakai) |
| created_at | TIMESTAMP    | Waktu daftar              |

### Tabel `games`
| Kolom       | Tipe          | Keterangan                |
|-------------|---------------|---------------------------|
| id          | INT (PK, AI)  | ID unik game              |
| user_id     | INT (FK)      | Referensi ke `users.id`   |
| title       | VARCHAR(200)  | Judul game                |
| genre       | VARCHAR(100)  | Genre (input manual)      |
| platform    | VARCHAR(100)  | Platform (combobox/manual)|
| cover_image | VARCHAR(255)  | Path file cover           |
| notes       | TEXT          | Catatan opsional          |
| created_at  | TIMESTAMP     | Waktu ditambahkan         |
| updated_at  | TIMESTAMP     | Waktu terakhir diupdate   |

---

## 🎨 Fitur Website

- ✅ **Landing page** dengan hero section
- ✅ **Overlay Login & Register** yang terhubung ke MySQL
- ✅ **Library game** dengan grid card + cover opsional
- ✅ **Filter** berdasarkan genre & platform (chip + dropdown)
- ✅ **Search** judul game secara realtime
- ✅ **Tambah, Edit, Hapus** game
- ✅ **Upload cover** game (JPG/PNG/WEBP, maks 2MB)
- ✅ **Platform combobox** lengkap (PC, PS1-5, Xbox, Nintendo, iOS, Android + manual)
- ✅ **Genre** dengan datalist suggestions (bisa ketik manual)
- ✅ **Halaman akun**: profil, edit nickname/email, ganti password, hapus akun
- ✅ **Dark theme** dengan color palette teal accent
- ✅ **Responsive** untuk mobile
