# Dokumentasi API Mobile — Digital Breakfast Planner

Dokumen ini menjadi acuan integrasi aplikasi Flutter siswa dengan backend Laravel (`/api`).

## Konvensi Umum

- **Base URL**: `/api`
- **Format Respons**: Seluruh endpoint `/api/*` selalu merespons dalam format `application/json` (tidak pernah redirect ke HTML), dengan amplop standar:
  ```json
  {
    "data": ...,
    "message": "Pesan penjelasan dalam Bahasa Indonesia."
  }
  ```
- **Autentikasi**: Menggunakan **Laravel Sanctum Personal Access Token**. Kirimkan header:
  ```http
  Authorization: Bearer <token>
  Accept: application/json
  ```
- **Kode Status HTTP Standar**:
  - `200 OK` / `201 Created` — Permintaan berhasil.
  - `401 Unauthorized` — Token tidak dikirim, kedaluwarsa, dicabut, atau kredensial login salah.
  - `403 Forbidden` — Akun tidak aktif, peran bukan `SISWA`, atau `status_akun` belum memenuhi syarat middleware.
  - `404 Not Found` — Endpoint atau data tidak ditemukan (termasuk materi tidak aktif).
  - `409 Conflict` — Operasi berulang yang tidak diizinkan (mis. mengirim pretest lebih dari sekali).
  - `422 Unprocessable Entity` — Validasi input gagal atau prasyarat data belum tersedia.
  - `429 Too Many Requests` — Melewati batas *rate limit*.

---

## Alur Status Akun (`status_akun.kode`)

Aplikasi Flutter tidak boleh menyimpan "langkah terakhir" secara lokal. Gunakan satu layar pengarah yang membaca `GET /api/me` lalu mengarahkan berdasarkan `data.status_akun.kode`:

1. `PRETEST` — Baru registrasi, arahkan ke halaman Pretest.
2. `EDUKASI` — Pretest selesai, arahkan ke daftar Materi Edukasi wajib.
3. `MENUNGGU_VERIFIKASI` — Semua materi wajib terbaca, arahkan ke layar tunggu wawancara SQ-FFQ petugas.
4. `TERVERIFIKASI` — SQ-FFQ tahap awal selesai diverifikasi petugas, arahkan ke Beranda utama.

> **Catatan**: Perpindahan `status_akun` dilakukan sepenuhnya oleh *trigger* PostgreSQL di sisi database, bukan diubah manual oleh klien maupun PHP.

---

## A. Autentikasi & Profil

### 1. Daftar Kelas Aktif (Publik)

- **Method & Path**: `GET /api/kelas`
- **Auth**: Tidak diperlukan (Publik)
- **Request Body**: Tidak ada

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": [
    {
      "kelas_id": 1,
      "nama_kelas": "8A",
      "tahun_ajaran": "2025/2026"
    },
    {
      "kelas_id": 2,
      "nama_kelas": "8B",
      "tahun_ajaran": "2025/2026"
    }
  ],
  "message": "Daftar kelas aktif."
}
```

---

### 2. Registrasi Siswa (Publik)

Membuat akun `pengguna_m` (peran `SISWA`, password di-*hash* bcrypt) dan profil `siswa_m` (status awal `PRETEST`) dalam satu transaksi database, lalu mengembalikan token Sanctum beserta data profil `/me`.

- **Method & Path**: `POST /api/auth/register`
- **Auth**: Tidak diperlukan (Publik)

#### Request Body (`application/json`)
| Field | Tipe | Wajib | Aturan Validasi |
|---|---|---|---|
| `nama_lengkap` | `string` | Ya | Maksimal 120 karakter |
| `email` | `string` | Ya | Format email valid, maks. 150 karakter, unik *case-insensitive* (`lower(email)`) |
| `password` | `string` | Ya | Minimal 8 karakter, wajib cocok dengan `password_confirmation` |
| `password_confirmation` | `string` | Ya | Harus sama persis dengan `password` |
| `kelas_id` | `integer` | Ya | Harus terdaftar pada `kelas_m` yang `aktif = true` |
| `jenis_kelamin` | `string\|null` | Opsional | `"L"` atau `"P"` |
| `tanggal_lahir` | `string\|null` | Opsional | Format tanggal `YYYY-MM-DD` |

```json
{
  "nama_lengkap": "Siti Aisyah",
  "email": "siti.aisyah@siswa.id",
  "password": "password123",
  "password_confirmation": "password123",
  "kelas_id": 1,
  "jenis_kelamin": "P",
  "tanggal_lahir": "2012-04-15"
}
```

#### Contoh Respons Sukses (`201 Created`)
```json
{
  "data": {
    "token": "1|xYzAbCdEfGhIjKlMnOpQrStUvWxYz1234567890",
    "user": {
      "pengguna_id": 12,
      "nama_lengkap": "Siti Aisyah",
      "email": "siti.aisyah@siswa.id",
      "siswa_id": 5,
      "kelas": {
        "kelas_id": 1,
        "nama_kelas": "8A"
      },
      "status_akun": {
        "kode": "PRETEST",
        "nama": "Belum pretest"
      },
      "terverifikasi_pada": null
    }
  },
  "message": "Registrasi berhasil."
}
```

#### Contoh Respons Error Validasi (`422 Unprocessable Entity`)
```json
{
  "data": null,
  "message": "Email sudah terdaftar.",
  "errors": {
    "email": [
      "Email sudah terdaftar."
    ]
  }
}
```

---

### 3. Login Siswa (Publik, Rate Limited)

Autentikasi khusus akun berperan `SISWA` yang aktif. Akun `ADMIN` dan `PETUGAS` ditolak karena hanya login melalui `/admin`. Memiliki *rate limit* **5 percobaan per menit per kombinasi email + IP**.

- **Method & Path**: `POST /api/auth/login`
- **Auth**: Tidak diperlukan (Publik)

#### Request Body (`application/json`)
```json
{
  "email": "siti.aisyah@siswa.id",
  "password": "password123"
}
```

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "token": "2|pQrStUvWxYzAbCdEfGhIjKlMnOpQrStUvWxYz98",
    "user": {
      "pengguna_id": 12,
      "nama_lengkap": "Siti Aisyah",
      "email": "siti.aisyah@siswa.id",
      "siswa_id": 5,
      "kelas": {
        "kelas_id": 1,
        "nama_kelas": "8A"
      },
      "status_akun": {
        "kode": "PRETEST",
        "nama": "Belum pretest"
      },
      "terverifikasi_pada": null
    }
  },
  "message": "Login berhasil."
}
```

#### Contoh Respons Error
- **Email / Password Salah (`401 Unauthorized`)**:
  ```json
  {
    "data": null,
    "message": "Email atau kata sandi salah."
  }
  ```
- **Bukan Akun Siswa atau Akun Nonaktif (`403 Forbidden`)**:
  ```json
  {
    "data": null,
    "message": "Login API hanya untuk akun siswa."
  }
  ```
- **Melebihi 5 Percobaan per Menit (`429 Too Many Requests`)**:
  ```json
  {
    "data": null,
    "message": "Terlalu banyak percobaan. Silakan coba lagi setelah satu menit."
  }
  ```

---

### 4. Logout Siswa

- **Method & Path**: `POST /api/auth/logout`
- **Auth**: `Bearer <token>` (`auth:sanctum`)
- **Request Body**: Tidak ada

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": null,
  "message": "Logout berhasil."
}
```

---

### 5. Profil & Status Akun Siswa (`/api/me`)

- **Method & Path**: `GET /api/me`
- **Auth**: `Bearer <token>` (`auth:sanctum`)
- **Request Body**: Tidak ada

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "pengguna_id": 12,
    "nama_lengkap": "Siti Aisyah",
    "email": "siti.aisyah@siswa.id",
    "siswa_id": 5,
    "kelas": {
      "kelas_id": 1,
      "nama_kelas": "8A"
    },
    "status_akun": {
      "kode": "TERVERIFIKASI",
      "nama": "Terverifikasi"
    },
    "terverifikasi_pada": "2026-10-29T12:30:00.000000Z"
  },
  "message": "Data profil pengguna."
}
```

---

## B. Onboarding Siswa (Pretest & Materi Edukasi)

### 6. Ambil Soal Pretest

Mengambil soal dan pilihan jawaban dari `kuesioner_m` berjenis `PRETEST` yang aktif, urut berdasarkan `nomor`. Kolom kunci jawaban (`benar`) tidak pernah disertakan pada respons.

- **Method & Path**: `GET /api/pretest`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.status:PRETEST`
- **Request Body**: Tidak ada

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "kuesioner_id": 1,
    "judul": "Pretest pengetahuan gizi",
    "soal": [
      {
        "soal_id": 1,
        "nomor": 1,
        "teks_soal": "Mengapa sarapan pagi sebelum berangkat sekolah penting bagi remaja?",
        "opsi": [
          {
            "opsi_jawaban_id": 1,
            "kode_opsi": "A",
            "teks_opsi": "Membantu menjaga konsentrasi belajar dan pasokan energi pagi hari"
          },
          {
            "opsi_jawaban_id": 2,
            "kode_opsi": "B",
            "teks_opsi": "Menyebabkan rasa kantuk berlebih sepanjang pelajaran"
          }
        ]
      }
    ]
  },
  "message": "Daftar soal pretest."
}
```

#### Contoh Respons Error
- **Status Akun Bukan `PRETEST` (`403 Forbidden`)**:
  ```json
  {
    "code": "STATUS_TIDAK_SESUAI",
    "message": "Status akun siswa tidak sesuai untuk mengakses endpoint ini.",
    "status_akun": "EDUKASI"
  }
  ```
- **Belum Ada Soal Pretest Aktif (`422 Unprocessable Entity`)**:
  ```json
  {
    "data": null,
    "message": "Tidak ada soal pretest aktif yang tersedia."
  }
  ```

---

### 7. Kirim Jawaban Pretest

Menilai jawaban pretest di server, menyimpan ke `hasil_kuesioner_t` dan `jawaban_kuesioner_t`, lalu di akhir transaksi menjalankan `UPDATE hasil_kuesioner_t SET selesai_pada = now()` sehingga trigger PostgreSQL memindahkan `status_akun` siswa dari `PRETEST` ke `EDUKASI`.

- **Method & Path**: `POST /api/pretest/submit`
- **Auth & Middleware**: `auth:sanctum`, `siswa` (hanya untuk siswa berstatus `PRETEST` yang belum pernah mengirim pretest)

#### Request Body (`application/json`)
| Field | Tipe | Wajib | Aturan Validasi |
|---|---|---|---|
| `jawaban` | `array` | Ya | Seluruh soal pada kuesioner pretest aktif wajib dijawab |
| `jawaban.*.soal_id` | `integer` | Ya | Unik (`distinct`), harus milik kuesioner pretest aktif |
| `jawaban.*.opsi_jawaban_id` | `integer` | Ya | Harus merupakan opsi milik `soal_id` terkait |

```json
{
  "jawaban": [
    { "soal_id": 1, "opsi_jawaban_id": 1 },
    { "soal_id": 2, "opsi_jawaban_id": 6 },
    { "soal_id": 3, "opsi_jawaban_id": 10 },
    { "soal_id": 4, "opsi_jawaban_id": 15 },
    { "soal_id": 5, "opsi_jawaban_id": 17 }
  ]
}
```

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "skor": 80,
    "jumlah_benar": 4,
    "jumlah_soal": 5,
    "status_akun": {
      "kode": "EDUKASI",
      "nama": "Wajib membaca materi edukasi"
    }
  },
  "message": "Pretest berhasil diselesaikan."
}
```

#### Contoh Respons Error
- **Pretest Sudah Pernah Dikirim (`409 Conflict`)**:
  ```json
  {
    "code": "PRETEST_SUDAH_DIKIRIM",
    "data": null,
    "message": "Pretest sudah pernah dikirim dan tidak dapat diulang."
  }
  ```
- **Opsi Bukan Milik Soalnya / Jawaban Belum Lengkap (`422 Unprocessable Entity`)**:
  ```json
  {
    "data": null,
    "message": "Opsi jawaban bukan milik soal yang dipilih.",
    "errors": {
      "jawaban.0.opsi_jawaban_id": [
        "Opsi jawaban bukan milik soal yang dipilih."
      ]
    }
  }
  ```
- **Tidak Ada Soal Pretest Aktif (`422 Unprocessable Entity`)**:
  ```json
  {
    "data": null,
    "message": "Tidak ada soal pretest aktif yang tersedia."
  }
  ```

---

### 8. Daftar Materi Edukasi

Mengembalikan daftar materi edukasi yang aktif (`aktif = true`) diurutkan berdasarkan `urutan`, dilengkapi status keterbacaan milik siswa serta ringkasan progres pada `meta`. Dapat diakses pada saat onboarding (`EDUKASI`) maupun dibuka kembali dari Beranda setelah `TERVERIFIKASI`.

- **Method & Path**: `GET /api/materi`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.status:EDUKASI,TERVERIFIKASI`
- **Request Body**: Tidak ada

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": [
    {
      "materi_edukasi_id": 1,
      "judul": "[DUMMY] Mengapa Sarapan Tidak Boleh Dilewatkan?",
      "ringkasan": "[DUMMY] Materi wajib 1: manfaat sarapan terhadap kadar glukosa darah, fokus, dan prestasi belajar.",
      "urutan": 1,
      "wajib": true,
      "selesai": true
    },
    {
      "materi_edukasi_id": 2,
      "judul": "[DUMMY] Panduan Piring Sarapan Gizi Seimbang",
      "ringkasan": "[DUMMY] Materi wajib 2: cara mudah memadukan karbohidrat, protein, sayur/buah, dan air putih saat pagi hari.",
      "urutan": 2,
      "wajib": true,
      "selesai": false
    },
    {
      "materi_edukasi_id": 3,
      "judul": "[DUMMY] Tips Menyiapkan Sarapan Praktis dalam 10 Menit",
      "ringkasan": "[DUMMY] Materi opsional (tidak wajib): ide persiapan malam hari agar tidak terburu-buru di pagi hari.",
      "urutan": 3,
      "wajib": false,
      "selesai": false
    }
  ],
  "meta": {
    "total_wajib": 2,
    "dibaca_wajib": 1,
    "status_akun": {
      "kode": "EDUKASI",
      "nama": "Wajib membaca materi edukasi"
    }
  },
  "message": "Daftar materi edukasi."
}
```

#### Contoh Respons Error (`403 Forbidden`)
```json
{
  "code": "STATUS_TIDAK_SESUAI",
  "message": "Status akun siswa tidak sesuai untuk mengakses endpoint ini.",
  "status_akun": "PRETEST"
}
```

---

### 9. Detail Materi Edukasi

Mengambil isi lengkap (`konten`) dari satu materi edukasi yang aktif. Mengembalikan `404` bila ID tidak ditemukan atau `aktif = false`.

- **Method & Path**: `GET /api/materi/{materi_edukasi_id}`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.status:EDUKASI,TERVERIFIKASI`
- **Request Body**: Tidak ada

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "materi_edukasi_id": 1,
    "judul": "[DUMMY] Mengapa Sarapan Tidak Boleh Dilewatkan?",
    "ringkasan": "[DUMMY] Materi wajib 1: manfaat sarapan terhadap kadar glukosa darah, fokus, dan prestasi belajar.",
    "konten": "Setelah tidur malam selama 7–8 jam, cadangan glukosa tubuh menurun...",
    "urutan": 1,
    "wajib": true,
    "selesai": true,
    "selesai_pada": "2026-10-29T13:15:00.000000Z"
  },
  "message": "Detail materi edukasi."
}
```

#### Contoh Respons Error (`404 Not Found`)
```json
{
  "data": null,
  "message": "Endpoint atau data tidak ditemukan."
}
```

---

### 10. Tandai Materi Edukasi Selesai Dibaca

Membuat atau memperbarui `baca_materi_t` (`selesai = true`, `selesai_pada = now()`) secara idempotent. Apabila seluruh materi aktif yang `wajib = true` telah selesai dibaca saat siswa berstatus `EDUKASI`, trigger PostgreSQL otomatis memindahkan `status_akun` ke `MENUNGGU_VERIFIKASI`.

- **Method & Path**: `POST /api/materi/{materi_edukasi_id}/selesai`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.status:EDUKASI,TERVERIFIKASI`
- **Request Body**: Tidak diperlukan

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "status_akun": {
      "kode": "MENUNGGU_VERIFIKASI",
      "nama": "Menunggu verifikasi"
    },
    "total_wajib": 2,
    "dibaca_wajib": 2
  },
  "message": "Materi edukasi ditandai selesai dibaca."
}
```

---

### 11. Ringkasan Progres Edukasi

Membaca ringkasan progres pembacaan materi wajib langsung dari view `progres_edukasi_v` beserta `status_akun` terkini.

- **Method & Path**: `GET /api/progres-edukasi`
- **Auth & Middleware**: `auth:sanctum`, `siswa`
- **Request Body**: Tidak ada

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "siswa_id": 5,
    "total_wajib": 2,
    "dibaca_wajib": 2,
    "selesai": true,
    "status_akun": {
      "kode": "MENUNGGU_VERIFIKASI",
      "nama": "Menunggu verifikasi"
    }
  },
  "message": "Progres membaca materi edukasi wajib."
}
```

---

## C. Program Harian & Posttest (Khusus Status `TERVERIFIKASI`)

Seluruh endpoint pada bagian ini memakai middleware `auth:sanctum`, `siswa`, dan `siswa.terverifikasi`, serta berjalan pada zona waktu aplikasi **`Asia/Jakarta` (WIB)**.

Apabila siswa sudah berstatus `TERVERIFIKASI` tetapi belum memiliki baris keikutsertaan aktif pada `peserta_program_t` (`status = 'AKTIF'`), semua endpoint yang membutuhkan konteks program (`beranda`, `planner/*`, `food-record/*`, `rekap`, `posttest/*`) akan mengembalikan `409 Conflict`:

```json
{
  "code": "PROGRAM_BELUM_ADA",
  "data": null,
  "message": "Siswa belum memiliki keikutsertaan program aktif."
}
```

---

### 12. Data Referensi Program Harian

Mengembalikan daftar master referensi untuk form Planner dan Food Record: `bahan_makanan` (hanya yang `aktif = true`, lengkap dengan objek `kelompok` dan `konversi_urt`), `urt`, `tempat_makan`, serta `status_realisasi`.

- **Method & Path**: `GET /api/referensi`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.terverifikasi`
- **Request Body**: Tidak ada

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "bahan_makanan": [
      {
        "bahan_makanan_id": 1,
        "nama_bahan": "Nasi putih",
        "urutan": 1,
        "porsi_standar_gram": 100,
        "kelompok": {
          "kelompok_bahan_id": 1,
          "kode": "A",
          "nama": "Bahan makanan pokok"
        },
        "konversi_urt": [
          {
            "urt_id": 2,
            "nama_urt": "piring",
            "berat_gram": 100
          }
        ]
      }
    ],
    "urt": [
      { "urt_id": 1, "nama_urt": "piring kecil" },
      { "urt_id": 2, "nama_urt": "piring" },
      { "urt_id": 8, "nama_urt": "butir" }
    ],
    "tempat_makan": [
      { "tempat_makan_id": 1, "kode": "RUMAH", "nama": "Rumah" },
      { "tempat_makan_id": 2, "kode": "SEKOLAH", "nama": "Sekolah" },
      { "tempat_makan_id": 3, "kode": "LUAR", "nama": "Di luar / perjalanan" }
    ],
    "status_realisasi": [
      { "status_realisasi_id": 1, "kode": "SESUAI", "nama": "Sesuai rencana" },
      { "status_realisasi_id": 2, "kode": "DIUBAH", "nama": "Diubah" },
      { "status_realisasi_id": 3, "kode": "TIDAK_DIMAKAN", "nama": "Tidak dimakan" },
      { "status_realisasi_id": 4, "kode": "TAMBAHAN", "nama": "Tambahan di luar rencana" }
    ]
  },
  "message": "Data referensi program harian."
}
```

---

### 13. Ringkasan Beranda Siswa

Mengembalikan ringkasan hari dan minggu berjalan pada program 3 minggu, status ketersediaan rencana sarapan besok dan food record hari ini, serta ringkasan kepatuhan minggu berjalan langsung dari view `kepatuhan_mingguan_v`.

- **Method & Path**: `GET /api/beranda`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.terverifikasi`
- **Request Body**: Tidak ada

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "nama": "Siti Aisyah",
    "nama_lengkap": "Siti Aisyah",
    "tanggal_hari_ini": "2026-10-29",
    "tanggal_besok": "2026-10-30",
    "tanggal_mulai": "2026-10-21",
    "tanggal_selesai": "2026-11-10",
    "minggu_ke": 2,
    "hari_ke": 9,
    "durasi_minggu": 3,
    "rencana_besok_ada": true,
    "food_record_hari_ini_ada": false,
    "kepatuhan_minggu_berjalan": {
      "minggu_ke": 2,
      "hari_berjalan": 2,
      "hari_tercatat": 1,
      "hari_sesuai": 1,
      "persen_kepatuhan": 50
    }
  },
  "message": "Ringkasan beranda program harian."
}
```

---

### 14. Ambil Rencana Sarapan (`GET /api/planner/{tanggal}`)

Mengambil rencana sarapan (`rencana_sarapan_t` + `rencana_sarapan_detail_t`) milik siswa pada tanggal `YYYY-MM-DD`, atau `data: null` jika belum ada rencana pada tanggal tersebut.

- **Method & Path**: `GET /api/planner/{tanggal}`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.terverifikasi`
- **Path Parameter**: `tanggal` (`YYYY-MM-DD`)

#### Contoh Respons Sukses — Sudah Ada Rencana (`200 OK`)
```json
{
  "data": {
    "rencana_sarapan_id": 14,
    "tanggal_sarapan": "2026-10-30",
    "jam_rencana": "06:15",
    "tempat_makan_id": 1,
    "tempat_makan": {
      "tempat_makan_id": 1,
      "kode": "RUMAH",
      "nama": "Rumah"
    },
    "items": [
      {
        "rencana_sarapan_detail_id": 27,
        "nama_makanan": "Nasi Putih",
        "bahan_makanan_id": 1,
        "nama_bahan": "Nasi putih",
        "urt_id": 2,
        "nama_urt": "piring",
        "jumlah_urt": 1,
        "urutan": 1
      },
      {
        "rencana_sarapan_detail_id": 28,
        "nama_makanan": "Telur Dadar",
        "bahan_makanan_id": 17,
        "nama_bahan": "Telur ayam",
        "urt_id": 8,
        "nama_urt": "butir",
        "jumlah_urt": 1,
        "urutan": 2
      }
    ]
  },
  "message": "Data rencana sarapan."
}
```

#### Contoh Respons Sukses — Belum Ada Rencana (`200 OK`)
```json
{
  "data": null,
  "message": "Belum ada rencana sarapan pada tanggal tersebut."
}
```

---

### 15. Simpan / Perbarui Rencana Sarapan (`PUT /api/planner/{tanggal}`)

Melakukan *upsert* rencana sarapan berdasarkan `peserta_program_id + tanggal_sarapan`. Seluruh item detail diganti penuh (*delete* lalu *insert* ulang) dalam satu transaksi database.

- **Method & Path**: `PUT /api/planner/{tanggal}`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.terverifikasi`
- **Aturan Tanggal**: Wajib format `YYYY-MM-DD`, berada dalam rentang `[tanggal_mulai, tanggal_selesai]` program siswa, dan **tidak boleh sudah lewat** (`>=` hari ini WIB).

#### Request Body (`application/json`)
| Field | Tipe | Wajib | Aturan Validasi |
|---|---|---|---|
| `jam_rencana` | `string\|null` | Opsional | Format jam `HH:MM` (mis. `"06:15"`) |
| `tempat_makan_id` | `integer\|null` | Opsional | Harus ada pada `tempat_makan_m` |
| `items` | `array` | Ya | Minimal 1 item (`min:1`) |
| `items.*.nama_makanan` | `string` | Ya | Maksimal 100 karakter |
| `items.*.bahan_makanan_id` | `integer\|null` | Opsional | Harus ada pada `bahan_makanan_m` yang `aktif = true` |
| `items.*.urt_id` | `integer` | Ya | Harus ada pada `urt_m` |
| `items.*.jumlah_urt` | `number` | Ya | Numerik `> 0` |

```json
{
  "jam_rencana": "06:15",
  "tempat_makan_id": 1,
  "items": [
    {
      "nama_makanan": "Nasi Putih",
      "bahan_makanan_id": 1,
      "urt_id": 2,
      "jumlah_urt": 1
    },
    {
      "nama_makanan": "Telur Dadar",
      "bahan_makanan_id": 17,
      "urt_id": 8,
      "jumlah_urt": 1
    }
  ]
}
```

#### Contoh Respons Sukses (`200 OK`)
Struktur `data` identik dengan respons `GET /api/planner/{tanggal}` dengan `"message": "Rencana sarapan berhasil disimpan."`.

#### Contoh Respons Error Validasi Tanggal (`422 Unprocessable Entity`)
```json
{
  "data": null,
  "message": "Tanggal rencana sarapan tidak boleh sudah lewat.",
  "errors": {
    "tanggal": [
      "Tanggal rencana sarapan tidak boleh sudah lewat."
    ]
  }
}
```

---

### 16. Ambil Food Record atau Prefill Rencana (`GET /api/food-record/{tanggal}`)

Memiliki 3 perilaku sesuai ketersediaan data siswa pada tanggal `YYYY-MM-DD`:
1. **Sudah ada `food_record_t`**: mengembalikan data tersimpan apa adanya dengan `"dari_rencana": false`.
2. **Belum ada `food_record_t`, tetapi ada `rencana_sarapan_t`**: mengembalikan **prefill otomatis** dari rencana dengan `"dari_rencana": true`, di mana setiap item membawa `rencana_sarapan_detail_id`, estimasi `berat_gram` (bila ada di `konversi_urt_m`), dan `status_realisasi` default `SESUAI`.
3. **Belum ada keduanya**: mengembalikan `"data": null`.

- **Method & Path**: `GET /api/food-record/{tanggal}`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.terverifikasi`

#### Contoh Respons Sukses — Prefill dari Rencana (`200 OK`)
```json
{
  "data": {
    "dari_rencana": true,
    "food_record_id": null,
    "rencana_sarapan_id": 14,
    "tanggal": "2026-10-29",
    "tidak_sarapan": false,
    "jam_makan": "06:15",
    "tempat_makan_id": 1,
    "tempat_makan": {
      "tempat_makan_id": 1,
      "kode": "RUMAH",
      "nama": "Rumah"
    },
    "items": [
      {
        "food_record_detail_id": null,
        "rencana_sarapan_detail_id": 27,
        "status_realisasi_id": 1,
        "status_realisasi": {
          "status_realisasi_id": 1,
          "kode": "SESUAI",
          "nama": "Sesuai rencana"
        },
        "nama_makanan": "Nasi Putih",
        "bahan_teks": "Nasi putih",
        "bahan_makanan_id": 1,
        "urt_id": 2,
        "nama_urt": "piring",
        "jumlah_urt": 1,
        "berat_gram": 100,
        "keterangan": null,
        "urutan": 1
      }
    ]
  },
  "message": "Prefill food record dari rencana sarapan."
}
```

---

### 17. Simpan / Perbarui Food Record (`PUT /api/food-record/{tanggal}`)

Melakukan *upsert* food record berdasarkan `peserta_program_id + tanggal`. Otomatis menautkan `rencana_sarapan_id` apabila siswa punya rencana sarapan pada hari tersebut, serta mengganti seluruh item detail dalam satu transaksi database.

- **Method & Path**: `PUT /api/food-record/{tanggal}`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.terverifikasi`
- **Aturan Tanggal**: Wajib format `YYYY-MM-DD`, berada dalam rentang `[tanggal_mulai, tanggal_selesai]` program siswa, dan **tidak boleh berada di masa depan** (`<=` hari ini WIB).

#### Request Body (`application/json`)
| Field | Tipe | Wajib | Aturan Validasi |
|---|---|---|---|
| `tidak_sarapan` | `boolean` | Ya | Jika `true`, `items` boleh kosong (`[]`); jika `false`, wajib minimal 1 item |
| `jam_makan` | `string\|null` | Opsional | Format jam `HH:MM` |
| `tempat_makan_id` | `integer\|null` | Opsional | Harus ada pada `tempat_makan_m` |
| `items` | `array` | Kondisional | Wajib `min:1` bila `tidak_sarapan = false` |
| `items.*.nama_makanan` | `string` | Ya* | Maksimal 100 karakter |
| `items.*.bahan_teks` | `string\|null` | Opsional | Maksimal 255 karakter |
| `items.*.bahan_makanan_id` | `integer\|null` | Opsional | Harus ada pada `bahan_makanan_m` |
| `items.*.urt_id` | `integer\|null` | Opsional | Harus ada pada `urt_m` |
| `items.*.jumlah_urt` | `number\|null` | Opsional | Numerik `>= 0` |
| `items.*.berat_gram` | `number\|null` | Opsional | Numerik `>= 0` (bila kosong, dihitung dari `konversi_urt_m` jika ada) |
| `items.*.keterangan` | `string\|null` | Opsional | Maksimal 255 karakter |
| `items.*.status_realisasi_id` | `integer` | Ya* | Harus ada pada `status_realisasi_m` |
| `items.*.rencana_sarapan_detail_id` | `integer\|null` | Opsional | Harus milik rencana sarapan siswa sendiri pada tanggal tersebut |

#### Contoh Request — Sarapan Tercatat (`tidak_sarapan = false`)
```json
{
  "tidak_sarapan": false,
  "jam_makan": "06:25",
  "tempat_makan_id": 1,
  "items": [
    {
      "nama_makanan": "Nasi Putih",
      "bahan_teks": "Nasi putih hangat",
      "bahan_makanan_id": 1,
      "urt_id": 2,
      "jumlah_urt": 1,
      "berat_gram": 100,
      "keterangan": "Habis 1 porsi",
      "status_realisasi_id": 1,
      "rencana_sarapan_detail_id": 27
    }
  ]
}
```

#### Contoh Request — Tidak Sarapan (`tidak_sarapan = true`)
```json
{
  "tidak_sarapan": true,
  "items": []
}
```

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "dari_rencana": false,
    "food_record_id": 9,
    "rencana_sarapan_id": 14,
    "tanggal": "2026-10-29",
    "tidak_sarapan": false,
    "jam_makan": "06:25",
    "tempat_makan_id": 1,
    "tempat_makan": {
      "tempat_makan_id": 1,
      "kode": "RUMAH",
      "nama": "Rumah"
    },
    "items": [
      {
        "food_record_detail_id": 19,
        "rencana_sarapan_detail_id": 27,
        "status_realisasi_id": 1,
        "status_realisasi": {
          "status_realisasi_id": 1,
          "kode": "SESUAI",
          "nama": "Sesuai rencana"
        },
        "nama_makanan": "Nasi Putih",
        "bahan_teks": "Nasi putih hangat",
        "bahan_makanan_id": 1,
        "urt_id": 2,
        "nama_urt": "piring",
        "jumlah_urt": 1,
        "berat_gram": 100,
        "keterangan": "Habis 1 porsi",
        "urutan": 1
      }
    ]
  },
  "message": "Food record berhasil disimpan."
}
```

---

### 18. Rekap Kepatuhan Mingguan & Skor Pengetahuan (`GET /api/rekap`)

Mengambil rekap program minggu 1–3 tanpa menghitung ulang di PHP:
- Status harian (`SESUAI`, `SEBAGIAN`, `TIDAK_SESUAI`, `BELUM_DICATAT`) dari `kepatuhan_harian_v`.
- Ringkasan per minggu dari `kepatuhan_mingguan_v`.
- Skor pretest, posttest, dan selisih dari `skor_pengetahuan_v`.

- **Method & Path**: `GET /api/rekap`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.terverifikasi`

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "minggu": [
      {
        "minggu_ke": 1,
        "ringkasan": {
          "hari_berjalan": 4,
          "hari_tercatat": 3,
          "hari_sesuai": 1,
          "persen_kepatuhan": 25
        },
        "hari": [
          {
            "tanggal": "2026-10-26",
            "status_kepatuhan": "SESUAI",
            "food_record_id": 6
          },
          {
            "tanggal": "2026-10-27",
            "status_kepatuhan": "SEBAGIAN",
            "food_record_id": 7
          },
          {
            "tanggal": "2026-10-28",
            "status_kepatuhan": "TIDAK_SESUAI",
            "food_record_id": 8
          },
          {
            "tanggal": "2026-10-29",
            "status_kepatuhan": "BELUM_DICATAT",
            "food_record_id": null
          }
        ]
      },
      {
        "minggu_ke": 2,
        "ringkasan": {
          "hari_berjalan": 0,
          "hari_tercatat": 0,
          "hari_sesuai": 0,
          "persen_kepatuhan": 0
        },
        "hari": []
      },
      {
        "minggu_ke": 3,
        "ringkasan": {
          "hari_berjalan": 0,
          "hari_tercatat": 0,
          "hari_sesuai": 0,
          "persen_kepatuhan": 0
        },
        "hari": []
      }
    ],
    "skor_pengetahuan": {
      "skor_pretest": 80,
      "skor_posttest": null,
      "selisih": null
    }
  },
  "message": "Rekap kepatuhan dan skor pengetahuan siswa."
}
```

---

### 19. Ambil Soal Posttest (`GET /api/posttest`)

Mengambil daftar soal `kuesioner_m` berjenis `POSTTEST` yang aktif (urut `nomor`, tanpa kolom `benar`). Hanya terbuka mulai **`tanggal_mulai + 21 hari`** (setelah program 3 minggu selesai).

- **Method & Path**: `GET /api/posttest`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.terverifikasi`

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "kuesioner_id": 2,
    "judul": "Post test pengetahuan gizi",
    "soal": [
      {
        "soal_id": 6,
        "nomor": 1,
        "teks_soal": "Mengapa sarapan pagi sebelum berangkat sekolah penting bagi remaja?",
        "opsi": [
          {
            "opsi_jawaban_id": 21,
            "kode_opsi": "A",
            "teks_opsi": "Membantu menjaga konsentrasi belajar dan pasokan energi pagi hari"
          },
          {
            "opsi_jawaban_id": 22,
            "kode_opsi": "B",
            "teks_opsi": "Menyebabkan rasa kantuk berlebih sepanjang pelajaran"
          }
        ]
      }
    ]
  },
  "message": "Daftar soal posttest."
}
```

#### Contoh Respons Error — Belum Waktunya (`403 Forbidden`)
```json
{
  "code": "POSTTEST_BELUM_DIBUKA",
  "data": null,
  "message": "Posttest baru dibuka setelah 21 hari sejak tanggal mulai program.",
  "tanggal_buka": "2026-11-11"
}
```

---

### 20. Kirim Jawaban Posttest (`POST /api/posttest/submit`)

Menilai jawaban posttest di server dan menyimpan ke `hasil_kuesioner_t` + `jawaban_kuesioner_t`. Hanya dapat dikirim satu kali setelah `tanggal_mulai + 21 hari`.

- **Method & Path**: `POST /api/posttest/submit`
- **Auth & Middleware**: `auth:sanctum`, `siswa`, `siswa.terverifikasi`

#### Request Body (`application/json`)
```json
{
  "jawaban": [
    { "soal_id": 6, "opsi_jawaban_id": 21 },
    { "soal_id": 7, "opsi_jawaban_id": 26 },
    { "soal_id": 8, "opsi_jawaban_id": 30 },
    { "soal_id": 9, "opsi_jawaban_id": 35 },
    { "soal_id": 10, "opsi_jawaban_id": 37 }
  ]
}
```

#### Contoh Respons Sukses (`200 OK`)
```json
{
  "data": {
    "skor": 100,
    "jumlah_benar": 5,
    "jumlah_soal": 5
  },
  "message": "Posttest berhasil diselesaikan."
}
```

#### Contoh Respons Error
- **Belum Mencapai `tanggal_mulai + 21 hari` (`403 Forbidden`)**:
  ```json
  {
    "code": "POSTTEST_BELUM_DIBUKA",
    "data": null,
    "message": "Posttest baru dibuka setelah 21 hari sejak tanggal mulai program.",
    "tanggal_buka": "2026-11-11"
  }
  ```
- **Posttest Sudah Pernah Dikirim (`409 Conflict`)**:
  ```json
  {
    "code": "POSTTEST_SUDAH_DIKIRIM",
    "data": null,
    "message": "Posttest sudah pernah dikirim dan tidak dapat diulang."
  }
  ```

---

## D. Kontrak Error Middleware Status Siswa

Seluruh rute di luar `/api/kelas`, `/api/auth/*`, dan `/api/me` dilindungi oleh middleware peran siswa dan middleware status akun:

1. **Middleware `siswa` (Bukan peran `SISWA` atau tidak punya `siswa_m`) — `403 Forbidden`**:
   ```json
   {
     "code": "AKSES_KHUSUS_SISWA",
     "data": null,
     "message": "Endpoint ini hanya dapat diakses oleh akun siswa aktif."
   }
   ```

2. **Middleware `siswa.terverifikasi` (Status akun belum `TERVERIFIKASI`) — `403 Forbidden`**:
   ```json
   {
     "code": "STATUS_BELUM_TERVERIFIKASI",
     "message": "Akun siswa belum berstatus TERVERIFIKASI.",
     "status_akun": "PRETEST"
   }
   ```

3. **Middleware `siswa.status:<KODE_1>,<KODE_2>` (Status akun di luar daftar kode yang diizinkan) — `403 Forbidden`**:
   ```json
   {
     "code": "STATUS_TIDAK_SESUAI",
     "message": "Status akun siswa tidak sesuai untuk mengakses endpoint ini.",
     "status_akun": "EDUKASI"
   }
   ```
