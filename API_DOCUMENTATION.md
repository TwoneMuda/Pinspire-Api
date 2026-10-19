# Pinspire API Documentation (v1)

Dokumentasi REST API untuk aplikasi **Pinspire** (clone Pinterest).
Semua respons berformat **JSON** dengan struktur yang konsisten.

---

## 1. Informasi Umum

| Item | Nilai |
| --- | --- |
| Base URL (lokal) | `http://127.0.0.1:8000/api/v1` |
| Format data | JSON |
| Autentikasi | Bearer Token (Laravel Sanctum) |
| Header wajib | `Accept: application/json` |
| Header auth | `Authorization: Bearer <access_token>` |
| Upload file | `Content-Type: multipart/form-data` |

> **Penting (Flutter):** URL gambar dibangun dari `APP_URL` pada file `.env`.
> Saat testing dengan `php artisan serve --port=8123`, set `APP_URL=http://127.0.0.1:8123`
> agar `image_url` / `avatar_url` dapat diakses.

---

## 2. Format Respons Standar

### 2.1 Sukses

```json
{
  "success": true,
  "message": "Pins retrieved successfully.",
  "data": {}
}
```

### 2.2 Sukses dengan pagination

```json
{
  "success": true,
  "message": "Pins retrieved successfully.",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "last_page": 3,
    "total": 40,
    "from": 1,
    "to": 15,
    "has_more_pages": true
  }
}
```

### 2.3 Gagal

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

> Blok `errors` hanya muncul pada error validasi (422).

---

## 3. Kode Status HTTP

| Status | Arti |
| --- | --- |
| `200 OK` | Permintaan berhasil |
| `201 Created` | Resource baru berhasil dibuat |
| `401 Unauthorized` | Token tidak ada / tidak valid, atau kredensial salah |
| `403 Forbidden` | Tidak berhak mengakses resource milik user lain |
| `404 Not Found` | Resource tidak ditemukan |
| `422 Unprocessable Entity` | Validasi gagal |
| `429 Too Many Requests` | Rate limit (login/register: maks. 6x per menit) |

### Contoh error umum

**401 – belum login**

```json
{
  "success": false,
  "message": "Unauthenticated. Please log in to continue."
}
```

**401 – kredensial salah**

```json
{
  "success": false,
  "message": "The provided credentials are incorrect."
}
```

**403 – bukan pemilik**

```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

**404**

```json
{
  "success": false,
  "message": "The requested resource was not found."
}
```

**429**

```json
{
  "success": false,
  "message": "Too many requests. Please try again later."
}
```

---

## 4. Objek Data (Resource)

### User

```json
{
  "id": 1,
  "name": "Demo User",
  "username": "demo",
  "email": "demo@pinspire.test",
  "avatar_url": "http://127.0.0.1:8000/storage/avatars/abc.jpg",
  "bio": "Suka mengoleksi ide desain.",
  "website": "https://example.com",
  "pins_count": 8,
  "boards_count": 3,
  "created_at": "2026-10-18T10:00:00.000000Z"
}
```

> Field `email` hanya muncul saat: (a) respons register/login, (b) `GET /auth/me`,
> (c) `GET /profile`, (d) profil milik user yang sedang login.

### Pin

```json
{
  "id": 26,
  "title": "Ide Dekorasi Ruang Tamu",
  "description": "Inspirasi warna netral.",
  "image_url": "http://127.0.0.1:8000/storage/pins/demo-abc.jpg",
  "image_width": 800,
  "image_height": 1200,
  "source_url": "https://example.com/sumber",
  "saves_count": 12,
  "is_saved": false,
  "user": { "...User..." },
  "created_at": "2026-10-18T10:05:00.000000Z",
  "updated_at": "2026-10-18T10:05:00.000000Z"
}
```

> `is_saved` bernilai `true` jika user yang sedang login sudah menyimpan pin ini.

### Board

```json
{
  "id": 13,
  "name": "Dekorasi Rumah",
  "slug": "dekorasi-rumah",
  "description": "Koleksi ide interior.",
  "is_private": false,
  "cover_image_url": "http://127.0.0.1:8000/storage/pins/demo-abc.jpg",
  "pins_count": 5,
  "user": { "...User..." },
  "created_at": "2026-10-18T10:10:00.000000Z",
  "updated_at": "2026-10-18T10:10:00.000000Z"
}
```

### Save

```json
{
  "id": 7,
  "board": { "...Board..." },
  "pin": { "...Pin..." },
  "created_at": "2026-10-18T10:15:00.000000Z"
}
```

---

## 5. Autentikasi

### 5.1 Register

`POST /api/v1/auth/register` — publik

**Request**

```json
{
  "name": "Budi Santoso",
  "username": "budi",
  "email": "budi@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "device_name": "flutter-app"
}
```

| Field | Tipe | Wajib | Aturan |
| --- | --- | --- | --- |
| `name` | string | ya | maks. 255 |
| `username` | string | ya | 3–30, huruf/angka/`-`/`_`, unik |
| `email` | string | ya | format email, unik, maks. 255 |
| `password` | string | ya | min. 8, harus sama dengan `password_confirmation` |
| `device_name` | string | tidak | maks. 255 (label token, mis. `flutter-app`) |

**Response `201`**

```json
{
  "success": true,
  "message": "Registration successful.",
  "data": {
    "user": {
      "id": 6,
      "name": "Budi Santoso",
      "username": "budi",
      "email": "budi@example.com",
      "avatar_url": null,
      "bio": null,
      "website": null,
      "pins_count": 0,
      "boards_count": 0,
      "created_at": "2026-10-19T11:30:00.000000Z"
    },
    "access_token": "6|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**Response `422`**

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password field confirmation does not match."]
  }
}
```

---

### 5.2 Login

`POST /api/v1/auth/login` — publik

**Request**

```json
{
  "email": "demo@pinspire.test",
  "password": "password",
  "device_name": "flutter-app"
}
```

**Response `200`**

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": 1,
      "name": "Demo User",
      "username": "demo",
      "email": "demo@pinspire.test",
      "avatar_url": "http://127.0.0.1:8000/storage/avatars/demo.jpg",
      "bio": "Akun demo Pinspire.",
      "website": null,
      "pins_count": 8,
      "boards_count": 3,
      "created_at": "2026-10-18T10:00:00.000000Z"
    },
    "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**Response `401`**

```json
{
  "success": false,
  "message": "The provided credentials are incorrect."
}
```

---

### 5.3 Logout

`POST /api/v1/auth/logout` — **auth**

Menghapus token yang sedang dipakai.

**Response `200`**

```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

### 5.4 Current User

`GET /api/v1/auth/me` — **auth**

**Response `200`**

```json
{
  "success": true,
  "message": "Authenticated user retrieved successfully.",
  "data": {
    "id": 1,
    "name": "Demo User",
    "username": "demo",
    "email": "demo@pinspire.test",
    "avatar_url": null,
    "bio": "Akun demo Pinspire.",
    "website": null,
    "pins_count": 8,
    "boards_count": 3,
    "created_at": "2026-10-18T10:00:00.000000Z"
  }
}
```

---

## 6. Pins (Gambar)

### 6.1 Feed Pins

`GET /api/v1/pins` — publik (opsional login untuk `is_saved`)

**Query params**

| Param | Default | Keterangan |
| --- | --- | --- |
| `search` | – | filter judul/deskripsi |
| `per_page` | 15 | 1–50 |
| `page` | 1 | halaman |

**Response `200`**

```json
{
  "success": true,
  "message": "Pins retrieved successfully.",
  "data": [
    {
      "id": 26,
      "title": "Ide Dekorasi Ruang Tamu",
      "description": "Inspirasi warna netral.",
      "image_url": "http://127.0.0.1:8000/storage/pins/demo-abc.jpg",
      "image_width": 800,
      "image_height": 1200,
      "source_url": null,
      "saves_count": 12,
      "is_saved": false,
      "user": {
        "id": 1,
        "name": "Demo User",
        "username": "demo",
        "avatar_url": null,
        "bio": "Akun demo Pinspire.",
        "website": null,
        "pins_count": 8,
        "boards_count": 3,
        "created_at": "2026-10-18T10:00:00.000000Z"
      },
      "created_at": "2026-10-18T10:05:00.000000Z",
      "updated_at": "2026-10-18T10:05:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "last_page": 3,
    "total": 40,
    "from": 1,
    "to": 15,
    "has_more_pages": true
  }
}
```

---

### 6.2 Detail Pin

`GET /api/v1/pins/{pin}` — publik (opsional login)

**Response `200`**

```json
{
  "success": true,
  "message": "Pin retrieved successfully.",
  "data": {
    "id": 26,
    "title": "Ide Dekorasi Ruang Tamu",
    "description": "Inspirasi warna netral.",
    "image_url": "http://127.0.0.1:8000/storage/pins/demo-abc.jpg",
    "image_width": 800,
    "image_height": 1200,
    "source_url": null,
    "saves_count": 12,
    "is_saved": true,
    "user": { "...User..." },
    "created_at": "2026-10-18T10:05:00.000000Z",
    "updated_at": "2026-10-18T10:05:00.000000Z"
  }
}
```

**Response `404`**

```json
{
  "success": false,
  "message": "The requested resource was not found."
}
```

---

### 6.3 Upload Pin

`POST /api/v1/pins` — **auth** — `multipart/form-data`

| Field | Tipe | Wajib | Aturan |
| --- | --- | --- | --- |
| `image` | file | ya | jpg/jpeg/png/webp/gif, maks. 5 MB, harus gambar valid |
| `title` | string | tidak | maks. 255 |
| `description` | string | tidak | maks. 1000 |
| `source_url` | string | tidak | URL valid, maks. 2048 |

**Response `201`**

```json
{
  "success": true,
  "message": "Pin uploaded successfully.",
  "data": {
    "id": 41,
    "title": "Sudut Baca Minimalis",
    "description": "Kursi rotan + lampu hangat.",
    "image_url": "http://127.0.0.1:8000/storage/pins/AbC123.jpg",
    "image_width": 1080,
    "image_height": 1350,
    "source_url": null,
    "saves_count": 0,
    "is_saved": false,
    "user": { "...User..." },
    "created_at": "2026-10-19T11:40:00.000000Z",
    "updated_at": "2026-10-19T11:40:00.000000Z"
  }
}
```

**Response `422`**

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "image": ["The image field is required."]
  }
}
```

---

### 6.4 Update Pin

`PUT /api/v1/pins/{pin}` atau `PATCH /api/v1/pins/{pin}` — **auth** (hanya pemilik)

Semua field opsional. Untuk mengganti gambar via `multipart/form-data`,
kirim `_method=PUT` (method spoofing) karena multipart tidak mendukung PUT langsung.

**Request (`multipart/form-data`)**

```
_method=PUT
title=Judul Baru
description=Deskripsi baru
image=<file>
```

**Response `200`**

```json
{
  "success": true,
  "message": "Pin updated successfully.",
  "data": { "...Pin..." }
}
```

**Response `403`** (bukan pemilik)

```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

---

### 6.5 Hapus Pin

`DELETE /api/v1/pins/{pin}` — **auth** (hanya pemilik)

**Response `200`**

```json
{
  "success": true,
  "message": "Pin deleted successfully."
}
```

---

## 7. Pencarian

### 7.1 Cari Pin

`GET /api/v1/search` — publik

**Query params**

| Param | Wajib | Keterangan |
| --- | --- | --- |
| `q` | ya | 1–100 karakter |

**Request contoh**

```
GET /api/v1/search?q=dekorasi&per_page=15&page=1
```

**Response `200`**

```json
{
  "success": true,
  "message": "Search results retrieved successfully.",
  "data": [ { "...Pin..." } ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "last_page": 1,
    "total": 4,
    "from": 1,
    "to": 4,
    "has_more_pages": false,
    "query": "dekorasi"
  }
}
```

**Response `422`**

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "q": ["The q field is required."]
  }
}
```

---

## 8. Boards

### 8.1 Daftar Board Milik Saya

`GET /api/v1/boards` — **auth**

**Query params:** `per_page` (default 15, maks. 50), `page`

**Response `200`**

```json
{
  "success": true,
  "message": "Boards retrieved successfully.",
  "data": [
    {
      "id": 13,
      "name": "Dekorasi Rumah",
      "slug": "dekorasi-rumah",
      "description": "Koleksi ide interior.",
      "is_private": false,
      "cover_image_url": "http://127.0.0.1:8000/storage/pins/demo-abc.jpg",
      "pins_count": 5,
      "user": { "...User..." },
      "created_at": "2026-10-18T10:10:00.000000Z",
      "updated_at": "2026-10-18T10:10:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "last_page": 1,
    "total": 3,
    "from": 1,
    "to": 3,
    "has_more_pages": false
  }
}
```

---

### 8.2 Buat Board

`POST /api/v1/boards` — **auth**

**Request**

```json
{
  "name": "Dekorasi Rumah",
  "description": "Koleksi ide interior.",
  "is_private": false
}
```

| Field | Tipe | Wajib | Aturan |
| --- | --- | --- | --- |
| `name` | string | ya | maks. 100 |
| `description` | string | tidak | maks. 500 |
| `is_private` | boolean | tidak | default `false` |

> `slug` dibuat otomatis dari `name` dan dijamin unik per user.

**Response `201`**

```json
{
  "success": true,
  "message": "Board created successfully.",
  "data": {
    "id": 21,
    "name": "Dekorasi Rumah",
    "slug": "dekorasi-rumah",
    "description": "Koleksi ide interior.",
    "is_private": false,
    "cover_image_url": null,
    "pins_count": 0,
    "user": { "...User..." },
    "created_at": "2026-10-19T11:45:00.000000Z",
    "updated_at": "2026-10-19T11:45:00.000000Z"
  }
}
```

---

### 8.3 Detail Board

`GET /api/v1/boards/{board}` — publik

Board privat hanya dapat dilihat oleh pemiliknya.

**Response `200`**

```json
{
  "success": true,
  "message": "Board retrieved successfully.",
  "data": { "...Board..." }
}
```

**Response `403`** (board privat milik orang lain)

```json
{
  "success": false,
  "message": "This action is unauthorized."
}
```

---

### 8.4 Update Board

`PUT /api/v1/boards/{board}` atau `PATCH /api/v1/boards/{board}` — **auth** (hanya pemilik)

**Request** (semua opsional)

```json
{
  "name": "Rumah & Interior",
  "description": "Update deskripsi.",
  "is_private": true
}
```

**Response `200`**

```json
{
  "success": true,
  "message": "Board updated successfully.",
  "data": { "...Board..." }
}
```

---

### 8.5 Hapus Board

`DELETE /api/v1/boards/{board}` — **auth** (hanya pemilik)

**Response `200`**

```json
{
  "success": true,
  "message": "Board deleted successfully."
}
```

---

### 8.6 Pins di Dalam Board

`GET /api/v1/boards/{board}/pins` — publik

Board privat hanya untuk pemilik. Pin diurutkan dari yang terbaru disimpan.

**Query params:** `per_page`, `page`

**Response `200`**

```json
{
  "success": true,
  "message": "Board pins retrieved successfully.",
  "data": [ { "...Pin..." } ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "last_page": 1,
    "total": 5,
    "from": 1,
    "to": 5,
    "has_more_pages": false
  }
}
```

---

## 9. Save / Unsave Pin

### 9.1 Simpan Pin ke Board

`POST /api/v1/pins/{pin}/save` — **auth**

**Request**

```json
{
  "board_id": 13
}
```

| Field | Tipe | Wajib | Keterangan |
| --- | --- | --- | --- |
| `board_id` | integer | ya | harus milik user yang login |

**Response `201`** (berhasil disimpan)

```json
{
  "success": true,
  "message": "Pin saved to board successfully.",
  "data": {
    "id": 31,
    "board": { "...Board..." },
    "pin": { "...Pin..." },
    "created_at": "2026-10-19T11:50:00.000000Z"
  }
}
```

**Response `200`** (sudah pernah disimpan ke board yang sama — idempoten)

```json
{
  "success": true,
  "message": "Pin is already saved to this board.",
  "data": { "...Save..." }
}
```

**Response `422`**

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "board_id": ["The selected board does not exist or does not belong to you."]
  }
}
```

---

### 9.2 Hapus Pin dari Board

`DELETE /api/v1/pins/{pin}/save` — **auth**

**Request**

```json
{
  "board_id": 13
}
```

**Response `200`**

```json
{
  "success": true,
  "message": "Pin removed from board successfully."
}
```

**Response `404`**

```json
{
  "success": false,
  "message": "This pin is not saved to the selected board."
}
```

---

### 9.3 Daftar Pin yang Saya Simpan

`GET /api/v1/saves` — **auth**

**Query params:** `per_page`, `page`

**Response `200`**

```json
{
  "success": true,
  "message": "Saved pins retrieved successfully.",
  "data": [
    {
      "id": 31,
      "board": {
        "id": 13,
        "name": "Dekorasi Rumah",
        "slug": "dekorasi-rumah",
        "description": "Koleksi ide interior.",
        "is_private": false,
        "cover_image_url": "http://127.0.0.1:8000/storage/pins/demo-abc.jpg",
        "pins_count": 5,
        "user": { "...User..." },
        "created_at": "2026-10-18T10:10:00.000000Z",
        "updated_at": "2026-10-18T10:10:00.000000Z"
      },
      "pin": { "...Pin..." },
      "created_at": "2026-10-19T11:50:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "last_page": 1,
    "total": 12,
    "from": 1,
    "to": 12,
    "has_more_pages": false
  }
}
```

---

## 10. Profil

### 10.1 Profil Publik

`GET /api/v1/users/{username}` — publik

Diparameter dengan **username** (bukan id).

**Response `200`**

```json
{
  "success": true,
  "message": "Profile retrieved successfully.",
  "data": {
    "id": 1,
    "name": "Demo User",
    "username": "demo",
    "avatar_url": null,
    "bio": "Akun demo Pinspire.",
    "website": null,
    "pins_count": 8,
    "boards_count": 3,
    "created_at": "2026-10-18T10:00:00.000000Z"
  }
}
```

> `email` tidak muncul kecuali ini profil user yang sedang login.

---

### 10.2 Pin Milik User

`GET /api/v1/users/{username}/pins` — publik

**Query params:** `per_page`, `page`

**Response `200`**

```json
{
  "success": true,
  "message": "User pins retrieved successfully.",
  "data": [ { "...Pin..." } ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "last_page": 1,
    "total": 8,
    "from": 1,
    "to": 8,
    "has_more_pages": false
  }
}
```

---

### 10.3 Board Milik User

`GET /api/v1/users/{username}/boards` — publik

Board privat disembunyikan kecuali milik user yang login.

**Query params:** `per_page`, `page`

**Response `200`**

```json
{
  "success": true,
  "message": "User boards retrieved successfully.",
  "data": [ { "...Board..." } ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "last_page": 1,
    "total": 3,
    "from": 1,
    "to": 3,
    "has_more_pages": false
  }
}
```

---

### 10.4 Profil Saya

`GET /api/v1/profile` — **auth**

**Response `200`**

```json
{
  "success": true,
  "message": "Profile retrieved successfully.",
  "data": {
    "id": 1,
    "name": "Demo User",
    "username": "demo",
    "email": "demo@pinspire.test",
    "avatar_url": null,
    "bio": "Akun demo Pinspire.",
    "website": "https://example.com",
    "pins_count": 8,
    "boards_count": 3,
    "created_at": "2026-10-18T10:00:00.000000Z"
  }
}
```

---

### 10.5 Update Profil

`PUT /api/v1/profile` atau `PATCH /api/v1/profile` — **auth**

**Request** (semua opsional)

```json
{
  "name": "Demo User Baru",
  "username": "demo_baru",
  "bio": "Bio baru.",
  "website": "https://example.com"
}
```

| Field | Tipe | Aturan |
| --- | --- | --- |
| `name` | string | maks. 255 |
| `username` | string | 3–30, `alpha_dash`, unik (kecuali milik sendiri) |
| `bio` | string | maks. 500 |
| `website` | string | URL valid, maks. 255 |

**Response `200`**

```json
{
  "success": true,
  "message": "Profile updated successfully.",
  "data": { "...User..." }
}
```

---

### 10.6 Upload Avatar

`POST /api/v1/profile/avatar` — **auth** — `multipart/form-data`

| Field | Tipe | Wajib | Aturan |
| --- | --- | --- | --- |
| `avatar` | file | ya | jpg/jpeg/png/webp, maks. 2 MB |

**Response `200`**

```json
{
  "success": true,
  "message": "Avatar updated successfully.",
  "data": {
    "id": 1,
    "name": "Demo User",
    "username": "demo",
    "email": "demo@pinspire.test",
    "avatar_url": "http://127.0.0.1:8000/storage/avatars/xyz.jpg",
    "bio": "Akun demo Pinspire.",
    "website": null,
    "pins_count": 8,
    "boards_count": 3,
    "created_at": "2026-10-18T10:00:00.000000Z"
  }
}
```

---

## 11. Pengaturan (Settings)

### 11.1 Lihat Settings

`GET /api/v1/settings` — **auth**

**Response `200`**

```json
{
  "success": true,
  "message": "Settings retrieved successfully.",
  "data": {
    "user": { "...User..." },
    "email": "demo@pinspire.test",
    "preferences": {
      "email_notifications": true,
      "push_notifications": true
    }
  }
}
```

---

### 11.2 Update Settings

`PUT /api/v1/settings` atau `PATCH /api/v1/settings` — **auth**

**Request** (semua opsional)

```json
{
  "email": "demo.baru@pinspire.test",
  "preferences": {
    "email_notifications": false,
    "push_notifications": true
  }
}
```

| Field | Tipe | Aturan |
| --- | --- | --- |
| `email` | string | format email, unik, maks. 255 |
| `preferences.email_notifications` | boolean | – |
| `preferences.push_notifications` | boolean | – |

> `preferences` digabung (merge) dengan nilai lama; field yang tidak dikirim tidak berubah.

**Response `200`**

```json
{
  "success": true,
  "message": "Settings updated successfully.",
  "data": {
    "user": { "...User..." },
    "email": "demo.baru@pinspire.test",
    "preferences": {
      "email_notifications": false,
      "push_notifications": true
    }
  }
}
```

---

### 11.3 Ganti Password

`PUT /api/v1/settings/password` atau `PATCH /api/v1/settings/password` — **auth**

**Request**

```json
{
  "current_password": "password",
  "password": "passwordBaru123",
  "password_confirmation": "passwordBaru123"
}
```

| Field | Tipe | Wajib | Aturan |
| --- | --- | --- | --- |
| `current_password` | string | ya | harus sesuai password saat ini |
| `password` | string | ya | min. 8, sama dengan konfirmasi, beda dari password lama |

> Setelah berhasil, **semua sesi/token lain dicabut** (kecuali token yang dipakai request ini).
> Token saat ini tetap valid.

**Response `200`**

```json
{
  "success": true,
  "message": "Password updated successfully. Other sessions have been signed out."
}
```

**Response `422`**

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "current_password": ["The provided password does not match your current password."]
  }
}
```

---

## 12. Ringkasan Endpoint

| # | Method | Endpoint | Auth | Keterangan |
| --- | --- | --- | --- | --- |
| 1 | POST | `/auth/register` | – | Registrasi + token |
| 2 | POST | `/auth/login` | – | Login + token |
| 3 | POST | `/auth/logout` | ✔ | Cabut token aktif |
| 4 | GET | `/auth/me` | ✔ | User saat ini |
| 5 | GET | `/pins` | – | Feed pins |
| 6 | GET | `/pins/{pin}` | – | Detail pin |
| 7 | POST | `/pins` | ✔ | Upload pin |
| 8 | PUT/PATCH | `/pins/{pin}` | ✔ | Update pin |
| 9 | DELETE | `/pins/{pin}` | ✔ | Hapus pin |
| 10 | GET | `/search` | – | Cari pin (`?q=`) |
| 11 | GET | `/boards` | ✔ | Board milik saya |
| 12 | POST | `/boards` | ✔ | Buat board |
| 13 | GET | `/boards/{board}` | – | Detail board |
| 14 | PUT/PATCH | `/boards/{board}` | ✔ | Update board |
| 15 | DELETE | `/boards/{board}` | ✔ | Hapus board |
| 16 | GET | `/boards/{board}/pins` | – | Pin di board |
| 17 | POST | `/pins/{pin}/save` | ✔ | Simpan pin ke board |
| 18 | DELETE | `/pins/{pin}/save` | ✔ | Hapus pin dari board |
| 19 | GET | `/saves` | ✔ | Pin yang saya simpan |
| 20 | GET | `/users/{username}` | – | Profil publik |
| 21 | GET | `/users/{username}/pins` | – | Pin milik user |
| 22 | GET | `/users/{username}/boards` | – | Board milik user |
| 23 | GET | `/profile` | ✔ | Profil saya |
| 24 | PUT/PATCH | `/profile` | ✔ | Update profil |
| 25 | POST | `/profile/avatar` | ✔ | Upload avatar |
| 26 | GET | `/settings` | ✔ | Lihat settings |
| 27 | PUT/PATCH | `/settings` | ✔ | Update settings |
| 28 | PUT/PATCH | `/settings/password` | ✔ | Ganti password |

---

## 13. Akun Demo

```
email:    demo@pinspire.test
password: password
```

Berisi 5 user, beberapa board, 40 pin, dan sejumlah save untuk pengujian feed.
