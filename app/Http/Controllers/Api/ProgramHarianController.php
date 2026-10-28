<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BahanMakanan;
use App\Models\FoodRecord;
use App\Models\FoodRecordDetail;
use App\Models\HasilKuesioner;
use App\Models\JawabanKuesioner;
use App\Models\KepatuhanHarian;
use App\Models\KepatuhanMingguan;
use App\Models\KonversiUrt;
use App\Models\Kuesioner;
use App\Models\OpsiJawaban;
use App\Models\PesertaProgram;
use App\Models\RencanaSarapan;
use App\Models\RencanaSarapanDetail;
use App\Models\Siswa;
use App\Models\SkorPengetahuan;
use App\Models\StatusRealisasi;
use App\Models\TempatMakan;
use App\Models\Urt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProgramHarianController extends Controller
{
    public function beranda(Request $request): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktif($siswa);

        $hariIni = now()->toDateString();
        $besok = now()->addDay()->toDateString();

        $hariKe = null;
        $mingguKe = null;
        $adaRencanaBesok = false;
        $statusFoodRecordHariIni = 'BELUM_DICATAT';

        if ($peserta) {
            $selisihHari = (int) $peserta->tanggal_mulai->diffInDays(Carbon::parse($hariIni), false);
            $hariKe = max(1, $selisihHari + 1);
            $mingguKe = (int) intdiv(max(0, $selisihHari), 7) + 1;

            $adaRencanaBesok = RencanaSarapan::query()
                ->where('peserta_program_id', $peserta->peserta_program_id)
                ->where('tanggal_sarapan', $besok)
                ->exists();

            $kepatuhanHariIni = KepatuhanHarian::query()
                ->where('peserta_program_id', $peserta->peserta_program_id)
                ->where('tanggal', $hariIni)
                ->first();

            $statusFoodRecordHariIni = $kepatuhanHariIni?->status_kepatuhan ?? 'BELUM_DICATAT';
        }

        return response()->json([
            'data' => [
                'peserta_program_id' => $peserta?->peserta_program_id,
                'tanggal_hari_ini' => $hariIni,
                'tanggal_besok' => $besok,
                'hari_ke' => $hariKe,
                'minggu_ke' => $mingguKe,
                'durasi_minggu' => $peserta?->program?->durasi_minggu ?? 3,
                'ada_rencana_malam_ini' => $adaRencanaBesok,
                'status_food_record_hari_ini' => $statusFoodRecordHariIni,
            ],
            'message' => 'Ringkasan beranda program harian.',
        ]);
    }

    public function referensi(): JsonResponse
    {
        $bahanMakanan = BahanMakanan::query()
            ->with(['kelompokBahan', 'konversiUrt.urt'])
            ->where('aktif', true)
            ->orderBy('kelompok_bahan_id')
            ->orderBy('urutan')
            ->get();

        $urt = Urt::query()->orderBy('nama_urt')->get();
        $tempatMakan = TempatMakan::query()->orderBy('tempat_makan_id')->get();
        $statusRealisasi = StatusRealisasi::query()->orderBy('status_realisasi_id')->get();

        return response()->json([
            'data' => [
                'bahan_makanan' => $bahanMakanan,
                'urt' => $urt,
                'tempat_makan' => $tempatMakan,
                'status_realisasi' => $statusRealisasi,
            ],
            'message' => 'Data referensi bahan makanan, URT, tempat makan, dan status realisasi.',
        ]);
    }

    public function getPlanner(Request $request, string $tanggal): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktif($siswa, abortIfMissing: true);

        $rencana = RencanaSarapan::query()
            ->with(['tempatMakan', 'rencanaSarapanDetail' => fn ($q) => $q->orderBy('urutan')->with(['bahanMakanan', 'urt'])])
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal_sarapan', $tanggal)
            ->first();

        return response()->json([
            'data' => $rencana ? [
                'rencana_sarapan_id' => $rencana->rencana_sarapan_id,
                'tanggal_sarapan' => $rencana->tanggal_sarapan?->toDateString(),
                'jam_rencana' => $rencana->jam_rencana,
                'tempat_makan_id' => $rencana->tempat_makan_id,
                'items' => $rencana->rencanaSarapanDetail,
            ] : null,
            'message' => 'Data rencana sarapan.',
        ]);
    }

    public function putPlanner(Request $request, string $tanggal): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktif($siswa, abortIfMissing: true);

        $validated = $request->validate([
            'jam_rencana' => ['nullable', 'date_format:H:i'],
            'tempat_makan_id' => ['nullable', 'integer', 'exists:tempat_makan_m,tempat_makan_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.nama_makanan' => ['required', 'string', 'max:100'],
            'items.*.bahan_makanan_id' => ['nullable', 'integer', 'exists:bahan_makanan_m,bahan_makanan_id'],
            'items.*.urt_id' => ['nullable', 'integer', 'exists:urt_m,urt_id'],
            'items.*.jumlah_urt' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rencana = DB::transaction(function () use ($peserta, $tanggal, $validated): RencanaSarapan {
            $rencanaRow = RencanaSarapan::query()->updateOrCreate(
                [
                    'peserta_program_id' => $peserta->peserta_program_id,
                    'tanggal_sarapan' => $tanggal,
                ],
                [
                    'jam_rencana' => $validated['jam_rencana'] ?? null,
                    'tempat_makan_id' => $validated['tempat_makan_id'] ?? null,
                ],
            );

            $rencanaRow->rencanaSarapanDetail()->delete();

            foreach (array_values($validated['items']) as $idx => $item) {
                RencanaSarapanDetail::query()->create([
                    'rencana_sarapan_id' => $rencanaRow->rencana_sarapan_id,
                    'nama_makanan' => $item['nama_makanan'],
                    'bahan_makanan_id' => $item['bahan_makanan_id'] ?? null,
                    'urt_id' => $item['urt_id'] ?? null,
                    'jumlah_urt' => $item['jumlah_urt'] ?? null,
                    'urutan' => $idx + 1,
                ]);
            }

            return $rencanaRow->load(['rencanaSarapanDetail' => fn ($q) => $q->orderBy('urutan')]);
        });

        return response()->json([
            'data' => [
                'rencana_sarapan_id' => $rencana->rencana_sarapan_id,
                'tanggal_sarapan' => $rencana->tanggal_sarapan?->toDateString(),
                'jam_rencana' => $rencana->jam_rencana,
                'tempat_makan_id' => $rencana->tempat_makan_id,
                'items' => $rencana->rencanaSarapanDetail,
            ],
            'message' => 'Rencana sarapan berhasil disimpan.',
        ]);
    }

    public function getFoodRecord(Request $request, string $tanggal): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktif($siswa, abortIfMissing: true);

        $foodRecord = FoodRecord::query()
            ->with(['foodRecordDetail' => fn ($q) => $q->orderBy('urutan')->with(['statusRealisasi', 'bahanMakanan', 'urt'])])
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal', $tanggal)
            ->first();

        if ($foodRecord) {
            return response()->json([
                'data' => [
                    'tersimpan' => true,
                    'food_record_id' => $foodRecord->food_record_id,
                    'rencana_sarapan_id' => $foodRecord->rencana_sarapan_id,
                    'tanggal' => $foodRecord->tanggal?->toDateString(),
                    'jam_makan' => $foodRecord->jam_makan,
                    'tempat_makan_id' => $foodRecord->tempat_makan_id,
                    'tidak_sarapan' => $foodRecord->tidak_sarapan,
                    'items' => $foodRecord->foodRecordDetail,
                ],
                'message' => 'Data food record.',
            ]);
        }

        // Prefill dari rencana sarapan bila belum ada food record pada tanggal tersebut
        $rencana = RencanaSarapan::query()
            ->with(['rencanaSarapanDetail' => fn ($q) => $q->orderBy('urutan')->with('bahanMakanan')])
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal_sarapan', $tanggal)
            ->first();

        $statusSesuai = StatusRealisasi::query()->where('kode', 'SESUAI')->first();

        $prefillItems = $rencana
            ? $rencana->rencanaSarapanDetail->map(function (RencanaSarapanDetail $detail) use ($statusSesuai): array {
                $beratGram = $this->hitungBeratGram($detail->bahan_makanan_id, $detail->urt_id, $detail->jumlah_urt);

                return [
                    'rencana_sarapan_detail_id' => $detail->rencana_sarapan_detail_id,
                    'status_realisasi_id' => $statusSesuai?->status_realisasi_id,
                    'nama_makanan' => $detail->nama_makanan,
                    'bahan_teks' => $detail->bahanMakanan?->nama_bahan,
                    'bahan_makanan_id' => $detail->bahan_makanan_id,
                    'urt_id' => $detail->urt_id,
                    'jumlah_urt' => $detail->jumlah_urt !== null ? (float) $detail->jumlah_urt : null,
                    'berat_gram' => $beratGram,
                    'keterangan' => null,
                    'urutan' => $detail->urutan,
                ];
            })->values()->all()
            : [];

        return response()->json([
            'data' => [
                'tersimpan' => false,
                'food_record_id' => null,
                'rencana_sarapan_id' => $rencana?->rencana_sarapan_id,
                'tanggal' => $tanggal,
                'jam_makan' => $rencana?->jam_rencana,
                'tempat_makan_id' => $rencana?->tempat_makan_id,
                'tidak_sarapan' => false,
                'items' => $prefillItems,
            ],
            'message' => 'Prefill food record dari rencana sarapan.',
        ]);
    }

    public function putFoodRecord(Request $request, string $tanggal): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktif($siswa, abortIfMissing: true);

        $validated = $request->validate([
            'jam_makan' => ['nullable', 'date_format:H:i'],
            'tempat_makan_id' => ['nullable', 'integer', 'exists:tempat_makan_m,tempat_makan_id'],
            'tidak_sarapan' => ['required', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.rencana_sarapan_detail_id' => ['nullable', 'integer', 'exists:rencana_sarapan_detail_t,rencana_sarapan_detail_id'],
            'items.*.status_realisasi_id' => ['required_with:items', 'integer', 'exists:status_realisasi_m,status_realisasi_id'],
            'items.*.nama_makanan' => ['required_with:items', 'string', 'max:100'],
            'items.*.bahan_teks' => ['nullable', 'string', 'max:255'],
            'items.*.bahan_makanan_id' => ['nullable', 'integer', 'exists:bahan_makanan_m,bahan_makanan_id'],
            'items.*.urt_id' => ['nullable', 'integer', 'exists:urt_m,urt_id'],
            'items.*.jumlah_urt' => ['nullable', 'numeric', 'min:0'],
            'items.*.berat_gram' => ['nullable', 'numeric', 'min:0'],
            'items.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $rencana = RencanaSarapan::query()
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal_sarapan', $tanggal)
            ->first();

        $foodRecord = DB::transaction(function () use ($peserta, $rencana, $tanggal, $validated): FoodRecord {
            $record = FoodRecord::query()->updateOrCreate(
                [
                    'peserta_program_id' => $peserta->peserta_program_id,
                    'tanggal' => $tanggal,
                ],
                [
                    'rencana_sarapan_id' => $rencana?->rencana_sarapan_id,
                    'jam_makan' => $validated['tidak_sarapan'] ? null : ($validated['jam_makan'] ?? null),
                    'tempat_makan_id' => $validated['tidak_sarapan'] ? null : ($validated['tempat_makan_id'] ?? null),
                    'tidak_sarapan' => (bool) $validated['tidak_sarapan'],
                ],
            );

            $record->foodRecordDetail()->delete();

            if (! $validated['tidak_sarapan'] && ! empty($validated['items'])) {
                foreach (array_values($validated['items']) as $idx => $item) {
                    $beratGram = $item['berat_gram'] ?? $this->hitungBeratGram(
                        $item['bahan_makanan_id'] ?? null,
                        $item['urt_id'] ?? null,
                        $item['jumlah_urt'] ?? null,
                    );

                    FoodRecordDetail::query()->create([
                        'food_record_id' => $record->food_record_id,
                        'rencana_sarapan_detail_id' => $item['rencana_sarapan_detail_id'] ?? null,
                        'status_realisasi_id' => $item['status_realisasi_id'],
                        'nama_makanan' => $item['nama_makanan'],
                        'bahan_teks' => $item['bahan_teks'] ?? null,
                        'bahan_makanan_id' => $item['bahan_makanan_id'] ?? null,
                        'urt_id' => $item['urt_id'] ?? null,
                        'jumlah_urt' => $item['jumlah_urt'] ?? null,
                        'berat_gram' => $beratGram,
                        'keterangan' => $item['keterangan'] ?? null,
                        'urutan' => $idx + 1,
                    ]);
                }
            }

            return $record->load(['foodRecordDetail' => fn ($q) => $q->orderBy('urutan')]);
        });

        return response()->json([
            'data' => [
                'food_record_id' => $foodRecord->food_record_id,
                'tanggal' => $foodRecord->tanggal?->toDateString(),
                'tidak_sarapan' => $foodRecord->tidak_sarapan,
                'items' => $foodRecord->foodRecordDetail,
            ],
            'message' => 'Food record berhasil disimpan.',
        ]);
    }

    public function rekap(Request $request): JsonResponse
    {
        $siswa = $this->getSiswa($request);

        $kepatuhanMingguan = KepatuhanMingguan::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->orderBy('minggu_ke')
            ->get();

        $kepatuhanHarian = KepatuhanHarian::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->orderBy('tanggal')
            ->get();

        $skorPengetahuan = SkorPengetahuan::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->first();

        return response()->json([
            'data' => [
                'kepatuhan_mingguan' => $kepatuhanMingguan,
                'kepatuhan_harian' => $kepatuhanHarian,
                'skor_pengetahuan' => [
                    'skor_pretest' => $skorPengetahuan?->skor_pretest !== null ? (float) $skorPengetahuan->skor_pretest : null,
                    'skor_posttest' => $skorPengetahuan?->skor_posttest !== null ? (float) $skorPengetahuan->skor_posttest : null,
                    'selisih' => $skorPengetahuan?->selisih !== null ? (float) $skorPengetahuan->selisih : null,
                ],
            ],
            'message' => 'Rekap kepatuhan dan skor pengetahuan siswa.',
        ]);
    }

    public function posttest(Request $request): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        if (! $this->apakahPosttestTerbuka($siswa)) {
            return response()->json([
                'data' => null,
                'message' => 'Post test hanya terbuka setelah memasuki akhir minggu ke-3 program.',
            ], 403);
        }

        $kuesioner = Kuesioner::query()
            ->with(['soal' => fn ($query) => $query->orderBy('nomor')->with(['opsiJawaban' => fn ($oq) => $oq->orderBy('kode_opsi')])])
            ->where('jenis', 'POSTTEST')
            ->where('aktif', true)
            ->first();

        if (! $kuesioner) {
            return response()->json([
                'data' => null,
                'message' => 'Kuesioner post test aktif belum tersedia.',
            ], 404);
        }

        $daftarSoal = $kuesioner->soal->map(fn ($soal): array => [
            'soal_id' => $soal->soal_id,
            'nomor' => $soal->nomor,
            'teks_soal' => $soal->teks_soal,
            'opsi' => $soal->opsiJawaban->map(fn ($opsi): array => [
                'opsi_jawaban_id' => $opsi->opsi_jawaban_id,
                'kode_opsi' => $opsi->kode_opsi,
                'teks_opsi' => $opsi->teks_opsi,
            ])->values()->all(),
        ])->values()->all();

        return response()->json([
            'data' => [
                'kuesioner_id' => $kuesioner->kuesioner_id,
                'jenis' => $kuesioner->jenis,
                'judul' => $kuesioner->judul,
                'soal' => $daftarSoal,
            ],
            'message' => 'Daftar soal post test.',
        ]);
    }

    public function submitPosttest(Request $request): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        if (! $this->apakahPosttestTerbuka($siswa)) {
            return response()->json([
                'data' => null,
                'message' => 'Post test hanya terbuka setelah memasuki akhir minggu ke-3 program.',
            ], 403);
        }

        $validated = $request->validate([
            'jawaban' => ['required', 'array', 'min:1'],
            'jawaban.*.soal_id' => ['required', 'integer', 'exists:soal_m,soal_id'],
            'jawaban.*.opsi_jawaban_id' => ['required', 'integer', 'exists:opsi_jawaban_m,opsi_jawaban_id'],
        ]);

        $kuesioner = Kuesioner::query()
            ->with('soal')
            ->where('jenis', 'POSTTEST')
            ->where('aktif', true)
            ->firstOrFail();

        $hasil = DB::transaction(function () use ($siswa, $kuesioner, $validated): HasilKuesioner {
            $jumlahSoal = max(1, $kuesioner->soal->count());
            $jumlahBenar = 0;
            $detailEvaluasi = [];

            foreach ($validated['jawaban'] as $item) {
                $opsi = OpsiJawaban::query()
                    ->where('opsi_jawaban_id', $item['opsi_jawaban_id'])
                    ->where('soal_id', $item['soal_id'])
                    ->firstOrFail();

                $apakahBenar = (bool) $opsi->getAttribute('benar');
                if ($apakahBenar) {
                    $jumlahBenar++;
                }

                $detailEvaluasi[] = [
                    'soal_id' => (int) $item['soal_id'],
                    'opsi_jawaban_id' => (int) $item['opsi_jawaban_id'],
                    'benar' => $apakahBenar,
                ];
            }

            $skor = round(($jumlahBenar / $jumlahSoal) * 100, 2);

            $hasilKuesioner = HasilKuesioner::query()->updateOrCreate(
                [
                    'siswa_id' => $siswa->siswa_id,
                    'kuesioner_id' => $kuesioner->kuesioner_id,
                ],
                [
                    'mulai_pada' => now(),
                    'selesai_pada' => null,
                    'jumlah_soal' => $jumlahSoal,
                    'jumlah_benar' => $jumlahBenar,
                    'skor' => $skor,
                ],
            );

            foreach ($detailEvaluasi as $evaluasi) {
                JawabanKuesioner::query()->updateOrCreate(
                    [
                        'hasil_kuesioner_id' => $hasilKuesioner->hasil_kuesioner_id,
                        'soal_id' => $evaluasi['soal_id'],
                    ],
                    [
                        'opsi_jawaban_id' => $evaluasi['opsi_jawaban_id'],
                        'benar' => $evaluasi['benar'],
                    ],
                );
            }

            $hasilKuesioner->update([
                'selesai_pada' => now(),
            ]);

            return $hasilKuesioner;
        });

        return response()->json([
            'data' => [
                'hasil_kuesioner_id' => $hasil->hasil_kuesioner_id,
                'jumlah_soal' => $hasil->jumlah_soal,
                'jumlah_benar' => $hasil->jumlah_benar,
                'skor' => (float) $hasil->skor,
            ],
            'message' => 'Post test berhasil diselesaikan.',
        ]);
    }

    protected function getSiswa(Request $request): Siswa
    {
        return $request->user()->siswa()->firstOrFail();
    }

    protected function getPesertaProgramAktif(Siswa $siswa, bool $abortIfMissing = false): ?PesertaProgram
    {
        $peserta = PesertaProgram::query()
            ->with('program')
            ->where('siswa_id', $siswa->siswa_id)
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (! $peserta && $abortIfMissing) {
            abort(404, 'Siswa belum terdaftar pada periode program.');
        }

        return $peserta;
    }

    protected function apakahPosttestTerbuka(Siswa $siswa): bool
    {
        $peserta = $this->getPesertaProgramAktif($siswa);
        if (! $peserta) {
            return false;
        }

        if ($peserta->status === 'SELESAI') {
            return true;
        }

        // Terbuka bila sudah mencapai minggu ke-3 (>= 14 hari sejak tanggal_mulai) atau melewati tanggal_selesai
        $hariBerjalan = (int) $peserta->tanggal_mulai->diffInDays(now()->startOfDay(), false) + 1;

        return $hariBerjalan >= 15 || now()->startOfDay()->greaterThanOrEqualTo($peserta->tanggal_selesai);
    }

    protected function hitungBeratGram(int|string|null $bahanMakananId, int|string|null $urtId, float|int|string|null $jumlahUrt): ?float
    {
        if (! $bahanMakananId || ! $urtId || ! is_numeric($jumlahUrt)) {
            return null;
        }

        $konversi = KonversiUrt::query()
            ->where('bahan_makanan_id', (int) $bahanMakananId)
            ->where('urt_id', (int) $urtId)
            ->first();

        if (! $konversi) {
            return null;
        }

        return round((float) $konversi->berat_gram * (float) $jumlahUrt, 2);
    }
}

