<?php

use App\Models\Kelas;
use App\Models\Kuesioner;
use App\Models\MateriEdukasi;
use App\Models\OpsiJawaban;
use App\Models\Pengguna;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\StatusAkun;
use Database\Seeders\DevContentSeeder;

it('completes the full onboarding flow via DB triggers: register -> pretest -> EDUKASI -> baca semua materi wajib -> MENUNGGU_VERIFIKASI', function (): void {
    $this->seed(DevContentSeeder::class);

    $kelas = Kelas::query()->where('nama_kelas', '8A')->firstOrFail();

    // 1. Register -> status PRETEST
    $registerRes = $this->postJson('/api/auth/register', [
        'nama_lengkap' => 'Alya Rahmawati',
        'email' => 'alya@siswa.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'kelas_id' => $kelas->kelas_id,
        'jenis_kelamin' => 'P',
    ])->assertCreated();

    $token = $registerRes->json('data.token');
    expect($registerRes->json('data.user.status_akun.kode'))->toBe('PRETEST');

    // 2. GET /api/pretest -> 5 soal urut nomor, tiap soal 4 opsi, TANPA kolom benar
    $pretestRes = $this->withToken($token)
        ->getJson('/api/pretest')
        ->assertOk()
        ->assertJsonCount(5, 'data.soal');

    $daftarSoalResponse = $pretestRes->json('data.soal');
    expect(array_column($daftarSoalResponse, 'nomor'))->toBe([1, 2, 3, 4, 5]);

    foreach ($daftarSoalResponse as $soalItem) {
        expect($soalItem)->toHaveKeys(['soal_id', 'nomor', 'teks_soal', 'opsi'])
            ->and($soalItem['opsi'])->toHaveCount(4);

        foreach ($soalItem['opsi'] as $opsiItem) {
            expect($opsiItem)->toHaveKeys(['opsi_jawaban_id', 'kode_opsi', 'teks_opsi'])
                ->and($opsiItem)->not->toHaveKey('benar');
        }
    }

    // Siapkan 4 jawaban benar + 1 jawaban salah -> skor 80
    $kuesionerPretest = Kuesioner::query()
        ->with(['soal' => fn ($q) => $q->orderBy('nomor')->with('opsiJawaban')])
        ->where('jenis', 'PRETEST')
        ->where('aktif', true)
        ->firstOrFail();

    $payloadJawaban = [];
    foreach ($kuesionerPretest->soal->values() as $idx => $soal) {
        $opsiDipilih = $idx < 4
            ? $soal->opsiJawaban->first(fn (OpsiJawaban $o) => (bool) $o->getAttribute('benar'))
            : $soal->opsiJawaban->first(fn (OpsiJawaban $o) => ! (bool) $o->getAttribute('benar'));

        $payloadJawaban[] = [
            'soal_id' => $soal->soal_id,
            'opsi_jawaban_id' => $opsiDipilih->opsi_jawaban_id,
        ];
    }

    // 3. POST /api/pretest/submit -> trigger DB pindahkan status ke EDUKASI
    $submitRes = $this->withToken($token)
        ->postJson('/api/pretest/submit', [
            'jawaban' => $payloadJawaban,
        ])
        ->assertOk()
        ->assertJsonPath('data.jumlah_soal', 5)
        ->assertJsonPath('data.jumlah_benar', 4)
        ->assertJsonPath('data.status_akun.kode', 'EDUKASI');

    expect((float) $submitRes->json('data.skor'))->toBe(80.0);

    // 4. GET /api/materi -> 3 materi (2 wajib, 1 opsional), meta memuat total_wajib, dibaca_wajib, status_akun
    $materiListRes = $this->withToken($token)
        ->getJson('/api/materi')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.total_wajib', 2)
        ->assertJsonPath('meta.dibaca_wajib', 0)
        ->assertJsonPath('meta.status_akun.kode', 'EDUKASI');

    $materiItems = collect($materiListRes->json('data'));
    $materiWajibIds = $materiItems->where('wajib', true)->pluck('materi_edukasi_id')->values();
    $materiOpsionalId = $materiItems->firstWhere('wajib', false)['materi_edukasi_id'];

    expect($materiWajibIds)->toHaveCount(2);

    // 5. GET /api/materi/{id} mengembalikan detail termasuk konten; materi tidak aktif -> 404
    $this->withToken($token)
        ->getJson('/api/materi/'.$materiWajibIds[0])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'materi_edukasi_id',
                'judul',
                'ringkasan',
                'konten',
                'urutan',
                'wajib',
                'selesai',
            ],
            'message',
        ]);

    $materiNonaktif = MateriEdukasi::query()->create([
        'judul' => '[DUMMY] Materi Nonaktif',
        'ringkasan' => 'Tidak aktif',
        'konten' => 'Konten tersembunyi',
        'urutan' => 99,
        'wajib' => false,
        'aktif' => false,
    ]);

    $this->withToken($token)
        ->getJson('/api/materi/'.$materiNonaktif->materi_edukasi_id)
        ->assertNotFound();

    // 6. Baca materi wajib ke-1 (idempotent dipanggil 2x) + materi opsional -> masih EDUKASI
    $this->withToken($token)
        ->postJson('/api/materi/'.$materiWajibIds[0].'/selesai')
        ->assertOk()
        ->assertJsonPath('data.total_wajib', 2)
        ->assertJsonPath('data.dibaca_wajib', 1)
        ->assertJsonPath('data.status_akun.kode', 'EDUKASI');

    $this->withToken($token)
        ->postJson('/api/materi/'.$materiWajibIds[0].'/selesai')
        ->assertOk()
        ->assertJsonPath('data.dibaca_wajib', 1)
        ->assertJsonPath('data.status_akun.kode', 'EDUKASI');

    $this->withToken($token)
        ->postJson('/api/materi/'.$materiOpsionalId.'/selesai')
        ->assertOk()
        ->assertJsonPath('data.dibaca_wajib', 1)
        ->assertJsonPath('data.status_akun.kode', 'EDUKASI');

    // 7. Baca materi wajib ke-2 -> trigger DB otomatis pindahkan status ke MENUNGGU_VERIFIKASI
    $this->withToken($token)
        ->postJson('/api/materi/'.$materiWajibIds[1].'/selesai')
        ->assertOk()
        ->assertJsonPath('data.total_wajib', 2)
        ->assertJsonPath('data.dibaca_wajib', 2)
        ->assertJsonPath('data.status_akun.kode', 'MENUNGGU_VERIFIKASI');

    // 8. GET /api/progres-edukasi membaca dari progres_edukasi_v
    $this->withToken($token)
        ->getJson('/api/progres-edukasi')
        ->assertOk()
        ->assertJsonPath('data.total_wajib', 2)
        ->assertJsonPath('data.dibaca_wajib', 2)
        ->assertJsonPath('data.selesai', true)
        ->assertJsonPath('data.status_akun.kode', 'MENUNGGU_VERIFIKASI');
});

it('returns 409 Conflict when pretest is submitted twice and 403 STATUS_TIDAK_SESUAI when EDUKASI student calls GET /api/pretest', function (): void {
    $this->seed(DevContentSeeder::class);

    $kelas = Kelas::query()->where('nama_kelas', '8A')->firstOrFail();
    $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();

    $akun = Pengguna::factory()->siswa()->create();
    Siswa::query()->create([
        'pengguna_id' => $akun->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusPretest->status_akun_id,
    ]);
    $token = $akun->createToken('mobile')->plainTextToken;

    $kuesioner = Kuesioner::query()
        ->with('soal.opsiJawaban')
        ->where('jenis', 'PRETEST')
        ->where('aktif', true)
        ->firstOrFail();

    $jawabanValid = $kuesioner->soal->map(fn (Soal $soal): array => [
        'soal_id' => $soal->soal_id,
        'opsi_jawaban_id' => $soal->opsiJawaban->first()->opsi_jawaban_id,
    ])->values()->all();

    // Kirim pertama kali -> 200 OK & status pindah ke EDUKASI
    $this->withToken($token)
        ->postJson('/api/pretest/submit', ['jawaban' => $jawabanValid])
        ->assertOk()
        ->assertJsonPath('data.status_akun.kode', 'EDUKASI');

    // Kirim kedua kali -> 409 Conflict
    $this->withToken($token)
        ->postJson('/api/pretest/submit', ['jawaban' => $jawabanValid])
        ->assertConflict()
        ->assertJsonPath('code', 'PRETEST_SUDAH_DIKIRIM');

    // Siswa berstatus EDUKASI memanggil GET /api/pretest -> 403 STATUS_TIDAK_SESUAI
    $this->withToken($token)
        ->getJson('/api/pretest')
        ->assertForbidden()
        ->assertJson([
            'code' => 'STATUS_TIDAK_SESUAI',
            'status_akun' => 'EDUKASI',
        ]);
});

it('rejects pretest submission with 422 when an option belongs to another question, answers are incomplete, or duplicate questions are sent', function (): void {
    $this->seed(DevContentSeeder::class);

    $kelas = Kelas::query()->where('nama_kelas', '8A')->firstOrFail();
    $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();

    $akun = Pengguna::factory()->siswa()->create();
    Siswa::query()->create([
        'pengguna_id' => $akun->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusPretest->status_akun_id,
    ]);
    $token = $akun->createToken('mobile')->plainTextToken;

    $soalList = Soal::query()
        ->with('opsiJawaban')
        ->whereHas('kuesioner', fn ($q) => $q->where('jenis', 'PRETEST')->where('aktif', true))
        ->orderBy('nomor')
        ->get();

    // 1. Opsi milik soal ke-2 dipakai untuk menjawab soal ke-1 -> 422
    $jawabanOpsiSilang = $soalList->map(fn (Soal $soal): array => [
        'soal_id' => $soal->soal_id,
        'opsi_jawaban_id' => $soal->opsiJawaban->first()->opsi_jawaban_id,
    ])->values()->all();

    $jawabanOpsiSilang[0]['opsi_jawaban_id'] = $soalList[1]->opsiJawaban->first()->opsi_jawaban_id;

    $this->withToken($token)
        ->postJson('/api/pretest/submit', ['jawaban' => $jawabanOpsiSilang])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['jawaban.0.opsi_jawaban_id']);

    // 2. Jawaban belum lengkap (hanya 2 dari 5 soal) -> 422
    $this->withToken($token)
        ->postJson('/api/pretest/submit', [
            'jawaban' => array_slice($jawabanOpsiSilang, 1, 2),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['jawaban']);

    // 3. Soal ganda -> 422
    $jawabanGanda = $soalList->map(fn (Soal $soal): array => [
        'soal_id' => $soal->soal_id,
        'opsi_jawaban_id' => $soal->opsiJawaban->first()->opsi_jawaban_id,
    ])->values()->all();
    $jawabanGanda[1]['soal_id'] = $jawabanGanda[0]['soal_id'];

    $this->withToken($token)
        ->postJson('/api/pretest/submit', ['jawaban' => $jawabanGanda])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['jawaban.1.soal_id']);
});

it('returns 422 with a clear message when there are no active pretest questions', function (): void {
    $kelas = Kelas::query()->create([
        'nama_kelas' => '8A',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);
    $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();

    $akun = Pengguna::factory()->siswa()->create();
    Siswa::query()->create([
        'pengguna_id' => $akun->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusPretest->status_akun_id,
    ]);
    $token = $akun->createToken('mobile')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/pretest/submit', [
            'jawaban' => [
                ['soal_id' => 1, 'opsi_jawaban_id' => 1],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Tidak ada soal pretest aktif yang tersedia.');
});
