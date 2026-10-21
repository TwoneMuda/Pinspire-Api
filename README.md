# Pinspire API

Backend REST API untuk aplikasi **Pinspire** 
Dibangun dengan **Laravel 13** dan **Laravel Sanctum**, menyediakan endpoint untuk
autentikasi, pin, board, interaksi sosial (like, komentar, follow), notifikasi, dan pesan.

> Dokumentasi lengkap setiap endpoint beserta contoh JSON request/response ada di
> [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md).


## Fitur

**Autentikasi**
- Register, login, logout, dan data user saat ini (token Sanctum)
- Rate limit 6 request/menit pada endpoint register dan login

**Pin (gambar)**
- Feed pin dengan pencarian judul/deskripsi
- Upload pin beserta gambar (dimensi terdeteksi otomatis)
- Ubah dan hapus pin (dibatasi lewat `PinPolicy`)

**Board**
- Buat, lihat, ubah, hapus board — mendukung board privat
- Daftar pin di dalam board

**Simpan pin**
- Simpan / hapus pin ke board (save / unsave)
- Daftar seluruh pin yang disimpan

**Interaksi sosial**
- Like / unlike pin, daftar pin yang disukai
- Komentar pada pin (tambah, daftar, hapus)
- Follow / unfollow user, daftar yang diikuti
- Notifikasi in-app: daftar, tandai dibaca, hapus
- Percakapan dan pesan antar user

**Profil & pengaturan**
- Profil publik dan profil pribadi, upload / hapus avatar
- Pengaturan akun: email, preferensi notifikasi, ganti password, hapus akun

Semua respons memakai format JSON:

```json
{ "success": true, "message": "...", "data": { }, "meta": { } }
```


## Skema Database

| Tabel | Isi |
| --- | --- |
| `users` | Akun + field profil (username, bio, website, avatar, preferensi notifikasi) |
| `pins` | Gambar: judul, deskripsi, path gambar, dimensi, kategori, `user_id` |
| `boards` | Koleksi pin milik user, mendukung flag privat + cover image |
| `saves` | Relasi pin → board (pin disimpan ke board tertentu) |
| `pin_likes` | Like user terhadap pin (unik per user+pin) |
| `comments` | Komentar user pada pin |
| `follows` | Relasi follow antar user (`follower_id`, `following_id`) |
| `app_notifications` | Notifikasi in-app beserta status dibaca |
| `messages` | Pesan antar user untuk fitur percakapan |
| `personal_access_tokens` | Token Sanctum |

---

## Persyaratan

- PHP 8.3+ 
- Composer
- MySQL / MariaDB

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

# 5. Jalankan server
php artisan serve
```

Base URL default: `http://127.0.0.1:8000/api/v1`


---

## Akun Demo

```
email:    demo@pinspire.test
password: password
```

`DemoContentSeeder` mengisi database dengan 5 user, 10–15 board, 40 pin bergambar,
30 like, 18 komentar, relasi follow, percakapan, dan notifikasi.

---

## Mencoba API

Tersedia dua cara siap pakai di folder `docs/`:

- **Postman** — import `docs/Pinspire.postman_collection.json`
- **REST Client / VS Code** — buka `docs/api.http` lalu jalankan request per blok

---

## Testing

```bash
php artisan test
```




---

Nama Anggota:

(135) ADHIPRAMANA SAIFULLAH ARIF
(123) SYAHRINDRA ALWI ZAKARIA
(129) TEUKU RIFQI AR RAFI'
(110) RAFIKHAR LUCIANO
(138) BELVA DZAKWAN MAULA RAMADHAN
