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
use App\Models\Soal;
use App\Models\StatusRealisasi;
use App\Models\TempatMakan;
use App\Models\Urt;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProgramHarianController extends Controller
{
    public function referensi(): JsonResponse
    {
        $bahanMakanan = BahanMakanan::query()
            ->with(['kelompokBahan', 'konversiUrt.urt'])
            ->where('aktif', true)
            ->orderBy('kelompok_bahan_id')
            ->orderBy('urutan')
            ->get()
            ->map(fn (BahanMakanan $bahan): array => [
                'bahan_makanan_id' => $bahan->bahan_makanan_id,
                'nama_bahan' => $bahan->nama_bahan,
                'urutan' => $bahan->urutan,
                'porsi_standar_gram' => $bahan->porsi_standar_gram !== null ? (float) $bahan->porsi_standar_gram : null,
                'kelompok' => $bahan->kelompokBahan ? [
                    'kelompok_bahan_id' => $bahan->kelompokBahan->kelompok_bahan_id,
                    'kode' => $bahan->kelompokBahan->kode,
                    'nama' => $bahan->kelompokBahan->nama,
                ] : null,
                'konversi_urt' => $bahan->konversiUrt->map(fn (KonversiUrt $k): array => [
                    'urt_id' => $k->urt_id,
                    'nama_urt' => $k->urt?->nama_urt,
                    'berat_gram' => (float) $k->berat_gram,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $urt = Urt::query()
            ->orderBy('urt_id')
            ->get(['urt_id', 'nama_urt']);

        $tempatMakan = TempatMakan::query()
            ->orderBy('tempat_makan_id')
            ->get(['tempat_makan_id', 'kode', 'nama']);

        $statusRealisasi = StatusRealisasi::query()
            ->orderBy('status_realisasi_id')
            ->get(['status_realisasi_id', 'kode', 'nama']);

        return response()->json([
            'data' => [
                'bahan_makanan' => $bahanMakanan,
                'urt' => $urt,
                'tempat_makan' => $tempatMakan,
                'status_realisasi' => $statusRealisasi,
            ],
            'message' => 'Data referensi program harian.',
        ]);
    }

    public function beranda(Request $request): JsonResponse
    {
        $pengguna = $request->user();
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktifOrFail($siswa);

        $hariIni = Carbon::today();
        $besok = $hariIni->copy()->addDay();
        $tanggalMulai = $peserta->tanggal_mulai->copy()->startOfDay();
        $durasiMinggu = (int) ($peserta->program?->durasi_minggu ?? 3);

        $selisihHari = (int) $tanggalMulai->diffInDays($hariIni, false);
        $hariKe = max(1, $selisihHari + 1);
        $mingguKe = (int) intdiv(max(0, $selisihHari), 7) + 1;
        $mingguBerjalan = min($durasiMinggu, $mingguKe);

        $rencanaBesokAda = RencanaSarapan::query()
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal_sarapan', $besok->toDateString())
            ->exists();

        $foodRecordHariIniAda = FoodRecord::query()
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal', $hariIni->toDateString())
            ->exists();

        $ringkasanMinggu = KepatuhanMingguan::query()
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('minggu_ke', $mingguBerjalan)
            ->first();

        return response()->json([
            'data' => [
                'nama' => $pengguna->nama_lengkap,
                'nama_lengkap' => $pengguna->nama_lengkap,
                'tanggal_hari_ini' => $hariIni->toDateString(),
                'tanggal_besok' => $besok->toDateString(),
                'tanggal_mulai' => $peserta->tanggal_mulai->toDateString(),
                'tanggal_selesai' => $peserta->tanggal_selesai->toDateString(),
                'minggu_ke' => $mingguKe,
                'hari_ke' => $hariKe,
                'durasi_minggu' => $durasiMinggu,
                'rencana_besok_ada' => $rencanaBesokAda,
                'food_record_hari_ini_ada' => $foodRecordHariIniAda,
                'kepatuhan_minggu_berjalan' => [
                    'minggu_ke' => $mingguBerjalan,
                    'hari_berjalan' => (int) ($ringkasanMinggu?->hari_berjalan ?? 0),
                    'hari_tercatat' => (int) ($ringkasanMinggu?->hari_tercatat ?? 0),
                    'hari_sesuai' => (int) ($ringkasanMinggu?->hari_sesuai ?? 0),
                    'persen_kepatuhan' => $ringkasanMinggu?->persen_kepatuhan !== null
                        ? (float) $ringkasanMinggu->persen_kepatuhan
                        : 0.0,
                ],
            ],
            'message' => 'Ringkasan beranda program harian.',
        ]);
    }

    public function getPlanner(Request $request, string $tanggal): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktifOrFail($siswa);
        $tanggalValid = $this->parseTanggalOrFail($tanggal);

        $rencana = RencanaSarapan::query()
            ->with([
                'tempatMakan',
                'rencanaSarapanDetail' => fn ($q) => $q->orderBy('urutan')->with(['bahanMakanan', 'urt']),
            ])
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal_sarapan', $tanggalValid->toDateString())
            ->first();

        return response()->json([
            'data' => $rencana ? $this->formatRencana($rencana) : null,
            'message' => $rencana ? 'Data rencana sarapan.' : 'Belum ada rencana sarapan pada tanggal tersebut.',
        ]);
    }

    public function putPlanner(Request $request, string $tanggal): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktifOrFail($siswa);
        $tanggalTarget = $this->parseTanggalOrFail($tanggal);

        $this->pastikanTanggalDalamRentangProgram($tanggalTarget, $peserta);

        if ($tanggalTarget->lt(Carbon::today())) {
            throw ValidationException::withMessages([
                'tanggal' => ['Tanggal rencana sarapan tidak boleh sudah lewat.'],
            ]);
        }

        $validated = $request->validate([
            'jam_rencana' => ['nullable', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'tempat_makan_id' => ['nullable', 'integer', 'exists:tempat_makan_m,tempat_makan_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.nama_makanan' => ['required', 'string', 'max:100'],
            'items.*.bahan_makanan_id' => [
                'nullable',
                'integer',
                Rule::exists('bahan_makanan_m', 'bahan_makanan_id')->where('aktif', true),
            ],
            'items.*.urt_id' => ['required', 'integer', 'exists:urt_m,urt_id'],
            'items.*.jumlah_urt' => ['required', 'numeric', 'gt:0'],
        ]);

        $rencana = DB::transaction(function () use ($peserta, $tanggalTarget, $validated): RencanaSarapan {
            $rencanaRow = RencanaSarapan::query()->updateOrCreate(
                [
                    'peserta_program_id' => $peserta->peserta_program_id,
                    'tanggal_sarapan' => $tanggalTarget->toDateString(),
                ],
                [
                    'jam_rencana' => $validated['jam_rencana'] ?? null,
                    'tempat_makan_id' => $validated['tempat_makan_id'] ?? null,
                ],
            );

            $detailLamaIds = $rencanaRow->rencanaSarapanDetail()->pluck('rencana_sarapan_detail_id');
            if ($detailLamaIds->isNotEmpty()) {
                FoodRecordDetail::query()
                    ->whereIn('rencana_sarapan_detail_id', $detailLamaIds)
                    ->update(['rencana_sarapan_detail_id' => null]);
            }

            $rencanaRow->rencanaSarapanDetail()->delete();

            foreach (array_values($validated['items']) as $idx => $item) {
                RencanaSarapanDetail::query()->create([
                    'rencana_sarapan_id' => $rencanaRow->rencana_sarapan_id,
                    'nama_makanan' => $item['nama_makanan'],
                    'bahan_makanan_id' => $item['bahan_makanan_id'] ?? null,
                    'urt_id' => $item['urt_id'],
                    'jumlah_urt' => $item['jumlah_urt'],
                    'urutan' => $idx + 1,
                ]);
            }

            return $rencanaRow->load([
                'tempatMakan',
                'rencanaSarapanDetail' => fn ($q) => $q->orderBy('urutan')->with(['bahanMakanan', 'urt']),
            ]);
        });

        return response()->json([
            'data' => $this->formatRencana($rencana),
            'message' => 'Rencana sarapan berhasil disimpan.',
        ]);
    }

    public function getFoodRecord(Request $request, string $tanggal): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktifOrFail($siswa);
        $tanggalTarget = $this->parseTanggalOrFail($tanggal);

        $foodRecord = FoodRecord::query()
            ->with([
                'tempatMakan',
                'foodRecordDetail' => fn ($q) => $q->orderBy('urutan')->with(['statusRealisasi', 'bahanMakanan', 'urt']),
            ])
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal', $tanggalTarget->toDateString())
            ->first();

        if ($foodRecord) {
            return response()->json([
                'data' => $this->formatFoodRecordTersimpan($foodRecord),
                'message' => 'Data food record.',
            ]);
        }

        $rencana = RencanaSarapan::query()
            ->with([
                'tempatMakan',
                'rencanaSarapanDetail' => fn ($q) => $q->orderBy('urutan')->with(['bahanMakanan.konversiUrt', 'urt']),
            ])
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal_sarapan', $tanggalTarget->toDateString())
            ->first();

        if (! $rencana) {
            return response()->json([
                'data' => null,
                'message' => 'Belum ada food record maupun rencana sarapan pada tanggal tersebut.',
            ]);
        }

        $statusSesuai = StatusRealisasi::query()->where('kode', 'SESUAI')->firstOrFail();

        $prefillItems = $rencana->rencanaSarapanDetail->map(function (RencanaSarapanDetail $detail) use ($statusSesuai): array {
            $konversi = $detail->bahanMakanan?->konversiUrt
                ?->firstWhere('urt_id', $detail->urt_id);

            $beratGram = ($konversi && $detail->jumlah_urt !== null)
                ? round((float) $konversi->berat_gram * (float) $detail->jumlah_urt, 2)
                : null;

            return [
                'food_record_detail_id' => null,
                'rencana_sarapan_detail_id' => $detail->rencana_sarapan_detail_id,
                'status_realisasi_id' => $statusSesuai->status_realisasi_id,
                'status_realisasi' => [
                    'status_realisasi_id' => $statusSesuai->status_realisasi_id,
                    'kode' => $statusSesuai->kode,
                    'nama' => $statusSesuai->nama,
                ],
                'nama_makanan' => $detail->nama_makanan,
                'bahan_teks' => $detail->bahanMakanan?->nama_bahan,
                'bahan_makanan_id' => $detail->bahan_makanan_id,
                'urt_id' => $detail->urt_id,
                'nama_urt' => $detail->urt?->nama_urt,
                'jumlah_urt' => $detail->jumlah_urt !== null ? (float) $detail->jumlah_urt : null,
                'berat_gram' => $beratGram,
                'keterangan' => null,
                'urutan' => $detail->urutan,
            ];
        })->values()->all();

        return response()->json([
            'data' => [
                'dari_rencana' => true,
                'food_record_id' => null,
                'rencana_sarapan_id' => $rencana->rencana_sarapan_id,
                'tanggal' => $tanggalTarget->toDateString(),
                'tidak_sarapan' => false,
                'jam_makan' => $this->formatJam($rencana->jam_rencana),
                'tempat_makan_id' => $rencana->tempat_makan_id,
                'tempat_makan' => $rencana->tempatMakan ? [
                    'tempat_makan_id' => $rencana->tempatMakan->tempat_makan_id,
                    'kode' => $rencana->tempatMakan->kode,
                    'nama' => $rencana->tempatMakan->nama,
                ] : null,
                'items' => $prefillItems,
            ],
            'message' => 'Prefill food record dari rencana sarapan.',
        ]);
    }

    public function putFoodRecord(Request $request, string $tanggal): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktifOrFail($siswa);
        $tanggalTarget = $this->parseTanggalOrFail($tanggal);

        $this->pastikanTanggalDalamRentangProgram($tanggalTarget, $peserta);

        if ($tanggalTarget->gt(Carbon::today())) {
            throw ValidationException::withMessages([
                'tanggal' => ['Tanggal food record tidak boleh berada di masa depan.'],
            ]);
        }

        $tidakSarapan = $request->boolean('tidak_sarapan');

        $rules = [
            'tidak_sarapan' => ['required', 'boolean'],
            'jam_makan' => ['nullable', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
            'tempat_makan_id' => ['nullable', 'integer', 'exists:tempat_makan_m,tempat_makan_id'],
            'items' => $tidakSarapan ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
        ];

        if (! $tidakSarapan) {
            $rules = array_merge($rules, [
                'items.*.nama_makanan' => ['required', 'string', 'max:100'],
                'items.*.bahan_teks' => ['nullable', 'string', 'max:255'],
                'items.*.bahan_makanan_id' => ['nullable', 'integer', 'exists:bahan_makanan_m,bahan_makanan_id'],
                'items.*.urt_id' => ['nullable', 'integer', 'exists:urt_m,urt_id'],
                'items.*.jumlah_urt' => ['nullable', 'numeric', 'min:0'],
                'items.*.berat_gram' => ['nullable', 'numeric', 'min:0'],
                'items.*.keterangan' => ['nullable', 'string', 'max:255'],
                'items.*.status_realisasi_id' => ['required', 'integer', 'exists:status_realisasi_m,status_realisasi_id'],
                'items.*.rencana_sarapan_detail_id' => ['nullable', 'integer'],
            ]);
        }

        $validated = $request->validate($rules);

        $rencana = RencanaSarapan::query()
            ->with('rencanaSarapanDetail')
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->where('tanggal_sarapan', $tanggalTarget->toDateString())
            ->first();

        if (! $tidakSarapan && ! empty($validated['items'])) {
            $rencanaDetailIdsMilikSiswa = $rencana
                ? $rencana->rencanaSarapanDetail->pluck('rencana_sarapan_detail_id')->all()
                : [];

            $itemErrors = [];
            foreach (array_values($validated['items']) as $idx => $item) {
                if (! empty($item['rencana_sarapan_detail_id'])
                    && ! in_array((int) $item['rencana_sarapan_detail_id'], $rencanaDetailIdsMilikSiswa, true)
                ) {
                    $itemErrors["items.{$idx}.rencana_sarapan_detail_id"] = [
                        'Detail rencana sarapan tidak valid untuk siswa dan tanggal ini.',
                    ];
                }
            }

            if ($itemErrors !== []) {
                throw ValidationException::withMessages($itemErrors);
            }
        }

        $foodRecord = DB::transaction(function () use ($peserta, $rencana, $tanggalTarget, $validated, $tidakSarapan): FoodRecord {
            $record = FoodRecord::query()->updateOrCreate(
                [
                    'peserta_program_id' => $peserta->peserta_program_id,
                    'tanggal' => $tanggalTarget->toDateString(),
                ],
                [
                    'rencana_sarapan_id' => $rencana?->rencana_sarapan_id,
                    'jam_makan' => $tidakSarapan ? null : ($validated['jam_makan'] ?? null),
                    'tempat_makan_id' => $tidakSarapan ? null : ($validated['tempat_makan_id'] ?? null),
                    'tidak_sarapan' => $tidakSarapan,
                ],
            );

            $record->foodRecordDetail()->delete();

            if (! $tidakSarapan && ! empty($validated['items'])) {
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

            return $record->load([
                'tempatMakan',
                'foodRecordDetail' => fn ($q) => $q->orderBy('urutan')->with(['statusRealisasi', 'bahanMakanan', 'urt']),
            ]);
        });

        return response()->json([
            'data' => $this->formatFoodRecordTersimpan($foodRecord),
            'message' => 'Food record berhasil disimpan.',
        ]);
    }

    public function rekap(Request $request): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktifOrFail($siswa);
        $durasiMinggu = (int) ($peserta->program?->durasi_minggu ?? 3);

        $harianPerMinggu = KepatuhanHarian::query()
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->orderBy('tanggal')
            ->get()
            ->groupBy('minggu_ke');

        $ringkasanPerMinggu = KepatuhanMingguan::query()
            ->where('peserta_program_id', $peserta->peserta_program_id)
            ->orderBy('minggu_ke')
            ->get()
            ->keyBy('minggu_ke');

        $skorPengetahuan = SkorPengetahuan::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->first();

        $daftarMinggu = [];
        for ($minggu = 1; $minggu <= $durasiMinggu; $minggu++) {
            /** @var KepatuhanMingguan|null $ringkasan */
            $ringkasan = $ringkasanPerMinggu->get($minggu);
            $daftarHari = $harianPerMinggu->get($minggu, collect());

            $daftarMinggu[] = [
                'minggu_ke' => $minggu,
                'ringkasan' => [
                    'hari_berjalan' => (int) ($ringkasan?->hari_berjalan ?? 0),
                    'hari_tercatat' => (int) ($ringkasan?->hari_tercatat ?? 0),
                    'hari_sesuai' => (int) ($ringkasan?->hari_sesuai ?? 0),
                    'persen_kepatuhan' => $ringkasan?->persen_kepatuhan !== null
                        ? (float) $ringkasan->persen_kepatuhan
                        : 0.0,
                ],
                'hari' => $daftarHari->map(fn (KepatuhanHarian $baris): array => [
                    'tanggal' => $baris->tanggal?->toDateString(),
                    'status_kepatuhan' => $baris->status_kepatuhan,
                    'food_record_id' => $baris->food_record_id,
                ])->values()->all(),
            ];
        }

        return response()->json([
            'data' => [
                'minggu' => $daftarMinggu,
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
        $peserta = $this->getPesertaProgramAktifOrFail($siswa);
        $this->pastikanPosttestSudahDibuka($peserta);

        $kuesioner = Kuesioner::query()
            ->with([
                'soal' => fn ($query) => $query
                    ->orderBy('nomor')
                    ->with(['opsiJawaban' => fn ($oq) => $oq->orderBy('kode_opsi')]),
            ])
            ->where('jenis', 'POSTTEST')
            ->where('aktif', true)
            ->first();

        if (! $kuesioner || $kuesioner->soal->isEmpty()) {
            return response()->json([
                'data' => null,
                'message' => 'Tidak ada soal posttest aktif yang tersedia.',
            ], 422);
        }

        $daftarSoal = $kuesioner->soal->map(fn (Soal $soal): array => [
            'soal_id' => $soal->soal_id,
            'nomor' => $soal->nomor,
            'teks_soal' => $soal->teks_soal,
            'opsi' => $soal->opsiJawaban->map(fn (OpsiJawaban $opsi): array => [
                'opsi_jawaban_id' => $opsi->opsi_jawaban_id,
                'kode_opsi' => $opsi->kode_opsi,
                'teks_opsi' => $opsi->teks_opsi,
            ])->values()->all(),
        ])->values()->all();

        return response()->json([
            'data' => [
                'kuesioner_id' => $kuesioner->kuesioner_id,
                'judul' => $kuesioner->judul,
                'soal' => $daftarSoal,
            ],
            'message' => 'Daftar soal posttest.',
        ]);
    }

    public function submitPosttest(Request $request): JsonResponse
    {
        $siswa = $this->getSiswa($request);
        $peserta = $this->getPesertaProgramAktifOrFail($siswa);
        $this->pastikanPosttestSudahDibuka($peserta);

        $sudahMengirimPosttest = HasilKuesioner::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->whereNotNull('selesai_pada')
            ->whereHas('kuesioner', fn ($query) => $query->where('jenis', 'POSTTEST'))
            ->exists();

        if ($sudahMengirimPosttest) {
            return response()->json([
                'code' => 'POSTTEST_SUDAH_DIKIRIM',
                'data' => null,
                'message' => 'Posttest sudah pernah dikirim dan tidak dapat diulang.',
            ], 409);
        }

        $kuesioner = Kuesioner::query()
            ->with(['soal.opsiJawaban'])
            ->where('jenis', 'POSTTEST')
            ->where('aktif', true)
            ->first();

        if (! $kuesioner || $kuesioner->soal->isEmpty()) {
            return response()->json([
                'data' => null,
                'message' => 'Tidak ada soal posttest aktif yang tersedia.',
            ], 422);
        }

        $validated = $request->validate([
            'jawaban' => ['required', 'array', 'min:1'],
            'jawaban.*.soal_id' => ['required', 'integer', 'distinct'],
            'jawaban.*.opsi_jawaban_id' => ['required', 'integer'],
        ]);

        $soalMap = $kuesioner->soal->keyBy('soal_id');
        $errors = [];
        $soalTerjawabIds = [];
        $detailEvaluasi = [];

        foreach (array_values($validated['jawaban']) as $indeks => $item) {
            $soalId = (int) $item['soal_id'];
            $opsiId = (int) $item['opsi_jawaban_id'];

            /** @var Soal|null $soal */
            $soal = $soalMap->get($soalId);

            if (! $soal) {
                $errors["jawaban.{$indeks}.soal_id"] = ['Soal tidak termasuk dalam kuesioner posttest aktif.'];

                continue;
            }

            $soalTerjawabIds[] = $soalId;

            /** @var OpsiJawaban|null $opsi */
            $opsi = $soal->opsiJawaban->firstWhere('opsi_jawaban_id', $opsiId);

            if (! $opsi) {
                $errors["jawaban.{$indeks}.opsi_jawaban_id"] = ['Opsi jawaban bukan milik soal yang dipilih.'];

                continue;
            }

            $detailEvaluasi[] = [
                'soal_id' => $soalId,
                'opsi_jawaban_id' => $opsiId,
                'benar' => (bool) $opsi->getAttribute('benar'),
            ];
        }

        $soalBelumTerjawab = $soalMap->keys()->diff($soalTerjawabIds);
        if ($soalBelumTerjawab->isNotEmpty()) {
            $errors['jawaban'] = ['Semua soal posttest wajib dijawab lengkap.'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $hasil = DB::transaction(function () use ($siswa, $kuesioner, $detailEvaluasi): HasilKuesioner {
            $jumlahSoal = count($detailEvaluasi);
            $jumlahBenar = collect($detailEvaluasi)->where('benar', true)->count();
            $skor = round(($jumlahBenar / $jumlahSoal) * 100, 2);

            $hasilKuesioner = HasilKuesioner::query()->updateOrCreate(
                [
                    'siswa_id' => $siswa->siswa_id,
                    'kuesioner_id' => $kuesioner->kuesioner_id,
                ],
                [
                    'mulai_pada' => now(),
                    'selesai_pada' => null,
                    'jumlah_soal' => null,
                    'jumlah_benar' => null,
                    'skor' => null,
                ],
            );

            $hasilKuesioner->jawabanKuesioner()->delete();

            foreach ($detailEvaluasi as $evaluasi) {
                JawabanKuesioner::query()->create([
                    'hasil_kuesioner_id' => $hasilKuesioner->hasil_kuesioner_id,
                    'soal_id' => $evaluasi['soal_id'],
                    'opsi_jawaban_id' => $evaluasi['opsi_jawaban_id'],
                    'benar' => $evaluasi['benar'],
                ]);
            }

            $hasilKuesioner->update([
                'jumlah_soal' => $jumlahSoal,
                'jumlah_benar' => $jumlahBenar,
                'skor' => $skor,
                'selesai_pada' => now(),
            ]);

            return $hasilKuesioner;
        });

        return response()->json([
            'data' => [
                'skor' => (float) $hasil->skor,
                'jumlah_benar' => (int) $hasil->jumlah_benar,
                'jumlah_soal' => (int) $hasil->jumlah_soal,
            ],
            'message' => 'Posttest berhasil diselesaikan.',
        ]);
    }

    protected function getSiswa(Request $request): Siswa
    {
        return $request->user()->siswa()->firstOrFail();
    }

    protected function getPesertaProgramAktifOrFail(Siswa $siswa): PesertaProgram
    {
        $peserta = PesertaProgram::query()
            ->with('program')
            ->where('siswa_id', $siswa->siswa_id)
            ->where('status', 'AKTIF')
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (! $peserta) {
            throw new HttpResponseException(response()->json([
                'code' => 'PROGRAM_BELUM_ADA',
                'data' => null,
                'message' => 'Siswa belum memiliki keikutsertaan program aktif.',
            ], 409));
        }

        return $peserta;
    }

    protected function parseTanggalOrFail(string $tanggal): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            throw ValidationException::withMessages([
                'tanggal' => ['Format tanggal harus YYYY-MM-DD.'],
            ]);
        }

        try {
            $parsed = Carbon::createFromFormat('!Y-m-d', $tanggal);
        } catch (InvalidFormatException) {
            throw ValidationException::withMessages([
                'tanggal' => ['Tanggal tidak valid.'],
            ]);
        }

        if (! $parsed || $parsed->format('Y-m-d') !== $tanggal) {
            throw ValidationException::withMessages([
                'tanggal' => ['Tanggal tidak valid.'],
            ]);
        }

        return $parsed;
    }

    protected function pastikanTanggalDalamRentangProgram(Carbon $tanggal, PesertaProgram $peserta): void
    {
        $mulai = $peserta->tanggal_mulai->copy()->startOfDay();
        $selesai = $peserta->tanggal_selesai->copy()->startOfDay();

        if ($tanggal->lt($mulai) || $tanggal->gt($selesai)) {
            throw ValidationException::withMessages([
                'tanggal' => [
                    "Tanggal harus berada dalam rentang program ({$mulai->toDateString()} s/d {$selesai->toDateString()}).",
                ],
            ]);
        }
    }

    protected function pastikanPosttestSudahDibuka(PesertaProgram $peserta): void
    {
        $tanggalBuka = $peserta->tanggal_mulai->copy()->startOfDay()->addDays(21);

        if (Carbon::today()->lt($tanggalBuka)) {
            throw new HttpResponseException(response()->json([
                'code' => 'POSTTEST_BELUM_DIBUKA',
                'data' => null,
                'message' => 'Posttest baru dibuka setelah 21 hari sejak tanggal mulai program.',
                'tanggal_buka' => $tanggalBuka->toDateString(),
            ], 403));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatRencana(RencanaSarapan $rencana): array
    {
        return [
            'rencana_sarapan_id' => $rencana->rencana_sarapan_id,
            'tanggal_sarapan' => $rencana->tanggal_sarapan?->toDateString(),
            'jam_rencana' => $this->formatJam($rencana->jam_rencana),
            'tempat_makan_id' => $rencana->tempat_makan_id,
            'tempat_makan' => $rencana->tempatMakan ? [
                'tempat_makan_id' => $rencana->tempatMakan->tempat_makan_id,
                'kode' => $rencana->tempatMakan->kode,
                'nama' => $rencana->tempatMakan->nama,
            ] : null,
            'items' => $rencana->rencanaSarapanDetail->map(fn (RencanaSarapanDetail $detail): array => [
                'rencana_sarapan_detail_id' => $detail->rencana_sarapan_detail_id,
                'nama_makanan' => $detail->nama_makanan,
                'bahan_makanan_id' => $detail->bahan_makanan_id,
                'nama_bahan' => $detail->bahanMakanan?->nama_bahan,
                'urt_id' => $detail->urt_id,
                'nama_urt' => $detail->urt?->nama_urt,
                'jumlah_urt' => $detail->jumlah_urt !== null ? (float) $detail->jumlah_urt : null,
                'urutan' => $detail->urutan,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatFoodRecordTersimpan(FoodRecord $foodRecord): array
    {
        return [
            'dari_rencana' => false,
            'food_record_id' => $foodRecord->food_record_id,
            'rencana_sarapan_id' => $foodRecord->rencana_sarapan_id,
            'tanggal' => $foodRecord->tanggal?->toDateString(),
            'tidak_sarapan' => (bool) $foodRecord->tidak_sarapan,
            'jam_makan' => $this->formatJam($foodRecord->jam_makan),
            'tempat_makan_id' => $foodRecord->tempat_makan_id,
            'tempat_makan' => $foodRecord->tempatMakan ? [
                'tempat_makan_id' => $foodRecord->tempatMakan->tempat_makan_id,
                'kode' => $foodRecord->tempatMakan->kode,
                'nama' => $foodRecord->tempatMakan->nama,
            ] : null,
            'items' => $foodRecord->foodRecordDetail->map(fn (FoodRecordDetail $detail): array => [
                'food_record_detail_id' => $detail->food_record_detail_id,
                'rencana_sarapan_detail_id' => $detail->rencana_sarapan_detail_id,
                'status_realisasi_id' => $detail->status_realisasi_id,
                'status_realisasi' => $detail->statusRealisasi ? [
                    'status_realisasi_id' => $detail->statusRealisasi->status_realisasi_id,
                    'kode' => $detail->statusRealisasi->kode,
                    'nama' => $detail->statusRealisasi->nama,
                ] : null,
                'nama_makanan' => $detail->nama_makanan,
                'bahan_teks' => $detail->bahan_teks,
                'bahan_makanan_id' => $detail->bahan_makanan_id,
                'urt_id' => $detail->urt_id,
                'nama_urt' => $detail->urt?->nama_urt,
                'jumlah_urt' => $detail->jumlah_urt !== null ? (float) $detail->jumlah_urt : null,
                'berat_gram' => $detail->berat_gram !== null ? (float) $detail->berat_gram : null,
                'keterangan' => $detail->keterangan,
                'urutan' => $detail->urutan,
            ])->values()->all(),
        ];
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

    protected function formatJam(?string $jam): ?string
    {
        if ($jam === null || $jam === '') {
            return null;
        }

        return substr($jam, 0, 5);
    }
}
