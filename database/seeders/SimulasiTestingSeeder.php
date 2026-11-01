<?php

namespace Database\Seeders;

use App\Models\BacaMateri;
use App\Models\BahanMakanan;
use App\Models\FoodRecord;
use App\Models\FoodRecordDetail;
use App\Models\Frekuensi;
use App\Models\HasilKuesioner;
use App\Models\JawabanKuesioner;
use App\Models\Kelas;
use App\Models\Kuesioner;
use App\Models\MateriEdukasi;
use App\Models\OpsiJawaban;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\PesertaProgram;
use App\Models\Program;
use App\Models\RencanaSarapan;
use App\Models\RencanaSarapanDetail;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Sqffq;
use App\Models\SqffqDetail;
use App\Models\StatusAkun;
use App\Models\StatusRealisasi;
use App\Models\TempatMakan;
use App\Models\Urt;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SimulasiTestingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Pastikan Seeder Admin dan DevContent berjalan
        $this->call(DatabaseSeeder::class);
        $this->call(DevContentSeeder::class);

        // Update Materi 1 dengan file PDF dummy untuk testing fitur PDF
        $materi1 = MateriEdukasi::query()->where('urutan', 1)->first();
        if ($materi1) {
            $materi1->update([
                'file_pdf' => 'materi-pdf/panduan-sarapan-seimbang.pdf',
            ]);
        }

        // 2. Data Master Sekolah & Kelas
        $sekolah1 = Sekolah::query()->firstOrCreate(
            ['nama_sekolah' => 'SMP Negeri 1 (Sekolah Utama)'],
            ['npsn' => '20100001', 'alamat' => 'Jl. Pendidikan No. 1, Jakarta', 'aktif' => true],
        );

        $sekolah2 = Sekolah::query()->firstOrCreate(
            ['nama_sekolah' => 'SMP Negeri 2 Harapan Bangsa'],
            ['npsn' => '20102002', 'alamat' => 'Jl. Pemuda Merdeka No. 45, Jakarta', 'aktif' => true],
        );

        $kelas1A = Kelas::query()->firstOrCreate(
            ['sekolah_id' => $sekolah1->sekolah_id, 'nama_kelas' => '8A', 'tahun_ajaran' => '2025/2026'],
            ['aktif' => true],
        );

        $kelas1B = Kelas::query()->firstOrCreate(
            ['sekolah_id' => $sekolah1->sekolah_id, 'nama_kelas' => '8B', 'tahun_ajaran' => '2025/2026'],
            ['aktif' => true],
        );

        $kelas1C = Kelas::query()->firstOrCreate(
            ['sekolah_id' => $sekolah1->sekolah_id, 'nama_kelas' => '8C', 'tahun_ajaran' => '2025/2026'],
            ['aktif' => true],
        );

        $kelas2A = Kelas::query()->firstOrCreate(
            ['sekolah_id' => $sekolah2->sekolah_id, 'nama_kelas' => '8A', 'tahun_ajaran' => '2025/2026'],
            ['aktif' => true],
        );

        // 3. Akun Petugas Gizi
        $peranPetugas = Peran::query()->where('kode', 'PETUGAS')->firstOrFail();
        $peranSiswa = Peran::query()->where('kode', 'SISWA')->firstOrFail();

        $petugas = Pengguna::query()->updateOrCreate(
            ['email' => 'petugas@breakfastplanner.test'],
            [
                'peran_id' => $peranPetugas->peran_id,
                'sekolah_id' => $sekolah1->sekolah_id,
                'nama_lengkap' => 'Siti Rahayu, S.Gz',
                'kata_sandi_hash' => Hash::make('password'),
                'aktif' => true,
            ],
        );

        // 4. Program Intervensi Sarapan Aktif (dimulai 6 hari lalu agar 7 hari tercatat s.d. hari ini)
        $tanggalMulaiProgram = Carbon::today()->subDays(6)->toDateString();
        $program = Program::query()->updateOrCreate(
            ['nama_program' => '[DUMMY] Program Sarapan Sehat 3 Minggu'],
            [
                'tanggal_mulai' => $tanggalMulaiProgram,
                'durasi_minggu' => 3,
                'aktif' => true,
            ],
        );

        // Referensi pendukung
        $pretest = Kuesioner::query()->where('jenis', 'PRETEST')->where('aktif', true)->with('soal.opsiJawaban')->firstOrFail();
        $posttest = Kuesioner::query()->where('jenis', 'POSTTEST')->where('aktif', true)->with('soal.opsiJawaban')->first();
        $materiWajibList = MateriEdukasi::query()->where('wajib', true)->where('aktif', true)->orderBy('urutan')->get();
        $tempatRumah = TempatMakan::query()->where('kode', 'RUMAH')->first() ?? TempatMakan::query()->first();
        $tempatKantin = TempatMakan::query()->where('kode', 'JAJAN')->first()
            ?? TempatMakan::query()->where('kode', 'SEKOLAH')->first()
            ?? $tempatRumah;

        $statusSesuai = StatusRealisasi::query()->where('kode', 'SESUAI')->first() ?? StatusRealisasi::query()->first();
        $statusDiubah = StatusRealisasi::query()->where('kode', 'DIUBAH')->first() ?? $statusSesuai;
        $statusTidakDimakan = StatusRealisasi::query()->where('kode', 'TIDAK_DIMAKAN')->first() ?? $statusSesuai;
        $statusTambahan = StatusRealisasi::query()->where('kode', 'TAMBAHAN')->first() ?? $statusSesuai;

        $bahanNasi = BahanMakanan::query()->where('nama_bahan', 'like', '%nasi%')->first() ?? BahanMakanan::query()->first();
        $bahanTelur = BahanMakanan::query()->where('nama_bahan', 'like', '%telur%')->first() ?? BahanMakanan::query()->first();
        $bahanSusu = BahanMakanan::query()->where('nama_bahan', 'like', '%susu%')->first() ?? BahanMakanan::query()->first();
        $bahanPisang = BahanMakanan::query()->where('nama_bahan', 'like', '%pisang%')->first() ?? BahanMakanan::query()->first();

        $urtPiring = Urt::query()->where('nama_urt', 'like', '%piring%')->first() ?? Urt::query()->first();
        $urtButir = Urt::query()->where('nama_urt', 'like', '%butir%')->first() ?? Urt::query()->first();
        $urtGelas = Urt::query()->where('nama_urt', 'like', '%gelas%')->first() ?? Urt::query()->first();
        $urtBuah = Urt::query()->where('nama_urt', 'like', '%buah%')->first() ?? $urtButir;

        $frekHarian = Frekuensi::query()->where('kode', '1X_HARI')->first() ?? Frekuensi::query()->first();
        $frekMingguan = Frekuensi::query()->where('kode', '4_6X_MINGGU')->first() ?? Frekuensi::query()->first();
        $frekJarang = Frekuensi::query()->where('kode', '1_3X_BULAN')->first() ?? Frekuensi::query()->first();

        // -------------------------------------------------------------
        // SISWA 1: Status PRETEST (Baru register, belum pretest)
        // -------------------------------------------------------------
        $p1 = Pengguna::query()->updateOrCreate(
            ['email' => 'ahmad.pretest@siswa.id'],
            [
                'peran_id' => $peranSiswa->peran_id,
                'nama_lengkap' => 'Ahmad Pratama',
                'kata_sandi_hash' => Hash::make('password'),
                'aktif' => true,
            ],
        );
        $statusPretest = StatusAkun::query()->where('kode', 'PRETEST')->firstOrFail();
        Siswa::query()->updateOrCreate(
            ['pengguna_id' => $p1->pengguna_id],
            [
                'kelas_id' => $kelas1A->kelas_id,
                'status_akun_id' => $statusPretest->status_akun_id,
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '2012-05-10',
            ],
        );

        // -------------------------------------------------------------
        // SISWA 2: Status EDUKASI (Pretest selesai skor 80, baca 1 materi)
        // -------------------------------------------------------------
        $p2 = Pengguna::query()->updateOrCreate(
            ['email' => 'bunga.edukasi@siswa.id'],
            [
                'peran_id' => $peranSiswa->peran_id,
                'nama_lengkap' => 'Bunga Citra',
                'kata_sandi_hash' => Hash::make('password'),
                'aktif' => true,
            ],
        );
        $s2 = Siswa::query()->updateOrCreate(
            ['pengguna_id' => $p2->pengguna_id],
            [
                'kelas_id' => $kelas1A->kelas_id,
                'status_akun_id' => $statusPretest->status_akun_id,
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '2012-08-15',
            ],
        );

        $this->isiPretestDanSubmit($s2, $pretest, 4); // 4 benar -> skor 80 -> trigger pindahkan ke EDUKASI
        if ($materiWajibList->isNotEmpty()) {
            BacaMateri::query()->updateOrCreate(
                ['siswa_id' => $s2->siswa_id, 'materi_edukasi_id' => $materiWajibList[0]->materi_edukasi_id],
                ['selesai' => true, 'selesai_pada' => now()],
            );
        }

        // -------------------------------------------------------------
        // SISWA 3: Status MENUNGGU_VERIFIKASI (Tampil di Antrean Verifikasi)
        // -------------------------------------------------------------
        $p3 = Pengguna::query()->updateOrCreate(
            ['email' => 'citra.antrean@siswa.id'],
            [
                'peran_id' => $peranSiswa->peran_id,
                'nama_lengkap' => 'Citra Lestari',
                'kata_sandi_hash' => Hash::make('password'),
                'aktif' => true,
            ],
        );
        $s3 = Siswa::query()->updateOrCreate(
            ['pengguna_id' => $p3->pengguna_id],
            [
                'kelas_id' => $kelas1B->kelas_id,
                'status_akun_id' => $statusPretest->status_akun_id,
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '2012-03-22',
            ],
        );

        $this->isiPretestDanSubmit($s3, $pretest, 5); // 5 benar -> skor 100
        foreach ($materiWajibList as $mw) {
            BacaMateri::query()->updateOrCreate(
                ['siswa_id' => $s3->siswa_id, 'materi_edukasi_id' => $mw->materi_edukasi_id],
                ['selesai' => true, 'selesai_pada' => now()],
            );
        }
        // Trigger otomatis memindahkan s3 ke MENUNGGU_VERIFIKASI!

        // -------------------------------------------------------------
        // SISWA 4: Status TERVERIFIKASI (Kepatuhan Sarapan TINGGI)
        // -------------------------------------------------------------
        $p4 = Pengguna::query()->updateOrCreate(
            ['email' => 'dimas.patuh@siswa.id'],
            [
                'peran_id' => $peranSiswa->peran_id,
                'nama_lengkap' => 'Dimas Anggara',
                'kata_sandi_hash' => Hash::make('password'),
                'aktif' => true,
            ],
        );
        $s4 = Siswa::query()->updateOrCreate(
            ['pengguna_id' => $p4->pengguna_id],
            [
                'kelas_id' => $kelas1A->kelas_id,
                'status_akun_id' => $statusPretest->status_akun_id,
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '2012-01-30',
            ],
        );

        $this->isiPretestDanSubmit($s4, $pretest, 4); // pretest skor 80
        foreach ($materiWajibList as $mw) {
            BacaMateri::query()->updateOrCreate(
                ['siswa_id' => $s4->siswa_id, 'materi_edukasi_id' => $mw->materi_edukasi_id],
                ['selesai' => true, 'selesai_pada' => now()],
            );
        }

        // SQ-FFQ Awal -> selesai -> trigger memverifikasi akun & insert ke peserta_program_t
        $sqffq4 = Sqffq::query()->updateOrCreate(
            ['siswa_id' => $s4->siswa_id, 'tahap' => 'AWAL'],
            [
                'petugas_pengguna_id' => $petugas->pengguna_id,
                'tanggal_wawancara' => Carbon::today()->subDays(7),
                'catatan' => 'Pola makan teratur, asupan sarapan seimbang.',
                'status' => 'DRAF',
            ],
        );

        // Isi SQ-FFQ Detail untuk beberapa bahan makanan utama
        $allBahan = BahanMakanan::query()->where('aktif', true)->get();
        foreach ($allBahan as $idx => $b) {
            $frek = ($idx % 3 === 0) ? $frekHarian : (($idx % 2 === 0) ? $frekMingguan : $frekJarang);
            SqffqDetail::query()->updateOrCreate(
                ['sqffq_id' => $sqffq4->sqffq_id, 'bahan_makanan_id' => $b->bahan_makanan_id],
                ['frekuensi_id' => $frek->frekuensi_id, 'porsi_gram' => 100.0],
            );
        }

        // Selesaikan SQ-FFQ -> Trigger DB membuat status TERVERIFIKASI dan mendaftar peserta_program_t
        $sqffq4->update(['status' => 'SELESAI', 'selesai_pada' => now()]);

        // Sesuaikan tanggal peserta_program agar mencakup 7 hari monitoring
        $peserta4 = PesertaProgram::query()->where('siswa_id', $s4->siswa_id)->first();
        if ($peserta4) {
            $peserta4->update([
                'tanggal_mulai' => $tanggalMulaiProgram,
                'tanggal_selesai' => Carbon::parse($tanggalMulaiProgram)->addDays(20)->toDateString(),
            ]);

            // Buat data Rencana Sarapan dan Food Record selama 7 hari (H-6 s.d. hari ini)
            for ($i = 6; $i >= 0; $i--) {
                $tgl = Carbon::today()->subDays($i)->toDateString();

                $rencana = RencanaSarapan::query()->updateOrCreate(
                    ['peserta_program_id' => $peserta4->peserta_program_id, 'tanggal_sarapan' => $tgl],
                    ['jam_rencana' => '06:30:00', 'tempat_makan_id' => $tempatRumah->tempat_makan_id],
                );

                $rDetail1 = RencanaSarapanDetail::query()->updateOrCreate(
                    ['rencana_sarapan_id' => $rencana->rencana_sarapan_id, 'nama_makanan' => 'Nasi Putih'],
                    ['bahan_makanan_id' => $bahanNasi->bahan_makanan_id, 'urt_id' => $urtPiring->urt_id, 'jumlah_urt' => 1, 'urutan' => 1],
                );
                $rDetail2 = RencanaSarapanDetail::query()->updateOrCreate(
                    ['rencana_sarapan_id' => $rencana->rencana_sarapan_id, 'nama_makanan' => 'Telur Dadar'],
                    ['bahan_makanan_id' => $bahanTelur->bahan_makanan_id, 'urt_id' => $urtButir->urt_id, 'jumlah_urt' => 1, 'urutan' => 2],
                );

                $fr = FoodRecord::query()->updateOrCreate(
                    ['peserta_program_id' => $peserta4->peserta_program_id, 'tanggal' => $tgl],
                    [
                        'rencana_sarapan_id' => $rencana->rencana_sarapan_id,
                        'jam_makan' => '06:35:00',
                        'tempat_makan_id' => $tempatRumah->tempat_makan_id,
                        'tidak_sarapan' => false,
                    ],
                );

                // Hari ke-5 diubah, sisanya SESUAI
                $statusItem = ($i === 2) ? $statusDiubah : $statusSesuai;

                FoodRecordDetail::query()->updateOrCreate(
                    ['food_record_id' => $fr->food_record_id, 'nama_makanan' => 'Nasi Putih'],
                    [
                        'rencana_sarapan_detail_id' => $rDetail1->rencana_sarapan_detail_id,
                        'status_realisasi_id' => $statusSesuai->status_realisasi_id,
                        'bahan_makanan_id' => $bahanNasi->bahan_makanan_id,
                        'urt_id' => $urtPiring->urt_id,
                        'jumlah_urt' => 1,
                        'berat_gram' => 100,
                        'keterangan' => 'Sesuai rencana sarapan',
                        'urutan' => 1,
                    ],
                );

                FoodRecordDetail::query()->updateOrCreate(
                    ['food_record_id' => $fr->food_record_id, 'nama_makanan' => 'Telur Dadar'],
                    [
                        'rencana_sarapan_detail_id' => $rDetail2->rencana_sarapan_detail_id,
                        'status_realisasi_id' => $statusItem->status_realisasi_id,
                        'bahan_makanan_id' => $bahanTelur->bahan_makanan_id,
                        'urt_id' => $urtButir->urt_id,
                        'jumlah_urt' => 1,
                        'berat_gram' => 60,
                        'keterangan' => ($i === 2) ? 'Porsi ditambah sedikit' : 'Sesuai rencana',
                        'urutan' => 2,
                    ],
                );
            }
        }

        // Posttest skor 100 untuk Dimas
        if ($posttest) {
            $this->isiPosttestDanSubmit($s4, $posttest, 5);
        }

        // -------------------------------------------------------------
        // SISWA 5: Status TERVERIFIKASI (Kepatuhan Sarapan RENDAH / BOLONG)
        // -------------------------------------------------------------
        $p5 = Pengguna::query()->updateOrCreate(
            ['email' => 'eko.kurang@siswa.id'],
            [
                'peran_id' => $peranSiswa->peran_id,
                'nama_lengkap' => 'Eko Prasetyo',
                'kata_sandi_hash' => Hash::make('password'),
                'aktif' => true,
            ],
        );
        $s5 = Siswa::query()->updateOrCreate(
            ['pengguna_id' => $p5->pengguna_id],
            [
                'kelas_id' => $kelas1C->kelas_id,
                'status_akun_id' => $statusPretest->status_akun_id,
                'jenis_kelamin' => 'L',
                'tanggal_lahir' => '2012-11-05',
            ],
        );

        $this->isiPretestDanSubmit($s5, $pretest, 3); // pretest skor 60
        foreach ($materiWajibList as $mw) {
            BacaMateri::query()->updateOrCreate(
                ['siswa_id' => $s5->siswa_id, 'materi_edukasi_id' => $mw->materi_edukasi_id],
                ['selesai' => true, 'selesai_pada' => now()],
            );
        }

        $sqffq5 = Sqffq::query()->updateOrCreate(
            ['siswa_id' => $s5->siswa_id, 'tahap' => 'AWAL'],
            [
                'petugas_pengguna_id' => $petugas->pengguna_id,
                'tanggal_wawancara' => Carbon::today()->subDays(7),
                'catatan' => 'Sering melewatkan sarapan pagi.',
                'status' => 'DRAF',
            ],
        );

        foreach ($allBahan->take(30) as $b) {
            SqffqDetail::query()->updateOrCreate(
                ['sqffq_id' => $sqffq5->sqffq_id, 'bahan_makanan_id' => $b->bahan_makanan_id],
                ['frekuensi_id' => $frekJarang->frekuensi_id, 'porsi_gram' => 50.0],
            );
        }
        $sqffq5->update(['status' => 'SELESAI', 'selesai_pada' => now()]);

        $peserta5 = PesertaProgram::query()->where('siswa_id', $s5->siswa_id)->first();
        if ($peserta5) {
            $peserta5->update([
                'tanggal_mulai' => $tanggalMulaiProgram,
                'tanggal_selesai' => Carbon::parse($tanggalMulaiProgram)->addDays(20)->toDateString(),
            ]);

            // 7 hari simulasi (sebagian tidak sarapan, sebagian tidak sesuai, sebagian belum dicatat)
            for ($i = 6; $i >= 0; $i--) {
                $tgl = Carbon::today()->subDays($i)->toDateString();

                if ($i === 6) {
                    // Hari 1: Rencana ada, Food record sesuai
                    $rencana = RencanaSarapan::query()->updateOrCreate(
                        ['peserta_program_id' => $peserta5->peserta_program_id, 'tanggal_sarapan' => $tgl],
                        ['jam_rencana' => '06:30:00', 'tempat_makan_id' => $tempatRumah->tempat_makan_id],
                    );
                    $rDetail = RencanaSarapanDetail::query()->updateOrCreate(
                        ['rencana_sarapan_id' => $rencana->rencana_sarapan_id, 'nama_makanan' => 'Nasi Goreng'],
                        ['bahan_makanan_id' => $bahanNasi->bahan_makanan_id, 'urt_id' => $urtPiring->urt_id, 'jumlah_urt' => 1, 'urutan' => 1],
                    );
                    $fr = FoodRecord::query()->updateOrCreate(
                        ['peserta_program_id' => $peserta5->peserta_program_id, 'tanggal' => $tgl],
                        ['rencana_sarapan_id' => $rencana->rencana_sarapan_id, 'jam_makan' => '06:40:00', 'tempat_makan_id' => $tempatRumah->tempat_makan_id, 'tidak_sarapan' => false],
                    );
                    FoodRecordDetail::query()->updateOrCreate(
                        ['food_record_id' => $fr->food_record_id, 'nama_makanan' => 'Nasi Goreng'],
                        ['rencana_sarapan_detail_id' => $rDetail->rencana_sarapan_detail_id, 'status_realisasi_id' => $statusSesuai->status_realisasi_id, 'berat_gram' => 100, 'urutan' => 1],
                    );
                } elseif ($i === 5 || $i === 3) {
                    // Hari 2 & 4: TIDAK SARAPAN
                    $rencana = RencanaSarapan::query()->updateOrCreate(
                        ['peserta_program_id' => $peserta5->peserta_program_id, 'tanggal_sarapan' => $tgl],
                        ['jam_rencana' => '06:30:00', 'tempat_makan_id' => $tempatRumah->tempat_makan_id],
                    );
                    FoodRecord::query()->updateOrCreate(
                        ['peserta_program_id' => $peserta5->peserta_program_id, 'tanggal' => $tgl],
                        ['rencana_sarapan_id' => $rencana->rencana_sarapan_id, 'tidak_sarapan' => true],
                    );
                } elseif ($i === 4 || $i === 2) {
                    // Hari 3 & 5: TIDAK SESUAI (rencana nasi di rumah, realisasi jajan di kantin)
                    $rencana = RencanaSarapan::query()->updateOrCreate(
                        ['peserta_program_id' => $peserta5->peserta_program_id, 'tanggal_sarapan' => $tgl],
                        ['jam_rencana' => '06:30:00', 'tempat_makan_id' => $tempatRumah->tempat_makan_id],
                    );
                    $rDetail = RencanaSarapanDetail::query()->updateOrCreate(
                        ['rencana_sarapan_id' => $rencana->rencana_sarapan_id, 'nama_makanan' => 'Nasi Putih'],
                        ['bahan_makanan_id' => $bahanNasi->bahan_makanan_id, 'urt_id' => $urtPiring->urt_id, 'jumlah_urt' => 1, 'urutan' => 1],
                    );
                    $fr = FoodRecord::query()->updateOrCreate(
                        ['peserta_program_id' => $peserta5->peserta_program_id, 'tanggal' => $tgl],
                        ['rencana_sarapan_id' => $rencana->rencana_sarapan_id, 'jam_makan' => '09:30:00', 'tempat_makan_id' => $tempatKantin->tempat_makan_id, 'tidak_sarapan' => false],
                    );
                    FoodRecordDetail::query()->updateOrCreate(
                        ['food_record_id' => $fr->food_record_id, 'nama_makanan' => 'Gorengan'],
                        ['rencana_sarapan_detail_id' => $rDetail->rencana_sarapan_detail_id, 'status_realisasi_id' => $statusDiubah->status_realisasi_id, 'keterangan' => 'Jajan gorengan saat istirahat', 'urutan' => 1],
                    );
                }
                // Hari 6 & 7 sengaja belum diisi (BELUM_DICATAT)
            }
        }

        // -------------------------------------------------------------
        // SISWA 6: Status TERVERIFIKASI (Sekolah 2 - SMP Negeri 2)
        // -------------------------------------------------------------
        $p6 = Pengguna::query()->updateOrCreate(
            ['email' => 'fani.smp2@siswa.id'],
            [
                'peran_id' => $peranSiswa->peran_id,
                'nama_lengkap' => 'Fani Rahmawati',
                'kata_sandi_hash' => Hash::make('password'),
                'aktif' => true,
            ],
        );
        $s6 = Siswa::query()->updateOrCreate(
            ['pengguna_id' => $p6->pengguna_id],
            [
                'kelas_id' => $kelas2A->kelas_id,
                'status_akun_id' => $statusPretest->status_akun_id,
                'jenis_kelamin' => 'P',
                'tanggal_lahir' => '2012-07-19',
            ],
        );

        $this->isiPretestDanSubmit($s6, $pretest, 4); // pretest skor 80
        foreach ($materiWajibList as $mw) {
            BacaMateri::query()->updateOrCreate(
                ['siswa_id' => $s6->siswa_id, 'materi_edukasi_id' => $mw->materi_edukasi_id],
                ['selesai' => true, 'selesai_pada' => now()],
            );
        }

        $sqffq6 = Sqffq::query()->updateOrCreate(
            ['siswa_id' => $s6->siswa_id, 'tahap' => 'AWAL'],
            [
                'petugas_pengguna_id' => $petugas->pengguna_id,
                'tanggal_wawancara' => Carbon::today()->subDays(7),
                'catatan' => 'Siswa SMP Negeri 2, sarapan rutin telur dan susu.',
                'status' => 'DRAF',
            ],
        );

        foreach ($allBahan->take(50) as $idx => $b) {
            $frek = ($idx % 2 === 0) ? $frekHarian : $frekMingguan;
            SqffqDetail::query()->updateOrCreate(
                ['sqffq_id' => $sqffq6->sqffq_id, 'bahan_makanan_id' => $b->bahan_makanan_id],
                ['frekuensi_id' => $frek->frekuensi_id, 'porsi_gram' => 80.0],
            );
        }
        $sqffq6->update(['status' => 'SELESAI', 'selesai_pada' => now()]);

        $peserta6 = PesertaProgram::query()->where('siswa_id', $s6->siswa_id)->first();
        if ($peserta6) {
            $peserta6->update([
                'tanggal_mulai' => $tanggalMulaiProgram,
                'tanggal_selesai' => Carbon::parse($tanggalMulaiProgram)->addDays(20)->toDateString(),
            ]);

            for ($i = 5; $i >= 1; $i--) {
                $tgl = Carbon::today()->subDays($i)->toDateString();
                $rencana = RencanaSarapan::query()->updateOrCreate(
                    ['peserta_program_id' => $peserta6->peserta_program_id, 'tanggal_sarapan' => $tgl],
                    ['jam_rencana' => '06:15:00', 'tempat_makan_id' => $tempatRumah->tempat_makan_id],
                );
                $rDetail = RencanaSarapanDetail::query()->updateOrCreate(
                    ['rencana_sarapan_id' => $rencana->rencana_sarapan_id, 'nama_makanan' => 'Susu & Pisang'],
                    ['bahan_makanan_id' => $bahanSusu->bahan_makanan_id, 'urt_id' => $urtGelas->urt_id, 'jumlah_urt' => 1, 'urutan' => 1],
                );
                $fr = FoodRecord::query()->updateOrCreate(
                    ['peserta_program_id' => $peserta6->peserta_program_id, 'tanggal' => $tgl],
                    ['rencana_sarapan_id' => $rencana->rencana_sarapan_id, 'jam_makan' => '06:20:00', 'tempat_makan_id' => $tempatRumah->tempat_makan_id, 'tidak_sarapan' => false],
                );
                FoodRecordDetail::query()->updateOrCreate(
                    ['food_record_id' => $fr->food_record_id, 'nama_makanan' => 'Susu & Pisang'],
                    ['rencana_sarapan_detail_id' => $rDetail->rencana_sarapan_detail_id, 'status_realisasi_id' => $statusSesuai->status_realisasi_id, 'berat_gram' => 200, 'urutan' => 1],
                );
            }
        }
    }

    private function isiPretestDanSubmit(Siswa $siswa, Kuesioner $kuesioner, int $jumlahBenarTarget): void
    {
        $hasil = HasilKuesioner::query()->updateOrCreate(
            ['siswa_id' => $siswa->siswa_id, 'kuesioner_id' => $kuesioner->kuesioner_id],
            ['selesai_pada' => null],
        );

        $soalList = $kuesioner->soal->sortBy('nomor')->values();
        $totalSoal = $soalList->count();
        $benarCount = 0;

        foreach ($soalList as $idx => $soal) {
            $jadikanBenar = ($idx < $jumlahBenarTarget);
            $opsi = $soal->opsiJawaban->first(fn (OpsiJawaban $o) => (bool) $o->benar === $jadikanBenar)
                ?? $soal->opsiJawaban->first();

            $isBenar = (bool) ($opsi?->benar ?? false);
            if ($isBenar) {
                $benarCount++;
            }

            JawabanKuesioner::query()->updateOrCreate(
                ['hasil_kuesioner_id' => $hasil->hasil_kuesioner_id, 'soal_id' => $soal->soal_id],
                ['opsi_jawaban_id' => $opsi->opsi_jawaban_id, 'benar' => $isBenar],
            );
        }

        $skor = $totalSoal > 0 ? round(($benarCount / $totalSoal) * 100, 2) : 0;
        $hasil->update([
            'jumlah_soal' => $totalSoal,
            'jumlah_benar' => $benarCount,
            'skor' => $skor,
            'selesai_pada' => now(), // Trigger DB fn_set_status_akun ke EDUKASI
        ]);
    }

    private function isiPosttestDanSubmit(Siswa $siswa, Kuesioner $kuesioner, int $jumlahBenarTarget): void
    {
        $hasil = HasilKuesioner::query()->updateOrCreate(
            ['siswa_id' => $siswa->siswa_id, 'kuesioner_id' => $kuesioner->kuesioner_id],
            ['selesai_pada' => null],
        );

        $soalList = $kuesioner->soal->sortBy('nomor')->values();
        $totalSoal = $soalList->count();
        $benarCount = 0;

        foreach ($soalList as $idx => $soal) {
            $jadikanBenar = ($idx < $jumlahBenarTarget);
            $opsi = $soal->opsiJawaban->first(fn (OpsiJawaban $o) => (bool) $o->benar === $jadikanBenar)
                ?? $soal->opsiJawaban->first();

            $isBenar = (bool) ($opsi?->benar ?? false);
            if ($isBenar) {
                $benarCount++;
            }

            JawabanKuesioner::query()->updateOrCreate(
                ['hasil_kuesioner_id' => $hasil->hasil_kuesioner_id, 'soal_id' => $soal->soal_id],
                ['opsi_jawaban_id' => $opsi->opsi_jawaban_id, 'benar' => $isBenar],
            );
        }

        $skor = $totalSoal > 0 ? round(($benarCount / $totalSoal) * 100, 2) : 0;
        $hasil->update([
            'jumlah_soal' => $totalSoal,
            'jumlah_benar' => $benarCount,
            'skor' => $skor,
            'selesai_pada' => now(),
        ]);
    }
}
