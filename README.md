# Pinspire API

Backend REST API untuk aplikasi **Pinspire** (clone Pinterest) berbasis **Laravel 13** + **Laravel Sanctum**.

Dokumentasi lengkap setiap endpoint (beserta contoh JSON request/response) ada di
[`API_DOCUMENTATION.md`](API_DOCUMENTATION.md).

---

## Fitur

- Autentikasi: register, login, logout, profil user saat ini (token Sanctum)
- Feed gambar (pins) dari database, dengan pencarian judul/deskripsi
- Upload pin beserta gambar (dimensi terdeteksi otomatis)
- Board: buat, lihat, ubah, hapus (mendukung board privat)
- Simpan / hapus pin ke board (save / unsave), daftar pin tersimpan
- Profil publik & profil pribadi, upload avatar
- Pengaturan akun: email, preferensi notifikasi, ganti password

Semua respons JSON memakai format konsisten:

```json
{ "success": true, "message": "...", "data": { }, "meta": { } }
```

---

## Persyaratan

- PHP 8.3+
- Composer
- MySQL / MariaDB (XAMPP: MariaDB 10.4)
- Ekstensi PHP GD (untuk generate gambar demo saat seeding)

---

## Instalasi

```bash
# 1. Install dependency
composer install

# 2. Siapkan environment
copy .env.example .env      # Windows
cp .env.example .env        # macOS/Linux
php artisan key:generate

# 3. Buat database MySQL bernama "pinspire" (via phpMyAdmin/XAMPP),
#    lalu sesuaikan kredensial di .env bila perlu (default: root tanpa password).

# 4. Migrasi + data demo
php artisan migrate --seed

# 5. Buat symlink storage (agar gambar bisa diakses lewat URL)
php artisan storage:link

# 6. Jalankan server
php artisan serve
```

Base URL default: `http://127.0.0.1:8000/api/v1`

> Jika menjalankan server di port lain (mis. `--port=8123`), ubah `APP_URL` di `.env`
> agar `image_url` dan `avatar_url` menunjuk ke host/port yang benar.

---

## Akun Demo

```
email:    demo@pinspire.test
password: password
```

Seeder (`DemoContentSeeder`) membuat 5 user, beberapa board, 40 pin bergambar,
dan sejumlah save untuk mengisi feed.

---

## Testing

```bash
php artisan test
```

Nama Anggota:

(135) ADHIPRAMANA SAIFULLAH ARIF
(123) SYAHRINDRA ALWI ZAKARIA
(129) TEUKU RIFQI AR RAFI'
(110) RAFIKHAR LUCIANO
(138) BELVA DZAKWAN MAULA RAMADHAN
