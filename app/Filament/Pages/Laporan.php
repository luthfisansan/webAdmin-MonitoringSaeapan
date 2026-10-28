<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\KelompokBahan;
use App\Models\LaporanKelas;
use App\Models\PolaKonsumsiKelompok;
use App\Models\SqffqSkor;
use App\Models\TempatMakan;
use App\Models\TempatSarapan;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Laporan extends Page
{
    protected static ?string $title = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $slug = 'laporan';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.laporan';

    #[Url(as: 'kelas_id', except: null)]
    public ?int $kelasId = null;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Rekap seluruh peserta · pengetahuan gizi, kepatuhan rencana, pola konsumsi SQ-FFQ, dan tempat sarapan';
    }

    public function unduhExcelCsv(): StreamedResponse
    {
        $laporanKelas = LaporanKelas::query()
            ->when($this->kelasId, fn ($query) => $query->where('kelas_id', $this->kelasId))
            ->orderBy('nama_kelas')
            ->get();

        $polaKonsumsi = PolaKonsumsiKelompok::query()
            ->when($this->kelasId, fn ($query) => $query->where('kelas_id', $this->kelasId))
            ->orderBy('kelompok_kode')
            ->get();

        $tempatSarapan = TempatSarapan::query()
            ->when($this->kelasId, fn ($query) => $query->where('kelas_id', $this->kelasId))
            ->get();

        $sqffqSkor = SqffqSkor::query()
            ->with(['siswa.pengguna', 'siswa.kelas', 'sqffq'])
            ->when($this->kelasId, fn ($query) => $query->whereHas('siswa', fn ($sq) => $sq->where('kelas_id', $this->kelasId)))
            ->get();

        return response()->streamDownload(function () use ($laporanKelas, $polaKonsumsi, $tempatSarapan, $sqffqSkor): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['1. LAPORAN PER KELAS (PENGETAHUAN & KEPATUHAN MINGGUAN)']);
            fputcsv($handle, [
                'Kelas',
                'Jumlah Siswa',
                'Rata-rata Pretest',
                'Rata-rata Post Test',
                'Rata-rata Selisih',
                'Kepatuhan Minggu 1 (%)',
                'Kepatuhan Minggu 2 (%)',
                'Kepatuhan Minggu 3 (%)',
            ]);

            foreach ($laporanKelas as $row) {
                fputcsv($handle, [
                    $row->nama_kelas,
                    $row->jumlah_siswa,
                    $row->rata_pretest ?? 0,
                    $row->rata_posttest ?? 0,
                    $row->rata_selisih ?? 0,
                    $row->kepatuhan_minggu_1 ?? 0,
                    $row->kepatuhan_minggu_2 ?? 0,
                    $row->kepatuhan_minggu_3 ?? 0,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['2. POLA KONSUMSI KELOMPOK BAHAN MAKANAN (>=3X/MINGGU)']);
            fputcsv($handle, ['Kode Kelompok', 'Nama Kelompok', 'Responden', 'Jumlah Sering (>=3x/mgg)', 'Persentase (%)']);

            foreach ($polaKonsumsi as $row) {
                fputcsv($handle, [
                    $row->kelompok_kode,
                    $row->kelompok_nama,
                    $row->jumlah_responden,
                    $row->jumlah_sering,
                    $row->persen_sering_3x_minggu ?? 0,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['3. TEMPAT SARAPAN (FOOD RECORD)']);
            fputcsv($handle, ['Tempat Sarapan', 'Jumlah Catatan', 'Persentase (%)']);

            foreach ($tempatSarapan as $row) {
                fputcsv($handle, [
                    $row->tempat,
                    $row->jumlah_catatan,
                    $row->persen ?? 0,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['4. REKAP SKOR KONSUMSI PANGAN SQ-FFQ PER SISWA']);
            fputcsv($handle, ['Nama Siswa', 'Kelas', 'Tahap SQ-FFQ', 'Jumlah Bahan Terisi', 'Total Skor Pangan']);

            foreach ($sqffqSkor as $row) {
                fputcsv($handle, [
                    $row->siswa?->pengguna?->nama_lengkap ?? '-',
                    $row->siswa?->kelas?->nama_kelas ?? '-',
                    $row->sqffq?->tahap ?? 'AWAL',
                    $row->jumlah_terisi,
                    $row->total_skor ?? 0,
                ]);
            }

            fclose($handle);
        }, 'laporan-penelitian-breakfast-planner.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $daftarKelas = Kelas::query()->where('aktif', true)->orderBy('nama_kelas')->get();

        $laporanKelasList = LaporanKelas::query()
            ->when($this->kelasId, fn ($query) => $query->where('kelas_id', $this->kelasId))
            ->orderBy('nama_kelas')
            ->get();

        $kelompokMaster = KelompokBahan::query()->orderBy('urutan')->get();
        $polaRows = PolaKonsumsiKelompok::query()
            ->when($this->kelasId, fn ($query) => $query->where('kelas_id', $this->kelasId))
            ->get()
            ->groupBy('kelompok_bahan_id');

        $polaKonsumsiList = $kelompokMaster->map(function (KelompokBahan $kelompok) use ($polaRows): array {
            $rows = $polaRows->get($kelompok->kelompok_bahan_id, collect());
            $totalResponden = (int) $rows->sum('jumlah_responden');
            $totalSering = (int) $rows->sum('jumlah_sering');
            $persen = $totalResponden > 0
                ? (int) round(($totalSering / $totalResponden) * 100)
                : 0;

            return [
                'kode' => $kelompok->kode,
                'nama' => $kelompok->nama,
                'persen' => $persen,
            ];
        });

        $tempatMaster = TempatMakan::query()->orderBy('tempat_makan_id')->get();
        $tempatRows = TempatSarapan::query()
            ->when($this->kelasId, fn ($query) => $query->where('kelas_id', $this->kelasId))
            ->get();

        $totalCatatanTempat = (int) $tempatRows->sum('jumlah_catatan');
        $tempatGrouped = $tempatRows->groupBy('tempat_makan_id');

        $tempatSarapanList = $tempatMaster->map(function (TempatMakan $tempat) use ($tempatGrouped, $totalCatatanTempat): array {
            $rows = $tempatGrouped->get($tempat->tempat_makan_id, collect());
            $jml = (int) $rows->sum('jumlah_catatan');
            $persen = $totalCatatanTempat > 0
                ? (int) round(($jml / $totalCatatanTempat) * 100)
                : 0;

            return [
                'nama' => $tempat->nama,
                'jumlah' => $jml,
                'persen' => $persen,
            ];
        });

        $sqffqSkorList = SqffqSkor::query()
            ->with(['siswa.pengguna', 'siswa.kelas', 'sqffq'])
            ->when($this->kelasId, fn ($query) => $query->whereHas('siswa', fn ($sq) => $sq->where('kelas_id', $this->kelasId)))
            ->get();

        return [
            'daftarKelas' => $daftarKelas,
            'laporanKelasList' => $laporanKelasList,
            'polaKonsumsiList' => $polaKonsumsiList,
            'tempatSarapanList' => $tempatSarapanList,
            'sqffqSkorList' => $sqffqSkorList,
        ];
    }
}

