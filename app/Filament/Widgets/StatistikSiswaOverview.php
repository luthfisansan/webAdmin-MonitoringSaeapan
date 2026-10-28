<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Models\Kelas;
use App\Models\KepatuhanHarian;
use App\Models\Siswa;
use App\Models\SiswaStatus;
use App\Models\SkorPengetahuan;
use App\Models\Sqffq;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class StatistikSiswaOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.statistik-siswa-overview';

    public ?int $bulan = null;

    public ?int $tahun = null;

    public ?int $kelasId = null;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $bulan = Dashboard::resolveBulan($this->bulan);
        $tahun = Dashboard::resolveTahun($this->tahun);
        $kelasId = $this->kelasId ?? (request()->filled('kelas_id') ? request()->integer('kelas_id') : null);

        $namaBulan = Dashboard::daftarBulan()[$bulan] ?? 'Oktober';
        $namaBulanSingkat = Dashboard::daftarBulanSingkat()[$bulan] ?? 'Okt';

        $totalSiswa = Siswa::query()
            ->when($kelasId, fn ($query) => $query->where('kelas_id', $kelasId))
            ->count();

        $totalKelas = Kelas::query()
            ->where('aktif', true)
            ->when($kelasId, fn ($query) => $query->whereKey($kelasId))
            ->count();

        $statusBaseQuery = SiswaStatus::query()
            ->when($kelasId, fn ($query) => $query->where('kelas_id', $kelasId));

        $jumlahPretest = (clone $statusBaseQuery)->where('status_akun_kode', 'PRETEST')->count();
        $jumlahEdukasi = (clone $statusBaseQuery)->where('status_akun_kode', 'EDUKASI')->count();
        $jumlahMenungguVerifikasi = (clone $statusBaseQuery)->where('status_akun_kode', 'MENUNGGU_VERIFIKASI')->count();
        $jumlahTerverifikasi = (clone $statusBaseQuery)->where('status_akun_kode', 'TERVERIFIKASI')->count();

        $jumlahSqffqSelesai = Sqffq::query()
            ->where('status', 'SELESAI')
            ->when($kelasId, fn ($query) => $query->whereHas('siswa', fn ($siswaQuery) => $siswaQuery->where('kelas_id', $kelasId)))
            ->count();

        $awalBulan = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $akhirBulan = $awalBulan->copy()->endOfMonth();
        $jumlahHariBulan = $awalBulan->daysInMonth;

        $kepatuhanBulanQuery = KepatuhanHarian::query()
            ->whereBetween('tanggal', [$awalBulan->toDateString(), $akhirBulan->toDateString()])
            ->when($kelasId, fn ($query) => $query->whereHas('siswa', fn ($siswaQuery) => $siswaQuery->where('kelas_id', $kelasId)));

        $totalHariBulan = (clone $kepatuhanBulanQuery)->count();
        $hariSesuaiBulan = (clone $kepatuhanBulanQuery)->where('status_kepatuhan', 'SESUAI')->count();
        $rataKepatuhanBulan = $totalHariBulan > 0
            ? (int) round(($hariSesuaiBulan / $totalHariBulan) * 100)
            : 0;

        $mingguBulanList = [];
        $mingguKe = 1;

        for ($hariMulai = 1; $hariMulai <= $jumlahHariBulan; $hariMulai += 7) {
            $hariSelesai = min($hariMulai + 6, $jumlahHariBulan);
            $tanggalMulaiMinggu = $awalBulan->copy()->day($hariMulai)->toDateString();
            $tanggalSelesaiMinggu = $awalBulan->copy()->day($hariSelesai)->toDateString();

            $kepatuhanMingguQuery = KepatuhanHarian::query()
                ->whereBetween('tanggal', [$tanggalMulaiMinggu, $tanggalSelesaiMinggu])
                ->when($kelasId, fn ($query) => $query->whereHas('siswa', fn ($siswaQuery) => $siswaQuery->where('kelas_id', $kelasId)));

            $totalHariMinggu = (clone $kepatuhanMingguQuery)->count();
            $hariSesuaiMinggu = (clone $kepatuhanMingguQuery)->where('status_kepatuhan', 'SESUAI')->count();
            $persenMinggu = $totalHariMinggu > 0
                ? (int) round(($hariSesuaiMinggu / $totalHariMinggu) * 100)
                : 0;

            $mingguBulanList[] = [
                'minggu_ke' => $mingguKe,
                'label' => "Minggu {$mingguKe}",
                'rentang' => "{$hariMulai}–{$hariSelesai} {$namaBulanSingkat}",
                'tanggal_mulai' => $tanggalMulaiMinggu,
                'tanggal_selesai' => $tanggalSelesaiMinggu,
                'total_hari' => $totalHariMinggu,
                'hari_sesuai' => $hariSesuaiMinggu,
                'persen' => $persenMinggu,
            ];

            $mingguKe++;
        }

        $skorQuery = SkorPengetahuan::query()
            ->when($kelasId, fn ($query) => $query->whereHas('siswa', fn ($siswaQuery) => $siswaQuery->where('kelas_id', $kelasId)));

        $rataPretest = (int) round((float) ((clone $skorQuery)->avg('skor_pretest') ?? 0));
        $rataPosttest = (int) round((float) ((clone $skorQuery)->avg('skor_posttest') ?? 0));
        $selisihSkor = $rataPosttest - $rataPretest;

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'namaBulan' => $namaBulan,
            'namaBulanSingkat' => $namaBulanSingkat,
            'totalSiswa' => $totalSiswa,
            'totalKelas' => $totalKelas,
            'jumlahPretest' => $jumlahPretest,
            'jumlahEdukasi' => $jumlahEdukasi,
            'jumlahMenungguVerifikasi' => $jumlahMenungguVerifikasi,
            'jumlahTerverifikasi' => $jumlahTerverifikasi,
            'jumlahSqffqSelesai' => $jumlahSqffqSelesai,
            'rataKepatuhanBulan' => $rataKepatuhanBulan,
            'totalHariBulan' => $totalHariBulan,
            'mingguBulanList' => $mingguBulanList,
            'rataPretest' => $rataPretest,
            'rataPosttest' => $rataPosttest,
            'selisihSkor' => $selisihSkor,
        ];
    }

    protected function getStats(): array
    {
        $totalSiswa = Siswa::query()->count();
        $jumlahPretest = SiswaStatus::query()->where('status_akun_kode', 'PRETEST')->count();
        $jumlahEdukasi = SiswaStatus::query()->where('status_akun_kode', 'EDUKASI')->count();
        $jumlahMenungguVerifikasi = SiswaStatus::query()->where('status_akun_kode', 'MENUNGGU_VERIFIKASI')->count();
        $jumlahTerverifikasi = SiswaStatus::query()->where('status_akun_kode', 'TERVERIFIKASI')->count();
        $jumlahSqffqSelesai = Sqffq::query()->where('status', 'SELESAI')->count();

        return [
            Stat::make('Total Siswa', (string) $totalSiswa)
                ->description('Seluruh siswa terdaftar')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Status: Pretest (PRETEST)', (string) $jumlahPretest)
                ->description('Belum menyelesaikan pretest')
                ->icon('heroicon-o-pencil-square')
                ->color('gray'),

            Stat::make('Status: Edukasi (EDUKASI)', (string) $jumlahEdukasi)
                ->description('Wajib membaca materi edukasi')
                ->icon('heroicon-o-book-open')
                ->color('info'),

            Stat::make('Menunggu Verifikasi (MENUNGGU_VERIFIKASI)', (string) $jumlahMenungguVerifikasi)
                ->description('Menunggu wawancara SQ-FFQ')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Terverifikasi (TERVERIFIKASI)', (string) $jumlahTerverifikasi)
                ->description('Aktif mengikuti program')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('SQ-FFQ Selesai', (string) $jumlahSqffqSelesai)
                ->description('Total wawancara SQ-FFQ selesai')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary'),
        ];
    }
}
