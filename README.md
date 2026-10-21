# Pinspire API

Backend REST API untuk aplikasi **Pinspire** — platform berbagi gambar bergaya Pinterest.

Dibangun dengan **Laravel 13** dan **Laravel Sanctum**, menyediakan 48 endpoint untuk
autentikasi, pin, board, interaksi sosial (like, komentar, follow), notifikasi, dan pesan.

> Dokumentasi lengkap setiap endpoint beserta contoh JSON request/response ada di
> [`API_DOCUMENTATION.md`](API_DOCUMENTATION.md).

---

## Teknologi

| Komponen | Versi / Keterangan |
| --- | --- |
| PHP | 8.3+ |
| Framework | Laravel 13.17 |
| Autentikasi | Laravel Sanctum 4 (Bearer token) |
| Database | MySQL / MariaDB |
| Storage | Disk `public` — disajikan lewat route `GET /media/{path}` |
| Testing | PHPUnit + Laravel HTTP Test |

---

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

Semua respons JSON memakai format yang konsisten:

```json
{ "success": true, "message": "...", "data": { }, "meta": { } }
```

---

## Anatomi Kode

```
app/
├── Http/
│   ├── Controllers/Api/V1/   # 12 controller, satu per domain fitur
│   ├── Requests/Api/V1/      # Form request, dipisah per domain
│   ├── Resources/            # API resource (bentuk JSON respons)
│   └── Middleware/
├── Models/                   # User, Pin, Board, Save, PinLike, Comment, Follow, Message, AppNotification
├── Policies/                 # PinPolicy, BoardPolicy
├── Services/                 # NotificationService
└── Support/                  # Helper Media (pembangun URL gambar)

database/
├── migrations/               # 14 migration
├── factories/
└── seeders/                  # DemoContentSeeder (data demo siap pakai)

docs/
├── Pinspire.postman_collection.json
└── api.http                  # Contoh request siap kirim

routes/
├── api.php                   # Seluruh endpoint API (prefix /api/v1)
└── web.php                   # Route media publik

tests/Feature/Api/V1/         # Auth, Board, Pin, Profile, Save, Search, Settings
```

Alur satu request: `routes/api.php` → `FormRequest` (validasi) → `Controller` →
`Policy` (otorisasi) → `Model` → `Resource` (serialisasi JSON).

---

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

- PHP 8.3+ (tanpa ekstensi tambahan — seeder menulis PNG langsung, tidak butuh GD)
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

> Gambar dan avatar disajikan lewat route `GET /media/{path}`, sehingga
> `php artisan storage:link` **tidak wajib** dijalankan.
>
> Jika server dijalankan di port lain (mis. `--port=8123`), ubah `APP_URL` di `.env`
> agar `image_url` dan `avatar_url` menunjuk ke host/port yang benar.

---

## Akun Demo

```
email:    demo@pinspire.test
password: password
```

`DemoContentSeeder` mengisi database dengan 5 user, 10–15 board, 40 pin bergambar,
30 like, 18 komentar, relasi follow, percakapan, dan notifikasi — cukup untuk
mencoba seluruh endpoint tanpa input manual.

> Seeder mencari foto asli di folder `../pinspire/assets/images` (aset milik klien Flutter).
> Bila folder tersebut tidak ada, seeder membuat gambar gradien sebagai pengganti,
> sehingga seeding tetap berhasil di mesin mana pun tanpa file pendamping.

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

Test berjalan di SQLite in-memory (lihat `phpunit.xml`), jadi tidak menyentuh
database development. Cakupan saat ini mencakup autentikasi, pin, board, save,
pencarian, profil, dan pengaturan.

---

## Catatan Desain

Beberapa keputusan teknis yang diambil beserta alasannya:

- **Bearer token, bukan cookie SPA.** Sanctum dipakai dalam mode token API agar klien
  mobile (Flutter) dan web memakai mekanisme autentikasi yang sama, tanpa perlu CSRF.
- **Autentikasi opsional pada endpoint publik.** `/pins`, `/search`, `/boards/{id}`, dan
  `/users/...` tetap bisa diakses tanpa token; bila token dikirim, server menambahkan
  data khusus viewer (`is_saved`, `is_liked`, `is_following`) dan membuka board privat
  milik sendiri.
- **Route media sendiri, bukan symlink `/storage`.** `MediaController` menyajikan file
  disk `public` dengan header CORS yang sama seperti API, sehingga gambar tetap tampil
  di Flutter Web dari origin berbeda.
- **Otorisasi lewat Policy.** Hak ubah/hapus pin dan board dipusatkan di `PinPolicy`
  dan `BoardPolicy`, tidak tersebar sebagai pengecekan `if` di controller.
- **Form request per domain.** Validasi dipisah dari controller agar aturan tiap
  endpoint mudah dibaca dan diuji ulang.

---

## Anggota Kelompok

| NIM | Nama |
| --- | --- |
| 135 | Adhipramana Saifullah Arif |
| 123 | Syahrindra Alwi Zakaria |
| 129 | Teuku Rifqi Ar Rafi' |
| 110 | Rafikhar Luciano |
| 138 | Belva Dzakwan Maula Ramadhan |
