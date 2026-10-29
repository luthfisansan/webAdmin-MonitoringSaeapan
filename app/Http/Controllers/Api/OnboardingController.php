<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BacaMateri;
use App\Models\HasilKuesioner;
use App\Models\JawabanKuesioner;
use App\Models\Kuesioner;
use App\Models\MateriEdukasi;
use App\Models\OpsiJawaban;
use App\Models\ProgresEdukasi;
use App\Models\Soal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnboardingController extends Controller
{
    public function pretest(Request $request): JsonResponse
    {
        $kuesioner = Kuesioner::query()
            ->with([
                'soal' => fn ($query) => $query
                    ->orderBy('nomor')
                    ->with(['opsiJawaban' => fn ($oq) => $oq->orderBy('kode_opsi')]),
            ])
            ->where('jenis', 'PRETEST')
            ->where('aktif', true)
            ->first();

        if (! $kuesioner || $kuesioner->soal->isEmpty()) {
            return response()->json([
                'data' => null,
                'message' => 'Tidak ada soal pretest aktif yang tersedia.',
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
            'message' => 'Daftar soal pretest.',
        ]);
    }

    public function submitPretest(Request $request): JsonResponse
    {
        $siswa = $request->user()->siswa()->with('statusAkun')->firstOrFail();

        $sudahMengirimPretest = HasilKuesioner::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->whereNotNull('selesai_pada')
            ->whereHas('kuesioner', fn ($query) => $query->where('jenis', 'PRETEST'))
            ->exists();

        if ($sudahMengirimPretest) {
            return response()->json([
                'code' => 'PRETEST_SUDAH_DIKIRIM',
                'data' => null,
                'message' => 'Pretest sudah pernah dikirim dan tidak dapat diulang.',
            ], 409);
        }

        $kodeStatusSaatIni = $siswa->statusAkun?->kode;
        if ($kodeStatusSaatIni !== 'PRETEST') {
            return response()->json([
                'code' => 'STATUS_TIDAK_SESUAI',
                'message' => 'Status akun siswa tidak sesuai untuk mengirim pretest.',
                'status_akun' => $kodeStatusSaatIni,
            ], 403);
        }

        $kuesioner = Kuesioner::query()
            ->with(['soal.opsiJawaban'])
            ->where('jenis', 'PRETEST')
            ->where('aktif', true)
            ->first();

        if (! $kuesioner || $kuesioner->soal->isEmpty()) {
            return response()->json([
                'data' => null,
                'message' => 'Tidak ada soal pretest aktif yang tersedia.',
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
                $errors["jawaban.{$indeks}.soal_id"] = ['Soal tidak termasuk dalam kuesioner pretest aktif.'];

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
            $errors['jawaban'] = ['Semua soal pretest wajib dijawab lengkap.'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $hasil = DB::transaction(function () use ($siswa, $kuesioner, $detailEvaluasi): HasilKuesioner {
            $jumlahSoal = count($detailEvaluasi);
            $jumlahBenar = collect($detailEvaluasi)->where('benar', true)->count();
            $skor = round(($jumlahBenar / $jumlahSoal) * 100, 2);

            // 1. Buat baris hasil_kuesioner_t terlebih dahulu dengan selesai_pada = null
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

            // 2. Isi jawaban_kuesioner_t beserta kolom benar yang dinilai di server
            $hasilKuesioner->jawabanKuesioner()->delete();

            foreach ($detailEvaluasi as $evaluasi) {
                JawabanKuesioner::query()->create([
                    'hasil_kuesioner_id' => $hasilKuesioner->hasil_kuesioner_id,
                    'soal_id' => $evaluasi['soal_id'],
                    'opsi_jawaban_id' => $evaluasi['opsi_jawaban_id'],
                    'benar' => $evaluasi['benar'],
                ]);
            }

            // 3. Simpan skor & TERAKHIR set selesai_pada = now() agar trigger DB memindahkan status ke EDUKASI
            $hasilKuesioner->update([
                'jumlah_soal' => $jumlahSoal,
                'jumlah_benar' => $jumlahBenar,
                'skor' => $skor,
                'selesai_pada' => now(),
            ]);

            return $hasilKuesioner;
        });

        $siswa->refresh()->load('statusAkun');

        return response()->json([
            'data' => [
                'skor' => (float) $hasil->skor,
                'jumlah_benar' => (int) $hasil->jumlah_benar,
                'jumlah_soal' => (int) $hasil->jumlah_soal,
                'status_akun' => [
                    'kode' => $siswa->statusAkun?->kode,
                    'nama' => $siswa->statusAkun?->nama,
                ],
            ],
            'message' => 'Pretest berhasil diselesaikan.',
        ]);
    }

    public function daftarMateri(Request $request): JsonResponse
    {
        $siswa = $request->user()->siswa()->with('statusAkun')->firstOrFail();

        $bacaMap = BacaMateri::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->get()
            ->keyBy('materi_edukasi_id');

        $daftarMateri = MateriEdukasi::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('materi_edukasi_id')
            ->get()
            ->map(function (MateriEdukasi $materi) use ($bacaMap): array {
                $baca = $bacaMap->get($materi->materi_edukasi_id);

                return [
                    'materi_edukasi_id' => $materi->materi_edukasi_id,
                    'judul' => $materi->judul,
                    'ringkasan' => $materi->ringkasan,
                    'urutan' => $materi->urutan,
                    'wajib' => (bool) $materi->wajib,
                    'selesai' => (bool) ($baca?->selesai ?? false),
                ];
            })
            ->values()
            ->all();

        $progres = ProgresEdukasi::query()->where('siswa_id', $siswa->siswa_id)->first();

        return response()->json([
            'data' => $daftarMateri,
            'meta' => [
                'total_wajib' => (int) ($progres?->total_wajib ?? 0),
                'dibaca_wajib' => (int) ($progres?->dibaca ?? 0),
                'status_akun' => [
                    'kode' => $siswa->statusAkun?->kode,
                    'nama' => $siswa->statusAkun?->nama,
                ],
            ],
            'message' => 'Daftar materi edukasi.',
        ]);
    }

    public function detailMateri(Request $request, int $materi_edukasi_id): JsonResponse
    {
        $siswa = $request->user()->siswa()->firstOrFail();

        $materi = MateriEdukasi::query()
            ->where('aktif', true)
            ->findOrFail($materi_edukasi_id);

        $baca = BacaMateri::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->where('materi_edukasi_id', $materi->materi_edukasi_id)
            ->first();

        return response()->json([
            'data' => [
                'materi_edukasi_id' => $materi->materi_edukasi_id,
                'judul' => $materi->judul,
                'ringkasan' => $materi->ringkasan,
                'konten' => $materi->konten,
                'urutan' => $materi->urutan,
                'wajib' => (bool) $materi->wajib,
                'selesai' => (bool) ($baca?->selesai ?? false),
                'selesai_pada' => $baca?->selesai_pada?->toIso8601String(),
            ],
            'message' => 'Detail materi edukasi.',
        ]);
    }

    public function selesaiMateri(Request $request, int $materi_edukasi_id): JsonResponse
    {
        $siswa = $request->user()->siswa()->firstOrFail();

        $materi = MateriEdukasi::query()
            ->where('aktif', true)
            ->findOrFail($materi_edukasi_id);

        $baca = BacaMateri::query()->firstOrNew([
            'siswa_id' => $siswa->siswa_id,
            'materi_edukasi_id' => $materi->materi_edukasi_id,
        ]);

        if (! $baca->exists) {
            $baca->mulai_pada = now();
        }

        $baca->selesai = true;
        $baca->selesai_pada = $baca->selesai_pada ?? now();
        $baca->save();

        $siswa->refresh()->load('statusAkun');
        $progres = ProgresEdukasi::query()->where('siswa_id', $siswa->siswa_id)->first();

        return response()->json([
            'data' => [
                'status_akun' => [
                    'kode' => $siswa->statusAkun?->kode,
                    'nama' => $siswa->statusAkun?->nama,
                ],
                'total_wajib' => (int) ($progres?->total_wajib ?? 0),
                'dibaca_wajib' => (int) ($progres?->dibaca ?? 0),
            ],
            'message' => 'Materi edukasi ditandai selesai dibaca.',
        ]);
    }

    public function progresEdukasi(Request $request): JsonResponse
    {
        $siswa = $request->user()->siswa()->with('statusAkun')->firstOrFail();
        $progres = ProgresEdukasi::query()->where('siswa_id', $siswa->siswa_id)->first();

        return response()->json([
            'data' => [
                'siswa_id' => $siswa->siswa_id,
                'total_wajib' => (int) ($progres?->total_wajib ?? 0),
                'dibaca_wajib' => (int) ($progres?->dibaca ?? 0),
                'selesai' => (bool) ($progres?->selesai ?? false),
                'status_akun' => [
                    'kode' => $siswa->statusAkun?->kode,
                    'nama' => $siswa->statusAkun?->nama,
                ],
            ],
            'message' => 'Progres membaca materi edukasi wajib.',
        ]);
    }
}
