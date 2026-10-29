<?php

use App\Models\BacaMateri;
use App\Models\BahanMakanan;
use App\Models\FoodRecord;
use App\Models\FoodRecordDetail;
use App\Models\HasilKuesioner;
use App\Models\Kelas;
use App\Models\KonversiUrt;
use App\Models\Kuesioner;
use App\Models\MateriEdukasi;
use App\Models\OpsiJawaban;
use App\Models\Pengguna;
use App\Models\PesertaProgram;
use App\Models\Program;
use App\Models\RencanaSarapan;
use App\Models\RencanaSarapanDetail;
use App\Models\Siswa;
use App\Models\Sqffq;
use App\Models\StatusRealisasi;
use App\Models\TempatMakan;
use App\Models\Urt;
use Database\Seeders\DevContentSeeder;
use Illuminate\Support\Carbon;

/**
 * Helper: daftarkan siswa lewat endpoint register lalu jalankan transisi trigger DB
 * (pretest selesai -> materi wajib selesai -> SQ-FFQ awal selesai) hingga berstatus TERVERIFIKASI,
 * tanpa pernah menulis kolom status_akun_id dari PHP.
 *
 * @return array{token: string, siswa: Siswa, peserta: ?PesertaProgram}
 */
function buatSiswaTerverifikasiViaTrigger(
    string $email,
    string $namaLengkap = 'Siswa Terverifikasi',
    float $skorPretest = 80.0,
): array {
    $kelas = Kelas::query()->where('aktif', true)->first()
        ?? Kelas::query()->create([
            'nama_kelas' => '8A',
            'tahun_ajaran' => '2025/2026',
            'aktif' => true,
        ]);

    $petugas = Pengguna::factory()->petugas()->create();

    $registerRes = test()->postJson('/api/auth/register', [
        'nama_lengkap' => $namaLengkap,
        'email' => $email,
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'kelas_id' => $kelas->kelas_id,
    ])->assertCreated();

    $token = (string) $registerRes->json('data.token');
    $siswa = Siswa::query()->findOrFail($registerRes->json('data.user.siswa_id'));

    $pretest = Kuesioner::query()->where('jenis', 'PRETEST')->firstOrFail();

    HasilKuesioner::query()->create([
        'siswa_id' => $siswa->siswa_id,
        'kuesioner_id' => $pretest->kuesioner_id,
        'mulai_pada' => now(),
        'jumlah_soal' => 5,
        'jumlah_benar' => (int) round(($skorPretest / 100) * 5),
        'skor' => $skorPretest,
        'selesai_pada' => now(),
    ]);

    $materiWajibIds = MateriEdukasi::query()
        ->where('wajib', true)
        ->where('aktif', true)
        ->pluck('materi_edukasi_id');

    foreach ($materiWajibIds as $materiId) {
        BacaMateri::query()->updateOrCreate(
            [
                'siswa_id' => $siswa->siswa_id,
                'materi_edukasi_id' => $materiId,
            ],
            [
                'selesai' => true,
                'selesai_pada' => now(),
            ],
        );
    }

    Sqffq::query()->create([
        'siswa_id' => $siswa->siswa_id,
        'petugas_pengguna_id' => $petugas->pengguna_id,
        'tahap' => 'AWAL',
        'status' => 'SELESAI',
        'tanggal_wawancara' => Carbon::today()->toDateString(),
        'selesai_pada' => now(),
    ]);

    auth()->forgetGuards();

    $siswa->refresh()->load('statusAkun');
    expect($siswa->statusAkun->kode)->toBe('TERVERIFIKASI');

    $peserta = PesertaProgram::query()
        ->where('siswa_id', $siswa->siswa_id)
        ->where('status', 'AKTIF')
        ->first();

    return [
        'token' => $token,
        'siswa' => $siswa,
        'peserta' => $peserta,
    ];
}

it('uses Asia/Jakarta as the application timezone', function (): void {
    expect(config('app.timezone'))->toBe('Asia/Jakarta');
});

it('returns 409 PROGRAM_BELUM_ADA when a verified student has no active peserta_program', function (): void {
    // Pastikan belum ada program aktif sebelum verifikasi sehingga trigger tidak mendaftarkan peserta_program_t
    Program::query()->update(['aktif' => false]);

    ['token' => $token, 'peserta' => $peserta] = buatSiswaTerverifikasiViaTrigger('tanpaprogram@siswa.id');

    expect($peserta)->toBeNull();

    $this->withToken($token)
        ->getJson('/api/beranda')
        ->assertStatus(409)
        ->assertJsonPath('code', 'PROGRAM_BELUM_ADA');

    $this->withToken($token)
        ->getJson('/api/rekap')
        ->assertStatus(409)
        ->assertJsonPath('code', 'PROGRAM_BELUM_ADA');
});

it('returns only active bahan_makanan with kelompok along with urt, tempat_makan, and status_realisasi on GET /api/referensi', function (): void {
    $this->seed(DevContentSeeder::class);

    $bahanNonaktif = BahanMakanan::query()->orderByDesc('bahan_makanan_id')->firstOrFail();
    $bahanNonaktif->update(['aktif' => false]);

    $bahanAktif = BahanMakanan::query()->where('aktif', true)->orderBy('bahan_makanan_id')->firstOrFail();
    $urtPertama = Urt::query()->orderBy('urt_id')->firstOrFail();

    KonversiUrt::query()->updateOrCreate(
        [
            'bahan_makanan_id' => $bahanAktif->bahan_makanan_id,
            'urt_id' => $urtPertama->urt_id,
        ],
        [
            'berat_gram' => 100,
        ],
    );

    ['token' => $token] = buatSiswaTerverifikasiViaTrigger('referensi@siswa.id');

    $res = $this->withToken($token)
        ->getJson('/api/referensi')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'bahan_makanan' => [
                    '*' => ['bahan_makanan_id', 'nama_bahan', 'kelompok', 'konversi_urt'],
                ],
                'urt' => [
                    '*' => ['urt_id', 'nama_urt'],
                ],
                'tempat_makan' => [
                    '*' => ['tempat_makan_id', 'kode', 'nama'],
                ],
                'status_realisasi' => [
                    '*' => ['status_realisasi_id', 'kode', 'nama'],
                ],
            ],
            'message',
        ]);

    $bahanIds = array_column($res->json('data.bahan_makanan'), 'bahan_makanan_id');

    expect($bahanIds)
        ->toContain($bahanAktif->bahan_makanan_id)
        ->not->toContain($bahanNonaktif->bahan_makanan_id);
});

it('returns beranda summary with week/day numbers, plan/food-record flags, and kepatuhan_mingguan_v stats', function (): void {
    $this->seed(DevContentSeeder::class);

    ['token' => $token, 'peserta' => $peserta] = buatSiswaTerverifikasiViaTrigger('beranda@siswa.id', 'Nadia Putri');

    $hariIni = Carbon::today();
    $besok = $hariIni->copy()->addDay();

    // Geser tanggal_mulai ke 8 hari lalu -> Minggu ke-2, Hari ke-9
    $peserta->update([
        'tanggal_mulai' => $hariIni->copy()->subDays(8)->toDateString(),
        'tanggal_selesai' => $hariIni->copy()->addDays(12)->toDateString(),
    ]);

    $this->withToken($token)
        ->getJson('/api/beranda')
        ->assertOk()
        ->assertJsonPath('data.nama', 'Nadia Putri')
        ->assertJsonPath('data.minggu_ke', 2)
        ->assertJsonPath('data.hari_ke', 9)
        ->assertJsonPath('data.rencana_besok_ada', false)
        ->assertJsonPath('data.food_record_hari_ini_ada', false)
        ->assertJsonPath('data.kepatuhan_minggu_berjalan.minggu_ke', 2)
        ->assertJsonPath('data.kepatuhan_minggu_berjalan.hari_berjalan', 2)
        ->assertJsonPath('data.kepatuhan_minggu_berjalan.hari_sesuai', 0);

    $tempatRumah = TempatMakan::query()->where('kode', 'RUMAH')->firstOrFail();
    $urt = Urt::query()->orderBy('urt_id')->firstOrFail();
    $bahan = BahanMakanan::query()->where('aktif', true)->firstOrFail();
    $statusSesuai = StatusRealisasi::query()->where('kode', 'SESUAI')->firstOrFail();

    // Buat rencana besok dan food record hari ini
    $this->withToken($token)->putJson('/api/planner/'.$besok->toDateString(), [
        'jam_rencana' => '06:15',
        'tempat_makan_id' => $tempatRumah->tempat_makan_id,
        'items' => [
            [
                'nama_makanan' => 'Roti Telur',
                'bahan_makanan_id' => $bahan->bahan_makanan_id,
                'urt_id' => $urt->urt_id,
                'jumlah_urt' => 2,
            ],
        ],
    ])->assertOk();

    auth()->forgetGuards();

    $this->withToken($token)->putJson('/api/food-record/'.$hariIni->toDateString(), [
        'tidak_sarapan' => false,
        'jam_makan' => '06:30',
        'tempat_makan_id' => $tempatRumah->tempat_makan_id,
        'items' => [
            [
                'nama_makanan' => 'Nasi Uduk Telur',
                'bahan_makanan_id' => $bahan->bahan_makanan_id,
                'urt_id' => $urt->urt_id,
                'jumlah_urt' => 1,
                'berat_gram' => 150,
                'status_realisasi_id' => $statusSesuai->status_realisasi_id,
            ],
        ],
    ])->assertOk();

    auth()->forgetGuards();

    $berandaAfter = $this->withToken($token)
        ->getJson('/api/beranda')
        ->assertOk()
        ->assertJsonPath('data.rencana_besok_ada', true)
        ->assertJsonPath('data.food_record_hari_ini_ada', true)
        ->assertJsonPath('data.kepatuhan_minggu_berjalan.hari_tercatat', 1)
        ->assertJsonPath('data.kepatuhan_minggu_berjalan.hari_sesuai', 1);

    expect((float) $berandaAfter->json('data.kepatuhan_minggu_berjalan.persen_kepatuhan'))->toBe(50.0);
});

it('upserts planner twice without duplicates and rejects past dates, out-of-range dates, or empty items', function (): void {
    $this->seed(DevContentSeeder::class);

    ['token' => $token, 'peserta' => $peserta] = buatSiswaTerverifikasiViaTrigger('planner@siswa.id');

    $hariIni = Carbon::today();
    $kemarin = $hariIni->copy()->subDay()->toDateString();
    $besok = $hariIni->copy()->addDay()->toDateString();
    $diLuarRentang = $peserta->tanggal_selesai->copy()->addDay()->toDateString();

    // Rentang program dimulai kemarin agar kita bisa menguji penolakan tanggal lewat yang masih di dalam program
    $peserta->update([
        'tanggal_mulai' => $kemarin,
    ]);

    $tempatRumah = TempatMakan::query()->where('kode', 'RUMAH')->firstOrFail();
    $tempatSekolah = TempatMakan::query()->where('kode', 'SEKOLAH')->firstOrFail();
    $urt = Urt::query()->orderBy('urt_id')->firstOrFail();
    $bahanList = BahanMakanan::query()->where('aktif', true)->orderBy('bahan_makanan_id')->limit(2)->get();

    // Belum ada rencana -> data null
    $this->withToken($token)
        ->getJson("/api/planner/{$besok}")
        ->assertOk()
        ->assertJsonPath('data', null);

    // Upsert ke-1 (2 item)
    $this->withToken($token)
        ->putJson("/api/planner/{$besok}", [
            'jam_rencana' => '06:15',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Nasi Putih',
                    'bahan_makanan_id' => $bahanList[0]->bahan_makanan_id,
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 1,
                ],
                [
                    'nama_makanan' => 'Telur Rebus',
                    'bahan_makanan_id' => $bahanList[1]->bahan_makanan_id,
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 1,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonCount(2, 'data.items');

    // Upsert ke-2 pada tanggal yang sama -> item diganti penuh tanpa duplikat header maupun detail lama
    $putKedua = $this->withToken($token)
        ->putJson("/api/planner/{$besok}", [
            'jam_rencana' => '06:45',
            'tempat_makan_id' => $tempatSekolah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Roti Gandum Pisang',
                    'bahan_makanan_id' => null,
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 2,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.jam_rencana', '06:45')
        ->assertJsonPath('data.tempat_makan_id', $tempatSekolah->tempat_makan_id)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.nama_makanan', 'Roti Gandum Pisang');

    $rencanaCount = RencanaSarapan::query()
        ->where('peserta_program_id', $peserta->peserta_program_id)
        ->where('tanggal_sarapan', $besok)
        ->count();

    $detailCount = RencanaSarapanDetail::query()
        ->where('rencana_sarapan_id', $putKedua->json('data.rencana_sarapan_id'))
        ->count();

    expect($rencanaCount)->toBe(1)
        ->and($detailCount)->toBe(1);

    // Tanggal sudah lewat -> 422
    $this->withToken($token)
        ->putJson("/api/planner/{$kemarin}", [
            'jam_rencana' => '06:30',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Bubur Ayam',
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 1,
                ],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tanggal']);

    // Tanggal di luar rentang program -> 422
    $this->withToken($token)
        ->putJson("/api/planner/{$diLuarRentang}", [
            'jam_rencana' => '06:30',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Bubur Ayam',
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 1,
                ],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tanggal']);

    // Items kosong -> 422
    $this->withToken($token)
        ->putJson("/api/planner/{$besok}", [
            'jam_rencana' => '06:30',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

it('prefills food record from planner, supports tidak_sarapan, and rejects future or out-of-range dates', function (): void {
    $this->seed(DevContentSeeder::class);

    ['token' => $token, 'peserta' => $peserta] = buatSiswaTerverifikasiViaTrigger('foodrecord@siswa.id');

    $hariIni = Carbon::today();
    $kemarin = $hariIni->copy()->subDay()->toDateString();
    $duaHariLalu = $hariIni->copy()->subDays(2)->toDateString();
    $besok = $hariIni->copy()->addDay()->toDateString();

    $peserta->update([
        'tanggal_mulai' => $hariIni->copy()->subDay()->toDateString(),
        'tanggal_selesai' => $hariIni->copy()->addDays(19)->toDateString(),
    ]);

    $tempatRumah = TempatMakan::query()->where('kode', 'RUMAH')->firstOrFail();
    $urt = Urt::query()->orderBy('urt_id')->firstOrFail();
    $bahan = BahanMakanan::query()->where('aktif', true)->orderBy('bahan_makanan_id')->firstOrFail();
    $statusSesuai = StatusRealisasi::query()->where('kode', 'SESUAI')->firstOrFail();

    KonversiUrt::query()->updateOrCreate(
        [
            'bahan_makanan_id' => $bahan->bahan_makanan_id,
            'urt_id' => $urt->urt_id,
        ],
        [
            'berat_gram' => 75,
        ],
    );

    // 1. Belum ada rencana maupun food record -> data null
    $this->withToken($token)
        ->getJson('/api/food-record/'.$hariIni->toDateString())
        ->assertOk()
        ->assertJsonPath('data', null);

    // 2. Buat rencana hari ini, lalu GET /api/food-record/{tanggal} -> prefill dari_rencana = true
    $plannerRes = $this->withToken($token)
        ->putJson('/api/planner/'.$hariIni->toDateString(), [
            'jam_rencana' => '06:20',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Nasi Telur Dadar',
                    'bahan_makanan_id' => $bahan->bahan_makanan_id,
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 2,
                ],
            ],
        ])
        ->assertOk();

    $rencanaId = (int) $plannerRes->json('data.rencana_sarapan_id');
    $rencanaDetailId = (int) $plannerRes->json('data.items.0.rencana_sarapan_detail_id');

    $prefillRes = $this->withToken($token)
        ->getJson('/api/food-record/'.$hariIni->toDateString())
        ->assertOk()
        ->assertJsonPath('data.dari_rencana', true)
        ->assertJsonPath('data.rencana_sarapan_id', $rencanaId)
        ->assertJsonPath('data.jam_makan', '06:20')
        ->assertJsonPath('data.items.0.rencana_sarapan_detail_id', $rencanaDetailId)
        ->assertJsonPath('data.items.0.status_realisasi.kode', 'SESUAI');

    expect((float) $prefillRes->json('data.items.0.berat_gram'))->toBe(150.0);

    // 3. Simpan food record aktual -> dari_rencana = false dan otomatis menautkan rencana_sarapan_id
    $savedRes = $this->withToken($token)
        ->putJson('/api/food-record/'.$hariIni->toDateString(), [
            'tidak_sarapan' => false,
            'jam_makan' => '06:25',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Nasi Telur Dadar',
                    'bahan_teks' => 'Nasi putih & telur ayam',
                    'bahan_makanan_id' => $bahan->bahan_makanan_id,
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 2,
                    'berat_gram' => 150,
                    'keterangan' => 'Habis semua',
                    'status_realisasi_id' => $statusSesuai->status_realisasi_id,
                    'rencana_sarapan_detail_id' => $rencanaDetailId,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.dari_rencana', false)
        ->assertJsonPath('data.rencana_sarapan_id', $rencanaId)
        ->assertJsonCount(1, 'data.items');

    // 4. tidak_sarapan = false tanpa items ditolak 422
    $this->withToken($token)
        ->putJson("/api/food-record/{$kemarin}", [
            'tidak_sarapan' => false,
            'items' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items']);

    // 5. tidak_sarapan = true membolehkan items kosong & menghapus detail lama pada upsert
    $this->withToken($token)
        ->putJson('/api/food-record/'.$hariIni->toDateString(), [
            'tidak_sarapan' => true,
            'items' => [],
        ])
        ->assertOk()
        ->assertJsonPath('data.tidak_sarapan', true)
        ->assertJsonCount(0, 'data.items');

    $detailTersisa = FoodRecordDetail::query()
        ->where('food_record_id', $savedRes->json('data.food_record_id'))
        ->count();
    expect($detailTersisa)->toBe(0);

    // 6. Tanggal masa depan & di luar rentang program ditolak 422
    $this->withToken($token)
        ->putJson("/api/food-record/{$besok}", [
            'tidak_sarapan' => true,
            'items' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tanggal']);

    $this->withToken($token)
        ->putJson("/api/food-record/{$duaHariLalu}", [
            'tidak_sarapan' => true,
            'items' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tanggal']);
});

it('isolates data strictly so student A cannot read, overwrite, or reference student B data', function (): void {
    $this->seed(DevContentSeeder::class);

    ['token' => $tokenA, 'peserta' => $pesertaA] = buatSiswaTerverifikasiViaTrigger('siswa.a@siswa.id', 'Siswa A');
    ['token' => $tokenB, 'peserta' => $pesertaB] = buatSiswaTerverifikasiViaTrigger('siswa.b@siswa.id', 'Siswa B');

    $tanggalUji = Carbon::today()->toDateString();
    $tempatRumah = TempatMakan::query()->where('kode', 'RUMAH')->firstOrFail();
    $urt = Urt::query()->orderBy('urt_id')->firstOrFail();
    $bahan = BahanMakanan::query()->where('aktif', true)->firstOrFail();
    $statusSesuai = StatusRealisasi::query()->where('kode', 'SESUAI')->firstOrFail();

    // Siswa B membuat rencana & food record pada hari ini
    $plannerB = $this->withToken($tokenB)
        ->putJson("/api/planner/{$tanggalUji}", [
            'jam_rencana' => '06:00',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Menu Rahasia Siswa B',
                    'bahan_makanan_id' => $bahan->bahan_makanan_id,
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 1,
                ],
            ],
        ])
        ->assertOk();

    $detailRencanaIdMilikB = (int) $plannerB->json('data.items.0.rencana_sarapan_detail_id');

    $this->withToken($tokenB)
        ->putJson("/api/food-record/{$tanggalUji}", [
            'tidak_sarapan' => false,
            'jam_makan' => '06:05',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Catatan Sarapan Siswa B',
                    'bahan_makanan_id' => $bahan->bahan_makanan_id,
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 1,
                    'berat_gram' => 120,
                    'status_realisasi_id' => $statusSesuai->status_realisasi_id,
                    'rencana_sarapan_detail_id' => $detailRencanaIdMilikB,
                ],
            ],
        ])
        ->assertOk();

    auth()->forgetGuards();

    // Siswa A membaca tanggal yang sama -> tidak boleh melihat data Siswa B (harus null)
    $this->withToken($tokenA)
        ->getJson("/api/planner/{$tanggalUji}")
        ->assertOk()
        ->assertJsonPath('data', null);

    $this->withToken($tokenA)
        ->getJson("/api/food-record/{$tanggalUji}")
        ->assertOk()
        ->assertJsonPath('data', null);

    // Siswa A mencoba mengirim rencana_sarapan_detail_id milik Siswa B -> ditolak 422
    $this->withToken($tokenA)
        ->putJson("/api/food-record/{$tanggalUji}", [
            'tidak_sarapan' => false,
            'jam_makan' => '06:30',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Percobaan Pakai ID Siswa B',
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 1,
                    'status_realisasi_id' => $statusSesuai->status_realisasi_id,
                    'rencana_sarapan_detail_id' => $detailRencanaIdMilikB,
                ],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.rencana_sarapan_detail_id']);

    // Siswa A menyimpan datanya sendiri -> data Siswa B tetap utuh dan tidak tertimpa
    $this->withToken($tokenA)
        ->putJson("/api/planner/{$tanggalUji}", [
            'jam_rencana' => '07:00',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Menu Milik Siswa A',
                    'urt_id' => $urt->urt_id,
                    'jumlah_urt' => 1,
                ],
            ],
        ])
        ->assertOk();

    $rencanaBMasihUtuh = RencanaSarapan::query()
        ->with('rencanaSarapanDetail')
        ->where('peserta_program_id', $pesertaB->peserta_program_id)
        ->where('tanggal_sarapan', $tanggalUji)
        ->firstOrFail();

    $foodRecordBMasihUtuh = FoodRecord::query()
        ->with('foodRecordDetail')
        ->where('peserta_program_id', $pesertaB->peserta_program_id)
        ->where('tanggal', $tanggalUji)
        ->firstOrFail();

    expect($rencanaBMasihUtuh->rencanaSarapanDetail->first()->nama_makanan)->toBe('Menu Rahasia Siswa B')
        ->and($foodRecordBMasihUtuh->foodRecordDetail->first()->nama_makanan)->toBe('Catatan Sarapan Siswa B')
        ->and(RencanaSarapan::query()->where('peserta_program_id', $pesertaA->peserta_program_id)->count())->toBe(1);
});

it('builds GET /api/rekap directly from kepatuhan_harian_v, kepatuhan_mingguan_v, and skor_pengetahuan_v', function (): void {
    $this->seed(DevContentSeeder::class);

    ['token' => $token, 'peserta' => $peserta] = buatSiswaTerverifikasiViaTrigger('rekap@siswa.id', 'Rizky Pratama', 60.0);

    $hariIni = Carbon::today();
    $mulai = $hariIni->copy()->subDays(3);

    // 4 hari berjalan di Minggu 1:
    // H0 (mulai): SESUAI
    // H1: SEBAGIAN (1 SESUAI + 1 DIUBAH)
    // H2: TIDAK_SESUAI (tidak_sarapan = true)
    // H3 (hariIni): BELUM_DICATAT
    $peserta->update([
        'tanggal_mulai' => $mulai->toDateString(),
        'tanggal_selesai' => $mulai->copy()->addDays(20)->toDateString(),
    ]);

    $tempatRumah = TempatMakan::query()->where('kode', 'RUMAH')->firstOrFail();
    $urt = Urt::query()->orderBy('urt_id')->firstOrFail();
    $statusSesuai = StatusRealisasi::query()->where('kode', 'SESUAI')->firstOrFail();
    $statusDiubah = StatusRealisasi::query()->where('kode', 'DIUBAH')->firstOrFail();

    // H0: SESUAI
    $this->withToken($token)->putJson('/api/food-record/'.$mulai->toDateString(), [
        'tidak_sarapan' => false,
        'jam_makan' => '06:15',
        'tempat_makan_id' => $tempatRumah->tempat_makan_id,
        'items' => [
            [
                'nama_makanan' => 'Nasi & Telur',
                'urt_id' => $urt->urt_id,
                'jumlah_urt' => 1,
                'status_realisasi_id' => $statusSesuai->status_realisasi_id,
            ],
        ],
    ])->assertOk();

    // H1: SEBAGIAN
    $this->withToken($token)->putJson('/api/food-record/'.$mulai->copy()->addDay()->toDateString(), [
        'tidak_sarapan' => false,
        'jam_makan' => '06:30',
        'tempat_makan_id' => $tempatRumah->tempat_makan_id,
        'items' => [
            [
                'nama_makanan' => 'Nasi Putih',
                'urt_id' => $urt->urt_id,
                'jumlah_urt' => 1,
                'status_realisasi_id' => $statusSesuai->status_realisasi_id,
            ],
            [
                'nama_makanan' => 'Gorengan Pengganti',
                'urt_id' => $urt->urt_id,
                'jumlah_urt' => 2,
                'status_realisasi_id' => $statusDiubah->status_realisasi_id,
            ],
        ],
    ])->assertOk();

    // H2: TIDAK_SESUAI (tidak_sarapan = true)
    $this->withToken($token)->putJson('/api/food-record/'.$mulai->copy()->addDays(2)->toDateString(), [
        'tidak_sarapan' => true,
        'items' => [],
    ])->assertOk();

    $rekapRes = $this->withToken($token)
        ->getJson('/api/rekap')
        ->assertOk()
        ->assertJsonCount(3, 'data.minggu');

    $minggu1 = $rekapRes->json('data.minggu.0');
    $statusHarian = array_column($minggu1['hari'], 'status_kepatuhan');

    expect($minggu1['minggu_ke'])->toBe(1)
        ->and($statusHarian)->toBe(['SESUAI', 'SEBAGIAN', 'TIDAK_SESUAI', 'BELUM_DICATAT'])
        ->and($minggu1['ringkasan']['hari_berjalan'])->toBe(4)
        ->and($minggu1['ringkasan']['hari_tercatat'])->toBe(3)
        ->and($minggu1['ringkasan']['hari_sesuai'])->toBe(1)
        ->and((float) $minggu1['ringkasan']['persen_kepatuhan'])->toBe(25.0)
        ->and((float) $rekapRes->json('data.skor_pengetahuan.skor_pretest'))->toBe(60.0)
        ->and($rekapRes->json('data.skor_pengetahuan.skor_posttest'))->toBeNull();
});

it('blocks posttest before 21 days with 403 POSTTEST_BELUM_DIBUKA and grades without benar once opened', function (): void {
    $this->seed(DevContentSeeder::class);

    ['token' => $token, 'peserta' => $peserta] = buatSiswaTerverifikasiViaTrigger('posttest@siswa.id', 'Dinda Lestari', 60.0);

    // Hari ke-20 sejak tanggal_mulai (belum mencapai tanggal_mulai + 21 hari) -> 403 POSTTEST_BELUM_DIBUKA
    $peserta->update([
        'tanggal_mulai' => Carbon::today()->subDays(20)->toDateString(),
        'tanggal_selesai' => Carbon::today()->toDateString(),
    ]);

    $this->withToken($token)
        ->getJson('/api/posttest')
        ->assertStatus(403)
        ->assertJsonPath('code', 'POSTTEST_BELUM_DIBUKA');

    $this->withToken($token)
        ->postJson('/api/posttest/submit', ['jawaban' => []])
        ->assertStatus(403)
        ->assertJsonPath('code', 'POSTTEST_BELUM_DIBUKA');

    // Sudah mencapai tanggal_mulai + 21 hari -> Posttest terbuka
    $peserta->update([
        'tanggal_mulai' => Carbon::today()->subDays(21)->toDateString(),
        'tanggal_selesai' => Carbon::today()->subDay()->toDateString(),
    ]);

    $posttestRes = $this->withToken($token)
        ->getJson('/api/posttest')
        ->assertOk()
        ->assertJsonCount(5, 'data.soal');

    foreach ($posttestRes->json('data.soal') as $soalItem) {
        foreach ($soalItem['opsi'] as $opsiItem) {
            expect($opsiItem)->not->toHaveKey('benar');
        }
    }

    $kuesionerPost = Kuesioner::query()
        ->with(['soal' => fn ($q) => $q->orderBy('nomor')->with('opsiJawaban')])
        ->where('jenis', 'POSTTEST')
        ->where('aktif', true)
        ->firstOrFail();

    // Semua 5 soal dijawab benar -> skor 100
    $jawabanBenarSemua = $kuesionerPost->soal->map(function ($soal): array {
        $opsiBenar = $soal->opsiJawaban->first(fn (OpsiJawaban $o) => (bool) $o->getAttribute('benar'));

        return [
            'soal_id' => $soal->soal_id,
            'opsi_jawaban_id' => $opsiBenar->opsi_jawaban_id,
        ];
    })->values()->all();

    $submitRes = $this->withToken($token)
        ->postJson('/api/posttest/submit', ['jawaban' => $jawabanBenarSemua])
        ->assertOk()
        ->assertJsonPath('data.jumlah_soal', 5)
        ->assertJsonPath('data.jumlah_benar', 5);

    expect((float) $submitRes->json('data.skor'))->toBe(100.0);

    // Kirim posttest kedua kali -> 409 POSTTEST_SUDAH_DIKIRIM
    $this->withToken($token)
        ->postJson('/api/posttest/submit', ['jawaban' => $jawabanBenarSemua])
        ->assertStatus(409)
        ->assertJsonPath('code', 'POSTTEST_SUDAH_DIKIRIM');

    // Skor posttest & selisih kini muncul di GET /api/rekap dari skor_pengetahuan_v
    $rekapRes = $this->withToken($token)
        ->getJson('/api/rekap')
        ->assertOk();

    expect((float) $rekapRes->json('data.skor_pengetahuan.skor_pretest'))->toBe(60.0)
        ->and((float) $rekapRes->json('data.skor_pengetahuan.skor_posttest'))->toBe(100.0)
        ->and((float) $rekapRes->json('data.skor_pengetahuan.selisih'))->toBe(40.0);
});
