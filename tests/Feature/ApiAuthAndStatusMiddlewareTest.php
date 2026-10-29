<?php

use App\Models\Kelas;
use App\Models\Pengguna;
use App\Models\Siswa;
use App\Models\StatusAkun;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware(['api', 'auth:sanctum', 'siswa', 'siswa.terverifikasi'])
        ->get('/api/_test/terverifikasi-only', fn () => response()->json([
            'data' => ['ok' => true],
            'message' => 'Akses terverifikasi diizinkan.',
        ]));

    Route::middleware(['api', 'auth:sanctum', 'siswa', 'siswa.status:PRETEST'])
        ->get('/api/_test/pretest-only', fn () => response()->json([
            'data' => ['ok' => true],
            'message' => 'Akses pretest diizinkan.',
        ]));

    Route::middleware(['api', 'auth:sanctum', 'siswa', 'siswa.status:PRETEST,EDUKASI'])
        ->get('/api/_test/multi-status', fn () => response()->json([
            'data' => ['ok' => true],
            'message' => 'Akses multi status diizinkan.',
        ]));
});

it('returns only active classes on GET /api/kelas', function (): void {
    Kelas::query()->create([
        'nama_kelas' => '8A',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);
    Kelas::query()->create([
        'nama_kelas' => '8Z',
        'tahun_ajaran' => '2024/2025',
        'aktif' => false,
    ]);

    $this->getJson('/api/kelas')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['kelas_id', 'nama_kelas', 'tahun_ajaran'],
            ],
            'message',
        ])
        ->assertJsonFragment(['nama_kelas' => '8A'])
        ->assertJsonMissing(['nama_kelas' => '8Z']);
});

it('registers a new student with status PRETEST and returns a Sanctum token plus /me payload', function (): void {
    $kelas = Kelas::query()->create([
        'nama_kelas' => '8A',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $response = $this->postJson('/api/auth/register', [
        'nama_lengkap' => 'Siti Aisyah',
        'email' => 'Siti.Aisyah@Siswa.ID',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'kelas_id' => $kelas->kelas_id,
        'jenis_kelamin' => 'P',
        'tanggal_lahir' => '2012-04-15',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'token',
                'user' => [
                    'pengguna_id',
                    'nama_lengkap',
                    'email',
                    'siswa_id',
                    'kelas' => ['kelas_id', 'nama_kelas'],
                    'status_akun' => ['kode', 'nama'],
                    'terverifikasi_pada',
                ],
            ],
            'message',
        ])
        ->assertJsonPath('data.user.email', 'siti.aisyah@siswa.id')
        ->assertJsonPath('data.user.kelas.nama_kelas', '8A')
        ->assertJsonPath('data.user.status_akun.kode', 'PRETEST')
        ->assertJsonPath('data.user.terverifikasi_pada', null);

    $pengguna = Pengguna::query()->where('email', 'siti.aisyah@siswa.id')->firstOrFail();
    expect(Hash::check('rahasia123', $pengguna->kata_sandi_hash))->toBeTrue()
        ->and($pengguna->peran->kode)->toBe('SISWA');
});

it('rejects duplicate email regardless of letter case with HTTP 422', function (): void {
    $kelas = Kelas::query()->create([
        'nama_kelas' => '8A',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $akun = Pengguna::factory()->siswa()->create([
        'email' => 'andi@siswa.id',
    ]);
    $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();
    Siswa::query()->create([
        'pengguna_id' => $akun->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusPretest->status_akun_id,
    ]);

    $this->postJson('/api/auth/register', [
        'nama_lengkap' => 'Andi Duplikat',
        'email' => 'ANDI@SISWA.ID',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'kelas_id' => $kelas->kelas_id,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('data', null)
        ->assertJsonValidationErrors(['email']);
});

it('validates password minimum length of 8 and confirmation on register', function (): void {
    $kelas = Kelas::query()->create([
        'nama_kelas' => '8A',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $this->postJson('/api/auth/register', [
        'nama_lengkap' => 'Siswa Uji',
        'email' => 'uji@siswa.id',
        'password' => 'pendek',
        'password_confirmation' => 'berbeda',
        'kelas_id' => $kelas->kelas_id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('returns HTTP 401 when login credentials are wrong', function (): void {
    $kelas = Kelas::query()->create([
        'nama_kelas' => '8A',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $akun = Pengguna::factory()->siswa()->create([
        'email' => 'siswa@contoh.id',
        'kata_sandi_hash' => Hash::make('rahasia123'),
    ]);
    $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();
    Siswa::query()->create([
        'pengguna_id' => $akun->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusPretest->status_akun_id,
    ]);

    $this->postJson('/api/auth/login', [
        'email' => 'siswa@contoh.id',
        'password' => 'salah12345',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('data', null);
});

it('forbids ADMIN, PETUGAS, and inactive students from logging in via API', function (): void {
    $admin = Pengguna::factory()->admin()->create([
        'email' => 'admin@contoh.id',
        'kata_sandi_hash' => Hash::make('rahasia123'),
        'aktif' => true,
    ]);

    $petugas = Pengguna::factory()->petugas()->create([
        'email' => 'petugas@contoh.id',
        'kata_sandi_hash' => Hash::make('rahasia123'),
        'aktif' => true,
    ]);

    $kelas = Kelas::query()->create([
        'nama_kelas' => '8A',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);
    $siswaNonaktif = Pengguna::factory()->siswa()->nonaktif()->create([
        'email' => 'nonaktif@siswa.id',
        'kata_sandi_hash' => Hash::make('rahasia123'),
    ]);
    $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();
    Siswa::query()->create([
        'pengguna_id' => $siswaNonaktif->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusPretest->status_akun_id,
    ]);

    $this->postJson('/api/auth/login', [
        'email' => $admin->email,
        'password' => 'rahasia123',
    ])->assertForbidden();

    $this->postJson('/api/auth/login', [
        'email' => $petugas->email,
        'password' => 'rahasia123',
    ])->assertForbidden();

    $this->postJson('/api/auth/login', [
        'email' => $siswaNonaktif->email,
        'password' => 'rahasia123',
    ])->assertForbidden();
});

it('rate limits POST /api/auth/login to 5 attempts per minute per email and IP', function (): void {
    for ($i = 1; $i <= 5; $i++) {
        $this->postJson('/api/auth/login', [
            'email' => 'ratelimit@siswa.id',
            'password' => 'salah12345',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/auth/login', [
        'email' => 'ratelimit@siswa.id',
        'password' => 'salah12345',
    ])
        ->assertTooManyRequests()
        ->assertJsonPath('data', null);
});

it('always returns HTTP 401 JSON on /api/me without a token even without Accept application/json header', function (): void {
    $response = $this->get('/api/me');

    $response->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJson([
            'data' => null,
            'message' => 'Tidak terautentikasi.',
        ]);
});

it('logs in a student, updates terakhir_login_pada, serves /api/me, and revokes the current token on logout', function (): void {
    $kelas = Kelas::query()->create([
        'nama_kelas' => '8B',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);
    $akun = Pengguna::factory()->siswa()->create([
        'nama_lengkap' => 'Rina Permata',
        'email' => 'rina@siswa.id',
        'kata_sandi_hash' => Hash::make('rahasia123'),
        'terakhir_login_pada' => null,
    ]);
    $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();
    $siswa = Siswa::query()->create([
        'pengguna_id' => $akun->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusPretest->status_akun_id,
    ]);

    $loginRes = $this->postJson('/api/auth/login', [
        'email' => 'RINA@siswa.id',
        'password' => 'rahasia123',
    ])->assertOk();

    $token = $loginRes->json('data.token');
    expect($token)->not->toBeEmpty()
        ->and($akun->fresh()->terakhir_login_pada)->not->toBeNull();

    $this->withToken($token)
        ->getJson('/api/me')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                'pengguna_id' => $akun->pengguna_id,
                'nama_lengkap' => 'Rina Permata',
                'email' => 'rina@siswa.id',
                'siswa_id' => $siswa->siswa_id,
                'kelas' => [
                    'kelas_id' => $kelas->kelas_id,
                    'nama_kelas' => '8B',
                ],
                'status_akun' => [
                    'kode' => 'PRETEST',
                    'nama' => $statusPretest->nama,
                ],
                'terverifikasi_pada' => null,
            ],
            'message' => 'Data profil pengguna.',
        ]);

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertOk()
        ->assertJson([
            'data' => null,
            'message' => 'Logout berhasil.',
        ]);

    expect($akun->tokens()->count())->toBe(0);

    auth()->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/me')
        ->assertUnauthorized();
});

it('blocks unverified students on siswa.terverifikasi routes with 403 STATUS_BELUM_TERVERIFIKASI and enforces siswa.status codes', function (): void {
    $kelas = Kelas::query()->create([
        'nama_kelas' => '8C',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();
    $statusEdukasi = StatusAkun::query()->where('kode', 'EDUKASI')->firstOrFail();
    $statusTerverifikasi = StatusAkun::query()->where('kode', 'TERVERIFIKASI')->firstOrFail();

    $akunPretest = Pengguna::factory()->siswa()->create();
    Siswa::query()->create([
        'pengguna_id' => $akunPretest->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusPretest->status_akun_id,
    ]);
    $tokenPretest = $akunPretest->createToken('mobile')->plainTextToken;

    $akunEdukasi = Pengguna::factory()->siswa()->create();
    Siswa::query()->create([
        'pengguna_id' => $akunEdukasi->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusEdukasi->status_akun_id,
    ]);
    $tokenEdukasi = $akunEdukasi->createToken('mobile')->plainTextToken;

    $akunTerverifikasi = Pengguna::factory()->siswa()->create();
    Siswa::query()->create([
        'pengguna_id' => $akunTerverifikasi->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusTerverifikasi->status_akun_id,
        'terverifikasi_pada' => now(),
    ]);
    $tokenTerverifikasi = $akunTerverifikasi->createToken('mobile')->plainTextToken;

    // 1. siswa.terverifikasi menolak siswa PRETEST dengan format JSON yang tepat
    $this->withToken($tokenPretest)
        ->getJson('/api/_test/terverifikasi-only')
        ->assertForbidden()
        ->assertJson([
            'code' => 'STATUS_BELUM_TERVERIFIKASI',
            'status_akun' => 'PRETEST',
        ]);

    // 2. siswa.terverifikasi mengizinkan siswa TERVERIFIKASI
    auth()->forgetGuards();
    $this->withToken($tokenTerverifikasi)
        ->getJson('/api/_test/terverifikasi-only')
        ->assertOk()
        ->assertJsonPath('data.ok', true);

    // 3. siswa.status:PRETEST mengizinkan PRETEST dan menolak EDUKASI dengan STATUS_TIDAK_SESUAI
    auth()->forgetGuards();
    $this->withToken($tokenPretest)
        ->getJson('/api/_test/pretest-only')
        ->assertOk();

    auth()->forgetGuards();
    $this->withToken($tokenEdukasi)
        ->getJson('/api/_test/pretest-only')
        ->assertForbidden()
        ->assertJson([
            'code' => 'STATUS_TIDAK_SESUAI',
            'status_akun' => 'EDUKASI',
        ]);

    // 4. siswa.status:PRETEST,EDUKASI menerima lebih dari satu kode
    auth()->forgetGuards();
    $this->withToken($tokenEdukasi)
        ->getJson('/api/_test/multi-status')
        ->assertOk();

    auth()->forgetGuards();
    $this->withToken($tokenTerverifikasi)
        ->getJson('/api/_test/multi-status')
        ->assertForbidden()
        ->assertJson([
            'code' => 'STATUS_TIDAK_SESUAI',
            'status_akun' => 'TERVERIFIKASI',
        ]);

    // 5. Pengguna berperan ADMIN ditolak pada rute khusus siswa
    $admin = Pengguna::factory()->admin()->create();
    $tokenAdmin = $admin->createToken('test-admin')->plainTextToken;

    auth()->forgetGuards();
    $this->withToken($tokenAdmin)
        ->getJson('/api/_test/terverifikasi-only')
        ->assertForbidden()
        ->assertJsonPath('code', 'AKSES_KHUSUS_SISWA');
});
