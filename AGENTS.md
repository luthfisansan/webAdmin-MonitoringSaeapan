<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.2. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project uses the streamlined Laravel 11+ structure: register middleware, exceptions, and routing in `bootstrap/app.php` and service providers in `bootstrap/providers.php`. There is no `app/Http/Kernel.php` or `app/Console/Kernel.php`, and commands in `app/Console/Commands/` auto-register.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
# Digital Breakfast Planner — Project Brief

Aplikasi monitoring sarapan untuk tugas akhir mahasiswa gizi. Satu repo Laravel (web admin + API mobile) dan satu app Flutter untuk siswa. Bahasa UI dan penamaan domain: Indonesia.

## Stack (jangan diganti tanpa tanya)
- Backend + web admin: Laravel (versi stabil terbaru), PostgreSQL 14+, Laravel Sanctum (token mobile), Filament (CRUD web admin), Livewire (form SQ-FFQ custom).
- Mobile: Flutter stable, Riverpod, go_router, dio, flutter_secure_storage.
- Satu deploy: web admin di `/admin`, API di `/api`.

## Aturan paling penting
1. **Database adalah sumber kebenaran.** Skema ada di `database/schema/breakfast_planner_schema.sql`. Jangan ubah nama tabel/kolom. Kalau perlu perubahan skema, buat migration baru yang `ALTER`, jangan edit file asli.
2. **Jalankan skema lewat migration pertama** dengan `DB::unprepared(file_get_contents(...))`. Jangan ditulis ulang pakai Blueprint (trigger, view, partial index tidak terbawa).
3. **Jangan duplikasi logika status akun di PHP/Dart.** Perpindahan status dilakukan trigger database. Aplikasi hanya menulis data, lalu membaca `siswa_m.status_akun_id` yang sudah berubah.
4. **Penilaian pretest/posttest dilakukan di server.** Jangan pernah kirim kolom `benar` ke klien.
5. **Semua endpoint di luar onboarding harus ditolak (403) kalau status siswa belum TERVERIFIKASI.** Pakai satu middleware.

## Penamaan database
- `_m` = master, `_t` = transaksi, `_v` = view.
- Primary key = `<entitas>_id` (id di BELAKANG), contoh `siswa_id`, `sqffq_detail_id`. Jangan pernah `id` atau `id_siswa`.
- Foreign key memakai nama persis sama dengan PK tujuan. Kalau dua relasi ke tabel sama, beri awalan peran tapi tetap berakhiran `_id` (`petugas_pengguna_id`, `terverifikasi_oleh_pengguna_id`).
- Timestamp: `dibuat_pada` / `diubah_pada`.

### Setiap model Eloquent wajib punya
```php
protected $table = 'siswa_m';
protected $primaryKey = 'siswa_id';
const CREATED_AT = 'dibuat_pada';
const UPDATED_AT = 'diubah_pada';
```
Tabel `*_t` yang tidak punya `diubah_pada` → set `const UPDATED_AT = null;`. Cek kolom aktual di skema sebelum menulis model. View `_v` dipakai lewat model read-only.

## Alur final (state machine status akun)
```
Registrasi → PRETEST → (pretest selesai) → EDUKASI → (semua materi wajib terbaca)
→ MENUNGGU_VERIFIKASI → (petugas selesaikan SQ-FFQ awal di web admin) → TERVERIFIKASI → Beranda
```
- Kode status ada di `status_akun_m`: PRETEST, EDUKASI, MENUNGGU_VERIFIKASI, TERVERIFIKASI.
- Pemicu trigger:
  - `UPDATE hasil_kuesioner_t SET selesai_pada = now()` untuk kuesioner PRETEST → EDUKASI. Buat baris dulu, isi skor, lalu UPDATE `selesai_pada`.
  - `baca_materi_t` dengan `selesai = true` → bila semua materi `wajib` dan `aktif` sudah dibaca → MENUNGGU_VERIFIKASI.
  - `UPDATE sqffq_t SET status = 'SELESAI'` (tahap AWAL) → TERVERIFIKASI, siswa otomatis didaftarkan ke `peserta_program_t` program aktif terbaru.
- Beranda siswa: Materi edukasi, Planner, Food record, Rekap. Materi edukasi wajib dibaca setelah pretest, tetapi tetap bisa dibuka lagi dari Beranda.
- Aplikasi Flutter memakai SATU layar pengarah: baca `GET /api/me`, lalu arahkan berdasar `status_akun.kode`. Jangan simpan "langkah terakhir" di klien.

## Program harian (setelah TERVERIFIKASI)
- Program 3 minggu. Malam hari siswa membuat rencana sarapan besok (`rencana_sarapan_t` + detail). Pagi hari siswa mengisi food record (`food_record_t` + detail) yang otomatis terisi dari rencana, lalu dibandingkan (kepatuhan).
- Kolom food record mengikuti formulir kertas: Waktu, Tempat, Jenis makanan, Bahan makanan, URT, Gram, Keterangan.
- Satu food record per `peserta_program_id` + `tanggal`. Kalau tidak sarapan, set `tidak_sarapan = true`.
- Kepatuhan dihitung oleh view `kepatuhan_harian_v` dan `kepatuhan_mingguan_v`. Jangan hitung ulang di aplikasi.

## API mobile (`routes/api.php`, Sanctum)
Auth (publik)
- `POST /api/auth/register` — buat `pengguna_m` (peran SISWA) + `siswa_m` (status PRETEST), kembalikan token.
- `POST /api/auth/login`, `POST /api/auth/logout`
- `GET /api/me` — profil + `status_akun` (kode + nama) + `kelas`.

Onboarding (login, status apa pun)
- `GET /api/kelas` — daftar kelas untuk registrasi (publik).
- `GET /api/pretest` — soal + opsi (tanpa `benar`). Hanya status PRETEST.
- `POST /api/pretest/submit` — body: daftar `{soal_id, opsi_jawaban_id}`. Server menilai, simpan `hasil_kuesioner_t` + `jawaban_kuesioner_t`, lalu UPDATE `selesai_pada`.
- `GET /api/materi`, `GET /api/materi/{materi_edukasi_id}`
- `POST /api/materi/{materi_edukasi_id}/selesai`
- `GET /api/progres-edukasi` (dari `progres_edukasi_v`).

Terverifikasi saja (middleware status)
- `GET /api/beranda` — minggu/hari ke berapa, ada/tidaknya rencana malam ini, status food record hari ini.
- `GET /api/referensi` — bahan makanan, URT, tempat makan, status realisasi.
- `GET /api/planner/{tanggal}`, `PUT /api/planner/{tanggal}`
- `GET /api/food-record/{tanggal}`, `PUT /api/food-record/{tanggal}` — kalau belum ada, kembalikan prefill dari rencana.
- `GET /api/rekap` — kepatuhan per minggu + skor pretest/posttest.
- `GET /api/posttest`, `POST /api/posttest/submit` — hanya terbuka setelah minggu 3.

Respons konsisten: `{ "data": ..., "message": ... }`. Error validasi 422, status belum memenuhi 403 dengan `{"code": "STATUS_BELUM_TERVERIFIKASI"}`.

## Web admin (Filament di `/admin`, peran PETUGAS/ADMIN)
- Login petugas. Siswa tidak boleh masuk.
- CRUD: kelas, program, kuesioner + soal + opsi, materi edukasi, kelompok/bahan makanan, URT.
- **Antrean verifikasi** — dari `antrean_verifikasi_v`: siswa berstatus MENUNGGU_VERIFIKASI.
- **Form SQ-FFQ (halaman custom Livewire)** — petugas mewawancarai, jadi bukan form isian siswa. 9 kelompok (A–I), 93 bahan, tiap bahan: frekuensi (6 opsi) + porsi gram. Simpan sebagai draf, lalu tombol "Selesaikan" yang mengubah `sqffq_t.status` jadi SELESAI.
- Detail siswa: status, hasil pretest, progres edukasi, SQ-FFQ, kepatuhan.
- Laporan (dari view): `laporan_kelas_v`, `pola_konsumsi_kelompok_v`, `tempat_sarapan_v`, `sqffq_rekap_v`, `sqffq_skor_v`. Ekspor Excel/CSV.

## Urutan pengerjaan (kerjakan satu tahap, tes, baru lanjut)
1. Setup Laravel + PostgreSQL, migration dari file skema, model-model dengan konfigurasi PK, seeder admin/petugas + kelas + program + contoh soal/materi.
2. Auth API (register/login/me) + middleware status. Tes dengan Pest/PHPUnit: register → status PRETEST.
3. Web admin minimal: login, CRUD soal/materi/kelas/program.
4. API onboarding (pretest, materi) + tes alur sampai MENUNGGU_VERIFIKASI.
5. Form SQ-FFQ + antrean verifikasi di web admin. Tes alur sampai TERVERIFIKASI.
6. Flutter: layar pengarah, registrasi/login, pretest, edukasi wajib, menunggu verifikasi.
7. Flutter: Beranda, Planner, Food record, Rekap + endpoint terkait.
8. Laporan web admin, posttest, ekspor.

## Hal yang belum final (tanyakan ke pemilik proyek, jangan menebak)
- Nilai `frekuensi_m.kali_per_minggu` dan `skor` adalah asumsi sementara, harus dicocokkan dengan rumus food score.
- Isi soal pretest/posttest dan materi edukasi belum ada; pakai data contoh yang jelas bertanda dummy.
- Definisi kepatuhan harian (SESUAI / SEBAGIAN / TIDAK_SESUAI) belum dikonfirmasi dosen pembimbing.
- Belum ada alur penolakan/ulang SQ-FFQ.
- Kolom `jenis_kelamin` dan `tanggal_lahir` opsional.

## Desain
- Mobile: tema hijau (#17624B), aksen oranye (#F2A33A), font Plus Jakarta Sans, layar 390x844.
- Referensi layar ada di file Figma: https://www.figma.com/design/DsIbaqfL3UI7eZUz47OSYp (halaman 1 = mobile, halaman "Web Admin" = admin).
- Catatan: layar Edukasi di Figma mobile masih di Beranda, belum jadi langkah wajib setelah Pretest. Ikuti alur final di atas.
