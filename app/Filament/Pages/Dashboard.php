<?php

namespace App\Filament\Pages;

use App\Models\FoodRecord;
use App\Models\Kelas;
use App\Models\Program;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    /**
     * @return array<int, string>
     */
    public static function daftarBulan(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function daftarBulanSingkat(): array
    {
        return [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];
    }

    public static function resolveBulan(?int $bulan = null): int
    {
        $nilai = $bulan ?? (request()->filled('bulan') ? request()->integer('bulan') : (int) now()->month);

        if ($nilai < 1 || $nilai > 12) {
            return (int) now()->month;
        }

        return $nilai;
    }

    public static function resolveTahun(?int $tahun = null): int
    {
        $nilai = $tahun ?? (request()->filled('tahun') ? request()->integer('tahun') : (int) now()->year);

        if ($nilai < 2020 || $nilai > 2100) {
            return (int) now()->year;
        }

        return $nilai;
    }

    /**
     * @return array<int, int>
     */
    public static function daftarTahun(): array
    {
        $tahunSekarang = (int) now()->year;
        $tahunList = range($tahunSekarang - 1, $tahunSekarang + 1);

        $minProgram = Program::query()->min('tanggal_mulai');
        if ($minProgram) {
            $tahunList[] = (int) Carbon::parse($minProgram)->year;
        }

        $minFoodRecord = FoodRecord::query()->min('tanggal');
        if ($minFoodRecord) {
            $tahunList[] = (int) Carbon::parse($minFoodRecord)->year;
        }

        $tahunAktif = static::resolveTahun();
        $tahunList[] = $tahunAktif;

        $unik = array_values(array_unique($tahunList));
        sort($unik);

        return $unik;
    }

    /**
     * @return array{
     *     daftarBulan: array<int, string>,
     *     daftarTahun: array<int, int>,
     *     daftarKelas: Collection<int, Kelas>,
     *     bulanAktif: int,
     *     tahunAktif: int,
     *     kelasIdAktif: ?int
     * }
     */
    public static function filterViewData(): array
    {
        return [
            'daftarBulan' => static::daftarBulan(),
            'daftarTahun' => static::daftarTahun(),
            'daftarKelas' => Kelas::query()->where('aktif', true)->orderBy('nama_kelas')->get(),
            'bulanAktif' => static::resolveBulan(),
            'tahunAktif' => static::resolveTahun(),
            'kelasIdAktif' => request()->filled('kelas_id') ? request()->integer('kelas_id') : null,
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        $bulan = static::resolveBulan();
        $tahun = static::resolveTahun();
        $namaBulan = static::daftarBulan()[$bulan] ?? 'Oktober';

        return "Ringkasan penelitian program sarapan · Periode {$namaBulan} {$tahun}";
    }

    /**
     * @return int | array<string, ?int>
     */
    public function getColumns(): int|array
    {
        return 1;
    }
}
