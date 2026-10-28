<?php

use App\Filament\Resources\BahanMakananResource\Pages\CreateBahanMakanan;
use App\Filament\Resources\FrekuensiResource\Pages\CreateFrekuensi;
use App\Filament\Resources\KelasResource\Pages\CreateKelas;
use App\Filament\Resources\KelasResource\Pages\ListKelas;
use App\Filament\Resources\KelompokBahanResource\Pages\CreateKelompokBahan;
use App\Filament\Resources\KuesionerResource\Pages\CreateKuesioner;
use App\Filament\Resources\MateriEdukasiResource\Pages\CreateMateriEdukasi;
use App\Filament\Resources\ProgramResource\Pages\CreateProgram;
use App\Filament\Resources\SoalResource\Pages\CreateSoal;
use App\Filament\Resources\TempatMakanResource\Pages\CreateTempatMakan;
use App\Filament\Resources\UrtResource\Pages\CreateUrt;
use App\Filament\Widgets\StatistikSiswaOverview;
use App\Models\BahanMakanan;
use App\Models\FoodRecord;
use App\Models\FoodRecordDetail;
use App\Models\Frekuensi;
use App\Models\Kelas;
use App\Models\KelompokBahan;
use App\Models\Kuesioner;
use App\Models\MateriEdukasi;
use App\Models\Pengguna;
use App\Models\PesertaProgram;
use App\Models\Program;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\StatusAkun;
use App\Models\StatusRealisasi;
use App\Models\TempatMakan;
use App\Models\Urt;
use Livewire\Livewire;

it('dynamically splits selected month and year into weeks and calculates monthly and weekly compliance on the dashboard', function () {
    $admin = Pengguna::factory()->admin()->create();
    $this->actingAs($admin);

    $kelas = Kelas::query()->create([
        'nama_kelas' => 'XI IPA 2',
        'tahun_ajaran' => '2026/2027',
        'aktif' => true,
    ]);

    $program = Program::query()->create([
        'nama_program' => 'Program Februari 2026',
        'tanggal_mulai' => '2026-02-01',
        'durasi_minggu' => 4,
        'aktif' => true,
    ]);

    $akunSiswa = Pengguna::factory()->siswa()->create([
        'nama_lengkap' => 'Budi Santoso',
    ]);

    $statusTerverifikasi = StatusAkun::query()->where('kode', 'TERVERIFIKASI')->firstOrFail();

    $siswa = Siswa::query()->create([
        'pengguna_id' => $akunSiswa->pengguna_id,
        'kelas_id' => $kelas->kelas_id,
        'status_akun_id' => $statusTerverifikasi->status_akun_id,
    ]);

    $peserta = PesertaProgram::query()->create([
        'siswa_id' => $siswa->siswa_id,
        'program_id' => $program->program_id,
        'tanggal_mulai' => '2026-02-01',
        'tanggal_selesai' => '2026-02-14',
        'status' => 'AKTIF',
    ]);

    $statusSesuai = StatusRealisasi::query()->where('kode', 'SESUAI')->firstOrFail();

    // Buat 7 hari sesuai pada Minggu 1 Februari 2026 (1–7 Feb 2026) -> 100% di Minggu 1
    for ($hari = 1; $hari <= 7; $hari++) {
        $tanggal = sprintf('2026-02-%02d', $hari);

        $record = FoodRecord::query()->create([
            'peserta_program_id' => $peserta->peserta_program_id,
            'tanggal' => $tanggal,
            'tidak_sarapan' => false,
        ]);

        FoodRecordDetail::query()->create([
            'food_record_id' => $record->food_record_id,
            'status_realisasi_id' => $statusSesuai->status_realisasi_id,
            'nama_makanan' => 'Nasi Telur',
            'urutan' => 1,
        ]);
    }

    // Uji Februari 2026 (28 hari = 4 minggu: Minggu 1..4)
    Livewire::test(StatistikSiswaOverview::class, [
        'bulan' => 2,
        'tahun' => 2026,
        'kelasId' => $kelas->kelas_id,
    ])
        ->assertSee('Februari 2026')
        ->assertSee('rata-rata februari 2026')
        ->assertSee('50%')
        ->assertSee('100%')
        ->assertSee('1–7 Feb')
        ->assertSee('8–14 Feb')
        ->assertSee('15–21 Feb')
        ->assertSee('22–28 Feb')
        ->assertDontSee('Minggu 5');

    // Uji Oktober 2026 (31 hari = 5 minggu: Minggu 1..5)
    $this->get('/admin?bulan=10&tahun=2026')
        ->assertSuccessful()
        ->assertSee('Periode Oktober 2026')
        ->assertSee('1–7 Okt')
        ->assertSee('8–14 Okt')
        ->assertSee('15–21 Okt')
        ->assertSee('22–28 Okt')
        ->assertSee('29–31 Okt')
        ->assertSee('Minggu 5');
});

it('allows admin to create and manage master data via Filament Data Master resources', function () {
    $admin = Pengguna::factory()->admin()->create();
    $this->actingAs($admin);

    // 1. Kelas
    Livewire::test(ListKelas::class)
        ->assertSuccessful();

    Livewire::test(CreateKelas::class)
        ->fillForm([
            'nama_kelas' => 'XII IPA 1',
            'tahun_ajaran' => '2026/2027',
            'aktif' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Kelas::query()->where('nama_kelas', 'XII IPA 1')->exists())->toBeTrue();

    // 2. Program Intervensi (durasi dinamis 8 minggu)
    Livewire::test(CreateProgram::class)
        ->fillForm([
            'nama_program' => 'Program Sarapan Dinamis 2 Bulan',
            'tanggal_mulai' => '2026-11-01',
            'durasi_minggu' => 8,
            'aktif' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Program::query()->where('nama_program', 'Program Sarapan Dinamis 2 Bulan')->where('durasi_minggu', 8)->exists())->toBeTrue();

    // 3. Kuesioner (otomatis menonaktifkan kuesioner PRETEST lama saat membuat yang aktif baru)
    Livewire::test(CreateKuesioner::class)
        ->fillForm([
            'jenis' => 'PRETEST',
            'judul' => 'Pretest Gizi Baru 2026',
            'aktif' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $kuesionerBaru = Kuesioner::query()->where('judul', 'Pretest Gizi Baru 2026')->firstOrFail();
    expect($kuesionerBaru->aktif)->toBeTrue()
        ->and(Kuesioner::query()->where('jenis', 'PRETEST')->where('aktif', true)->count())->toBe(1);

    // 4. Bank Soal + Opsi Jawaban
    Livewire::test(CreateSoal::class)
        ->fillForm([
            'kuesioner_id' => $kuesionerBaru->kuesioner_id,
            'nomor' => 1,
            'teks_soal' => 'Manakah manfaat utama sarapan pagi bagi siswa?',
            'opsiJawaban' => [
                ['kode_opsi' => 'A', 'teks_opsi' => 'Meningkatkan konsentrasi belajar', 'benar' => true],
                ['kode_opsi' => 'B', 'teks_opsi' => 'Membuat mengantuk di kelas', 'benar' => false],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $soal = Soal::query()->where('kuesioner_id', $kuesionerBaru->kuesioner_id)->where('nomor', 1)->firstOrFail();
    expect($soal->opsiJawaban()->count())->toBe(2)
        ->and($soal->opsiJawaban()->where('benar', true)->value('kode_opsi'))->toBe('A');

    // 5. Materi Edukasi
    Livewire::test(CreateMateriEdukasi::class)
        ->fillForm([
            'judul' => 'Panduan Piring Makanku untuk Sarapan',
            'urutan' => 1,
            'ringkasan' => 'Komposisi karbohidrat, protein, sayur, dan buah saat sarapan.',
            'konten' => '<p>Sarapan sehat mencakup makanan pokok, lauk pauk, sayur, dan buah.</p>',
            'wajib' => true,
            'aktif' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(MateriEdukasi::query()->where('judul', 'Panduan Piring Makanku untuk Sarapan')->exists())->toBeTrue();

    // 6. Kelompok Bahan, URT, Bahan Makanan + Konversi URT, Frekuensi, Tempat Makan
    Livewire::test(CreateKelompokBahan::class)
        ->fillForm([
            'kode' => 'J',
            'urutan' => 10,
            'nama' => 'Suplemen Tambahan',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $kelompok = KelompokBahan::query()->where('kode', 'J')->firstOrFail();

    Livewire::test(CreateUrt::class)
        ->fillForm([
            'nama_urt' => 'cup kecil',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $urt = Urt::query()->where('nama_urt', 'cup kecil')->firstOrFail();

    Livewire::test(CreateBahanMakanan::class)
        ->fillForm([
            'kelompok_bahan_id' => $kelompok->kelompok_bahan_id,
            'nama_bahan' => 'Sereal Gandum Utuh',
            'urutan' => 1,
            'porsi_standar_gram' => 45,
            'aktif' => true,
            'konversiUrt' => [
                ['urt_id' => $urt->urt_id, 'berat_gram' => 45],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $bahan = BahanMakanan::query()->where('nama_bahan', 'Sereal Gandum Utuh')->firstOrFail();
    expect($bahan->konversiUrt()->count())->toBe(1);

    Livewire::test(CreateFrekuensi::class)
        ->fillForm([
            'kode' => '2_HARI',
            'label' => '2x/hari',
            'urutan' => 7,
            'kali_per_minggu' => 14,
            'skor' => 35,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Frekuensi::query()->where('kode', '2_HARI')->exists())->toBeTrue();

    Livewire::test(CreateTempatMakan::class)
        ->fillForm([
            'kode' => 'KANTIN_SEHAT',
            'nama' => 'Kantin Sehat',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TempatMakan::query()->where('kode', 'KANTIN_SEHAT')->exists())->toBeTrue();
});
