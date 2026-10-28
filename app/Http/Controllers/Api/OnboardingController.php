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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function pretest(Request $request): JsonResponse
    {
        $siswa = $request->user()?->siswa()->with('statusAkun')->first();

        if (! $siswa || $siswa->statusAkun?->kode !== 'PRETEST') {
            return response()->json([
                'data' => null,
                'message' => 'Pretest hanya dapat diakses oleh siswa berstatus PRETEST.',
            ], 403);
        }

        $kuesioner = Kuesioner::query()
            ->with(['soal' => fn ($query) => $query->orderBy('nomor')->with(['opsiJawaban' => fn ($oq) => $oq->orderBy('kode_opsi')])])
            ->where('jenis', 'PRETEST')
            ->where('aktif', true)
            ->first();

        if (! $kuesioner) {
            return response()->json([
                'data' => null,
                'message' => 'Kuesioner pretest aktif belum tersedia.',
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
            'message' => 'Daftar soal pretest.',
        ]);
    }

    public function submitPretest(Request $request): JsonResponse
    {
        $siswa = $request->user()?->siswa()->with('statusAkun')->first();

        if (! $siswa || $siswa->statusAkun?->kode !== 'PRETEST') {
            return response()->json([
                'data' => null,
                'message' => 'Pretest hanya dapat dikirim oleh siswa berstatus PRETEST.',
            ], 403);
        }

        $validated = $request->validate([
            'jawaban' => ['required', 'array', 'min:1'],
            'jawaban.*.soal_id' => ['required', 'integer', 'exists:soal_m,soal_id'],
            'jawaban.*.opsi_jawaban_id' => ['required', 'integer', 'exists:opsi_jawaban_m,opsi_jawaban_id'],
        ]);

        $kuesioner = Kuesioner::query()
            ->with('soal')
            ->where('jenis', 'PRETEST')
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

            // Sesuai aturan skema: buat baris dulu tanpa selesai_pada, simpan jawaban & skor, lalu UPDATE selesai_pada
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

            // Memicu trigger hasil_kuesioner_selesai_trg -> PRETEST ke EDUKASI
            $hasilKuesioner->update([
                'selesai_pada' => now(),
            ]);

            return $hasilKuesioner;
        });

        $siswa->refresh()->load('statusAkun');

        return response()->json([
            'data' => [
                'hasil_kuesioner_id' => $hasil->hasil_kuesioner_id,
                'jumlah_soal' => $hasil->jumlah_soal,
                'jumlah_benar' => $hasil->jumlah_benar,
                'skor' => (float) $hasil->skor,
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
        $siswa = $request->user()?->siswa()->firstOrFail();

        $bacaMap = BacaMateri::query()
            ->where('siswa_id', $siswa->siswa_id)
            ->get()
            ->keyBy('materi_edukasi_id');

        $materiList = MateriEdukasi::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get()
            ->map(function (MateriEdukasi $materi) use ($bacaMap): array {
                $baca = $bacaMap->get($materi->materi_edukasi_id);

                return [
                    'materi_edukasi_id' => $materi->materi_edukasi_id,
                    'judul' => $materi->judul,
                    'ringkasan' => $materi->ringkasan,
                    'urutan' => $materi->urutan,
                    'wajib' => $materi->wajib,
                    'selesai' => (bool) ($baca?->selesai ?? false),
                    'selesai_pada' => $baca?->selesai_pada?->toIso8601String(),
                ];
            });

        $progres = ProgresEdukasi::query()->where('siswa_id', $siswa->siswa_id)->first();

        return response()->json([
            'data' => [
                'materi' => $materiList,
                'progres' => [
                    'total_wajib' => $progres?->total_wajib ?? 0,
                    'dibaca' => $progres?->dibaca ?? 0,
                    'selesai' => (bool) ($progres?->selesai ?? false),
                ],
            ],
            'message' => 'Daftar materi edukasi.',
        ]);
    }

    public function detailMateri(Request $request, int $materi_edukasi_id): JsonResponse
    {
        $siswa = $request->user()?->siswa()->firstOrFail();

        $materi = MateriEdukasi::query()
            ->where('aktif', true)
            ->findOrFail($materi_edukasi_id);

        $baca = BacaMateri::query()->firstOrCreate(
            [
                'siswa_id' => $siswa->siswa_id,
                'materi_edukasi_id' => $materi->materi_edukasi_id,
            ],
            [
                'selesai' => false,
                'mulai_pada' => now(),
            ],
        );

        return response()->json([
            'data' => [
                'materi_edukasi_id' => $materi->materi_edukasi_id,
                'judul' => $materi->judul,
                'ringkasan' => $materi->ringkasan,
                'konten' => $materi->konten,
                'urutan' => $materi->urutan,
                'wajib' => $materi->wajib,
                'selesai' => (bool) $baca->selesai,
                'selesai_pada' => $baca->selesai_pada?->toIso8601String(),
            ],
            'message' => 'Detail materi edukasi.',
        ]);
    }

    public function selesaiMateri(Request $request, int $materi_edukasi_id): JsonResponse
    {
        $siswa = $request->user()?->siswa()->firstOrFail();

        $materi = MateriEdukasi::query()
            ->where('aktif', true)
            ->findOrFail($materi_edukasi_id);

        BacaMateri::query()->updateOrCreate(
            [
                'siswa_id' => $siswa->siswa_id,
                'materi_edukasi_id' => $materi->materi_edukasi_id,
            ],
            [
                'selesai' => true,
                'selesai_pada' => now(),
            ],
        );

        $siswa->refresh()->load('statusAkun');
        $progres = ProgresEdukasi::query()->where('siswa_id', $siswa->siswa_id)->first();

        return response()->json([
            'data' => [
                'materi_edukasi_id' => $materi->materi_edukasi_id,
                'selesai' => true,
                'progres' => [
                    'total_wajib' => $progres?->total_wajib ?? 0,
                    'dibaca' => $progres?->dibaca ?? 0,
                    'selesai' => (bool) ($progres?->selesai ?? false),
                ],
                'status_akun' => [
                    'kode' => $siswa->statusAkun?->kode,
                    'nama' => $siswa->statusAkun?->nama,
                ],
            ],
            'message' => 'Materi edukasi ditandai selesai dibaca.',
        ]);
    }

    public function progresEdukasi(Request $request): JsonResponse
    {
        $siswa = $request->user()?->siswa()->with('statusAkun')->firstOrFail();
        $progres = ProgresEdukasi::query()->where('siswa_id', $siswa->siswa_id)->first();

        return response()->json([
            'data' => [
                'siswa_id' => $siswa->siswa_id,
                'total_wajib' => $progres?->total_wajib ?? 0,
                'dibaca' => $progres?->dibaca ?? 0,
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

