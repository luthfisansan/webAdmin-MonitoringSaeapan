<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\SqffqSkor;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPerKelas extends Page
{
    protected static ?string $title = 'Laporan per Kelas';

    protected static ?string $navigationLabel = 'Laporan per Kelas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $slug = 'laporan-per-kelas';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.laporan-per-kelas';

    #[Url(as: 'sekolah_id', except: null)]
    public ?int $sekolahId = null;

    #[Url(as: 'kelas_id', except: null)]
    public ?int $kelasId = null;

    public string $cari = '';

    public function mount(): void
    {
        if ($this->sekolahId === null) {
            $sekolahPertama = Sekolah::query()->where('aktif', true)->orderBy('nama_sekolah')->first();
            $this->sekolahId = $sekolahPertama?->sekolah_id;
        }

        if ($this->kelasId === null && $this->sekolahId) {
            $kelasPertama = Kelas::query()
                ->where('sekolah_id', $this->sekolahId)
                ->where('aktif', true)
                ->orderBy('nama_kelas')
                ->first();
            $this->kelasId = $kelasPertama?->kelas_id;
        }
    }

    public function updatedSekolahId(): void
    {
        $kelasPertama = Kelas::query()
            ->where('sekolah_id', $this->sekolahId)
            ->where('aktif', true)
            ->orderBy('nama_kelas')
            ->first();

        $this->kelasId = $kelasPertama?->kelas_id;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Rekap individual seluruh siswa dalam satu kelas · Pengetahuan gizi, kecenderungan sarapan mingguan (sikap konatif), dan skor pangan SQ-FFQ';
    }

    public function unduhCsv(): StreamedResponse
    {
        $kelas = Kelas::query()->with('sekolah')->find($this->kelasId);
        $namaKelasClean = $kelas ? str_replace(' ', '_', $kelas->nama_kelas) : 'kelas';
        $namaSekolahClean = $kelas?->sekolah ? str_replace(' ', '_', $kelas->sekolah->nama_sekolah) : 'sekolah';
        $filename = "laporan_kelas_{$namaSekolahClean}_{$namaKelasClean}_".now()->format('Ymd_His').'.csv';

        $siswaRows = $this->ambilDataSiswaKelas();

        return response()->streamDownload(function () use ($kelas, $siswaRows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, ['LAPORAN PER KELAS — REKAP INDIVIDUAL SISWA (PENGETAHUAN & SIKAP KONATIF SARAPAN)']);
            fputcsv($handle, ['Sekolah', $kelas?->sekolah?->nama_sekolah ?? '-']);
            fputcsv($handle, ['Kelas', $kelas?->nama_kelas ?? '-']);
            fputcsv($handle, ['Tahun Ajaran', $kelas?->tahun_ajaran ?? '-']);
            fputcsv($handle, ['Tanggal Unduh', now()->translatedFormat('d F Y H:i').' WIB']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No.',
                'Nama Siswa',
                'ID Siswa',
                'L/P',
                'Status Akun',
                'Skor Pretest',
                'Skor Posttest',
                'Peningkatan (Selisih)',
                'Kecenderungan M1 (%)',
                'Kecenderungan M2 (%)',
                'Kecenderungan M3 (%)',
                'Rata-rata Kecenderungan (%)',
                'Skor SQ-FFQ',
                'Kategori SQ-FFQ',
            ]);

            foreach ($siswaRows as $idx => $row) {
                fputcsv($handle, [
                    $idx + 1,
                    $row['nama_lengkap'],
                    '#'.$row['siswa_id'],
                    $row['jenis_kelamin'],
                    $row['status_akun_nama'],
                    $row['skor_pretest'] !== null ? $row['skor_pretest'] : '-',
                    $row['skor_posttest'] !== null ? $row['skor_posttest'] : '-',
                    $row['selisih'] !== null ? ($row['selisih'] > 0 ? '+'.$row['selisih'] : $row['selisih']) : '-',
                    $row['m1_persen'] !== null ? $row['m1_persen'].'%' : '-',
                    $row['m2_persen'] !== null ? $row['m2_persen'].'%' : '-',
                    $row['m3_persen'] !== null ? $row['m3_persen'].'%' : '-',
                    $row['rata_kecenderungan'] !== null ? $row['rata_kecenderungan'].'%' : '-',
                    $row['sqffq_skor'] !== null ? $row['sqffq_skor'] : '-',
                    $row['sqffq_kategori'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function ambilDataSiswaKelas(): array
    {
        if (! $this->kelasId) {
            return [];
        }

        $daftarSiswa = Siswa::query()
            ->with(['pengguna', 'kelas.sekolah', 'statusAkun', 'skorPengetahuan', 'kepatuhanMingguan'])
            ->where('kelas_id', $this->kelasId)
            ->when($this->cari !== '', function ($query): void {
                $query->whereHas('pengguna', fn ($pq) => $pq->where('nama_lengkap', 'ilike', '%'.$this->cari.'%'));
            })
            ->get();

        $siswaIds = $daftarSiswa->pluck('siswa_id')->all();
        $sqffqMap = SqffqSkor::query()
            ->whereIn('siswa_id', $siswaIds)
            ->get()
            ->keyBy('siswa_id');

        return $daftarSiswa->map(function (Siswa $siswa) use ($sqffqMap): array {
            $km1 = $siswa->kepatuhanMingguan->firstWhere('minggu_ke', 1);
            $km2 = $siswa->kepatuhanMingguan->firstWhere('minggu_ke', 2);
            $km3 = $siswa->kepatuhanMingguan->firstWhere('minggu_ke', 3);

            $validMingguan = collect([
                $km1?->persen_kepatuhan,
                $km2?->persen_kepatuhan,
                $km3?->persen_kepatuhan,
            ])->filter(fn ($v) => $v !== null);

            $rataKecenderungan = $validMingguan->isNotEmpty()
                ? round((float) $validMingguan->avg(), 1)
                : null;

            $sqffq = $sqffqMap->get($siswa->siswa_id);
            $totalSkorSqffq = $sqffq?->total_skor !== null ? (float) $sqffq->total_skor : null;
            $kategoriSqffq = '-';
            if ($totalSkorSqffq !== null) {
                if ($totalSkorSqffq >= 80) {
                    $kategoriSqffq = 'Baik';
                } elseif ($totalSkorSqffq >= 60) {
                    $kategoriSqffq = 'Cukup';
                } else {
                    $kategoriSqffq = 'Kurang';
                }
            }

            return [
                'siswa_id' => $siswa->siswa_id,
                'nama_lengkap' => $siswa->nama_lengkap ?? 'Tanpa Nama',
                'email' => $siswa->pengguna?->email ?? '-',
                'jenis_kelamin' => $siswa->jenis_kelamin ?? '-',
                'status_akun_kode' => $siswa->statusAkun?->kode ?? 'PRETEST',
                'status_akun_nama' => $siswa->statusAkun?->nama ?? 'Belum pretest',
                'skor_pretest' => $siswa->skorPengetahuan?->skor_pretest !== null ? (float) $siswa->skorPengetahuan->skor_pretest : null,
                'skor_posttest' => $siswa->skorPengetahuan?->skor_posttest !== null ? (float) $siswa->skorPengetahuan->skor_posttest : null,
                'selisih' => $siswa->skorPengetahuan?->selisih !== null ? (float) $siswa->skorPengetahuan->selisih : null,
                'm1_persen' => $km1?->persen_kepatuhan !== null ? (float) $km1->persen_kepatuhan : null,
                'm1_sesuai' => $km1?->hari_sesuai ?? 0,
                'm1_berjalan' => $km1?->hari_berjalan ?? 0,
                'm2_persen' => $km2?->persen_kepatuhan !== null ? (float) $km2->persen_kepatuhan : null,
                'm2_sesuai' => $km2?->hari_sesuai ?? 0,
                'm2_berjalan' => $km2?->hari_berjalan ?? 0,
                'm3_persen' => $km3?->persen_kepatuhan !== null ? (float) $km3->persen_kepatuhan : null,
                'm3_sesuai' => $km3?->hari_sesuai ?? 0,
                'm3_berjalan' => $km3?->hari_berjalan ?? 0,
                'rata_kecenderungan' => $rataKecenderungan,
                'sqffq_skor' => $totalSkorSqffq,
                'sqffq_kategori' => $kategoriSqffq,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $daftarSekolah = Sekolah::query()->where('aktif', true)->orderBy('nama_sekolah')->get();

        $daftarKelas = Kelas::query()
            ->where('aktif', true)
            ->when($this->sekolahId, fn ($q) => $q->where('sekolah_id', $this->sekolahId))
            ->orderBy('nama_kelas')
            ->get();

        $kelasTerpilih = $this->kelasId ? Kelas::query()->with('sekolah')->find($this->kelasId) : null;
        $siswaRows = $this->ambilDataSiswaKelas();

        $totalSiswa = count($siswaRows);
        $kumpulanPretest = collect($siswaRows)->pluck('skor_pretest')->filter(fn ($v) => $v !== null);
        $kumpulanPosttest = collect($siswaRows)->pluck('skor_posttest')->filter(fn ($v) => $v !== null);
        $kumpulanSelisih = collect($siswaRows)->pluck('selisih')->filter(fn ($v) => $v !== null);
        $kumpulanKecenderungan = collect($siswaRows)->pluck('rata_kecenderungan')->filter(fn ($v) => $v !== null);
        $kumpulanSqffq = collect($siswaRows)->pluck('sqffq_skor')->filter(fn ($v) => $v !== null);

        $ringkasan = [
            'total_siswa' => $totalSiswa,
            'rata_pretest' => $kumpulanPretest->isNotEmpty() ? round($kumpulanPretest->avg(), 1) : null,
            'rata_posttest' => $kumpulanPosttest->isNotEmpty() ? round($kumpulanPosttest->avg(), 1) : null,
            'rata_selisih' => $kumpulanSelisih->isNotEmpty() ? round($kumpulanSelisih->avg(), 1) : null,
            'rata_kecenderungan' => $kumpulanKecenderungan->isNotEmpty() ? round($kumpulanKecenderungan->avg(), 1) : null,
            'rata_sqffq' => $kumpulanSqffq->isNotEmpty() ? round($kumpulanSqffq->avg(), 1) : null,
        ];

        return [
            'daftarSekolah' => $daftarSekolah,
            'daftarKelas' => $daftarKelas,
            'kelasTerpilih' => $kelasTerpilih,
            'siswaRows' => $siswaRows,
            'ringkasan' => $ringkasan,
        ];
    }
}
