<?php

use App\Filament\Pages\DataSiswa;
use App\Filament\Pages\DetailSiswa;
use App\Filament\Pages\Laporan;
use App\Filament\Pages\VerifikasiSqffq;
use App\Models\BahanMakanan;
use App\Models\Frekuensi;
use App\Models\Kelas;
use App\Models\Kuesioner;
use App\Models\MateriEdukasi;
use App\Models\OpsiJawaban;
use App\Models\Pengguna;
use App\Models\PesertaProgram;
use App\Models\Program;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\StatusRealisasi;
use App\Models\TempatMakan;
use App\Models\Urt;
use Livewire\Livewire;

it('completes the full student onboarding, SQ-FFQ verification, and daily program flow via DB triggers', function () {
    $kelas = Kelas::query()->create([
        'nama_kelas' => '8A',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $program = Program::query()->create([
        'nama_program' => 'Program Sarapan Sehat 3 Minggu',
        'tanggal_mulai' => now()->subDays(15)->toDateString(),
        'durasi_minggu' => 3,
        'aktif' => true,
    ]);

    $pretest = Kuesioner::query()->where('jenis', 'PRETEST')->firstOrFail();
    $soal1 = Soal::query()->create([
        'kuesioner_id' => $pretest->kuesioner_id,
        'nomor' => 1,
        'teks_soal' => '[DUMMY] Soal Pretest 1?',
    ]);
    $opsi1Benar = OpsiJawaban::query()->create([
        'soal_id' => $soal1->soal_id,
        'kode_opsi' => 'A',
        'teks_opsi' => 'Jawaban Benar',
        'benar' => true,
    ]);
    OpsiJawaban::query()->create([
        'soal_id' => $soal1->soal_id,
        'kode_opsi' => 'B',
        'teks_opsi' => 'Jawaban Salah',
        'benar' => false,
    ]);

    $materiWajib = MateriEdukasi::query()->create([
        'urutan' => 1,
        'judul' => '[DUMMY] Materi Wajib 1',
        'ringkasan' => 'Ringkasan materi wajib',
        'konten' => 'Isi materi wajib',
        'wajib' => true,
        'aktif' => true,
    ]);

    // 1. Daftar Kelas publik & Registrasi Siswa (Status: PRETEST)
    $this->getJson('/api/kelas')
        ->assertSuccessful()
        ->assertJsonFragment(['nama_kelas' => '8A']);

    $regResponse = $this->postJson('/api/auth/register', [
        'nama_lengkap' => 'Nadia Putri',
        'email' => 'nadia@siswa.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'kelas_id' => $kelas->kelas_id,
        'jenis_kelamin' => 'P',
    ])->assertCreated();

    $token = $regResponse->json('data.token');
    expect($token)->not->toBeEmpty()
        ->and($regResponse->json('data.user.status_akun.kode'))->toBe('PRETEST');

    // 2. Endpoint terverifikasi harus 403 sebelum TERVERIFIKASI
    $this->withToken($token)
        ->getJson('/api/beranda')
        ->assertForbidden()
        ->assertJson(['code' => 'STATUS_BELUM_TERVERIFIKASI']);

    // 3. Ambil soal Pretest (pastikan kolom `benar` tidak bocor) & Submit Pretest -> Trigger pindah ke EDUKASI
    $pretestRes = $this->withToken($token)
        ->getJson('/api/pretest')
        ->assertSuccessful();

    $opsiPertama = $pretestRes->json('data.soal.0.opsi.0');
    expect($opsiPertama)->not->toHaveKey('benar');

    $submitPretestRes = $this->withToken($token)
        ->postJson('/api/pretest/submit', [
            'jawaban' => [
                [
                    'soal_id' => $soal1->soal_id,
                    'opsi_jawaban_id' => $opsi1Benar->opsi_jawaban_id,
                ],
            ],
        ])->assertSuccessful();

    expect((float) $submitPretestRes->json('data.skor'))->toBe(100.0)
        ->and($submitPretestRes->json('data.status_akun.kode'))->toBe('EDUKASI');

    // 4. Baca semua materi wajib -> Trigger pindah ke MENUNGGU_VERIFIKASI
    $this->withToken($token)
        ->getJson('/api/materi')
        ->assertSuccessful()
        ->assertJsonFragment(['judul' => '[DUMMY] Materi Wajib 1']);

    $selesaiMateriRes = $this->withToken($token)
        ->postJson('/api/materi/'.$materiWajib->materi_edukasi_id.'/selesai')
        ->assertSuccessful();

    expect($selesaiMateriRes->json('data.status_akun.kode'))->toBe('MENUNGGU_VERIFIKASI');

    // 5. Petugas menyelesaikan SQ-FFQ di Web Admin -> Trigger pindah ke TERVERIFIKASI & daftar peserta_program_t
    $petugas = Pengguna::factory()->petugas()->create();
    $penggunaSiswa = Pengguna::query()->where('email', 'nadia@siswa.id')->firstOrFail();
    $siswa = Siswa::query()->where('pengguna_id', $penggunaSiswa->pengguna_id)->firstOrFail();
    $bahanPertama = BahanMakanan::query()->where('aktif', true)->orderBy('bahan_makanan_id')->firstOrFail();
    $frekuensiSering = Frekuensi::query()->where('kode', '1_HARI')->firstOrFail();

    $this->actingAs($petugas);

    Livewire::withQueryParams(['siswa_id' => $siswa->siswa_id])
        ->test(VerifikasiSqffq::class)
        ->assertSee('Nadia Putri')
        ->set("frekuensiPilihan.{$bahanPertama->bahan_makanan_id}", $frekuensiSering->frekuensi_id)
        ->set("porsiGram.{$bahanPertama->bahan_makanan_id}", '120')
        ->call('simpanDraf')
        ->call('selesaikanSqffq');

    $siswa->refresh()->load('statusAkun');
    expect($siswa->statusAkun->kode)->toBe('TERVERIFIKASI');

    $peserta = PesertaProgram::query()->where('siswa_id', $siswa->siswa_id)->first();
    expect($peserta)->not->toBeNull()
        ->and($peserta->program_id)->toBe($program->program_id);

    auth()->forgetGuards();

    // 6. Setelah TERVERIFIKASI, siswa bisa akses Beranda, Planner, Food Record (prefill dari rencana), Rekap, & Posttest
    $this->withToken($token)
        ->getJson('/api/beranda')
        ->assertSuccessful()
        ->assertJsonPath('data.minggu_ke', 1);

    $tanggalUji = now()->toDateString();
    $tempatRumah = TempatMakan::query()->where('kode', 'RUMAH')->firstOrFail();
    $urtPiring = Urt::query()->orderBy('urt_id')->firstOrFail();
    $statusSesuai = StatusRealisasi::query()->where('kode', 'SESUAI')->firstOrFail();

    $this->withToken($token)
        ->putJson("/api/planner/{$tanggalUji}", [
            'jam_rencana' => '06:30',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'nama_makanan' => 'Nasi Goreng Telur',
                    'bahan_makanan_id' => $bahanPertama->bahan_makanan_id,
                    'urt_id' => $urtPiring->urt_id,
                    'jumlah_urt' => 1,
                ],
            ],
        ])->assertSuccessful();

    // GET food-record sebelum diisi harus mengembalikan prefill dari rencana
    $prefillRes = $this->withToken($token)
        ->getJson("/api/food-record/{$tanggalUji}")
        ->assertSuccessful();

    expect($prefillRes->json('data.dari_rencana'))->toBeTrue()
        ->and($prefillRes->json('data.items.0.nama_makanan'))->toBe('Nasi Goreng Telur');

    // Simpan Food Record aktual
    $this->withToken($token)
        ->putJson("/api/food-record/{$tanggalUji}", [
            'tidak_sarapan' => false,
            'jam_makan' => '06:35',
            'tempat_makan_id' => $tempatRumah->tempat_makan_id,
            'items' => [
                [
                    'status_realisasi_id' => $statusSesuai->status_realisasi_id,
                    'nama_makanan' => 'Nasi Goreng Telur',
                    'bahan_makanan_id' => $bahanPertama->bahan_makanan_id,
                    'urt_id' => $urtPiring->urt_id,
                    'jumlah_urt' => 1,
                    'berat_gram' => 150,
                    'keterangan' => 'Habis 1 porsi',
                ],
            ],
        ])->assertSuccessful();

    // Rekap Kepatuhan & Skor
    $rekapRes = $this->withToken($token)
        ->getJson('/api/rekap')
        ->assertSuccessful();

    expect((float) $rekapRes->json('data.skor_pengetahuan.skor_pretest'))->toBe(100.0);

    // Simulasikan peserta sudah melewati 21 hari program agar Posttest terbuka
    $peserta->update([
        'tanggal_mulai' => now()->subDays(21)->toDateString(),
    ]);

    $posttest = Kuesioner::query()->where('jenis', 'POSTTEST')->firstOrFail();
    $soalPost = Soal::query()->create([
        'kuesioner_id' => $posttest->kuesioner_id,
        'nomor' => 1,
        'teks_soal' => '[DUMMY] Soal Posttest 1?',
    ]);
    $opsiPostBenar = OpsiJawaban::query()->create([
        'soal_id' => $soalPost->soal_id,
        'kode_opsi' => 'A',
        'teks_opsi' => 'Benar Posttest',
        'benar' => true,
    ]);

    $this->withToken($token)
        ->getJson('/api/posttest')
        ->assertSuccessful();

    $postSubmitRes = $this->withToken($token)
        ->postJson('/api/posttest/submit', [
            'jawaban' => [
                [
                    'soal_id' => $soalPost->soal_id,
                    'opsi_jawaban_id' => $opsiPostBenar->opsi_jawaban_id,
                ],
            ],
        ])->assertSuccessful();

    expect((float) $postSubmitRes->json('data.skor'))->toBe(100.0);
});

it('renders Web Admin DataSiswa, DetailSiswa, and Laporan pages cleanly', function () {
    $admin = Pengguna::factory()->admin()->create();
    $kelas = Kelas::query()->create([
        'nama_kelas' => '8B',
        'tahun_ajaran' => '2025/2026',
        'aktif' => true,
    ]);

    $this->actingAs($admin);

    // Tambah siswa dari halaman DataSiswa
    Livewire::test(DataSiswa::class)
        ->call('bukaModalTambah')
        ->set('namaLengkapBaru', 'Budi Santoso')
        ->set('emailBaru', 'budi@siswa.id')
        ->set('kataSandiBaru', 'rahasia123')
        ->set('kelasIdBaru', $kelas->kelas_id)
        ->set('jenisKelaminBaru', 'L')
        ->call('simpanSiswaBaru')
        ->assertHasNoErrors()
        ->assertSee('Budi Santoso');

    $penggunaBudi = Pengguna::query()->where('email', 'budi@siswa.id')->firstOrFail();
    $siswa = Siswa::query()->where('pengguna_id', $penggunaBudi->pengguna_id)->firstOrFail();

    // Halaman DetailSiswa & perpindahan tab
    Livewire::withQueryParams(['siswa_id' => $siswa->siswa_id])
        ->test(DetailSiswa::class)
        ->assertSee('Budi Santoso')
        ->call('pilihTab', 'sqffq')
        ->assertSet('tabAktif', 'sqffq')
        ->call('pilihTab', 'food_record')
        ->assertSet('tabAktif', 'food_record')
        ->call('pilihTab', 'pengetahuan')
        ->assertSet('tabAktif', 'pengetahuan');

    // Halaman Laporan
    Livewire::test(Laporan::class)
        ->assertSee('1. Pengetahuan gizi: pretest vs post test')
        ->assertSee('2. Kepatuhan rencana sarapan per minggu')
        ->assertSee('3. Pola konsumsi (SQ-FFQ)')
        ->assertSee('4. Tempat sarapan (food record)')
        ->assertSee('5. Rekap skor konsumsi pangan (SQ-FFQ)');
});
